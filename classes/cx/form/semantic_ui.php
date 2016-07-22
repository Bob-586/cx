<?php

namespace cx\form;
use cx\app\main_functions as main_fn;

trait semantic_ui {

  public function ui_country($selected = '', $countries = 'default', $field_name = "country") {
    if ( ! is_array($countries) ) {
      $countries = main_fn::countries_array();
    }
  
    $ret = "\r\n".'
  <div class="ui fluid search selection dropdown">
    <input type="hidden" name="' . $field_name . '" value="'.$selected.'">
    <i class="dropdown icon"></i>
    <div class="default text">Select Country</div>
    <div class="menu">';

  foreach($countries as $country_code => $country_name) {
    $ret .= '<div class="item" data-value="'.strtoupper($country_code).'"><i class="'.strtolower($country_code).' flag"></i>' . $country_name . '</div>';
  }

  $ret .= "\r\n".'
    </div>
  </div>
    ';
  
  return $ret;
  }

}