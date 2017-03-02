<?php
/**
 * This file contains only the Text class
 *
 * @package CMS
 */

namespace WordPress\CMS;

use \WordPress\CMS\Utils\Utils;

/**
 * A Template is a wrapper for a Twig file
 */
class Template {

	/**
	 * The name of the template to render (if not using a Twig string).
	 *
	 * @var string
	 */
	protected $template_name;

	/**
	 * The Twig string to render (if not using a template file).
	 *
	 * @var string
	 */
	protected $template_string;

	/**
	 * The template data, all of which is passed to the Twig template.
	 *
	 * @var string[]
	 */
	protected $data;

	/**
	 * Paths at which to find templates.
	 *
	 * @var string[]
	 */
	protected static $paths = array();

	/**
	 * The name of the transient used to store notices.
	 *
	 * @var string
	 */
	protected $transient_notices;

	/**
	 * Create a new template either with a file-based Twig template, or a Twig string.
	 *
	 * @global type $wpdb
	 * @param string|false $template_name   The name of a Twig file to render.
	 * @param string|false $template_string A Twig string to render.
	 */
	public function __construct( $template_name = false, $template_string = false ) {
		global $wpdb;
		$this->template_name = $template_name;
		$this->template_string = $template_string;
		$this->transient_notices = CMS_SLUG . '_notices';
		$notices = get_transient( $this->transient_notices );
		if ( ! is_array( $notices ) ) {
			$notices = array();
		}

		global $current_user;
		get_currentuserinfo();

		/*
    echo 'Username: ' . $current_user->user_login . "\n";
    echo 'User email: ' . $current_user->user_email . "\n";
    echo 'User level: ' . $current_user->user_level . "\n";
    echo 'User first name: ' . $current_user->user_firstname . "\n";
    echo 'User last name: ' . $current_user->user_lastname . "\n";
    echo 'User display name: ' . $current_user->display_name . "\n";
    echo 'User ID: ' . $current_user->ID . "\n";
    */

		$this->data = array(
			'cms_version' => CMS_VERSION,
			'slug' => CMS_SLUG,
			'notices' => $notices,
			'wp_api' => Util::is_plugin_active( 'rest-api/plugin.php' ),
			'tfo_graphviz' => Util::is_plugin_active( 'tfo-graphviz/tfo-graphviz.php' ),
			'wpdb_prefix' => $wpdb->prefix,
			'current_user_is_admin' => current_user_can( 'promote_users' ),
			'query' => $_GET,
			'user' => $current_user
		);
		self::add_path( __DIR__ . '/../templates' );
	}

	/**
	 * Add a filesystem path under which to look for template files.
	 *
	 * @param string $new_path The path to add.
	 */
	public static function add_path( $new_path ) {
		$path = realpath( $new_path );
		if ( ! in_array( $path, self::$paths, true ) ) {
			self::$paths[] = $path;
		}
	}

	/**
	 * Get a list of the filesystem paths searched for template files.
	 *
	 * @return string[] An array of paths
	 */
	public static function get_paths() {
		return self::$paths;
	}

	/**
	 * Get a list of templates in a given directory, across all registered template paths.
	 *
	 * @param string $directory The directory to search in.
	 */
	public function get_templates( $directory ) {
		$templates = array();
		foreach ( self::$paths as $path ) {
			$dir = $path . '/' . ltrim( $directory, '/' );
			foreach ( preg_grep( '/^[^\.].*\.(twig|html)$/', scandir( $dir ) ) as $file ) {
				$templates[] = $directory . '/' . $file;
			}
		}
		return $templates;
	}

	/**
	 * Magically set a template variable.
	 *
	 * @param string $name  The name of the variable.
	 * @param mixed  $value The value of the variable.
	 */
	public function __set( $name, $value ) {
		$this->data[ $name ] = $value;
	}

	/**
	 * Find out whether a given item of template data is set.
	 *
	 * @param string $name The property name.
	 * @return boolean
	 */
	public function __isset( $name ) {
		return isset( $this->data[ $name ] );
	}

	/**
	 * Get an item from this template's data.
	 *
	 * @param string $name The name of the template variable.
	 * @return mixed
	 */
	public function __get( $name ) {
		return $this->data[ $name ];
	}

	/**
	 * Add a notice. All notices are saved to a Transient, which is deleted when
	 * the template is rendered but otherwise available to all subsequent
	 * instances of the Template class.
	 *
	 * @param string $type Either 'updated' or 'error'.
	 * @param string $message The message to display.
	 */
	public function add_notice( $type, $message ) {
		$this->data['notices'][] = array(
			'type' => $type,
			'message' => $message,
		);
		set_transient( $this->transient_notices, $this->data['notices'] );
	}

	/**
	 * Render the template and output it.
	 *
	 * @return void
	 */
	public function __toString() {
		echo $this->render();
	}

	/**
	 * Render the template and return the output.
	 *
	 * @return string
	 */
	public function render() {
		delete_transient( $this->transient_notices );
		$loader = new \Twig_Loader_Filesystem( self::$paths );
		$twig = new \Twig_Environment( $loader );

		// Add some useful functions to Twig.
		$funcs = array( 'admin_url', '__', '_e', 'wp_create_nonce' );
		foreach ( $funcs as $f ) {
			$twig->addFunction( $f, new \Twig_SimpleFunction( $f, $f ) );
		}
		// Handle wp_nonce_field() differently in order to default it to returning the string.
		$wp_nonce_field = new \Twig_SimpleFunction( 'wp_nonce_field', function ( $action = -1, $name = "_wpnonce", $referer = true, $echo = false ) {
			return wp_nonce_field( $action, $name, $referer, $echo );
		});
		$twig->addFunction( $wp_nonce_field );

		// Handle wp_nonce_field() differently in order to default it to returning the string.
		$in_set = new \Twig_SimpleFunction( 'in_set', function ( $values, $value ) {
			$found = false;

			if (is_string($values)) {
				$values = explode(',', $values);
			}

			if (is_array($values)) {
				foreach ($values as $val) {
					if (preg_match('/^' . print_r($value, 1) . '$/i', $val)) {
						$found = true;
						break;
					}
				}
			}

			return $found;
		});

		$twig->addFunction( $in_set );

    // Check if a column is an associative column type with the suffix _ids
    $twig->addFunction(new \Twig_SimpleFunction( 'is_type', function ( $name, $type) {
      $bool = false;

      if (is_string($name) && is_string($type)) {
        if (preg_match('/^ids$/i', $type)) {
          $bool = preg_match('/_ids$/i', $name);
        }
      }

      return $bool;
    }));

		// Titlecase Filter
		$filter = new \Twig_SimpleFilter('titlecase', '\\WordPress\\CMS\\Text::titlecase');
		$twig->addFilter($filter);

		// Date Filters
		$filter = new \Twig_SimpleFilter('wp_date_format', '\\WordPress\\CMS\\Text::wp_date_format');
		$twig->addFilter($filter);

		$filter = new \Twig_SimpleFilter('wp_time_format', '\\WordPress\\CMS\\Text::wp_time_format');
		$twig->addFilter($filter);

		$twig->addFilter(new \Twig_SimpleFilter('get_date_from_gmt', 'get_date_from_gmt'));

		// Strtolower Filter
		$strtolower_filter = new \Twig_SimpleFilter('strtolower', function($str) {
			if ( is_array( $str ) ) {
				return array_map( 'strtolower', $str );
			} else {
				return strtolower( $str );
			}
		});

		$twig->addFilter( $strtolower_filter );

		// Crop Filter
		$filter = new \Twig_SimpleFilter('cropstring', function($str, $length = 0, $ellipsis = 1, $wordsOnly = 0, $wordBreaks = 1) {
			return Utils::cropString($str, $length, $ellipsis, $wordsOnly, $wordBreaks);
		});

		$twig->addFilter($filter);

		// Enable debugging.
		if ( WP_DEBUG ) {
			$twig->enableDebug();
			$twig->addExtension( new \Twig_Extension_Debug() );
		}

		// Render the template.
		if ( ! empty( $this->template_string ) ) {
			$template = $twig->createTemplate( $this->template_string );
		} else {
			$template = $twig->loadTemplate( $this->template_name );
		}
		return $template->render( $this->data );
	}
}