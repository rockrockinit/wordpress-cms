<?php
/**
 * This file contains only a single class.
 *
 * @file
 * @package CMS
 */

namespace WordPress\CMS\Utils;

class UtilsBase
{
    public static function slug ($str = '', $delimiter = '-')
    {
      return self::vanity($str, $delimiter);
    }

    /**
     * Returns a vanity of the string
     *
     * @param string $string The string to convert to a vanity
     * @param boolean [$delimiter='-'] The space delimiter conversion
     * @return string A vanity of the string
     */
    public static function vanity ($str = '', $delimiter = '-')
    {
        $str = strtolower($str);
        $str = preg_replace('/\s/', $delimiter, $str);

        $delimiters = ['_', '-'];
        foreach($delimiters as $d) {
            if ($delimiter !== $d) {
                $str = preg_replace('/' . preg_quote($d, '/'). '/', $delimiter, $str);
            }
        }

        return $str;
    }

    /**
     * Returns a html attribute safe value
     *
     * @param string $string The string to clean
     * @param boolean [$stripslashes=1] Whether to strip slashes
     * @return string A cropped version of the string
     */
    public static function cleanup ($str = '', $stripslashes = 1){
        $str = ($stripslashes) ? stripslashes($str) : $str;
        return htmlentities($str, ENT_QUOTES, 'UTF-8');
    }

    /**
     * Returns a cropped version of the string
     *
     * @param string [$string] The string to crop
     * @param integer [$length=0] The maximum length to crop the string at
     * @param string [$ellipsis=0] The optional ellipsis to apend to the cropped string
     * @param boolean [$wordsOnly=0] An option to perform cropping by last word near provided length
     * @param boolean [$wordsBreaks=1] An option to add html word breaks
     * @return string A cropped version of the string
     */
    public static function cropString ($string = '', $length = 0, $ellipsis = 0, $wordsOnly = 0, $wordBreaks = 1)
    {
        $string = trim(strip_tags($string));

        if ($length > 0 && strlen($string) > $length) {
            if (!$wordsOnly) {
                $string = substr($string, 0, $length);
            } else {
                $count = $length - 1;
                for ($i = $length; $i < strlen($string); $i++) {
                    $char = substr($string, $i, 1);
                    if ($char == ' ') {
                        $string = substr($string, 0, $i);
                        break;
                    }
                }
            }

            if (!empty($ellipsis)) {
                if (is_string($ellipsis)) {
                    $string .= $ellipsis;
                } else if (is_bool($ellipsis) && $ellipsis == true) {
                    $string .= '...';
                } else if (is_numeric($ellipsis) && $ellipsis == 1) {
                    $string .= '...';
                }
            }
        }

        if ($wordBreaks) {
            $string = Utils::wbr($string);
        }

        return $string;
    }

    public static function dataToString ($input = '')
    {
        if (isset($input) && (is_array($input) || is_object($input))) {
            return json_encode($input, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
        }

        return $input;
    }

    /**
     * Returns formatted money
     *
     * @param string $amount The number to format
     * @param boolean [$trim=1] Whether to trim the cents off
     * @param string [$symbol=$] The money symbol to prepend
     * @return string Formatted money
     */
    public static function formatMoney ($amount, $trim = true, $symbol = '$')
    {
        $output = '';

        if ($amount > 0) {
            //setlocale(LC_MONETARY, 'en_US');
            //$output = money_format('%i', $number) . "\n";
            $output = number_format($amount, 2);
        }

        $output = trim($output);

        if (empty($output)) {
            $output = '0.00';
        }

        if ($trim) {
            $output = str_replace('.00', '', $output);
        }

        if (!empty($symbol)) {
            $output = $symbol . $output;
        }

        return $output;
    }

    /**
     * Returns a formatted phone number
     *
     * @param string $phone The phone number
     * @param string [$pattern=({$1}) {$2}-{$3}] The pattern to format the phone with
     * @return string A formatted phone number
     */
    public static function formatPhone ($phone = '', $pattern = '($1) $2-$3')
    {
        $val = $phone;
        $phone = is_integer($phone) ? trim($phone) : $phone;


        if (!empty($pattern) && !empty($phone) && is_string($phone)) {
            $phone = preg_replace('/^\+1/', '', $phone);
            $phone = preg_replace('/[^0-9]/', '', $phone);

            if (is_numeric($phone)) {
                if (strlen($phone) === 11 && preg_match('/^1/', $phone)) {
                    $phone = substr($phone, 1);
                }

                if (strlen($phone) === 10) {
                    if ($pattern && is_string($pattern)) {
                        $val = preg_replace('/^(\d{3})(\d{3})(\d{4})$/', $pattern, $phone);
                    }
                }
            }
        }

        return $val;
    }

    /**
     * Alias: Returns the boolean value of a value
     *
     * @param mixed $value The value to parse
     * @return boolean
     */
    public static function getBool ($value)
    {
        return Utils::getBoolean($value);
    }

    /**
     * Returns the boolean value of a value
     *
     * @param mixed $value The value to parse
     * @return boolean
     */
    public static function getBoolean ($value)
    {
        $bool = false;

        if (is_bool($value)) {
            return $value;
        } elseif (is_int($value)) {
            $bool = ($value == 1) ? true : false;
        } elseif (is_string($value)) {
            $value = strtolower(trim($value));
            $booleans = array('true', '1', 'yes', 'ok');
            $bool = (in_array($value, $booleans)) ? true : false;
        }

        return $bool;
    }

    /**
     * Add spaces to
     * @param string $name The name of the class
     * @return string Tha name with spaces
     */
    public static function getClassname ($name = '', $search = '', $replace = '')
    {
        $array = self::trimExplode('/\\\/', $name);
        $name = array_pop($array);
        $name = trim(preg_replace('/([A-Z]{1})([a-z]{1})/', " $1$2", $name));
        $name = self::getName($name);

        if ($search) {
            $name = preg_replace($search, $replace, $name);
            $name = trim($name);
        }

        return $name;
    }

    /*
     * Returns a formatted name
     *
     * @param string $str The string to format
     * @param boolean $words Whether to uppercase words
     * @param string [$delimiter=_] The delimiter to replace with spaces
     * @param boolean|number $crop Whether to crop the result
     * @return string Returns a formatted name
     */
    public static function getName ($str, $words = true, $delimiter = '_', $crop = false)
    {
        $str = (!empty($delimiter)) ? str_replace($delimiter, ' ', $str) : $str;
        $str = ($words) ? ucwords($str) : $str;
        $str = (is_numeric($crop) && $crop > 0) ? self::cropString($str, $crop, 1, 1) : $str;

        return $str;
    }

    /**
     * Alias: Returns true if a string is boolean in nature
     *
     * @param string $str The string to check
     * @return boolean
     */
    public static function isBool ($str)
    {
        return Utils::isBoolean($str);
    }

    /**
     * Returns true if a string is boolean in nature
     *
     * @param string $str The string to check
     * @return boolean
     */
    public static function isBoolean ($str)
    {
        $bool = false;

        if (is_bool($str)) {
            return $str;
        } elseif (is_string($str)) {
            $str = strtolower(trim($str));
            $booleans = array('true', 'false');
            $bool = (in_array($str, $booleans)) ? true : false;
        }

        return $bool;
    }

    /**
     * Validates whether a string is an email address
     *
     * preg_match('/^[^@]+@[a-zA-Z0-9._-]+\.[a-zA-Z]+$/', $email)
     *
     * [Validation Rules]
     * Username: At least 1 character and it isn't an @
     * Domain: At least 1 character and contains only valid characters.
     * TLD: At least 1 character, alpha only
     *
     * @param string $email The email to validate
     * @return boolean True if email is valid
     */
    public static function isValidEmail ($email)
    {
        $result = true;
        $email = trim($email);

        // Ex: Company Name <name@domain.com>
        if (strpos($email, '<') && strpos($email, '>')) {
            if (!preg_match('/[<]+[^@]+@[a-z0-9._-]+\.[a-z]+[>]/i', $email)) {
                $result = false;
            }
        } else {
            if (!preg_match('/^[^@]+@[a-z0-9._-]+\.[a-z]+$/i', $email)) {
                $result = false;
            }
        }

        return $result;
    }

    /**
     * Merges objects together
     *
     * @param object $obj1 The main object
     * @param object $obj2 The object to merge into the main
     * @param string [$props] The properties to merge
     * @param string [$excludes] The properties to exclude
     * @return object A merged object
     */
    public static function mergeObjects (&$obj1, $obj2, $props = '', $excludes = '')
    {
        if (isset($obj1) && is_object($obj1) && isset($obj2) && is_object($obj2)) {
            $props = (empty($props)) ? array_keys(get_object_vars($obj2)) : Utils::trimExplode(',', $props);
            $excludes = (empty($excludes)) ? array() : Utils::trimExplode(',', $excludes);

            if (isset($obj1->config)) {
                $excludes[] = $obj1->config->idColumn;
                $excludes[] = 'config';
            }

            foreach ($props as $prop) {
                if (!in_array($prop, $excludes)) {
                    $obj1->{$prop} = '';
                    if (isset($obj2->{$prop})) {
                        $obj1->{$prop} = $obj2->{$prop};
                    }
                }
            }

            //$obj1 = (object) array_merge((array) $obj1, (array) $obj2);
        }

        return $obj1;
    }

    /**
     * Removes all numerical keys and returns the array
     *
     * @param array [$array=array()] The array to convert
     * @return array The array to manipulate
     */
    public static function keyArray ($array = array())
    {
        foreach ($array as $key => $value) {
            if (is_numeric($key)) {
                unset($array[$key]);
            }
        }

        return $array;
    }

    /**
     * Escapes php variable dollar sign characters ($) in strings
     * before they get parsed. The PHP string parser tends to
     * greedily replace them as if they were variables.
     *
     * @param string $string The string to php esacpe
     * @return string The php escaped string
     */
    public static function phpEscape ($string)
    {
        return preg_replace('/\$/', '&#36;', $string);
    }

    /**
     * Converts an array of strings or objects to an associative array.
     *
     * <code>
     *      Utils::arrayToAssoc(array('first_name','Ed','last_name','Rodriguez'));
     *      // array('first_name'=>'Ed','last_name'=>'Rodriguez');
     *
     *      Utils::arrayToAssoc($objs, 'id');
     *      // array('(id)1'=>$obj1, '(id)2'=>$obj2);
     * </code>
     *
     * @param array [$array=array()] The array to convert
     * @param mixed [$value=0] Either a boolean decode value or an object string prop
     * @return array An associative array
     */
    public static function arrayToAssoc($array=array(), $value=0)
    {
        if (is_array($array)) {
            if (is_bool($value) || is_numeric($value)) {
                $decode = self::getBool($value);
            } else if(is_string($value)) {
                $prop = $value;
            }

            // ARRAY OF STRING
            if (isset($decode)) {
                $array2 = array();
                $count = 1;

                foreach ($array as $value) {
                    if (is_string($value)) {
                        if ($decode) {
                            $value = urldecode($value);
                        }

                        if ($count % 2 == 1) {
                            $key = $value;
                            $count = 1;
                        } else {
                            $array2[$key] = $value;
                        }

                        $count++;
                    }
                }

                return $array2;

                // ARRAY OF OBJECTS
            } else if (isset($prop)) {

                $assoc = array();

                foreach ($array as $item) {
                    if (isset($item->{$prop})) {
                        $assoc[$item->{$prop}] = $item;
                    }
                }

                return $assoc;
            }
        }

        return array();
    }

    /**
     * Sorts an array of objects and associative arrays by the specified property name or path
     *
     * @param array $array The array to sort
     * @param string $props The property name or path to sort by
     *    Name - 'first_name' (simple array)
     *    Path - 'contact->patients->first_name' (complex nested array)
     * @param string $order The order to return the sort
     *    ASC
     *    DESC
     * @param integer $flags Any sorting flags to pass that relate to the type of property
     *    SORT_REGULAR - compare items normally (don't change types)
     *    SORT_NUMERIC - compare items numerically
     *    SORT_STRING - compare items as strings
     *    SORT_LOCALE_STRING - compare items as strings, based on the current locale. It uses the locale, which can be changed using setlocale()
     *    SORT_NATURAL - compare items as strings using "natural ordering" like natsort()
     *    SORT_FLAG_CASE - can be combined (bitwise OR) with SORT_STRING or SORT_NATURAL to sort strings case-insensitively
     * @return boolean Returns true if sort was successful
     */
    public static function sortArray (&$array, $props, $order = 'ASC', $flags = '')
    {
        $success = false;

        if (isset($array) && is_array($array) && $array) {
            $order = trim($order);
            $indices = array();
            $props = explode('->', $props);
            $depth = count($props);

            foreach ($array as $item) {
                $value = '';

                for ($i = 0; $i < $depth; $i++) {
                    $prop = $props[$i];

                    if (isset($item)) {
                        if (is_object($item) && isset($item->{$prop})) {
                            $item = $item->{$prop};
                        } else if (is_array($item) && isset($item[$prop])) {
                            $item = $item[$prop];
                        }
                    }
                }

                if (is_string($item) || is_numeric($item) || is_bool($item)) {
                    $value = trim($item);
                }

                $indices[] = $value;
            }

            $order = (!preg_match('/^(ASC|DESC)$/i', $order)) ? SORT_ASC : $order;
            $order = (preg_match('/ASC/i', $order)) ? SORT_ASC : $order;
            $order = (preg_match('/DESC/i', $order)) ? SORT_DESC : $order;

            $flags = (is_string($flags) && strlen($flags) == 0) ? SORT_REGULAR : $flags;

            $success = array_multisort($indices, $order, $flags, $array);
        }

        return $success;
    }

    /**
     * Alias: Sorts an array of objects by the specified property.
     */
    public static function sortObjects (&$array, $props, $order = 'ASC', $flags = '')
    {
        return self::sortArray($array, $props, $order, $flags);
    }

    /**
     * Returns a safe id name for html purposes
     *
     * @param string [$str] The name to convert
     * @param string [$spaceChar=_] The character to replace spaces with
     * @param boolean [$random=0] Whether to add a random factor to the key
     * @return string Returns a safe id name
     */
    public static function stringToId ($str = '', $spaceChar = '_', $random = 0)
    {
        $output = '';
        $str = strtolower(trim($str));
        $str = preg_replace('/\ {2,}/', ' ', $str);
        $str = preg_replace('/\ /', $spaceChar, $str);

        for ($i = 0; $i < strlen($str); $i++) {
            if (preg_match('([0-9]|[a-z]|_|-)', $str[$i])) {
                $output .= $str[$i];
            }
        }

        // NEEDS TO START WITH CHAR
        if (!preg_match('/^[a-z]/', $output)) {
            $output = 'id' . $spaceChar . $output;
        }

        if ($random) {
            $output .= $spaceChar . rand(1, 100);
        }

        // CLEANS UP DOUBLE UNDERSCORES
        $output = preg_replace('/' . preg_quote($spaceChar, '/') . '{2,}/', $spaceChar, $output);

        return $output;
    }

    /**
     * Trims each value of an exploded string to array
     *
     * @param string $delimiter The delimiter to explode by
     * @param string $string The string to explode
     * @param boolean [$lowercase=0] Whether to lowercase each item
     * @return array An array with each item trimmed
     */
    public static function trimExplode ($delimiter, $string, $lowercase = 0)
    {
        $array = array();
        if (isset($delimiter) && is_string($delimiter)
            && isset($string) && is_string($string)
        ) {

            if (strlen($delimiter) > 1 && preg_match('/^\//i', $delimiter) && preg_match('/\/(m|i|s)*$/i', $delimiter)) {
                $temp = preg_split($delimiter, $string);
            } else {
                $temp = explode($delimiter, $string);
            }

            foreach ($temp as $value) {
                $value = trim($value);
                if ($lowercase) {
                    $value = strtolower($value);
                }
                if (strlen($value) > 0) {
                    $array[] = $value;
                }
            }
        }

        return $array;
    }

    /**
     * A cool way to create objects on the fly
     *
     * @params string|array $params Default object properties
     * @return object An object
     */
    public static function object ($params = '')
    {
        if (is_string($params) && $params != '' || is_array($params)) {
            $params = Utils::prepParams($params);
            if ($params) {
                return self::arrayToObject($params);
            }
        }

        return (object) null;
    }

    /**
     * Returns true if the string starts with the substring
     *
     * @param string $str The string to check
     * @param string $sub The substring to find
     * @param boolean [$ignore=0] Ignore case sensitivity
     * @return boolean
     */
    public static function startsWith ($str, $sub, $ignore=0)
    {
      $modifiers = empty($ignore) ? '' : 'i';
      $pattern = '/^'.preg_quote($sub, '/').'/'.$modifiers;

      return preg_match($pattern, $str);
    }

    /**
     * Returns true if the string ends with the substring
     *
     * @param string $str The string to check for a sequence
     * @param string $sub The sequence to find
     * @param boolean [$ignore=0] Ignore case sensitivity
     * @return boolean
     */
    public static function endsWith ($str, $sub, $ignore=0)
    {
      $modifiers = empty($ignore) ? '' : 'i';
      $pattern = '/'.preg_quote($sub, '/').'$/'.$modifiers;

      return preg_match($pattern, $str);
    }

    /**
     * Converts an array to an object
     *
     * [Input Ex]
     * array('first_name'=>'Ed','last_name'=>'Rodriguez')
     *
     * [Output Ex]
     * {first_name: Ed, last_name: Rodriguez}
     *
     * @param array $array The array to convert
     * @return array An object
     */
    public static function arrayToObject ($array = array())
    {
        $object = self::object();

        if (is_array($array)) {
            $keys = array_keys($array);
            foreach ($keys as $key) {
                $object->{$key} = $array[$key];
            }
        }

        return $object;
    }

    /*
     * Inserts word breaks into a string
     *
     * @param string [$str] The string to add word breaks to
     * @return string Returns a string with work breaks
     */
    public static function wbr ($str = '')
    {
        // SKIP HTML
        if (!preg_match('/<\//', $str)) {
            $str = preg_replace('/(\/|@|%|=|_)/', "$1<wbr />", $str);

            // Domain URLs
            $str = preg_replace('/(\w)(\.)(\w)/', "$1$2<wbr />$3", $str);
        }

        return $str;
    }
}