<?php
/**
 * This file contains only a single class.
 *
 * @file
 * @package CMS
 */

namespace WordPress\CMS\Utils;

class Utils extends UtilsBase
{
    /**
     * Processes and returns an array of parameters from the supplied string or array.
     *
     * EXAMPLES:
     * $params='first_name=Ed;last_name=Rodriguez;'
     * $params='first_name: Ed, last_name: Rodriguez'
     * $params='
     *     first_name=Ed;
     *     last_name=Rodriguez;'
     * $params='
     *     first_name: Ed,
     *     last_name: Rodriguez'
     *
     * Note:
     * The following examples make it easier to convert single argument functions to params functions
     *
     * MAIN PARAM EXAMPLES:
     * 1) FIRST ARGUMENT EXAMPLE:
     * $params='Ed, last_name: Rodriguez'
     * array(0=>'Ed', 'last_name'=>'Rodriguez')
     *
     * 2) SINGLE ARGUMENT EXAMPLE:
     * $params='Ed Rodriguez'
     * array(0=>'Ed Rodriguez');
     *
     * Special Delimiter Characters: (need escaping in string param versions)
     * = :
     *
     * @param string|array Mixed values to process
     * @param string [$groupDelimiter=;] The group delimiter
     * @param string [$pairDelimiter='='] The pair delimiter
     * @return array The params array
     */
    public static function prepParams($params='', $groupDelimiter=';', $pairDelimiter='=')
    {
        // First ARGUMENT (experimental)
        $arg = true;

        // DELIMITERS
        $groupDelimiters = array(',', ';');
        if (!in_array($groupDelimiter, $groupDelimiters)) {
            array_unshift($groupDelimiters, $groupDelimiter);
        }

        $pairDelimiters = array(':', '=');
        if (!in_array($pairDelimiter, $pairDelimiters)) {
            array_unshift($pairDelimiters, $pairDelimiter);
        }
        //$delimiters = array_merge($groupDelimiters, $pairDelimiters);

        // STRING TO ARRAY
        if (is_string($params) && strlen($params) > 0) {
            $str = $params;
            $results = array();

            /*
             2011-04-19 - edr - Changed (?&param) to (?1) for broader compatibility because
             some older versions of the PCRE Library don't support "recursive named subpatterns"
             as described here http://www.php.net/manual/en/regexp.reference.recursive.php
            */
            $pattern_params = PatternUtils::$patterns['params'];

            // 1) Removes jquery like object wrapper syntax if any ({ })
            $str = preg_replace('/^\{/i', '', trim($str));
            $str = preg_replace('/\}$/i', '', $str);
            $str = trim($str);

            preg_match_all($pattern_params, $str, $results);

            if ($results['param']) {
                // REMOVES NUMERICAL RESULT KEYS
                foreach ($results as $key=>$value) {
                    if (is_numeric($key)) {
                        unset($results[$key]);
                    }
                }

                // PARAM VALUE CLEANUPS
                $count = 0;
                foreach ($results['value'] as $key=>$value) {
                    $value = trim($value);

                    // FIRST ARGUMENT
                    if ($arg) {
                        $params = preg_replace('/'.preg_quote($results['param'][$count], '/').'/', '', $params, 1);
                    }

                    // 1) Removes closure syntaxes if any (, ;)
                    if (preg_match('/('.implode('|', $groupDelimiters).')$/xis', $value)) {
                        $value = preg_replace('/('.implode('|', $groupDelimiters).')$/xis', '', $value);
                    }

                    // 2) Removes quotation wrapper syntaxes if any (" ')
                    // This syntax if found was used for maintaining leading and ending whitespace
                    if (preg_match('/^("|\').*?(\1)$/xis', $value)) {
                        $value = preg_replace('/^("|\')/i', '', $value);
                        $value = preg_replace('/("|\')$/i', '', $value);
                    }

                    // 3) Unescapes the equalfier syntaxes (: =)
                    $value = preg_replace('/\\\('.implode('|', $pairDelimiters).')/i',"$1", $value);

                    $value = urldecode($value);
                    if (Utils::isBoolean($value)) {
                        $value = Utils::getBoolean($value);
                    }

                    $results['value'][$key] = $value;
                    $count++;
                }

                // FIRST ARGUMENT (Ed, last_name: Rodriguez)
                if ($arg) {
                    // 1) Removes jquery like object wrapper syntax if any ({ })
                    $params = preg_replace('/^\{/i', '', trim($params));
                    $params = preg_replace('/\}$/i', '', $params);
                    $params = trim($params);

                    // 2) Removes closure syntaxes if any (, ;)
                    if (preg_match('/('.implode('|', $groupDelimiters).')$/xis', $params)) {
                        $params = preg_replace('/('.implode('|', $groupDelimiters).')$/xis', '', $params);
                        $params = trim($params);
                    }

                    $arg = $params;
                }

                // SET PARAMS
                $params = array();

                // FIRST ARGUMENT
                if (isset($arg) && is_string($arg) && strlen($arg) > 0) {
                    $params[0] = $arg;
                }

                for ($i=0; $i<count($results['name']); $i++) {
                    $name = str_replace('-', '_', $results['name'][$i]);
                    $value = $results['value'][$i];

                    // 2016-07-12 | edr | Replace empty strings with nulls
                    $value = is_string($value) && ($value === '' || preg_match('/^null$/i', $value)) ? null : $value;

                    $params[$name] = $value;
                }

                // DEBUGGING
                //print_r($params);
                return $params;
            }
            // SINGLE ARGUMENT
            else{
                return array($params);
            }
        }else if (is_array($params) || is_object($params)) {
            $params2 = array();
            foreach ($params as $key=>$value) {
                if (!is_numeric($key)) {
                    if (Utils::isBoolean($value)) {
                        $value = Utils::getBoolean($value);
                    }
                    $params2[$key] = $value;
                }
            }

            return $params2;
        }

        return array();
    }

    /**
     * Converts stat objects to javascript flot data arrays
     *
     * @param array $stats
     * @return array
     */
    public static function toFlotData(&$stats)
    {
        $values = get_object_vars($stats);

        foreach ($values as $key => $val) {
            if (is_array($val)) {
                $data = [];
                foreach ($val as $key2 => $val2) {
                    $data[] = [$key2, $val2];
                }
                $stats->{$key} = $data;
            }
        }

        return $stats;
    }
}