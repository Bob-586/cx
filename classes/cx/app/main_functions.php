<?php

/**
 * @copyright (c) 2012
 * @author Robert Strutts
 */

namespace cx\app;

/*
 * Note: all methods must be public static function !!!
 */

class main_functions {

  public static function is_json($input) {

    $input = trim($input);

    if (substr($input, 0, 1) != '[' && substr($input, 0, 1) != '{' || substr($input, -1, 1) != ']' && substr($input, -1, 1) != '}') {
      return false;
    }
    return is_array(@json_decode($input, true));
  }

  /**
   * Purpose: To return the proper boolean output from any type of
   * boolean input (case-insensitive: true/false, 1/0). Useful for forms, etc...
   * @method get_bool_value
   * @return bool (true, false, or default of null if no valid input was given)
   */
  public static function get_bool_value($bool) {
    if (!isset($bool)) {
      return null;
    }
    if (is_array($bool)) {
      if (isset($bool[0])) {
        $bool = $bool[0];
      } else {
        return null;
      }
    }
    if (is_array($bool)) {
      return null;
    }

    $s_bool = self::get_serialized_bool($bool);
    if ($s_bool === true) {
      return true;
    } elseif ($s_bool === false) {
      return false;
    }

    switch (safe_strtolower($bool)) {
      case 'true':
      case '1':
        $value = true;
        break;
      case 'false':
      case false:
      case '0':
      case '2': // Odd I know, but forms have this as a default for No  
        $value = false;
        break;
      case true: // this must be done right here do not move true as it would bypass false!!!
        $value = true;
        break;
      default:
        $value = null;
        break;
    }
    return $value;
  }

  public static function safe_unserialize($var) {
    if (version_compare(PHP_VERSION, '7.0.0') >= 0) {
      return unserialize($var, ["allowed_classes" => false]);
    } else {
      return unserialize($var);
    }
  }

  public static function is_serialized($str) {
    if (is_array($str)) {
      return false;
    }

    /*
     * Make sure if found { but closure is missing it is not a whole serialized field!
     */
    if (stristr($str, '{') != false) {
      if (stristr($str, '}') === false) {
        return false;
      }
    }

    if (stristr($str, ';') != false && stristr($str, ':') != false) {
      return true;
    } else {
      return false;
    }
  }

  public static function get_serialized_bool($input) {
    if (is_array($input)) {
      return 'array';
    }

    if ($input == 'a:1:{i:0;s:4:"true";}' || $input == 'a:1:{i:0;b:1;}' ||
      $input == 'b:1;' || $input == 's:4:"true";') {
      return true;
    } elseif ($input == 'a:1:{i:0;s:5:"false";}' || $input == 'a:1:{i:0;b:0;}' ||
      $input == 'b:0;' || $input == 's:5:"false";') {
      return false;
    }

    return 'other';
  }

  public static function get_db_column($column) {
    $ae_db_col = explode(".", $column, 2);
    if (count($ae_db_col) > 1) {
      return trim($ae_db_col[1], "`"); // get only the columns
    } else {
      return trim($ae_db_col[0], "`"); // no table, just column
    }
  }

  public static function get_db_table($column) {
    if (self::found($column, ".") === false) {
      return '';
    }

    $ae_db_col = explode(".", $column, 2);
    return (isset($ae_db_col[0])) ? trim($ae_db_col[0], "`") : '';
  }

  public static function fix_db_column($column) {
    return (self::found($column, "`") || self::found($column, ".")) ? $column : "`{$column}`";
  }

  public static function get_array_index($a, $index) {
    return (array_key_exists($index, $a)) ? $a[$index] : false;
  }
  
  public static function found($data, $find) {
    return (stripos($data, $find) !== false);
  }

  public static function left($str, $length) {
    return substr($str, 0, $length);
  }

  public static function right($str, $length) {
    return substr($str, -$length);
  }

  public static function is_base64url_encoded($s) {
    // Check if there are valid base64 characters
    if (!preg_match('/^[a-zA-Z0-9\/\r\n+]*={0,2}$/', $s)) return false;

    // Decode the string in strict mode and check the results
    $decoded = self::base64url_decode($s);
    if (false === $decoded) {
      return false;
    }

    // Encode the string again
    if (self::base64url_encode($decoded) != $s) { 
      return false;
    }
    return true;  
  }
  
  public static function base64url_encode($data) { 
    return rtrim(strtr(base64_encode($data), '+/', '-_'), '='); 
  } 

  public static function base64url_decode($data) { 
    return base64_decode(str_pad(strtr($data, '-_', '+/'), strlen($data) % 4, '=', STR_PAD_RIGHT)); 
  } 

  /**
   * Purpose: To return list of globals for use with URL/AJAX calls, etc...
   * @param type $skip - array of globals to not include, default empty string (so all variables get processed).
   * @param type $type_of_globals - string (none, get, post, or request) defaults to request.
   * @param type $only_these - (optional) array of globals to only use.
   * @return string 
   */
  public static function get_globals($skip = '', $type_of_globals = 'request', $only_these = '') {
    $the_request = '';
    //      $type_of_globals = strtolower($type_of_globals);
    switch ($type_of_globals) {
      case 'get':
        $globals = $_GET;
        break;
      case 'post':
        $globals = $_POST;
        break;
      case 'request':
      default:
        $globals = $_REQUEST;
        break;
    }
    //    if (!is_array($skip) && !empty($skip)) $skip = array($skip);
    foreach ($globals as $key => $value) {
      if (is_array($skip) && in_array($key, $skip)) {
        continue;
      }
      if ($key == 'route') {
        $the_request .= '?' . $key . '=' . $value;
      } else {
        if ((is_array($only_these) && in_array($key, $only_these)) || !is_array($only_these)) {
          $the_request .= '&' . $key . '=' . $value;
        }
      }
    }

    return $the_request;
  }

  public static function is_associative_or_string($array) {
    if (is_string($array)) {
      return true;
    }
    return array_values($array) !== $array;
  }

  public static function call_class($class_name, $args = false) {
    if (class_exists($class_name, false)) {
      $arg_count = count($args);
      if ($args === false || $arg_count == 0) {
        return new $class_name();
      }
       
      if (method_exists($class_name,  '__construct') === false) { 
          exit("Constructor for the class <strong>$class_name</strong> does not exist, you should not pass arguments to the constructor of this class!"); 
      } 

      if (self::is_associative_or_string($args)) {
        return new $class_name($args); // This is an key=>value array or string, so just pass it...
      }
      
      $refMethod = new \ReflectionMethod($class_name,  '__construct'); 
      $params = $refMethod->getParameters(); 

      $re_args = array(); 
      $c = 0;
      foreach($params as $key => $param) {
        $c++;
        if ($c > $arg_count) {
          break; // Bail as it is passed the args count given!
        }
        if (! isset($args[$key])) {
          break;
        }
        
        if ($param->isPassedByReference()) { 
            $re_args[$key] = &$args[$key]; 
        } else { 
            $re_args[$key] = $args[$key]; 
        } 
      } 

      $refClass = new \ReflectionClass($class_name); 
      return $refClass->newInstanceArgs((array) $re_args); 

    } else {
      echo 'Class ' . $class_name . ' does not exist!';
      return false;
    }

  }  
  
  /**
   * Purpose: To make a temparary valid timestamp for a database
   */
  public static function expires_in_hours($hours = 1) {
    $hours = ($hours > 0 && $hours < 10) ? $hours : 1;
    $expires = new DateTime('NOW', new \DateTimeZone('UTC'));
    $expires->add(new DateInterval("PT0{$hours}H"));
    return $expires->format('Y-m-d H:i:s');
  }

  /**
   * Purpose: To convert a database timestamp into the users own Timezone.
   */
  public static function convert_time_zone($options) {
    $format = (isset($options['format'])) ? $options['format'] : 'normal';

    $session_time_zone = (isset($_SESSION[\cx_configure::a_get('cx', 'session_variable') . 'login_timezone'])) ? $_SESSION[\cx_configure::a_get('cx', 'session_variable') . 'login_timezone'] : 'America/Detroit';
    $tz = (isset($options['timezone']) && !empty($options['timezone'])) ? $options['timezone'] : $session_time_zone;

    $offset = (isset($options['offset'])) ? $options['offset'] : '';

    $db_time = (isset($options['time'])) ? $options['time'] : '';

    $new_time = (empty($db_time)) ? self::get_offset($offset) : self::get_offset_by($offset, $db_time);

    // Convert date("U"); unix timestamps to proper format for DateTime function...
    if (substr_count($new_time, ':') == 0) {
      $the_time = (empty($new_time) || $new_time == 'now' || $new_time == 'current') ? date("Y-m-d H:i:s") : date("Y-m-d H:i:s", $new_time);
    }

    $userTime = new \DateTime($the_time, new \DateTimeZone('UTC'));

    // Set the users timezone to their zone
    $userTime->setTimezone(new \DateTimeZone($tz));

    switch (safe_strtolower($format)) {
      case 'object':
        return $userTime;
      case 'unix':
        return $userTime->format('U');
      case 'day':
        return $userTime->format('l');
      case 'fancy':
        return $userTime->format('l jS \of F Y h:i:s A');
      case 'logging':
      case 'log':
        return $userTime->format('g:i A \o\n l jS F Y');
      case 'date':
        return $userTime->format('m/d/Y');
      case 'date-time':
        return $userTime->format('m/d/Y h:i:s A');
      case 'time':
        return $userTime->format('h:i A');
      case 'y-m-d':
        return $userTime->format('Y-m-d');
      case 'military':
        return $userTime->format('H:i:s');
      case 'standard':
      case 'computer':
      case 'database':
        return $userTime->format('Y-m-d H:i:s');
      case 'full':
        return $userTime->format('Y-m-d h:i:s A');
      case 'atom':
        return $userTime->format(DateTime::ATOM);
      case 'cookie':
        return $userTime->format(DateTime::COOKIE);
      case 'iso8601':
      case 'iso':
      case '8601':
        return $userTime->format(DateTime::ISO8601);
      case 'rfc822':
        return $userTime->format(DateTime::RFC822);
      case 'rfc850':
        return $userTime->format(DateTime::RFC850);
      case 'rfc1036':
        return $userTime->format(DateTime::RFC1036);
      case 'rfc1123':
        return $userTime->format(DateTime::RFC1123);
      case 'rfc2822':
        return $userTime->format(DateTime::RFC2822);
      case 'rfc3339':
        return $userTime->format(DateTime::RFC3339);
      case 'rss':
        return $userTime->format(DateTime::RSS);
      case 'w3c':
        return $userTime->format(DateTime::W3C);
      case 'normal':
        return $userTime->format('m/d/Y h:i A');
      default:
        return $userTime->format('Y-m-d h:i A');
    }
  }

  private static function is_valid_offset($offset) {
    if (substr_count($offset, 'second') > 0) {
      return true;
    } elseif (substr_count($offset, 'minute') > 0) {
      return true;
    } elseif (substr_count($offset, 'hour') > 0) {
      return true;
    } elseif (substr_count($offset, 'day') > 0) {
      return true;
    } elseif (substr_count($offset, 'week') > 0) {
      return true;
    } elseif (substr_count($offset, 'month') > 0) {
      return true;
    } elseif (substr_count($offset, 'year') > 0) {
      return true;
    } elseif (substr_count($offset, 'next') > 0) {
      return true;
    } elseif (substr_count($offset, 'last') > 0) {
      return true;
    } else {
      return false;
    }
  }

  private static function get_offset($offset) {
    return (self::is_valid_offset($offset)) ? strtotime($offset) : $offset;
  }

  private static function get_offset_by($offset, $db_time) {
    // strtotime requires a int timestamp
    if (substr_count($db_time, ':') > 0) {
      $UTC = new \DateTime($db_time, new \DateTimeZone('UTC'));
      $db_time = $UTC->format('U');
    }

    return (self::is_valid_offset($offset)) ? strtotime($offset, $db_time) : $db_time;
  }

  /*
   * Used by the application for save_settings method
   */

  public static function make_safer_string($s) {
    return preg_replace('/[^a-zA-Z0-9_ #,&;.\'-@]/', '', self::safe_escaped_string($s));
  }

  public static function safe_escaped_string($s) {
    $s = str_replace("\\", "", $s);
    $s = str_replace("\$", "\\$", $s);
    return str_replace('"', '\"', $s);
  }

  public static function unescaped_string($s) {
    $s = str_replace("\\$", "$", $s);
    return str_replace('\"', '"', $s);
  }

  /**
   * Purpose: To redirect to another page. Useful when page headers have already been sent...
   * @param type $url page to go to.
   */
  public static function delay_url($url, $delay_speed = 'slow', $return_data = false) {
    switch ($delay_speed) {
      case 'fast':
        $delay = '0';
        break;
      case 'long':
        $delay = '10';
        break;
      case 'slow':
        $delay = '1';
      default:
        $delay = intval($delay_speed);
        break;
    }

    $meta = '<meta http-equiv="refresh" content="' . $delay . ';url=' . $url . '">';

    if ($return_data) {
      return $meta;
    } else {
      echo $meta;
    }
  }

  /**
   * Purpose: To redirect to another page. Must be called before headers/content is loaded.
   * @param type $url
   * @param type $http_response_type 
   */
  public static function goto_url($url, $http_response_type = 'found') {
    switch ($http_response_type) {
      // 301 Moved Permanently
      case 'permanently':
      case 'moved':
      case '301':
        $http_response_code = '301';
        break;
      // 303 See Other
      case 'other':
      case 'see_other':
      case '303':
        $http_response_code = '303';
        break;
      // 307 Temporary Redirect
      case 'temp':
      case 'temporary':
      case '307':
        $http_response_code = '307';
        break;
      // 302 Found
      case 'found':
      default:
        $http_response_code = '302';
        break;
    }
    header('Location: ' . $url, TRUE, $http_response_code);
    exit;
  }

  /**
   * Purpose: To output all HTML select (drop down) option values.
   * @param type $options array of key=>value
   * @param type $default Item to select on.
   * @param type $select_by ['value'] for most of your needs.
   */
  public static function do_options($options, $default = '', $select_by = 'text') {
    $values = '';
    foreach ($options as $value => $text) {
      $compair_to = ($select_by == 'text') ? $text : $value;
      $selected = (!empty($default) && $default == $compair_to) ? 'selected' : '';
      $values .= "<option value=\"{$value}\" " . $selected . ">{$text}</option>";
    }
    return $values;
  }

  /**
   * Purpose: To get the current name of the page the user is on.
   * @return string 
   */
  public static function get_page() {
    if (!isset($_GET['m'])) {
      return 'index.php';
    }
    return basename($_GET['m'] . '.php');
  }

  /*
   * Make a readable password...
   * @length - length of random string (must be a multiple of 2)
   */

  public static function readable_random_string($length = 6) {
    $conso = array("b", "c", "d", "f", "g", "h", "j", "k", "l",
        "m", "n", "p", "r", "s", "t", "v", "w", "x", "y", "z");
    $vocal = array("a", "e", "i", "o", "u");
    $password = "";
    srand((double) microtime() * 1000000);
    $max = $length / 2;
    for ($i = 1; $i <= $max; $i++) {
      $password.=$conso[rand(0, 19)];
      $password.=$vocal[rand(0, 4)];
    }
    return $password;
  }

  public static function generate_random_string($length = 10) {
    $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $charactersLength = strlen($characters);
    srand((double) microtime() * 1000000);
    $randomString = '';
    for ($i = 0; $i < $length; $i++) {
      $randomString .= $characters[rand(0, $charactersLength - 1)];
    }
    return $randomString;
  }

  /**
   * Purpose: To remove non-alphanumeric chars and replace whitespace with underscores
   * @param type $string - input
   * @return type string of sanitised output 
   */
  public static function clean_string($string) {
    $string = preg_replace("/[^a-zA-Z0-9\s]/", " ", $string);
    return preg_replace("/\s+/", "_", $string);
  }

  /**
   * Purpose: Check string and return "none" if empty
   * @param type $string
   */
  public static function empty_string($string) {
    if (strlen(cleanString($string)) == 0) {
      return '<span class="empty">none</span>';
    }
    return $string;
  }

  /**
   * Purpose: To return the CSS for use with page.
   * @param type $file - external CSS file.
   * @param type $media - type of screen/device to render to.
   * @return type string of data for page to display the CSS.
   */
  public static function wrap_css($file, $media = 'all') {
    return "<link rel=\"stylesheet\" href=\"{$file}\" type=\"text/css\" media=\"{$media}\" />\r\n";
  }

  /**
   * Purpose: To return the JS for use with page.
   * @param type $file - external JS file.
   */
  public static function wrap_js($file) {
//    return "<script src=\"{$file}\" type=\"text/javascript\"></script>\r\n";
    return "<script type=\"text/javascript\">js_loader(\"{$file}\");</script>";
  }

  /**
   * Purpose: To do inline JavaScript.
   * @param type $code string of code to inline into page.
   * @return type 
   */
  public static function inline_js($code) {
    return "<script type=\"text/javascript\">\r\n//<![CDATA[\r\n    {$code}\r\n //]]> \r\n </script>\r\n";
  }

  /**
   * Purpose: To set session variable for use in template view.
   * @method setMessage
   * @param type $message 
   */
  public static function set_message($message, $alert = 'info', $fade_out = false) {
    $app_sess = (\cx_configure::a_get('cx', 'session_variable')) ? \cx_configure::a_get('cx', 'session_variable') : '';
    $fade = ($fade_out) ? ' alert-dismissable' : '';
    $message .= ($fade_out) ? '<button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>' : '';
    switch ($alert) {
      case 'info': $_SESSION[$app_sess . 'message'] = '<div class="alert alert-info' . $fade . '">' . $message . '</div>';
        break;
      case 'success': $_SESSION[$app_sess . 'message'] = '<div class="alert alert-success' . $fade . '">' . $message . '</div>';
        break;
      case 'warning': $_SESSION[$app_sess . 'message'] = '<div class="alert alert-warning' . $fade . '">' . $message . '</div>';
        break;
      case 'danger': $_SESSION[$app_sess . 'message'] = '<div class="alert alert-danger' . $fade . '">' . $message . '</div>';
        break;
      default: $_SESSION[$app_sess . 'message'] = '<div class="alert' . $fade . '">' . $message . '</div>';
        break;
    }
  }

  public static function do_alert($message, $alert = 'info', $fade_out = false) {
    $fade = ($fade_out) ? ' alert-dismissable' : '';
    $message .= ($fade_out) ? '<button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>' : '';
    switch ($alert) {
      case 'info': return '<div class="alert alert-info' . $fade . '">' . $message . '</div>';
      case 'success': return '<div class="alert alert-success' . $fade . '">' . $message . '</div>';
      case 'warning': return '<div class="alert alert-warning' . $fade . '">' . $message . '</div>';
      case 'danger': return '<div class="alert alert-danger' . $fade . '">' . $message . '</div>';
      default: return '<div class="alert' . $fade . '">' . $message . '</div>';
    }
  }

  /**
   * Purpose: To return to a view/form all months of the year.
   * @method months
   * @return type array of months.
   */
  public static function months() {
    return array(
        '01' => 'January',
        '02' => 'February',
        '03' => 'March',
        '04' => 'April',
        '05' => 'May',
        '06' => 'June',
        '07' => 'July',
        '08' => 'August',
        '09' => 'September',
        '10' => 'October',
        '11' => 'November',
        '12' => 'December'
    );
  }

  public static function states_array() {
    return array(
        'AL' => 'Alabama',
        'AK' => 'Alaska',
        'AZ' => 'Arizona',
        'AR' => 'Arkansas',
        'CA' => 'California',
        'CO' => 'Colorado',
        'CT' => 'Connecticut',
        'DE' => 'Delaware',
        'DC' => 'District of Columbia',
        'FL' => 'Florida',
        'GA' => 'Georgia',
        'HI' => 'Hawaii',
        'ID' => 'Idaho',
        'IL' => 'Illinois',
        'IN' => 'Indiana',
        'IA' => 'Iowa',
        'KS' => 'Kansas',
        'KY' => 'Kentucky',
        'LA' => 'Louisiana',
        'ME' => 'Maine',
        'MD' => 'Maryland',
        'MA' => 'Massachusetts',
        'MI' => 'Michigan',
        'MN' => 'Minnesota',
        'MS' => 'Mississippi',
        'MO' => 'Missouri',
        'MT' => 'Montana',
        'NE' => 'Nebraska',
        'NV' => 'Nevada',
        'NH' => 'New Hampshire',
        'NJ' => 'New Jersey',
        'NM' => 'New Mexico',
        'NY' => 'New York',
        'NC' => 'North Carolina',
        'ND' => 'North Dakota',
        'OH' => 'Ohio',
        'OK' => 'Oklahoma',
        'OR' => 'Oregon',
        'PA' => 'Pennsylvania',
        'RI' => 'Rhode Island',
        'SC' => 'South Carolina',
        'SD' => 'South Dakota',
        'TN' => 'Tennessee',
        'TX' => 'Texas',
        'UT' => 'Utah',
        'VT' => 'Vermont',
        'VA' => 'Virginia',
        'WA' => 'Washington',
        'WV' => 'West Virginia',
        'WI' => 'Wisconsin',
        'WY' => 'Wyoming',
    );
  }

  public static function countries_array() {
    return array(
        'AX' => 'Aland Islands',
        'AF' => 'Afghanistan',
        'AL' => 'Albania',
        'DZ' => 'Algeria',
        'AS' => 'American Samoa',
        'AD' => 'Andorra',
        'AO' => 'Angola',
        'AI' => 'Anguilla',
        'AQ' => 'Antarctica',
        'AG' => 'Antigua And Barbuda',
        'AR' => 'Argentina',
        'AM' => 'Armenia',
        'AW' => 'Aruba',
        'AU' => 'Australia',
        'AT' => 'Austria',
        'AZ' => 'Azerbaijan',
        'BS' => 'Bahamas',
        'BH' => 'Bahrain',
        'BD' => 'Bangladesh',
        'BB' => 'Barbados',
        'BY' => 'Belarus',
        'BE' => 'Belgium',
        'BZ' => 'Belize',
        'BJ' => 'Benin',
        'BM' => 'Bermuda',
        'BT' => 'Bhutan',
        'BO' => 'Bolivia',
        'BA' => 'Bosnia And Herzegovina',
        'BW' => 'Botswana',
        'BV' => 'Bouvet Island',
        'BR' => 'Brazil',
        'IO' => 'British Indian Ocean Territory',
        'BN' => 'Brunei',
        'BG' => 'Bulgaria',
        'BF' => 'Burkina Faso',
        'AR' => 'Burma', 
        'BI' => 'Burundi',
        'KH' => 'Cambodia',
        'CM' => 'Cameroon',
        'CA' => 'Canada',
        'CV' => 'Cape Verde',
        'KY' => 'Cayman Islands',
        'CF' => 'Central African Republic',
        'TD' => 'Chad',
        'CL' => 'Chile',
        'CN' => 'China',
        'CX' => 'Christmas Island',
        'CC' => 'Cocos (Keeling) Islands',
        'CO' => 'Columbia',
        'KM' => 'Comoros',
        'CG' => 'Congo',
        'CK' => 'Cook Islands',
        'CR' => 'Costa Rica',
        'CI' => 'Cote D\'Ivorie (Ivory Coast)',
        'HR' => 'Croatia (Hrvatska)',
        'CU' => 'Cuba',
        'CY' => 'Cyprus',
        'CZ' => 'Czech Republic static',
        'CD' => 'Democratic Republic Of Congo (Zaire)',
        'DK' => 'Denmark',
        'DJ' => 'Djibouti',
        'DM' => 'Dominica',
        'DO' => 'Dominican Republic',
        'TP' => 'East Timor',
        'EC' => 'Ecuador',
        'EG' => 'Egypt',
        'SV' => 'El Salvador',
        'GB' => 'England',
        'GQ' => 'Equatorial Guinea',
        'ER' => 'Eritrea',
        'EE' => 'Estonia',
        'ET' => 'Ethiopia',
        'EU' => 'European Union',
        'FK' => 'Falkland Islands (Malvinas)',
        'FO' => 'Faroe Islands',
        'FJ' => 'Fiji',
        'FI' => 'Finland',
        'FR' => 'France',
        'FX' => 'France, Metropolitan',
        'GF' => 'French Guinea',
        'PF' => 'French Polynesia',
        'TF' => 'French Southern Territories',
        'GA' => 'Gabon',
        'GM' => 'Gambia',
        'GE' => 'Georgia',
        'DE' => 'Germany',
        'GH' => 'Ghana',
        'GI' => 'Gibraltar',
        'GR' => 'Greece',
        'GL' => 'Greenland',
        'GD' => 'Grenada',
        'GP' => 'Guadeloupe',
        'GU' => 'Guam',
        'GT' => 'Guatemala',
        'GN' => 'Guinea',
        'GW' => 'Guinea-Bissau',
        'GY' => 'Guyana',
        'HT' => 'Haiti',
        'HM' => 'Heard And McDonald Islands',
        'HN' => 'Honduras',
        'HK' => 'Hong Kong',
        'HU' => 'Hungary',
        'IS' => 'Iceland',
        'IN' => 'India',
        'ID' => 'Indonesia',
        'IR' => 'Iran',
        'IQ' => 'Iraq',
        'IE' => 'Ireland',
        'IL' => 'Israel',
        'IT' => 'Italy',
        'JM' => 'Jamaica',
        'JP' => 'Japan',
        'JO' => 'Jordan',
        'KZ' => 'Kazakhstan',
        'KE' => 'Kenya',
        'KI' => 'Kiribati',
        'KW' => 'Kuwait',
        'KG' => 'Kyrgyzstan',
        'LA' => 'Laos',
        'LV' => 'Latvia',
        'LB' => 'Lebanon',
        'LS' => 'Lesotho',
        'LR' => 'Liberia',
        'LY' => 'Libya',
        'LI' => 'Liechtenstein',
        'LT' => 'Lithuania',
        'LU' => 'Luxembourg',
        'MO' => 'Macau',
        'MK' => 'Macedonia',
        'MG' => 'Madagascar',
        'MW' => 'Malawi',
        'MY' => 'Malaysia',
        'MV' => 'Maldives',
        'ML' => 'Mali',
        'MT' => 'Malta',
        'MH' => 'Marshall Islands',
        'MQ' => 'Martinique',
        'MR' => 'Mauritania',
        'MU' => 'Mauritius',
        'YT' => 'Mayotte',
        'MX' => 'Mexico',
        'FM' => 'Micronesia',
        'MD' => 'Moldova',
        'MC' => 'Monaco',
        'MN' => 'Mongolia',
        'ME' => 'Montenegro',
        'MS' => 'Montserrat',
        'MA' => 'Morocco',
        'MZ' => 'Mozambique',
        'MM' => 'Myanmar (Burma)',
        'NA' => 'Namibia',
        'NR' => 'Nauru',
        'NP' => 'Nepal',
        'NL' => 'Netherlands',
        'AN' => 'Netherlands Antilles',
        'NC' => 'New Caledonia',
        'NZ' => 'New Zealand',
        'NI' => 'Nicaragua',
        'NE' => 'Niger',
        'NG' => 'Nigeria',
        'NU' => 'Niue',
        'NF' => 'Norfolk Island',
        'KP' => 'North Korea',
        'MP' => 'Northern Mariana Islands',
        'NO' => 'Norway',
        'OM' => 'Oman',
        'PK' => 'Pakistan',
        'PW' => 'Palau',
        'PS' => 'Palestine',
        'PA' => 'Panama',
        'PG' => 'Papua New Guinea',
        'PY' => 'Paraguay',
        'PE' => 'Peru',
        'PH' => 'Philippines',
        'PN' => 'Pitcairn',
        'PL' => 'Poland',
        'PT' => 'Portugal',
        'PR' => 'Puerto Rico',
        'QA' => 'Qatar',
        'RE' => 'Reunion',
        'RO' => 'Romania',
        'RU' => 'Russia',
        'RW' => 'Rwanda',
        'SH' => 'Saint Helena',
        'KN' => 'Saint Kitts And Nevis',
        'LC' => 'Saint Lucia',
        'PM' => 'Saint Pierre And Miquelon',
        'VC' => 'Saint Vincent And The Grenadines',
        'SM' => 'San Marino',
        'ST' => 'Sao Tome And Principe',
        'SA' => 'Saudi Arabia',
        'SN' => 'Senegal',
        'SC' => 'Seychelles',
        'SL' => 'Sierra Leone',
        'SG' => 'Singapore',
        'SK' => 'Slovakia',
        'SI' => 'Slovenia',
        'SB' => 'Solomon Islands',
        'SO' => 'Somalia',
        'ZA' => 'South Africa',
        'GS' => 'South Georgia And South Sandwich Islands',
        'KR' => 'South Korea',
        'ES' => 'Spain',
        'LK' => 'Sri Lanka',
        'SD' => 'Sudan',
        'SR' => 'Suriname',
        'SJ' => 'Svalbard And Jan Mayen',
        'SZ' => 'Swaziland',
        'SE' => 'Sweden',
        'CH' => 'Switzerland',
        'SY' => 'Syria',
        'TW' => 'Taiwan',
        'TJ' => 'Tajikistan',
        'TZ' => 'Tanzania',
        'TH' => 'Thailand',
        'TG' => 'Togo',
        'TK' => 'Tokelau',
        'TO' => 'Tonga',
        'TT' => 'Trinidad And Tobago',
        'TN' => 'Tunisia',
        'TR' => 'Turkey',
        'TM' => 'Turkmenistan',
        'TC' => 'Turks And Caicos Islands',
        'TV' => 'Tuvalu',
        'UG' => 'Uganda',
        'UA' => 'Ukraine',
        'AE' => 'United Arab Emirates',
        'UK' => 'United Kingdom',
        'US' => 'United States',
        'UM' => 'United States Minor Outlying Islands',
        'UY' => 'Uruguay',
        'UZ' => 'Uzbekistan',
        'VU' => 'Vanuatu',
        'VA' => 'Vatican City',
        'VE' => 'Venezuela',
        'VN' => 'Vietnam',
        'VG' => 'Virgin Islands (British)',
        'VI' => 'Virgin Islands (US)',
        'WF' => 'Wallis And Futuna Islands',
        'EH' => 'Western Sahara',
        'WS' => 'Western Samoa',
        'YE' => 'Yemen',
        'YU' => 'Yugoslavia',
        'ZM' => 'Zambia',
        'ZW' => 'Zimbabwe'
    );
  }
  
  public static function jquery_load($code) {
    return "\r\n$(function() { \r\n \t {$code} \r\n }); \r\n";
  }

  function cx_post($url, $parms, $results='results') {
    $post = '';
    foreach($parms as $key=>$value) {
       if (is_object($key) || is_object($value)) {
           continue;
       } 
       $post .= " , {$key}: \"{$value}\"";
    }
    
    $post = ltrim($post, " , "); // Must remove first comma!!
    
    return "\r\n
   $.post(\"{$url}\", { {$post} }, function(status) { \r\n
       $(\"#{$results}\").html(status); \r\n
   });\r\n";
  }
  
  public static function get_real_ip_address() {
    if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
      $ip = $_SERVER['HTTP_CLIENT_IP'];
    } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
      //to check ip is pass from proxy
      $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
    } else {
      $ip = $_SERVER['REMOTE_ADDR'];
    }
    return $ip;
  }

  public static function get_host($ip) {
    if (self::exec_enabled() === false) {
      return 'Unknown....';
    }
    $host = exec("host {$ip}");
    $host = explode(' ', $host);
    return end($host); // The host is the last element of the array
  }  
  
  public static function download($file, $name, $mime) {
    if (headers_sent()) {
      return false;
    }

    if (file_exists($file)) {
      header("Content-Description: File Transfer");
      header("Content-Type: {$mime}");
      header("Content-Disposition: attachment; filename=\"{$name}\"");
      header('Content-Transfer-Encoding: binary');
      header('Expires: 0');
      header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
      header('Pragma: public');
      header('Content-Length: ' . filesize($file));
      ob_clean();
      flush();
      readfile($file);
      exit;
    } else {
      return false;
    }
  }

  /**
   * Converts bytes to a string representation including the type
   *
   */
  public static function bytes($bytes) {
    $unim = array("B", "KB", "MB", "GB", "TB", "PB");
    $c = 0;
    while ($bytes >= 1024) {
      $c++;
      $bytes = $bytes / 1024;
    }
    return number_format($bytes, ($c ? 2 : 0), ",", ".") . " " . $unim[$c];
  }
  
  public static function exec_enabled() {
    $safe_mode = ini_get('safe_mode');
    if (safe_strtolower($safe_mode) == 'on') {
      return false;
    }

    $disabled = array_map('trim', explode(',', ini_get('disable_functions')));
    return !in_array('exec', $disabled);
  }

  public static function git_log_version() {
    if (self::exec_enabled() === true) {
      $commit_data = exec('git log -1 --format="%cd"');
      $commit_date = (isset($commit_data[0])) ? $commit_data[0] : '';
      if (!empty($commit_data)) {
        return date('y.n.j', strtotime($commit_date));
      } else {
        return false;
      }
    } else {
      return false;
    }
  }

  // execute ImageMagick command 'convert' and convert PDF to JPG with applied settings
  public static function make_jpg_from_pdf($pdf_file, $save_to) {
    if (self::exec_enabled() === false) {
      return false;
    }
    exec('convert "' . $pdf_file . '" -colorspace RGB -resize 800 "' . $save_to . '"', $output, $return_var);

    if ($return_var == 0) {              //if exec successfuly converted pdf to jpg
      return true;
    } else {
      return false;
    }
  }

}
