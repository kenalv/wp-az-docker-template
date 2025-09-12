<?php
/**
 * Custom database connection with SSL support for Azure MySQL
 */

if (!defined('ABSPATH')) {
    die('Direct access not permitted.');
}

// Hook into WordPress database connection
add_action('wp_loaded', function() {
    global $wpdb;
    
    // Only apply SSL configuration for Azure MySQL
    if (!empty(getenv('WEBSITE_SITE_NAME')) && defined('DB_SSL_CA')) {
        // Force SSL connection for Azure MySQL Flexible Server
        $wpdb->ssl_ca = DB_SSL_CA;
        $wpdb->ssl_verify = defined('DB_SSL_VERIFY') ? DB_SSL_VERIFY : false;
        
        // Add SSL context to mysqli connection
        add_filter('wp_mysql_connect', function($mysql_connection) {
            if ($mysql_connection instanceof mysqli) {
                $mysql_connection->ssl_set(null, null, DB_SSL_CA, null, null);
                $mysql_connection->options(MYSQLI_OPT_SSL_VERIFY_SERVER_CERT, false);
            }
            return $mysql_connection;
        });
    }
});