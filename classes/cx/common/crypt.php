<?php

namespace cx\common;

final class crypt {

  const max_key_size = 56;
  const high_key_size = 32;
  const med_key_size = 24;
  const min_key_size = 16;
  
  public $cleartext;
  public $ciphertext;
  
  private $use_openssl;
  private $binary;
  private $key;
  private $auto_key_size = 32;
  private $iv_size_in_bytes = 8;
  private $method = 'blowfish';
  private $algorithm;
  private $mode;
  private $random_source;
  private $iv_size = false; // init to false, so it auto loads
  private $iv;

  public function __construct(
    $use_openssl = false, 
    $binary = false, 
    $key = false, 
    $algorithm = MCRYPT_RIJNDAEL_256, 
    $mode = MCRYPT_MODE_CBC, 
    $random_source = MCRYPT_DEV_URANDOM
  ) {
    $this->use_openssl = $use_openssl;
    $this->binary = $binary;
    $this->key = ($key === false && \cx_configure::a_get('security', 'main_key') !== false) ? 
        \cx_configure::a_get('security', 'main_key') : $key;
    $this->algorithm = $algorithm;
    $this->mode = $mode;
    $this->random_source = $random_source;
  }

  public function set_use_openssl($useit = false) {
    if ($this->iv_size === false) {
      $this->use_openssl = $useit;
    }
  } 
  
  public function change_security($level) {
    if ($this->iv_size === false) {
      if ($this->use_openssl) {
        $this->change_openssl($level);
      } else {
        $this->change_mcrypt($level);
      }
    } // end of if
  }

  private function change_openssl($level) {
    $this->iv_size_in_bytes = 16;
    $this->auto_key_size = self::max_key_size;
    switch (strtolower($level)) {
      case 'low':
        $this->iv_size_in_bytes = 8;
        $this->method = 'blowfish';
        break;
      case 'medium':
        $this->method = 'AES-128-CBC';
        break;
      case 'medium-high':
        $this->method = 'AES-192-CBC';
        break;
      case 'high':
        $this->method = 'AES-256-CBC';
        break;
    } // end of switch
  }

  private function change_mcrypt($level) {
    switch (strtolower($level)) {
      case 'low':
        $this->auto_key_size = self::max_key_size;
        $this->algorithm = MCRYPT_BLOWFISH;
        break;
      case 'medium':
        $this->auto_key_size = self::high_key_size;
        $this->algorithm = MCRYPT_RIJNDAEL_128;
        break;
      case 'medium-high':
        $this->auto_key_size = self::high_key_size;
        $this->algorithm = MCRYPT_RIJNDAEL_192;
        break;
      case 'high':
        $this->auto_key_size = self::high_key_size;
        $this->algorithm = MCRYPT_RIJNDAEL_256;
        break;
    } // end of switch
  }

  public function change_algorithm($algorithm) {
    if ($this->iv_size === false) {
      $this->algorithm = $algorithm;
    }
  }

  public function change_mode($mode) {
    if ($this->iv_size === false) {
      $this->mode = $mode;
    }
  }

  private function get_valid_key() {
    $key = substr($this->key, 0, $this->auto_key_size);
    $keysize = strlen($key);
    
    if ($keysize > self::max_key_size && $keysize < self::min_key_size) {
      throw new Exception('Unable to use ENC key: Bad key size!');
    }
    return pack('H*', $key);
  }

  public function generate_valid_key() {
    $strong = true;
    return bin2hex(openssl_random_pseudo_bytes(self::max_key_size, $strong));
  }

  public function set_cleartext($text) {
    $this->cleartext = $text;
  }

  public function set_ciphertext($code) {
    $this->ciphertext = $code;
  }

  public function generate_iv() {
    if ($this->use_openssl) {
      $this->iv_size = $this->iv_size_in_bytes;
    } else {
      $this->iv_size = \mcrypt_get_iv_size($this->algorithm, $this->mode);
    }
    $this->iv = \mcrypt_create_iv($this->iv_size, $this->random_source);
  }

  public function get_iv_size() {
    return $this->iv_size;
  }

  public function get_iv() {
    return $this->iv;
  }

  public function encrypt() {
    if (!extension_loaded('mcrypt')) {
      return false;
    }

    $key = $this->get_valid_key();

    if ($this->iv_size === false) {
      $this->generate_iv();
    }
    if ($this->use_openssl) {
      $encrypted = @\openssl_encrypt($this->cleartext, $this->method, $key, OPENSSL_RAW_DATA, $this->iv);
    } else {
      $encrypted = \mcrypt_encrypt($this->algorithm, $key, $this->cleartext, $this->mode, $this->iv);
    }
    $this->ciphertext = (!$this->binary) ? base64_encode($this->iv . $encrypted) : $encrypted;
    return $this->ciphertext;
  }

  public function decrypt($iv = false) {
    if (!extension_loaded('mcrypt')) {
      return false;
    }

    $key = $this->get_valid_key();

    if ($this->iv_size === false) {
      $this->generate_iv();
    }

    $string = base64_decode($this->ciphertext);
    $iv = (!$this->binary && $iv === false) ? substr($string, 0, $this->iv_size) : $iv;

    $ciphertext = substr($string, $this->iv_size);
    if ($this->use_openssl) {
      $this->cleartext = @\openssl_decrypt($ciphertext, $this->method, $key, OPENSSL_RAW_DATA, $iv);
    } else {
      $this->cleartext = \mcrypt_decrypt($this->algorithm, $key, $ciphertext, $this->mode, $iv);
    }
    return $this->cleartext;
  }

  public function list_modes() {
    echo 'pre' . print_r(\mcrypt_list_modes(), true) . '</pre>';
  }

  public function list_algoritms() {
    echo 'pre' . print_r(\mcrypt_list_algorithms(), true) . '</pre>';
  }

  public function list_openssl_methods() {
    $ciphers = openssl_get_cipher_methods();
    $ciphers_and_aliases = openssl_get_cipher_methods(true);
    $cipher_aliases = array_diff($ciphers_and_aliases, $ciphers);

    print_r($ciphers);

    print_r($cipher_aliases);
  }

  public static function make_hash($text, $level = 'low') {
    $salt = (\cx_configure::a_get('security', 'main_salt') !== false) ? \cx_configure::a_get('security', 'main_key') : $this->key;

    switch (strtolower($level)) {
      case 'high':
        // Prefer computing using HMAC
        if (function_exists("hash_hmac")) {
          return hash_hmac("sha256", $text, $salt);
        }
        // Sha256 hash is the next best thing
        if (function_exists("hash")) {
          return hash("sha256", $salt . $text);
        }
      case 'weak':
        return md5($salt . $text);
      case 'low':
      default:
        return md5($salt . md5($text . $salt));
    }
    return md5($salt . md5($text . $salt)); // Fall Back FN
  }

  public function get_large_random_hash() {
    $abc = \cx\app\main_functions::generate_random_string(24);
    $midnight = microtime();
    $mid = \cx\app\main_functions::generate_random_string(12);
    $day = date("d");
    $month = date("m");
    $year = date("Y");
    $hour = date("h");
    $second = date("i");
    $xyz = \cx\app\main_functions::generate_random_string(24);
    return hash("sha512", ($abc . $hour . $day . $midnight . $mid . $year . $month . $second . $xyz));
  }

  /*
   * Can be used for database unique ids
   */
  public static function get_unique_number() {
    return abs(crc32(microtime()));
  }

  /*
   * Can be used for tokens
   */
  public static function get_unique_id() {
    $more_entropy = true;
    return uniqid(rand(), $more_entropy);
  }

}

/*
 * INSERT:
INSERT INTO users (username, password) VALUES ('root', AES_ENCRYPT('somepassword', 'key12346123'));
and SELECT:

SELECT AES_DECRYPT(password, 'key12346123') FROM users WHERE username = 'root';
Also, this requires SSL connection to the database.
 */