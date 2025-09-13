#!/bin/bash
set -e

# Script de inicialización para WordPress en Azure App Service

echo "🚀 Iniciando WordPress en Azure App Service..."

# Establecer permisos correctos
chown -R www-data:www-data /var/www/html
find /var/www/html -type d -exec chmod 755 {} \;
find /var/www/html -type f -exec chmod 644 {} \;

# Copiar wp-config.php personalizado si no existe o si es diferente
if [ -f "/tmp/wp-config-custom.php" ]; then
    echo "📝 Configurando wp-config.php personalizado..."
    cp /tmp/wp-config-custom.php /var/www/html/wp-config.php
    chown www-data:www-data /var/www/html/wp-config.php
    chmod 644 /var/www/html/wp-config.php
fi

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
    
    \$dsn = \"mysql:host=\$host:\$port;dbname=\$dbname;charset=utf8mb4\";
    \$options = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ];
    
    // Add SSL options for Azure MySQL
    if (!empty(getenv('WEBSITE_SITE_NAME')) || !empty(getenv('AZURE_ENVIRONMENT'))) {
        \$ssl_ca = '/usr/local/share/ca-certificates/DigiCertGlobalRootCA.crt.pem';
        if (file_exists(\$ssl_ca)) {
            \$options[PDO::MYSQL_ATTR_SSL_CA] = \$ssl_ca;
            \$options[PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT] = false;
        }
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

# Verificar que los archivos principales de WordPress existan
echo "🔍 Verificando instalación de WordPress..."
REQUIRED_FILES=("wp-load.php" "wp-admin/index.php" "wp-includes/version.php")
missing_files=0
for file in "${REQUIRED_FILES[@]}"; do
    if [ ! -f "/var/www/html/$file" ]; then
        echo "⚠️  Warning: Archivo WordPress no encontrado: $file"
        missing_files=1
    fi
done

# Si faltan archivos críticos, descargar WordPress
if [ $missing_files -eq 1 ]; then
    echo "📦 Descargando WordPress completo..."
    cd /tmp
    wget -q https://wordpress.org/latest.tar.gz
    if [ $? -eq 0 ]; then
        tar -xzf latest.tar.gz
        cp -rf wordpress/* /var/www/html/
        rm -rf wordpress latest.tar.gz
        echo "✅ WordPress descargado e instalado correctamente"
        chown -R www-data:www-data /var/www/html
        chmod -R 755 /var/www/html
    else
        echo "❌ Error descargando WordPress, usando página de debug"
        cp /tmp/debug-index.php /var/www/html/index.php
        chown www-data:www-data /var/www/html/index.php
        chmod 644 /var/www/html/index.php
    fi
else
    echo "✅ WordPress ya está instalado"
fi

echo "✅ Verificaciones completas. WordPress listo para iniciar."
echo "✅ Inicialización completa. Iniciando Apache..."

# Ejecutar el comando original
exec "$@"