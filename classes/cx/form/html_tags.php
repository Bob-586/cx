<?php
/**
 * Purpose: To convert HTML/JavaScript double quotes commands 
 * into single quotes, so the HTML does not close out.... 
 * @param type $html
 * @return type double quotes into a string of single quotes
 */
function change_quotes($js) {
  return str_replace('"', "'", $js);
}

function combine_tags($option, $value, $extra, $js = true) {
  $e = (isset($extra[$option])) ? $extra[$option] : '';
  if ($js === true) {
    if (! empty($e) && strrpos($e, ";") === false) {
      $e .= "; ";
    }
    if (strrpos($value, ";") === false) {
      $value .= ";";
    }
  } else {
    $e .= ' ';
  }
  return " {$option}=\"" . change_quotes($e . $value) . '"';
}

function add_tags($option, $extra) {
  if (isset($extra[$option])) {
    return " {$option}=\"" . change_quotes($extra[$option]) . '"';
  } else {
    return false;
  }
}

/**
 * Purpose: To define good behaviors for HTML...
 * @param type $htmlOptions
 */
function get_default_options($command, $name, $htmlOptions, $extra = array()) {
  $r  = get_html_styles($htmlOptions, $extra);
  $r .= get_html_common_attributes($htmlOptions);
  $r .= get_html_misc_attributes($htmlOptions);
  $r .= get_html_global_attributes($htmlOptions);
  $r .= get_html_event_attributes($htmlOptions, $extra);
  return $r;
}

function get_html_styles($htmlOptions, $extra) {
  $a = array('id','class','style');
  $ret = '';
  foreach($a as $option) {
    if (isset($htmlOptions[$option])) {
      $ret .= combine_tags($option, $htmlOptions[$option], $extra, false);
    } else {
      $e = add_tags($option, $extra);
      if ($e !== false) {
        $ret .= $e;
      }
    }
  }
  return $ret;
}

function get_html_common_attributes($htmlOptions) {
  $a = array('align','size','maxlength','cols','rows');
  $ret = '';
  foreach($htmlOptions as $option=>$value) {
    if (is_array($htmlOptions[$option])) {
      continue;
    }
    if (isset($htmlOptions[$option]) && in_array($option, $a)) {
      $ret .= " {$option}=\"" . change_quotes($htmlOptions[$option]) . '"';
    }
  }
  return $ret;
}

function get_html_misc_attributes($htmlOptions) {
  $a = array('charset','coords','hreflang','media','name','rel','rev','shape',
  'target','type','alt','border','height','src','width','form','disabled',
  'color','accept','autocomplete','formnovalidate','formtarget','list','max',
  'min','multiple','pattern','placeholder','step','bgcolor','colspan',
  'headers','nowrap','rowspan','scope','valign','cellpadding','cellspacing',
  'rules','summary','autofocus','for');
  $ret = '';
  foreach($htmlOptions as $option=>$value) {
    if (is_array($htmlOptions[$option])) {
      continue;
    }
    if (isset($htmlOptions[$option]) && in_array($option, $a)) {
      $ret .= " {$option}=\"" . change_quotes($htmlOptions[$option]) . '"';
    }
  }
  return $ret;
}

/**
 * @link http://www.w3schools.com/tags/ref_standardattributes.asp
 */
function get_html_global_attributes($htmlOptions) {
  $a = array('accesskey','contenteditable','contextmenu',
  'dir','draggable','hidden','lang','spellcheck','tabindex','title','translate');
  $ret = '';
  foreach($htmlOptions as $option=>$value) {
    if (is_array($htmlOptions[$option])) {
      continue;
    }
    if (isset($htmlOptions[$option]) && in_array($option, $a)) {
      $ret .= " {$option}=\"" . change_quotes($htmlOptions[$option]) . '"';
    }
  }
  return $ret;
}

/**
 * @link http://www.w3schools.com/tags/ref_eventattributes.asp
 */
function get_html_event_attributes($htmlOptions, $extra) {
  $r  = get_window_event_attributes($htmlOptions, $extra);
  $r .= get_form_events($htmlOptions, $extra);
  $r .= get_keyboard_events($htmlOptions, $extra);
  $r .= get_mouse_events($htmlOptions, $extra);
//    $r .= get_media_events($htmlOptions);
  return $r;
}

function get_window_event_attributes($htmlOptions, $extra) {
  $a = array('onafterprint','onbeforeprint','onbeforeunload','onerror',
  'onhaschange','onload','onmessage','onoffline','ononline','onpagehide',
  'onpageshow','onpopstate','onredo','onresize','onstorage','onundo','onunload');
  $ret = '';
  foreach($a as $option) {
    if (isset($htmlOptions[$option])) {
      $ret .= combine_tags($option, $htmlOptions[$option], $extra);
    } else {
      $e = add_tags($option, $extra);
      if ($e !== false) {
        $ret .= $e;
      }
    }
  }
  return $ret;
}

function get_form_events($htmlOptions, $extra) {
  $a = array('onblur','onchange','oncontextmenu','onfocus',
  'onformchange','onforminput','oninput','oninvalid','onreset','onselect');
  $ret = '';
  foreach($a as $option) {
    if (isset($htmlOptions[$option])) {
      $ret .= combine_tags($option, $htmlOptions[$option], $extra);
    } else {
      $e = add_tags($option, $extra);
      if ($e !== false) {
        $ret .= $e;
      }
    }
  }
  return $ret;
}

function get_keyboard_events($htmlOptions, $extra) {
  $a = array('onkeydown','onkeypress','onkeyup');
  $ret = '';
  foreach($a as $option) {
    if (isset($htmlOptions[$option])) {
      $ret .= combine_tags($option, $htmlOptions[$option], $extra);
    } else {
      $e = add_tags($option, $extra);
      if ($e !== false) {
        $ret .= $e;
      }
    }
  }
  return $ret;
}

function get_mouse_events($htmlOptions, $extra) {
  $a = array('onclick','ondrag','ondragend','ondragenter',
  'ondragleave','ondragover','ondragstart','ondrop','onmousedown','onmousemove',
  'onmouseout','onmouseover','onmouseup','onmousewheel','onscroll');
  $ret = '';
  foreach($a as $option) {
    if (isset($htmlOptions[$option])) {
      $ret .= combine_tags($option, $htmlOptions[$option], $extra);
    } else {
      $e = add_tags($option, $extra);
      if ($e !== false) {
        $ret .= $e;
      }
    }
  }
  return $ret;
}

function get_media_events($htmlOptions, $extra) {
  $a = array('onabort','oncanplay','oncanplaythrough','ondurationchange',
  'onemptied','onended','onloadeddata','onloadedmetadata','onloadstart',
  'onpause','onplay','onplaying','onprogress','onratechange',
  'onreadystatechange','onseeked','onstalled','onsuspend','ontimeupdate',
  'onvolumechange','onwaiting');
  $ret = '';
  foreach($a as $option) {
    if (isset($htmlOptions[$option])) {
      $ret .= combine_tags($option, $htmlOptions[$option], $extra);
    } else {
      $e = add_tags($option, $extra);
      if ($e !== false) {
        $ret .= $e;
      }
    }
  }
  return $ret;
}