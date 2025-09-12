<?php
/**
 * Health Check Endpoint for Azure App Service
 * This file provides a simple health check for Azure to verify the application is running
 */

header('Content-Type: application/json');

$health = [
    'status' => 'healthy',
    'timestamp' => date('c'),
    'checks' => []
];

try {
    // Check database connection
    if (defined('DB_HOST') && defined('DB_NAME') && defined('DB_USER') && defined('DB_PASSWORD')) {
        $pdo = new PDO('mysql:host=' . DB_HOST . ';dbname=' . DB_NAME, DB_USER, DB_PASSWORD, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_TIMEOUT => 5,
        ]);
        $health['checks']['database'] = 'connected';
    } else {
        // Try with environment variables if constants not defined
        $host = getenv('MYSQL_HOST');
        $port = getenv('MYSQL_PORT') ?: '3306';
        $dbname = getenv('MYSQL_DATABASE');
        $username = getenv('MYSQL_USERNAME');
        $password = getenv('MYSQL_PASSWORD');
        
        if ($host && $dbname && $username && $password) {
            $dsn = "mysql:host=$host:$port;dbname=$dbname;charset=utf8mb4";
            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_TIMEOUT => 5,
            ];
            
            // Add SSL options for Azure MySQL
            if (!empty(getenv('WEBSITE_SITE_NAME')) || !empty(getenv('AZURE_ENVIRONMENT'))) {
                $ssl_ca = '/usr/local/share/ca-certificates/DigiCertGlobalRootCA.crt.pem';
                if (file_exists($ssl_ca)) {
                    $options[PDO::MYSQL_ATTR_SSL_CA] = $ssl_ca;
                    $options[PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT] = false;
                }
            }
            
            $pdo = new PDO($dsn, $username, $password, $options);
            $health['checks']['database'] = 'connected';
        } else {
            $health['checks']['database'] = 'not_configured';
        }
    }
} catch (Exception $e) {
    $health['status'] = 'unhealthy';
    $health['checks']['database'] = 'failed: ' . $e->getMessage();
}

// Check Redis connection if configured
if (getenv('REDIS_URL')) {
    try {
        $redis_url = parse_url(getenv('REDIS_URL'));
        $redis = new Redis();
        $redis->connect($redis_url['host'], $redis_url['port'], 2); // 2 second timeout
        if (isset($redis_url['pass'])) {
            $redis->auth($redis_url['pass']);
        }
        $redis->ping();
        $health['checks']['redis'] = 'connected';
        $redis->close();
    } catch (Exception $e) {
        $health['checks']['redis'] = 'failed: ' . $e->getMessage();
    }
} else {
    $health['checks']['redis'] = 'not_configured';
}

// Check file system
$health['checks']['filesystem'] = is_writable('/var/www/html/wp-content') ? 'writable' : 'readonly';

// Check SSL certificate
$ssl_ca = '/usr/local/share/ca-certificates/DigiCertGlobalRootCA.crt.pem';
$health['checks']['ssl_cert'] = file_exists($ssl_ca) ? 'present' : 'missing';

// Overall status
if ($health['checks']['database'] !== 'connected' && $health['checks']['database'] !== 'not_configured') {
    $health['status'] = 'unhealthy';
}

http_response_code($health['status'] === 'healthy' ? 200 : 503);
echo json_encode($health, JSON_PRETTY_PRINT);