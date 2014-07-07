<?php if (!defined ('ABSPATH')) die('No direct access allowed');
//limit process to 15 minutes
@set_time_limit(900);

/**
 * WP Backitup Backup 
 * 
 * @package WP Backitup 
 * 
 * @author cssimmon
 * 
 */

/*** Includes ***/

if( !class_exists( 'WPBackItUp_Logger' ) ) {
    include_once 'class-logger.php';
}

if( !class_exists( 'WPBackItUp_Utility' ) ) {
 	include_once 'class-utility.php';
}


if( !class_exists( 'WPBackItUp_SQL' ) ) {
	include_once 'class-sql.php';
}

// include backup class
if( !class_exists( 'WPBackItUp_Backup' ) ) {
	include_once 'class-backup.php';
}

// include logger class
 if( !class_exists( 'WPBackItUp_Zip' ) ) {
 	include_once 'class-zip.php';
 }

// include file system class
if( !class_exists( 'WPBackItUp_Filesystem' ) ) {
	include_once 'class-filesystem.php';
}

/*** Globals ***/
global $WPBackitup;
global $backup_start_time;

//Get root
$url = str_replace('http://','',home_url());
$url = str_replace('/','-',$url);
$fileUTCDateTime=current_time( 'timestamp' );
$localDateTime = date_i18n('Y-m-d-His',$fileUTCDateTime);
$backup_name = 'Backup_' . $url .'_' .$localDateTime;

global $status_array,$inactive,$active,$complete,$failure,$warning,$success;
$inactive=0;
$active=1;
$complete=2;
$failure=-1;
$warning=-2;
$success=99;

//setup the status array
global $status_array;
$status_array = array(
	'preparing' =>$inactive,
	'backupdb' =>$inactive ,
	'infofile'=>$inactive,
	'backupfiles'=>$inactive,	
	'zipfile'=>$inactive,
	'cleanup'=>$inactive	
 );

global $logger;
$log_path = WPBACKITUP__BACKUP_PATH;
//echo "</br>Log Path:" .$log_path ;
$logger = new WPBackItUp_Logger(false,$log_path,$backup_name);

global $wp_backup; //Eventually everything will be migrated to this class
$wp_backup = new WPBackItUp_Backup($logger,$backup_name);

//*****************//
//*** MAIN CODE ***//
//*****************//
$logger->log('***BEGIN BACKUP***');
$logger->logConstants();
$backup_start_time = new datetime('now');

$WPBackitup->increment_backup_count();

//Dont do anything with this now, just post date time
$jsonResponse = new stdClass();
$jsonResponse->message = 'processing';
$jsonResponse->server_time=$backup_start_time->format('U');
write_response_file($jsonResponse);

//Cleanup & Validate the backup folded is ready 
set_status('preparing',$active,true);
sleep(3);//For UI only

//TESTS GO HERE

//TEST END HERE

$logger->log('**CLEANUP**');

cleanup_BackupFolder($wp_backup->backup_folder_root);
create_folder($wp_backup->backup_folder_root); //Create Root Folder

create_folder($wp_backup->backup_project_path);//Create Project Folder
check_BackupFolder($wp_backup->backup_project_path);

set_status('preparing',$complete,false);
$logger->log('**END CLEANUP**');

//Backup the database
$logger->log('**SQL EXPORT**');
set_status('backupdb',$active,true);
sleep(3);//For UI only
$sqlFileName=$wp_backup->backup_project_path . WPBACKITUP__SQL_DBBACKUP_FILENAME;
export_Database($sqlFileName);
set_status('backupdb',$complete,false);
$logger->log('**END SQL EXPORT**');

//Extract the site info
$logger->log('**SITE INFO**');
set_status('infofile',$active,true);
sleep(3);//For UI only
create_SiteInfoFile($wp_backup->backup_project_path);
set_status('infofile',$complete,false);
$logger->log('**END SITE INFO**');

//Backup the WP-Content
$logger->log('**WP CONTENT**');
set_status('backupfiles',$active,true);
sleep(3);//For UI only
backup_wpcontent();
//validate_wpcontent(); - add this for next release?
set_status('backupfiles',$complete,false);
$logger->log('**END WP CONTENT**');

//Zip up the backup folder
$logger->log('**BACKUP ZIP**');
set_status('zipfile',$active,true);
sleep(3);//For UI only
$logger->log('Zip Up the Backup Folder:'.$wp_backup->backup_project_path);
$zip = new WPBackItUp_Zip($logger);
$zip->compress($wp_backup->backup_project_path, $wp_backup->backup_folder_root);
set_status('zipfile',$complete,false);
$logger->log('**END BACKUP ZIP**');

//Send JSON response
$jsonResponse = new stdClass();
$jsonResponse->message = 'success';
$jsonResponse->filedate = $localDateTime;
$jsonResponse->file = $wp_backup->backup_filename;
$jsonResponse->zip_link = WPBACKITUP__BACKUP_URL . '/' . $wp_backup->backup_filename;
$jsonResponse->license = $this->license_active();
$jsonResponse->retained = $wp_backup->backup_retained_number;

if (file_exists($logger->logFilePath)) {
    $jsonResponse->log_link = basename($logger->logFileName,'.log');
}

//Cleanup
$logger->log('**CLEANUP**');
set_status('cleanup',$active,true);
cleanup($wp_backup->backup_project_path);
set_status('cleanup',$complete,false);
$logger->log('**END CLEANUP**');

//Send success Email to user before cleanup
send_backup_notification_email(null,true);

//DONE!
set_status_success();
write_response_file($jsonResponse);

$WPBackitup->increment_successful_backup_count();

$logger->log("Backup completed successfully");
$logger->log("*** END BACKUP ***");
die();

/******************/
/*** Functions ***/
/******************/
function cleanup($path){
	global $logger,$wp_backup;
	$logger->log('Delete Backup Folder:'.$path);
	$fileSystem = new WPBackItUp_FileSystem($logger);
	if(!$fileSystem ->recursive_delete($path)) {
		write_warning_status('error106');
	}
	else
	{
		$logger->log('Backup Folder Deleted');
	}

	//Check the retention
	$fileSystem->purge_FilesByDate($wp_backup->backup_retained_number,$wp_backup->backup_folder_root);

    //Purge logs older than 5 days
    $fileSystem->purge_files(WPBACKITUP__BACKUP_PATH .'/','log',$wp_backup->backup_retained_days);
  	
}

function backup_wpcontent(){
	global $logger,$wp_backup;

    $fromFolder = WPBACKITUP__CONTENT_PATH . '/';
	$ignore = array( WPBACKITUP__BACKUP_FOLDER,$wp_backup->backup_name,$wp_backup->restore_folder_root,'upgrade','cache' );
		
	$logger->log('Recursive Copy FROM:'.$fromFolder);
	$logger->log('Recursive Copy TO:'.$wp_backup->backup_project_path);
	$logger->log('Ignore Array:');
	$logger->log($ignore);
	
	$fileSystem = new WPBackItUp_FileSystem($logger);
	if(!$fileSystem->recursive_copy($fromFolder, $wp_backup->backup_project_path, $ignore) ) {
	    write_fatal_error_status('error103');
		cleanup_on_failure($wp_backup->backup_project_path);
		send_backup_notification_email(103,false);
		die();
	}
	$logger->log('Recursive Copy completed');
}

function validate_wpcontent(){
    global $logger,$wp_backup;
    $source_dir_path = WPBACKITUP__CONTENT_PATH . '/';
    $target_dir_path = $wp_backup->backup_project_path;

    $logger->log('Validate content folder TO:' .$source_dir_path);
    $logger->log('Validate content folder FROM:' .$target_dir_path);

    $ignore = array(WPBACKITUP__PLUGIN_FOLDER,'debug.log','backupsiteinfo.txt','db-backup.sql');
    $filesystem = new WPBackItUp_FileSystem($logger);
    if(!$filesystem->recursive_validate($source_dir_path. '/', $target_dir_path . '/',$ignore)) {
        $logger->log('Error: Content folder is not the same as backup.');
    }

    $logger->log('Content folder validation complete.');
}

//Create siteinfo in project dir
function create_SiteInfoFile($path){
	global $wpdb,$logger;

	$logger->log('Create Site Info File:'.$path);
	if (!create_siteinfo($path, $wpdb->prefix) ) {
		write_fatal_error_status('error105');
		cleanup_on_failure($path);
	   	send_backup_notification_email(105,false);
		die();
	}
	$logger->log('Site Info File Created.');
}

function create_siteinfo($path, $table_prefix) {
		$siteinfo = $path ."backupsiteinfo.txt"; 
		$handle = fopen($siteinfo, 'w+');
		$entry = site_url( '/' ) ."\n$table_prefix";
		fwrite($handle, $entry); 
		fclose($handle);
		return true;
}

function export_Database($sqlFileName){
	global $wp_backup,$logger;

	$SQL = new WPBackItUp_SQL($logger);

	//Try SQLDump First	
	$logger->log('Create the SQL Backup File:'.$sqlFileName);
	if(!$SQL->mysqldump_export($sqlFileName) ) {
		//Try manual extract if sqldump isnt working
		if(!$SQL->manual_export($sqlFileName) ) {
			write_fatal_error_status('error104');
			cleanup_on_failure($wp_backup->backup_project_path);
	    	send_backup_notification_email(104,false);
			die();
		}
	 }
	$logger->log('Created the SQL Backup File:'.$sqlFileName);
}
//Check to see if the directory is writeable
function check_BackupFolder($path){	
	global $wp_backup,$logger;
	$logger->log("Is folder writeable: " .$path);
	if(!is_writeable($path)) {
		write_fatal_error_status('error102');
	    send_backup_notification_email(102,false);
		die();
	} else {
		//If the directory is writeable, create the backup folder if it doesn't exist
		$logger->log("Folder IS writeable: " .$path);
		if(!is_dir($path) ) {
			$logger->log("Create Backup Content Folder: " .$path);
			@mkdir($wp_backup->backup_project_path, 0755);
			$logger->log('Backup Content Folder Created:'.$path);
		}
	}
}

function create_folder($path){
	global $logger;
	$fileSystem = new WPBackItUp_FileSystem($logger);
	
	$logger->log("Create Backup Folder: " .$path);
	if(!$fileSystem->create_dir($path)) {
		$logger->log('Error: Cant create backup folder :'. $path);
		write_fatal_error_status('error101');
	    send_backup_notification_email(101, false);
		die();	
	}
	$logger->log("Backup Folder Created");
}

function send_backup_notification_email($err, $status)
{
	global $WPBackitup, $logger, $backup_start_time,$status_array;
	$utility = new WPBackItUp_Utility($logger);

    //$start_date = new DateTime(date( 'Y-m-d H:i:s',$backup_start_time));
    $backup_end_time = new DateTime('now');

    $util = new WPBackItUp_Utility($logger);
    $seconds = $util->date_diff_seconds($backup_start_time,$backup_end_time);

    $processing_minutes = round($seconds / 60);
    $processing_seconds = $seconds % 60;

    //PHP 5.3
    //$interval = $start_date->diff(new DateTime(date( 'Y-m-d H:i:s',$backup_end_time)));

    $logger->log('Script Processing Time:' .$processing_minutes .' Minutes ' .$processing_seconds .' Seconds');

    $status_description = array(
        'preparing'=>'Preparing for backup...Done',
        'backupdb'=>'Backing-up database...Done',
        'infofile'=>'Creating backup information file...Done',
        'backupfiles'=>'Backing up plugins, themes, and uploads...Done',
        'zipfile'=>'Zipping backup directory...Done',
        'cleanup'=>'Cleaning up...Done'
    );

    $error_description = array(
        '101' =>'Error 101: Unable to create a new directory for backup. Please check your CHMOD settings of your wp-backitup backup directory',
        '102'=> 'Error 102: Cannot create backup directory. Please check the CHMOD settings of your wp-backitup plugin directory',
        '103'=> 'Error 103: Unable to backup your files. Please try again',
        '104'=> 'Error 104: Unable to backup your database. Please try again',
        '105'=> 'Error 105: Unable to create site information file. Please try again',
        '106'=> 'Warning 106: Unable to cleanup your backup directory',
        '114'=> 'Error 114: Your database was accessible but an export could not be created. Please contact support by clicking the get support link on the right. Please let us know who your host is when you submit the request'
    );

	if($status)
	{
        $subject = 'WP BackItUp - Backup completed successfully.';
        $message = '<b>Your backup completed successfully.</b><br/><br/>';

    } else  {
        $subject = 'WP BackItUp - Backup did not complete successfully.';
        $message = '<b>Your backup did not complete successfully.</b><br/><br/>';
    }

    $message .= 'Backup started: ' . $backup_start_time->format( 'Y-m-d H:i:s') . '<br/>';
    $message .= 'Backup ended: ' . $backup_end_time->format( 'Y-m-d H:i:s') . '<br/>';
    $message .= 'Processing Time: ' .$processing_minutes .' Minutes ' .$processing_seconds .' Seconds <br/>';

    $message .= '<br/>';

    $message .='<b>Steps Completed</b><br/>';

    //Add the completed statuses
    foreach ($status_array as $status_key => $status_value) {
//        echo($status_key. ':' .$status_value);
        if ($status_value==2) {
            foreach ($status_description as $msg_key => $msg_value) {
//                echo($status_key. ':' .$msg_key);
                if ($status_key==$msg_key) {
                    $message .=  $msg_value . '<br/>';
                    break;
                }
            }
         }
    }

    //Add the errors
    if(!$status)
    {
        $message .= '<br/>';
        $message .= 'Errors:<br/>';

        foreach ($error_description as $key => $value) {
            if ($err==$key){
                $message .=$error_description[$key];
            }
        }
	}

    $message .='<br/><br/>Checkout <a href="https://www.wpbackitup.com">www.wpbackitup.com</a> for info about WP BackItUp and our other products.<br/>';

	$notification_email = $WPBackitup->get_option('notification_email');
	if($notification_email)
		$utility->send_email($notification_email,$subject,$message);
}

function cleanup_on_failure($path){
	global $logger;
	if (WPBACKITUP__DEBUG===true){
		$logger->log('Cleanup On Fail suspended: debug on.');
	}
	else{
		cleanup_backupFolder($path);
	}
}

function cleanup_BackupFolder($dir){
    global $logger;
    $logger->log('Cleanup Backup Folder:'.$dir);
    $ignore = array('cgi-bin','.','..','._');
    if( is_dir($dir) ){
        if($dh = opendir($dir)) {
            while( ($file = readdir($dh)) !== false ) {
                $ext = pathinfo($file, PATHINFO_EXTENSION);
                if (!in_array($file, $ignore) && substr($file, 0, 1) != '.' && $ext!="zip" && $ext!="log") { //Check the file is not in the ignore array
                    if(!is_dir($dir .'/'. $file)) {
                        unlink($dir .'/'. $file);
                    } else {
                        $fileSystem = new WPBackItUp_FileSystem($logger);
                        $fileSystem->recursive_delete($dir.'/'. $file, $ignore);
                    }
                }
            }
        }
        @rmdir($dir);
        closedir($dh);
    }
    $logger->log('Cleanup Backup Folder completed:'.$dir);
    return true;
}


function write_fatal_error_status($status_code) {
	global $status_array,$active,$failure;
	
	//Find the active status and set to failure
	foreach ($status_array as $key => $value) {
		if ($value==$active){
			$status_array[$key]=$failure;	
		}
	}

	//Add failure to array
	$status_array[$status_code]=$failure;
	write_status();
}

function write_warning_status($status_code) {
	global $status_array,$warning;
		
	//Add warning to array
	$status_array[$status_code]=$warning;
	write_status();
}

function write_status() {
	global $status_array;
	$fh=getStatusLog();

	foreach ($status_array as $key => $value) {
		fwrite($fh, '<div class="' . $key . '">' . $value .'</div>');		
	}
	fclose($fh);
}

function set_status($process,$status,$flush){
	global $status_array;
	$status_array[$process]=$status;
	
	if ($flush) write_status(); 
}

function set_status_success(){
	global $status_array,$success;

	$status_array['finalinfo']=$success;
	write_status();
}

//Get Status Log
function getStatusLog() {
	$log = WPBACKITUP__PLUGIN_PATH .'/logs/status.log';
	if (file_exists($log)){
		unlink($log);
	}
	$fh = fopen($log, 'w') or die( "Can't write to log file" );
	return $fh;
}

//write Response Log
function write_response_file($object) {
    global $logger;
    $json_response = json_encode($object);
    $logger->log('Write response file:' . $json_response);

    $fh=get_response_file();
    fwrite($fh, $json_response);
    fclose($fh);
}

//Get Response Log
function get_response_file() {
    global $logger;
    $response_file_path = WPBACKITUP__PLUGIN_PATH .'/logs/response.log';
    $filesytem = new WPBackItUp_FileSystem($logger);
    return $filesytem->get_file_handle($response_file_path,true);
}



