<?php

/**
 * @copyright (c) 2012
 * @author Robert Strutts 
 */

namespace cx\cache;

class redis_cache {

  protected $redis;

  function __construct() {
    $my_redis_servers = array('redis1:6379', 'redis2:6379');
    try {
    $this->redis = new \RedisArray($my_redis_servers, array("retry_timeout" => 100));
//    $this->redis = new \Redis();
//    $this->redis->pconnect('redis1');
    $this->redis->auth('Yoo87342');
    $this->redis->select(1); // SELECT DB # 1
//    print_r($this->redis);
    } catch (Exception $e) {
      echo 'Message: ' .$e->getMessage();
    }
  }

  public function get_opcache_server() {
    return $this->redis;
  }
  
  public function exists($key) {
 		$time = time();
		$cachetime = intval($this->redis->get($key . '_expires'));
		if ($cachetime !== 0 && $cachetime < $time) {
			return false;
		}
    return $this->redis->exists($key);
  }
  
  public function load($key) {
    $data = $this->redis->get($key);
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

    if ($this->redis->exists($key) === true) {
      $this->redis->del($key . '_expires');
      $this->redis->del($key);
    }
    
    $this->redis->setex($key . '_expires', $ttl, $expires);
    
    if (is_array($value)) {
      $value = serialize($value);
    }

    return $this->redis->setex($key, $ttl, $value);
  }
  
  public function flush() {
    $this->redis->flushDB();
  }
}