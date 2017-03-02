<?php
/**
 * This file contains only a single class.
 *
 * @file
 * @package CMS
 */

namespace WordPress\CMS\Models;

use \WordPress\CMS\Utils\UrlUtils;

class Classes
{
  public static function get ($value = '')
  {
    global $wpdb;

    $class = null;

    if (!$value && isset($_GET['class'])) {
      $value = $_GET['class'];
    }

    if (!$value) {
      $value = UrlUtils::getSegment(-1);
    }

    if ($value) {
      if (is_numeric($value)) {
        $sql = "SELECT * FROM classes WHERE id = '{$value}' LIMIT 1;";
        $rows = $wpdb->get_results($sql);
        $class = $rows ? $rows[0] : $class;
      } else if (is_string($value)) {
        $value = preg_replace('/^class-/i', '', $value);
        $value = preg_replace('/-/i', ' ', $value);
        $value = $wpdb->_escape($value);

        $sql = "SELECT * FROM classes WHERE name = '{$value}' LIMIT 1;";
        $rows = $wpdb->get_results($sql);
        $class = $rows ? $rows[0] : $class;
      }
    }

    return $class;
  }

  public static function all($order = 'name ASC')
  {
    global $wpdb;

    $sql = "SELECT * FROM classes ORDER BY {$order};";

    $rows = $wpdb->get_results($sql);

    return $rows;
  }
}