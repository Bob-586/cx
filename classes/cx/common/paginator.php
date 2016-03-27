<?php
/**
 * Downloaded from:
 * @link http://net.tutsplus.com/tutorials/php/how-to-paginate-data-with-php/
 */

namespace cx\common;

class paginator extends \cx\app\app {
    public $js_call = 'ajp';
    public $items_per_page;
    public $items_total = 0;
    public $current_page;
    public $num_pages;
    public $mid_range;
    public $low = 0;
    public $high;
    public $limit;
    public $return; // classic paginator css 
    public $bootstap = '<ul class="pagination pull-right">';
    public $ajax_return = '<ul class="pagination pull-right">'; // bootstrap
    public $default_ipp = 24;
    public $mobile_ipp = 12;
    public $max_ipp = 1000;
    public $show_all = true;
    public $show_page_num_title = false; // title for href
    private $ipp;
    public $page;
    public $server_url;
    private $url_vars;
    
    public function __construct() {
      parent::__construct(); // Need to call the parent so data is correct
        $skip = array('ipp','page','PHPSESSID','go','load_data');
        if ($this->short_url === true) {
          $skip[] = 'route';
          $skip[] = 'm';
        }
        $this->url_vars = "go=1" . $this->get_globals($skip);
        $this->ipp = (isset($_REQUEST['ipp'])) ? $_REQUEST['ipp'] : '';
        $this->page = (isset($_REQUEST['page'])) ? $_REQUEST['page'] : '';
        $this->current_page = 1;
        $this->mid_range = 7;
        
        if (!empty($_SERVER['REQUEST_URI'])) {
        
          $pos = strpos($_SERVER['REQUEST_URI'], "?");
          if ($pos === false) { // note: three equal signs
              // ? not found...
              $this->server_url = 'http://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'] . "?";
          } else {
              $this->server_url = 'http://' . $_SERVER['HTTP_HOST'] . substr($_SERVER['REQUEST_URI'], 0, $pos) . "?";
          }
        } else {
          /**
           * @todo make ipp and page arg checking
           */
           $this->server_url = '';
        }
    }

    public function paginate() {
        if (is_mobile() === true) {
          $this->items_per_page = (!empty($this->ipp)) ? $this->ipp : $this->mobile_ipp;
        } else {
          $this->items_per_page = (!empty($this->ipp)) ? $this->ipp : $this->default_ipp;
        }

        if (!is_numeric($this->items_per_page) || $this->items_per_page <= 0) {
          $this->items_per_page = $this->default_ipp;
        } elseif ($this->items_per_page > $this->max_ipp) {
          $this->items_per_page = $this->max_ipp;
        }
        
        $this->high = $this->items_total - 1;
        
        if ($this->ipp == 'All' && $this->items_total < $this->max_ipp) {
          $this->num_pages = 1;
          $this->limit = "";
          return;
        } else {
          $this->num_pages = ($this->items_total>0) ? ceil($this->items_total / $this->items_per_page) : 1;
        }
        
        if ($this->num_pages < 2) {
          $this->num_pages = 1;
          $this->limit = "";
          return;
        }
        
        $this->current_page = (int) $this->page; // must be numeric > 0
        if($this->current_page < 1 || !is_numeric($this->current_page)) $this->current_page = 1;
        if($this->current_page > $this->num_pages) $this->current_page = $this->num_pages;
        $prev_page = $this->current_page-1;
        $next_page = $this->current_page+1;

        $this->show_prev($prev_page);
        
        if($this->num_pages > 10) {
          
            $this->start_range = $this->current_page - floor($this->mid_range / 2);
            $this->end_range = $this->current_page + floor($this->mid_range / 2);

            if($this->start_range <= 0) {
                $this->end_range += abs($this->start_range)+1;
                $this->start_range = 1;
            }
            if($this->end_range > $this->num_pages) {
                $this->start_range -= $this->end_range - $this->num_pages;
                $this->end_range = $this->num_pages;
            }
            $this->range = range($this->start_range, $this->end_range);

            for($i=1; $i<=$this->num_pages; $i++) {
                if($this->range[0] > 2 && $i == $this->range[0]) {
                  $this->return .= " ... ";
                  $this->bootstap .= "<li class=\"disabled\"><a href=\"javascript:;\"> ... </a></li>";
                  $this->ajax_return .= "<li class=\"disabled\"><a href=\"javascript:;\"> ... </a></li>";
                }
                // loop through all pages. if first, last, or in range, display
                if($i==1 Or $i==$this->num_pages || in_array($i,$this->range)) {
                    $this->return .= ($i == $this->current_page) ? "<a href=\"javascript:;\">{$i}</a> ":"<a class=\"paginate\" " . $this->show_goto_page($i) . " href=\"{$this->server_url}{$this->url_vars}&page={$i}&ipp={$this->items_per_page}\">{$i}</a> ";
                    $this->bootstap .= ($i == $this->current_page) ? "<li class=\"active\"><a href=\"javascript:;\">{$i} </a></li> ":"<li><a " . $this->show_goto_page($i) . " href=\"{$this->server_url}{$this->url_vars}&page={$i}&ipp={$this->items_per_page}\">{$i}</a></li> ";
                    $this->ajax_return .= ($i == $this->current_page) ? "<li class=\"active\"><a href=\"javascript:;\">{$i} </a></li> ":"<li><a " . $this->show_goto_page($i) . " href=\"javascript:{$this->js_call}('{$i}','{$this->items_per_page}');\">{$i}</a></li> ";
                }
                if($this->range[$this->mid_range - 1] < $this->num_pages - 1 && $i == $this->range[$this->mid_range-1]) {
                  $this->return .= " ... ";
                  $this->bootstap .= "<li class=\"disabled\"><a href=\"javascript:;\"> ... </a></li>";
                  $this->ajax_return .= "<li class=\"disabled\"><a href=\"javascript:;\"> ... </a></li>";
                }
            }
            
            $this->show_next($next_page); 
            $this->show_all();
        } else {
            for($i=1; $i<=$this->num_pages; $i++) {
                $this->return .= ($i == $this->current_page) ? "<a class=\"current\" href=\"javascript:;\">{$i}</a> ":"<a class=\"paginate\" " . $this->show_goto_page($i) . " href=\"{$this->server_url}{$this->url_vars}&page={$i}&ipp={$this->items_per_page}\">{$i}</a> ";
                $this->bootstap .= ($i == $this->current_page) ? "<li class=\"active\"><a href=\"javascript:;\">{$i}</a></li> ":"<li><a " . $this->show_goto_page($i) . " href=\"{$this->server_url}{$this->url_vars}&page={$i}&ipp={$this->items_per_page}\">{$i}</a></li> ";
                $this->ajax_return .= ($i == $this->current_page) ? "<li class=\"active\"><a href=\"javascript:;\">{$i}</a></li> ":"<li><a " . $this->show_goto_page($i) . " href=\"javascript:{$this->js_call}('{$i}','{$this->items_per_page}');\">{$i}</a></li> ";
            }
            
            $this->show_next($next_page);
            $this->show_all();
        }
        $this->bootstap .= "</ul> \r\n <div class=\"clearfix\"></div>";
        $this->ajax_return .= "</ul> \r\n <div class=\"clearfix\"></div>";
        
        $this->low = ($this->current_page - 1) * $this->items_per_page;
        $this->high = ($this->current_page != $this->num_pages) ? ($this->current_page * $this->items_per_page) - 1 : $this->items_total - 1;
        
        $db_safe_low = intval($this->low);
        $db_safe_items_per_page = intval($this->items_per_page);
        
        $this->limit = " LIMIT {$db_safe_items_per_page} OFFSET {$db_safe_low}";
    }

    public function get_entries() {
      return "Showing " . ($this->low + 1) . " to " . ($this->high + 1) . " of {$this->items_total} entries";
    }
    
    private function show_goto_page($i) {
      return ($this->show_page_num_title === true) ? "title=\"Go to page {$i} of {$this->num_pages}\"" : "";
    }

    private function show_prev($prev_page) {
      $this->return = ($this->current_page != 1 && $this->items_total >= 10) ? "<a class=\"paginate\" href=\"{$this->server_url}{$this->url_vars}&page={$prev_page}&ipp={$this->items_per_page}\">&laquo; Previous</a> ":"<span class=\"inactive\"> <a href=\"javascript:;\">&laquo; Previous</a></span> ";
      $this->bootstap .= ($this->current_page != 1 && $this->items_total >= 10) ? "<li><a href=\"{$this->server_url}{$this->url_vars}&page={$prev_page}&ipp={$this->items_per_page}\">&laquo; Previous</a></li> ":"<li class=\"disabled\"><a href=\"javascript:;\">&laquo; Previous</a></li> ";
      $this->ajax_return .= ($this->current_page != 1 && $this->items_total >= 10) ? "<li><a href=\"javascript:{$this->js_call}('{$prev_page}','{$this->items_per_page}');\">&laquo; Previous</a></li> ":"<li class=\"disabled\"> <a href=\"javascript:;\">&laquo; Previous</a></li> ";
    }

    private function show_next($next_page) {
      $this->return .= (($this->current_page != $this->num_pages && $this->items_total >= 10) && ($this->page != 'All')) ? "<a class=\"paginate\" href=\"{$this->server_url}{$this->url_vars}&page={$next_page}&ipp={$this->items_per_page}\">Next &raquo;</a>\n":"<span class=\"inactive\"> <a href=\"javascript:;\">Next &raquo;</a></span>\n";
      $this->bootstap .= (($this->current_page != $this->num_pages && $this->items_total >= 10) && ($this->page != 'All')) ? "<li><a href=\"{$this->server_url}{$this->url_vars}&page={$next_page}&ipp={$this->items_per_page}\">Next &raquo;</a></li>\n":"<li class=\"disabled\"> <a href=\"javascript:;\">Next &raquo;</a></li>\n";
      $this->ajax_return .= (($this->current_page != $this->num_pages && $this->items_total >= 10) && ($this->page != 'All')) ? "<li><a href=\"javascript:{$this->js_call}('{$next_page}','{$this->items_per_page}');\">Next &raquo;</a></li>\n":"<li class=\"disabled\"> <a href=\"javascript:;\">Next &raquo;</a></li>\n";  
    }
    
    private function show_all() {
      if ($this->show_all === true && $this->items_total < $this->max_ipp) {
        $this->return .= ($this->page == 'All') ? "<a class=\"current\" style=\"margin-left:10px\" href=\"javascript:;\">All</a> \n":"<a class=\"paginate\" style=\"margin-left:10px\" href=\"{$this->server_url}{$this->url_vars}&page=1&ipp=All\">All</a> \n";
        $this->bootstap .= ($this->page == 'All') ? "<li class=\"active\"><a style=\"margin-left:10px\" href=\"javascript:;\">All</a></li> \n":"<li><a style=\"margin-left:10px\" href=\"{$this->server_url}{$this->url_vars}&page=1&ipp=All\">All</a></li> \n";
        $this->ajax_return .= ($this->page == 'All') ? "<li class=\"active\"><a style=\"margin-left:10px\" href=\"javascript:;\">All</a></li> \n":"<li><a style=\"margin-left:10px\" href=\"javascript:{$this->js_call}('1','All');\">All</a></li> \n";
      }
    }

    public function display_items_per_page() {
        $items = '';
        $ipp_array = array(3,6,12,24,50,100);
        foreach($ipp_array as $ipp_opt) {
          $items .= ($ipp_opt == $this->items_per_page) ? "<option selected value=\"{$ipp_opt}\">{$ipp_opt}</option>\n":"<option value=\"{$ipp_opt}\">{$ipp_opt}</option>\n";
        }
        return "<div class=\"dataTables_wrapper form-inline dt-bootstrap no-footer\"><div class=\"row\"><div class=\"col-sm-6\"><div class=\"dataTables_length\"><label>Show <select class=\"form-control input-sm\" onchange=\"window.location='{$this->server_url}{$this->url_vars}&page=1&ipp='+this[this.selectedIndex].value;return false;\">{$items}</select> entries</label></div></div></div></div>\n";
    }

    public function display_jump_menu() {
        for($i=1; $i<=$this->num_pages; $i++) {
            $option .= ($i == $this->current_page) ? "<option value=\"{$i}\" selected>{$i}</option>\n":"<option value=\"{$i}\">{$i}</option>\n";
        }
        return "<div class=\"dataTables_wrapper form-inline dt-bootstrap no-footer\"><div class=\"row\"><div class=\"col-sm-6\"><div class=\"dataTables_length\"><label>Jump to <select class=\"form-control input-sm\" onchange=\"window.location='{$this->server_url}{$this->url_vars}&page='+this[this.selectedIndex].value+'&ipp={$this->items_per_page}';return false;\">{$option}</select></label></div></div></div></div>\n";
    }

    private function no_pag() {
      return "<ul class=\"pagination pull-right\"><li class=\"disabled\"><a href=\"javascript:;\">&laquo; Previous</a></li><li class=\"active\"><a href=\"javascript:;\">1</a></li><li class=\"disabled\"> <a href=\"javascript:;\">Next &raquo;</a></li></ul> \r\n <div class=\"clearfix\"></div>";
    }
    
    public function classic_pages() {
        return ($this->num_pages > 1) ? $this->return : $this->no_pag();
    }
    
    public function display_pages() {
        return ($this->num_pages > 1) ? $this->bootstap : $this->no_pag();
    }
    
    public function get_ajax_pages() {
        return ($this->num_pages > 1) ? $this->ajax_return : $this->no_pag();
    }

    protected function get_globals($skip = '', $type_of_globals = 'request', $only_these = '') {
      $the_request = '';
    //      $type_of_globals = strtolower($type_of_globals);
      switch ($type_of_globals) {
        case 'get':
          $globals = $_GET;
          break;
        case 'post':
          $globals = $_POST;
          break;
        case 'request':
        default:
          $globals = $_REQUEST;
          break;
      }
    //    if (!is_array($skip) && !empty($skip)) $skip = array($skip);
      foreach ($globals as $key => $value) {
        if (is_array($skip) && in_array($key, $skip)) {
          continue;
        }

        $value = urldecode($value);
        if ((is_array($only_these) && in_array($key, $only_these)) || !is_array($only_these)) {
          $the_request .= '&' . $key . '=' . $value;
        }
      }

      return $the_request;
  }
    
}