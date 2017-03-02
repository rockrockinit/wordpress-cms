<?php
/**
 * This file contains only the Menus class
 *
 * @package CMS
 */

namespace WordPress\CMS;
use WordPress\CMS\Utils\Utils;

/**
 * This class is an attempt to group all functionality around managing the menus
 * in the Admin Area in one place. It includes adding scripts and stylesheets.
 */
class Menus {

	/**
	 * The global wpdb object.
	 *
	 * @var \wpdb
	 */
	protected $wpdb;

	/**
	 * The page output is stored between being called/created in
	 * self::dispatch() and output in self::add_menu_pages()
	 *
	 * @var string
	 */
	protected $output;

	/**
	 * Create a new Menus object, supplying it with the database so that it
	 * doesn't have to use a global.
	 *
	 * @param \wpdb $wpdb The global wpdb object.
	 */
	public function __construct( $wpdb ) {
		$this->wpdb = $wpdb;
	}

	/**
	 * Set up all required hooks. This is called from the top level of cms.php
	 *
	 * @return void
	 */
	public function init() {
		add_action( 'init', array( $this, 'dispatch' ) );
		add_action( 'admin_menu', array( $this, 'add_menu_pages' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue' ) );
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue' ) );
		add_action( 'admin_bar_menu', array( $this, 'admin_bar_menu' ) );
	}

	/**
	 * Add menu items to the main admin menu.
	 *
	 * @return void
	 */
	public function add_menu_pages() {
		$dispatch_callback = array( $this, 'output' );

		// Home page (also change the first submenu item's title).
		add_menu_page( 'Custom Data', 'Custom Data', 'read', CMS_SLUG, $dispatch_callback );
		$page_title = ( isset( $_GET['table'] ) ) ? Text::titlecase( $_GET['table'] ) : 'Cms';
		add_submenu_page( CMS_SLUG, $page_title, 'Overview', 'read', CMS_SLUG, $dispatch_callback );

		// Add submenu pages.
		if ( Util::is_plugin_active( 'tfo-graphviz/tfo-graphviz.php' ) ) {
			add_submenu_page( CMS_SLUG, 'Cms ERD', 'ERD', 'read', CMS_SLUG . '_erd', $dispatch_callback );
		}

		// EDWIN
		//add_submenu_page( CMS_SLUG, 'Cms Reports', 'Reports', 'promote_users', CMS_SLUG . '_reports', $dispatch_callback );
		//add_submenu_page( CMS_SLUG, 'Cms Grants', 'Grants', 'promote_users', CMS_SLUG . '_grants', $dispatch_callback );
	}

	/**
	 * Add all tables in which the user is allowed to create records to the
	 * Admin Bar new-content menu. If there are more than ten, none are added
	 * because the menu would get too long. Not sure how this should be fixed.
	 *
	 * @global \WP_Admin_Bar $wp_admin_bar
	 * @global \wpdb $wpdb
	 */
	public function admin_bar_menu() {
		global $wp_admin_bar, $wpdb;
		$db = new DB\Database( $wpdb );
		$tables = $db->get_tables();
		if ( count( $tables ) > 10 ) {
			return false;
		}
		foreach ( $tables as $table ) {
			if ( ! DB\Grants::current_user_can( DB\Grants::CREATE, $table->get_name() ) ) {
				continue;
			}
			$wp_admin_bar->add_menu( array(
				'parent' => 'new-content',
				'id'     => CMS_SLUG . '-' . $table->get_name(),
				'title'  => $table->get_title(),
				'href'   => $table->get_url( 'index', null, 'record' ),
			) );
		}
	}

	/**
	 * Print the currently-stored output; this is the callback for all the menu items.
	 */
	public function output() {
		echo $this->output;
	}

	/**
	 * Create and dispatch the controller, capturing its output for use later
	 * in the callback for the menu items.
	 *
	 * @return string The HTML to display.
	 */
	public function dispatch() {
		$request = $_REQUEST;

		// Only dispatch when it's our page.
		$slug_lenth = strlen( CMS_SLUG );
		if ( ! isset( $request['page'] ) || substr( $request['page'], 0, $slug_lenth ) !== CMS_SLUG ) {
			return;
		}

		// Discern the controller name, based on an explicit request parameter, or
		// the trailing part of the page slug (i.e. after 'cms_').
		$controller_name = 'home';
		if ( isset( $request['controller'] ) ) {
			$controller_name = $request['controller'];
		} elseif ( isset( $request['page'] ) && strlen( $request['page'] ) > $slug_lenth ) {
			$controller_name = substr( $request['page'], $slug_lenth + 1 );
		}

		// Create the controller and run the action.
		$controller_classname = 'WordPress\\CMS\\Controllers\\' . ucfirst( $controller_name ) . 'Controller';
		$controller = new $controller_classname( $this->wpdb );
		$action = ! empty( $request['action'] ) ? $request['action'] : 'index';
		unset( $request['page'], $request['controller'], $request['action'] );
		try {
			$this->output = $controller->$action( $request );
		} catch ( \Exception $e ) {
			$this->output = '<h1>An error occured</h1><div class="error"><p>' . $e->getMessage() . '</p></div>';
			if ( WP_DEBUG ) {
				$this->output .= '<h2>Stack trace</h2><pre>' . $e->getTraceAsString() . '</pre>';
			}
		}
	}

	/**
	 * This is the callback method used in self::init() to add scripts and
	 * styles to the Cms admin pages and everywhere the shortcode is used.
	 *
	 * @param string $page The current page name.
	 * @return void
	 */
	public function enqueue( $page ) {

		$allowed_pages = array(
			'index.php', // For the Dashboard widget.
			'cms_shortcode', // Not really a page.
			'toplevel_page_cms',
			'cms_page_cms_erd',
			'cms_page_cms_reports',
			'cms_page_cms_grants',
			'cms_page_cms_schema',
		);

		if (!( empty( $page ) || in_array( $page, $allowed_pages, true ))) {
			return;
		}

    $js = "
    	/assets/js/jquery-setup.js,
      /assets/vendor/bootstrap/3.3.7/js/bootstrap.js,
      /assets/vendor/jquery-ui-timepicker/jquery-ui-timepicker-addon.min.js,
      /assets/vendor/jquery-maskedinput/jquery.maskedinput.min.js,
      /assets/vendor/leaflet/leaflet-omnivore.min.js,
      /assets/vendor/leaflet/leaflet.js,
      /assets/vendor/summernote/0.8.2/summernote.js, 
      /assets/vendor/lodash/4.13.1/lodash.js,
      /assets/js/cms/Paginator.js,
      /assets/js/cms/Base.js,
      /assets/vendor/vue/2.1.6/vue.js,
      /assets/js/cms/CmsIds.js,
      /assets/vendor/bootstrap/3.3.7/bootstrap-hack.js,
      /assets/js/scripts.js
    ";

    $css = " 
      /assets/vendor/jquery-ui/jquery-ui.min.css,
      /assets/vendor/jquery-ui/jquery-ui.theme.min.css,
      /assets/vendor/jquery-ui-timepicker/jquery-ui-timepicker-addon.css,
      /assets/vendor/leaflet/leaflet.css,
      /assets/vendor/summernote/0.8.2/summernote.css,
      /assets/css/style.css
    ";

    // JS
    $count = 1;
    $deps = Util::is_plugin_active('rest-api/plugin.php') ? ['wp-api'] : [];
    $deps = array_merge(['jquery-ui-datepicker'], $deps);

    $pathes_js = Utils::trimExplode(',', $js);

    foreach ($pathes_js as $path) {
      $slug = 'cms-js' . $count;
      $url = plugins_url(CMS_SLUG) . $path;

      wp_enqueue_script($slug, $url, $deps, CMS_VERSION, true);

      $deps[] = $slug;
      $count++;
    }

    // CSS
    $count = 1;
    $deps = [];
    $pathes_css = Utils::trimExplode(',', $css);

    foreach ($pathes_css as $path) {
      $slug = 'cms-css' . $count;
      $url = plugins_url(CMS_SLUG) . $path;

      wp_enqueue_style($slug, $url, $deps, CMS_VERSION);

      $deps[] = $slug;
      $count++;
    }

		// Javascript page variables
		$vars = [
			'admin_url' => admin_url() . 'admin.php?page=' . CMS_SLUG,
			'plugin_url' => plugins_url(CMS_SLUG),
			'site_url' => site_url()
		];
		wp_localize_script( 'cms-js'. count($pathes_js), 'cms', $vars );
	}
}
