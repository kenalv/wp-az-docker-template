#!/bin/bash
set -e

# Script de inicialización para WordPress en Azure App Service

echo "🚀 Iniciando WordPress en Azure App Service..."

# Establecer permisos correctos
chown -R www-data:www-data /var/www/html
find /var/www/html -type d -exec chmod 755 {} \;
find /var/www/html -type f -exec chmod 644 {} \;

# Verificar que las variables de entorno estén configuradas
echo "🔍 Verificando variables de entorno..."
REQUIRED_VARS=("MYSQL_DATABASE" "MYSQL_USERNAME" "MYSQL_PASSWORD" "MYSQL_HOST")
for var in "${REQUIRED_VARS[@]}"; do
    if [ -z "${!var}" ]; then
        echo "❌ Error: Variable de entorno $var no está configurada"
        exit 1
    fi
done
echo "✅ Variables de entorno configuradas correctamente"

# Verificar conexión a la base de datos usando variables correctas
echo "🔍 Verificando conexión a la base de datos..."
php -r "
try {
    \$host = getenv('MYSQL_HOST');
    \$port = getenv('MYSQL_PORT') ?: '3306';
    \$dbname = getenv('MYSQL_DATABASE');
    \$username = getenv('MYSQL_USERNAME');
    \$password = getenv('MYSQL_PASSWORD');
    
    \$dsn = \"mysql:host=\$host;\$port;\dbname=\$dbname;charset=utf8mb4\";
    \$options = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ];
    
    // Add SSL options for Azure MySQL
    if (!empty(getenv('WEBSITE_SITE_NAME')) || !empty(getenv('AZURE_ENVIRONMENT'))) {
        \$options[PDO::MYSQL_ATTR_SSL_CA] = '/usr/local/share/ca-certificates/DigiCertGlobalRootCA.crt.pem';
        \$options[PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT] = false;
    }
    
    \$pdo = new PDO(\$dsn, \$username, \$password, \$options);
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
        \$redis_url = parse_url(getenv('REDIS_URL'));
        \$redis = new Redis();
        \$redis->connect(\$redis_url['host'], \$redis_url['port']);
        if (isset(\$redis_url['pass'])) {
            \$redis->auth(\$redis_url['pass']);
        }
        \$redis->ping();
        echo '✅ Conexión a Redis exitosa\n';
        \$redis->close();
    } catch (Exception \$e) {
        echo '⚠️  Warning: No se pudo conectar a Redis: ' . \$e->getMessage() . '\n';
    }
    "
fi

# Verificar que wp-config.php existe
if [ ! -f "/var/www/html/wp-config.php" ]; then
    echo "❌ Error: wp-config.php no encontrado"
    exit 1
fi

echo "✅ Inicialización completa. Iniciando Apache..."

# Ejecutar el comando original
exec "$@"