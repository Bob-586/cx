<?php

/**
 * @copyright (c) 2012
 * @author Robert Strutts 
 */
namespace cx\cache;

class xcache {

  public function exists($key) {
		$time = time();
		$cachetime = intval(xcache_get($key . '_expires'));
		if ($cachetime !== 0 && $cachetime < $time) {
			return false;
		}

    return xcache_isset($key);
  }

  public function load($key) {
    return xcache_get($key);
  }

  public function save($key, $value, $ttl) {
    $expires = 0;
		if ($ttl > 0) {
			$expires = time() + $ttl;
		}
    xcache_set($key . '_expires', $expires, $ttl);
    xcache_set($key, $value, $ttl);
  }

  public function flush() {
    for($i=0, $max=xcache_count(XC_TYPE_VAR); $i<$max; $i++) {
			if(xcache_clear_cache(XC_TYPE_VAR, $i)===false) {
        return false;
      }
		}
		return true;
  }
  
}