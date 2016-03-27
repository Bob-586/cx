<?php
// OpenCart 2.0.2
namespace cx\cache;

class file {
	private $expire;

	public function __construct($expire = 3600) {
		$this->expire = $expire;

		$files = glob(DIR_CACHE . 'cache.*');

		if ($files) {
			foreach ($files as $file) {
				$time = substr(strrchr($file, '.'), 1);

				if ($time < time()) {
					if (file_exists($file)) {
						unlink($file);
					}
				}
			}
		}
	}

  public function exists($key) {
    $files = glob(DIR_CACHE . 'cache.' . preg_replace('/[^A-Z0-9\._-]/i', '', $key) . '.*');

		return ($files) ? true : false;

  }
  
	public function load($key) {
		$files = glob(DIR_CACHE . 'cache.' . preg_replace('/[^A-Z0-9\._-]/i', '', $key) . '.*');

		if ($files) {
			$handle = fopen($files[0], 'r');

			flock($handle, LOCK_SH);

			$data = fread($handle, filesize($files[0]));

			flock($handle, LOCK_UN);

			fclose($handle);

			return cx\app\main_functions::safe_unserialize($data);
		}

		return false;
	}

	public function save($key, $value, $ttl) {
    $exp = (isset($ttl) && $ttl>0) ? $ttl : $this->expire;
    
		$this->flush($key);

		$file = DIR_CACHE . 'cache.' . preg_replace('/[^A-Z0-9\._-]/i', '', $key) . '.' . (time() + $exp);

		$handle = fopen($file, 'w');

		flock($handle, LOCK_EX);

		fwrite($handle, serialize($value));

		fflush($handle);

		flock($handle, LOCK_UN);

		fclose($handle);
	}

	public function flush($key) {
		$files = glob(DIR_CACHE . 'cache.' . preg_replace('/[^A-Z0-9\._-]/i', '', $key) . '.*');

		if ($files) {
			foreach ($files as $file) {
				if (file_exists($file)) {
					unlink($file);
				}
			}
		}
	}
}