<?php

/**
 * @copyright (c) 2012
 * @author Robert Strutts 
 */
namespace cx\cache;

class apc {

  public function exists($key) {
 		$time = time();
		$cachetime = intval(apc_fetch($key . '_expires'));
		if ($cachetime !== 0 && $cachetime < $time) {
			return false;
		}

    return apc_exists($key);
  }

  public function load($key) {
    return apc_fetch($key);
  }

  public function save($key, $value, $ttl) {
    $expires = 0;
		if ($ttl > 0) {
			$expires = time() + $ttl;
		}
    apc_store($key . '_expires', $expires, $ttl);
    apc_store($key, $value, $ttl);
  }

  public function flush() {
    return apc_clear_cache('user');
  }
  
}