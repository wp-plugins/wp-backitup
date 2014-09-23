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

$backup_name =  get_backup_name();

global $logger;
$logger = new WPBackItUp_Logger(false,null,$backup_name);

global $wp_backup;
$wp_backup = new WPBackItUp_Backup($logger,$backup_name,$WPBackitup->backup_type);

//*****************//
//*** MAIN CODE ***//
//*****************//
$logger->log('***BEGIN BACKUP***');
$logger->logConstants();

$logger->log('Backup Type:' .strtoupper($wp_backup->backup_type));

//Check to see if a backup is already running
if (!$wp_backup->start()) {
    $logger->log('Backup Already in progress');
    if ($wp_backup->backup_type=='manual'){
        $wp_backup->check_lock_status();
    }
    end_backup();
}

//Run cleanup only
if ($wp_backup->backup_type=='cleanup'){
    $logger->log('Cleanup requested');
    $wp_backup->cleanup_unfinished_backups();
    $wp_backup->purge_old_files();
    $current_datetime = current_time( 'timestamp' );
    $WPBackitup->set_cleanup_lastrun_date($current_datetime);
    end_backup();
}

//This is neither a scheduled OR manual backup so just run some cleanup
if ($wp_backup->backup_type!='scheduled' && $wp_backup->backup_type!='manual'){
    $logger->log('No backup requested - ending');
    end_backup();
}

// Run scheduled OR manual backup

$WPBackitup->increment_backup_count();

//Cleanup & Validate the backup folded is ready
write_response_file("preparing for backup");
set_status('preparing',$active,true);
sleep(3);//For UI only

//TESTS GO HERE

//TEST END HERE

$logger->log('**BEGIN CLEANUP**');
write_response_file("Cleanup before backup");

//Cleanup any backups that didnt finish normally
$wp_backup->cleanup_unfinished_backups();

//Make sure wpbackitup_backups exists
if (!$wp_backup->backup_root_folder_exists()){
    write_fatal_error_status('error101');
    end_backup(101, false);
}

//Create the root folder for the current backup
if (!$wp_backup->create_current_backup_folder()){
    write_fatal_error_status('error101');
    end_backup(101, false);
}

//Check to see if the directory exists and is writeable
if (!$wp_backup->backup_folder_exists()){
    write_fatal_error_status('error102');
    end_backup(102,false);
}

set_status('preparing',$complete,false);
$logger->log('**END CLEANUP**');

//Backup the database
$logger->log('**BEGIN SQL EXPORT**');
write_response_file("Create SQL Export");
set_status('backupdb',$active,true);
sleep(3);//For UI only
if (!$wp_backup->export_database()){
    write_fatal_error_status('error104');
    cleanup_on_failure($wp_backup->backup_project_path);
    end_backup(104,false);
}

set_status('backupdb',$complete,false);
$logger->log('**END SQL EXPORT**');

//Extract the site info
$logger->log('**SITE INFO**');
write_response_file("Retrieve Site Info");
set_status('infofile',$active,true);
sleep(3);//For UI only

if (!$wp_backup->create_siteinfo_file()){
    write_fatal_error_status('error105');
    cleanup_on_failure($wp_backup->backup_project_path);
    end_backup(105,false);
}

set_status('infofile',$complete,false);
$logger->log('**END SITE INFO**');


//Backup the WP-Content
$logger->log('**WP CONTENT**');
write_response_file("Backup Content");
set_status('backupfiles',$active,true);
sleep(3);//For UI only

if (!$wp_backup->backup_wpcontent()){
    write_fatal_error_status('error103');
    cleanup_on_failure($wp_backup->backup_project_path);
    end_backup(103,false);
}

//auditing only
//If logging is turned on Validate
if ($WPBackitup->logging()){
    $wp_backup->validate_wpcontent();
}
set_status('backupfiles',$complete,false);
$logger->log('**END WP CONTENT**');

//Zip up the backup folder
$logger->log('**BACKUP ZIP**');
write_response_file("Compress Backup ");
set_status('zipfile',$active,true);
sleep(3);//For UI only
if (!$wp_backup->compress_backup()){
    write_fatal_error_status('error107');
    cleanup_on_failure($wp_backup->backup_project_path);
    end_backup(107,false);
}

set_status('zipfile',$complete,false);
$logger->log('**END BACKUP ZIP**');



//Cleanup
$logger->log('**CLEANUP**');
write_response_file("Cleanup after Backup ");
set_status('cleanup',$active,true);

if (!$wp_backup->cleanup_current_backup()){
    write_warning_status('error106');
}

//Check retention limits and cleanup
$wp_backup->purge_old_files();

set_status('cleanup',$complete,false);
$logger->log('**END CLEANUP**');

//DONE!
set_status_success();
write_response_file_success();

$WPBackitup->increment_successful_backup_count();

end_backup(null,true);

/******************/
/*** Functions ***/
/******************/
function get_backup_name(){

    $url = str_replace('http://','',home_url());
    $url = str_replace('/','-',$url);
    $fileUTCDateTime=current_time( 'timestamp' );
    $localDateTime = date_i18n('Y-m-d-His',$fileUTCDateTime);
    $backup_name = 'Backup_' . $url .'_' .$localDateTime;

    return $backup_name;

}
function end_backup($err=null, $success=null){
    global $wp_backup, $logger;
    $logger->log_info(__METHOD__,"Begin");

    $wp_backup->end(); //Release the lock

    $util = new WPBackItUp_Utility($logger);
    $seconds = $util->date_diff_seconds($wp_backup->backup_start_time,$wp_backup->backup_end_time);

    $processing_minutes = round($seconds / 60);
    $processing_seconds = $seconds % 60;

    $logger->log('Script Processing Time:' .$processing_minutes .' Minutes ' .$processing_seconds .' Seconds');

    //if null was passed then this was just a schedule check
    if (null!=$success){
        send_backup_notification_email($err, $success);
    }

    if ($success) $logger->log("Backup completed successfully");
    $logger->log("*** END BACKUP ***");

    $logFileName = $logger->logFileName;
    $logFilePath = $logger->logFilePath;
    $logger->close_file();

    //Move the log if it exists
    $newlogFilePath = $wp_backup->backup_folder_root .$logFileName;
    if (file_exists($logFilePath)){
        copy ($logFilePath,$newlogFilePath);
        unlink($logFilePath);
    }

    echo('Backup has completed');
    exit(0);
}

function send_backup_notification_email($err, $success) {
	global $WPBackitup, $wp_backup, $logger,$status_array;
    $logger->log_info(__METHOD__,"Begin");

	$utility = new WPBackItUp_Utility($logger);

    $util = new WPBackItUp_Utility($logger);
    $seconds = $util->date_diff_seconds($wp_backup->backup_start_time,$wp_backup->backup_end_time);

    $processing_minutes = round($seconds / 60);
    $processing_seconds = $seconds % 60;

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
        '107'=> 'Error 107: Unable to compress(zip) your backup. Please try again',
        '114'=> 'Error 114: Your database was accessible but an export could not be created. Please contact support by clicking the get support link on the right. Please let us know who your host is when you submit the request'

    );

	if($success)
	{
        $subject = 'WP BackItUp - Backup completed successfully.';
        $message = '<b>Your backup completed successfully.</b><br/><br/>';

    } else  {
        $subject = 'WP BackItUp - Backup did not complete successfully.';
        $message = '<b>Your backup did not complete successfully.</b><br/><br/>';
    }

    $message .= 'Backup started: '  . $wp_backup->backup_start_time->format( 'Y-m-d H:i:s') . '<br/>';
    $message .= 'Backup ended: '    . $wp_backup->backup_end_time->format( 'Y-m-d H:i:s') . '<br/>';
    $message .= 'Processing Time: ' . $processing_minutes .' Minutes ' .$processing_seconds .' Seconds <br/>';

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
    if(!$success)
    {
        $message .= '<br/>';
        $message .= 'Errors:<br/>';

        foreach ($error_description as $key => $value) {
            if ($err==$key){
                $message .=$error_description[$key];
            }
        }
	}

    $term='success';
    if(!$success)$term='error';
      $message .='<br/><br/>Checkout '. $WPBackitup->get_anchor_with_utm('www.wpbackitup.com', '', 'notification+email', $term) .' for info about WP BackItUp and our other products.<br/>';

	$notification_email = $WPBackitup->get_option('notification_email');
	if($notification_email)
		$utility->send_email($notification_email,$subject,$message);

    $logger->log_info(__function__,"End");
}

function cleanup_on_failure($path){
	global $logger;
    global $wp_backup;

	if (WPBACKITUP__DEBUG===true){
		$logger->log('Cleanup On Fail suspended: debug on.');
	}
	else{
        $wp_backup->cleanup_unfinished_backups();
	}
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
function write_response_file($message) {
    global $wp_backup,$logger;

    $jsonResponse = new stdClass();
    $jsonResponse->message = $message;
    $jsonResponse->server_time=$wp_backup->backup_start_time->format('U');

    $json_response = json_encode($jsonResponse);
    $logger->log('Write response file:' . $json_response);

    $fh=get_response_file();
    fwrite($fh, $json_response);
    fclose($fh);
}

//write Response Log
function write_response_file_success() {
    global $WPBackitup,$wp_backup,$logger;

    //Send JSON response
    $jsonResponse = new stdClass();
    $jsonResponse->message = 'success';
    $jsonResponse->file = $wp_backup->backup_filename;
    $jsonResponse->zip_link = WPBACKITUP__BACKUP_URL . '/' . $wp_backup->backup_filename;
    $jsonResponse->license = $WPBackitup->license_active();
    $jsonResponse->retained = $wp_backup->backup_retained_number;

    if (file_exists($logger->logFilePath)) {
        $jsonResponse->log_link = basename($logger->logFileName,'.log');
    }

    $json_response = json_encode($jsonResponse);
    $logger->log('Write response file:' . $json_response);

    $fh=get_response_file();
    fwrite($fh, $json_response);
    fclose($fh);
}

//Get Response Log
function get_response_file() {
    global $logger;
    $response_file_path = WPBACKITUP__PLUGIN_PATH .'logs/response.log';
    $filesytem = new WPBackItUp_FileSystem($logger);
    return $filesytem->get_file_handle($response_file_path,true);
}



