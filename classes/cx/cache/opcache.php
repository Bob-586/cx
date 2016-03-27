<?php

/**
 * @copyright (c) 2012
 * @author Robert Strutts 
 */

namespace cx\cache;

class opcache {

  protected $opcache;

  function __construct() {
    try {
    $this->opcache = new \stdClass;
    } catch (Exception $e) {
      echo 'Message: ' .$e->getMessage();
    }
  }

  public function get_opcache_server() {
    return $this->opcache;
  }
  
  public function exists($key) {
 		$time = time();
		$cachetime = intval($this->opcache->get($key . '_expires'));
		if ($cachetime !== 0 && $cachetime < $time) {
			return false;
		}
    return $this->opcache->exists($key);
  }
  
  public function load($key) {
    $data = $this->opcache->get($key);
    if (\cx\application\main_functions::is_serialized($data) === true) {
      $data = cx\app\main_functions::safe_unserialize($data);
    }
    return $data;
  }

  public function save($key, $value, $ttl) {
    $expires = 0;
		if ($ttl > 0) {
			$expires = time() + $ttl;
		}

    if ($this->opcache->exists($key) === true) {
      $this->opcache->del($key . '_expires');
      $this->opcache->del($key);
    }
    
    $this->opcache->setex($key . '_expires', $ttl, $expires);
    
    if (is_array($value)) {
      $value = serialize($value);
    }

    return $this->opcache->setex($key, $ttl, $value);
  }
  
  public function flush() {
    $this->opcache->flushDB();
  }
}