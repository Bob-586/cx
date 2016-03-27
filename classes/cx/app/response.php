<?php

namespace cx\app;

class response {

  private $headers = array();
  private $level = 0;
  private $output;
  public $status = '200';
  private $etag = false;
  private $last_modified_time = false;

  /**
   * Set Cache for Do Cache method to fire....
   * @param type $data - view data or DB data
   * @param type $last_modified_time
   */
  public function set_cache($last_modified_time, $data = false) {
    $this->last_modified_time = $last_modified_time;
    
    if ($data === false || empty($data)) {
      return false;
    }
    $this->etag = md5($data);
  }

  /**
   * 
   * HTTP_IF_MODIFIED_SINCE is the right way to do it. If you aren't getting it, 
   * check that Apache has mod_expires and mod_headers enabled and working properly. 
   * 
   * a2enmod expires
   * a2enmod headers
   */
  
  public function do_cache() {
    if ($this->etag === false) {
      $this->etag = md5($this->get_output());
    }
    if ($this->last_modified_time === false) {
      return false;
    }
    
    // Always send headers
    header("Last-Modified: ".gmdate("D, d M Y H:i:s", $this->last_modified_time)." GMT"); 
    header("Etag: {$this->etag}"); 
    header('Cache-Control: public');
    // Exit if not modified
    $modified_since = (isset($_SERVER['HTTP_IF_MODIFIED_SINCE'])) ? $_SERVER['HTTP_IF_MODIFIED_SINCE'] : 0;
    $none_match = (isset($_SERVER['HTTP_IF_NONE_MATCH'])) ? $_SERVER['HTTP_IF_NONE_MATCH'] : '';
    if (@strtotime($modified_since) == $this->last_modified_time || 
        @trim($none_match) == $this->etag) { 
        header("HTTP/1.1 304 Not Modified"); 
        exit; 
    }
  }  
  
  public function add_header($header) {
    $this->headers[] = $header;
  }

  public function redirect($url, $status = 302) {
    header('Location: ' . str_replace(array('&amp;', "\n", "\r"), array('&', '', ''), $url), true, $status);
    exit();
  }

  public function set_compression($level) {
    $this->level = $level;
  }

  public function set_output($output) {
    $this->output .= $output;
  }

  public function get_output() {
    return $this->output;
  }

  public function clear_output() {
    $this->output = '';
  }

  private function compress($data, $level = 0) {
    if (isset($_SERVER['HTTP_ACCEPT_ENCODING']) && (strpos($_SERVER['HTTP_ACCEPT_ENCODING'], 'gzip') !== false)) {
      $encoding = 'gzip';
    }

    if (isset($_SERVER['HTTP_ACCEPT_ENCODING']) && (strpos($_SERVER['HTTP_ACCEPT_ENCODING'], 'x-gzip') !== false)) {
      $encoding = 'x-gzip';
    }

    if (!isset($encoding) || ($level < -1 || $level > 9)) {
      return $data;
    }

    if (!extension_loaded('zlib') || ini_get('zlib.output_compression')) {
      return $data;
    }

    if (headers_sent()) {
      return $data;
    }

    if (connection_status()) {
      return $data;
    }

    if (error_get_last() !== NULL) {
      return $data;
    }

    $this->add_header(array('header' => 'Content-Encoding: ' . $encoding, 'status' => $this->status));

    return gzencode($data, (int) $level);
  }

  public function output() {    
    if ($this->output) {
      if ($this->level) {
        $output = $this->compress($this->output, $this->level);
      } else {
        $output = $this->output;
      }

      if (!headers_sent()) {
        foreach ($this->headers as $header) {
          if (isset($header['header'])) {
            $status = (isset($header['status'])) ? $header['status'] : '200';
            header($header['header'], true, $status);
          } else {
            header($header, true);
          }
        }
      }

      $this->do_cache();
      
      echo $output;
    }
  }

}