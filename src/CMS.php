<?php
/**
 * This file contains only the CSV class.
 *
 * @file
 * @package CMS
 */

namespace WordPress\CMS;

use WordPress\CMS\Utils\Utils;
use WordPress\CMS\Controllers\ApiController;
use WordPress\CMS\Controllers\ShortcodeController;
use WordPress\CMS\Template;

class CMS {

	public function __construct()
  {
	  global $wpdb;

	  // Admin Footer
    add_filter('admin_footer_text', array($this, 'adminFooter'));

    // Languages
    add_action('plugins_loaded', array($this, 'loadLanguages'));

    // Menus
    (new Menus($wpdb))->init();

    // Add grants-checking callback
    add_filter('user_has_cap', '\\WordPress\\CMS\\DB\\Grants::check', 0, 3);

    // Activation hooks. Uninstall is handled by uninstall.php.
    register_activation_hook(__FILE__, '\\WordPress\\CMS\\DB\\ChangeTracker::activate');
    register_activation_hook(__FILE__, '\\WordPress\\CMS\\DB\\Reports::activate');
    register_activation_hook(__FILE__, function() {
      // Clean up out-of-date option.
      delete_option(CMS_SLUG . '_managed_tables');
    });

    // Restful API
    add_action('rest_api_init', array($this, 'loadAPI'));

    // Shortcode
    $shortcode = new ShortcodeController($wpdb);
    add_shortcode(CMS_SLUG, array($shortcode, 'run'));

    // Dashboard
    add_action('wp_dashboard_setup', array($this, 'adminDashboard'));
  }

	public function scripts ($scripts, $deps = array())
	{
		$pathes = Utils::trimExplode(',', $scripts);

		foreach ($pathes as $path) {
			if (preg_match('/\.js$/i', $path)) {
				if (!preg_match('/^(http(s)?:)?\/\//', $path) && !preg_match('/^\//', $path)) {
					if (!preg_match('/^assets\//', $path)) {
						$path = 'assets/' . $path;
					}
					$path = !preg_match('/^\//', $path) ? '/' . $path : $path;
					$path = dirname(get_stylesheet_uri()) . $path;
				}

				$url = $path;
				$slug = 'cms-js-' . md5($url);

				wp_enqueue_script($slug, $url, $deps, CMS_VERSION, true);

				$deps[] = $slug;
			}
		}
	}

	public function styles($styles, $deps = array())
	{
		$pathes = Utils::trimExplode(',', $styles);

		foreach ($pathes as $path) {
			if (preg_match('/\.css$/i', $path)) {
				if (!preg_match('/^(http(s)?:)?\/\//', $path) && !preg_match('/^\//', $path)) {
					if (!preg_match('/^assets\//', $path)) {
						$path = 'assets/' . $path;
					}
					$path = !preg_match('/^\//', $path) ? '/' . $path : $path;
					$path = dirname(get_stylesheet_uri()) . $path;
				}

				$url = $path;
				$slug = 'cms-css-' . md5($url);

				wp_enqueue_style($slug, $url, $deps, CMS_VERSION);

				$deps[] = $slug;
			}
		}
	}

	public function loadLanguages()
  {
    load_plugin_textdomain(CMS_SLUG, false, basename(__DIR__) . '/../languages/');
  }

	public function loadAPI()
  {
    global $wpdb;

    $api_controller = new ApiController($wpdb, $_GET);
    $api_controller->register_routes();
  }

  public function adminFooter()
  {
    echo '';
  }

  public function adminDashboard()
  {
    wp_add_dashboard_widget(CMS_SLUG . 'dashboard_widget', 'Cms', function() {
      $template = new Template('dashboard.html');
      echo $template->render();
    });
  }
}
