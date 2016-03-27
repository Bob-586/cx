<?php

/**
 * @copyright (c) 2015
 * @author Chris Allen, Robert Strutts
 */
namespace cx\form;

use cx\app\main_functions as main_fn;

trait aliases {
  
  // All alias_frm_ and frm_ methods are supposed to be private and not called outside of here!
  // Begaining of user "frm_" defined methods that will be loaded with form command:
  // $frm->form(REQUIRED COMMAND, Name of Element, Array of Options);
  // Here is an example of useage for an TextArea from with in a form file:
  // $this->form('textarea','user-name', array('label'=>'User Name:'));
  // *********************************************************************************************
  
  
   /**
   * Purpose: To end this form properly.
   */
  private function alias_frm_end_form($name, $options) {
    $this->end_form();
  }
  
  private function alias_frm_js_file($name, $options) {
    $file = (isset($options['file'])) ? $options['file'] : $name;
    $this->js_file($file);
  } 

  private function alias_frm_js_inline($name, $options) {
    $code = (isset($options['code'])) ? $options['code'] : $name;
    $this->js_inline($code);
  }
  
  private function alias_frm_js_inline_jquery($name, $options) {
    $code = (isset($options['code'])) ? $options['code'] : $name;
    $this->js_inline_jquery($code);
  }

  private function alias_frm_tinymce($name, $options) {
    $selector = '.tinymce';
    $this->tinymce($selector);
  }
 
  private function alias_frm_set_html($name, $options) {
    $html = (isset($options['html'])) ? $options['html'] : $name;
    $this->set_html($html);
  }
  
  private function alias_frm_set_php($name, $options) {
    $php = (isset($options['php'])) ? $options['php'] : $name;
    $this->set_html($this->do_eval_view($php, false));
  }
 
  /*
  private function alias_frm_do_eval($name, $options) {
    $packed = (isset($options['packed'])) ? $options['packed'] : false;
    if (isset($this->model['php']) && ! empty($this->model['php'])) {
      $this->set_html($this->do_eval_view($this->model['php'], $packed));
    }
  } 
   */

  private function alias_frm_do_html($name, $options) {
    if (isset($this->model['html']) && ! empty($this->model['html'])) {
      $this->set_html($this->model['html']);
    }
  }

  /**
   * Purpose: The only way to get the form data!!!
   */
  private function alias_frm_get_html($name, $options) {
    return $this->get_html();
  }
  
  /**
   * Purpose: To see what your form is named, useful for JavaScript/CSS.
   * @param $name - name of your element
   */
  private function alias_frm_get_form_id($name, $option) {
    return $this->get_form_id($name);
  }

  /**
   * Purpose: To open a form for later use with a view.
   * @param type $form - The form from protected/forms
   * @param type $model - The database model
   */
  private function alias_frm_grab_form($form, $model) {
    $this->grab_form($form, $model);
  }

  /**
   * Purpose: To display a hyperlink.
   * @param type $name - not used at present time.
   * @param type $options [href] or [link] and [label] or name
   */
  private function alias_frm_link($name, $options) {
    $this->form('hyper_link', $name, $options);
  }

  /**
   * Purpose: To create a DIV.
   * @param type $options [div-class], [div-id], or [align]
   */
  private function alias_frm_start_div($name, $options) {
    $this->do_start_div('start_div', $name, $options);
  }

  /**
   * Purpose: To end a given DIV created by start_div.
   */
  private function alias_frm_end_div($name, $options) {
    $this->set_html("</div>");
    if (is_array($options)) {
      if (isset($options['div-class']) || isset($options['div-id'])) {
        if (isset($options['div-class'])) {
          $this->set_html("<!-- end of div class=\"{$options['div-class']}\" -->");
        }
        if (isset($options['div-id'])) {
          $this->set_html("<!-- end of div id=\"{$options['div-id']}\" -->");
        }
      }
    }
    $this->set_html("\r\n");
  }

  /**
   * Purpose: To set a div for a new row.
   */
  private function alias_frm_start_row($name, $options) {
    $this->set_html("<div class=\"row\"><!-- start of Row {$name} -->\r\n");
  }
  
  /**
   * Purpose: To set a div for the end of a row.
   */
  private function alias_frm_end_row($name, $options) {
    $this->set_html("</div><!-- end of Row {$name} -->\r\n");
  }

  /**
   * Purpose: To quickly clear formatting...
   */
  private function alias_frm_div_clear($name, $options) {
    $this->set_html("<div class=\"clear\"></div>\r\n");
  }

 /**
 * Purpsoe: To set a label for an element...
 * @param type $name - the label
 */
  private function alias_frm_label($name, $options) {
    $this->set_html("<div class=\"txt-label\">{$name}</div>\r\n");
  }

  /**
   * Purpose: To group related elements in a form with a box.
   * @param type $name - to specify a form name.
   * @param type $options [legend] or [label] - to set the label for the legend.
   * @param type $options [form] - Specifies one or more forms the fieldset belongs to.
   */
  private function alias_frm_start_fieldset($name, $options) {
    $legend = (isset($options['legend'])) ? $options['legend'] : $name;
    $legend = (isset($options['label'])) ? $options['label'] : $legend;
    $fieldset_options = '';
    if (isset($options['disabled']) && main_fn::get_bool_value($options['disabled']) === true) {
      $fieldset_options .= " disabled";
    }
    
    $fieldset_options .= (isset($options['class'])) ? " class=\"{$options['class']}\"" : '';
    $fieldset_options .= (isset($options['form'])) ? " form=\"{$options['form']}\"" : '';
    $fieldset_options .= (! empty($name)) ? " name=\"{$name}\"" : '';
    $this->set_html("<fieldset{$fieldset_options}>");
    $this->set_html("<legend>{$legend}</legend>\r\n");
  }

  /**
   * Purpose: To end a given fieldset.
   * @param type [$name] - to remark about the end of fieldset name.
   */
  private function alias_frm_end_fieldset($name, $options) {
    $this->set_html("</fieldset>");
    if (! empty($name)) {
      $this->set_html("<!-- end of fieldset {$name} -->");
    }
    $this->set_html("\r\n");
  }

  private function alias_frm_tbody($name, $options) {
    $this->set_html("<tbody>");
  }
  
  private function alias_frm_end_tbody($name, $options) {
    $this->set_html("</tbody>");
  }
  
  /**
   * Purpose: To end a given table.
   * @param type [$name] - to remark about the end of table named X.
   */
  private function alias_frm_end_table($name, $options) {
    $this->set_html("</table>");
    if (! empty($name)) {
      $this->set_html("<!-- end of table {$name} -->");
    }
    $this->set_html("\r\n");
  }  
  
  private function alias_frm_hidden_field($name, $options) {
    $value = (isset($options['value'])) ? $options['value'] : '';
    $this->set_html("<input type=\"hidden\" name=\"{$name}\" value=\"{$value}\" />");
    $this->set_html("\r\n");
  }  
  
}