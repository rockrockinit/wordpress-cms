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

  public static function getPageLink ($slug, $suffix = '') {
    $type = 'page';
    $post = get_page_by_path($slug, OBJECT, $type);
    $link = get_permalink($post->ID);

    if ($suffix) {
      $suffix = self::slug('-'.$suffix);
      $link = preg_replace('/\/$/', $suffix . '/', $link);
    }

    return $link;
  }
}