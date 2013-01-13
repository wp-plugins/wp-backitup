<?php 
/**
 * Plugin Name: WP Backitup
 * Plugin URI: http://www.wpbackitup.com
 * Description: Backup your content, settings, themes, plugins and media in just a few simple clicks.
 * Version: 1.1.0
 * Author: John Peden
 * Author URI: http://www.johncpeden.com
 * License: GPLv2 or later
 * Text Domain: wp-backitup
 */

/*
	Copyright 2012-current  John Peden Ltd ( email : support@wpbackitup.com )
*/

//define constants
define("WPBACKITUP_PLUGIN_URL", WP_PLUGIN_URL ."/wp-backitup/");
define("WPBACKITUP_PLUGIN_PATH", WP_PLUGIN_DIR."/wp-backitup/");
define("WPBACKITUP_DIRNAME", "wp-backitup");
define("BACKUP_PATH", WPBACKITUP_PLUGIN_PATH .'backups/');
define("BACKUP_PATH", WPBACKITUP_PLUGIN_PATH .'backups/');

//load admin menu
function wpbackitup_admin_menus() {
	$wpbackituppage = add_menu_page( __( 'WP Backitup', 'wpBackitup' ), __( 'Backup', 'wpBackitup' ), 'manage_options', 'wp-backitup', 'wpbackitup_admin', plugin_dir_url(__FILE__ ) .'images/icon.png', 77);
	add_action('admin_print_scripts-'.$wpbackituppage, 'wpbackitup_javascript');
	add_action('admin_print_styles-' .$wpbackituppage, 'wpbackitup_stylesheet' );
}
add_action('admin_menu', 'wpbackitup_admin_menus');

//enqueue javascript
function wpbackitup_javascript() {
	wp_enqueue_script('wpbackitup-javascript', WPBACKITUP_PLUGIN_URL.'/js/wp-backitup.js');
	//this needs moved to addon dir (as above)
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

//load addons
function dir_contains_children($dir) {
    $result = false;
    if($dh = opendir($dir)) {
        while(!$result && ($file = readdir($dh)) !== false) {
            if($file != ("."||"..") {
            	include $file;
            }
        }
	closedir($dh);
}

/**
* PressTrends Plugin API
*/
	function presstrends_WPBackitup_plugin() {

		// PressTrends Account API Key
		$api_key = 'rwiyhqfp7eioeh62h6t3ulvcghn2q8cr7j5x';
		$auth    = 'lpa0nvlhyzbyikkwizk4navhtoaqujrbw';

		// Start of Metrics
		global $wpdb;
		$data = get_transient( 'presstrends_cache_data' );
		if ( !$data || $data == '' ) {
			$api_base = 'http://api.presstrends.io/index.php/api/pluginsites/update/auth/';
			$url      = $api_base . $auth . '/api/' . $api_key . '/';

			$count_posts    = wp_count_posts();
			$count_pages    = wp_count_posts( 'page' );
			$comments_count = wp_count_comments();

			// wp_get_theme was introduced in 3.4, for compatibility with older versions, let's do a workaround for now.
			if ( function_exists( 'wp_get_theme' ) ) {
				$theme_data = wp_get_theme();
				$theme_name = urlencode( $theme_data->Name );
			} else {
				$theme_data = get_theme_data( get_stylesheet_directory() . '/style.css' );
				$theme_name = $theme_data['Name'];
			}

			$plugin_name = '&';
			foreach ( get_plugins() as $plugin_info ) {
				$plugin_name .= $plugin_info['Name'] . '&';
			}
			// CHANGE __FILE__ PATH IF LOCATED OUTSIDE MAIN PLUGIN FILE
			$plugin_data         = get_plugin_data( __FILE__ );
			$posts_with_comments = $wpdb->get_var( "SELECT COUNT(*) FROM $wpdb->posts WHERE post_type='post' AND comment_count > 0" );
			$data                = array(
				'url'             => stripslashes( str_replace( array( 'http://', '/', ':' ), '', site_url() ) ),
				'posts'           => $count_posts->publish,
				'pages'           => $count_pages->publish,
				'comments'        => $comments_count->total_comments,
				'approved'        => $comments_count->approved,
				'spam'            => $comments_count->spam,
				'pingbacks'       => $wpdb->get_var( "SELECT COUNT(comment_ID) FROM $wpdb->comments WHERE comment_type = 'pingback'" ),
				'post_conversion' => ( $count_posts->publish > 0 && $posts_with_comments > 0 ) ? number_format( ( $posts_with_comments / $count_posts->publish ) * 100, 0, '.', '' ) : 0,
				'theme_version'   => $plugin_data['Version'],
				'theme_name'      => $theme_name,
				'site_name'       => str_replace( ' ', '', get_bloginfo( 'name' ) ),
				'plugins'         => count( get_option( 'active_plugins' ) ),
				'plugin'          => urlencode( $plugin_name ),
				'wpversion'       => get_bloginfo( 'version' ),
			);

			foreach ( $data as $k => $v ) {
				$url .= $k . '/' . $v . '/';
			}
			wp_remote_get( $url );
			set_transient( 'presstrends_cache_data', $data, 60 * 60 * 24 );
		}
	}

// PressTrends WordPress Action
add_action('admin_init', 'presstrends_WPBackitup_plugin');