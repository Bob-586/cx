<?php
/**
 * @copyright (c) 2012
 * @author Robert Strutts 
 */
namespace cx\cache;

class cache extends \cx\app\app {
  private $id;
  private $ttl; // Time To Live, in seconds for cache
  private $cache;
  const EXPIRES_IN_SECONDS_DEFAULT = 300;
  const MONTH_IN_SECONDS = 2592000;

  public function __construct($options = array()) {
    if (isset($options['ttl'])) {
      $this->ttl = intval($options['ttl']);
    } elseif (defined('CACHE_TTL')) {
      $this->ttl = CACHE_TTL;
    } else {
      $this->ttl = self::EXPIRES_IN_SECONDS_DEFAULT;
    }
    
    if ($this->ttl > self::MONTH_IN_SECONDS) { 
      $this->ttl = self::MONTH_IN_SECONDS;
    }
    
    if (\cx_configure::a_get('cx', 'live') === true) {
      $this->cache = '';
      return false;
    }
    
    if (isset($options['engine'])) {
      $engine = $options['engine'];
    } elseif (defined('CACHE_ENGINE')) {
      $engine = CACHE_ENGINE;
    } else {
      $engine = 'auto';
    }

    switch ($engine) {
      case 'none':
        $this->cache = '';
        break;
      case 'file':
        $this->cache = $this->load_class('cx\cache\file');
      case 'redis':
        $this->cache = $this->load_class('cx\cache\redis_cache');
        break;
      case 'op':
        $this->cache = $this->load_class('cx\cache\opcache');
        break;
      case 'apc':
        $this->cache = $this->load_class('cx\cache\apc');
        break;
      case 'xcache':
        $this->cache = $this->load_class('cx\cache\xcache');
        break;
      case 'memcached':
        $this->cache = $this->load_class('cx\cache\memcached');
        break;
      case 'auto':
      default:
        if (class_exists('Redis')) {
          $this->cache = $this->load_class('cx\cache\redis_cache');
//        } elseif (extension_loaded('Zend OPcache')) {
//          $this->cache = $this->load_class('cx\cache\opcache');
        } elseif (extension_loaded('apc')) {
          $this->cache = $this->load_class('cx\cache\apc');
        } elseif (function_exists('xcache_isset')) {
          $this->cache = $this->load_class('cx\cache\xcache');
        } elseif (class_exists('Memcached') && function_exists('memcached_servers')) {
          $this->cache = $this->load_class('cx\cache\memcached');
        } else {
          $this->cache = '';
        }
        break;
    }
    
  } 
 
  public function set_ttl($ttl) {
    $this->ttl = $ttl;
  }

  private function get_vars() {
    foreach ($_GET as $key => $value) {
      if ($key == 'route') {
        $the_request .= '?' . $key . '=' . $value;
      } else {
        $the_request .= '&' . $key . '=' . $value;
      }
    }
    return $the_request;
  }

  public function set_cache_id($id, $ignore_get_data = false) {
    $get = ($ignore_get_data === false) ? $this->get_vars() : '';
    $this->id = md5($get . $id);
  }
  
  public function get_cache_id() {
    return $this->id;
  }

  public function exists($key='') {
    if (empty($key)) {
      $key = $this->id;
    }

    if (isset($_REQUEST['no_cache_db'])) {
      return false;
    } elseif (empty($this->cache)) {
      return false;
    } else {
      return $this->cache->exists($key);
    }
  }

  public function load($key='') {
    if (empty($key)) {
      $key = $this->id;
    }

    if (empty($this->cache)) {
      return false;
    } else {
//      file_put_contents(CCD_PROTECTED_DIR . 'settings' . DS . 'success.txt', "Database cache[{$key}]: " . date('l jS \of F Y h:i:s A') . ' ' . $_SERVER['REQUEST_URI']."\r\n", FILE_APPEND);
      return $this->cache->load($key);
    }
  }

  public function save($value, $key='') {
    if (empty($key)) {
      $key = $this->id;
    }
    if (empty($this->cache)) {
      return false;
    } else {
      return $this->cache->save($key, $value, $this->ttl);
    }
  }

  public function flush($key='') {
    if (empty($this->cache)) {
      return false;
    } else {
      $this->cache->flush();
    }
  }
  
  // To be used inside of controllers:
  public function do_cache_object($key, $object, $method, $vars='', $ignore_get_data=false) {
    $this->set_cache_id($key, $ignore_get_data);
    if ($this->exists()) {
      return $this->load();
    } else {
      $data = $object->$method($vars);
      $this->save($data);
      return $data;
    }    
  }

  // To be used inside of models:
  public function do_cache($key, $ignore_get_data=false) {
    $this->set_cache_id($key, $ignore_get_data);
    if ($this->exists()) {
      return $this->load();
    } else {
      return false;
    }    
  }
  
}