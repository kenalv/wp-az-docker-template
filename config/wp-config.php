<?php
/**
 * WordPress Configuration for Azure App Service
 * 
 * This configuration supports both local development and Azure production environment
 * with MySQL Flexible Server, Redis Cache, and Blob Storage
 */

// Environment detection
$environment = getenv('WP_ENVIRONMENT_TYPE') ?: 'production';
$is_azure = !empty(getenv('WEBSITE_SITE_NAME'));

// ** Database settings ** //
if ($is_azure) {
    // Azure MySQL Flexible Server configuration
    define('DB_NAME', getenv('MYSQL_DATABASE'));
    define('DB_USER', getenv('MYSQL_USERNAME'));
    define('DB_PASSWORD', getenv('MYSQL_PASSWORD'));
    define('DB_HOST', getenv('MYSQL_HOST') . ':' . (getenv('MYSQL_PORT') ?: '3306'));
    
    // SSL configuration for Azure MySQL
    define('MYSQL_CLIENT_FLAGS', MYSQLI_CLIENT_SSL);
    define('MYSQL_SSL_CA', '/usr/local/share/ca-certificates/DigiCertGlobalRootCA.crt.pem');
} else {
    // Local development database
    define('DB_NAME', getenv('WORDPRESS_DB_NAME') ?: 'wordpress');
    define('DB_USER', getenv('WORDPRESS_DB_USER') ?: 'wordpress');
    define('DB_PASSWORD', getenv('WORDPRESS_DB_PASSWORD') ?: 'wordpress_password');
    define('DB_HOST', getenv('WORDPRESS_DB_HOST') ?: 'mysql:3306');
}

define('DB_CHARSET', 'utf8mb4');
define('DB_COLLATE', '');

// ** Custom database connection for Azure SSL ** //
if ($is_azure && !defined('DB_SSL_CA')) {
    define('DB_SSL_CA', '/usr/local/share/ca-certificates/DigiCertGlobalRootCA.crt.pem');
    define('DB_SSL_VERIFY', false); // Set to true for strict verification
}

// ** Table prefix ** //
$table_prefix = getenv('WORDPRESS_TABLE_PREFIX') ?: 'wp_';

// ** Authentication keys and salts ** //
// You should generate these at https://api.wordpress.org/secret-key/1.1/salt/
define('AUTH_KEY',         getenv('WP_AUTH_KEY') ?: 'put your unique phrase here');
define('SECURE_AUTH_KEY',  getenv('WP_SECURE_AUTH_KEY') ?: 'put your unique phrase here');
define('LOGGED_IN_KEY',    getenv('WP_LOGGED_IN_KEY') ?: 'put your unique phrase here');
define('NONCE_KEY',        getenv('WP_NONCE_KEY') ?: 'put your unique phrase here');
define('AUTH_SALT',        getenv('WP_AUTH_SALT') ?: 'put your unique phrase here');
define('SECURE_AUTH_SALT', getenv('WP_SECURE_AUTH_SALT') ?: 'put your unique phrase here');
define('LOGGED_IN_SALT',   getenv('WP_LOGGED_IN_SALT') ?: 'put your unique phrase here');
define('NONCE_SALT',       getenv('WP_NONCE_SALT') ?: 'put your unique phrase here');

// ** Azure Blob Storage Configuration ** //
if ($is_azure && getenv('AZURE_STORAGE_ACCOUNT')) {
    define('AZURE_STORAGE_ACCOUNT', getenv('AZURE_STORAGE_ACCOUNT'));
    define('AZURE_STORAGE_KEY', getenv('AZURE_STORAGE_KEY'));
    define('AZURE_STORAGE_CONTAINER', getenv('AZURE_STORAGE_CONTAINER') ?: 'media');
    define('AZURE_STORAGE_URL', 'https://' . AZURE_STORAGE_ACCOUNT . '.blob.core.windows.net/' . AZURE_STORAGE_CONTAINER);
}

// ** Redis Cache Configuration ** //
if (getenv('REDIS_URL')) {
    $redis_url = parse_url(getenv('REDIS_URL'));
    define('WP_REDIS_HOST', $redis_url['host']);
    define('WP_REDIS_PORT', $redis_url['port']);
    if (isset($redis_url['pass'])) {
        define('WP_REDIS_PASSWORD', $redis_url['pass']);
    }
    define('WP_REDIS_DATABASE', 0);
    define('WP_REDIS_TIMEOUT', 1);
    define('WP_REDIS_READ_TIMEOUT', 1);
    define('WP_CACHE', true);
}

// ** WordPress debugging ** //
if ($environment === 'development') {
    define('WP_DEBUG', true);
    define('WP_DEBUG_LOG', true);
    define('WP_DEBUG_DISPLAY', true);
    define('SCRIPT_DEBUG', true);
} else {
    define('WP_DEBUG', false);
    define('WP_DEBUG_LOG', false);
    define('WP_DEBUG_DISPLAY', false);
}

// ** Security enhancements ** //
define('DISALLOW_FILE_EDIT', true);
define('DISALLOW_FILE_MODS', false);
define('FORCE_SSL_ADMIN', $is_azure);
define('AUTOMATIC_UPDATER_DISABLED', false);

// ** Performance optimizations ** //
define('WP_MEMORY_LIMIT', '512M');
define('WP_MAX_MEMORY_LIMIT', '512M');
define('WP_POST_REVISIONS', 5);
define('AUTOSAVE_INTERVAL', 300);

// ** URL Configuration ** //
if ($is_azure) {
    define('WP_HOME', 'https://' . getenv('WEBSITE_SITE_NAME') . '.azurewebsites.net');
    define('WP_SITEURL', 'https://' . getenv('WEBSITE_SITE_NAME') . '.azurewebsites.net');
} else {
    define('WP_HOME', 'http://localhost:8080');
    define('WP_SITEURL', 'http://localhost:8080');
}

// ** File system configuration ** //
define('FS_METHOD', 'direct');

// ** Multisite configuration (if needed) ** //
// define('WP_ALLOW_MULTISITE', true);

// ** Language configuration ** //
define('WPLANG', getenv('WP_LANG') ?: '');

// ** Plugin specific configurations ** //
// W3 Total Cache
if (defined('WP_CACHE') && WP_CACHE) {
    define('WP_CACHE_KEY_SALT', getenv('WEBSITE_SITE_NAME') ?: 'WP-AZ-DOCKER-TEMPLATE');
}

/* That's all, stop editing! Happy publishing. */

/** Absolute path to the WordPress directory. */
if (!defined('ABSPATH')) {
    define('ABSPATH', dirname(__FILE__) . '/');
}

/** Sets up WordPress vars and included files. */
require_once ABSPATH . 'wp-settings.php';

// Load custom SSL configuration for Azure MySQL
if ($is_azure && file_exists(ABSPATH . 'db-ssl-config.php')) {
    require_once ABSPATH . 'db-ssl-config.php';
}