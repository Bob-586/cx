<?php
cx_configure::set('cx_framework', array(
'version' => '0.0.2',
));

define('CX_START_TIME', microtime(true));
define('CX_BASE_DIR', dirname(__FILE__) . DS);
define('CX_LOGS_DIR', CX_BASE_DIR . 'logs' . DS);
define('CX_INCLUDES_DIR', CX_BASE_DIR . 'includes' . DS);

ini_set('display_errors', 0);
ini_set("log_errors" , "1");
ini_set("error_log" , CX_LOGS_DIR . "Errors.log.txt");

function is_on_error_page() {
   return (stripos($_SERVER['REQUEST_URI'], "error.html") !== false);
}

require_once CX_INCLUDES_DIR . "common_functions.php";

function cx_folder_name() {
  $paths = dirname(__FILE__);
  $paths = str_replace('\\', "/", $paths); // Fix for Windows
  $folder_name = substr($paths, strrpos($paths, '/'));
  return $folder_name;  
}
define('CX_BASE_REF', CX_SITE_URL . cx_folder_name() );
// PROJECT_BASE_REF defined in common_functions.php

require_once CX_INCLUDES_DIR . 'emailer.php';
require_once CX_INCLUDES_DIR . 'errors.php';

if (cx_configure::exists('php_timezone')) {
  date_default_timezone_set(cx_configure::get('php_timezone'));
}

//setup php for working with Unicode data, if possible
if (extension_loaded('mbstring')) {
  mb_internal_encoding('UTF-8');
  mb_http_output('UTF-8');
  mb_http_input('UTF-8');
  mb_language('uni');
  mb_regex_encoding('UTF-8');
  setlocale(LC_ALL, "en_US.UTF-8");
}

// System up or down?
require_once CX_INCLUDES_DIR . "maintenance.php";

require_once CX_BASE_DIR . 'classes' . DS . 'cx' . DS . 'app' . DS . 'main_functions.php';
require_once CX_BASE_DIR . 'classes' . DS . 'cx' . DS . 'common' . DS . 'crypt.php';
require_once CX_BASE_DIR . 'classes' . DS . 'cx' . DS . 'app' . DS . 'app.php';

function cx_load_library($lib) {
    require_once(CX_BASE_DIR . 'libraries' . DS . $lib . '.php');
}

/**
 * Purpose: (debugging) - To display total time in seconds that have elpased since start time.
 * @param type $start int in seconds from calling microtime()
 */
function cx_timer($start) {
  $end = microtime(true);
  $parseTime = $end - $start;
  return array('string'=>'Took: ' . $parseTime . ' seconds', 'time'=>$parseTime);
}

$app = new \cx\app\app();