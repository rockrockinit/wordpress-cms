<?php
/**
 * Plugin Name: Custom Data
 * Description: Manage relational CMS data within the WP admin area, using the full power of your MySQL database.
 * License: GPL-2.0+
 * Text Domain: cms
 * Domain Path: /languages
 * Version: 1.0.0
 */

define('CMS_VERSION', '1.0.0');
define('CMS_SLUG', 'cms');

// Composer check
if (!file_exists( __DIR__ . '/vendor/autoload.php')) {
  add_action('admin_notices', function() {
    $msg = __('Please run <kbd>composer install</kbd> prior to using the CMS plugin.', 'cms');
    echo "<div class='error'><p>$msg</p></div>";
  });

  return;
}

require_once 'vendor/autoload.php';

$cms = new \WordPress\CMS\CMS();