<?php
//limit process to 15 minutes
@set_time_limit(900);

/**
 * WP Backitup Backup Functions
 * 
 * @package WP Backitup Pro
 * 
 * @author cssimmon
 * @version 1.4.2
 * @since 1.0.1
 */
/*** Includes ***/
// Define WP_DIR_PATH - required for constants include
if (!defined('WP_DIR_PATH')) define('WP_DIR_PATH',dirname(dirname(dirname(dirname(dirname(dirname(__FILE__)))))));
include_once WP_DIR_PATH . '/wp-config.php';
include_once dirname(dirname( __FILE__ )) . '/constants.php';

/*** Globals ***/
global $WPBackitup;
$fileUTCDateTime=current_time( 'timestamp' );
$localDateTime = date_i18n('Y-m-d-Hi',$fileUTCDateTime);
$backup_project_dirname = get_bloginfo('name') .'-Export-' .$localDateTime; 
$backup_project_path = WPBACKITUP_CONTENT_PATH .WPBACKITUP_BACKUP_FOLDER .'/'. $backup_project_dirname .'/';
$backup_folder_root = WPBACKITUP_CONTENT_PATH .WPBACKITUP_BACKUP_FOLDER .'/';
$restore_folder_root = WPBACKITUP_RESTORE_FOLDER;

//*****************//
//*** MAIN CODE ***//
//*****************//
deleteDebugLog();
_log('***BEGIN BACKUP.PHP***');
_log_constants();

//create status log file that is used in the UI
$fh = getStatusLog();

//Delete the contents of the existing backup directory
_log("Delete all files BUT zips: " .$backup_folder_root);
delete_allbutzips($backup_folder_root); //delete everything but old zips

//Re-create and empty backup dir
_log("Call Create Directory: " .$backup_folder_root);
if(!create_dir($backup_folder_root)) {	
    fwrite($fh, '<div class="prerequsites">0</div>');
	fwrite($fh, '<div class="error101">1</div>');
	fclose($fh);    
	die();	
}

//Check to see if the directory is writeable
_log("Is folder writeable: " .$backup_folder_root);
if(!is_writeable($backup_folder_root)) {
    fwrite($fh, '<div class="prerequsites">0</div>');
	fwrite($fh, '<div class="error102">1</div>');       
	die();
} else {
	//If the directory is writeable, create the backup folder if it doesn't exist
	_log("Folder IS writeable: " .$backup_folder_root);
	if( !is_dir($backup_project_path) ) {
		_log("Create Backup Content Folder: " .$backup_project_path);
		@mkdir($backup_project_path, 0755);
		_log('Backup Content Folder Created:'.$backup_project_path);
	}
	//Why do we need to do this? - delete all zip files in backup folder
	//foreach(glob($backup_folder_root ."*.zip") as $zip) {
	//	unlink($zip);
	//	_log('Zip file removed:'.$zip);
	//}
	fwrite($fh, '<div class="prerequisites">1</div>');
}

//Try MySQLDump First
_log('Create the SQL Backup File:'.$backup_project_path);
$sqlFileName=$backup_project_path . WPBACKITUP_SQL_DBBACKUP_FILENAME;
if(db_SQLDump($sqlFileName) ) { 
	fwrite($fh, '<div class="backupdb">1</div>');
} else {
	//Try manual extract if mysqldump isnt working
	if(db_backup($sqlFileName) ) { 
		fwrite($fh, '<div class="backupdb">1</div>');
	} else {
		fwrite($fh, '<div class="backupdb">0</div>');
		fwrite($fh, '<div class="error104">1</div>');
		recursive_delete($backup_project_path);
		die();
	}
}
_log('Created the SQL Backup File:'.$backup_project_path);

//Backup with copy
_log('Recursive Copy FROM:'.WPBACKITUP_CONTENT_PATH);
_log('Recursive Copy TO:'.$backup_project_path);
_log('Ignore:'.$backup_project_dirname);
_log('Ignore:'.$backup_folder_root);
_log('Ignore:'.$restore_folder_root);

if(recursive_copy(WPBACKITUP_CONTENT_PATH, $backup_project_path, $ignore = array( 'cgi-bin','.','..','._',WPBACKITUP_BACKUP_FOLDER,$backup_project_dirname,$restore_folder_root,'backupbuddy_backups','*.zip','cache' ) ) ) {
	fwrite($fh, '<div class="backupfiles">1</div>');
} else {
    fwrite($fh, '<div class="backupfiles">0</div>');
	fwrite($fh, '<div class="error103">1</div>');
	die();
}

//Create siteinfo in project dir
global $wpdb;
_log('Create Site Info:'.$backup_project_path);
if (!create_siteinfo($backup_project_path, $wpdb->prefix) ) {
    fwrite($fh, '<div class="infofile">0</div>');
		fwrite($fh, '<div class="error105">1</div>');
		recursive_delete($backup_project_path);
		die();
} else {
    fwrite($fh, '<div class="infofile">1</div>');
}

//Zip the project dir
_log('Zip Up the Backup Folder:'.$backup_project_path);
$z = new recurseZip();
$src = rtrim($backup_project_path, '/');
$z->compress($src, $backup_folder_root);
fwrite($fh, '<div class="zipfile">1</div>');


//Delete backup dir
_log('Delete Backup Folder:'.$backup_project_path);
if(!recursive_delete($backup_project_path)) {
    fwrite($fh, '<div class="cleanup">0</div>');
	fwrite($fh, '<div class="error106">1</div>');      
} else {
    fwrite($fh, '<div class="cleanup">1</div>');
}

//close log file
fwrite($fh, '<div class="finalinfo">1</div>');
fclose($fh);

$response['file'] = basename($src) . '.zip';
//$response['CreateDate'] = date('F j, Y g:i a',strtotime($localDateTime));
$response['link'] = WPBACKITUP_BACKUPFILE_URLPATH . '/' . $backup_project_dirname . '.zip';

_log('Jason Response Values:');
_log(json_encode($response));

echo json_encode($response);

_log("*** END BACKUP.PHP ***");
die();
//End backup function