<?php

/**
 * Constants used by this plugin
 * 
 * @package WP Backitup Pro
 * 
 * @author jcpeden
 * @version 1.1.6
 * @since 1.1.3
 */

//limit process to 15 minutes
@set_time_limit(900);

//Define variables
$backup_project_dirname = get_bloginfo('name') .'-Export-' .date('Y-m-d-Hi'); 
$backup_project_path = WPBACKITUP_DIRNAME ."/backups/". $backup_project_dirname .'/';
$wp_content_path = dirname(dirname(dirname(dirname(dirname(__FILE__))))) .'/';

//create the backup dir
if( !is_dir(WPBACKITUP_DIRNAME ."/backups") ) {
	@mkdir(WPBACKITUP_DIRNAME ."/backups", 0775);
}

//create log file
$log = WPBACKITUP_DIRNAME ."/backups/status.log";
$fh = fopen($log, 'w') or die("can't open file");
fwrite($fh, '<ul>');
	
//Check to see if the directory is writeable
fwrite($fh, '<li>Creating backup directory...');
if(!is_writeable(WPBACKITUP_DIRNAME ."/backups/")) {
	fwrite($fh, '</li><li class="error">Cannot create backup directory. Please check the CHMOD settings of your wp-backitup plugin directory.</li></ul>');
	die();
} else {
	//If the directory is writeable, create the backup folder if it doesn't exist
	if( !is_dir($backup_project_path) ) {
		@mkdir($backup_project_path, 0775);
		fwrite($fh, 'Done!</li>');
	}
	foreach(glob(WPBACKITUP_DIRNAME ."/backups/*.zip") as $zip) {
		unlink($zip);
	}
}

//Backup content to project dir
fwrite($fh, '<li>Backing up your files...');
//Backup with copy
if(recursive_copy($wp_content_path, $backup_project_path, $ignore = array( 'cgi-bin','.','..','._',$backup_project_dirname,'backupbuddy_backups','*.zip','cache' ) ) ) {
	fwrite($fh, 'Done!</li>');
} else {
	fwrite($fh, '</li><li class="error">Unable to backup your files. Please try again.</li></ul>');
	die();
}

//Dump DB to project dir
fwrite($fh, '<li>Backing up your database...');
if(	db_backup($backup_project_path) ) {
	fwrite($fh, 'Done!</li>');
} else {
	fwrite($fh, '</li><li class="error">Unable to backup your database. Please try again.</li></ul>');
	recursive_delete($backup_project_path);
	die();
}

//Create siteinfo in project dir
global $wpdb;
if (!create_siteinfo($backup_project_path, $wpdb->prefix) ) {
	fwrite($fh, '<li class="error">Unable to create site information file. Please try again.</li></ul>');
	recursive_delete($backup_project_path);
	die();
}

//Zip the project dir
fwrite($fh, '<li>Creating backup zip...');
$z = new recurseZip();
$src = rtrim($backup_project_path, '/');
$z->compress($src, WPBACKITUP_DIRNAME ."/backups/");
fwrite($fh, 'Done!</li>');

//Delete backup dir
if(!recursive_delete($backup_project_path)) {
	fwrite($fh, '<li class="error">Warning: Unable to cleanup your backup directory.</li>');
}

//close log file
fwrite($fh, '<li>Backup file created successfully. You can download your backup file using the link above.</li>');
fwrite($fh, '</ul>');
fclose($fh);

//End backup function
die();