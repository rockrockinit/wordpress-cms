<?php
/**
 * This file contains only a single class.
 *
 * @file
 * @package CMS
 */

namespace WordPress\CMS\Utils;

/**
 * URL specific utilities
 *
 * @author Ed Rodriguez
 * @version 1.0
 */
class UrlUtils extends Utils{

  public function __construct(){}

  /**
   * Returns a param value if found
   *
   * Examples: getParam(1)
   * 1) /activities/aaaa/bbbbb/ (returns 'aaaa')
   * 2) /activities/?test1=aaaa&test2=bbbbb (returns 'aaaa')
   * 3) /activities/?2=aaaa&1=bbbbb (returns 'bbbb')
   * 4) /activities.php/aaaa/bbbbb/ (returns 'aaaa')
   * 5) /activities.php/?test1=aaaa&test2=bbbbb (returns 'aaaa')
   * 6) /activities.php/?2=aaaa&1=bbbbb (returns 'bbbb')
   * 7) /activities.php?test1=aaaa&test2=bbbbb (returns 'aaaa')
   * 8) /activities.php?2=aaaa&1=bbbbb (returns 'bbbb')
   *
   * @param string [$param] The name of the param
   * @return string The param value
   */
  public static function getParam ($param='')
  {
    $param = trim($param);
    $value = null;

    if (!empty($param)) {
      // SEARCH GET (by name)
      $value = isset($_GET[$param]) ? $_GET[$param] : null;

      // SEARCH PATHINFO (by position)
      if (!isset($value)) {
        $vars = self::getPathInfo();
        $p = is_numeric($param) && $param > 0 ? $param-1 : null;

        if (isset($p)) {
          if (!empty($vars) && (isset($vars[$p]) && strlen($vars[$p]) > 0)) {
            $value= $vars[$p];
          } else if (!empty($_GET)) {
            // SEARCH GET (by position)
            $keys = ($_GET) ? array_keys($_GET) : array();
            if ($keys && isset($keys[$p]) && isset($_GET[$keys[$p]])) {
              $value= $_GET[$keys[$p]];
            }
          }
        }
      }
    }

    return $value;
  }

  /**
   * Returns the segment at the specified position of the current uri
   *
   * <br><b>Usage</b><br>
   * <code>
   *      $id = $cms->segment(-1);
   * <code>
   *
   * Examples: getSegment(2)
   * 1) /activities/aaaa/bbbbb/ (returns 'aaaa')
   *
   * ----
   *
   * @param integer [$segment] The position of the uri to return its value
   * @param string [$uri] The uri to use
   * @return string The uri segment value
   */
  public static function getSegment ($segment='', $uri='')
  {
    $segment = trim($segment);
    $value = null;

    if (!empty($segment) && is_numeric($segment)) {
      $array = self::getSegments($uri);

      if ($array) {
        if ($segment > 0) {
          $segment--;
        } else {
          $segment = count($array) + $segment;
        }

        if ($segment >= 0 && isset($array[$segment]) && strlen($array[$segment]) > 0) {
          $value = $array[$segment];
        }
      }
    }

    return $value;
  }

  /**
   * Returns the uri in a segment array
   *
   * @param string [$uri] The uri to segment
   * @return array An array of segmented uri values
   */
  public static function getSegments ($uri='')
  {
    $uri = empty($uri) ? self::getUri(1, $_SERVER['REQUEST_URI']) : $uri;
    $array = is_array($uri) ? $uri : self::uriToArray($uri);

    return $array;
  }

  /**
   * Alias: Returns a sgment of a uri
   *
   * @param integer [$segment] The position of the uri to return its value
   * @param string [$uri] The uri to use
   * @return string The uri segment value
   */
  public static function segment ($segment='', $uri='')
  {
    return self::getSegment($segment, $uri);
  }

  /**
   * Alias: Returns the uri in a segment array
   *
   * @param string [$uri] The uri to segment
   * @return array An array of segmented uri values
   */
  public static function segments ($uri='')
  {
    return self::getSegments($uri);
  }

  /**
   * Returns true if a url has parameters
   *
   * @return string True if a url has parameters
   */
  public static function hasParams ()
  {
    if (self::getParams2()) {
      return true;
    }

    return false;
  }

  /**
   * Alias: Returns the URL parameters
   *
   * Example: /edit/users/1/
   *
   * @return string The current pathinfo array
   */
  public static function getParams2 ()
  {
    return self::getPathInfo();
  }

  /**
   * Returns the path info
   *
   * Example: /edit/users/1/
   *
   * @return string The current pathinfo array
   */
  public static function getPathInfo ()
  {
    $uri = isset($_SERVER['PATH_INFO']) ? $_SERVER['PATH_INFO'] : '';
    $uri = empty($uri) && defined('PATH_INFO') ? PATH_INFO : $uri;

    // 2014-03-11 | edr | PHP Running as CGI
    if (empty($uri) && isset($_SERVER['REQUEST_URI'])) {
      $parts = explode('?', $_SERVER['REQUEST_URI']);
      $uri = $parts[0];
    }

    return self::uriToArray($uri);
  }

  /**
   * Preps and returns a uri array
   *
   * @param string [$uri] The uri to convert to an array
   * @param boolean [$assoc=false] Whether to return an associative array
   * @return array A uri array
   */
  public static function uriToArray ($uri='', $assoc=false)
  {
    $array = array();

    if (isset($uri)) {
      if(is_string($uri)){
        $uri = Utils::endsWith($uri, '/') ? substr($uri, 0, strlen($uri)-1) : $uri;
        $uri = Utils::startsWith($uri, '/') && strlen($uri) > 1 ? substr($uri, 1) : $uri;

        if (!empty($uri)) {
          $array = explode('/', $uri);

          if ($assoc) {
            $array = self::arrayToAssoc($array, 1);
          }
        }
      } else if (is_array($uri)) {
        $array = $uri;

        if ($assoc) {
          $array = self::arrayToAssoc($array, 1);
        }
      }
    }

    return $array;
  }

  /**
   * Preps and returns a uri
   * @param array $array The array to convert
   * @return string A uri string
   */
  public static function arrayToUri ($array='')
  {
    $uri = '';

    if (is_array($array)) {
      foreach ($array as $key=>$value) {
        if (!is_numeric($key)
          && (is_string($value)
            || is_numeric($value)
            || is_bool($value))
        ){

          $key = urlencode($key);
          $value = urlencode($value);
          $uri .= "/{$key}/{$value}/";
        }
      }
    }

    $uri = self::fixSlashes($uri);

    return $uri;
  }

  /*
   * Determines and formats a href attribute for links
   *
   * @param string $link The link to format
   * @return string Returns a formatted href attribute
   */
  public static function getHref ($link)
  {
    $link = trim($link);
    $link = str_replace("'", '"', $link);
    $href = "href='http://{$link}'";

    if (Utils::startsWith($link, '#')
      || Utils::startsWith($link, 'http://')
      || Utils::startsWith($link, 'https://')
      || Utils::startsWith($link, 'ftp://')
    ){
      $href = "href='{$link}'";
    } else if(Utils::startsWith($link, 'javascript:')
      || (strpos($link, '("') && strpos($link, '")'))
    ){
      $href = "href='#' onclick='{$link}'";
    } else if(Utils::startsWith($link, '/')) {
      $link = SITE_URL.$link;
      $link = UrlUtils::fixSlashes($link);
      $href = "href='http://{$link}'";
    }

    return $href;
  }

  /**
   * Returns a unique page identifier for page look ups
   *
   * @param boolean [$extensions=false] Whether to include extensions
   * @param string [$uri] The uri to process
   * @return string Returns a unique page identifier
   */
  public static function getUri ($extensions=false, $uri='')
  {
    /*
        TODO: Detemine the effects of this patch.
        Added inorder to implement dynamic pages with restful uri's
        which take place in the PageController.php
    */
    if (empty($uri)) {
      if (defined('PAGE_URI_END')) {
        return PAGE_URI_END;
      }
      $uri = PAGE_URI;
    }

    $uri = explode('?', $uri);
    $uri = $uri[0];
    $uri = (!Utils::endsWith($uri, '/')) ? $uri.'/' : $uri;
    $uri = str_replace('//', '/', $uri);

    if (!$extensions) {
      // REPLACES EXTENSIONS (.php, .html, etc...)
      $uri = preg_replace("/([\.]+[A-Za-z]*+[\/])/", '/', $uri);
    }

    return $uri;
  }

  /**
   * Returns the current page
   *
   * @param boolean [$encode=true] Whether to encode the url
   * @param boolean|string [$protocol=false] Whether to include the protocol
   * @param string|array [$excludes] A list of query parameters to not include
   * @return string Returns the current page
   */
  public static function getCurrentPage ($encode=true, $protocol=false, $excludes='')
  {
    $query = self::getQuery('', $excludes);
    $query = (!empty($query)) ? '?'.$query : '';
    $page = PAGE_URI.$query;
    $page = ($protocol) ? self::fixUrl($page, $protocol) : $page;
    $page = ($encode) ? urlencode($page) : $page;

    return $page;
  }

  /**
   * Returns the last page
   *
   * @param boolean [$encode=true] Whether to encode the url
   * @param boolean [$protocol=false] Whether to include the protocol
   * @param boolean [$uri=false] Whether to return just the uri
   * @return string Returns the last page
   */
  public static function getLastPage ($encode=true, $protocol=false, $uri=false)
  {
    $page = urldecode(FormUtils::getPostValue('lastpage'));
    $page = (empty($page) && isset($_SERVER['HTTP_REFERER']) && !empty($_SERVER['HTTP_REFERER'])) ? $_SERVER['HTTP_REFERER'] : $page;

    if (empty($page) && isset($_SESSION['LAST_PAGE']) && !empty($_SESSION['LAST_PAGE'])) {
      $page = $_SESSION['LAST_PAGE'];
      unset($_SESSION['LAST_PAGE']);
    }

    $page = ($protocol) ? self::fixUrl($page) : $page;
    $page = ($encode) ? urlencode($page) : $page;

    if ($uri) {
      $array = preg_split('/(\/\/|\/)/', $page);
      array_shift($array);
      array_shift($array);
      $page = implode('/', $array);
    }

    return $page;
  }

  /**
   * Fixes malformed urls
   *
   * @param string [$url] The url to fix
   * @param boolean [$protocol=false] The protocol to use if suppplied
   * @return string The fixed url
   */
  public static function fixUrl ($url='', $protocol=false)
  {
    if (strpos($url, '://') === false) {
      if (!Utils::startsWith($url, SITE_URL)) {
        $url = SITE_URL.$url;
        $url = UrlUtils::fixSlashes($url);
      }

      if (!is_string($protocol)) {
        $protocol = explode('/', $_SERVER['SERVER_PROTOCOL']);
        $protocol = strtolower($protocol[0]);
      }

      $url = $protocol.'://'.$url;
    }

    return $url;
  }

  /**
   * Fixes double forward slashes in urls, uris, file paths, etc.
   *
   * <br><b>Example</b><br>
   * http://www.laserspineinstitute.com//about//index.php<br>
   * http://www.laserspineinstitute.com/about/index.php<br>
   *
   * ----
   *
   * @param string [$path] The path to fix slashes for
   * @return string The fixed path
   */
  public static function fixSlashes($path='')
  {
    $path = preg_replace('/(?<!:)([\/]+)/', '/', $path);
    $path = preg_replace('/(?<!:)([\\\]+)/', '\\', $path);

    if (preg_match('/:\\\{1}/', $path)) {
      $path = preg_replace('/\//', '\\', $path);
    }

    return $path;
  }
}