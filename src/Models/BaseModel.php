<?php
/**
 * Created by PhpStorm.
 * User: erodriguez
 * Date: 3/2/2017
 * Time: 9:56 AM
 */

namespace WordPress\CMS\Models;

use \WordPress\CMS\Utils\UrlUtils;

class BaseModel
{
  public static function __callStatic($method, $args)
  {
    $results = null;

    $class = get_called_class();
    $method = '_' . $method;

    $obj = new $class();

    if (method_exists($obj, $method)) {
      $results = call_user_func_array([$obj, $method], $args);
    }

    return $results;
  }

  public function _get ($value = '')
  {
    global $wpdb;

    $row = null;

    if ($this->table) {
      if (!$value && $this->slug && isset($_GET[$this->slug])) {
        $value = $_GET[$slug];
      }

      if (!$value) {
        $value = UrlUtils::getSegment(-1);
      }

      if ($value) {
        if (is_numeric($value)) {
          $sql = "SELECT * FROM {$this->table} WHERE id = '{$value}' LIMIT 1;";
          $rows = $wpdb->get_results($sql);
          $row = $rows ? $rows[0] : $row;
        } else if (is_string($value)) {
          $value = preg_replace('/^' . $this->prefix . '/i', '', $value);
          $value = preg_replace('/-/i', ' ', $value);
          $value = $wpdb->_escape($value);

          $sql = "SELECT * FROM {$this->table} WHERE name = '{$value}' LIMIT 1;";

          $rows = $wpdb->get_results($sql);
          $row = $rows ? $rows[0] : $row;
        }
      }

      $row->classes = [];

      if ($row) {
        $columns = get_object_vars($row);
        $columns = array_keys($columns);

        foreach ($columns as $column) {
          if (preg_match('/_ids$/i', $column)) {
            $table2 = preg_replace('/_ids$/i', '', $column);

            $row->{$table2} = [];

            if ($row->{$column}) {
              $ids = explode(',', $row->{$column});

              if ($ids) {
                $sql = "SELECT * FROM {$table2} WHERE id IN ('" . implode("','", $ids) . "')";

                $rows2 = $wpdb->get_results($sql);

                // Takes into account ordering
                foreach ($ids as $id) {
                  foreach ($rows2 as $row2) {
                    if ($row2->id == $id) {
                      $row->classes[] = $row2;
                    }
                  }
                }
              }
            }
          }
        }
      }
    }

    return $row;
  }

  public function _all($order = 'name ASC')
  {
    global $wpdb;

    $rows = [];

    if ($this->table) {
      $sql = "SELECT * FROM {$this->table} ORDER BY {$order};";

      $rows = $wpdb->get_results($sql);

      foreach ($rows as &$row) {
        if ($row) {
          $columns = get_object_vars($row);
          $columns = array_keys($columns);

          foreach ($columns as $column) {
            if (preg_match('/_ids$/i', $column)) {
              $table2 = preg_replace('/_ids$/i', '', $column);

              $row->{$table2} = [];

              if ($row->{$column}) {
                $ids = explode(',', $row->{$column});

                if ($ids) {
                  $sql = "SELECT * FROM {$table2} WHERE id IN ('" . implode("','", $ids) . "')";

                  $rows2 = $wpdb->get_results($sql);

                  // Takes into account ordering
                  foreach ($ids as $id) {
                    foreach ($rows2 as $row2) {
                      if ($row2->id == $id) {
                        $row->{$table2}[] = $row2;
                      }
                    }
                  }
                }
              }
            }
          }
        }
      }
    }

    return $rows;
  }
}