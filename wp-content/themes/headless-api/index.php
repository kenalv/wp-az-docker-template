<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo('charset'); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php bloginfo('name'); ?> - Headless WordPress API</title>
    <?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>

<div class="api-info">
    <h1><?php bloginfo('name'); ?> REST API</h1>
    
    <p>This is a headless WordPress installation optimized for REST API usage. The frontend is handled by external applications.</p>
    
    <h2>Available Endpoints</h2>
    
    <div class="api-endpoint method-get">
        <h3>GET /wp-json/wp/v2/posts</h3>
        <p>Retrieve blog posts</p>
    </div>
    
    <div class="api-endpoint method-get">
        <h3>GET /wp-json/wp/v2/pages</h3>
        <p>Retrieve pages</p>
    </div>

    <h2>Authentication</h2>
    <p>Use JWT tokens for authenticated requests:</p>
    <pre><code>Authorization: Bearer YOUR_JWT_TOKEN</code></pre>
    
    <h2>Admin Access</h2>
    <p><a href="<?php echo admin_url(); ?>">WordPress Admin</a></p>
    
    <h2>API Documentation</h2>
    <p><a href="<?php echo rest_url(); ?>">Browse API</a></p>
</div>

<?php wp_footer(); ?>
</body>
</html>
