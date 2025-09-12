<?php
/**
 * Custom database handler for Azure MySQL SSL connections
 * This file overrides WordPress default database connection to support SSL
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    die('Direct access not permitted.');
}

// Only override if we're in Azure and have SSL configuration
if ((!empty(getenv('WEBSITE_SITE_NAME')) || !empty(getenv('AZURE_ENVIRONMENT'))) && defined('MYSQL_SSL_CA')) {
    
    // Override the wpdb class to support SSL connections
    class Custom_wpdb extends wpdb {
        
        public function db_connect($allow_bail = true) {
            $this->is_mysql = true;

            $client_flags = defined('MYSQL_CLIENT_FLAGS') ? MYSQL_CLIENT_FLAGS : 0;
            
            if ($this->use_mysqli) {
                $this->dbh = mysqli_init();
                
                // Set SSL options for Azure MySQL
                if (defined('MYSQL_SSL_CA')) {
                    mysqli_ssl_set(
                        $this->dbh,
                        null,        // key
                        null,        // cert
                        MYSQL_SSL_CA,// ca
                        null,        // capath
                        null         // cipher
                    );
                }
                
                // Enable SSL and disable server certificate verification for Azure
                mysqli_options($this->dbh, MYSQLI_OPT_SSL_VERIFY_SERVER_CERT, false);
                
                $host = $this->dbhost;
                $port = null;
                
                // Parse port from host if present
                if (strpos($this->dbhost, ':') !== false) {
                    list($host, $port) = explode(':', $this->dbhost, 2);
                    $port = (int) $port;
                }
                
                // Attempt connection with SSL
                if (mysqli_real_connect($this->dbh, $host, $this->dbuser, $this->dbpassword, $this->dbname, $port, null, $client_flags)) {
                    $this->has_connected = true;
                    $this->set_charset($this->dbh);
                    $this->ready = true;
                    $this->set_sql_mode();
                    $this->select($this->dbname, $this->dbh);
                    return true;
                }
            }
            
            // If SSL connection failed, try parent method
            return parent::db_connect($allow_bail);
        }
    }
    
    // Replace global $wpdb with our custom class
    if (!isset($wpdb)) {
        $wpdb = new Custom_wpdb(DB_USER, DB_PASSWORD, DB_NAME, DB_HOST);
    }
}