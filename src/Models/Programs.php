<?php
/**
 * This file contains only a single class.
 *
 * @file
 * @package CMS
 */

namespace WordPress\CMS\Models;

use \WordPress\CMS\Utils\UrlUtils;

class Programs
{
  public static function get ($value = '')
  {
    global $wpdb;

    $row = null;

    if (!$value && isset($_GET['program'])) {
      $value = $_GET['program'];
    }

    if (!$value) {
      $value = UrlUtils::getSegment(-1);
    }

    if ($value) {
      if (is_numeric($value)) {
        $sql = "SELECT * FROM programs WHERE id = '{$value}' LIMIT 1;";
        $rows = $wpdb->get_results($sql);
        $row = $rows ? $rows[0] : $row;
      } else if (is_string($value)) {
        $value = preg_replace('/^at-a-glance-/i', '', $value);
        $value = preg_replace('/-/i', ' ', $value);
        $value = $wpdb->_escape($value);

        $sql = "SELECT * FROM programs WHERE name = '{$value}' LIMIT 1;";

        $rows = $wpdb->get_results($sql);
        $row = $rows ? $rows[0] : $row;
      }
    }

    $row->classes = [];

    if ($row) {
      if ($row->classes_ids) {
        $ids = explode(',', $row->classes_ids);

        if ($ids) {
          $ids = implode("','", $ids);
          $sql = "SELECT * FROM classes WHERE id IN ('{$ids}')";
          $row->classes = $wpdb->get_results($sql);
        }
      }
    }

    return $row;
  }

  public static function all($order = 'name ASC')
  {
    global $wpdb;

    $sql = "SELECT * FROM programs ORDER BY {$order};";

    $rows = $wpdb->get_results($sql);

    foreach ($rows as &$row) {
      $row->classes = [];

      if ($row) {
        if ($row->classes_ids) {
          $ids = explode(',', $row->classes_ids);

          if ($ids) {
            $ids = implode("','", $ids);
            $sql = "SELECT * FROM classes WHERE id IN ('{$ids}')";
            $row->classes = $wpdb->get_results($sql);
          }
        }
      }
    }

    return $rows;
  }
}