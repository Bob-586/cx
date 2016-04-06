<?php

define('CX_DUPLICATE_FOUND', '3');
define('CX_VALIDATION_ERROR', '4');

function cx_email_error($a_error) {
  $errors = "The following errors occured:\r\n ";
  if (is_array($a_error)) {
    foreach ($a_error as $error) {
      $errors .= $error . "\r\n";
    }
  } elseif (is_object($a_error)) {
    $errors .= serialize($a_error);
  } elseif (is_string($a_error)) {
    $errors .= $a_error;
  } else {
    return false;
  }

  // Only send error to email once per day!!
  $file = CX_BASE_DIR . 'last_error.txt';
  $lines = (file_exists($file)) ? file_get_contents($file) : '';
  $date = date('m/d/Y');
  if (substr_count($lines, $date) > 0) {
    return false;
  }

  if (! is_writable($file)) {
    return false; // Avoid repeated messages.....
  }
  
  // Save date to last_error, to prevent further error reports.
  $worked = file_put_contents($file, $date, LOCK_EX);

  if ($worked === false) {
    return false; // Avoid repeated messages as it did NOT update correctly!
  }

  $to = cx_configure::a_get('cx', 'admin_name');
  $email = cx_configure::a_get('cx', 'admin_email');
  
  if (defined('CX_SITE_NAME')) {
    $site = CX_SITE_NAME;
  } else {
    $site = 'system';
  }
  
  $subject = 'System error in ' . $site;
  $from = 'noreply@' . str_replace(" ", "_", $site);

  if ($email === false || empty($email)) {
    return false;
  }
  
  if (cx_configure::a_get('cx', 'email_on_errors') === true) {     
    if (! empty($to)) {
      cx_send_email(array('to'=>array('address'=>$email, 'name'=>$to),'from'=>$from,'subject'=>$subject,'message'=>$errors));
    } else {
      cx_send_email(array('to'=>$email,'from'=>$from,'subject'=>$subject,'message'=>$errors));
    }
  }
//  cx_twilio($errors);
}

function cx_json_error_h($data) {
  if (! isset($_GET['callback']) && ! isset($_GET['json']) ) {
    return false;
  }
  
  $status_code = 501;
  if (!headers_sent()) {
    header($_SERVER['SERVER_PROTOCOL'] . " " . $status_code);
    header("Access-Control-Allow-Orgin: *"); 
    header("Access-Control-Allow-Methods: *");
    header('Content-Type: application/json; charset=utf-8', true, intval($status_code));
  }
  echo json_encode(array('success'=>false,'error'=>$data));
  exit;
}

function cx_exception_handler($exception) {
  $err = "Fatal Error: Uncaught exception " . get_class($exception) . " with message: " . $exception->getMessage();
  $err .= " thrown in: " . $exception->getFile() . " on line: " . $exception->getLine() . "\r\n";
  error_log($err);

  $msg = '<link rel="stylesheet" href="' . CX_BASE_REF . '/assets/uikit/css/uikit.gradient.min.css" type="text/css" media="all" />';
  $msg .= '<div class="uk-alert uk-alert-danger">';
  $msg .= '<b>Fatal error</b>:  Uncaught exception \'' . get_class($exception) . '\' with message ';
  $msg .= $exception->getMessage() . '<br>';
  $msg .= 'Stack trace:<pre>' . $exception->getTraceAsString() . '</pre>';
  $msg .= 'thrown in <b>' . $exception->getFile() . '</b> on line <b>' . $exception->getLine() . '</b><br>';
  $msg .= '</div>';

  cx_json_error_h($msg);
    
  if (\cx_configure::a_get('cx', 'live') === true) {
    cx_email_error($msg);
    cx_global_error_handler();
  } else {
    echo $msg;
    exit;
  }
}

set_exception_handler('cx_exception_handler');

if (\cx_configure::a_get('cx', 'live') === true) {
  error_reporting(E_ALL ^ E_NOTICE);
  set_error_handler('cx_global_error_handler', E_ALL ^ (E_NOTICE | E_USER_NOTICE));
} else {
  error_reporting(E_ALL);
}

register_shutdown_function('cx_custom_error_checker');

function cx_custom_error_checker() {
  $a_errors = error_get_last();
  if (is_array($a_errors)) {
    $msg = "Error: {$a_errors['message']} File:{$a_errors['file']} Line:{$a_errors['line']}.";
    error_log($msg);

    cx_json_error_h($msg);
    if (\cx_configure::a_get('cx', 'live') === true) {
      cx_email_error($msg);
      cx_global_error_handler();
    } else {
      echo '<link rel="stylesheet" href="' . CX_BASE_REF . '/assets/bootstrap/css/bootstrap.min.css" type="text/css" media="all" />';
      echo "</head>\r\n<body>\r\n";
      echo '<div class="alert alert-danger">';
      echo "{$a_errors['message']}, in file: {$a_errors['file']}, on line #{$a_errors['line']}.";
      echo '</div>';
      echo '<script>alert("'.str_replace('"', '', $msg).'");</script>';
    }
  }
}

function cx_global_error_handler($errno=0, $errstr='', $errfile='', $errline=0) {
  switch ($errno) {
    case E_USER_ERROR:
      $err = "My ERROR [$errno] $errstr<br />\n";
      $err .= "  Fatal error on line $errline in file $errfile";
      $err .= ", PHP " . PHP_VERSION . " (" . PHP_OS . ")<br />\n";
      $err .= "Aborting...<br />\n";
      break;

    case E_USER_WARNING:
      $err = "<b>My WARNING</b> [$errno] $errstr<br />\n";
      break;

    case E_USER_NOTICE:
      $err = "<b>My NOTICE</b> [$errno] $errstr<br />\n";
      break;

    default:
      $err = (! empty($errstr)) ? "Unknown error type: [$errno] $errstr<br />\n" : '';
      break;
  }
  if (! empty($err)) {
    error_log($err);
  }

  cx_json_error_h($err);
  
  if (is_on_error_page() === true) {
    require PROJECT_BASE_DIR . "templates" . DS . "error.tpl.php";
    exit(1); // Prevent HTML Looping!!!
  }

  $http_response_code = '307'; // 307 Temporary Redirect
  header('Location: ' . PROJECT_BASE_REF . '/app/' . DEFAULT_PROJECT . '/error.html', TRUE, $http_response_code);
  exit(1);
}

/*
 * Purpose: To check if desconstructor has been run yet.
 */

function cx_not_done() {
  static $is_done = 0; // keep static so this works. thanks.
  ++$is_done;
  if ($is_done > 1) {
    return false;
  } else {
    return true;
  }
}

class CxException extends Exception {
  
}
