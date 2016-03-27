<?php

/**
 * @copyright (c) 2015
 * @author Chris Allen, Robert Strutts
 */

namespace cx\form;

use cx\app\main_functions as main_fn;

trait commands {

  /**
   * Purpose: To create a table.
   * @param type $name - id of table
   * @param type $htmlOptions [dynamic] [border]
   */
  private function frm_table($name, $htmlOptions) {
    $border = (! isset($htmlOptions['border'])) ? 'border="0" ' : '';
    $name = (! isset($name) || empty($name)) ? 'dataTable' : $name;
    $id = $this->get_form_id($name, 'table');
    $this->set_html("<table name=\"{$name}\" id=\"{$id}\" " . $border);
  }

  private function frm_table_post_hook_after($name, $htmlOptions) {
    if (isset($htmlOptions['dynamic']) && main_fn::get_bool_value($htmlOptions['dynamic']) == true) {
      $this->set_html("<tbody>\r\n</tbody>");  
      $this->set_html("</table>");  
    }
  }

  /**
   * Purpose: To create an hyper-link.
   * @param type $name 
   * @param type $htmlOptions [href] or [link] and [label] or name
   */
  private function frm_hyper_link($name, $htmlOptions) {
    $link = (isset($htmlOptions['href'])) ? $htmlOptions['href'] : 'javascript:void();';
    $link = (isset($htmlOptions['link'])) ? $htmlOptions['link'] : $link;
    $this->set_html("<a href=\"{$link}\"");
  }

  private function frm_hyper_link_post_hook_after($name, $htmlOptions) {
    $label = (isset($htmlOptions['label'])) ? $htmlOptions['label'] : $name;
    $label = (isset($htmlOptions['name'])) ? $htmlOptions['name'] : $label;

    $this->set_html($label);
    $this->set_html('</a>');
  }

  /**
   * Purpose: To create a text field.
   * @param type $name - to define the name/id of the text field.
   * @param type $htmlOptions [required] - to mark a required field.
   * @param type $htmlOptions [label] - to display a label.
   */
  private function frm_text($name, $htmlOptions) {
    $id = $this->get_form_id($name, 'text');
    $req = $this->get_required($htmlOptions);
    
    if (!isset($htmlOptions['label'])) {
      $label = '';
    } else {
      $label = ($htmlOptions['label'] === '') ? ucwords(str_replace('_', ' ', $name)) . $req['label'] : $htmlOptions['label'] . $req['label'];
    }
    $this->label_for($name, $id, $label, $htmlOptions);
    $auto = (isset($htmlOptions['no_auto_complete']) && main_fn::get_bool_value($htmlOptions['no_auto_complete']) === true) ? ' AUTOCOMPLETE="off" ' : '';
    
    if (isset($htmlOptions['div-inner'])) {
      $this->set_html("<div class=\"{$htmlOptions['div-inner']}\"><!-- start of div inner -->");
    }

    $this->set_html('<input type="text" name="' . $name . '" id="' . $id . '"' . $auto . $req['required']);
  }
  
  private function frm_text_post_hook_after($name, $htmlOptions) {
    if (isset($htmlOptions['div-inner'])) {
      $this->set_html("</div><!-- end of div inner -->");
    }
  }

  /**
   * Purpose: To create a password field.
   * @param type $name - to define the name/id of the text field.
   * @param type $htmlOptions [required] - to mark a required field.
   * @param type $htmlOptions [label] - to display a label.
   */
  private function frm_password($name, $htmlOptions) {
    $id = $this->get_form_id($name, 'password');
    $req = $this->get_required($htmlOptions);
    
    if (!isset($htmlOptions['label'])) {
      $label = '';
    } else {
      $label = ($htmlOptions['label'] === '') ? ucwords(str_replace('_', ' ', $name)) . $req['label'] : $htmlOptions['label'] . $req['label'];
    }
    $this->label_for($name, $id, $label, $htmlOptions);
    
    if (isset($htmlOptions['div-inner'])) {
      $this->set_html("<div class=\"{$htmlOptions['div-inner']}\"><!-- start of div inner -->");
    }
    
    $this->set_html('<input type="password" name="' . $name . '" id="' . $id . '" ' . $req['required']);
  }

  private function frm_password_post_hook_after($name, $htmlOptions) {
    if (isset($htmlOptions['div-inner'])) {
      $this->set_html("</div><!-- end of div inner -->");
    }
  }
  
  /**
   * Purpose: To display an image.
   * @param type $name
   * @param type $htmlOptions [border] - specify border width.
   * @param type $htmlOptions [src] - source of image file.
   */
  private function frm_image($name, $htmlOptions) {
    $id = $this->get_form_id($name, 'submit');
    $border = (! isset($htmlOptions['border'])) ? ' border="0"' : '';
    $this->set_html("<img{$border} id=\"{$id}\"");
  }

  /**
   * Purpose: To create a TextArea field.
   * @param type $name - to define the name/id of the text field.
   * @param type $htmlOptions [required] - to mark a required field.
   * @param type $htmlOptions [label] - to display a label.
   */
  private function frm_textarea($name, $htmlOptions) {
    $id = $this->get_form_id($name, 'textarea');
    $req = $this->get_required($htmlOptions);
    
    if (!isset($htmlOptions['label'])) {
      $label = '';
    } else {
      $label = ($htmlOptions['label'] === '') ? ucwords(str_replace('_', ' ', $name)) . $req['label'] : $htmlOptions['label'] . $req['label'];
    }
    $this->label_for($name, $id, $label, $htmlOptions);
    
    if (isset($htmlOptions['div-inner'])) {
      $this->set_html("<div class=\"{$htmlOptions['div-inner']}\"><!-- start of div inner -->");
    }
    
    $this->set_html('<textarea name="' . $name . '" id="' . $id . '"' . $req['required']);
  }
  
  private function frm_textarea_post_hook_after($name, $htmlOptions) {
    $this->set_html("</textarea>\r\n");
    
    if (isset($htmlOptions['div-inner'])) {
      $this->set_html("</div><!-- end of div inner -->");
    }
  }
  
  /**
   * Purpose: To create a submit button.
   * @param type $name - name of submit button, do not use submit!
   */
  private function frm_submit($name, $htmlOptions) {
    $name = ($name == 'submit') ? 'ajax' : $name;
    $id = $this->get_form_id($name, 'submit');
    $this->set_html('<input type="submit" name="' . $name . '" id="' . $id . '"');
  }
  
  /**
   * Purpose: To create a button.
   * @param type $name - name of button, do not use submit! 
   */
  private function frm_button($name, $htmlOptions) {
    $name = ($name == 'submit') ? 'go' : $name;
    $id = $this->get_form_id($name, 'button');
    $this->set_html('<button type="button" name="' . $name . '" id="' . $id . '"');
  }

  private function frm_button_post_hook_after($name, $htmlOptions) {
     $this->set_html("</button>\r\n");
  }
  
  /**
   * Purpose: To create a selection box.
   * @param type $name - name of element.
   * @param type $htmlOptions [required] - if required...
   * @param type $htmlOptions [label] - what to show for label.
   * @param type $htmlOptions [default] - Default value if none defined
   * @param type $htmlOptions <options> - required field - selection choices.
   */
  private function frm_select($name, $htmlOptions) {
    $id = $this->get_form_id($name, 'select');
    $req = $this->get_required($htmlOptions);
    
    if (!isset($htmlOptions['label'])) {
      $label = '';
    } else {
      $label = ($htmlOptions['label'] === '') ? ucwords(str_replace('_', ' ', $name)) . $req['label'] : $htmlOptions['label'] . $req['label'];
    }
    $this->label_for($name, $id, $label, $htmlOptions);
    
    if (isset($htmlOptions['div-inner'])) {
      $this->set_html("<div class=\"{$htmlOptions['div-inner']}\"><!-- start of div inner -->");
    }
    
    $this->set_html('<select name="' . $name . '" id="' . $id . '"' . $req['required']);
  }

  private function frm_select_post_hook_after($name, $htmlOptions) {
    $options = (isset($htmlOptions['options'])) ? $htmlOptions['options'] : array();
    $selected = (isset($this->model[$name]) && !empty($this->model[$name])) ? $this->model[$name] : 'unsure';
    $this->set_html('<option value="0">Select</option>');
    $counter = 0;
    foreach ($options as $value => $option) {
      $counter++;
      $select = ($value == $this->get_request($name, $selected, $this->method)) ? ' selected="selected"' : '';
      $this->set_html('<option value="' . $value . '"' . $select . '>' . $option . '</option>');
    }
    
    if ($counter == '0') {
      $err = "Form Command - select - Named: {$name} - Missing Options!";
      $this->report_form_error($err);
    }
    
    $this->set_html("</select>\r\n");
    
    if (isset($htmlOptions['div-inner'])) {
      $this->set_html("</div><!-- end of div inner -->");
    }
  }

  /**
   * Purpose: To generate a group of checkboxes.
   * @param type $name - The name for the group of checkboxes.
   * @param $htmlOptions - <options> - Array of checkbox data.
   */
  private function frm_checkboxes($name, $htmlOptions) {
    $checkboxes = (isset($htmlOptions['options'])) ? $htmlOptions['options'] : array();
    $c = count($checkboxes);
    $input_name = ($c > 1) ? $name . '[]' : $name;

    foreach ($checkboxes as $value => $label) {
      $selected = array('');
      if (isset($this->model[$name])) {
        if (is_array($this->model[$name])) {
          $selected = $this->model[$name];
        } elseif (main_fn::is_serialized($this->model[$name]) === true) {
          $selected = main_fn::safe_unserialize($this->model[$name]);
        } elseif (!empty($this->model[$name])) {
          $selected = array($this->model[$name]);
        }
      }
      
      if ($c == 1) {
        $checkit = (isset($htmlOptions['checked']) && main_fn::get_bool_value($htmlOptions['checked']) === true) ? ' checked="checked"' : '';
      } else {
        $checkit = (isset($htmlOptions['checked'][$value]) && main_fn::get_bool_value($htmlOptions['checked'][$value]) === true) ? ' checked="checked"' : '';
      }
      
      $req = $this->get_request($name, $selected, $this->method);
      if (is_array($req)) {
        $select = (in_array($value, $req)) ? ' checked="checked"' : $checkit;
      } else {
        $select = ($value == $req) ? ' checked="checked"' : $checkit;    
      }     
      
      $this->input('checkbox', $input_name, $value, $label, $select, $htmlOptions);
    } // end foreach
    if ($c == '0') {
      $err = "Form Command - checkboxes - Named: {$name} - Missing Options!";
      $this->report_form_error($err);
    }

  }

  /**
   * Purpose: To generate a group of radio buttons.
   * @param $name - The name of the group of radio buttons.
   * @param $htmlOptions - <options> - Array of radio buttons.
   */
  private function frm_radios($name, $htmlOptions) {
    $radios = (isset($htmlOptions['options'])) ? $htmlOptions['options'] : array();
    $counter = 0;
    foreach ($radios as $value => $label) {
      $counter++;
      $selected = (isset($this->model[$name]) ? $this->model[$name] : '');
      $select = ($value == $this->get_request($name, $selected, $this->method)) ? ' checked="checked"' : '';
      $this->input('radio', $name, $value, $label, $select, $htmlOptions);
    }
    if ($counter == '0') {
      $err = "Form Command - radios - Named: {$name} - Missing Options!";
      $this->report_form_error($err);
    }
  }
  
}