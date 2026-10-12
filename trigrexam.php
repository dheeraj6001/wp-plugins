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

require_once(trigrexam_plugin_dir . 'controller/Core.php');
require_once(trigrexam_plugin_dir . 'controller/Functions.php');
require_once(trigrexam_plugin_dir . 'controller/ContactForm.php');
require_once(trigrexam_plugin_dir . 'controller/Menu.php');

$TrigrexamPlugin = new TrigrexamFunctions();
$TrigrexamPlugin->register();

$TrigrexamContactForm = new TrigrexamContactForm();
$TrigrexamContactForm->register();

$TrigrexamMenu = new TrigrexamMenu();
$TrigrexamMenu->register();
