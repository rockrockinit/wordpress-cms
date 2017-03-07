<?php
/**
 * Created by PhpStorm.
 * User: erodriguez
 * Date: 3/2/2017
 * Time: 9:56 AM
 */

namespace WordPress\CMS\Models;

use \WordPress\CMS\Utils\UrlUtils;
use WordPress\CMS\Utils\Utils;

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

  public function _get($value = '', $ids_tables = [])
  {
    global $wpdb;

    $rows = [];
    $row = null;
    $url = false;

    if ($this->table) {
      if (!$value && $this->slug && isset($_GET[$this->slug])) {
        $value = $_GET[$this->slug];
      }

      if (!$value) {
        $value = UrlUtils::getSegment(-1);

        $url = true;
      }

      if ($value) {
        if (is_array($value)) {
          $ids = $value;
        } else if (is_numeric($value)) {
          $sql = "SELECT * FROM {$this->table} WHERE id = '{$value}' LIMIT 1;";
          $rows = $wpdb->get_results($sql);
        } else if (is_string($value)) {
          if ($url) {
            $value = $wpdb->_escape($value);

            if (is_numeric($value)) {
              $sql = "SELECT * FROM {$this->table} WHERE id = '{$value}' LIMIT 1;";
            } else {
              $sql = "SELECT * FROM {$this->table} WHERE vanity = '{$value}' LIMIT 1;";
            }

            $rows = $wpdb->get_results($sql);
          } else {
            $ids = Utils::trimExplode('/,|\|/', $value);

            $sql = "SELECT * FROM {$this->table} WHERE id IN ('" . implode("','", $ids) . "');";

            $rows = $wpdb->get_results($sql);
          }
        }
      }

      $rows = $this->ids_columns($rows);



      if ($ids_tables) {
        $ids_tables = Utils::trimExplode(',', $ids_tables);

        $rows = $this->ids_tables($rows, $ids_tables);
      }
    }

    $response = $rows && count($rows) > 1 ? $rows : $rows[0];

    return $response;
  }

  public function ids_tables($rows = [], $tables = [])
  {
    global $wpdb;

    if (is_array($rows) && $rows && is_array($tables) && $tables) {
      $column = $this->table . '_ids';

      foreach ($rows as &$row) {
        $id = $row->id;

        foreach ($tables as $table) {
          $row->{$table} = [];

          $sql = "SELECT * FROM {$table} WHERE {$column} = '{$id}' OR {$column} LIKE '{$id},%' OR {$column} LIKE '%,{$id},%' OR {$column} LIKE '%,{$id}';";

          $rows2 = $wpdb->get_results($sql);

          // Takes into account ordering
          if ($rows2) {
            $row->{$table} = $rows2;
          }

        }
      }
    }

    return $rows;
  }

  public function ids_columns($rows = [])
  {
    global $wpdb;

    if (is_array($rows)) {
      foreach ($rows as &$row) {
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

    return $rows;
  }

  public function _all($order = 'name ASC')
  {
    global $wpdb;

    $rows = [];

    if ($this->table) {
      $sql = "SELECT * FROM {$this->table} ORDER BY {$order};";

      $rows = $wpdb->get_results($sql);

      $rows = $this->ids_columns($rows);
    }

    return $rows;
  }
}