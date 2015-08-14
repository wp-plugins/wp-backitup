<?php if (!defined ('ABSPATH')) die('No direct access allowed');

// Checking safe mode is on/off and set time limit
if( ini_get('safe_mode') ){
   @ini_set('max_execution_time', WPBACKITUP__SCRIPT_TIMEOUT_SECONDS);
}else{
   @set_time_limit(WPBACKITUP__SCRIPT_TIMEOUT_SECONDS);
}

/**
 * WP BackItUp  - Backup Cancelled Job
 *
 * @package WP BackItUp
 * @author  Md. Al-Amin <alamin.opu10@gmail.com>
 * @link    http://www.wpbackitup.com
 *
 */

/*** Includes ***/

if( !class_exists( 'WPBackItUp_Filesystem' ) ) {
	include_once 'class-filesystem.php';
}

if( !class_exists( 'WPBackItUp_Job' ) ) {
	include_once 'class-job.php';
}

/*** Globals ***/
global $WPBackitup;

global $status_array,$inactive,$active,$complete,$failure,$warning,$success,$cancelled;
$inactive=0;
$active=1;
$complete=2;
$failure=-1;
$warning=-2;
$success=99;
$cancelled=-99;


//setup the status array
global $status_array;
$status_array = array(
	'preparing' =>$inactive,
	'backupdb' =>$inactive ,
	'infofile'=>$inactive,
	'backup_themes'=>$inactive,
	'backup_plugins'=>$inactive,
	'backup_uploads'=>$inactive,
	'backup_other'=>$inactive,
	'validate_backup'=>$inactive,
	'finalize_backup'=>$inactive,
 );

global $backup_job;
$backup_job=null;

$backup_job = WPBackItUp_Job::get_job('backup');

//Get the backup ID
$backup_name =  get_backup_name($backup_job->backup_id);
$backup_logname = $backup_name;

// Setting status cancelled
set_status_cancelled();

/******************/
/*** Functions ***/
/******************/
function get_backup_name($timestamp){

	$url = home_url();
    $url = str_replace('http://','',$url);
	$url = str_replace('https://','',$url);
    $url = str_replace('/','-',$url);
    $fileUTCDateTime=$timestamp;//current_time( 'timestamp' );
    $localDateTime = date_i18n('Y-m-d-His',$fileUTCDateTime);
    $backup_name = 'Backup_' . $url .'_' .$localDateTime;

    return $backup_name;

}


function write_status() {
	global $status_array;
	$fh=getStatusLog();

	foreach ($status_array as $key => $value) {
		fwrite($fh, '<div class="' . $key . '">' . $value .'</div>');		
	}

	fclose($fh);
}

// Set Status Cancelled
function set_status_cancelled(){
	global $status_array,$cancelled;

	//Mark all the cancelled and flush
	foreach ($status_array as $key => $value) {
		$status_array[$key]=$cancelled;
	}

	$status_array['finalinfo']=$cancelled;
	write_status();
}

//Get Status Log
function getStatusLog(){
	global $backup_logname;

	$status_file_path = WPBACKITUP__PLUGIN_PATH .'/logs/backup_status.log';
	$filesystem = new WPBackItUp_FileSystem($backup_logname);
	return $filesystem->get_file_handle($status_file_path);

}
