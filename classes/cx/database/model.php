<?php

/**
 * Model Class
 */

namespace cx\database;

use cx\app\app as app;
use cx\app\main_functions as main_fn;
use cx\database\db as cx_db;

class model extends app {

  /**
   * Model members
   * @var object $database
   * @var string $table
   * @var array $members
   * @var string $primary_key
   */
  protected $db_cache;
  protected $database;
  protected $table;
  protected $members;
  protected $primary_key;
  private $paginator_links = '';
  private $paginator_object = false;
  private $my_fields;
  private $html_purifier;

  /**
   * Model constructor
   *
   * Must pass table name and primary key from child class
   * @method __construct
   * @param $options of database
   * @return void
   */
  public function __construct($options) {
    $this->db_cache = $this->get_cache();
    $this->table = (isset($options['table'])) ? $options['table'] : $options;
    $default_key = (isset($options['primary_key'])) ? $options['primary_key'] : 'id';
    $primary_key = (isset($options['key'])) ? $options['key'] : $default_key;

    $db_info = \cx_configure::get('database');
    
    $db_socket = (isset($db_info['SOCKET'])) ? $db_info['SOCKET'] : false;

    try {
      if (! empty($db_socket)) {
        $this->connect_database($db_info['TYPE'] . ':unix_socket=' . $db_socket . ';dbname=' . $db_info['NAME'], $db_info['USER'], $db_info['PASS']);
      } else {
        $this->connect_database($db_info['TYPE'] . ':host=' . $db_info['HOST'] . ';port=' . $db_info['PORT'] . ';dbname=' . $db_info['NAME'], $db_info['USER'], $db_info['PASS']);
      }
    } catch (PDOException $e) {
      $error = 'Failed to connect to Database! ' . $e->getMessage();
      cx_email_error('The Database is DOWN!!!' . $error);
      if (\cx_configure::a_get('cx', 'live') === true) {
        cx_global_error_handler();
        exit;
      } else {
        echo "The Database is down!!! {$error}";
        exit;
      }
    } catch (Exception $e) {
      $error = 'Failed to connect to Database! ' . $e->getMessage();
      cx_email_error('The Database is DOWN!!!' . $error);
      if (\cx_configure::a_get('cx', 'live') === true) {
        cx_global_error_handler();
        exit;
      } else {
        echo "The Database is down!!! {$error}";
        exit;
      }
    }

    $this->primary_key = $primary_key;
    $this->generate_members();
    parent::__construct();
  }
  
  /**
   * Connect to Database
   * @method connnectDatabase
   * @return void
   */
  protected function connect_database($dsn, $user = '', $passwd = '') {
    require_once(CX_BASE_DIR . 'classes' . DS . 'cx' . DS . 'database' . DS . 'db.php');
    $this->database = new cx_db($dsn, $user, $passwd);
    if (! is_object($this->database)) {
      cx_email_error('The Database is DOWN!!!');
      if (\cx_configure::a_get('cx', 'live') === true) {
        cx_global_error_handler();
        exit;
      } else {
        echo "The Database is down";
        exit;
      }
    }
    
    if (is_object($this->database)) {
      $this->database->error_callback_set('cx_global_error_handler');
      $this->database->init_db();
    } else {
      throw new \Exception('Unable to connect to database!!');
    }
  }
 
  private function load_html_purifier() {
    if (is_object($this->html_purifier)) {
      return true;
    }
    
    cx_load_library('htmlpurifier' . DS . 'library' . DS . 'HTMLPurifier.auto');
    $config = \HTMLPurifier_Config::createDefault();
    $config->set('Core.Encoding', 'UTF-8');
    $config->set('HTML.Doctype', 'HTML 4.01 Transitional');

    if (defined('PURIFIER_CACHE')) {
      $config->set('Cache.SerializerPath', PURIFIER_CACHE);
    } else {
      // Disable the cache entirely
      $config->set('Cache.DefinitionImpl', null);
    }

    $this->html_purifier = new \HTMLPurifier($config);
  }

  public function __destruct() {
    $this->unload_html_purifier();
  }

  public function unload_html_purifier() {
    if (is_object($this->html_purifier)) {
      unset($this->html_purifier);
      spl_autoload_unregister(array('HTMLPurifier_Bootstrap', 'autoload'));
    }
  }
  
  
  public function get_pwd_hash($pwd) {
    if (function_exists('password_hash')) {
      return password_hash($pwd, PASSWORD_DEFAULT); // will add random SALT by it self
    } else {
      $salt = (isset($_SERVER[\cx_configure::a_get('security','main_salt')])) ? $_SERVER[\cx_configure::a_get('security','main_key')] : 'fAllBacK843854';
      $salt2 = (isset($_SERVER[\cx_configure::a_get('security','main_key')])) ? $_SERVER[\cx_configure::a_get('security','main_key')] : 'EViluDe123874132';
      return hash('SHA256', $salt . $pwd . $salt2, false);
    }
  }
  
  public function check_pwd($pwd, $hash) {
    if (function_exists('password_hash')) {
      return password_verify($pwd, $hash);
    } else {
      $salt = (isset($_SERVER[\cx_configure::a_get('security','main_salt')])) ? $_SERVER[\cx_configure::a_get('security','main_key')] : 'fAllBacK843854';
      $salt2 = (isset($_SERVER[\cx_configure::a_get('security','main_key')])) ? $_SERVER[\cx_configure::a_get('security','main_key')] : 'EViluDe123874132';
      $check = hash('SHA256', $salt . $pwd . $salt2, false);
      return ($check == $hash) ? true : false;
    }
  }  
    /**
   * Disconnect from Database
   * @method disconnect_database
   * @return void
   */
  protected function disconnect_database() {
    $this->database = null;
  }

  /**
   * Read database table and set members for class
   * @method generate_members
   * @return void
   */
  protected function generate_members() {
    $fields = $this->database->get_members($this->table);
    foreach ($fields as $key => $value) {
      $this->members[$value] = NULL;
      $this->my_fields[$value] = true;
    }
  }
  
  public function has_member($member) {
    return isset($this->my_fields[$member]);
  }
 
  /**
   * Setter for class members
   *
   * @return void
   */
  public function set_member($name, $value) {
    if (is_array($this->members) && !array_key_exists($name, $this->members) && $name != $this->primary_key) {
      return;
    }
    
    $this->members[$name] = (is_array($value)) ? serialize($value) : html_entity_decode($value);
  }

  /**
   * Array Setter for class members
   *
   * @return void
   */
  public function set_members($namesValues) {
    foreach ($namesValues as $name => $value) {
      $this->set_member($name, $value);
    }
  }

  /**
   * Automatically set members by DB names to POST names, must match for this to work!!
   * @return boolean success
   */
  public function auto_set_members($skip = array(), $extra = '') {
    if (!is_array($this->members)) {
      return false;
    }

    $success = true;
    $method = (isset($extra['method'])) ? $extra['method'] : 'POST';
    switch ($method) {
      case 'get':
      case 'GET':
        $globals = $_GET;
        break;
      case 'request':
      case 'REQUEST':
        $globals = $_REQUEST;
        break;
      case 'post':
      case 'POST':
      default:
        $globals = $_POST;
        break;
    }

    $debug = (isset($extra['debug'])) ? $extra['debug'] : false;

    foreach ($this->members as $name => $value) {
      if (is_array($skip) && in_array($name, $skip)) {
        continue;
      }
      if ($name == $this->primary_key || $name == 'company_id' || $name == 'user_id' || $name == 'deleted') {
        continue;
      }
      if (isset($globals[$name])) {
        $this->set_member($name, $globals[$name]);
      } elseif (!isset($value)) {
        if ($debug == true) {
          echo "Error: field not set or skipped: {$name}<br/>\r\n";
        }
        $success = false;
      }
    }

    if ($debug == true && $success == false) {
      exit;
    }
    return $success;
  }

  public function safe_html($name, $text, $allow_html) {
    if ($allow_html === 'no_safety') {
      return $text;
    }
    
    $pos = strrpos($name, "_html");
    if ($allow_html === true || ($pos !== false)) {
      if (! is_object($this->html_purifier)) {
        $this->load_html_purifier();
      }
      $pur = $this->html_purifier->purify($text);
      $clean = str_replace('@double;', '&quot;', str_replace('@single;', '&#39;', $pur)); 
      return $clean;
    } else {
      return htmlentities($text, ENT_QUOTES, "UTF-8");
    }
  }
  
  /**
   * Getter for class members
   *
   * @return void
   */
  public function get_member($name, $allow_html = false) {
    if (is_array($this->members) && !array_key_exists($name, $this->members)) {
      return;
    }
    $value = $this->members[$name];
    
    if (main_fn::is_serialized($value)) {
      return $value;
    }
    
    if (is_string($value)) {
      // Prevent XSS
      return $this->safe_html($name, $value, $allow_html);
    }
    
    if (count($value) == 0) {
      return;
    }
    
    $my_array = array();
    foreach($value as $key=>$content) {
      $my_array[$key] = (is_string($content)) ? $this->safe_html($key, $content, $allow_html) : $content;
    }
    return $my_array;
  }

  /**
   * Array Getter for class members
   *
   * @return void
   */
  public function get_members($allow_html = false) {
    $clean = array();
    $data = $this->members;
    
    if (!is_array($data)) {
      return $clean;
    }
    
    foreach($data as $key=>$value) {
      $clean[$key] = $this->get_member($key, $allow_html);
    }
    return $clean;
  }

  public function set_primary_key($id) {
    $this->primary_key = $id;
  }
  
  public function empty_data() {
    unset($this->members);
    $this->generate_members();
  }

  /**
   *  return value of current object's primary key
   *
   * @return int
   */
  public function get_primary_key() {
    return (isset($this->members[$this->primary_key])) ? $this->members[$this->primary_key] : null;
  }

  /**
   * Validate current class members
   * @method valudate
   * @return bool
   */
  public function validate_mysql() {
    foreach ($this->members as $field => $value) {
      if ($field == $this->primary_key) {
        continue;
      }
      
      $tbl = (main_fn::found($this->table, "`")) ? $this->table : "`{$this->table}`";
      
      $query = "SELECT `{$field}` FROM {$tbl} LIMIT 1";
      $pdostmt = $this->database->prepare($query);
      $pdostmt->execute();
      $meta = $pdostmt->getColumnMeta(0);
      $type = (isset($meta['native_type']) ? $meta['native_type'] : '');
      $len = $meta['len'];
      //echo $type." : len=".$len;
      switch ($type) { //This should be all uppercase input.
        case 'SHORT': //Small INT
        case 'INT24': //MED INT
        case 'LONGLONG': //BIG INT or SERIAL is an alias for BIGINT UNSIGNED NOT NULL AUTO_INCREMENT UNIQUE.
        case 'LONG': // Integers 
          if (! preg_match('/^[0-9]*$/', $value)) {
            echo "Failed Validation: NOT a digit {$type} {$field}";
            return false;
          } // Does not allow decimal numbers!!
          if (strlen($value) > $len) {
            echo "Failed Validation: too long {$type} {$field}";
            return false;
          }
          break;
        case 'FLOAT':
          if (strlen($value) > $len) {
            echo "Failed Validation: too long {$type} {$field}";
            return false;
          }
          if (!is_float($value)) {
            echo "Failed Validation: NOT a float {$type} {$field}";
            return false;
          }
          break;
        case 'NEWDECIMAL':
          if (strlen($value) > $len) {
            echo "Failed Validation: too long {$type} {$field}";
            return false;
          }
          //if (!is_float($value)) return false; //This fails so its commented out.
          break;
        case 'DOUBLE':
          if (strlen($value) > $len) {
            echo "Failed Validation: too long {$type} {$field}";
            return false;
          }
          if (!is_double($value)) {
            echo "Failed Validation: NOT a double {$type} {$field}";
            return false;
          }
          break;
        case 'BLOB': // Text
          if ($len == '4294967295' || $len == '16777215')
            continue; //Too Big to process, 16777215 MEDIUMTEXT
          if (strlen($value) > $len) {
            echo "Failed Validation: too long {$type} {$field}";
            return false;
          }
          break;
        case 'VAR_STRING': // VARCHAR or VARBINARY
        case 'STRING': //CHAR or BINARY   
          if (strlen($value) > $len) {
            echo "Failed Validation: too long {$type} {$field}";
            return false;
          }
          break;
        case 'TIMESTAMP':
        case 'TIME': /** @todo strtotime check */
        case 'DATE':
        case 'DATETIME':
          if (strlen($value) > $len) {
            echo "Failed Validation: too long {$type} {$field}";
            return false;
          }
          //if (!is_Date($value)) return false;
          break;
        default: //TINYINT, Bit, Bool, or Year is the default for no meta data
          //if (!is_Digits($value)) return false; //This fails so its commented out.
          if ($len == 3) { // Tiny INT
            if (intval($value) > 255) {
              echo "Failed Validation: too long {$type} {$field}";
              return false;
            }
            if (intval($value) < -127) {
              echo "Failed Validation: too short {$type} {$field}";
              return false;
            }
          } elseif ($len == 1) { // Bit or Bool
            if (intval($value) > 9) {
              echo "Failed Validation: too long {$type} {$field}";
              return false;
            }
            if (intval($value) < 0) {
              echo "Failed Validation: too short {$type} {$field}";
              return false;
            }
          }
          break;
      }
    }
    return true;
  }

  public function get_paginator_object() {
    return $this->paginator_object;
  }
  
  public function get_paginator_links() {
    return $this->paginator_links;
  }
  
  /**
   * @method load
   * @param $id - primary key
   */
  public function load($id="", $options = array(), $bind="") {

    if (method_exists($this, 'pre_load')) {
      $this->pre_load();
    }
    
    if (empty($id)) {
      
      if (isset($options['paginator']) && $options['paginator'] == 'true') {
        $pages = $this->load_class('cx\common\paginator');
        if ($pages !== false) {
          
          if (isset($options['js_call'])) {
            $pages->js_call = $options['js_call'];
          }
          
          $a_count['fields'] = "Count(*) AS `numrows`";
          $a_count['fetch'] = 'fetch_row';
          $count = array_merge($options, $a_count);
          
          if (isset($count['order_by'])) {
            unset($count['order_by']);
          }
          
          $total = $this->database->select($this->table, $count, $bind);
          $pages->items_total = $total['numrows'];
          $pages->mid_range = (isset($options['paginator_mid_range'])) ? $options['paginator_mid_range'] : 9;  
          $pages->paginate();
          $options['pageinator_limit'] = $pages->limit;
          $this->paginator_links = ($pages->items_total>0) ? $pages->display_pages() : '';
        }
        $this->paginator_object = $pages;
      }
      
      $this->members = $this->database->select($this->table, $options, $bind);
    } else {    
      $where = "`{$this->primary_key}` = :search_key";
      $options = array('where' => $where, 'fetch' => 'fetch_row');
      $this->members = $this->database->select($this->table, $options, array(':search_key'=>$id));
    }
    
    if (method_exists($this, 'post_load')) {
      $this->post_load();
    }

    return true;
  }
  
  public function ssp_load($columns, $options = array(), $bind="", $skip="") {

    if (method_exists($this, 'pre_load')) {
      $this->pre_load();
    }

    $bindings = array();
    
    if (! isset($options['fields']) || $options['fields'] == 'auto') {
      $options['fields'] = '';
      foreach($columns as $fields) {
        if (isset($fields['db'])) {
          $options['fields'] .= $fields['db'] . ", ";
        }
      }
      $options['fields'] = rtrim($options['fields'], ", ");
    }
    
    $limit = ssp_get_limit();
    if (! empty($limit)) {
      $options['limit'] = $limit;
    }
    
    $order = ssp_get_order($columns, "", $skip);
    if (! empty($order)) {
      $options['order_by'] = $order;
    }
    
    $options['other'] = "SQL_CALC_FOUND_ROWS ";
    
    $options['where'] = ssp_get_where($columns, $_GET, $bindings) . $options['where'];
    $this->database->set_bindings($bindings);
    $this->members = $this->database->select($this->table, $options, $bind);
    $c = $this->database->get_pdo_count();
    
    $this->database->set_bindings('');
    
    if (method_exists($this, 'post_load')) {
      $this->post_load();
    }

    ssp_output($c, $this, $columns);
  }

  /**
   * Save current object to database
   *
   * Insert if primary key not set
   * Update if primary key set
   */
  public function save() {
    
    if (method_exists($this, 'pre_save')) {
      $this->pre_save();
    }

    if (CX_DB_TYPE == 'mysql') {
      if (! $this->validate_mysql()) {
        return CX_VALIDATION_ERROR;
      }
    }
    
    if ($this->has_member('modified')) {
      $this->set_member('modified', $this->make_db_time_stamp());
    }
    
    if (empty($this->members[$this->primary_key])) {
      if (method_exists($this, 'duplicate_check')) {
        $dup = $this->duplicate_check();
        if ($dup) {
          return CX_DUPLICATE_FOUND;
        }
      }

      if ($this->has_member('user_id')) {
        $this->set_member('user_id', $this->get_user_id());
      }
      if ($this->has_member('company_id')) {
        $this->set_member('company_id', $this->get_company_id());
      }
      if ($this->has_member('created')) {
        $this->set_member('created', $this->make_db_time_stamp());
      }
     
      $this->database->insert($this->table, $this->members);
      $this->members[$this->primary_key] = $this->database->lastInsertId();
    } else {
      $do_unsafe_save = false;
      $this->database->update($this->table, $this->members, 
        "`{$this->primary_key}` = :search_key", 
          array(':search_key'=>$this->members[$this->primary_key]),
          $do_unsafe_save
          );
    }
    
    if (method_exists($this, 'post_save')) {
      $this->post_save();
    }
        
    return true;
  }

  public function db_expires_in_hours($hours = 1) {
    return main_fn::expires_in_hours($hours);
  }
  
  public function make_db_time_stamp() {
    $settings = array('format'=>'database', 'timezone'=>'UTC');
    return main_fn::convert_time_zone($settings);
  }

  public function get_dyn_local_time($input) {
    if (strpos($input, ':') !== false) {
      $settings = array('format'=>'normal', 'time'=>$input);
      return main_fn::convert_time_zone($settings);
    } else {
      return false;
    }
  }
  
  public function get_db_local_time($time_field) {
    if (! $this->has_member($time_field)) {
      return false;
    }
    
    return $this->get_dyn_local_time($this->get_member($time_field));
  }
    
  /**
   * Debugging method to print details about fields
   * @method print_object
   * @return void
   */
  public function show_fields() {
    echo '<pre>FIELDS:<br>';
    
    if (strrpos($this->table, "`") === false) {
      $table = "`{$this->table}`";
    }
    
    foreach($this->my_fields as $field => $value) {
      $query = "SELECT `{$field}` FROM {$table} LIMIT 1";
      $pdostmt = $this->database->prepare($query);
      $pdostmt->execute();
      $meta = $pdostmt->getColumnMeta(0);
      $type = (isset($meta['native_type']) ? $meta['native_type'] : '');
      $len = $meta['len'];
      echo "{$field} => {$type} ({$len})<br>";
    }
    echo '</pre>';
  }

    /**
   * Debugging method to print out what's loaded
   * @method print_object
   * @return void
   */
  public function print_members() {
    echo '<pre>';
    print_r($this->members);
    echo '</pre>';
  }
  
}