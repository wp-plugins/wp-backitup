<?php if (!defined ('ABSPATH')) die('No direct access allowed');

// Checking safe mode is on/off and set time limit
if( ini_get('safe_mode') ){
   @ini_set('max_execution_time', WPBACKITUP__SCRIPT_TIMEOUT_SECONDS);
}else{
   @set_time_limit(WPBACKITUP__SCRIPT_TIMEOUT_SECONDS);
}

/**
 * WP BackItUp  - Cleanup Job
 *
 * @package WP BackItUp
 * @author  Chris Simmons <chris.simmons@wpbackitup.com>
 * @link    http://www.wpbackitup.com
 *
 */

/*** Includes ***/

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

if( !class_exists( 'WPBackItUp_Restore' ) ) {
	include_once 'class-restore.php';
}

// include zip class
if( !class_exists( 'WPBackItUp_Zip' ) ) {
	include_once 'class-zip.php';
}

// include file system class
if( !class_exists( 'WPBackItUp_Filesystem' ) ) {
	include_once 'class-filesystem.php';
}

// include job class
if( !class_exists( 'WPBackItUp_Job' ) ) {
	include_once 'class-job.php';
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


//**************************//
//   SINGLE THREAD BACKUPS  //
//**************************//
$tasks_logname='debug_tasks';
$backup_process_id = uniqid();

//If there is a queued or active job then add a resume check
if ('scheduled'==$this->backup_type){
	wp_schedule_single_event( time()+30, 'wpbackitup_run_cleanup_tasks');
}

//Make sure backup is NOT already running before you run the current task
if (!WPBackItUp_Backup::start()) {
	WPBackItUp_LoggerV2::log_info($tasks_logname,$backup_process_id ,'Cleanup job cant acquire job lock.');
	return; //nothing to do
}else{
	WPBackItUp_LoggerV2::log_info($tasks_logname,$backup_process_id ,'Cleanup job lock acquired.');
}
//**************************//

//**************************//
//     Task Handling        //
//**************************//
global $cleanup_job;
$cleanup_job=null;
$current_task= null;

$backup_error=false;


$cleanup_job = WPBackItUp_Job::get_job('cleanup');
WPBackItUp_LoggerV2::log_info($tasks_logname,$backup_process_id ,'Check for available job');
if ($cleanup_job){

	//Get the next task in the stack
	$next_task = $cleanup_job->get_next_task();
	if (false!==$next_task){
		$backup_id=$cleanup_job->backup_id;
		$current_task=$next_task;

		//If task contains error then timeout has occurred
		if (strpos($current_task,'error') !== false){
			$backup_error=true;
		}

		WPBackItUp_LoggerV2::log_info($tasks_logname,$backup_process_id ,'Available Task Found:' . $current_task);

	}else{
		WPBackItUp_LoggerV2::log_info($tasks_logname,$backup_process_id ,'No available tasks found.');
		WPBackItUp_Backup::end(); //release lock
		return;
	}
}else {
	WPBackItUp_LoggerV2::log_info($tasks_logname,$backup_process_id ,'No backup job available.');
	wp_clear_scheduled_hook( 'wpbackitup_run_cleanup_tasks');
	WPBackItUp_Backup::end(); //release lock
	return;
}


//Should only get here when there is a task to run
WPBackItUp_LoggerV2::log_info($tasks_logname,$backup_process_id ,'Run cleanup task:' .$current_task);

//*************************//
//*** MAIN BACKUP CODE  ***//
//*************************//

//Get the backup ID
$job_name =  get_job_name($cleanup_job->backup_id);

global $cleanup_logname;
$cleanup_logname=$job_name;

global $wp_backup;
$wp_backup = new WPBackItUp_Backup($cleanup_logname,$job_name,$WPBackitup->backup_type);

//***   SCHEDULED TASKS   ***//

//Run cleanup task
if ('task_scheduled_cleanup'==$current_task) {

	//Init
	WPBackItUp_LoggerV2::log($cleanup_logname,'***BEGIN JOB***');
	WPBackItUp_LoggerV2::log_sysinfo($cleanup_logname);

	WPBackItUp_LoggerV2::log($cleanup_logname,'**CHECK LICENSE**');
	do_action( 'wpbackitup_check_license');
	WPBackItUp_LoggerV2::log($cleanup_logname,'**END CHECK LICENSE**');

	WPBackItUp_LoggerV2::log($cleanup_logname,'**CLEAN UNFINISHED BACKUPS**' );
	//cleanup any folders that have the TMP_ prefix
	$wp_backup->cleanup_backups_by_prefix('TMP_');
	WPBackItUp_LoggerV2::log($cleanup_logname,'**END CLEAN UNFINISHED BACKUPS**' );

	WPBackItUp_LoggerV2::log($cleanup_logname,'**CLEAN DELETED BACKUPS**' );
	//cleanup any folders that have the DLT_ prefix
	$wp_backup->cleanup_backups_by_prefix('DLT_');
	WPBackItUp_LoggerV2::log($cleanup_logname,'**END CLEAN DELETED BACKUPS**' );

	WPBackItUp_LoggerV2::log($cleanup_logname,'**CLEAN OLD BACKUPS**' );
	//Cleanup any folders that exceed retention limit
	$wp_backup->cleanup_old_backups();
	WPBackItUp_LoggerV2::log($cleanup_logname,'**END CLEAN OLD BACKUPS**' );

	WPBackItUp_LoggerV2::log($cleanup_logname,'**CLEAN OLD RESTORES**' );
	//Cleanup any folders that exceed retention limit
	$wp_restore = new WPBackItUp_Restore($cleanup_logname,$job_name,null);
	$wp_restore->delete_restore_folder();
	WPBackItUp_LoggerV2::log($cleanup_logname,'**END CLEAN OLD RESTORES**' );

	WPBackItUp_LoggerV2::log($cleanup_logname,'**PURGE OLD FILES**' );
	$wp_backup->purge_old_files();
	WPBackItUp_LoggerV2::log($cleanup_logname,'**END PURGE OLD FILES**' );

	WPBackItUp_LoggerV2::log($cleanup_logname,'**SECURE FOLDERS**' );
	//Make sure backup folder is secured
	$file_system = new WPBackItUp_FileSystem($cleanup_logname);

	//Make sure backup folder is secured
	$backup_dir = WPBACKITUP__CONTENT_PATH . '/' . WPBACKITUP__BACKUP_FOLDER;
	$file_system->secure_folder( $backup_dir);

	//--Check restore folder folders
	$restore_dir = WPBACKITUP__CONTENT_PATH . '/' . WPBACKITUP__RESTORE_FOLDER;
	$file_system->secure_folder( $restore_dir);

	//Make sure logs folder is secured
	$logs_dir = WPBACKITUP__PLUGIN_PATH .'/logs/';
	$file_system->secure_folder( $logs_dir);
	WPBackItUp_LoggerV2::log($cleanup_logname,'**END SECURE FOLDERS**' );

	WPBackItUp_LoggerV2::log($cleanup_logname,'**CLEANUP OLD JOBS**' );
	$backup_job_purge_count = WPBackItUp_Job_v2::purge_completed_jobs('backup');
	WPBackItUp_LoggerV2::log($cleanup_logname,'Backup job records purged:' .$backup_job_purge_count );

	$cleanup_job_purge_count = WPBackItUp_Job_v2::purge_completed_jobs('cleanup');
	WPBackItUp_LoggerV2::log_info($cleanup_logname,__METHOD__,'Cleanup job records purged:' .$cleanup_job_purge_count );
	WPBackItUp_LoggerV2::log($cleanup_logname,'**END CLEANUP OLD JOBS**' );

	$cleanup_job->set_task_complete();
}

end_job(null,true);

//*** END SCHEDULED TASKS ***//

/******************/
/*** Functions ***/
/******************/
function get_job_name($timestamp){

	$fileUTCDateTime=$timestamp;//current_time( 'timestamp' );
	$localDateTime = date_i18n('Y-m-d-His',$fileUTCDateTime);
	$job_name = 'cleanup_' .$localDateTime;

	return $job_name;

}

function end_job($err=null, $success=null){
	global $WPBackitup, $cleanup_logname, $cleanup_job;
	WPBackItUp_LoggerV2::log_info($cleanup_logname,__METHOD__,'Begin');

	WPBackItUp_Backup::end(); //Release the lock
	$current_datetime = current_time( 'timestamp' );
	$WPBackitup->set_cleanup_lastrun_date($current_datetime);

	$util = new WPBackItUp_Utility($cleanup_logname);
	$seconds = $util->timestamp_diff_seconds($cleanup_job->get_job_start_time(),$cleanup_job->get_job_end_time());

	$processing_minutes = round($seconds / 60);
	$processing_seconds = $seconds % 60;

	WPBackItUp_LoggerV2::log_info($cleanup_logname,__METHOD__,'Script Processing Time:' .$processing_minutes .' Minutes ' .$processing_seconds .' Seconds');

	if (true===$success) WPBackItUp_LoggerV2::log($cleanup_logname,'Cleanup completed: SUCCESS');
	if (false===$success) WPBackItUp_LoggerV2::log($cleanup_logname,'Cleanup completed: ERROR');
	WPBackItUp_LoggerV2::log($cleanup_logname,'*** END JOB ***');

	echo('cleanup has completed');
	exit(0);
}

