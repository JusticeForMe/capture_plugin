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
    /** @var  Capture_DB_Additions $watchstate */
    public $watchstate;

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
        if ( in_array(array_keys($this->have_seen), $plugin_name)) return;
        $this->watching = $plugin_name;
        $this->watchstate = new Capture_DB_Additions();
        $this->watchstate->baseline();
    }

    private function checkpoint($plugin_name) {
        if ( in_array(array_keys($this->have_seen), $plugin_name)) return;
        if ( $plugin_name != $this->watching ) {
            // we got our wires cross.  give up.
            $this->clear_state();
            return;
        }
        $this->watchstate->checkpoint();
        $this->store_result();
    }

    private function store_result() {
        $this->have_seen[ $this->watching ] = array(
          'new_tables' => $this->watchstate->new_tables,
          'new_values' => $this->watchstate->new_values
        );
    }

    private function clear_state() {
        $this->watching = null;
        $this->watchstate = null;
    }

    public function get_results() {
        return $this->have_seen;
    }
}

Capture_Plugin::start();

