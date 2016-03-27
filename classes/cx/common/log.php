<?php

namespace cx\common;

class log {

    private $handle;

    public function __construct($filename) {
        $this->handle = fopen(CX_LOGS_DIR . $filename, 'a');
    }

    public function write($message) {
        $tz = \cx_configure::a_get('cx', 'logger_time_zone');
        if ($tz !== false && ! empty($tz)) {
            $tz_obj = new \DateTimeZone($tz);
            $dt = new \DateTime();
            $dt->setTimezone($tz_obj);
            $now = $dt->format('g:i A \o\n l jS F Y');
        } else {
            $dt = new \DateTime();
            $now = $dt->format('g:i A \o\n l jS F Y');
        }
        fwrite($this->handle, $now . ' - ' . print_r($message, true) . "\n");
    }

    public function __destruct() {
        fclose($this->handle);
    }

}
