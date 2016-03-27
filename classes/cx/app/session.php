<?php

namespace cx\app;

require_once CX_INCLUDES_DIR . 'db_sessions.php';

class session {

    public $data = array();

    public function __construct() {

        if (empty($_SERVER['REQUEST_URI'])) {
            return false;
        }

        if (! session_id()) {
            $sess_name = \cx_configure::a_get('security','session_name');
            if ($sess_name !== false) {
              ini_set('session.name', $sess_name);
            }
            ini_set('session.use_only_cookies', 'On');
            ini_set('session.use_trans_sid', 'Off');
            ini_set('session.cookie_httponly', 'On');

            session_set_cookie_params(0, '/');
            
            if (\cx_configure::a_get('security','session_table') !== false) {
              session_set_save_handler("_cxs_open", "_cxs_close", "_cxs_read", "_cxs_write", "_cxs_destroy", "_cxs_clean");
            }
            
            session_start();
        }

        $this->data = & $_SESSION;
    }

    public function session_var($var) {
        return (isset($this->data[\cx_configure::a_get('cx', 'session_variable') . $var])) ? $this->data[\cx_configure::a_get('cx', 'session_variable') . $var] : ':null';
    }

    public function set_session_var($var, $content) {
        $this->data[\cx_configure::a_get('cx', 'session_variable') . $var] = $content;
    }

    public function get_int($var) {
        return (isset($this->data[\cx_configure::a_get('cx', 'session_variable') . $var])) ? intval($this->data[\cx_configure::a_get('cx', 'session_variable') . $var]) : -1;
    }

    public function get_id() {
        return session_id();
    }

    public function destroy() {
        return session_destroy();
    }

}
