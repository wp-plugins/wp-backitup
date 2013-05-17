<?php

/**
 * WP Backitup Backup Functions
 * 
 * @package WP Backitup Lite
 * 
 * @author jcpeden
 * @version 1.2.2
 * @since 1.0.1
 */

global $WPBackitupLite;

//limit process to 15 minutes
@set_time_limit(900);

//Define variables
$backup_project_dirname = get_bloginfo('name') .'-Export-' .date('Y-m-d-Hi'); 
$backup_project_path = WPBACKITUP_DIRNAME ."/backups/". $backup_project_dirname .'/';
$wp_content_path = dirname(dirname(dirname(dirname(dirname(__FILE__))))) .'/';

//create log file

$log = WPBACKITUP_DIRNAME ."/logs/status.log";
unlink($log);
$fh = fopen($log, 'w') or die("Can't open log file");

//Delete the existing backup directory
recursive_delete( WPBACKITUP_DIR_PATH .'/backups/' );

//Re-create and empty backup dir
if(!create_dir( WPBACKITUP_DIR_PATH .'/backups/' )) {
        fwrite($fh, '<status code="prerequsites">'.__('Failed', $WPBackitupLite->namespace ).'</status>');
	fwrite($fh, '<error  code="errorMessage">' . __('Error: Unable to create new directory for backup. Please check your CHMOD settings in ' , $WPBackitupLite->namespace ).WPBACKITUP_DIR_PATH  . '.</error>');

	fclose($fh);
        
	die();
}

//Check to see if the directory is writeable

if(!is_writeable(WPBACKITUP_DIRNAME ."/backups/")) {
        fwrite($fh, '<status code="prerequsites">' . __('Failed', $WPBackitupLite->namespace ) . '</status>');
	fwrite($fh, '<error  code="errorMessage">' . __('Error: Cannot create backup directory. Please check the CHMOD settings of your wp-backitup plugin directory.', $WPBackitupLite->namespace ) . '</error>');
       
	die();
} else {
	//If the directory is writeable, create the backup folder if it doesn't exist
	if( !is_dir($backup_project_path) ) {
		@mkdir($backup_project_path, 0755);
	}
	foreach(glob(WPBACKITUP_DIRNAME ."/backups/*.zip") as $zip) {
		unlink($zip);
	}
	fwrite($fh, '<status code="prerequisites">' . __('Done', $WPBackitupLite->namespace ) . '</status>');
}

//Backup with copy
if(recursive_copy($wp_content_path, $backup_project_path, $ignore = array( 'cgi-bin','.','..','._',$backup_project_dirname,'backupbuddy_backups','*.zip','cache' ) ) ) {
	fwrite($fh, '<status code="backupfiles">' . __('Done',$WPBackitupLite->namespace ) . '</status>');
} else {
        fwrite($fh, '<status code="backupfiles">' . __('Failed', $WPBackitupLite->namespace ) . '</status>');
	fwrite($fh, '<error code="errorMessage">' . __('Error: Unable to backup your files. Please try again.', $WPBackitupLite->namespace ) . "</error>");
	die();
}

//Dump DB to project dir


if(	db_backup($backup_project_path) ) {
	fwrite($fh, '<status code="backupdb">' . __('Done', $WPBackitupLite->namespace ) . '</status>');
} else {
	fwrite($fh, '<error code="errorMessage">' . __('Error: Unable to backup your database. Please try again.', $WPBackitupLite->namespace ) . '</error>');
	recursive_delete($backup_project_path);
	die();
}

//Create siteinfo in project dir
global $wpdb;

if (!create_siteinfo($backup_project_path, $wpdb->prefix) ) {
        fwrite($fh, '<status code="infofile">' . __('Failed' , $WPBackitupLite->namespace ) . '</status>');
	fwrite($fh, '<error code="errorMessage">' . __('Error: Unable to create site information file. Please try again.', $WPBackitupLite->namespace ) . '</error>');
	recursive_delete($backup_project_path);
        
	die();
}
else
{
    fwrite($fh, '<status code="infofile">' . __('Done', $WPBackitupLite->namespace ) . '</status>');
}

//Zip the project dir

$z = new recurseZip();
$src = rtrim($backup_project_path, '/');
$z->compress($src, WPBACKITUP_DIRNAME ."/backups/");
fwrite($fh, '<status code="zipfile">' . __('Done', $WPBackitupLite->namespace ) . '</status>');

//Delete backup dir
if(!recursive_delete($backup_project_path)) {
        fwrite($fh, '<status code="cleanup">' . __('Failed', $WPBackitupLite->namespace ). '</status>');
	fwrite($fh, '<error code="errorMessage">' . __('Warning: Unable to cleanup your backup directory.', $WPBackitupLite->namespace ) . "</error>");
        
}
else
{
    fwrite($fh, '<status code="cleanup">' . __('Done' , $WPBackitupLite->namespace ) . '</status>');
}

//close log file
fwrite($fh, '<status code="finalinfo">' . __('Backup file created successfully. You can download your backup file using the link above.', $WPBackitupLite->namespace ) . "</status>");
fwrite($fh, '<status code="end">' . __('End', $WPBackitupLite->namespace ) . '</status>');
fclose($fh);

//End backup function
die();