<?php

/**
 * @copyright (c) 2015
 * @author Chris Allen, Robert Strutts
 */

namespace cx\app;

class cx_curl {
  public $hostname;
  public $port;
  public $json_decode = true;
  public $ssl = true;
  public $authentication = false;
  
  public $includeHeader = true;
  public $noBody = false;
  public $binary = false;
  public $referer;
  public $useragent = 'Mozilla/4.0 (compatible; MSIE 6.0; Windows NT 5.1';
  public $cookie;
  public $auth_name;
  public $auth_pass;
  private $_status;
  private $_header_response;

  public function exists() {
    return (function_exists('curl_exec'));
  }
  
  /*
	 * object/array? get (string action_path)
	 */
	public function get($action_path, $parameters = array()) {
		return $this->action($action_path, "GET", $parameters);
	}

	/*
	 * bool put (string action_path, array parameters)
	 */
	public function put($action_path, $parameters = array()) {
		return $this->action($action_path, "PUT", $parameters);
	}

	/*
	 * bool post (string action_path, array parameters)
	 */
	public function post($action_path, $parameters = array()) {
		return $this->action($action_path, "POST", $parameters);
	}

	/*
	 * bool delete (string action_path)
	 */
	public function delete($action_path) {
		return $this->action($action_path, "DELETE");
	}

  /*
	 * bool delete (string action_path, array parameters)
	 */
	public function patch($action_path, $parameters = array()) {
		return $this->action($action_path, "PATCH", $parameters);
	}
  /*
   * HTTP response from action request
   */
  public function get_status() {
    return $this->_status;
  }
  
  public function get_header_response() {
    return $this->_header_response;
  }
  
  /*
	 * object action (string action_path, string http_method[, array put_post_parameters])
	 * This method is responsible for the general cURL requests to the JSON API,
	 * and sits behind the abstraction layer methods get/put/post/delete etc.
	 */
	private function action($action_path, $http_method, $put_post_parameters = null) {
    if (\cx\app\main_functions::found($action_path, "://") === false) {
      // Check if we have a prefixed / on the path, if not add one.
      if (substr($action_path, 0, 1) != "/") {
        $action_path = "/".$action_path;
      }
    }

		// Prepare cURL resource.
		$ch = curl_init();
		
		$put_post_http_headers = array();
		// Lets decide what type of action we are taking...
		switch ($http_method) {
			case "GET":
				// cURL used to translate POSTFIELDS into the query string when the
				// request method was GET, but that doesn't seem to be the case any
				// longer, so we need to build them into the query string ourselves.
				$action_postfields_string = http_build_query($put_post_parameters);
				$action_path .= (strpos($action_path, '?') === FALSE ? '?' : '&') . $action_postfields_string;
				unset($action_postfields_string);

				break;
			case "PUT":
				curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "PUT");

				// Set "POST" data.
				$action_postfields_string = http_build_query($put_post_parameters);
				curl_setopt($ch, CURLOPT_POSTFIELDS, $action_postfields_string);
				unset($action_postfields_string);

				// Add required HTTP headers.
				curl_setopt($ch, CURLOPT_HTTPHEADER, $put_post_http_headers);
				break;
			case "POST":
				curl_setopt($ch, CURLOPT_POST, true);

				// Set POST data.
				$action_postfields_string = http_build_query($put_post_parameters);
				curl_setopt($ch, CURLOPT_POSTFIELDS, $action_postfields_string);
				unset($action_postfields_string);

				// Add required HTTP headers.
				curl_setopt($ch, CURLOPT_HTTPHEADER, $put_post_http_headers);
				break;
			case "DELETE":
				curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "DELETE");
				// No "POST" data required, the delete destination is specified in the URL.

				// Add required HTTP headers.
				curl_setopt($ch, CURLOPT_HTTPHEADER, $put_post_http_headers);
				break;
      case "PATCH":
				curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "PATCH");

				// Set "POST" data.
				$action_postfields_string = http_build_query($put_post_parameters);
				curl_setopt($ch, CURLOPT_POSTFIELDS, $action_postfields_string);
				unset($action_postfields_string);

				// Add required HTTP headers.
				curl_setopt($ch, CURLOPT_HTTPHEADER, $put_post_http_headers);
			default:
				return false;
		}
		
    $http = ($this->ssl) ? 'https' : 'http';
    
    $action_path = (\cx\app\main_functions::found($action_path, "://")) ? $action_path : "{$http}://{$this->hostname}:{$this->port}{$action_path}";
    
    curl_setopt($ch, CURLOPT_URL, $action_path);
    
    if ($this->authentication){ 
      curl_setopt($ch, CURLOPT_USERPWD, $this->auth_name.':'.$this->auth_pass); 
    } 
    
		if ($this->includeHeader) { 
      curl_setopt($ch, CURLOPT_HEADER, true); 
    } 

    if ($this->noBody) { 
      curl_setopt($ch, CURLOPT_NOBODY, true); 
    } 

/*    
    if ($this->binary) { 
      curl_setopt($ch,CURLOPT_BINARYTRANSFER, true); 
    }
*/
    
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    
    if (isset($this->cookie)) {
      curl_setopt($ch, CURLOPT_COOKIE, $this->cookie);
    }
    
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, $this->ssl);
    curl_setopt($ch, CURLOPT_USERAGENT, $this->useragent);
    
    if (isset($this->referer)) {
      curl_setopt($ch, CURLOPT_REFERER, $this->referer);
    }

		$action_response = curl_exec($ch);
    $this->_status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    
		curl_close($ch);
		unset($ch);

    if($this->noBody) {
      return true;
    }
    
    if(! $this->includeHeader) { 
      return ($this->json_decode) ? json_decode($action_response, true) : $action_response;
    }

    
		$split_action_response = explode("\r\n\r\n", $action_response, 2);
        
    if (! isset($split_action_response[1])) {
      return false;
    }
    
		$this->_header_response = $split_action_response[0];
		$body_response = $split_action_response[1];
    return ($this->json_decode) ? json_decode($body_response, true) : $body_response;
  }

}