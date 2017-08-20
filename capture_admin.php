<?php

/**
 * @class Capture_Admin_UI creates the UI interface for this plugin, which consists of a button on the admin bar,
 * and a pop-up overlay to show the results.
 */
class Capture_Admin_UI {

    public function __construct() {
        add_action('admin_bar_menu',array($this,'add_button'), 5000);
        add_action('wp_enqueue_scripts', array($this,'send_scripts'));
        add_action('admin_enqueue_scripts', array($this,'send_scripts'));

        add_option('capture_plugin_is_recording',false);

        add_action('wp_ajax_capture_plugin_toggle', array($this,'toggle'));
    }

    public function add_button( $adminBar ) {
        $args = array(
            'id' => 'capture_plugin_button',
            'title' => ( get_option('capture_plugin_is_recording') ? "Stop Capture" : "Start Capture" ),
            'meta' => array(
                'class' => 'capture_plugin_button',
            )
        );
        $adminBar->add_node( $args );
    }

    public function send_scripts() {
        wp_enqueue_script('capture_plugin_client',plugins_url( 'capture_plugin_client.js', __FILE__ ), array('jquery'));
        wp_localize_script('capture_plugin_client','capture_plugin_client_settings',array(
            'url' => admin_url( 'admin-ajax.php' ),
        ));
    }

    // Toggle the recording state
    public function toggle() {
        $stop_now = get_option('capture_plugin_is_recording');
        if ( $stop_now ) {
            Capture_Plugin::$monitor->checkpoint();
            update_option('capture_plugin_is_recording',false);
            $result = array(
                'label' => "Start Capture",
                'new_tables' => Capture_Plugin::$monitor->new_tables,
                'new_keys' => Capture_Plugin::$monitor->cleanup(Capture_Plugin::$monitor->new_keys)
            );
        }
        else {
            Capture_Plugin::$monitor->baseline();
            update_option('capture_plugin_is_recording',true);
            $result = array('label'=> "Stop Capture");
        }

        header( 'Content-type: application/json' );
        ob_clean();
        echo wp_json_encode($result);
        wp_die();
    }


}