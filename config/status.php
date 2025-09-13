<?php
// Simple health check
header('Content-Type: text/plain');
echo "WordPress Container Status: RUNNING\n";
echo "Timestamp: " . date('Y-m-d H:i:s') . "\n";
echo "PHP Version: " . phpversion() . "\n";

// Check MySQL connection
$mysql_host = getenv('MYSQL_HOST');
if ($mysql_host) {
    echo "MySQL Host: " . $mysql_host . "\n";
    try {
        $pdo = new PDO('mysql:host=' . $mysql_host . ';dbname=' . getenv('MYSQL_DATABASE'), 
                       getenv('MYSQL_USERNAME'), 
                       getenv('MYSQL_PASSWORD'));
        echo "Database: CONNECTED\n";
    } catch (Exception $e) {
        echo "Database: ERROR - " . $e->getMessage() . "\n";
    }
} else {
    echo "Database: NOT CONFIGURED\n";
}
?>