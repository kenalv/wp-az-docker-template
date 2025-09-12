#!/bin/bash
set -e

# Script de inicialización para WordPress en Azure App Service

echo "🚀 Iniciando WordPress en Azure App Service..."

# Establecer permisos correctos
chown -R www-data:www-data /var/www/html
find /var/www/html -type d -exec chmod 755 {} \;
find /var/www/html -type f -exec chmod 644 {} \;

# Verificar conexión a la base de datos
echo "🔍 Verificando conexión a la base de datos..."
php -r "
try {
    \$pdo = new PDO('mysql:host=' . getenv('DB_HOST') . ';dbname=' . getenv('DB_NAME'), getenv('DB_USER'), getenv('DB_PASSWORD'));
    echo '✅ Conexión a la base de datos exitosa\n';
} catch (Exception \$e) {
    echo '❌ Error conectando a la base de datos: ' . \$e->getMessage() . '\n';
    exit(1);
}
"

# Verificar conexión a Redis si está configurado
if [ ! -z "$REDIS_URL" ]; then
    echo "🔍 Verificando conexión a Redis..."
    php -r "
    try {
        \$redis = new Redis();
        \$redis->connect(getenv('WP_REDIS_HOST'), getenv('WP_REDIS_PORT'));
        if (getenv('WP_REDIS_PASSWORD')) {
            \$redis->auth(getenv('WP_REDIS_PASSWORD'));
        }
        \$redis->ping();
        echo '✅ Conexión a Redis exitosa\n';
        \$redis->close();
    } catch (Exception \$e) {
        echo '⚠️  Warning: No se pudo conectar a Redis: ' . \$e->getMessage() . '\n';
    }
    "
fi

# Instalar WordPress Core si no existe
if [ ! -f "/var/www/html/wp-config.php" ]; then
    echo "📦 Instalando WordPress Core..."
    wp core download --path=/var/www/html --allow-root
    cp /var/www/html/wp-config.php.bak /var/www/html/wp-config.php 2>/dev/null || true
fi

# Instalar plugins recomendados para Azure
echo "🔌 Instalando plugins recomendados..."
PLUGINS=(
    "redis-cache"
    "w3-total-cache"
    "wp-super-cache"
    "azure-storage"
    "health-check"
)

for plugin in "${PLUGINS[@]}"; do
    if ! wp plugin is-installed $plugin --path=/var/www/html --allow-root; then
        echo "📦 Instalando plugin: $plugin"
        wp plugin install $plugin --path=/var/www/html --allow-root --quiet || echo "⚠️  No se pudo instalar $plugin"
    fi
done

# Activar caché Redis si está disponible
if [ ! -z "$REDIS_URL" ]; then
    echo "🔄 Configurando Redis Cache..."
    wp plugin activate redis-cache --path=/var/www/html --allow-root --quiet || true
    wp redis enable --path=/var/www/html --allow-root --quiet || true
fi

echo "✅ Inicialización completa. Iniciando Apache..."

# Ejecutar el comando original
exec "$@"