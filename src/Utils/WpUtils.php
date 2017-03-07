<?php
/**
 * This file contains only a single class.
 *
 * @file
 * @package CMS
 */

namespace WordPress\CMS\Utils;

class WpUtils extends UtilsBase
{
  public static function getPages () {
    global $wpdb;

    $sql = "SELECT * FROM {$wpdb->prefix}posts WHERE post_type = 'page' AND ping_status = 'open' ORDER BY post_title ASC;";
    $rows = $wpdb->get_results($sql);

    return $rows;
  }
}