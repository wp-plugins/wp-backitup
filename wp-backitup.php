<?php 
/**
 * Plugin Name: WP Backitup
 * Plugin URI: http://www.wpbackitup.com
 * Description: Backup your content, settings, themes, plugins and media in just a few simple clicks.
 * Version: 1.0.6
 * Author: John Peden
 * Author URI: http://www.johncpeden.com
 * License: GPLv2 or later
 * Text Domain: wp-backitup
 */

/*
	Copyright 2012-current  John C. Peden  ( email : mail@johncpeden.com )
*/

//define constants
define("WPBACKITUP_PLUGIN_URL", WP_PLUGIN_URL ."/wp-backitup/");
define("WPBACKITUP_PLUGIN_PATH", WP_PLUGIN_DIR."/wp-backitup/");
define("WPBACKITUP_DIRNAME", "wp-backitup");
define("BACKUP_PATH", WPBACKITUP_PLUGIN_PATH .'backups/');

//add plugin options
function wpbackitup_options_init() {
	register_setting ('wpbackitup_options', 'wpbackitup', 'wpbackitup_options_validate');
}
add_action('admin_init', 'wpbackitup_options_init' );

//load admin menu
function wpbackitup_admin_menus() {
	$wpbackituppage = add_submenu_page( 'tools.php', 'WP BackItUp', 'WP BackItUp', 'manage_options', 'wp-backitup', 'wpbackitup_admin' );
	add_action('admin_print_scripts-'.$wpbackituppage, 'wpbackitup_javascript');
	add_action('admin_print_styles-' .$wpbackituppage, 'wpbackitup_stylesheet' );
}
add_action('admin_menu', 'wpbackitup_admin_menus');

//enqueue javascript
function wpbackitup_javascript() {
	wp_enqueue_script('wpbackitup-javascript', WPBACKITUP_PLUGIN_URL.'/js/wp-backitup.js');
	wp_enqueue_script('ajaxfileupload', WPBACKITUP_PLUGIN_URL.'/js/ajaxfileupload.js');
}

//enqueue stylesheet
function wpbackitup_stylesheet(){
	wp_enqueue_style('wpbackitup-stylesheet', WPBACKITUP_PLUGIN_URL.'/css/wp-backitup.css');
}

//load plugin functions
include_once 'includes/functions.php';

//load admin page
function wpbackitup_admin() {
	include_once('includes/admin_page.php');
}

//load backup function
function wpbackitup_backup() {
	include 'includes/backup.php';
}
add_action('wp_ajax_wpbackitup_backup', 'wpbackitup_backup');

//load download function
function wpbackitup_download() {
	include 'includes/download.php';
}
add_action('wp_ajax_wpbackitup_download', 'wpbackitup_download');

//load download function
function wpbackitup_logreader() {
	if(file_exists(BACKUP_PATH .'/status.log') ) {
		readfile(BACKUP_PATH .'/status.log');
	}
	die();
}
add_action('wp_ajax_wpbackitup_logreader', 'wpbackitup_logreader');