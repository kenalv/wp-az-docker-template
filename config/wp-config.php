<?php
// Configuración SSL para Azure Web App
if (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https') {
    $_SERVER['HTTPS'] = 'on';
}

// Configuración de URLs para HTTPS
define('WP_HOME', 'https://' . $_SERVER['HTTP_HOST']);
define('WP_SITEURL', 'https://' . $_SERVER['HTTP_HOST']);

// Forzar HTTPS en admin
define('FORCE_SSL_ADMIN', true);

// Configuración de contenido para HTTPS
define('WP_CONTENT_URL', 'https://' . $_SERVER['HTTP_HOST'] . '/wp-content');

// Configuración de base de datos desde variables de entorno
define('DB_HOST', getenv('MYSQL_HOST'));
define('DB_USER', getenv('MYSQL_USER'));
define('DB_PASSWORD', getenv('MYSQL_PASSWORD'));
define('DB_NAME', getenv('MYSQL_DATABASE'));
define('DB_CHARSET', 'utf8mb4');
define('DB_COLLATE', '');
define('MYSQL_SSL_CA', '/var/www/html/ssl/DigiCertGlobalRootCA.crt.pem');
define('MYSQL_CLIENT_FLAGS', MYSQLI_CLIENT_SSL | MYSQLI_CLIENT_SSL_DONT_VERIFY_SERVER_CERT);
// Prefijo de tablas
$table_prefix = 'wp_';

// Configuración de Redis si está disponible
if (getenv('AZURE_REDIS_HOST')) {
    define('WP_REDIS_HOST', getenv('AZURE_REDIS_HOST'));
    define('WP_REDIS_PORT', getenv('AZURE_REDIS_PORT') ?: 6380);
    define('WP_REDIS_PASSWORD', getenv('AZURE_REDIS_PASSWORD'));
    define('WP_REDIS_TIMEOUT', 1);
    define('WP_REDIS_READ_TIMEOUT', 1);
    define('WP_REDIS_DATABASE', 0);
}

// JWT Configuration
if (getenv('JWT_SECRET_KEY')) {
    define('JWT_AUTH_SECRET_KEY', getenv('JWT_SECRET_KEY'));
    define('JWT_AUTH_CORS_ENABLE', true);
}

// Azure Storage Configuration
if (getenv('AZURE_STORAGE_ACCOUNT')) {
    define('AZURE_STORAGE_ACCOUNT', getenv('AZURE_STORAGE_ACCOUNT'));
    define('AZURE_STORAGE_KEY', getenv('AZURE_STORAGE_KEY'));
    define('AZURE_STORAGE_CONTAINER', getenv('AZURE_STORAGE_CONTAINER') ?: 'wordpress-media');
    define('AZURE_STORAGE_URL', getenv('AZURE_STORAGE_URL'));
}

// Debug configuration (disable in production)
define('WP_DEBUG', false);
define('WP_DEBUG_LOG', false);
define('WP_DEBUG_DISPLAY', false);
define('SCRIPT_DEBUG', false);

// Memory limits
define('WP_MEMORY_LIMIT', '512M');

// Security keys (se generan automáticamente si no existen)
if (!defined('AUTH_KEY'))         define('AUTH_KEY',         getenv('WORDPRESS_AUTH_KEY') ?: 'put your unique phrase here');
if (!defined('SECURE_AUTH_KEY'))  define('SECURE_AUTH_KEY',  getenv('WORDPRESS_SECURE_AUTH_KEY') ?: 'put your unique phrase here');
if (!defined('LOGGED_IN_KEY'))    define('LOGGED_IN_KEY',    getenv('WORDPRESS_LOGGED_IN_KEY') ?: 'put your unique phrase here');
if (!defined('NONCE_KEY'))        define('NONCE_KEY',        getenv('WORDPRESS_NONCE_KEY') ?: 'put your unique phrase here');
if (!defined('AUTH_SALT'))        define('AUTH_SALT',        getenv('WORDPRESS_AUTH_SALT') ?: 'put your unique phrase here');
if (!defined('SECURE_AUTH_SALT')) define('SECURE_AUTH_SALT', getenv('WORDPRESS_SECURE_AUTH_SALT') ?: 'put your unique phrase here');
if (!defined('LOGGED_IN_SALT'))   define('LOGGED_IN_SALT',   getenv('WORDPRESS_LOGGED_IN_SALT') ?: 'put your unique phrase here');
if (!defined('NONCE_SALT'))       define('NONCE_SALT',       getenv('WORDPRESS_NONCE_SALT') ?: 'put your unique phrase here');

// Absolute path to WordPress directory
if (!defined('ABSPATH')) {
    define('ABSPATH', __DIR__ . '/');
}

// Sets up WordPress vars and included files
require_once ABSPATH . 'wp-settings.php';