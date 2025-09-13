<?php
/**
 * Headless API Theme
 * 
 * Minimal WordPress theme optimized for headless/API usage
 * This theme provides only the essential functions needed for API endpoints
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Guarda siempre en /acf-json del tema activo
add_filter('acf/settings/save_json', function($path) {
  return get_stylesheet_directory() . '/acf-json';
});

// Carga desde /acf-json del tema activo (y puedes añadir más rutas)
add_filter('acf/settings/load_json', function($paths) {
  // Elimina la ruta por defecto (para evitar duplicados)
  if (isset($paths[0])) unset($paths[0]);
  // Añade la de tu tema
  $paths[] = get_stylesheet_directory() . '/acf-json';
  // (Opcional) Añade otras rutas (por ejemplo, de un plugin)
  // $paths[] = WP_CONTENT_DIR . '/mu-plugins/mi-plugin/acf-json';
  return $paths;
});

add_filter('acf/settings/rest_api_format', function( $format ) {
    return 'standard';
});

// Theme setup
add_action('after_setup_theme', 'headless_api_setup');

function headless_api_setup() {
    // Add theme support for features needed by API
    add_theme_support('post-thumbnails');
    //add_theme_support('title-tag');
    //add_theme_support('custom-logo');
    //add_theme_support('menus');

    // Add image sizes for API responses
    add_image_size('api-thumbnail', 300, 300, true);
    add_image_size('api-medium', 600, 400, true);
    add_image_size('api-large', 1200, 800, true);
}

function get_featured_image( $object, $field_name, $request ) {
    // Get the post ID from the object.
    if ( ! isset( $object['featured_media'] ) || empty( $object['featured_media'] ) ) {
        return false; // Return empty if no featured media is set.
    }
    $post_id = $object['featured_media'];
    $post_images = array();
    foreach ( get_intermediate_image_sizes() as $size ) {
        // Get the featured image URL for the post.
        $image_url = wp_get_attachment_image_src( $post_id, $size );
        if ( $image_url ) {
            $post_images[ $size === '1536x1536' || $size === '2048x2048' ? 'full' : $size ] = array(
                'url' => $image_url[0],
                'width' => $image_url[1],
                'height' => $image_url[2],
            );
        }
    }

    // Return the image URL.
    return $post_images;
}

function headless_api_init() {
    // Register a custom REST field for posts to include featured_image field.
    register_rest_field( 
        array('page',), 
        'featured_image', 
        array('get_callback' => 'get_featured_image')
    );
}

add_action( 'rest_api_init', 'headless_api_init' );

// Remove unnecessary head elements for API-only usage
remove_action('wp_head', 'wp_generator');
remove_action('wp_head', 'print_emoji_detection_script', 7);
remove_action('wp_head', 'wp_print_styles', 8);
remove_action('wp_head', 'wp_print_head_scripts', 9);
remove_action('wp_head', 'feed_links', 2);
remove_action('wp_head', 'feed_links_extra', 3);
remove_action('wp_head', 'rsd_link');
remove_action('wp_head', 'wlwmanifest_link');

// Customize login for API users
add_filter('login_redirect', function($redirect_to, $request, $user) {
    if (!is_wp_error($user) && isset($user->roles) && in_array('api_user', $user->roles)) {
        return home_url('/wp-json/');
    }
    return $redirect_to;
}, 10, 3);
