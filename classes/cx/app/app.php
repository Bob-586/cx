<?php

/**
 * @copyright (c) 2014
 * @author Chris Allen, Robert Strutts
 */

namespace cx\app;

use cx\app\main_functions as main_fn;
use Exception;

require_once PROJECT_BASE_DIR . 'classes' . DS . 'app' . DS . 'security.php';
require_once PROJECT_BASE_DIR . 'classes' . DS . 'app' . DS . 'assets.php';

class app {
  
  use security, assets;
  
  public $status_code = '200';
  public $compression_level = 0;
  public $short_url = false;
  public $slow_time_out = 1; // in seconds, before appending to slow.txt
  public $breadcrumb;
  public $active_crumb;
  public $active_dropdown; // adds active class to page_links.php
  public $title = '';
  public $keywords = '';
  public $description = '';
  public $robots = '';
  public $url_id;
  public $registry;
  public $request;
  public $response;
  public $session;
  public $languages;
  public $header_type;
  public $file;
  public $class;
  public $method;
  public $view;
  protected $page = '';
  protected $main_styles;
  protected $main_scripts;
  protected $styles;
  protected $scripts;
  protected $js_onready;  
  protected $header;
  protected $footer;
  protected $tags_to_check = array('div', 'span', 'form');
  private $do_time_alerts = true;
  private $app_cache;
  private $errors = array();
  private $a_last;
  
  public function error404($ignore = false) { // 404 page not found error 
    if (! isset($_SERVER['HTTP_HOST'])) {
        echo "Page NOT Found!"; // CLI Error
        exit;
    }       
      
    $view = new \cx\app\view();
    $view->set_template('page');
    $view->content = '<img src="'.PROJECT_BASE_REF.'/assets/images/404page.jpg" alt="Page not found." />';
    
    if ($ignore === false) {
      main_fn::set_message('Sorry, 404 - Page not found.', 'warning'); 
    }
    $view->bad_page();
    $view->fetch($this);
    exit;
  }

  public function clear_errors() {
    $this->errors = array();
  }
    
  /**
   * Sets the given errors into the object, Appending to existing errors (if any)
   *
   * @param array $errors An array of errors as returned by errors()
   * @see errors()
   */
  public function set_errors(array $errors) {
    array_push($this->errors, $errors);
  }

  /**
   * Return all errors
   *
   * @return mixed An array of error messages indexed as their field name, boolean false if no errors set
   */
  public function get_errors() {
    if (count($this->errors) == 0) {
        return false;
    }
    return $this->errors;
  }

    /**
   * Method will take the error array and make it a string if errors, else false
   */
  public function get_all_errors_to_string() {
    $errors = $this->get_errors();

    if ($errors === false) {
      return false; // No Errors
    }

    $nl = (isset($_SERVER['HTTP_HOST'])) ? "<br />\r\n" : "\r\n"; // If web site, break, else CLI New Lines
    $strong = (isset($_SERVER['HTTP_HOST'])) ? "<strong>" : '';
    $strong_end = (isset($_SERVER['HTTP_HOST'])) ? "</strong>" : '';
    $ret = ''; // Init String
    $err_count = 0;
    
    foreach ($errors as $no => $err_msg) {
      foreach ($err_msg as $cat => $error) {
        if (is_array($error)) {
          foreach ($error as $key => $value) {
            $k = ( isset($key) && !empty($key) ) ? $key . ": " : '';
            if ($this->a_last == $cat.$k.$value) {
              continue; // Loop as its duplicated
            }
            $this->a_last = $cat.$k.$value;
            $err_count++;
            $err_number = ($err_count > 1) ? "#{$err_count}" : "";
            $ret .= "{$strong}Error {$err_number}:{$strong_end} {$cat} [ {$k}{$value} ]{$nl}";
          }
        } elseif (is_string($error)) {
          if ($this->s_last == $error) {
              continue; // Loop as its duplicated
            }
          $this->s_last = $error;
          $err_count++;
          $err_number = ($err_count > 1) ? "#{$err_count}" : "";
          $ret .= "{$strong}Error {$err_number}:{$strong_end} {$error}{$nl}";
        }
      }
    }
    return rtrim($ret, $nl);
  }

    public function show_errors($ajax = false) {
    $errors = $this->get_all_errors_to_string();
    
    if ($errors === false || empty($errors)) {
      return '';
    }

    $this->clear_errors();
    
    if ($ajax === true || $this->request->is_ajax() === true) {
      \cx\app\cx_api::error(array('code'=>422, 'reason'=>$errors));
    }

    return "<div class=\"alert alert-danger fade in\">{$errors}</div>";
  }
  
  public function do_registry() {
    require_once CX_BASE_DIR . 'classes' . DS . 'cx' . DS . 'app' . DS . 'cx_api.php';
    require_once CX_BASE_DIR . 'classes' . DS . 'cx' . DS . 'app' . DS . 'registry.php';
    require_once CX_BASE_DIR . 'classes' . DS . 'cx' . DS . 'app' . DS . 'request.php';
    require_once CX_BASE_DIR . 'classes' . DS . 'cx' . DS . 'app' . DS . 'session.php';
    require_once CX_BASE_DIR . 'classes' . DS . 'cx' . DS . 'app' . DS . 'document.php';
    require_once CX_BASE_DIR . 'classes' . DS . 'cx' . DS . 'app' . DS . 'response.php';
    require_once CX_BASE_DIR . 'classes' . DS . 'cx' . DS . 'app' . DS . 'view.php';

    // Registry
    $this->registry = new \cx\app\registry();

    // Request
    $this->request = new \cx\app\request();
    $this->registry->set('request', $this->request);

    // Session
    $this->session = new \cx\app\session();
    $this->registry->set('session', $this->session);

    // Language Detection
    $this->languages = array();

    // Document
    $this->registry->set('document', new \cx\app\document());
    
    // Response
    $this->response = new \cx\app\response();
  }
  
  /**
   * This is a security feature for this class
   * @method filter_uri
   * @param uri of view
   * @return path without .. in it
   */
  public function filter_uri($uri, $secure = true) {
    $uri = ($secure === true) ? preg_replace('#[^/\a-zA-Z0-9_]#', '', $uri) : $uri;
    return str_replace('..', '', $uri);
  }

  /**
   * @method filter_class
   * @param type $class
   * @return type string of safe class name
   */
  public function filter_class($class) {
    return preg_replace('/[^a-zA-Z0-9_]/', '', $class);
  }

  /**
   * This is used by the constructor to route to the correct method in the controller script
   * @return void
   */
  public function load_controller() {
    $this->action($this->file, $this->class, $this->method);
  }

  public function load_cli_controller() {
    
    $argv = $GLOBALS['argv'];

    $request_uri = '';

    // Build the request URI based on the command line parameters
    $num_args = count($argv);

    if ($num_args > 1) {

      $uri = $this->filter_uri($argv[1]);
      
      $uri = str_replace(".html", "", $uri); // remove .html
      
      $parts = explode('/', $uri);
      if (count($parts) < 3) {
        $this->error404();
      }

      //check for default site controller first
      if (is_dir(PROJECT_BASE_DIR . 'controllers' . DS . $parts[1])) {
        if (is_file(PROJECT_BASE_DIR . 'controllers' . DS . $parts[1] . DS . basename($parts[2]) . '.php')) {
          $this->file = PROJECT_BASE_DIR . 'controllers' . DS . $parts[1] . DS . basename($parts[2]) . '.php';
          $this->class = $this->filter_class('cx_loader_' . $parts[1] . '_' . $parts[2]);
        }
      }

      $this->method = "cli_".$parts[3]; // CLI methods must start with cli_
    } else {
      $this->file = PROJECT_BASE_DIR . 'controllers' . DS . 'app' . DS . DEFAULT_PROJECT . '.php';
      $this->class = $this->filter_class('cx_loader_' . 'app' . '_' . DEFAULT_PROJECT);
      $this->method = 'cli_index';
    }

    $this->action($this->file, $this->class, $this->method);
  }

  private function api_method_not_found() {
    $status = 400; // Bad Request
    \cx\app\cx_api::error(array('code' => $status, 'reason' => 'Command not found'));
  }

 private function action($file, $class, $method) {
    if (!empty($_SERVER['REQUEST_URI'])) {
      $use_api = $this->is_api();
    } else {
      $use_api = false;
    }

    if (!file_exists($file)) {
      if ($use_api) {
        $this->api_method_not_found();
      } else {
        $this->error404();
      }
    }

    require_once ($file);
    
    $controller = new $class();

    if ($use_api) {

      if (!empty($method) && method_exists($controller, $method) && method_exists($controller, $method . "_api")) {
        return $controller->$method();
      } else {
        $this->api_method_not_found();
      }
    } else {

      if (!empty($method) && method_exists($controller, $method)) {
        return $controller->$method();
      } else {
        if (empty($method) && method_exists($controller, 'index')) {
          return $controller->index();
        } else {
          $this->error404();
        }
      }
    }
  }

  /**
   * @return string of clean url
   * @example url.com/123
   */
  private function parse_path() {
    $path = array();
    if (isset($_SERVER['REQUEST_URI'])) {
      $request_path = explode('?', $_SERVER['REQUEST_URI']);

      $path['base'] = rtrim(dirname($_SERVER['SCRIPT_NAME']), '\/');
      $path['call_utf8'] = substr(urldecode($request_path[0]), strlen($path['base']) + 1);
      $path['call'] = utf8_decode($path['call_utf8']);
      if ($path['call'] == basename($_SERVER['PHP_SELF'])) {
        $path['call'] = '';
      }
      $path['call_parts'] = explode('/', $path['call']);
      if (isset($request_path[1])) {
        $path['query_utf8'] = urldecode($request_path[1]);
        $path['query'] = utf8_decode(urldecode($request_path[1]));
        $vars = explode('&', $path['query']);
        foreach ($vars as $var) {
          $t = explode('=', $var);
          $ok = (isset($t[1])) ? $t[1] : '';
          $path['query_vars'][$t[0]] = $ok;
        }
      } else {
        $path['query_utf8'] = '';
        $path['query'] = '';
      }
    }
    return $path;
  }

  public function router($route, $method, $is_controller = false) {
    $file = "";
    $class = "";
    $path = $this->parse_path();
    if (!empty($path['call_parts']['0'])) {
      $this->url_id = $path['call_parts']['0'];
    }
    
    if ($this->request->is_empty($route)) {
      $uri = '/app/' . DEFAULT_PROJECT;
    } else {
      $uri = $route;
    }

    $uri = $this->filter_uri($uri);
    $parts = explode('/', $uri);
    if (count($parts) < 3) {
      $this->error404();
    }

    //check for default site controller first
    if (is_dir(PROJECT_BASE_DIR . 'controllers' . DS . $parts[1])) {
      if (is_file(PROJECT_BASE_DIR . 'controllers' . DS . $parts[1] . DS . basename($parts[2]) . '.php')) {
        $file = PROJECT_BASE_DIR . 'controllers' . DS . $parts[1] . DS . basename($parts[2]) . '.php';
        $class = $this->filter_class('cx_loader_' . $parts[1] . '_' . $parts[2]);
      }
    }

    if ($this->request->is_empty($method)) {
      $method = "";
    } else {
      $method = $method;
    }

    // Stop any magical methods being called
    if (substr($method, 0, 2) == '__') {
      $method = "";
    }

    if ($is_controller === true) {
      $this->file = $file;
      $this->class = $class;
      $this->method = $method;
    } else {
      return $this->action($file, $class, $method);
    }
  }  

  public function __construct() {
    if (cx_bool(\cx_configure::a_get('cx', 'short_url')) === true) {
      $this->short_url = true;
    }
    
    $this->do_registry();
    $this->set_code();
    $this->router($this->request->get_var('route'), $this->request->get_var('m'), true);
  }
  
  public function __destruct() {
    // Do not add ob_get_contents, as it will break JS pages!!!
    $page = (isset($this->page) && ! empty($this->page)) ? $this->page : '';
    $len = strlen($page);
    if ($len > 0) {
      if (cx_not_done()) {
        $this->slow_check();
        echo $this->end_fn($this->check_tags($page));
      }
    }
  }

  /*
   * Check for any open tags and report them along with elapsed server time.
   */

  public function check_tags($page) {
    $alert = '';
    $output = '';
    $l_page = safe_strtolower($page);

    foreach ($this->tags_to_check as $tag_name) {

      $otag = "<{$tag_name}";
      $ctag = "</{$tag_name}>";
      $open = substr_count($l_page, $otag);
      $closed = substr_count($l_page, $ctag);
      $total_still_open = $open - $closed;

      if ($total_still_open > 0) {
        $msg = "{$total_still_open} possibly MISSING closing {$tag_name} !!!";
        $alert .= "cx_log('{$msg}');\r\n";
        $output .= (\cx_configure::a_get('cx', 'live') === true) ? "<!-- {$msg} -->\r\n" : "{$msg} <br>\r\n";
      } elseif ($total_still_open < 0) {
        $msg = abs($total_still_open) . " possibly MISSING opening {$tag_name} !!!";
        $alert .= "cx_log('{$msg}');\r\n";
        $output .= (\cx_configure::a_get('cx', 'live') === true) ? "<!-- {$msg} -->\r\n" : "{$msg} <br>\r\n";
      }
    }
    return array('output' => $output, 'alert' => $alert);
  }

  public function slow_check() {
    // If it's a slow function like uploading, skip slow_check...
    if ($this->do_time_alerts === false) {
      return false;
    }

    $end_time = cx_timer(CX_START_TIME);
    
    $slow_time_out = (\cx_configure::a_get('cx', 'seconds_to_log_slow_timeout') > 1) ? \cx_configure::a_get('cx', 'seconds_to_log_slow_timeout') : 2;    
    
    if ($end_time['time'] > $slow_time_out) {
      $alert_file = CX_LOGS_DIR . 'slow.txt';

      $a_time = array('format' => 'fancy', 'timezone' => 'America/Detroit');

      $EST = main_fn::convert_time_zone($a_time);
      $script = cx_current_page_name();
      $q = (isset($_SERVER['QUERY_STRING'])) ? $_SERVER['QUERY_STRING'] : '';
      $alert = CX_CANONICAL . " AKA {$script}?{$q} \r\n({$EST} EST) - Server is slow...{$end_time['string']}.\r\n";
      file_put_contents($alert_file, $alert, FILE_APPEND);
    }
  }

  private function end_fn($a) {
    $alert = $a['alert'];
    $output = $a['output'];

    $end_time = cx_timer(CX_START_TIME);
    $timeout = (\cx_configure::a_get('cx', 'web_alert_timeout') > 1) ? \cx_configure::a_get('cx', 'web_alert_timeout') : 5;    
    
    if ($end_time['time'] > $timeout) {
      if ($this->do_time_alerts === true) {
        $msg = "Server is slow...{$end_time['string']}";
        $alert .= "cx_log('{$msg}');\r\n";
        $output .= (\cx_configure::a_get('cx', 'live') === true) ? "<!-- {$msg} -->\r\n" : "{$msg} <br>\r\n";
      }
    } else {
      $output .= "<!-- Server is fine...{$end_time['string']} -->\r\n";
    }

    if (!empty($alert)) {
      $output .= $this->inline_js($alert);
    }
    return $output;
  }

  /*
   * Use this method to prevent time out alerts for long processes.
   */

  public function long_fn_no_time_alerts() {
    $this->do_time_alerts = false;
  }

  private function parm_encode($q) {
    if (substr_count($q, "=") > 0) {
      $parms = explode("=", $q);
      $parm = urlencode($parms[0]);
      $value = urlencode($parms[1]);
      return $parm . "=" . $value . "&";
    } else {
      return urlencode($q);
    }
  }

  public function safe_url($vars) {
    $new = '';

    if (substr_count($vars, "&") > 0) {
      $qs = explode("&", $vars);
      foreach ($qs as $q) {
        $new .= $this->parm_encode($q);
      }
      $new = rtrim($new, "&");
    } else {
      $new = rtrim($this->parm_encode($vars), "&");
    }

    return $new;
  }

  /**
   * Purpose to auto create url based on if short url is needed
   * @param type $route
   * @param type $method
   * @param type $vars
   * @return type url
   */
  public function get_url($route, $method, $vars = '') {
    $route = ltrim($route, "/");

    if (is_array($vars)) {
      $vars = http_build_query($vars);
    } elseif (is_string($vars) && !empty($vars)) {
      $vars = $this->safe_url($vars);
    }

    if ($this->short_url === true) {
      $vars = (!empty($vars)) ? "?{$vars}" : '';
      return rtrim(PROJECT_BASE_REF, "/") . "/{$route}/{$method}.html{$vars}";
    } else {
      $vars = (!empty($vars)) ? "&{$vars}" : '';
      return rtrim(PROJECT_BASE_REF, "/") . "?route={$route}&m={$method}{$vars}";
    }
  }

  public function get_api_url($route, $method, $vars = '') {
    $route = ltrim($route, "/");

    if (is_array($vars)) {
      $vars = http_build_query($vars);
    } elseif (is_string($vars) && !empty($vars)) {
      $vars = $this->safe_url($vars);
    }

    if ($this->short_url === true) {
      $vars = (!empty($vars)) ? "?{$vars}" : '?x=0';
      return rtrim(PROJECT_BASE_REF, "/") . "/api/{$route}/{$method}{$vars}&api=true";
    } else {
      $vars = (!empty($vars)) ? "&{$vars}" : '';
      return rtrim(PROJECT_BASE_REF, "/") . "?route={$route}&m={$method}&code=/api/{$vars}&api=true";
    }
  }

  public function is_api() {
    return (substr_count($_SERVER['REQUEST_URI'], "/api/") == 1 && isset($_GET['api']) && $_GET['api'] == 'true') ? true : false;
  }

  public function do_response($type) {
    switch ($type) {
      case 'html':
        $this->compression_level = 3;
        $this->response->add_header('Content-Type: text/html; charset=utf-8');
        break;
      case 'xml':
        $this->response->add_header(array('header' => 'Content-Type: text/xml;', 'status' => $this->status_code));
        break;
      case 'json':
        $this->response->add_header(array('header' => 'Content-Type: application/json; charset=utf-8', 'status' => $this->status_code));
        break;
      case 'php':
        $this->response->add_header(array('header' => 'Content-Type: text/php; charset=utf-8', 'status' => $this->status_code));
    }

    $this->response->set_compression($this->compression_level);
    $this->registry->set('response', $this->response);
  }
  
  public function set_title_and_header($title) {
    $this->title = $title;
    $this->header = $title;
  }

  /**
   * Purpose: To add the H1 header to the template page.
   * @param type $header
   */
  public function set_header($header) {
    $this->header = $header;
  }

  public function set_footer($footer) {
    $this->footer = $footer;
  }

  public function set_title($title) {
    $this->title = $title;
  }

  public function add_to_style($style) {
    if (!empty($style)) {
      $this->styles .= "<style>{$style}</style>\r\n";
    }
  }

  public function js_log($log) {
    $this->add_to_javascript("cx_log('{$log}');");
  }
  
  public function add_to_javascript($js) {
    if (!empty($js)) {
      $this->scripts .= $this->inline_js($js);
    }
  }

  public function form_js($js) {
    $this->scripts .= $js;
  }

  private function wrap_asset($file, $scope = '') {
    switch(strtolower($scope)) {
      case 'project':
      case 'app':
        $safe_file = $this->filter_uri($file);
        $cx = 'assets/'.$safe_file;
        if (file_exists(PROJECT_BASE_DIR . $cx)) {
          return PROJECT_BASE_REF . '/' . $cx;
        } else {
          return false;
        }              
      case 'framework':
      case 'cx':
        $safe_file = $this->filter_uri($file);
        $cx = 'assets/'.$safe_file;
        if (file_exists(CX_BASE_DIR . $cx)) {
          return CX_BASE_REF . '/' . $cx;
        } else {
          return false;
        }      
      case 'cdn':
        return (cx_found($file, '://') === true) ? $file : PROTOCOL . $file;
      default:
      case '':
        return $file;
    }
  }

  /**
   * @method add_css
   * @param file path to the css file being added
   */
  public function add_css($file, $scope = '') {
    $css = $this->wrap_asset($file, $scope);
    if ($css === false) {
      $this->js_log($file . " - {$scope} Asset Failed to Load!");
      return false;
    }  
    $this->styles .= $this->wrap_css($css);
    return true;
  }

  public function add_js_onready($code) {
    $this->js_onready .= $this->inline_js(main_fn::jquery_load($code));
  }

  /**
   * @method add_js
   * @param file path to the JS file being added
   */
  public function add_js($file, $scope = '') {
   $js = $this->wrap_asset($file, $scope);
    if ($js === false) {
      $this->js_log($file . " - {$scope} Asset Failed to Load!");
      return false;
    }      
    $this->scripts .= $this->wrap_js($js);
    return true;
  }

  public function add_main_css($file, $scope = '') {
    $css = $this->wrap_asset($file, $scope);
    if ($css === false) {
      $this->js_log($file . " - {$scope} Asset Failed to Load!");
      return false;
    }      
    $this->main_styles .= $this->wrap_css($css);
    return true;    
  }

  public function add_main_js($file, $scope = '') {
    $js = $this->wrap_asset($file, $scope);
    if ($js === false) {
      $this->js_log($file . " - {$scope} Asset Failed to Load!");
      return false;
    }          
    $this->main_scripts .= $this->wrap_js($js);
  }

  public function datatables_code() {
    $this->add_css(CX_BASE_REF.'/assets/datatables/datatables.min.css');
    $this->add_js(CX_BASE_REF.'/assets/datatables/datatables_no_jquery.min.js');
  }

  public function broken_error() {
    if (\cx_configure::a_get('cx', 'live') === true) {
      cx_redirect_url(CX_BASE_REF . 'app/' . DEFAULT_PROJECT . '/error.html');
    } else {
      echo "<pre>";
      var_dump(debug_backtrace());
      echo "</pre>";
    }
  }

  /*
   * Access app_cache engine
   */

  public function get_cache() {
    if (!is_object($this->app_cache)) {
      require_once(CX_BASE_DIR . 'classes' . DS . 'cx' . DS . 'cache' . DS . 'cache.php');
      $this->app_cache = new \cx\cache\cache();
    }
    return $this->app_cache;
  }

  /**
   * @method load_model
   * @param type $model, the data model to use for your view
   * @param $table, the database table to use
   */
  public function load_model($model = '') {
    require_once CX_INCLUDES_DIR . 'ssp_datatables_helper.php';
    require_once(CX_BASE_DIR . 'classes' . DS . 'cx' . DS . 'database' . DS . 'model.php');

    if (empty($model)) {
      return true;
    }

    try {
      if (file_exists(PROJECT_BASE_DIR . 'models' . DS . $model . '.php')) {
        require_once (PROJECT_BASE_DIR . 'models' . DS . $model . '.php');
      } else {
        throw new \Exception('Missing model');
      }
    } catch (Exception $e) {
      $_SESSION[\cx_configure::a_get('cx', 'session_variable') . 'last_error'] = 'Error loading model: ' . $e->getMessage();
      $this->broken_error();
    }
  }

  public function get_current_local_time() {
    $settings = array('format' => 'normal');
    return \cx\app\main_functions::convert_time_zone($settings);
  }

  public function load_library($lib) {
    require_once(CX_BASE_DIR . 'libraries' . DS . $lib . '.php');
  }

  private function my_autoload($class) {
    // Detect OS
    if (DS == "\\") {
      // Windows file path
      $class_file = str_replace('/', "\\", $class);
    } else {
      // convert namespace to full file path for Linux
      $class_file = str_replace("\\", '/', $class);
    }

    // Prevent from going up folders!
    $class_file = str_replace("..", '', $class_file);

    $class_file = CX_BASE_DIR . 'classes' . DS . $class_file . '.php';

    if (file_exists($class_file)) {
      require_once($class_file);
    } else {
      throw new Exception("Unable to load {$class}.");
    }
  }
  
  public function load_class($class_name, $parms = false) {
    $this->my_autoload($class_name);
    // Make class name valid...
    $class_name = str_replace('/', "\\", $class_name);
    try {
      return main_fn::call_class($class_name, $parms);
    } catch (Exception $e) {
      echo $e->getMessage(), "\n";
      return false;
    }
  }

  public function auth($checks = array()) {
    $ajax_required = (isset($checks['ajax'])) ? $checks['ajax'] : false;

    $access_check = (isset($checks['user'])) ? $checks['user'] : false; // Do not change from false default

    if ($this->is_api()) {
      if ($access_check !== false && method_exists($this, "api_" . $access_check)) {
        $access_check = "api_" . $access_check;
        return $this->$access_check();
      } else {
        \cx\app\cx_api::error(array('code' => 401, 'reason' => 'Invalid Login, method not found'));
      }
    } elseif ($ajax_required && !$this->request->is_ajax()) {
      echo 'Error no ajax';
      exit;
    } elseif ($access_check !== false && method_exists($this, "auth_" . $access_check)) {
      $access_check = "auth_" . $access_check;
      return $this->$access_check();
    } else {
      $this->do_login_redirect();
    }
  }

  public function get_login_email_address() {
    return $this->session->session_var(cx_configure::a_get('cx','login') . 'email');
  }

  public function do_login_redirect() {
    if ($this->is_api()) {
      \cx\app\cx_api::error(array('code' => 401, 'reason' => 'Invalid Login'));
    }
    $this->page_redirect($this->get_url('/app/' . DEFAULT_PROJECT, 'login'));
  }

  public function do_login_success_redirect() {
    $this->page_redirect($this->get_url('/app/' . DEFAULT_PROJECT, 'main'));
  }

  public function page_redirect($url) {
    $http_response_code = '302'; // Found Page
    header('Location: ' . $url, TRUE, $http_response_code);
    exit;
  }

  private function wrap_css($file, $media = 'all') {
    return "<link rel=\"stylesheet\" href=\"{$file}\" type=\"text/css\" media=\"{$media}\" />\r\n";
  }

  /**
   * Purpose: To return the JS for use with page.
   * @param type $file - external JS file.
   */
  private function wrap_js($file) {
    return "<script src=\"{$file}\" type=\"text/javascript\"></script>\r\n";
  }

  /**
   * Purpose: To do inline JavaScript.
   * @param type $code string of code to inline into page.
   * @return type 
   */
  private function inline_js($code) {
    return "<script type=\"text/javascript\">\r\n//<![CDATA[\r\n    {$code}\r\n //]]> \r\n </script>\r\n";
  }
  
  public function semantic_ui_component($component) {
    $safe_component = $this->filter_uri($component);

    $js = 'assets/semantic_ui/components/'.$safe_component.'.min.js';
    $css = 'assets/semantic_ui/components/'.$safe_component.'.min.css';

    if (file_exists(CX_BASE_DIR . $js)) {
      $this->add_js(CX_BASE_REF . '/' . $js);
    }
    
    if (file_exists(CX_BASE_DIR . $css)) {
      $this->add_css(CX_BASE_REF . '/' . $css);
    }
  }  
  
}
