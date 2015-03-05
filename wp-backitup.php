<?php if (!defined ('ABSPATH')) die('No direct access allowed');

/**
 * WP BackItUp  - Backup View
 *
 * @package WP BackItUp
 * @author  Chris Simmons <chris.simmons@wpbackitup.com>
 * @link    http://www.wpbackitup.com
 *
 */

/*
Plugin Name: WP Backitup
Plugin URI: http://www.wpbackitup.com
Description: Backup your content, settings, themes, plugins and media in just a few simple clicks.
Version: 1.10.6
Author: Chris Simmons
Author URI: http://www.wpbackitup.com
License: GPL3

Copyright 2012-2014 WPBackItUp  (email : support@wpbackitup.com)

This program is free software: you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation, either version 3 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program.  If not, see <http://www.gnu.org/licenses/>.
*/

define( 'WPBACKITUP__NAMESPACE', 'wp-backitup' );
define( 'WPBACKITUP__VERSION', '1.10.6');
define( 'WPBACKITUP__DEBUG', false );
define( 'WPBACKITUP__MINIMUM_WP_VERSION', '3.0' );
define( 'WPBACKITUP__ITEM_NAME', 'WP Backitup' ); 
define( 'WPBACKITUP__FRIENDLY_NAME', 'WP BackItUp' );

define( 'WPBACKITUP__CONTENT_PATH', WP_CONTENT_DIR  );

define( 'WPBACKITUP__SITE_URL', 'http://www.wpbackitup.com');
define( 'WPBACKITUP__SECURESITE_URL', 'https://www.wpbackitup.com' );

define( 'WPBACKITUP__PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'WPBACKITUP__PLUGIN_PATH', plugin_dir_path( __FILE__ ) );
define( 'WPBACKITUP__PLUGIN_FOLDER',basename(dirname(__FILE__)));

define( 'WPBACKITUP__BACKUP_FOLDER', 'wpbackitup_backups' );
define( 'WPBACKITUP__BACKUP_URL', content_url() . "/" .WPBACKITUP__BACKUP_FOLDER);
define( 'WPBACKITUP__BACKUP_PATH',WPBACKITUP__CONTENT_PATH  . '/' . WPBACKITUP__BACKUP_FOLDER);
define( 'WPBACKITUP__UPLOAD_FOLDER','TMP_Uploads');
define( 'WPBACKITUP__UPLOAD_PATH',WPBACKITUP__BACKUP_PATH . '/' .WPBACKITUP__UPLOAD_FOLDER);

define( 'WPBACKITUP__RESTORE_FOLDER', 'wpbackitup_restore' );
define( 'WPBACKITUP__RESTORE_PATH',WPBACKITUP__CONTENT_PATH . '/' . WPBACKITUP__RESTORE_FOLDER);

define( 'WPBACKITUP__PLUGINS_ROOT_PATH',WP_PLUGIN_DIR );
define( 'WPBACKITUP__THEMES_ROOT_PATH',get_theme_root() );
define( 'WPBACKITUP__THEMES_FOLDER',basename(get_theme_root()));

define( 'WPBACKITUP__SQL_DBBACKUP_FILENAME', 'db-backup.sql');

define( 'WPBACKITUP__BACKUP_IGNORE_LIST', WPBACKITUP__BACKUP_FOLDER .',' .WPBACKITUP__RESTORE_FOLDER .',updraft*,wp-clone*,backwpup*,backupwordpress*,cache,backupcreator*,backupbuddy*');
define( 'WPBACKITUP__TASK_TIMEOUT_SECONDS', 300);//300 = 5 minutes
define( 'WPBACKITUP__SCRIPT_TIMEOUT_SECONDS', 900);//900 = 15 minutes

define( 'WPBACKITUP__BACKUP_RETAINED_DAYS', 5);//5 days
define( 'WPBACKITUP__SUPPORT_EMAIL', 'wpbackitupcomsupport@wpbackitup.freshdesk.com');

define( 'WPBACKITUP__ZIP_MAX_FILE_SIZE', 524288000); //524288000; # 500Mb
define( 'WPBACKITUP__THEMES_BATCH_SIZE', 5000); //~100kb each = 5000*100 = 500000 kb = 500 mb
define( 'WPBACKITUP__PLUGINS_BATCH_SIZE', 5000); //~100kb each = 5000*100 = 500000 kb = 500 mb
define( 'WPBACKITUP__OTHERS_BATCH_SIZE', 500); //~100kb each = 5000*100 = 500000 kb = 500 mb
define( 'WPBACKITUP__UPLOADS_BATCH_SIZE', 500); //anyones guess here

register_activation_hook( __FILE__, array( 'WPBackitup_Admin', 'activate' ) );
register_deactivation_hook( __FILE__, array( 'WPBackitup_Admin', 'deactivate' ) );


function wpbackitup_modify_cron_schedules($schedules) {
    $schedules['weekly'] = array('interval' => 604800, 'display' => 'Once Weekly');
    $schedules['monthly'] = array('interval' => 2592000, 'display' => 'Once Monthly');
    $schedules['every4hours'] = array('interval' => 14400, 'display' => sprintf(__('Every %s hours', 'wpbackitup'), 4));
    $schedules['every8hours'] = array('interval' => 28800, 'display' => sprintf(__('Every %s hours', 'wpbackitup'), 8));
    return $schedules;
}

add_filter('cron_schedules', 'wpbackitup_modify_cron_schedules', 30);

function  wpbackitup_custom_post_status(){
	register_post_status( 'queued', array(
		'public'                    => false,
		'exclude_from_search'       => false,
		'show_in_admin_all_list'    => false,
		'show_in_admin_status_list' => true,
	));

	register_post_status( 'active', array(
		'public'                    => false,
		'exclude_from_search'       => false,
		'show_in_admin_all_list'    => false,
		'show_in_admin_status_list' => true,
	));

	register_post_status( 'error', array(
		'public'                    => false,
		'exclude_from_search'       => false,
		'show_in_admin_all_list'    => false,
		'show_in_admin_status_list' => true,
	));

	register_post_status( 'complete', array(
		'public'                    => false,
		'exclude_from_search'       => false,
		'show_in_admin_all_list'    => false,
		'show_in_admin_status_list' => true,
	));

	register_post_status( 'cancelled', array(
		'public'                    => false,
		'exclude_from_search'       => false,
		'show_in_admin_all_list'    => false,
		'show_in_admin_status_list' => true,
	));

}
add_action( 'init', 'wpbackitup_custom_post_status' );

// Admin class will not be instantiate if any of these conditions are met
if (!is_admin()
    && (!defined('DOING_CRON') || !DOING_CRON)
    && (!defined('XMLRPC_REQUEST') || !XMLRPC_REQUEST)
    && empty($_SERVER['SHELL'])
    && empty($_SERVER['USER'])) {

	return;  //END HERE
}

require_once( WPBACKITUP__PLUGIN_PATH .'/lib/includes/class-wpbackitup-admin.php' );
require_once( WPBACKITUP__PLUGIN_PATH .'/lib/includes/class-logger.php' );

global $WPBackitup;
$WPBackitup = WPBackitup_Admin::get_instance();
$WPBackitup->initialize();
