<?php

/**
 * WP Backitup Backup Functions
 * 
 * @package WP Backitup Pro
 * 
 * @author jcpeden
 * @version 1.4.2
 * @since 1.0.1
 */

global $WPBackitup;

//limit process to 15 minutes
@set_time_limit(900);

//Define variables
$backup_project_dirname = get_bloginfo('name') .'-Export-' .date('Y-m-d-Hi'); 
$backup_project_path = WPBACKITUP_DIRNAME ."/backups/". $backup_project_dirname .'/';

//create log file
$log = WPBACKITUP_DIRNAME ."/logs/status.log";
unlink($log);
$fh = fopen($log, 'w') or die("Can't open log file");

//Delete the existing backup directory
recursive_delete( WPBACKITUP_DIR_PATH .'/backups/' );

//Re-create and empty backup dir
if(!create_dir( WPBACKITUP_DIR_PATH .'/backups/' )) {
    fwrite($fh, '<div class="prerequsites">0</div>');
	fwrite($fh, '<div class="error101">1</div>');
	fclose($fh);    
	die();
}

//Check to see if the directory is writeable
if(!is_writeable(WPBACKITUP_DIRNAME ."/backups/")) {
    fwrite($fh, '<div class="prerequsites">0</div>');
	fwrite($fh, '<div class="error102">1</div>');       
	die();
} else {
	//If the directory is writeable, create the backup folder if it doesn't exist
	if( !is_dir($backup_project_path) ) {
		@mkdir($backup_project_path, 0755);
	}
	foreach(glob(WPBACKITUP_DIRNAME ."/backups/*.zip") as $zip) {
		unlink($zip);
	}
	fwrite($fh, '<div class="prerequisites">1</div>');
}

//Dump DB to project dir
if( db_backup(DB_USER, DB_PASSWORD, DB_HOST, DB_NAME, $backup_project_path) ) { 
	fwrite($fh, '<div class="backupdb">1</div>');
} else {
	fwrite($fh, '<div class="backupdb">0</div>');
	fwrite($fh, '<div class="error104">1</div>');
	recursive_delete($backup_project_path);
	die();
}

//Backup with copy
if(recursive_copy(WPBACKITUP_CONTENT_PATH, $backup_project_path, $ignore = array( 'cgi-bin','.','..','._',$backup_project_dirname,'backupbuddy_backups','*.zip','cache' ) ) ) {
	fwrite($fh, '<div class="backupfiles">1</div>');
} else {
    fwrite($fh, '<div class="backupfiles">0</div>');
	fwrite($fh, '<div class="error103">1</div>');
	die();
}

//Create siteinfo in project dir
global $wpdb;

if (!create_siteinfo($backup_project_path, $wpdb->prefix) ) {
    fwrite($fh, '<div class="infofile">0</div>');
	fwrite($fh, '<div class="error105">1</div>');
	recursive_delete($backup_project_path);
	die();
} else {
    fwrite($fh, '<div class="infofile">1</div>');
}

//Zip the project dir
$z = new recurseZip();
$src = rtrim($backup_project_path, '/');
$z->compress($src, WPBACKITUP_DIRNAME ."/backups/");
fwrite($fh, '<div class="zipfile">1</div>');

//Delete backup dir
if(!recursive_delete($backup_project_path)) {
    fwrite($fh, '<div class="cleanup">0</div>');
	fwrite($fh, '<div class="error106">1</div>');      
} else {
    fwrite($fh, '<div class="cleanup">1</div>');
}

//close log file
fwrite($fh, '<div class="finalinfo">1</div>');
fclose($fh);

//End backup function
die();