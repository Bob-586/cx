<?php

namespace cx\common;

use cx\app\main_functions as main_fn;

class settings {
  private static $settings_file;
  
  public static function __construct() {
      self::$settings_file = PROJECT_BASE_DIR . 'settings' . DS . 'custom';
  }

  public static function save_settings($data) {
    if (!is_array($data)) {
      return false;
    }
    $a_data = array_merge($this->get_settings($data, true), $data);
    $ret = serialize($a_data);
    file_put_contents(self::$settings_file, $ret, LOCK_EX);

    $contents = "<?php\r\n";
    foreach ($a_data as $key => $value) {
      $key = main_fn::safe_escaped_string(strtoupper($key));
      $value = main_fn::safe_escaped_string($value);
      $contents .= "define(\"{$key}\",\"{$value}\");\r\n";
    }
    file_put_contents(CX_BASE_DIR . 'includes' . DS . 'settings.php', $contents, LOCK_EX);
    return true;
  }

  public static function get_settings($find, $save = false) {
    if (file_exists(self::$settings_file)) {
      $settings = file_get_contents(self::$settings_file);
      $settings_pos = stripos($settings, 'a:'); // make sure we have serial data
      if ($settings_pos !== false) {
        $a_settings = main_fn::safe_unserialize($settings);
        if (is_array($find)) {
          if (is_array($a_settings)) {
            $ret = '';
            foreach ($a_settings as $key => $value) {
              if ($save === false && !in_array($key, $find)) {
                continue;
              }
              if ($save === true && in_array($key, $find)) {
                continue;
              }
              $ret[$key] = $value;
            }
            return $ret;
          }
        }
      }
    }
    return array();
  }

}