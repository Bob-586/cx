<?php

/**
 * @copyright (c) 2015
 * @author Chris Allen, Robert Strutts
 */

namespace cx\app;

class cx_api {
  const CONTINUE_STATUS = "Continue"; // 100
  const SWITCHING_PROTOCOLS = "Switching Protocols"; // 101
  const OK = "OK"; // 200
  const CREATED = "Created"; // 201
  const ACCEPTED = "Accepted"; // 202
  const NON_AUTHORITATIVE = "Non-Authoritative Information"; // 203
  const NO_CONTENT = "No Content"; // 204
  const RESET_CONTENT = "Reset Content"; // 205
  const PARTIAL_CONTENT = "Partial Content"; // 206  
  const ALREADY_REPORTED = "Already Reported"; // 208
  const MULTI_STATUS = "Multiple Choices"; // 300
  const MOVED_PERMANENTLY = "Moved Permanently"; // 301
  const MOVED_TEMPORARILY = "Moved Temporarily"; // 302
  const SEE_OTHER = "See Other"; // 303
  const NOT_MODIFIED = "Not Modified"; // 304
  const USE_PROXY = "Use Proxy"; // 305
  const TEMP_REDIRECT = "Temporary Redirect"; // 307
  const BAD_REQUEST = "The request cannot be fulfilled due to bad syntax."; // 400
  const UNAUTHORIZED = "The authorization details given appear to be invalid."; // 401
  const PAYMENT_REQUIRED = "Payment Required"; // 402
  const FORBIDDEN = "The requested resource is not accessible."; // 403
  const NOT_FOUND = "The requested resource does not exist."; // 404
  const METHOD_NOT_ALLOWED = "Method Not Allowed"; // 405
  const NOT_ACCEPTABLE = "Not Acceptable"; // 406
  const PROXY_AUTH_REQUIRED = "Proxy Authentication Required"; // 407
  const REQUEST_TIME_OUT = "Request Time-out"; // 408
  const CONFLICT = "Conflict"; // 409
  const GONE = "Gone"; // 410
  const LENGTH_REQUIRED = "Length Required"; // 411
  const PRECONDITION_FAILED = "Precondition Failed"; // 412
  const REQUEST_ENTITY_TOO_LARGE = "Request Entity Too Large"; // 413
  const REQUEST_URI_TOO_LARGE = "Request-URI Too Large"; // 414
  const UNSUPPORTED_FORMAT = "The format requested is not supported by the server."; // 415
  const EXPECTATION_FAILED = "Expectation Failed"; // 417
  const INTERNAL_ERROR = "An unexpected error occured."; // 500
  const NOT_IMPLEMENTED = "Not Implemented"; // 501
  const BAD_GATEWAY = "Bad Gateway"; // 502
  const MAINTENANCE_MODE = "The requested resource is currently unavailable due to maintenance."; // 503
  const GATEWAY_TIME_OUT = "Gateway Time-out"; // 504
  const HTTP_VERSION_NOT_SUPPORTED = "HTTP Version not supported"; // 505

  public static function encode($data, $status_code) {
    $response_type = \cx\app\static_request::init('post', 'return')->to_string();
    switch ($response_type) {
      case 'xml':
        self::xml_encode($status_code, $data);
        break;
      case 'php':
        self::php_encode($data, $status_code);
        break;
      case 'json':
      default:
        self::json_encode($data, $status_code);
        break;
      }
    }

    /**
     * See: HTTP_status_codes.txt
     * @link https://en.wikipedia.org/wiki/List_of_HTTP_status_codes
     * HTTP $code to try, use $default is not valid
     */
  public static function get_code_number($code, $default) {
    return ($code > 99 && $code < 600) ? $code : $default;
  }

  public static function error($data) {
    $error_code = (isset($data['code'])) ? $data['code'] : 400;

    $code = self::get_code_number($error_code, 400);
    $data['result'] = false;

    if (isset($data['response'])) {
      switch ($data['response']) {
        case self::CONTINUE_STATUS:
          $long_code = "100 Continue";
          $data['message'] = self::CONTINUE_STATUS;
          break;
        case self::SWITCHING_PROTOCOLS:
          $long_code = "101 Switching Protocols";
          $data['message'] = self::SWITCHING_PROTOCOLS;
          break;
        case self::MULTI_STATUS:
          $long_code = "300 Multiple Choices";
          $data['message'] = self::MULTI_STATUS;
          break;
        case self::MOVED_PERMANENTLY:
          $long_code = "301 Moved Permanently";
          $data['message'] = self::MOVED_PERMANENTLY;
          break;
        case self::MOVED_TEMPORARILY:
          $long_code = "302 Moved Temporarily";
          $data['message'] = self::MOVED_TEMPORARILY;
          break;
        case self::SEE_OTHER:
          $long_code = "303 See Other";
          $data['message'] = self::SEE_OTHER;
          break;
        case self::NOT_MODIFIED:
          $long_code = "304 Not Modified";
          $data['message'] = self::NOT_MODIFIED;
          break;
        case self::USE_PROXY:
          $long_code = "305 Use Proxy";
          $data['message'] = self::USE_PROXY;
          break;
        case self::TEMP_REDIRECT: 
          $long_code = "307 Temporary Redirect"; 
          $data['message'] = self::TEMP_REDIRECT;
        case self::BAD_REQUEST:
          $long_code = "400 Bad Request";
          $data['message'] = self::BAD_REQUEST;
          break;
        case self::UNAUTHORIZED:
          $long_code = "401 Unauthorized";
          $data['message'] = self::UNAUTHORIZED;
          break;
        case self::PAYMENT_REQUIRED:
          $long_code = "402 Payment Required";
          $data['message'] = self::PAYMENT_REQUIRED;
          break;
        case self::FORBIDDEN:
          $long_code = "403 Forbidden";
          $data['message'] = self::FORBIDDEN;
          break;
        case self::NOT_FOUND:
          $long_code = "404 Not Found";
          $data['message'] = self::NOT_FOUND;
          break;
        case self::METHOD_NOT_ALLOWED:
          $long_code = "405 Method Not Allowed";
          $data['message'] = self::METHOD_NOT_ALLOWED;
          break;
        case self::NOT_ACCEPTABLE:
          $long_code = "406 Bad Request";
          $data['message'] = self::NOT_ACCEPTABLE;
          break;
        case self::PROXY_AUTH_REQUIRED:
          $long_code = "407 Proxy Authentication Required";
          $data['message'] = self::PROXY_AUTH_REQUIRED;
          break;
        case self::REQUEST_TIME_OUT:
          $long_code = "408 Request Time-out";
          $data['message'] = self::REQUEST_TIME_OUT;
          break;
        case self::CONFLICT:
          $long_code = "409 Bad Request";
          $data['message'] = self::CONFLICT;
          break;
        case self::GONE:
          $long_code = "410 Gone";
          $data['message'] = self::GONE;
          break;
        case self::LENGTH_REQUIRED:
          $long_code = "411 Length Required";
          $data['message'] = self::LENGTH_REQUIRED;
          break;
        case self::PRECONDITION_FAILED:
          $long_code = "412 Precondition Failed";
          $data['message'] = self::PRECONDITION_FAILED;
          break;
        case self::REQUEST_ENTITY_TOO_LARGE:
          $long_code = "413 Request Entity Too Large";
          $data['message'] = self::REQUEST_ENTITY_TOO_LARGE;
          break;
        case self::REQUEST_URI_TOO_LARGE:
          $long_code = "414 Request-URI Too Large";
          $data['message'] = self::REQUEST_URI_TOO_LARGE;
          break;        
        case self::UNSUPPORTED_FORMAT:
          $long_code = "415 Unsupported Media Type";
          $data['message'] = self::UNSUPPORTED_FORMAT;
          break;
        case self::EXPECTATION_FAILED:
          $long_code = "417 Expectation Failed";
          $data['message'] = self::EXPECTATION_FAILED;
          break;
        case self::INTERNAL_ERROR:
          $long_code = "500 Internal Server Error";
          $data['message'] = self::INTERNAL_ERROR;
          break;
        case self::NOT_IMPLEMENTED:
          $long_code = "501 Not Implemented";
          $data['message'] = self::NOT_IMPLEMENTED;
          break;
        case self::BAD_GATEWAY:
          $long_code = "502 Bad Gateway";
          $data['message'] = self::BAD_GATEWAY;
          break;        
        case self::MAINTENANCE_MODE:
          $long_code = "503 Service Unavailable";
          $data['message'] = self::MAINTENANCE_MODE;
          break;
        case self::GATEWAY_TIME_OUT:
          $long_code = "504 Gateway Time-out";
          $data['message'] = self::GATEWAY_TIME_OUT;
          break;
        case self::HTTP_VERSION_NOT_SUPPORTED:
          $long_code = "505 HTTP Version not supported";
          $data['message'] = self::HTTP_VERSION_NOT_SUPPORTED;
          break;        
        default:
          $long_code = $code;
          break;
      }
    } else {
      $long_code = $code;
    }

    $data['code'] = $long_code;

    if (\cx\app\static_request::init('post', 'debug')->compair_it('true')) {
      $echo = false;
      $post = true;
      $data['memory_used'] = cx_get_memory_stats($echo, $post);
    }

    self::encode($data, $long_code);
  }

  public static function ok($data = array()) {
    $data['result'] = true;
    $code = 200; // OK

    if (\cx\app\static_request::init('post', 'debug')->compair_it('true')) {
      $echo = false;
      $post = true;
      $data['memory_used'] = cx_get_memory_stats($echo, $post);
    }

    if (isset($data['code'])) {
      if ($data['code'] > 199 && $data['code'] < 209) {
        $code = $data['code'];
      }
      unset($data['code']);
    }

    if (isset($data['response'])) {
      switch ($data['response']) {
        case self::CREATED: $long_code = "201 Created"; break;
        case self::ACCEPTED: $long_code = "202 Accepted"; break;
        case self::NON_AUTHORITATIVE: $long_code = "203 Non-Authoritative Information"; break;
        case self::NO_CONTENT: $long_code = "204 No Content"; break;
        case self::RESET_CONTENT: $long_code = "205 Reset Content"; break;
        case self::PARTIAL_CONTENT: $long_code = "206 Partial Content"; break;
        case self::ALREADY_REPORTED: $long_code = "208 Already Reported"; break;
        case self::OK: $long_code = "200 OK"; break;
        default: $long_code = $code; break;
      }
    } else {
      $long_code = $code;
    }

    self::encode($data, $long_code);
  }

  public static function xml_encode($status_code, $mixed, $domElement = null, $DOMDocument = null) {
    if (is_null($DOMDocument)) {
      $DOMDocument = new \DOMDocument;
      $DOMDocument->formatOutput = true;
      self::xml_encode($status_code, $mixed, $DOMDocument, $DOMDocument);

      if (!headers_sent()) {
        header($_SERVER['SERVER_PROTOCOL'] . " " . $status_code);
        header("Access-Control-Allow-Orgin: *");
        header("Access-Control-Allow-Methods: *");
        header('Content-Type: text/xml; charset=utf-8', true, intval($status_code));
      }

      echo $DOMDocument->saveXML();
      exit;
    } else {
      if (is_array($mixed)) {
        foreach ($mixed as $index => $mixedElement) {
          if (is_int($index)) {
            if ($index === 0) {
              $node = $domElement;
            } else {
              $node = $DOMDocument->createElement($domElement->tagName);
              $domElement->parentNode->appendChild($node);
            }
          } else {
            $plural = $DOMDocument->createElement($index);
            $domElement->appendChild($plural);
            $node = $plural;
            if (!(rtrim($index, 's') === $index)) {
              $singular = $DOMDocument->createElement(rtrim($index, 's'));
              $plural->appendChild($singular);
              $node = $singular;
            }
          }

          self::xml_encode($status_code, $mixedElement, $node, $DOMDocument);
        } // end of foreach
      } else {
        $mixed = is_bool($mixed) ? ($mixed ? 'true' : 'false') : $mixed;
        $domElement->appendChild($DOMDocument->createTextNode($mixed));
      }
    }
  }

  /*
   * Purpose to decode XML into an array
   */

  public static function xml_decode($xmlstring) {
    $xml = simplexml_load_string($xmlstring);
    $json = json_encode($xml);
    return json_decode($json, true);
  }

  public static function xml_parse($htmlStr) {
    $xmlStr = str_replace('<', '&lt;', $htmlStr);
    $xmlStr = str_replace('>', '&gt;', $xmlStr);
    $xmlStr = str_replace('"', '&quot;', $xmlStr);
    $xmlStr = str_replace("'", '&#39;', $xmlStr);
    $xmlStr = str_replace("&", '&amp;', $xmlStr);
    return $xmlStr;
  }

  public static function json_encode($data, $status_code) {
    if (!headers_sent()) {
      header($_SERVER['SERVER_PROTOCOL'] . " " . $status_code);
      /*
       * Allow JavaScript from anywhere. CORS - Cross Origin Resource Sharing
       * @link https://manning-content.s3.amazonaws.com/download/f/54fa960-332e-4a8c-8e7f-1eb213831e5a/CORS_ch01.pdf
       */
      header("Access-Control-Allow-Orgin: *"); 
      header("Access-Control-Allow-Methods: *");
      header('Content-Type: application/json; charset=utf-8', true, intval($status_code));
    }
    echo json_encode($data);
    exit;
  }

  public static function php_encode($data, $status_code) {
    if (!headers_sent()) {
      header($_SERVER['SERVER_PROTOCOL'] . " " . $status_code);
      header("Access-Control-Allow-Orgin: *");
      header("Access-Control-Allow-Methods: *");
      header('Content-Type: text/php; charset=utf-8', true, intval($status_code));
    }
    echo serialize($data);
    exit;
  }

}
