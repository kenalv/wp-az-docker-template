<?php
/**
 * Test file to verify PHP is working
 * This file can be deleted after WordPress is fully configured
 */

// Redirect to WordPress if it's installed and working
if (file_exists('wp-config.php') && file_exists('wp-load.php')) {
    // Try to load WordPress
    try {
        define('WP_USE_THEMES', true);
        require_once('wp-load.php');
        exit; // WordPress will handle everything from here
    } catch (Exception $e) {
        // If WordPress fails, show debug info below
        $wp_error = $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>WordPress Loading...</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 40px; background: #f1f1f1; }
        .container { background: white; padding: 30px; border-radius: 8px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); }
        .status { padding: 10px; margin: 10px 0; border-radius: 4px; }
        .success { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .warning { background: #fff3cd; color: #856404; border: 1px solid #ffeaa7; }
        .error { background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
        .info { background: #d1ecf1; color: #0c5460; border: 1px solid #bee5eb; }
    </style>
</head>
<body>
    <div class="container">
        <h1>🚀 WordPress on Azure App Service</h1>
        
        <?php if (isset($wp_error)): ?>
            <div class="status error">
                <strong>WordPress Error:</strong> <?php echo htmlspecialchars($wp_error); ?>
            </div>
        <?php endif; ?>
        
        <div class="status info">
            <strong>Status:</strong> PHP is working correctly!<br>
            <strong>PHP Version:</strong> <?php echo phpversion(); ?><br>
            <strong>Server Time:</strong> <?php echo date('Y-m-d H:i:s'); ?>
        </div>
        
        <?php
        // Check database connection
        try {
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
                echo '<div class="status success"><strong>Database:</strong> Connected successfully!</div>';
            } else {
                echo '<div class="status warning"><strong>Database:</strong> Environment variables not configured</div>';
            }
        } catch (Exception $e) {
            echo '<div class="status error"><strong>Database Error:</strong> ' . htmlspecialchars($e->getMessage()) . '</div>';
        }
        
        // Check WordPress files
        $wp_files = ['wp-config.php', 'wp-load.php', 'wp-admin/index.php'];
        $missing_files = [];
        foreach ($wp_files as $file) {
            if (!file_exists($file)) {
                $missing_files[] = $file;
            }
        }
        
        if (empty($missing_files)) {
            echo '<div class="status success"><strong>WordPress Files:</strong> All core files present</div>';
        } else {
            echo '<div class="status error"><strong>WordPress Files:</strong> Missing files: ' . implode(', ', $missing_files) . '</div>';
        }
        ?>
        
        <div style="margin-top: 20px;">
            <strong>Next Steps:</strong>
            <ol>
                <li>If database is connected and WordPress files are present, try refreshing the page</li>
                <li>Check <a href="/health.php" target="_blank">/health.php</a> for detailed health status</li>
                <li>If issues persist, check Azure App Service logs</li>
            </ol>
        </div>
    </div>
</body>
</html>