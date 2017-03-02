<?php
/**
 * Plugin Name: Custom Data
 * Description: Manage relational CMS data within the WP admin area, using the full power of your MySQL database.
 * License: GPL-2.0+
 * Text Domain: cms
 * Domain Path: /languages
 * Version: 1.0.0
 */

define( 'CMS_VERSION', '1.0.0' );
define( 'CMS_SLUG', 'cms' );

// Admin footer modification
function remove_footer_admin () {
  //echo '<span id="footer-thankyou">Developed by <a href="http://www.edrodriguez.com" target="_blank">Ed Rodriguez</a></span>';
  echo '';
}
add_filter('admin_footer_text', 'remove_footer_admin');

// Load textdomain.
add_action( 'plugins_loaded', function() {
	load_plugin_textdomain( CMS_SLUG, false, basename( __DIR__ ) . '/languages/' );
} );

// Make sure Composer has been set up (for installation from Git, mostly).
if ( ! file_exists( __DIR__ . '/vendor/autoload.php' ) ) {
	add_action( 'admin_notices', function() {
		$msg = __( 'Please run <kbd>composer install</kbd> prior to using CMS.', 'cms' );
		echo "<div class='error'><p>$msg</p></div>";
	} );
	return;
}
require __DIR__ . '/vendor/autoload.php';

// This file contains the only global usages of wpdb; it's injected from here to
// everywhere else.
global $wpdb;

// Set up the menus; their callbacks do the actual dispatching to controllers.
$menus = new \WordPress\CMS\Menus( $wpdb );
$menus->init();

// Add grants-checking callback.
add_filter( 'user_has_cap', '\\WordPress\\CMS\\DB\\Grants::check', 0, 3 );

// Activation hooks. Uninstall is handled by uninstall.php.
register_activation_hook( __FILE__, '\\WordPress\\CMS\\DB\\ChangeTracker::activate' );
register_activation_hook( __FILE__, '\\WordPress\\CMS\\DB\\Reports::activate' );
register_activation_hook(__FILE__, function() {
	// Clean up out-of-date option.
	delete_option( CMS_SLUG . '_managed_tables' );
});

// Register JSON API.
add_action( 'rest_api_init', function() {
	global $wpdb;
	$api_controller = new \WordPress\CMS\Controllers\ApiController( $wpdb, $_GET );
	$api_controller->register_routes();
} );

// Shortcode.
$shortcode = new \WordPress\CMS\Controllers\ShortcodeController( $wpdb );
add_shortcode( CMS_SLUG, array( $shortcode, 'run' ) );

// Dashboard widget.
add_action( 'wp_dashboard_setup', function() {
	wp_add_dashboard_widget( CMS_SLUG . 'dashboard_widget', 'Cms', function(){
		$template = new \WordPress\CMS\Template( 'quick_jump.html' );
		echo $template->render();
	} );
} );

