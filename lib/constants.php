<?php
/**
 * Constants used by this plugin
 * 
 * @package WPBackitup
 * 
 * @author jcpeden
 * @version 1.1.6
 * @since 1.1.3
 */

// The current version of this plugin
if( !defined( 'WPBACKITUP_VERSION' ) ) define( 'WPBACKITUP_VERSION', '1.1.5' );

// The directory the plugin resides in
if( !defined( 'WPBACKITUP_DIRNAME' ) ) define( 'WPBACKITUP_DIRNAME', dirname( dirname( __FILE__ ) ) );

// The URL path of this plugin
if( !defined( 'WPBACKITUP_URLPATH' ) ) define( 'WPBACKITUP_URLPATH', WP_PLUGIN_URL . "/" . plugin_basename( WPBACKITUP_DIRNAME ) );

if( !defined( 'IS_AJAX_REQUEST' ) ) define( 'IS_AJAX_REQUEST', ( !empty( $_SERVER['HTTP_X_REQUESTED_WITH'] ) && strtolower( $_SERVER['HTTP_X_REQUESTED_WITH'] ) == 'xmlhttprequest' ) );