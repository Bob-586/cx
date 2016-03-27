<?php
if (! empty($_SERVER['REQUEST_URI'])) {
  
  function cx_site_url() {
    $protocol = strtolower(substr($_SERVER["SERVER_PROTOCOL"],0,strpos( $_SERVER["SERVER_PROTOCOL"],'/'))).'://';
    $domainName = $_SERVER['HTTP_HOST'];
    return $protocol . $domainName;
  }

  function cx_current_page_name() {
    return substr($_SERVER["SCRIPT_NAME"],strrpos($_SERVER["SCRIPT_NAME"],"/"));
  }

  define('CX_SITE_URL', cx_site_url() );
  define('CX_CANONICAL', CX_SITE_URL . $_SERVER['REQUEST_URI']);
  define('PROJECT_BASE_REF', CX_SITE_URL . project_folder_name() );
  define("BROWSER", $_SERVER['HTTP_USER_AGENT']);
} else {
  // define CLI functions
  $local = (count($argv) > 2 && $argv[2] == 'remote') ? false : true;
  // If passed remote as 2nd param, use remote database! ex: php index.php URI remote
  
  function cx_site_url() { return ''; }
  function cx_current_page_name() { return ''; }
  function project_folder_name() { return ''; }
  
  define('CX_SITE_URL', '');
  define('CX_CANONICAL', '');
  define('CX_BASE_REF', '');  
  define("BROWSER", '');
}

/*
 * Break Lines
 */
// How to handle the line breaks
if (! isset($_SERVER['HTTP_HOST'])) {
  define('BR', "\n"); // Console, new lines
} else {
  define('BR', "<br>\r\n"); // Web site, breaking lines
}

function is_mobile() {
  $useragent=BROWSER;
  if(preg_match('/(android|bb\d+|meego).+mobile|avantgo|bada\/|blackberry|blazer|compal|elaine|fennec|hiptop|iemobile|ip(hone|od)|iris|kindle|lge |maemo|midp|mmp|mobile.+firefox|netfront|opera m(ob|in)i|palm( os)?|phone|p(ixi|re)\/|plucker|pocket|psp|series(4|6)0|symbian|treo|up\.(browser|link)|vodafone|wap|windows ce|xda|xiino/i',$useragent)||preg_match('/1207|6310|6590|3gso|4thp|50[1-6]i|770s|802s|a wa|abac|ac(er|oo|s\-)|ai(ko|rn)|al(av|ca|co)|amoi|an(ex|ny|yw)|aptu|ar(ch|go)|as(te|us)|attw|au(di|\-m|r |s )|avan|be(ck|ll|nq)|bi(lb|rd)|bl(ac|az)|br(e|v)w|bumb|bw\-(n|u)|c55\/|capi|ccwa|cdm\-|cell|chtm|cldc|cmd\-|co(mp|nd)|craw|da(it|ll|ng)|dbte|dc\-s|devi|dica|dmob|do(c|p)o|ds(12|\-d)|el(49|ai)|em(l2|ul)|er(ic|k0)|esl8|ez([4-7]0|os|wa|ze)|fetc|fly(\-|_)|g1 u|g560|gene|gf\-5|g\-mo|go(\.w|od)|gr(ad|un)|haie|hcit|hd\-(m|p|t)|hei\-|hi(pt|ta)|hp( i|ip)|hs\-c|ht(c(\-| |_|a|g|p|s|t)|tp)|hu(aw|tc)|i\-(20|go|ma)|i230|iac( |\-|\/)|ibro|idea|ig01|ikom|im1k|inno|ipaq|iris|ja(t|v)a|jbro|jemu|jigs|kddi|keji|kgt( |\/)|klon|kpt |kwc\-|kyo(c|k)|le(no|xi)|lg( g|\/(k|l|u)|50|54|\-[a-w])|libw|lynx|m1\-w|m3ga|m50\/|ma(te|ui|xo)|mc(01|21|ca)|m\-cr|me(rc|ri)|mi(o8|oa|ts)|mmef|mo(01|02|bi|de|do|t(\-| |o|v)|zz)|mt(50|p1|v )|mwbp|mywa|n10[0-2]|n20[2-3]|n30(0|2)|n50(0|2|5)|n7(0(0|1)|10)|ne((c|m)\-|on|tf|wf|wg|wt)|nok(6|i)|nzph|o2im|op(ti|wv)|oran|owg1|p800|pan(a|d|t)|pdxg|pg(13|\-([1-8]|c))|phil|pire|pl(ay|uc)|pn\-2|po(ck|rt|se)|prox|psio|pt\-g|qa\-a|qc(07|12|21|32|60|\-[2-7]|i\-)|qtek|r380|r600|raks|rim9|ro(ve|zo)|s55\/|sa(ge|ma|mm|ms|ny|va)|sc(01|h\-|oo|p\-)|sdk\/|se(c(\-|0|1)|47|mc|nd|ri)|sgh\-|shar|sie(\-|m)|sk\-0|sl(45|id)|sm(al|ar|b3|it|t5)|so(ft|ny)|sp(01|h\-|v\-|v )|sy(01|mb)|t2(18|50)|t6(00|10|18)|ta(gt|lk)|tcl\-|tdg\-|tel(i|m)|tim\-|t\-mo|to(pl|sh)|ts(70|m\-|m3|m5)|tx\-9|up(\.b|g1|si)|utst|v400|v750|veri|vi(rg|te)|vk(40|5[0-3]|\-v)|vm40|voda|vulc|vx(52|53|60|61|70|80|81|83|85|98)|w3c(\-| )|webc|whit|wi(g |nc|nw)|wmlb|wonu|x700|yas\-|your|zeto|zte\-/i',substr($useragent,0,4))) {
    return true;
  } else {
    return false;
  }
}

function safe_strtolower($string) {
  if (extension_loaded('mbstring')) {
    return mb_strtolower($string, 'UTF-8');
  } else {
    return strtolower($string);
  }
}

/**
 * Purpose: To set session variable for use in template view.
 * @method setMessage
 * @param type $message 
 */
function cx_set_message($message, $alert = 'info', $fade_out = false) {
  $cx_ses = (\cx_configure::a_get('cx', 'session_variable')) ? \cx_configure::a_get('cx', 'session_variable') : '';
  $fade = ($fade_out) ? ' alert-dismissable' : '';
  $message .= ($fade_out) ? '<button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>' : '';
  switch($alert) {
      case 'info': $_SESSION[$cx_ses . 'message'] = '<div class="alert alert-info' . $fade . '">' . $message . '</div>';
          break;
      case 'success': $_SESSION[$cx_ses . 'message'] = '<div class="alert alert-success' . $fade . '">' . $message . '</div>';
          break;
      case 'warning': $_SESSION[$cx_ses . 'message'] = '<div class="alert alert-warning' . $fade . '">' . $message . '</div>';
          break;
      case 'danger': $_SESSION[$cx_ses . 'message'] = '<div class="alert alert-danger' . $fade . '">' . $message . '</div>';
          break;
      default: $_SESSION[$cx_ses . 'message'] = '<div class="alert' . $fade . '">' . $message . '</div>';
          break;
  }
}

function cx_get_db_time($db_time) {
  $dt = new DateTime($db_time);
  return $dt->format('m/d/Y h:i: A');
}

function cx_set_db_time_to_now() {
  return date('Y-m-d G:i:s');
}

/**
 * @method do_options - Used with HTML Select to do dropboxes
 * @param $options - key-value array of choices
 * @param $default - string containing default choice
 * @param $select_by - [KEY=>VALUE]...text default (VALUE), anyother string will use KEY
 */
function cx_do_options($options, $default = '', $select_by = 'text') {
  $values = '';
  foreach ($options as $value => $text) {
    $compair_to = ($select_by == 'text') ? $text : $value;
    $selected = (!empty($default) && $default == $compair_to) ? 'selected' : '';
    $values .= "<option value=\"{$value}\" " . $selected . ">{$text}</option>";
  }
  return $values;
}

function cx_safer_html($input) {
  return htmlspecialchars($input, ENT_QUOTES | ENT_COMPAT, 'UTF-8');
}

function cx_safe_html($input) {
  require_once CX_BASE_DIR . 'libraries' . DS . 'htmlpurifier-4.6.0' . DS . 'library'. DS . 'HTMLPurifier.auto.php';
  $config = HTMLPurifier_Config::createDefault();

  $config->set('Core.Encoding', 'UTF-8');
  $config->set('HTML.Doctype', 'XHTML 1.0 Transitional');

  $purifier = new HTMLPurifier($config);

  return $purifier->purify($input);
}

function cx_found($data, $find) {
  return (stripos($data, $find) !== false); 
}

/*
 * Variable Dump and exit
 * @param end, if true ends the script
 */
function cx_dump($var='nothing', $end=true) {
    if (! is_object($var)) {
      var_dump($var);
      echo '<br>';
    }
    
    if ($var === false) {
      echo 'It is FALSE!';
    } elseif ($var === true) {
      echo 'It is TRUE!';
    } elseif (is_resource($var)) {  
      echo 'VAR IS a RESOURCE';
    } elseif (is_array($var) && count($var) == 0) {
      echo 'VAR IS an EMPTY ARRAY!';
    } elseif (is_numeric($var)) {
      echo 'VAR is a NUMBER = ' . $var;
    } elseif (empty($var) && ! is_null($var)) {
      echo 'VAR IS EMPTY!';
    } elseif ($var == 'nothing') {
      echo 'MISSING VAR!';
    } elseif (is_null($var)) {
      echo 'VAR IS NULL!';
    } elseif (is_string($var)) {
      echo 'VAR is a STRING = ' . $var;
    } else {
      echo "<pre style=\"border: 1px solid #000; overflow: auto; margin: 0.5em;\">";
      print_r($var);
      echo '</pre>';
    }
    echo '<br><br>';
    
    if ($end === true) {
      exit;
    }
}

function cx_error_log($msg) {
  error_log($msg, 3, CX_LOGS_DIR . "cx.log");
}

function cx_convert_bytes($size) {
    $unit=array('b','kb','mb','gb','tb','pb');
    return @round($size/pow(1024,($i=floor(log($size,1024)))),2).' '.$unit[$i];
}

function cx_show_table($header_fields, $fields) {
  $nl = PHP_EOL;
  echo "{$nl}<table border='1' cellpadding='0' cellspacing='0'>{$nl}";
  echo "\t<tr>{$nl}";
  foreach($header_fields as $header_field) {
    echo "\t\t<th>{$header_field}</th>{$nl}";
  }
  echo "\t</tr>{$nl}";
  
  foreach($fields as $field) {
  echo "\t<tr>{$nl}";
    foreach($field as $td) {
      echo "\t\t<td>{$td}</td>{$nl}";
    }
  echo "\t</tr>{$nl}";  
  }
  echo "</table>{$nl}"; 
}

/*
 * parameters are bool
 */
function cx_get_memory_stats($echo = true, $post = false) {
  global $startup_time, $mem_baseline;
  
  if ($post) {
    $check = (isset($_POST['debug']) && $_POST['debug'] === 'true') ? true : false; 
  } else { 
    $check = (isset($_GET['debug']) && $_GET['debug'] === 'true') ? true : false;
  }
  
  if ($check || defined('DEBUG') && DEBUG === true) {
    $now_mem = memory_get_usage();
    $diff_mem = $now_mem - $mem_baseline;
    
    $s_current = cx_convert_bytes($now_mem);
    $s_diff = cx_convert_bytes($diff_mem);
    $s_startup_mem = cx_convert_bytes($mem_baseline);
    
    $peak = memory_get_peak_usage();  
    $s_peak = cx_convert_bytes($peak);
    $diff_peak = $peak - $mem_baseline;
    $s_diff_peak = cx_convert_bytes($diff_peak);
    
    if ($post) {
      $a_fields['start-up'] = $s_startup_mem;
      $a_fields['currently'] = $s_current;
      $a_fields['total-diff'] = $s_diff;
      $a_fields['peak'] = $s_peak;
      $a_fields['total-diff-peak'] = $s_diff_peak;      
    } else {
      $a_headers = array('Memory Item', 'Size');
      $a_fields[] = array('On-StartUp', $s_startup_mem);
      $a_fields[] = array('Currently', $s_current);
      $a_fields[] = array('<b>Total Diff</b>', "<b>{$s_diff}</b>");
      $a_fields[] = array('&nbsp;', '&nbsp;');
      $a_fields[] = array('PEAK', $s_peak);
      $a_fields[] = array('<i>Total Diff PEAK</i>', "<i>{$s_diff_peak}</i>");      
    }
    
    if ($echo === true) {
      cx_show_table($a_headers, $a_fields); 
    } else {
      return $a_fields;
    }
  }
}

/* meta redirect when headers are already sent... */
function cx_goto_url($url) {
    echo '<META http-equiv="refresh" content="0;URL=' . $url . '">';
    exit;
}

/* rediect to url and attempt to send via header */
function cx_redirect_url($url) {
    if (! headers_sent()) {     
        header('Location: ' . $url);
    } else {
      cx_goto_url($url);
    }
    exit;
}