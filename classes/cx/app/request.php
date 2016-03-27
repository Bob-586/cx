<?php
/**
 * @copyright (c) 2015
 * @author Chris Allen, Robert Strutts 
 */

namespace cx\app;

use cx\app\main_functions as main_fn;

class request extends app {
  const csrf_token_name = '_csrf_token';

  public function __construct() {
    if (ini_get('register_globals') === 1) {
      echo "Error: Turn off Register Globals in INI file";
      exit;
    }
  }

  /**
	 * Generates a CSRF token
	 *
	 * @return string The computed CSRF token
	 */
	public function get_csrf_token($update = true) {
		$session_id = session_id();
    
    if ($update) {
      $_SESSION[\cx_configure::a_get('cx', 'session_variable') . 'token_time'] = time();
    }
    
    $enc = $this->load_class('cx\common\crypt');
    return $enc->make_hash($session_id, \cx_configure::a_get('security','csrf_security_level'));
	}
  
  public function do_csrf_token() {
    return "<input type=\"hidden\" name=\"". self::csrf_token_name . "\" value=\"{$this->get_csrf_token()}\" />";
  }
	
	/**
	 * Verifies that the given CSRF token is valid
	 *
	 * @param string $key The key used to generate the original CSRF token
	 * @param string $csrf_token The given CSRF token, null to automatically pull the CSRF token from the $_POST data
	 * @return boolean True if the token is valid, false otherwise
	 */
	public function verify_csrf_token($key = null, $csrf_token = null) {  
    $tk_time = (isset($_SESSION[\cx_configure::a_get('cx', 'session_variable') . 'token_time'])) ? $_SESSION[\cx_configure::a_get('cx', 'session_variable') . 'token_time'] : false;
   
    if ($tk_time === false) {
      return false;
    }
    
    $token_age = time() - $tk_time;
    $minutes_allowed = 60 * 12; // 60 seconds * X minutes
    if ($token_age > $minutes_allowed) {
      return false; // Too much time has went by since the request was made!
    }      
    
		if ($csrf_token === null && isset($_POST[self::csrf_token_name])) {
			$csrf_token = $_POST[self::csrf_token_name];
    }
    
    $update_csrf_timestamp = false;
		return ($this->get_csrf_token($update_csrf_timestamp) == $csrf_token);
	}
  
  /**
   * Purpose: To decode JQuery encoded objects, arrays, strings, int, bool types.
   * The content must be of application/json.
   * Returns the JSON encoded POST data, if any....
   * @param type $return_as_array (true) -> Array, (false) -> Object
   * @return type Object/Array|null
   * Note: It will return null if not valid json.
   */
  public function get_json_post_data($return_as_array = true) {
    $post_body = file_get_contents("php://input"); // get raw POST data.
    return json_decode($post_body, $return_as_array);
  }

  /**
   * Please note that not all web servers support: HTTP_X_REQUESTED_WITH
   * So, you need to code more checks! 
   * @return boolean true if AJAX request by JQuery, etc...
   */
  public function is_ajax() {
    if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && safe_safe_strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
      return true;
    } else {
      return false;
    }
  }

  public function compair_it($var, $var2) {
    return (safe_strtolower(trim($var)) === safe_strtolower(trim($var2)));
  }

  public function s_lowercase_trim($var) {
    return (safe_strtolower(trim($var)));
  }

  public function is_valid_id($var) {
    return ($var > 0) ? true : false;
  }

  public function is_not_valid_id($var) {
    return ($var < 1) ? true : false;
  }  
  
  public function is_int_set($var) {
    return ($var !== -1) ? true : false;
  }

  public function is_int_not_set($var) {
    return ($var === -1) ? true : false;
  }
  
  public function is_not_empty($var) {
    return ($var !== ':null' && !empty(trim($var))) ? true : false;
  }

  public function is_set($var) {
    return ($var !== ':null') ? true : false;
  }
  
  public function is_empty($var) {
    return ($var === ':null' || empty(trim($var))) ? true : false;
  }

  public function is_not_set($var) {
    return ($var === ':null') ? true : false;
  }
  
  /*
   * Purpose: To remove all undefined GLOBALS, only $expected will live on!
   * array $expected = array('firstname'=>'string', 'year'=>'number');
   */
  public function set_allowed($expected, $request_type="post") {
    switch (strtoupper($request_type)) {
      case 'GET':
        $globals = $_GET;
        break;
      case 'REQUEST':
        $globals = $_REQUEST;
        break;
      case 'POST':
      default:
        $globals = $_POST;
        break;
    }
    
    if (! is_array($expected) || count($expected) === 0) {
      return false;
    }
    
    foreach ($globals as $key=>$data) {
      
      if ($key == self::csrf_token_name) {
        continue; // We'll need this Security Token
      }
      
      if ( empty($data) ) {
        $this->_do_unset($key, $request_type); // Unset empty Super Global
        continue; // Next please
      } // end of if empty check
     
      if ($this->_do_allowed_check($expected, $key, $data, $request_type) === false) {
        $this->_do_unset($key, $request_type); // Remove not allowed Super Globals
        continue;
      }
      
    } // end of foreach
  }
  
  private function _do_allowed_check($expected, $key, $data, $request_type) {
    foreach($expected as $s_expected) {
      if ($s_expected == $key) {
        return true; // We have a non-key value pair, and key found, so it's good
      }
      
      $allowed_type = $s_expected;
      
      if (isset($expected[$key])) {
        // Key Value Pair found!! So check type
        if ($this->_check_type_valid($allowed_type, $data) === false) {
          return false; // NOT A VALID TYPE!!!
        }

        // It's allowed, so clean it up
        switch (strtolower($allowed_type)) {
          case 'filename':
            $safe_clean_filename = str_replace('%', '_', rawurlencode(basename($data)));
            $this->_do_update($key, $safe_clean_filename, $request_type); // Made Global safe
            break;
        }
        return true; // We have it!
      }
    }
    return false;
  }

  private function _check_type_valid($type, $data) {
    switch (strtolower($type)) {
      case 'string':
        if (is_string($data) && strlen($data) < 256) {
          return true;
        }
        break;
      case 'text':
        if (is_string($data)) {
          return true;
        }
        break;
      case 'number':
        if (is_numeric($data)) {
          return true;
        }
        break;
      case 'filename':
        if (is_string($data) && strlen($data) < 64) {
          if (strpos($data, '..') !== false) {
            return false; // Prevent goind up a folder!
          }
          return true;
        }
    }
    return false;
  }

  private function _do_unset($key, $request_type = "post") {
    switch (strtoupper($request_type)) {
      case 'GET':
        unset($_GET[$key]);
        break;
      case 'REQUEST':
        unset($_REQUEST[$key]);
        break;
      case 'POST':
      default:
        unset($_POST[$key]);
        break;
    }
  }

  private function _do_update($key, $value, $request_type = "post") {
    switch (strtoupper($request_type)) {
      case 'GET':
        $_GET[$key] = $value;
        break;
      case 'REQUEST':
        $_REQUEST[$key] = $value;
        break;
      case 'POST':
      default:
        $_POST[$key] = $value;
        break;
    }
  }
  
  public function get_var($var) {
    return (isset($_GET[$var])) ? $_GET[$var] : ':null';
  }

  public function post_var($var) {
    return (isset($_POST[$var])) ? $_POST[$var] : ':null';
  }

  public function request_var($var) {
    return (isset($_REQUEST[$var])) ? $_REQUEST[$var] : ':null';
  }

  public function clean_email($data) {
    return filter_var(trim($data), FILTER_SANITIZE_EMAIL);
  }

  public function encode_get_var($data) {
    return urlencode(filter_var(trim($data), FILTER_SANITIZE_URL));
  }

  public function decode_get_var($data) {
    return trim(urldecode($data));
  }

  public function encode_clean($data) {
    return htmlentities(trim($data), ENT_QUOTES, 'UTF-8');
  }

  public function decode_clean($data) {
    return html_entity_decode($data);
  }

  public function cookie_var($var) {
    if (isset($_COOKIE[\cx_configure::a_get('cx', 'session_variable') . $var])) {
      $c = $_COOKIE[\cx_configure::a_get('cx', 'session_variable') . $var];
      if (main_fn::is_base64url_encoded($c) === true) {
        $decoded = main_fn::base64url_decode($c);
        if (main_fn::is_serialized($decoded) === true) {
          return main_fn::safe_unserialize($decoded); // returns array
        }
        return $decoded; // return decoded cookie
      } else {
        return $c; // my cookie
      }
    } else {
      return false; // No cookie, found
    }
  }

  public function set_cookie_var($var, $content, $expires = 30, $length = "days", $domain = "", $path = "/", $secure = false, $httponly = false) {
    switch ($length) {
      case "days":
        $tx = (86400 * $expires);
        break;
      case "hours":
        $tx = (3600 * $expires);
        break;
      default:
        $tx = false; // cookie only lasts until browser is closed
        break;
    }

    if (is_array($content)) {
      $content = main_fn::base64url_encode(serialize($content));
    }

    if ($tx === false) {
      setcookie(\cx_configure::a_get('cx', 'session_variable') . $var, $content);
    } elseif (!empty($domain)) {
      setcookie(\cx_configure::a_get('cx', 'session_variable') . $var, $content, time() + $tx, $path, $domain, $secure, $httponly);
    } else {
      setcookie(\cx_configure::a_get('cx', 'session_variable') . $var, $content, time() + $tx);
    }
  }

  public function delete_cookie_var($var) {
    setcookie($var, "", time() - 3600);
  }

  public function file_var($var) {
    return (isset($_FILES[$var])) ? $_FILES[$var] : ':null';
  }

  public function server_var($var) {
    return (isset($_SERVER[$var])) ? $_SERVER[$var] : ':null';
  }
}

class static_request {
  public static $s_data;
  
  public static function init($input_type, $var) {
    switch(safe_strtolower($input_type)) {
      case 'get':
        $result = (isset($_GET[$var])) ? $_GET[$var]: ':null';
        break;
      case 'post':
        $result = (isset($_POST[$var])) ? $_POST[$var] : ':null';
        break;
      case 'files':
        $result = (isset($_FILES[$var])) ? $_FILES[$var] : ':null';
        break;
      case 'server':
        $result = (isset($_SERVER[$var])) ? $_SERVER[$var] : ':null';
        break;
      case 'var':
        $result = (isset($var)) ? $var : ':null';
        break;
      /**
       * @ todo add cookie
       */
      case 'request':
      default:  
        $result = (isset($_REQUEST[$var])) ? $_REQUEST[$var] : ':null';
        break;
    }
   
    static::$s_data = $result;
    return new static; 
  }
  
  public static function compair_it($var) {
    return (safe_strtolower(trim(static::$s_data)) === safe_strtolower(trim($var)));
  }
  
  public static function s_lowercase_trim() {
    return (safe_strtolower(trim(static::$s_data)));
  }
  
  public static function to_string() {
    return static::$s_data;
  }

  public static function to_int() {
    return intval(static::$s_data);
  }
  
  public static function is_valid_id() {
    return (static::$s_data > 0) ? true : false;
  }

  public static function is_not_valid_id() {
    return (static::$s_data < 1) ? true : false;
  }  
  
  public static function is_int_set() {
    return (static::$s_data !== -1) ? true : false;
  }

  public static function is_int_not_set() {
    return (static::$s_data === -1) ? true : false;
  }
  
  public static function is_not_empty() {
    return (static::$s_data !== ':null' && !empty(trim(static::$s_data))) ? true : false;
  }

  public static function is_set() {
    return (static::$s_data !== ':null') ? true : false;
  }
  
  public static function is_empty() {
    return (static::$s_data === ':null' || empty(trim(static::$s_data))) ? true : false;
  }

  public static function is_not_set() {
    return (static::$s_data === ':null') ? true : false;
  }  

  public static function clean_email() {
    return filter_var(trim(static::$s_data), FILTER_SANITIZE_EMAIL);
  }

  public static function encode_get_var() {
    return urlencode(filter_var(trim(static::$s_data), FILTER_SANITIZE_URL));
  }

  public static function decode_get_var() {
    return trim(urldecode(static::$s_data));
  }

  public static function encode_clean() {
    return htmlentities(trim(static::$s_data), ENT_COMPAT, 'UTF-8');
  }

  public static function decode_clean() {
    return html_entity_decode(static::$s_data);
  }
  
}