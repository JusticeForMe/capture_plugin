<?php
/*
Plugin Name: Capture Database Inserts
Description: See what gets added to your database
Version: 0.1
Author: Denise Draper
*/

class Capture_Plugin {
    /** @var Capture_Admin_UI $ui */
    public static $ui;
    /** @var Capture_DB_Monitor $monitor */
    public static $monitor;

    public static function start() {
        Capture_Plugin::$ui = new Capture_Admin_UI();
        Capture_Plugin::$monitor = new Capture_DB_Monitor();
    }
}
include "capture_monitor.php";
include "capture_admin.php";

Capture_Plugin::start();

