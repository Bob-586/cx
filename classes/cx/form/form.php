<?php

/**
 * @copyright (c) 2013
 * @author Robert Strutts 
 */
namespace cx\form;
use cx\app\app as app;
use cx\app\main_functions as main_fn;

require_once CX_BASE_DIR . 'classes' . DS . 'cx' . DS . 'form' . DS . 'html_tags.php';
require_once CX_BASE_DIR . 'classes' . DS . 'cx' . DS . 'form' . DS . 'aliases.php';
require_once CX_BASE_DIR . 'classes' . DS . 'cx' . DS . 'form' . DS . 'commands.php';

class form extends app {
  use aliases, commands;
  
  protected $formname;
  protected $router;
  protected $defaults;
  protected $db;
  protected $html;
  protected $model;
  protected $js;
  protected $font;
  protected $font_size;
  protected $field_bk_color;
  protected $field_color;
  protected $form_bk_color;
  protected $name_mode; // element naming convention
  public $taborder = 0; // Set to zero to disable tab order 
  public $layout = "default";
  private $extra;

  public function __construct($options = array()) {
    $name = (isset($options['name'])) ? $options['name'] : 'default';
    $router_action = (isset($options['router_action'])) ? $options['router_action'] : '';
    $method = (isset($options['method'])) ? $options['method'] : 'POST';
    $defaults = (isset($options['defaults'])) ? $options['defaults'] : array();
    $this->font = (isset($options['font'])) ? $options['font'] : '';
    $this->font_size = (isset($options['font_size'])) ? $options['font_size'] : '';
    $this->field_bk_color = (isset($options['field_bk_color'])) ? $options['field_bk_color'] : '';
    $this->field_color = (isset($options['field_color'])) ? $options['field_color'] : '';
    $this->form_bk_color = (isset($options['form_bk_color'])) ? $options['form_bk_color'] : '';
        
    $temp = strrchr($name, "/"); // If path found, remove path...
    if ($temp) {
      $frm_name = substr($temp, 1);
    } else {
      $frm_name = $name;
    }
    $frm_name = safe_strtolower($frm_name);
        
    $this->formname = $frm_name;
    $this->router = $router_action;
    $this->method = $method;
    $this->defaults = $defaults;
    
    $this->name_mode = (isset($defaults['name_mode'])) ? $defaults['name_mode'] : 'classic';
    
    $form_bk_color = (!empty($this->form_bk_color)) ? 'style="background-color:'.$this->form_bk_color.';"' : '';
    $disabled = (isset($defaults['read_only']) && main_fn::get_bool_value($defaults['read_only']) === true) ? ' onclick="javascript:alert(\'This control has been disabled\'); return false;"' : '';    
    $print_form = (isset($options['print_form'])) ? $options['print_form'] : true;
    if (main_fn::get_bool_value($print_form) === true) {
      $this->html = '<form name="' . $frm_name . '" onsubmit="window.onbeforeunload=null" '
            . $form_bk_color 
            . $disabled
            . ' id="' . $frm_name . '"' . (!empty($router_action) ? ' action="' . $router_action . '"' : '')
            . ' method="' . $this->method . '">' . "\r\n";
    }
  }

  

/*  
  private function alias_frm_page_links($name, $options) {
    $id = (isset($_GET['id'])) ? $_GET['id'] : 0;
    $page_id = (isset($_GET['page_id'])) ? $_GET['page_id'] : $id;
    $main_links = $this->load_class('cx\app\main_links');
    $this->set_html($main_links->get_links(array('id' => $page_id)));
  }
*/

 

  
  // The following may be used directly or better yet use $this->form(COMMAND); instead!!!
  // Begin public interfaces:
  // ***************************************************************************
  
  /**
   * Purpose: To end this form properly!!!
   */
  public function end_form() {
    $this->set_html('</form>');
  }

  public function js_file($file) {
    $this->set_js(main_fn::wrap_js($file));
  }

  public function js_inline($code) {
    $this->set_js(main_fn::inline_js($code));
  }
  
  public function js_inline_jquery($code) {
    $this->set_js(main_fn::inline_js(main_fn::jquery_load($code)));
  }
  
  public function tinymce($selector='.tinymce') {
    $this->js_file('public/tinymce/js/tinymce/jquery.tinymce.min.js');
    $this->js_inline('$.getScript("public/tinymce/js/tinymce/tinymce.min.js", function(){
tinymce.dom.Event.domLoaded = true;    
    tinyMCE.init({
      selector: "textarea'.$selector.'",
      theme: "modern",
      plugins: [
        "advlist autolink lists link image charmap print preview hr anchor pagebreak",
        "searchreplace wordcount visualblocks visualchars code fullscreen",
        "insertdatetime media nonbreaking save table contextmenu directionality",
        "emoticons template paste textcolor"
      ],
    toolbar1: "insertfile undo redo | styleselect | bold italic | alignleft aligncenter alignright alignjustify | bullist numlist outdent indent | link image",
    toolbar2: "print preview media | forecolor backcolor emoticons"
    });
});');
  }
  
  /**
   * Purpose: To set html for output later on via get_html.
   * @param type $html
   */
  public function set_html($html) {
    $this->html .= $html;
  }

  public function set_js($js) {
    $this->js .= $js;
  }
  
  /**
   * Purpose: To return the contents of this loaded/given form.
   */
  public function get_html() {
    $html = $this->html;
    $this->html = '';
    return $html;
  }

  public function get_js() {
    $js = $this->js;
    $this->js = '';
    return $js;
  }
  
  /**
   * Purpose: To return the name of the current form with an 
   * element name if present
   * @param type $name - string of element name
   * @return type string of formname
   */
  public function get_form_id($name = '', $cmd = '') {
    if (empty($name)) {
      if ($this->name_mode == 'classic') {
        return $this->formname;
      } else {
        return "input-{$cmd}";
      }
    } else {
      if ($this->name_mode == 'classic') {
        return $this->formname . '-' . $name;
      } else {
        return "input-{$cmd}-{$name}";
      }
    }
  }

  /**
   * Purpose: To grab a form and load it up for use.
   * @param type $form - the form file name to load.
   * @param type $model - the input to use with the form.
   */
  public function grab_form($form, $model = '') {
    if (is_array($model)) {
      $this->model = $model;
    } elseif (!empty($model)) {
      $this->model .= $model;
    }
    
    try {
      if (file_exists(PROJECT_BASE_DIR . 'forms' . DS . $form . '.php')) {
        require_once PROJECT_BASE_DIR . 'forms' . DS . $form . '.php';
      } else {
        throw new \Exception('Missing form');
      }
    } catch (Exception $e) {
      $_SESSION[\cx_configure::a_get('cx', 'session_variable') . 'last_error'] = 'Error loading form: ' . $e->getMessage();
      $this->broken_error();
    }
  }

  /**
   * Purpose: To load a given form command.
   * Note: All input is converted to lower case.
   * @param type $name string of id for element
   * @param type $options - optional array of parameters
   */
  public function form($command, $name = '', $options = array('')) {
    $command = safe_strtolower(trim($command));
    $cmd = 'frm_' . $this->filter_class($command);
    if (empty($command)) {
      return false;
    }
    
    /*
     * Add options via name: thing##option=>value
     */
    $more = explode("##", $name);
    if (count($more) > 0) {
      $x = 0;
      foreach($more as $more_options) {
        $x++;
        if ($x == 1) {
          continue;
        }
        $a_op = explode("=>", $more_options);
        if (isset($a_op[1])) {
          $options[$a_op[0]] = $a_op[1];
        }
      }
    }
    
    if (is_array($name)) {
      // A name should be a string. This should be a bug, so report it! 
      $err = "Form Command - {$cmd} - Missing Name!";
      $this->report_form_error($err);
      unset($options);
      $options = $name;
    } else {
      $name = trim($name);
    }
    
    if (is_array($options)) {
      $options = array_change_key_case($options, CASE_LOWER);
    } else {
      $options = safe_strtolower($options);
    }
    
    $alias_cmd = 'alias_' . $cmd;
    if (method_exists($this, $alias_cmd)) {
      if ($cmd == 'frm_get_html' || $cmd == 'frm_get_form_id') {
        return $this->$alias_cmd($name, $options);
      } else {
        $this->$alias_cmd($name, $options);
        return true;
      }
    }

    if (! method_exists($this, $cmd)) {
      $err = "Form Command - {$cmd} - Not Found!";
      $this->report_form_error($err);
      return false;
    }
    
    $this->do_start_div($cmd, $name, $options);

    $pre_before_hook = $cmd . '_pre_hook_before';
    if (method_exists($this, $pre_before_hook)) {
      $this->$pre_before_hook($name, $options);
    }

    $this->hook_before($cmd, $name, $options);

    $post_before_hook = $cmd . '_post_hook_before';
    if (method_exists($this, $post_before_hook)) {
      $this->$post_before_hook($name, $options);
    }

    $this->$cmd($name, $options);

    $pre_after_hook = $cmd . '_pre_hook_after';
    if (method_exists($this, $pre_after_hook)) {
      $this->$pre_after_hook($name, $options);
    }

    $this->hook_after($cmd, $name, $options);

    $post_after_hook = $cmd . '_post_hook_after';
    if (method_exists($this, $post_after_hook)) {
      $this->$post_after_hook($name, $options);
    }
    
    $this->do_end_div($cmd, $name, $options);
    return true;
  }

  /**
   * Purpose: Set input data for use with forms inside of views.
   * @param type $model - user input to use with this form.
   */
  public function set_model($model) {
    if (is_array($model)) {
      $this->model = $model;
    } else {
      $this->model .= $model;
    }
  }

  /**
   * Purpose: To reset user input...
   * @param type $model - user input to use with this form.
   */
  public function set_new_model($model = '') {
    $this->model = $model;
  }
  
  /**
   * Purpose: To debug form issues.
   * from a dynamic page.
   */
  public function get_model() {
    return $this->model;
  }
  
  // Begin helper methods:
  // **************************************************************************

  /**
   * Purpose: To return a default value from the user submition or database.
   * @param type $name of REQUEST variable
   * @param type $db_value of row returned from database
   * @param type $method either POST or GET or REQUEST data
   */
  private function get_request($name, $db_value = '', $method = 'POST') {
    switch (strtoupper($method)) {
      case 'GET':
        $globals = $_GET;
        break;
      case 'REQUEST':
        $globals = $_REQUEST;
        break;
      case 'POST':
      default:
        $globals = $_POST;
        break;
    }
    return (isset($globals[$name]) ? $globals[$name] : $db_value);
  }

  /**
   * Purpose: To create a div for form...
   */
  private function do_start_div($cmd, $name, $options) {
    
    if (is_array($options)) {
//      if (isset($options['div-class']) || isset($options['div-id'])) {
        $this->set_html("\r\n<div");
        if (isset($options['div-class'])) {
          $this->set_html(" class=\"form-item {$options['div-class']}\"");
        } else {
          $this->set_html(" class=\"form-item\"");
        }
        if (isset($options['div-id'])) {
          $this->set_html(" id=\"{$options['div-id']}\"");
        }
        if (isset($options['align'])) {
          $this->set_html(' align="' . $options['align'] . '" ');
        }
        $this->set_html(">");
//      }
    }
  }

  /**
   * Purpose: To create an end div for the form.
   */
  private function do_end_div($cmd, $name, $options) {
    if (is_array($options)) {
//      if (isset($options['div-class']) || isset($options['div-id'])) {
        $cmd = str_replace("frm_", "form_", $cmd);
        $this->set_html("</div><!-- end of {$cmd} : {$name} -->\r\n");
//      }
    }
  }

  /**
   * Purpose: To help html code have a readonly/asktosave check.
   * @param type $name
   * @param type $htmlOptions
   */
  private function get_readonly_and_asktosave_check($command, $name, $htmlOptions) {
    $default_ro = (isset($this->defaults['read_only'])) ? $this->defaults['read_only'] : false;
    $html_ro = (isset($htmlOptions['read_only'])) ? $htmlOptions['read_only'] : $default_ro;
    if (main_fn::get_bool_value($html_ro) === false) {
      if ($this->taborder > 0) {
        $this->set_html(' tabindex="' . $this->taborder . '" ');
        $this->taborder++;
      }
    } else {
      $this->set_html(' READONLY disabled="disabled"');
      $this->set_extra('onclick', 'javascript:alert(\'This control has been disabled\'); return false;');
    }

    if (isset($this->defaults['asktosave']) && main_fn::get_bool_value($this->defaults['asktosave']) === true) {
      $this->set_extra('onchange','askToSave();');
    }
  }

  /**
   * Purpose: Helper method to many commands, check if element is required.
   * @return array - label (*) and required (string of class required).
   */
  private function get_required($htmlOptions) {
    $ret = array();
    $ret['required'] = ''; // This will always not be set as it is now done by the validator method...
    if (isset($htmlOptions['required']) && main_fn::get_bool_value($htmlOptions['required']) === true) {
      $ret['label'] = ' *';
    } else {
      $ret['label'] = '';
    }
    return $ret;
  }

  /**
   * Purpose: To output class.
   */
  private function do_class_output($class) {
    if (! empty($class)) {
      $this->set_extra('class', $class);
    }
  }

  private function label_for($name, $id, $label, $htmlOptions) {
    if ($label != null) {
      $style = '';
      $skip = (isset($htmlOptions['skip_label_style'])) ? $htmlOptions['skip_label_style'] : false;
      if (main_fn::get_bool_value($skip) === true) {
        $font = '';
        $font_size = '';
        $font_color = '';
      } else {
        $font = (!empty($this->font)) ? 'font-family:'. $this->font . ';' : '';
        $font_size = (!empty($this->font_size)) ? 'font-size:'. $this->font . ';' : '';
        $font_color = (!empty($this->field_color)) ? 'color:'. $this->field_bk_color . ';' : '';
      }
      if (!empty($this->font) || !empty($this->field_bk_color) || !empty($this->field_color) || !empty($this->font_size)) {    
        $style .= 'style="';
        $style .= $font;
        $style .= $font_size;
        $style .= $font_color;
        $style .= (isset($htmlOptions['label_style'])) ? $htmlOptions['label_style'] : '';
        $style .= '"';
      }
//    class=txt-label  
      $style .= (isset($htmlOptions['skip_label_class']) && main_fn::get_bool_value($htmlOptions['skip_label_class']) === true) ? ' class="' : ' class="txt-label ';
      $style .= (isset($htmlOptions['label_class'])) ? $htmlOptions['label_class'] : '';
      $style .= '"';
      
      $this->set_html('<label for="' . $id . '" ' . $style . '> ' . $label . '</label> &nbsp;');
      $end = (isset($htmlOptions['end_of_label_class'])) ? $htmlOptions['end_of_label_class'] : '';
      if (! empty($end)) {
        $this->do_start_div('label', $name, array('div-class'=>$end));
        $this->do_end_div('label', $name, array('div-class'=>$end));
      }
    }
  }

  /**
   * Purpose: Helper method for validator - to validate text fields.
   */
  private function validator($command, $name, $options='') {
    // Add required class if required was set.
    if (isset($options['required']) && main_fn::get_bool_value($options['required']) === true) {
      $class = 'required';
    } else {
      $class = '';
    }
   
    //If nothing to do, exit.
    if (! is_array($options)) {
      $this->do_class_output($class);
      return false;
    }
    
    // If no validation type, exit;
    if (! isset($options['validator'][0])) {
      $this->do_class_output($class);
      return false;
    }
    
    // Only validate text, password, and textarea commands.
    if (! ( $command == 'frm_text' || $command == 'frm_password' || $command == 'frm_textarea') ) {
      $this->do_class_output($class);
      return false;
    }
    
    $type_of_validator = safe_strtolower($options['validator'][0]);
    $class .= $type_of_validator . ' ';
        
    // Show error if type of validator is not found.
    if (is_array($type_of_validator)) {
      $err = "Form Command - {$command} - Named: {$name} - Missing Validator!";
      $this->report_form_error($err);
      return false;
    }
    
    // Show error if a valid validator is not found.
    if (! in_array($type_of_validator, $this->get_valid_validators() )) {
      $err = "Form Command - {$command} - Named: {$name} - validator {$type_of_validator} Not Found!";
      $this->report_form_error($err);
      return false;
    }
    
    // If validator has an array, do validator helper on that array only.
    if (isset($options['validator'][1]) && is_array($options['validator'][1])) {
      $val_options = $options['validator'][1];
      $class .= $this->validator_helper($command, $name, $val_options); 
    }
    
    $this->do_class_output($class); // Fin
    return true;
  }

  private function validator_helper($command, $name, $val_options) {
    $ret = '';
    $validator_escape_char = "_";
    
    if (isset($val_options['maxlength'])) {
      $ret .= "maxlength{$validator_escape_char}{$val_options['maxlength']} ";
    }

    if (isset($val_options['minlength'])) {
      $ret .= "minlength{$validator_escape_char}{$val_options['minlength']} ";
    }

    if (isset($val_options['range']) && is_array($val_options['range'])) {
      $range = $val_options['range'];
      $low = (isset($range['low'])) ? $range['low'] : '0';
      $high = (isset($range['high'])) ? $range['high'] : '255';
      $ret .= "range-low{$validator_escape_char}{$low} ";
      $ret .= "range-high{$validator_escape_char}{$high} ";
    }

    return $ret;
  }
  
  /**
   * Purpose: Helper method for validator.
   * @return array of valid validators
   */
  public function get_valid_validators() {
    return array('alpha','num','alphanum','text','email','phone','zip','mdydate');
  }

    /**
   * Purpose: To always perform work before a given form command is done.
   */
  private function hook_before($command, $name, $options) {
//    $id = $this->get_form_id($name, $command);
    if (defined('LIVE') && (LIVE == false)) {
      $this->set_html("\r\n<!-- The following was generated by form: {$this->formname} -->\r\n");
    }
  }
  
  /**
   * Purpose: To always perform work after a given form command is done.
   */
  private function hook_after($command, $name, $options) {
    $cmd = str_replace('frm_', '', $command);
    switch($cmd) {
      case 'checkboxes':
      case 'radios':
        // Do nothing!!
        break;
      case 'textarea':
        $this->get_readonly_and_asktosave_check($command, $name, $options);
        $this->validator($command, $name, $options);
        $this->set_html(get_default_options($command, $name, $options, $this->extra));
        break;
      case 'text':
      case 'password':
        $this->get_readonly_and_asktosave_check($command, $name, $options);
        $this->validator($command, $name, $options);
        $this->set_html(get_default_options($command, $name, $options, $this->extra));
        break;
      case 'submit':
        $this->get_readonly_and_asktosave_check($command, $name, $options);
        $this->set_extra('class','form-submit');
        $this->validator($command, $name, $options);
        $this->set_html(get_default_options($command, $name, $options, $this->extra));
        break;
      case 'button':
        $this->get_readonly_and_asktosave_check($command, $name, $options);
        $this->set_extra('class','form-submit');
        $this->validator($command, $name, $options);
        $this->set_html(get_default_options($command, $name, $options, $this->extra));
        break;
      case 'select':
        $this->get_readonly_and_asktosave_check($command, $name, $options);
        $this->validator($command, $name, $options);
        $this->set_html(get_default_options($command, $name, $options, $this->extra));
        break;
      default:
        $this->validator($command, $name, $options);
        $this->set_html(get_default_options($command, $name, $options, $this->extra));
        break;
    }
                    
    switch($cmd) {
      case 'text':
      case 'password':
//        $this->get_auto_size($name, $options);
        if (isset($options['value']))
          $this->set_html(' value="' . $options['value'] . "\">\r\n");
        else {
          $dbval = (isset($this->model[$name])) ? $this->model[$name] : '';
          $this->set_html(' value="' . $this->get_request($name, $dbval, $this->method) . "\">\r\n");
        }
        break;
      case 'textarea':
        if (isset($options['value']))
          $this->set_html('>' . $options['value']);
        else {
          $dbval = (isset($this->model[$name])) ? $this->model[$name] : '';
          $this->set_html('>' . $this->get_request($name, $dbval, $this->method));
        }
        break;
      case 'hyper_link':
        $this->set_html(">"); // no new line breaks here!!
        break;
      case 'image':
        $this->set_html("\>\r\n");
        break;
      case 'submit':
        if (isset($options['value'])) {
          $this->set_html(' value="' . $options['value'] . '"');
        }
        $this->set_html("/>\r\n");
        break;
      case 'button':
        if (isset($options['value'])) {
          $this->set_html('>' . $options['value']);
        } else {
          $this->set_html('>');
        }
        break;
      case 'checkboxes':
      case 'radios':
        // Do nothing!
        break;
      case 'select':
        $this->set_html(">\r\n");
        break;
      
      default:
        $this->set_html(">\r\n");
        break;
    }
    unset($this->extra);
    $this->extra = '';
  }
  
  /**
   * Purpose: To report errors to Chrome console...
   * @param type $err
   */
  private function report_form_error($err) {
      $this->set_html("<!-- {$err} -->");
      $this->set_html(main_fn::inline_js("log('{$err}');"));
  }

  /**
   * Purpose: To make Checkboxes and Radios work.
   */
  private function input($type, $name, $value, $label, $additional_attr, $htmlOptions) {
    $id = $this->get_form_id($name, $type);
    $id .= '-' . $value;
    $req = $this->get_required($htmlOptions);
    
    $label = ($label === '') ? ucwords(str_replace('_', ' ', $value)) : $label;
    $this->set_html('<input type="' . $type . '" name="' . $name . '" value="' . $value . '" id="' . $id . '"' . $req['required']);

    $this->get_readonly_and_asktosave_check('input', $name, $htmlOptions);    
    $this->set_html(get_default_options('input', $name, $htmlOptions, $this->extra));
    $more = (!empty($additional_attr)) ? $additional_attr . ' />' : ' />';
    $this->set_html($more);
    $this->label_for($name, $id, $label . $req['label'], $htmlOptions);
  }

  private function set_extra($property, $value) {
    $this->extra[$property] = $value;
  }
  
} //end of Form class
