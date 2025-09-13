<?php
/**
 * Instalación automática de WordPress para Azure App Service
 * Este script crea las tablas de WordPress y configura la instalación inicial
 */

// Prevenir ejecución directa fuera del contexto correcto
if (!defined('ABSPATH')) {
    define('ABSPATH', dirname(__FILE__, 2) . '/');
}

// Configuración de la base de datos desde variables de entorno
$db_host = getenv('MYSQL_HOST');
$db_port = getenv('MYSQL_PORT') ?: '3306';
$db_name = getenv('MYSQL_DATABASE');
$db_user = getenv('MYSQL_USERNAME');
$db_password = getenv('MYSQL_PASSWORD');

// Configuración de WordPress
$site_title = 'WordPress en Azure App Service';
$admin_user = 'admin';
$admin_pass = 'Admin@123!'; // Cambiar después de la instalación
$admin_email = 'admin@example.com';
$site_url = 'https://' . ($_SERVER['HTTP_HOST'] ?? 'localhost');

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Instalación de WordPress</title>
    <style>
        body { font-family: -apple-system, BlinkMacSystemFont, sans-serif; margin: 0; padding: 20px; background: #f1f1f1; }
        .container { max-width: 600px; margin: 0 auto; background: white; padding: 30px; border-radius: 8px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); }
        .status { padding: 15px; margin: 15px 0; border-radius: 4px; }
        .success { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .error { background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
        .info { background: #d1ecf1; color: #0c5460; border: 1px solid #bee5eb; }
        .progress { background: #fff3cd; color: #856404; border: 1px solid #ffeaa7; }
        button { background: #0073aa; color: white; border: none; padding: 12px 20px; border-radius: 4px; cursor: pointer; }
        button:hover { background: #005a87; }
        .code { background: #f8f9fa; padding: 10px; border-radius: 4px; font-family: monospace; margin: 10px 0; }
    </style>
</head>
<body>
    <div class="container">
        <h1>🚀 Instalación de WordPress en Azure</h1>
        
        <?php
        if (isset($_POST['install']) && $_POST['install'] === 'true') {
            echo '<div class="progress">📦 Iniciando instalación de WordPress...</div>';
            
            try {
                // Conectar a la base de datos
                $dsn = "mysql:host=$db_host:$db_port;dbname=$db_name;charset=utf8mb4";
                $options = [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                ];
                
                // SSL para Azure MySQL
                $ssl_ca = '/usr/local/share/ca-certificates/DigiCertGlobalRootCA.crt.pem';
                if (file_exists($ssl_ca)) {
                    $options[PDO::MYSQL_ATTR_SSL_CA] = $ssl_ca;
                    $options[PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT] = false;
                }
                
                $pdo = new PDO($dsn, $db_user, $db_password, $options);
                echo '<div class="success">✅ Conexión a base de datos exitosa</div>';
                
                // Definir constantes de WordPress
                define('DB_NAME', $db_name);
                define('DB_USER', $db_user);
                define('DB_PASSWORD', $db_password);
                define('DB_HOST', "$db_host:$db_port");
                define('DB_CHARSET', 'utf8mb4');
                define('DB_COLLATE', '');
                
                // Definir tabla prefix
                $table_prefix = 'wp_';
                
                // Incluir funciones de WordPress necesarias
                if (!defined('WP_INSTALLING')) {
                    define('WP_INSTALLING', true);
                }
                
                // Cargar funciones de instalación
                require_once ABSPATH . 'wp-admin/includes/upgrade.php';
                require_once ABSPATH . 'wp-includes/wp-db.php';
                
                // Crear las tablas de WordPress
                echo '<div class="progress">📋 Creando tablas de WordPress...</div>';
                
                // SQL para crear tablas principales
                $tables_sql = "
                CREATE TABLE IF NOT EXISTS {$table_prefix}options (
                    option_id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
                    option_name varchar(191) NOT NULL DEFAULT '',
                    option_value longtext NOT NULL,
                    autoload varchar(20) NOT NULL DEFAULT 'yes',
                    PRIMARY KEY (option_id),
                    UNIQUE KEY option_name (option_name)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
                
                CREATE TABLE IF NOT EXISTS {$table_prefix}users (
                    ID bigint(20) unsigned NOT NULL AUTO_INCREMENT,
                    user_login varchar(60) NOT NULL DEFAULT '',
                    user_pass varchar(255) NOT NULL DEFAULT '',
                    user_nicename varchar(50) NOT NULL DEFAULT '',
                    user_email varchar(100) NOT NULL DEFAULT '',
                    user_url varchar(100) NOT NULL DEFAULT '',
                    user_registered datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
                    user_activation_key varchar(255) NOT NULL DEFAULT '',
                    user_status int(11) NOT NULL DEFAULT '0',
                    display_name varchar(250) NOT NULL DEFAULT '',
                    PRIMARY KEY (ID),
                    KEY user_login_key (user_login),
                    KEY user_nicename (user_nicename),
                    KEY user_email (user_email)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
                
                CREATE TABLE IF NOT EXISTS {$table_prefix}posts (
                    ID bigint(20) unsigned NOT NULL AUTO_INCREMENT,
                    post_author bigint(20) unsigned NOT NULL DEFAULT '0',
                    post_date datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
                    post_date_gmt datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
                    post_content longtext NOT NULL,
                    post_title text NOT NULL,
                    post_excerpt text NOT NULL,
                    post_status varchar(20) NOT NULL DEFAULT 'publish',
                    comment_status varchar(20) NOT NULL DEFAULT 'open',
                    ping_status varchar(20) NOT NULL DEFAULT 'open',
                    post_password varchar(255) NOT NULL DEFAULT '',
                    post_name varchar(200) NOT NULL DEFAULT '',
                    to_ping text NOT NULL,
                    pinged text NOT NULL,
                    post_modified datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
                    post_modified_gmt datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
                    post_content_filtered longtext NOT NULL,
                    post_parent bigint(20) unsigned NOT NULL DEFAULT '0',
                    guid varchar(255) NOT NULL DEFAULT '',
                    menu_order int(11) NOT NULL DEFAULT '0',
                    post_type varchar(20) NOT NULL DEFAULT 'post',
                    post_mime_type varchar(100) NOT NULL DEFAULT '',
                    comment_count bigint(20) NOT NULL DEFAULT '0',
                    PRIMARY KEY (ID),
                    KEY post_name (post_name(191)),
                    KEY type_status_date (post_type,post_status,post_date,ID),
                    KEY post_parent (post_parent),
                    KEY post_author (post_author)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
                ";
                
                // Ejecutar creación de tablas
                $statements = explode(';', $tables_sql);
                foreach ($statements as $statement) {
                    $statement = trim($statement);
                    if (!empty($statement)) {
                        $pdo->exec($statement);
                    }
                }
                
                echo '<div class="success">✅ Tablas de WordPress creadas</div>';
                
                // Insertar opciones básicas
                echo '<div class="progress">⚙️ Configurando opciones básicas...</div>';
                
                $basic_options = [
                    ['siteurl', $site_url],
                    ['home', $site_url],
                    ['blogname', $site_title],
                    ['blogdescription', 'WordPress en Azure App Service'],
                    ['admin_email', $admin_email],
                    ['start_of_week', '1'],
                    ['use_balanceTags', '0'],
                    ['use_smilies', '1'],
                    ['require_name_email', '1'],
                    ['comments_notify', '1'],
                    ['posts_per_rss', '10'],
                    ['rss_use_excerpt', '0'],
                    ['mailserver_url', 'mail.example.com'],
                    ['mailserver_login', 'login@example.com'],
                    ['mailserver_pass', 'password'],
                    ['mailserver_port', '110'],
                    ['default_category', '1'],
                    ['default_comment_status', 'open'],
                    ['default_ping_status', 'open'],
                    ['default_pingback_flag', '1'],
                    ['posts_per_page', '10'],
                    ['date_format', 'F j, Y'],
                    ['time_format', 'g:i a'],
                    ['links_updated_date_format', 'F j, Y g:i a'],
                    ['comment_moderation', '0'],
                    ['moderation_notify', '1'],
                    ['permalink_structure', '/%year%/%monthnum%/%day%/%postname%/'],
                    ['rewrite_rules', ''],
                    ['hack_file', '0'],
                    ['blog_charset', 'UTF-8'],
                    ['moderation_keys', ''],
                    ['active_plugins', 'a:0:{}'],
                    ['category_base', ''],
                    ['ping_sites', 'http://rpc.pingomatic.com/'],
                    ['comment_max_links', '2'],
                    ['gmt_offset', '0'],
                    ['default_email_category', '1'],
                    ['recently_edited', ''],
                    ['template', 'twentytwentyfour'],
                    ['stylesheet', 'twentytwentyfour'],
                    ['comment_registration', '0'],
                    ['html_type', 'text/html'],
                    ['use_trackback', '0'],
                    ['default_role', 'subscriber'],
                    ['db_version', '57155'],
                    ['uploads_use_yearmonth_folders', '1'],
                    ['upload_path', ''],
                    ['blog_public', '1'],
                    ['default_link_category', '2'],
                    ['show_on_front', 'posts'],
                    ['tag_base', ''],
                    ['show_avatars', '1'],
                    ['avatar_rating', 'G'],
                    ['upload_url_path', ''],
                    ['thumbnail_size_w', '150'],
                    ['thumbnail_size_h', '150'],
                    ['thumbnail_crop', '1'],
                    ['medium_size_w', '300'],
                    ['medium_size_h', '300'],
                    ['avatar_default', 'mystery'],
                    ['large_size_w', '1024'],
                    ['large_size_h', '1024'],
                    ['image_default_link_type', 'none'],
                    ['image_default_size', ''],
                    ['image_default_align', ''],
                    ['close_comments_for_old_posts', '0'],
                    ['close_comments_days_old', '14'],
                    ['thread_comments', '1'],
                    ['thread_comments_depth', '5'],
                    ['page_comments', '0'],
                    ['comments_per_page', '50'],
                    ['default_comments_page', 'newest'],
                    ['comment_order', 'asc'],
                    ['sticky_posts', 'a:0:{}'],
                    ['widget_categories', 'a:0:{}'],
                    ['widget_text', 'a:0:{}'],
                    ['widget_rss', 'a:0:{}'],
                    ['uninstall_plugins', 'a:0:{}'],
                    ['timezone_string', ''],
                    ['page_for_posts', '0'],
                    ['page_on_front', '0'],
                    ['default_post_format', '0'],
                    ['link_manager_enabled', '0'],
                    ['finished_splitting_shared_terms', '1'],
                    ['site_icon', '0'],
                    ['medium_large_size_w', '768'],
                    ['medium_large_size_h', '0'],
                    ['wp_page_for_privacy_policy', '3'],
                    ['show_comments_cookies_opt_in', '1'],
                    ['initial_db_version', '57155'],
                ];
                
                $stmt = $pdo->prepare("INSERT IGNORE INTO {$table_prefix}options (option_name, option_value) VALUES (?, ?)");
                foreach ($basic_options as $option) {
                    $stmt->execute($option);
                }
                
                // Crear usuario administrador
                echo '<div class="progress">👤 Creando usuario administrador...</div>';
                
                $user_pass_hash = password_hash($admin_pass, PASSWORD_DEFAULT);
                $user_registered = date('Y-m-d H:i:s');
                
                $stmt = $pdo->prepare("INSERT IGNORE INTO {$table_prefix}users 
                    (user_login, user_pass, user_nicename, user_email, user_url, user_registered, user_status, display_name) 
                    VALUES (?, ?, ?, ?, ?, ?, 0, ?)");
                $stmt->execute([$admin_user, $user_pass_hash, $admin_user, $admin_email, $site_url, $user_registered, $admin_user]);
                
                echo '<div class="success">✅ Instalación completada exitosamente</div>';
                
                echo '<div class="info">
                    <h3>🎉 ¡WordPress está listo!</h3>
                    <p><strong>URL del sitio:</strong> <a href="' . $site_url . '">' . $site_url . '</a></p>
                    <p><strong>Panel de administración:</strong> <a href="' . $site_url . '/wp-admin">' . $site_url . '/wp-admin</a></p>
                    <p><strong>Usuario:</strong> ' . $admin_user . '</p>
                    <p><strong>Contraseña temporal:</strong> ' . $admin_pass . '</p>
                    <div class="code">⚠️ <strong>Importante:</strong> Cambia la contraseña inmediatamente después del primer login</div>
                </div>';
                
            } catch (Exception $e) {
                echo '<div class="error">❌ Error durante la instalación: ' . htmlspecialchars($e->getMessage()) . '</div>';
                echo '<div class="code">Detalles del error: ' . htmlspecialchars($e->getTraceAsString()) . '</div>';
            }
        } else {
        ?>
            <div class="info">
                <p>Este asistente instalará WordPress creando las tablas necesarias en la base de datos.</p>
                <p><strong>Base de datos:</strong> <?php echo htmlspecialchars($db_name); ?></p>
                <p><strong>Host:</strong> <?php echo htmlspecialchars($db_host); ?></p>
            </div>
            
            <form method="post">
                <input type="hidden" name="install" value="true">
                <button type="submit">🚀 Instalar WordPress</button>
            </form>
            
            <div class="code">
                <strong>Configuración que se creará:</strong><br>
                • Título del sitio: <?php echo htmlspecialchars($site_title); ?><br>
                • Usuario admin: <?php echo htmlspecialchars($admin_user); ?><br>
                • Contraseña temporal: <?php echo htmlspecialchars($admin_pass); ?><br>
                • Email: <?php echo htmlspecialchars($admin_email); ?>
            </div>
        <?php
        }
        ?>
    </div>
</body>
</html>