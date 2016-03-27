<?php

/**
 * Allows the creation of views.
 *
 */

namespace cx\app;

use cx\app\app as app;

class view extends app {

  public $vars;
  public $files = array();
  public $view_ext = ".php";
  public $template = false;
  public $display = true;
  public $content = '';
  private $page_done = false;

  public function __construct($file = null, $path = null) {
    $this->view_ext = ".php";
    $this->set_view($file, $path);
    parent::__construct();
  }

  /**
   * Copy constructor used to clone the View with all properties (e.g.
   * helpers) retained, while clearing all variables to be set in the view
   * file.
   */
  public function __clone() {
    $this->vars = null;
  }

  /* Alias to set_view */

  public function add_view($file, $path = null) {
    $this->set_view($file, $path);
  }

  public function set_view($view_file = null, $path = null) {
    if ($view_file !== null) {
      $view_file = $this->filter_uri($view_file);
      if ($path === null || empty($path)) {
        $file = PROJECT_BASE_DIR . "views" . DS . $view_file . $this->view_ext;
      } else {
        $file = $path . DS . "views" . DS . $view_file . $this->view_ext;
      }

      if (!file_exists($file)) {
        echo "No view file exists for: {$file}!";
        throw new Exception("Files does not exist: " . $file);
      } else {
        $this->files[] = $file;
      }
    }
  }

  public function set_template($render_page) {
    if (!empty($render_page)) {
      $templ = PROJECT_BASE_DIR . 'templates' . DS . $render_page . '.tpl.php';
      if (file_exists($templ)) {
        $this->template = $templ;
        return true;
      }
    }
    $this->template = false;
    return false;
  }

  /**
   * Sets a variable in this view with the given name and value
   * 
   * @param mixed $name Name of the variable to set in the view, or an array of key/value pairs where each key is the variable and each value is the value to set.
   * @param mixed $value Value of the variable to set in the view.
   */
  public function set($name, $value = null) {
    if (is_array($name)) {
      foreach ($name as $var_name => $value) {
        $this->vars[$var_name] = $value;
      }
    } else {
      $this->vars[$name] = $value;
    }
  }

  protected function do_ob_start() {
    if (extension_loaded('mbstring')) {
      ob_start('mb_output_handler');
    } else {
      ob_start();
    }
  }

  public function bad_page() {
    $this->response->status = '404';
    $this->response->add_header(array('header' => 'HTTP/1.0 404 Not Found', 'status' => '404'));
  }

  public function set_page_cache($last_modified_time, $data = false) {
    $this->response->set_cache($last_modified_time, $data);
  }
  
  public function set_page_type($type = 'html') {
    $this->do_response($type);
    $this->page_done = true;
  }

  /**
   * 
   * @param type $local = $this
   */
  public function fetch($local, $file = null, $path = null) {
    $this->page = '';
    $this->response->clear_output();
    if ($this->page_done === false) {
      $this->set_page_type();
    }

    $this->set_view($file, $path);

    unset($file);
    unset($path);

    if (!is_object($local)) {
      $local = $this; // FALL Back, please use fetch($this);
    }

    if (is_array($this->vars)) {
      extract($this->vars, EXTR_PREFIX_SAME, "wddx");
      // Extract the vars to local namespace, duplcates will be called wddx_VARNAME
    }

    if ($local->get_errors() !== false) {
      $this->page .= $local->show_errors();
    }
    
    $this->page .= ob_get_clean(); // Get echos before View
    
    if (count($this->files) > 0) {
      $this->do_ob_start(); // Start output buffering
      foreach ($this->files as $view_file) {
        include $view_file; // Include the file
      }
      $this->page .= ob_get_clean(); // Get the contents of the buffer and close buffer.
    }

    if (!empty($this->content)) {
      $this->page .= $this->content;
      $this->content = ''; // reset content
    }
    
    if ($this->template !== false && file_exists($this->template)) {
      $this->do_ob_start();
      include $this->template;
      $this->response->set_output(ob_get_clean());
    } else {
      $this->response->set_output($this->page);
    }

    if (!empty($this->response->get_output())) {
      if ($this->display === true) {
        $this->response->output();
      } else {
        $this->response->do_cache(); // Needed as get_output does not call this method.
        return $this->response->get_output(); // Return the contents
      }
    } else {
      return false;
    }
  }

}

/**
 * JSP views ===============================================================
 */

class javascript_view extends app {

  public $vars;
  public $files = array();
  public $view_ext = ".jsp";
  public $content = '';

  public function __construct($file = null, $path = null) {
    $this->view_ext = ".jsp";
    $this->set_view($file, $path);
  }

  /**
   * Copy constructor used to clone the View with all properties (e.g.
   * helpers) retained, while clearing all variables to be set in the view
   * file.
   */
  public function __clone() {
    $this->vars = null;
  }

  /* Alias to set_view */

  public function add_view($file, $path = null) {
    $this->set_view($file, $path);
  }

  public function set_view($view_file = null, $path = null) {
    if ($view_file !== null) {
      $view_file = $this->filter_uri($view_file);
      if ($path === null || empty($path)) {
        $file = PROJECT_BASE_DIR . "views" . DS . $view_file . $this->view_ext;
      } else {
        $file = $path . DS . "views" . DS . $view_file . $this->view_ext;
      }

      if (!file_exists($file)) {
        echo "No view file exists for: {$file}!";
        throw new Exception("Files does not exist: " . $file);
      } else {
        $this->files[] = $file;
      }
    }
  }

  /**
   * Sets a variable in this view with the given name and value
   * 
   * @param mixed $name Name of the variable to set in the view, or an array of key/value pairs where each key is the variable and each value is the value to set.
   * @param mixed $value Value of the variable to set in the view.
   */
  public function set($name, $value = null) {
    if (is_array($name)) {
      foreach ($name as $var_name => $value) {
        $this->vars[$var_name] = $value;
      }
    } else {
      $this->vars[$name] = $value;
    }
  }

  protected function do_ob_start() {
    if (extension_loaded('mbstring')) {
      ob_start('mb_output_handler');
    } else {
      ob_start();
    }
  }

  /**
   * 
   * @param type $local = $this
   */
  public function fetch($local, $file = null, $path = null) {
    $this->set_view($file, $path);

    unset($file);
    unset($path);

    if (!is_object($local)) {
      $local = $this; // FALL Back, please use fetch($this);
    }

    if (is_array($this->vars)) {
      extract($this->vars, EXTR_PREFIX_SAME, "wddx");
      // Extract the vars to local namespace, duplcates will be called wddx_VARNAME
    }
    
    $jsp = '';
    if (count($this->files) > 0) {
      $this->do_ob_start(); // Start output buffering
      foreach ($this->files as $view_file) {
        include $view_file; // Include the file
      }
      $jsp .= ob_get_clean(); // Get the contents of the buffer and close buffer.
    }

    if (!empty($this->content)) {
      $jsp .= $this->content;
      $this->content = ''; // reset content
    }
    
    return $jsp;
  }

}