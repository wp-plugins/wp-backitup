<?php
/**
 * Constants used by this plugin
 *   
 * @package WP Backitup
 * 
 * @author jcpeden
 * @version 1.5.5
 * @since 1.0.1
 */

if( !defined( 'WPBACKITUP_VERSION' ) ) define( 'WPBACKITUP_VERSION', '1.6.4' );

if( !defined( 'WPBACKITUP_DIRNAME' ) ) define( 'WPBACKITUP_DIRNAME', dirname( dirname( __FILE__ ) ) );

if( !defined( 'WPBACKITUP_DIR_PATH' ) ) define( 'WPBACKITUP_DIR_PATH', dirname( dirname( dirname( __FILE__ ) ) ) );

if( !defined( 'WPBACKITUP_CONTENT_PATH' ) ) define( 'WPBACKITUP_CONTENT_PATH', dirname(dirname(dirname(dirname(__FILE__)))) .'/' );

if( !defined( 'WPBACKITUP_BACKUP_FOLDER' ) ) define( 'WPBACKITUP_BACKUP_FOLDER', 'wpbackitup_backups' );

if( !defined( 'WPBACKITUP_RESTORE_FOLDER' ) ) define( 'WPBACKITUP_RESTORE_FOLDER', 'wpbackitup_restore' );

if( !defined( 'WPBACKITUP_URLPATH' ) ) define( 'WPBACKITUP_URLPATH', WP_PLUGIN_URL . "/" . plugin_basename( WPBACKITUP_DIRNAME ) );

if( !defined( 'WPBACKITUP_BACKUPFILE_URLPATH' ) ) define( 'WPBACKITUP_BACKUPFILE_URLPATH', content_url() . "/" .WPBACKITUP_BACKUP_FOLDER);

if( !defined( 'IS_AJAX_REQUEST' ) ) define( 'IS_AJAX_REQUEST', ( !empty( $_SERVER['HTTP_X_REQUESTED_WITH'] ) && strtolower( $_SERVER['HTTP_X_REQUESTED_WITH'] ) == 'xmlhttprequest' ) );

if( !defined( 'WPBACKITUP_SITE_URL' ) ) define( 'WPBACKITUP_SITE_URL', 'http://www.wpbackitup.com' ); 

if( !defined( 'WPBACKITUP_ITEM_NAME' ) ) define( 'WPBACKITUP_ITEM_NAME', 'WP Backitup' ); 

if( !defined( 'WPBACKITUP_PLUGIN_FOLDER' ) ) define( 'WPBACKITUP_PLUGIN_FOLDER', plugin_basename( WPBACKITUP_DIRNAME ));
 
if( !defined( 'WPBACKITUP_SQL_DBBACKUP_FILENAME' ) ) define( 'WPBACKITUP_SQL_DBBACKUP_FILENAME', 'db-backup.sql');

if( !defined( 'WPBACKITUP_SQL_TABLE_RENAME_FILENAME' ) ) define( 'WPBACKITUP_SQL_TABLE_RENAME_FILENAME', 'db-rename-tables.sql');

if( !defined( 'WPBACKITUP_PLUGINS_PATH' ) ) define( 'WPBACKITUP_PLUGINS_PATH', dirname(dirname(__DIR__)));

if( !defined( 'WPBACKITUP_PLUGINS_FOLDER' ) ) define( 'WPBACKITUP_PLUGINS_FOLDER', basename(WPBACKITUP_PLUGINS_PATH));

if( !defined( 'WPBACKITUP_THEMES_PATH' ) ) define( 'WPBACKITUP_THEMES_PATH', dirname(get_bloginfo('template_directory')));

if( !defined( 'WPBACKITUP_THEMES_FOLDER' ) ) define( 'WPBACKITUP_THEMES_FOLDER', basename(WPBACKITUP_THEMES_PATH));
