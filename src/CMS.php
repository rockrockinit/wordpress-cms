<?php
/**
 * This file contains only the CSV class.
 *
 * @file
 * @package CMS
 */

namespace WordPress\CMS;

use WordPress\CMS\Utils\Utils;
use \WordPress\CMS\Utils\WpUtils;

class CMS {

	public function __construct() {}

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

	public function styles ($styles, $deps = array())
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
}
