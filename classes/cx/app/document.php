<?php

// OpenCart 2.0.2

namespace cx\app;

class document {

    private $title;
    private $description;
    private $keywords;
    private $links = array();
    private $styles = array();
    private $scripts = array();

    public function set_title($title) {
        $this->title = $title;
    }

    public function get_title() {
        return $this->title;
    }

    public function set_description($description) {
        $this->description = $description;
    }

    public function get_description() {
        return $this->description;
    }

    public function set_keywords($keywords) {
        $this->keywords = $keywords;
    }

    public function get_keywords() {
        return $this->keywords;
    }

    public function add_link($href, $rel) {
        $this->links[$href] = array(
            'href' => $href,
            'rel' => $rel
        );
    }

    public function get_links() {
        return $this->links;
    }

    public function add_style($href, $rel = 'stylesheet', $media = 'screen') {
        $this->styles[$href] = array(
            'href' => $href,
            'rel' => $rel,
            'media' => $media
        );
    }

    public function get_styles() {
        return $this->styles;
    }

    public function add_script($script) {
        $this->scripts[md5($script)] = $script;
    }

    public function get_scripts() {
        return $this->scripts;
    }

}
