<?php

/**
 * @copyright (c) 2012
 * @author Robert Strutts 
 */
namespace cx\cache;

class memcached {

  protected $memcached;

  function __construct() {
    $this->memcached = new Memcached();
    $servers = array(
      array('mem1', 11211, 33),
      array('mem2', 11211, 67)
    );
    $this->memcached->addServers($servers);
//    $this->memcached->addServers(memcached_servers());
  }

  public function get_memcached_server() {
    return $this->memcached;
  }
  
  public function exists($key) {
    $time = time();
		$cachetime = intval($this->memcached->get($key . '_expires'));
		if ($cachetime !== 0 && $cachetime < $time) {
			return false;
		}
    
    $ret = $this->memcached->get($key);
    if ($this->memcached->getResultCode() == Memcached::RES_NOTFOUND) {
      return false;
    } elseif ($ret === false) {
      return false;
    } else {
      return true;
    }
  }

  public function load($key) {
    return $this->memcached->get($key);
  }

  public function save($key, $value, $ttl) {
    $expires = 0;
		if ($ttl > 0) {
			$expires = time() + $ttl;
		}
    $this->memcached->set($key . '_expires', $expires, $ttl);
    $this->memcached->set($key, $value, $ttl);
  }
  
  public function flush() {
    $this->memcached->flush();
  }

  public function setMulti($items, $ttl) {
    $this->memcached->setMulti($items, $ttl);
  }
 
  // http://www.php.net/manual/en/memcached.getmulti.php
  public function getMulti($keys, & $cas_tokens, $same_order = false) {
    if ($same_order) {
      return $this->memcached->getMulti($keys, $cas_tokens, Memcached::GET_PRESERVE_ORDER);
    } else {
      return $this->memcached->getMulti($keys, $cas_tokens);
    }
  }

}