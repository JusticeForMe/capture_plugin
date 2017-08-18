<?php
/*
Plugin Name: Capture Plugin Config
Description: Capture what a plugin adds to your site: options, pages, etc.
Version: 0.1
Author: Denise Draper
*/

class Capture_Plugin {

    private static $singleton;
    private $have_seen;
    private $watching;

    public static function start() {
        if ( empty($singleton) ) {
            Capture_Plugin::$singleton = new Capture_Plugin();
        }
    }

    private function __construct() {
        // These are the generic hooks that capture *any* activation, not just our own
        add_action('activate_plugin',array($this,'baseline'),1,1);
        add_action('activated_plugin',array($this,'checkpoint'),1,1);
    }

    private function baseline($plugin_name) {
        if ( in_array(array_keys($this->have_seen),$plugin_name)) return;
        $this->watching = $plugin_name;
    }

    private function checkpoint($plugin_name) {
        if ( in_array(array_keys($this->have_seen),$plugin_name)) return;
        if ( $plugin_name != $this->watching ) {
            // we got our wires cross.  give up.
            $this->clear_state();
            return;
        }
    }
}

Capture_Plugin::start();

