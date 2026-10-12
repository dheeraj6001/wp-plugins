<?php
/*
Plugin Name: TrigrExam
Plugin URI: https://trigrexam.com
Description: Design by TrigrExam Team
Version: 1.0
Author: Dheeraj Kumar
Author URI: https://pesofts.com
*/

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

define('trigrexam_plugin_dir', plugin_dir_path(__FILE__));

require_once(trigrexam_plugin_dir . 'app/Model.php');
require_once(trigrexam_plugin_dir . 'app/Controller.php');
require_once(trigrexam_plugin_dir . 'app/Frontend.php');

// What the site uses. The admin has it too: it shows what is wrong with the shortcodes, and a form of the
// site is posted to admin-ajax.php.
$TrigrexamFrontend = new TrigrexamFrontend();
$TrigrexamFrontend->register();

// What the admin uses. A page of the site does not load it.
if(is_admin()){
    require_once(trigrexam_plugin_dir . 'app/Admin.php');
    $TrigrexamAdmin = new TrigrexamAdmin($TrigrexamFrontend);
    $TrigrexamAdmin->register();
}
