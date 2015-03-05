<?php if (!defined ('ABSPATH')) die('No direct access allowed');
@set_time_limit(WPBACKITUP__SCRIPT_TIMEOUT_SECONDS);

/**
 * WP BackItUp  - Backup Job
 *
 * @package WP BackItUp
 * @author  Chris Simmons <chris.simmons@wpbackitup.com>
 * @link    http://www.wpbackitup.com
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

if( !class_exists( 'WPBackItUp_Backup' ) ) {
	include_once 'class-backup.php';
}

 if( !class_exists( 'WPBackItUp_Zip' ) ) {
 	include_once 'class-zip.php';
 }


if( !class_exists( 'WPBackItUp_Filesystem' ) ) {
	include_once 'class-filesystem.php';
}


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


//**************************//
//   SINGLE THREAD BACKUPS  //
//**************************//

$logger_tasks = new WPBackItUp_Logger(false,null,'debug_tasks');
$backup_process_id = uniqid();

//Make sure backup is NOT already running before you run the current task

//Scheduled the next check
if ('scheduled'==$this->backup_type){
	wp_schedule_single_event( time()+30, 'wpbackitup_run_backup_tasks');
}

if (!WPBackItUp_Backup::start()) {
	$logger_tasks->log_info(__METHOD__.'(' .$backup_process_id .')','Backup job cant acquire job lock.');
	return; //nothing to do
}else{
	$logger_tasks->log_info(__METHOD__.'(' .$backup_process_id .')','Backup job lock acquired.');
}
//**************************//


//**************************//
//     Task Handling        //
//**************************//
global $backup_job;
$backup_job=null;
$current_task= null;

$backup_error=false;
$backup_job = WPBackItUp_Job::get_job('backup');
$logger_tasks->log_info(__METHOD__.'(' .$backup_process_id .')','Check for available backup job');
if ($backup_job){

	//Get the next task in the stack
	$next_task = $backup_job->get_next_task();
	if (false!==$next_task){
		$backup_id=$backup_job->backup_id;
		$current_task=$next_task;

		//If task contains error then timeout has occurred
		if (strpos($current_task,'error') !== false){
			$logger_tasks->log_info(__METHOD__.'(' .$backup_process_id .')','Backup Error Found:' .$current_task);
			$backup_error=true;
		}

		$logger_tasks->log_info(__METHOD__.'(' .$backup_process_id .')','Available Task Found:' . $current_task);

	}else{
		$logger_tasks->log_info(__METHOD__.'(' .$backup_process_id .')','No available tasks found.');
		WPBackItUp_Backup::end(); //release lock
		return;
	}
}else {
	$logger_tasks->log_info(__METHOD__.'(' .$backup_process_id .')','No backup job available.');

	wp_clear_scheduled_hook( 'wpbackitup_run_backup_tasks');
	WPBackItUp_Backup::end(); //release lock
	return;
}

//Should only get here when there is a task to run
$logger_tasks->log_info(__METHOD__.'(' .$backup_process_id .')','Run Backup task:' .$current_task);

//*************************//
//*** MAIN BACKUP CODE  ***//
//*************************//

//Get the backup ID
$backup_name =  get_backup_name($backup_job->backup_id);

global $logger;
$logger = new WPBackItUp_Logger(false,null,$backup_name);

global $wp_backup;
$wp_backup = new WPBackItUp_Backup($logger,$backup_name,$WPBackitup->backup_type);


//*************************//
//***   BACKUP TASKS    ***//
//*************************//

//An error has occurred on the previous tasks
if ($backup_error) {
	$error_task = substr($current_task,6);
	$logger->log('Fatal error on previous task:'. $error_task);

	//Check for error type
	switch ($error_task) {
		case "task_preparing":
			set_status('preparing',$active,true);
			write_fatal_error_status('2101');
			end_backup(2101, false);
			break;

		case "task_backup_db":
			set_status( 'backupdb', $active, true );
			write_fatal_error_status( '2104' );
			end_backup( 2104, false );
			break;

		case "task_backup_siteinfo":
			set_status( 'infofile', $active, true );
			write_fatal_error_status( '2105' );
			end_backup( 2105, false );
			break;

		case "task_backup_themes":
			set_status( 'backup_themes', $active, true );
			write_fatal_error_status( '2120' );
			end_backup( 2120, false );
			break;

		case "task_backup_plugins":
			set_status( 'backup_plugins', $active, true );
			write_fatal_error_status( '2121' );
			end_backup( 2121, false );
			break;

		case "task_backup_uploads":
			set_status( 'backup_uploads', $active, true );
			write_fatal_error_status( '2122' );
			end_backup( 2122, false );
			break;

		case "task_backup_other":
			set_status( 'backup_other', $active, true );
			write_fatal_error_status( '2123' );
			end_backup( 2123, false );
			break;

		case "task_validate_backup":
			set_status( 'validate_backup', $active, true );
			write_fatal_error_status( '2126' );
			end_backup( 2126, false );
			break;

		case "task_finalize_backup":
			set_status( 'finalize_backup', $active, true );
			write_fatal_error_status( '2109' );
			end_backup( 2109, false );
			break;



//		case "task_cleanup_current": //Dont end backup on this error
//			set_status( 'cleanup', $active, true );
//			write_warning_status( '2106' );
//			break;

		default:
			write_warning_status( '2999' );
			end_backup( 2999, false );
			break;
	}

}

//Cleanup Task
if ('task_preparing'==$current_task) {

	//Init
	$logger->log('***BEGIN BACKUP***');
	$logger->log_sysinfo();
	$logger->log('BACKUP TYPE:' .$wp_backup->backup_type);
	$logger->log('BACKUP BATCH SIZE:' .$wp_backup->backup_batch_size);
	$logger->log('BACKUP ID:' .$backup_job->backup_id);

	$WPBackitup->increment_backup_count();
	//End Init

	$logger->log('**BEGIN CLEANUP**');

	//Cleanup & Validate the backup folded is ready
	write_response_processing("preparing for backup");
	set_status('preparing',$active,true);

	write_response_processing("Cleanup before backup");

	//*** Check Dependencies ***
	if (!WPBackItUp_Zip::zip_utility_exists()) {
		$logger->log_error(__METHOD__, 'Zip Util does not exist.' );
		$backup_job->set_task_error('125');
		write_fatal_error_status( '125' );
		end_backup( 125, false );
	}

	//*** END Check Dependencies ***


	//This is handled in the cleanup jobs now
	//Cleanup any backups that didnt finish normally
	//$wp_backup->cleanup_unfinished_backups();

	//Make sure wpbackitup_backups exists
	if (! $wp_backup->backup_root_folder_exists() ){
		$backup_job->set_task_error('101');

	    write_fatal_error_status('101');
	    end_backup(101, false);
	}

	//Create the root folder for the current backup
	if (! $wp_backup->create_current_backup_folder()){
		$backup_job->set_task_error('101');

	    write_fatal_error_status('101');
	    end_backup(101, false);
	}

	//Check to see if the directory exists and is writeable
	if (! $wp_backup->backup_folder_exists()){
		$backup_job->set_task_error('102');

	    write_fatal_error_status('102');
	    end_backup(102,false);
	}

	//Generate the list of files to be backed up and update the tasks info

	$plugins_file_list = $wp_backup->get_plugins_file_list();
	$backup_job->update_job_meta('backup_plugins_filelist',$plugins_file_list);
	$backup_job->update_job_meta('backup_plugins_filelist_remaining',$plugins_file_list);
	$plugins_file_list=null;

	$themes_file_list = $wp_backup->get_themes_file_list();
	$backup_job->update_job_meta('backup_themes_filelist',$themes_file_list);
	$backup_job->update_job_meta('backup_themes_filelist_remaining',$themes_file_list);
	$themes_file_list=null;

	//some folders excluded
	$uploads_file_list = $wp_backup->get_uploads_file_list();
	$backup_job->update_job_meta('backup_uploads_filelist',$uploads_file_list);
	$backup_job->update_job_meta('backup_uploads_filelist_remaining',$uploads_file_list);
	$uploads_file_list=null;

	//some folders excluded
	$others_file_list = $wp_backup->get_other_file_list();
	$backup_job->update_job_meta('backup_others_filelist',$others_file_list);
	$backup_job->update_job_meta('backup_others_filelist_remaining',$others_file_list);
	$others_file_list=null;


	set_status('preparing',$complete,false);
	$backup_job->set_task_complete();

	$logger->log('**END CLEANUP**');
	return;
}


//Backup the database
if ('task_backup_db'==$current_task) {
	$logger->log( '**BEGIN SQL EXPORT**' );
	write_response_processing( "Create database export" );
	set_status( 'backupdb', $active, true );

	if ( ! $wp_backup->export_database() ) {
		$backup_job->set_task_error('104');

		write_fatal_error_status( '104' );
		//cleanup_on_failure( $wp_backup->backup_project_path );
		end_backup( 104, false );
	}

	set_status('backupdb',$complete,false);
	$backup_job->set_task_complete();

	$logger->log('**END SQL EXPORT**');
	return;

}

//Extract the site info
if ('task_backup_siteinfo'==$current_task) {
	$logger->log( '**SITE INFO**' );
	write_response_processing( "Retrieve Site Info" );
	set_status( 'infofile', $active, true );

	if ( $wp_backup->create_siteinfo_file()  ) {

		//Add site Info and SQL data to main zip
		$site_data_suffix='main';
		$source_site_data_root = $wp_backup->backup_project_path;
		$target_site_data_root = 'site-data';

		$site_data_files = array_filter(glob($wp_backup->backup_project_path. '*.{txt,sql}',GLOB_BRACE), 'is_file');
		$site_data_complete = $wp_backup->backup_file_list( $source_site_data_root, $target_site_data_root, $site_data_suffix, $site_data_files, WPBACKITUP__PLUGINS_BATCH_SIZE );
		if ( $site_data_complete == 'error' ) {
			$backup_job->set_task_error('105');

			write_fatal_error_status( '105' );
			//cleanup_on_failure( $wp_backup->backup_project_path );
			end_backup( 105, false );
		}
	} else {
		//Site data could be extracted
		$backup_job->set_task_error('105');

		write_fatal_error_status( '105' );
		//cleanup_on_failure( $wp_backup->backup_project_path );
		end_backup( 105, false );
	}

	//get rid of the SQL and sitedata file - will check again at end in cleanup
	$wp_backup->delete_site_data_files();

	set_status( 'infofile', $complete, false );
	$backup_job->set_task_complete();

	$logger->log( '**END SITE INFO**' );
	return;

}

//Backup the themes
if ('task_backup_themes'==$current_task) {
	$logger->log('**BACKUP THEMES TASK**' );
	write_response_processing( "Backup themes " );
	set_status( 'backup_themes', $active, true );

	$source_themes_root = WPBACKITUP__THEMES_ROOT_PATH;
	$target_theme_root = 'wp-content-themes';
	$themes_suffix='themes';
	$themes_file_list = $backup_job->get_job_meta('backup_themes_filelist_remaining');
	$themes_file_list_count= count($themes_file_list);

	$themes_remaining_files = $wp_backup->backup_file_list($source_themes_root,$target_theme_root,$themes_suffix,$themes_file_list,WPBACKITUP__THEMES_BATCH_SIZE);
	if ($themes_remaining_files=='error') {
		//ERROR
		$logger->log_error(__METHOD__,'Error backing up themes.');
		$backup_job->set_task_error('120');
		write_fatal_error_status( '120' );
		//cleanup_on_failure( $wp_backup->backup_project_path );
		end_backup( 120, false );
	}else{
		//update the file list with remaining files
		$backup_job->update_job_meta('backup_themes_filelist_remaining',$themes_remaining_files);

		$themes_remaining_files_count= count($themes_remaining_files);
		$themes_batch_count = $themes_file_list_count-$themes_remaining_files_count;
		$logger->log('Backed up in this batch:' .$themes_batch_count);

		$logger->log('Themes remaining:' .$themes_remaining_files_count);
		if ($themes_remaining_files_count>0){
			//CONTINUE
			$logger->log_info(__METHOD__,'Continue backing up themes.');
			$backup_job->set_task_queued();
		}else{
			//COMPLETE
			$logger->log_info(__METHOD__,'Complete - All themes backed up.');

			set_status( 'backup_themes', $complete, false );
			$backup_job->set_task_complete();
			$logger->log('**END BACKUP THEMES TASK**');
		}
	}

	return;
}

//Backup the plugins
if ('task_backup_plugins'==$current_task) {
	$logger->log( '**BACKUP PLUGINS TASK**' );
	write_response_processing( "Backup plugins " );
	set_status( 'backup_plugins', $active, true );

	$source_plugins_root = WPBACKITUP__PLUGINS_ROOT_PATH;
	$target_plugins_root = 'wp-content-plugins';
	$plugins_suffix='plugins';
	$plugins_file_list = $backup_job->get_job_meta('backup_plugins_filelist_remaining');
	$plugins_file_list_count= count($plugins_file_list);

	$plugins_remaining_files = $wp_backup->backup_file_list($source_plugins_root,$target_plugins_root,$plugins_suffix,$plugins_file_list,WPBACKITUP__PLUGINS_BATCH_SIZE);
	if ($plugins_remaining_files=='error') {
		//ERROR
		$logger->log('Error backing up plugins.');

		$backup_job->set_task_error('121');
		write_fatal_error_status( '121' );
		//cleanup_on_failure( $wp_backup->backup_project_path );
		end_backup( 121, false );
	} else {
		//update the file list with remaining files
		$backup_job->update_job_meta('backup_plugins_filelist_remaining',$plugins_remaining_files);

		$plugins_remaining_files_count= count($plugins_remaining_files);
		$plugins_batch_count = $plugins_file_list_count-$plugins_remaining_files_count;
		$logger->log('Backed up in this batch:' .$plugins_batch_count);

		$logger->log('Plugins remaining:' .$plugins_remaining_files_count);
		if ($plugins_remaining_files_count>0){
			//CONTINUE
			$logger->log('Continue backing up plugins.');
			$backup_job->set_task_queued();
		} else{
			//COMPLETE
			$logger->log('Complete - All plugins backed up.');
			set_status( 'backup_plugins', $complete, false );
			$backup_job->set_task_complete();
			$logger->log('**END BACKUP PLUGINS TASK**');
		}
	}

	return;
}


//Backup the uploads
if ('task_backup_uploads'==$current_task) {
	$logger->log( '**BACKUP UPLOADS TASK**' );
	write_response_processing( "Backup uploads " );
	set_status( 'backup_uploads', $active, true );

	$upload_array        = wp_upload_dir();
	$source_uploads_root = $upload_array['basedir'];
	$target_uploads_root = 'wp-content-uploads';
	$uploads_suffix      = 'uploads';

	$uploads_file_list       = $backup_job->get_job_meta( 'backup_uploads_filelist_remaining' );
	$uploads_file_list_count = count( $uploads_file_list );

	$batch_size = $WPBackitup->backup_batch_size();

	//exclude zip files from backup
	$uploads_remaining_files = $wp_backup->backup_file_list( $source_uploads_root, $target_uploads_root, $uploads_suffix, $uploads_file_list,$batch_size,'.zip' );
	if ( $uploads_remaining_files == 'error' ) {
		//ERROR
		$logger->log( 'Error backing up uploads.' );
		$backup_job->set_task_error( '122' );
		write_fatal_error_status( '122' );
		//cleanup_on_failure( $wp_backup->backup_project_path );
		end_backup( 122, false );
	} else {
		//update the file list with remaining files
		$backup_job->update_job_meta( 'backup_uploads_filelist_remaining',$uploads_remaining_files);

		$uploads_remaining_files_count = count( $uploads_remaining_files );
		$uploads_batch_count           = $uploads_file_list_count - $uploads_remaining_files_count;
		$logger->log( 'Backed up in this batch:' . $uploads_batch_count );
		$logger->log( 'Remaining Uploads:' . $uploads_remaining_files_count );
		if ( $uploads_remaining_files_count > 0 ) {
			//CONTINUE
			$logger->log( 'Continue backing up uploads.' );
			$backup_job->set_task_queued();

		} else {
			//COMPLETE
			$logger->log( 'All uploads backed up.' );
			set_status( 'backup_uploads', $complete, false );
			$backup_job->set_task_complete();
			$logger->log( '**END BACKUP UPLOADS TASK**' );
		}
	}

	return;
}

//Backup all the other content in the wp-content root
if ('task_backup_other'==$current_task) {
	$logger->log( '**BACKUP OTHER TASK**' );
	write_response_processing( "Backup other files " );
	set_status( 'backup_other', $active, true );

	$source_others_root = WPBACKITUP__CONTENT_PATH;
	$target_others_root = 'wp-content-other';
	$others_suffix      = 'others';

	$others_file_list       = $backup_job->get_job_meta( 'backup_others_filelist_remaining' );
	$others_file_list_count = count( $others_file_list );

	$batch_size = $WPBackitup->backup_batch_size();

	//exclude zip files from backup
	$others_remaining_files = $wp_backup->backup_file_list( $source_others_root, $target_others_root, $others_suffix, $others_file_list, $batch_size,'.zip'  );
	if ( $others_remaining_files == 'error' ) {
		//ERROR
		$logger->log( 'Error backing up others.' );
		$backup_job->set_task_error( '123' );

		write_fatal_error_status( '123' );
		//cleanup_on_failure( $wp_backup->backup_project_path );
		end_backup( 123, false );
	} else {
		//update the file list with remaining files
		$backup_job->update_job_meta( 'backup_others_filelist_remaining', $others_remaining_files );

		$others_remaining_files_count = count( $others_remaining_files );
		$others_batch_count           = $others_file_list_count - $others_remaining_files_count;
		$logger->log( 'Backed up in this batch:' . $others_batch_count );
		$logger->log( 'Remaining Others:' . $others_remaining_files_count );
		if ( $others_remaining_files_count > 0 ) {
			//CONTINUE
			$logger->log( 'Continue backing up others.' );
			$backup_job->set_task_queued();
		} else {
			//COMPLETE
			$logger->log( 'All others backed up.' );

			set_status( 'backup_other', $complete, false );
			$backup_job->set_task_complete();
			$logger->log( '**END BACKUP OTHER TASK**' );
		}

	}

	return;
}

//ENCRYPT CONTENT TASK
//wp-config.php
//db backup

//Validate the backup IF logging is turned on - reporting only
if ('task_validate_backup'==$current_task) {
	//Validate the content if logging is on
	$logger->log('**VALIDATE CONTENT**');

	write_response_processing( "Validating Backup " );
	set_status( 'validate_backup', $active, true );

	if ($WPBackitup->logging()){
		//$wp_backup->validate_backup();  --HOW DO I DO THIS
	}

	sleep(5);//temp UI only

	set_status( 'validate_backup', $complete, false );
	$backup_job->set_task_complete();
	$logger->log('**END VALIDATE CONTENT**');

	return;
}

//Zip up the backup folder
if ('task_finalize_backup'==$current_task) {
	$logger->log( '**FINALIZE BACKUP**' );
	write_response_processing( "Compress Backup " );
	set_status( 'finalize_backup', $active, true );

	//Generate manifest
	if ( ! $wp_backup->create_backup_manifest()) {
		$backup_job->set_task_error('109');

		write_fatal_error_status( '109' );
		//cleanup_on_failure( $wp_backup->backup_project_path );
		end_backup( 109, false );
	}

	if ( ! $wp_backup->cleanup_current_backup()  ) {
		//Warning - no need to error job
		write_warning_status( '106' );
	}

	//Rename backup folder
	if ( ! $wp_backup->rename_backup_folder()) {
		$backup_job->set_task_error('109');

		write_fatal_error_status( '109' );
		//cleanup_on_failure( $wp_backup->backup_project_path );
		end_backup( 109, false );
	}

	set_status( 'finalize_backup', $complete, false );
	$backup_job->set_task_complete();

	$logger->log( '**END FINALIZE BACKUP**' );

}

//If we get this far we have a finalized backup so change the path
$wp_backup->set_final_backup_path();

//Cleanup work folders - handled in cleanup jobs now
//if ('task_cleanup_current'==$current_task) {
//	$logger->log( '**CLEANUP**' );
//
//	write_response_processing( "Cleanup after Backup " );
//	set_status( 'cleanup', $active, true );
//
//	//Check retention limits and cleanup
//	$wp_backup->purge_old_files();
//
//	set_status( 'cleanup', $complete, false );
//	$backup_job->set_task_complete();
//
//	$logger->log( '**END CLEANUP**' );
//}

if ($backup_job->get_job_status()=='complete') {
	//SUCCESS- End Job!

	//write response file first to make sure it is there
	write_response_file_success();
	set_status_success();

	$WPBackitup->increment_successful_backup_count();
	end_backup( null, true );
}

exit();
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
function end_backup($err=null, $success=null){
    global $WPBackitup,$wp_backup, $logger, $backup_job;
    $logger->log_info(__METHOD__,"Begin");

	$logger->log('Zip up all the logs.');
	//Zip up all the logs in the log folder
	$logs_path = WPBACKITUP__PLUGIN_PATH .'logs';
	$zip_file_path = $wp_backup->backup_project_path .'logs_' .$backup_job->backup_id . '.zip';

	//copy/replace WP debug file
	$wpdebug_file_path = WPBACKITUP__CONTENT_PATH . '/debug.log';
	$logger->log_info(__METHOD__,"Copy WP Debug: " .$wpdebug_file_path);
	if (file_exists($wpdebug_file_path)) {
		copy( $wpdebug_file_path, $logs_path .'/wpdebug.log' );
	}

	$zip = new WPBackItUp_Zip($logger,$zip_file_path);
	$zip->zip_files_in_folder($logs_path,$backup_job->backup_id,'*.log');
	$zip->close();

	WPBackItUp_Backup::end(); //Release the lock
	$current_datetime = current_time( 'timestamp' );
	$WPBackitup->set_backup_lastrun_date($current_datetime);

    $util = new WPBackItUp_Utility($logger);
    $seconds = $util->timestamp_diff_seconds($backup_job->get_job_start_time(),$backup_job->get_job_end_time());

    $processing_minutes = round($seconds / 60);
    $processing_seconds = $seconds % 60;

    $logger->log('Script Processing Time:' .$processing_minutes .' Minutes ' .$processing_seconds .' Seconds');

    if (true===$success) $logger->log("Backup completed: SUCCESS");
	if (false===$success) $logger->log("Backup completed: ERROR");

	$logger->log("*** END BACKUP ***");

	//Send Notification email
	$logger->log('Send Email notification');
	$logs_attachment = array( $zip_file_path  );
	send_backup_notification_email($err, $success,$logs_attachment);

    $logFileName = $logger->logFileName;
    $logFilePath = $logger->logFilePath;
    $logger->close_file();

    //COPY the log if it exists
    $newlogFilePath = $wp_backup->backup_project_path .$logFileName;
    if (null!=$success && file_exists($logFilePath)){
	    copy($logFilePath,$newlogFilePath);
    }

    echo('Backup has completed');
    exit(0);
}

function send_backup_notification_email($err, $success,$logs=array()) {
	global $WPBackitup, $wp_backup, $logger,$status_array,$backup_job;
    $logger->log_info(__METHOD__,"Begin");

	$start_timestamp = $backup_job->get_job_start_time();
	$end_timestamp = $backup_job->get_job_end_time();
    $utility = new WPBackItUp_Utility($logger);
    $seconds = $utility->timestamp_diff_seconds($start_timestamp,$end_timestamp);

    $processing_minutes = round($seconds / 60);
    $processing_seconds = $seconds % 60;

    $status_description = array(
        'preparing'=>'Preparing for backup...Done',
        'backupdb'=>'Backing up database...Done',
        'infofile'=>'Creating backup information file...Done',
        'backup_themes'=>'Backing up themes...Done',
        'backup_plugins'=>'Backing up plugins...Done',
        'backup_uploads'=>'Backing up uploads...Done',
        'backup_other'=>'Backing up miscellaneous files...Done',
        'finalize_backup'=>'Finalizing backup...Done',
        'validate_backup'=>'Validating backup...Done',
        'cleanup'=>'Cleaning up...Done'
    );

	if($success)
	{
		//Don't send logs on success unless debug is on.
		if (WPBACKITUP__DEBUG!==true){
			$logs=array();
		}

        $subject = get_bloginfo() . ' - Backup completed successfully.';
        $message = '<b>Your backup completed successfully.</b><br/><br/>';

    } else  {
        $subject = get_bloginfo() .' - Backup did not complete successfully.';
        $message = '<b>Your backup did not complete successfully.</b><br/><br/>';
    }

	$local_start_datetime = get_date_from_gmt(date( 'Y-m-d H:i:s',$start_timestamp));
	$local_end_datetime = get_date_from_gmt(date( 'Y-m-d H:i:s',$end_timestamp));
	$message .= 'WordPress Site: <a href="'  . home_url() . '" target="_blank">' . home_url() .'</a><br/>';
    $message .= 'Backup date: '  . $local_start_datetime . '<br/>';
	$message .= 'Number of backups completed with WP BackItUp: '  . $WPBackitup->backup_count() . '<br/>';

	$message .= 'Completion Code: ' . $backup_job->backup_id .'-'. $processing_minutes .'-' .$processing_seconds .'<br/>';
	$message .= 'WP BackItUp Version: '  . WPBACKITUP__VERSION . '<br/>';
    $message .= '<br/>';


	//Add the completed steps on success
	if($success) {
	    $message .='<b>Steps Completed</b><br/>';

	    //Add the completed statuses
	    foreach ($status_array as $status_key => $status_value) {
	        if ($status_value==2) {
	            foreach ($status_description as $msg_key => $msg_value) {
	                if ($status_key==$msg_key) {
	                    $message .=  $msg_value . '<br/>';
	                    break;
	                }
	            }
	         }
	    }
	} else  {
		//Error occurred
        $message .= '<br/>';
        $message .= 'Errors:<br/>' . get_error_message($err);
	}

    $term='success';
    if(!$success)$term='error';
      $message .='<br/><br/>Checkout '. $WPBackitup->get_anchor_with_utm('www.wpbackitup.com', '', 'notification+email', $term) .' for info about WP BackItUp and our other products.<br/>';


	$notification_email = $WPBackitup->get_option('notification_email');
	if($notification_email)
		$utility->send_email($notification_email,$subject,$message,$logs);

    $logger->log_info(__function__,"End");
}

function cleanup_on_failure($path){
	global $logger;
    global $wp_backup;

	if (WPBACKITUP__DEBUG===true){
		$logger->log('Cleanup On Fail suspended: debug on.');
	}
	else{
        $wp_backup->cleanup_backups_by_prefix('TMP_');
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

	write_status();
	write_response_file_error($status_code);
}

function write_warning_status($status_code) {
	global $status_array,$active,$warning;

	//Find the active status and set to failure
	foreach ($status_array as $key => $value) {
		if ($value==$active){
			$status_array[$key]=$warning;
		}
	}

	write_status();
}

//function write_warning_status($status_code) {
//	global $status_array,$warning;
//
//	//Add warning to array
//	$status_array[$status_code]=$warning;
//	write_status();
//}

function write_status() {
	global $status_array;
	$fh=getStatusLog();

	foreach ($status_array as $key => $value) {
		fwrite($fh, '<div class="' . $key . '">' . $value .'</div>');		
	}

	fclose($fh);
}

function set_status($process,$status,$flush){
	global $status_array,$complete;

	$status_array[$process]=$status;

	//Mark all the others complete and flush
	foreach ($status_array as $key => $value) {
		if ($process==$key) {
			break;
		}else{
			$status_array[$key]=$complete;
		}
	}

	if ($flush) write_status(); 
}

function set_status_success(){
	global $status_array,$complete,$success;

	//Mark all the others complete and flush
	foreach ($status_array as $key => $value) {
		$status_array[$key]=$complete;
	}

	$status_array['finalinfo']=$success;
	write_status();
}

//Get Status Log
function getStatusLog(){
	global $logger;

	$status_file_path = WPBACKITUP__PLUGIN_PATH .'/logs/backup_status.log';
	$filesystem = new WPBackItUp_FileSystem($logger);
	return $filesystem->get_file_handle($status_file_path);

}

//write Response Log
function write_response_processing($message) {

    $jsonResponse = new stdClass();
	$jsonResponse->backupStatus = 'processing';
    $jsonResponse->backupMessage = $message;

	write_response_file($jsonResponse);
}


//write Response Log
function write_response_file_error($error) {

	$jsonResponse = new stdClass();
	$jsonResponse->backupStatus = 'error';
	$jsonResponse->backupMessage = get_error_message($error);

	write_response_file($jsonResponse);
}

//write Response Log
function write_response_file_success() {
    global $WPBackitup,$wp_backup,$logger;

    $jsonResponse = new stdClass();
	$jsonResponse->backupStatus = 'success';
    $jsonResponse->backupMessage = 'success';
    $jsonResponse->backupName = $wp_backup->backup_name;
    $jsonResponse->backupLicense = $WPBackitup->license_active();
    $jsonResponse->backupRetained = $wp_backup->backup_retained_number;

	$jsonResponse->logFileExists = file_exists($logger->logFilePath);

	write_response_file($jsonResponse);
}

//write Response Log
function write_response_file($JSON_Response) {
	global $logger;

	$json_response = json_encode($JSON_Response);
	$logger->log('Write response file:' . $json_response);

	$fh=get_response_file();
	fwrite($fh, $json_response);
	fclose($fh);
}

//Get Response Log
function get_response_file() {
    global $logger;
    $response_file_path = WPBACKITUP__PLUGIN_PATH .'logs/backup_response.log';
    $filesytem = new WPBackItUp_FileSystem($logger);
    return $filesytem->get_file_handle($response_file_path,false);
}


/**
 * Get error message
 *
 * @param $error_code
 *
 * @return string
 */
function get_error_message($error_code){

	$error_message_array = array(
		'101' =>'(101) Unable to create a new directory for backup. Please check your CHMOD settings of your wp-backitup backup directory',
		'102'=> '(102) Cannot create backup directory. Please check the CHMOD settings of your wp-backitup plugin directory',
		'103'=> '(103) Unable to backup your files. Please try again',
		'104'=> '(104) Unable to export your database. Please try again',
		'105'=> '(105) Unable to export site information file. Please try again',
		'106'=> '(106) Unable to cleanup your backup directory',
		'107'=> '(107) Unable to compress(zip) your backup. Please try again',
		'108'=> '(108) Unable to backup your site data files. Please try again',
		'109'=> '(109) Unable to finalize backup. Please try again',
		'114'=> '(114) Your database was accessible but an export could not be created. Please contact support by clicking the get support link on the right. Please let us know who your host is when you submit the request',
		'120'=> '(120) Unable to backup your themes. Please try again',
		'121'=> '(121) Unable to backup your plugins. Please try again',
		'122'=> '(122) Unable to backup your uploads. Please try again',
		'123'=> '(123) Unable to backup your miscellaneous files. Please try again',
		'125'=> '(125) Unable to compress your backup because there is no zip utility available.  Please contact support',
		'126'=> '(126) Unable to validate your backup. Please try again',

		'2101' =>'(2101) Unable to create a new directory for backup. Please check your CHMOD settings of your wp-backitup backup directory',
		'2102'=> '(2102) Cannot create backup directory. Please check the CHMOD settings of your wp-backitup plugin directory',
		'2103'=> '(2103) Unable to backup your files. Please try again',
		'2104'=> '(2104) Unable to export your database. Please try again',
		'2105'=> '(2105) Unable to export site information file. Please try again',
		'2106'=> '(2106) Unable to cleanup your backup directory',
		'2107'=> '(2107) Unable to compress(zip) your backup. Please try again',
		'2108'=> '(2108) Unable to backup your site data files. Please try again',
		'2109'=> '(2109) Unable to finalize backup. Please try again',
		'2114'=> '(2114) Your database was accessible but an export could not be created. Please contact support by clicking the get support link on the right. Please let us know who your host is when you submit the request',
		'2120'=> '(2120) Unable to backup your themes. Please try again',
		'2121'=> '(2121) Unable to backup your plugins. Please try again',
		'2122'=> '(2122) Unable to backup your uploads. Please try again',
		'2123'=> '(2123) Unable to backup your miscellaneous files. Please try again',
		'2125'=> '(2125) Unable to compress your backup because there is no zip utility available.  Please contact support',
		'2126'=> '(2126) Unable to validate your backup. Please try again',
	);

	$error_message = '(999) Unexpected error';
	if (array_key_exists($error_code,$error_message_array)) {
		$error_message = $error_message_array[ $error_code ];
	}

	return $error_message;
}



