<?php if (!defined ('ABSPATH')) die('No direct access allowed');

// Checking safe mode is on/off and set time limit
if( ini_get('safe_mode') ){
   @ini_set('max_execution_time', WPBACKITUP__SCRIPT_TIMEOUT_SECONDS);
}else{
   @set_time_limit(WPBACKITUP__SCRIPT_TIMEOUT_SECONDS);
}

/**
 * WP BackItUp  - Backup Job
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

if( !class_exists( 'WPBackItUp_DataAccess' ) ) {
	include_once 'class-database.php';
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
$process_id = uniqid();

//Make sure backup is NOT already running before you run the current task

//Scheduled the next check
if ('scheduled'==$this->backup_type){
	wp_schedule_single_event( time()+30, 'wpbackitup_run_backup_tasks');
}

$backup_task_log = 'debug_backup_tasks';
if (!WPBackItUp_Backup::start()) {
	WPBackItUp_LoggerV2::log_info($backup_task_log,$process_id ,'Backup job cant acquire job lock.');
	return; //nothing to do
}else{
	WPBackItUp_LoggerV2::log_info($backup_task_log,$process_id,'Backup job lock acquired.');
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

WPBackItUp_LoggerV2::log_info($backup_task_log,$process_id,'Check for available backup job');
if ($backup_job){

	//Get the next task in the stack
	$next_task = $backup_job->get_next_task();
	if (false!==$next_task){
		$backup_id=$backup_job->backup_id;
		$current_task=$next_task;

		//If task contains error then timeout has occurred
		if (strpos($current_task,'error') !== false){
			WPBackItUp_LoggerV2::log_info($backup_task_log,$process_id,'Backup Error Found:' .$current_task);
			$backup_error=true;
		}

		WPBackItUp_LoggerV2::log_info($backup_task_log,$process_id,'Available Task Found:' . $current_task);

	}else{
		WPBackItUp_LoggerV2::log_info($backup_task_log,$process_id,'No available tasks found.');
		WPBackItUp_Backup::end(); //release lock
		return;
	}
}else {
	WPBackItUp_LoggerV2::log_info($backup_task_log,$process_id,'No backup job available.');

	wp_clear_scheduled_hook( 'wpbackitup_run_backup_tasks');
	WPBackItUp_Backup::end(); //release lock
	return;
}

//Should only get here when there is a task to run
WPBackItUp_LoggerV2::log_info($backup_task_log,$process_id,'Run Backup task:' .$current_task);

//*************************//
//*** MAIN BACKUP CODE  ***//
//*************************//
global $backup_logname;
//Get the backup ID
$backup_name =  get_backup_name($backup_job->backup_id);
$backup_logname = $backup_name;

$log_function='job_backup::'.$current_task;

global $wp_backup;
$wp_backup = new WPBackItUp_Backup($backup_logname,$backup_name,$WPBackitup->backup_type);


//*************************//
//***   BACKUP TASKS    ***//
//*************************//

//An error has occurred on the previous tasks
if ($backup_error) {
	$error_task = substr($current_task,6);
	WPBackItUp_LoggerV2::log_error($backup_logname,$log_function,'Fatal error on previous task:'. $error_task);

	//Fetch last wordpress error(might not be related to timeout)
	//error type constants: http://php.net/manual/en/errorfunc.constants.php
	WPBackItUp_LoggerV2::log_info($backup_logname,__METHOD__,'Last Error: ' .var_export(error_get_last(),true));

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
	WPBackItUp_LoggerV2::log($backup_logname,'***BEGIN BACKUP***');
	WPBackItUp_LoggerV2::log_sysinfo($backup_logname);
	WPBackItUp_LoggerV2::log_info($backup_logname,$log_function,'BACKUP TYPE:' .$wp_backup->backup_type);
	WPBackItUp_LoggerV2::log_info($backup_logname,$log_function,'BACKUP ID:' .$backup_job->backup_id);

	$WPBackitup->increment_backup_count();
	//End Init

	WPBackItUp_LoggerV2::log($backup_logname,'**BEGIN CLEANUP**');

	//Cleanup & Validate the backup folded is ready
	write_response_processing("preparing for backup");
	set_status('preparing',$active,true);

	write_response_processing("Cleanup before backup");

	//*** Check Dependencies ***
	if (!WPBackItUp_Zip::zip_utility_exists()) {
		WPBackItUp_LoggerV2::log_error($backup_logname,$log_function,'Zip Util does not exist.' );
		$backup_job->set_task_error('125');
		write_fatal_error_status( '125' );
		end_backup( 125, false );
	}

	//*** END Check Dependencies ***


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

	// ** Generate the list of files to be backed up **

	//Create a job control record
	if ( ! $wp_backup->create_job_control($backup_job->backup_id)){
		WPBackItUp_LoggerV2::log_error($backup_logname,$log_function,'Cant create batch record');
		$backup_job->set_task_error('128');

		write_fatal_error_status('128');
		end_backup(128,false);
	};

	$global_exclude = explode(',', WPBACKITUP__BACKUP_GLOBAL_IGNORE_LIST);

	$plugin_exclude = array_merge (
		$global_exclude,
		array(
			"wp-backitup",
		)
	);
	if (! $wp_backup->save_folder_inventory(WPBACKITUP__SQL_BULK_INSERT_SIZE,$backup_job->backup_id,'plugins',WPBACKITUP__PLUGINS_ROOT_PATH,$plugin_exclude)){
		WPBackItUp_LoggerV2::log_error($backup_logname,$log_function,'Plugins Inventory Error.');
		$backup_job->set_task_error('127');

		write_fatal_error_status('127');
		end_backup(127,false);
	};

	$theme_exclude = WPBACKITUP__BACKUP_GLOBAL_IGNORE_LIST;
	if (! $wp_backup->save_folder_inventory(WPBACKITUP__SQL_BULK_INSERT_SIZE,$backup_job->backup_id,'themes',WPBACKITUP__THEMES_ROOT_PATH,$theme_exclude)){
		WPBackItUp_LoggerV2::log_error($backup_logname,$log_function,'Themes Inventory Error.');
		$backup_job->set_task_error('127');

		write_fatal_error_status('127');
		end_backup(127,false);
	};

	$upload_exclude = array_merge (
		$global_exclude,
		array(
			"backup",
			"backwpup",
		));

	$upload_array = wp_upload_dir();
	$uploads_root_path = $upload_array['basedir'];
	if (! $wp_backup->save_folder_inventory(WPBACKITUP__SQL_BULK_INSERT_SIZE,$backup_job->backup_id,'uploads',$uploads_root_path,$upload_exclude)){
		WPBackItUp_LoggerV2::log_error($backup_logname,$log_function,'Uploads Inventory Error.');
		$backup_job->set_task_error('127');

		write_fatal_error_status('127');
		end_backup(127,false);
	};

	$other_exclude = array_merge (
		$global_exclude,
		array(
		"debug.log",
		"plugins",
		"themes",
		"uploads",
		"wpbackitup_backups",
		"wpbackitup_restore",
		"backup",
		"w3tc-config",
		"updraft",
		"wp-clone",
		"backwpup",
		"backupwordpress",
		"cache",
		"backupcreator",
		"backupbuddy",
		"wptouch-data",
		"ai1wm-backups",
		"sedlex",
	));

	if (! $wp_backup->save_folder_inventory(WPBACKITUP__SQL_BULK_INSERT_SIZE,$backup_job->backup_id,'others',WPBACKITUP__CONTENT_PATH,$other_exclude)){
		WPBackItUp_LoggerV2::log_error($backup_logname,$log_function,'Other Inventory Error.');
		$backup_job->set_task_error('127');

		write_fatal_error_status('127');
		end_backup(127,false);
	};

	set_status('preparing',$complete,false);
	$backup_job->set_task_complete();

	WPBackItUp_LoggerV2::log($backup_logname,'**END CLEANUP**');
	return;
}

//Backup the database
if ('task_backup_db'==$current_task) {
	WPBackItUp_LoggerV2::log($backup_logname,'**BEGIN SQL EXPORT**');
	write_response_processing( "Create database export" );
	set_status( 'backupdb', $active, true );

	$export_database = $wp_backup->export_database();
	WPBackItUp_LoggerV2::log_info($backup_logname,$log_function,'Export Database return:' .var_export($export_database,true));
	if ( ! $export_database ) {
		$backup_job->set_task_error('104');

		write_fatal_error_status( '104' );
		//cleanup_on_failure( $wp_backup->backup_project_path );
		end_backup( 104, false );
	}

	WPBackItUp_LoggerV2::log_info($backup_logname,$log_function,'Database Export complete.');

	set_status('backupdb',$complete,false);
	$backup_job->set_task_complete();

	WPBackItUp_LoggerV2::log($backup_logname,'**END SQL EXPORT**');
	return;

}



//Extract the site info
if ('task_backup_siteinfo'==$current_task) {
	WPBackItUp_LoggerV2::log($backup_logname,'**SITE INFO**' );
	write_response_processing( "Retrieve Site Info" );
	set_status( 'infofile', $active, true );

	if ( $wp_backup->create_siteinfo_file()  ) {

		//Add site Info and SQL data to main zip
		$suffix='main';
		$source_site_data_root = $wp_backup->backup_project_path;
		$target_site_data_root = 'site-data';

		$file_system = new WPBackItUp_FileSystem($backup_logname);
		$site_data_files = $file_system->get_fileonly_list($wp_backup->backup_project_path, 'txt|sql');
		$site_data_complete = $wp_backup->backup_file_list( $source_site_data_root, $target_site_data_root, $suffix, $site_data_files, WPBACKITUP__OTHERS_BATCH_SIZE );
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
	$wp_backup->cleanup_current_backup();

	set_status( 'infofile', $complete, false );
	$backup_job->set_task_complete();

	WPBackItUp_LoggerV2::log($backup_logname,'**END SITE INFO**' );
	return;

}

//Backup the themes
if ('task_backup_themes'==$current_task) {
	WPBackItUp_LoggerV2::log($backup_logname,'**BACKUP THEMES TASK**' );
	write_response_processing( "Backup themes " );
	set_status( 'backup_themes', $active, true );

	$themes_remaining_files_count = $wp_backup->backup_files($backup_job->backup_id,WPBACKITUP__THEMES_ROOT_PATH,'themes');
	WPBackItUp_LoggerV2::log_info($backup_logname,$log_function,'Themes remaining:' .$themes_remaining_files_count);
	if ($themes_remaining_files_count===false) {
		//ERROR
		WPBackItUp_LoggerV2::log_error($backup_logname,$log_function,'Error backing up themes.');
		$backup_job->set_task_error('120');
		write_fatal_error_status( '120' );
		//cleanup_on_failure( $wp_backup->backup_project_path );
		end_backup( 120, false );
	}else{
		if ($themes_remaining_files_count>0){
			//CONTINUE
			WPBackItUp_LoggerV2::log_info($backup_logname,$log_function,'Continue backing up themes.');
			$backup_job->set_task_queued();
		}else{
			//COMPLETE
			WPBackItUp_LoggerV2::log_info($backup_logname,$log_function,'Complete - All themes backed up.');

			set_status( 'backup_themes', $complete, false );
			$backup_job->set_task_complete();
			WPBackItUp_LoggerV2::log($backup_logname,'**END BACKUP THEMES TASK**');
		}
	}

	return;
}


//Backup the plugins
if ('task_backup_plugins'==$current_task) {
	WPBackItUp_LoggerV2::log($backup_logname,'**BACKUP PLUGINS TASK**' );
	write_response_processing( "Backup plugins " );
	set_status( 'backup_plugins', $active, true );

	$plugins_remaining_files_count = $wp_backup->backup_files($backup_job->backup_id,WPBACKITUP__PLUGINS_ROOT_PATH,'plugins');
	WPBackItUp_LoggerV2::log_info($backup_logname,$log_function,'Plugins remaining:' .$plugins_remaining_files_count);
	if ($plugins_remaining_files_count===false) {
		//ERROR
		WPBackItUp_LoggerV2::log_error($backup_logname,$log_function,'Error backing up plugins.');

		$backup_job->set_task_error('121');
		write_fatal_error_status( '121' );
		//cleanup_on_failure( $wp_backup->backup_project_path );
		end_backup( 121, false );
	} else {
		if ($plugins_remaining_files_count>0){
			//CONTINUE
			WPBackItUp_LoggerV2::log_info($backup_logname,$log_function,'Continue backing up plugins.');
			$backup_job->set_task_queued();
		} else{
			//COMPLETE
			WPBackItUp_LoggerV2::log_info($backup_logname,$log_function,'Complete - All plugins backed up.');
			set_status( 'backup_plugins', $complete, false );
			$backup_job->set_task_complete();
			WPBackItUp_LoggerV2::log($backup_logname,'**END BACKUP PLUGINS TASK**');
		}
	}

	return;
}

//Backup the uploads
if ('task_backup_uploads'==$current_task) {
	WPBackItUp_LoggerV2::log($backup_logname,'**BACKUP UPLOADS TASK**' );
	write_response_processing( "Backup uploads " );
	set_status( 'backup_uploads', $active, true );

	$upload_array        = wp_upload_dir();
	$source_uploads_root = $upload_array['basedir'];

	//exclude zip files from backup
	$uploads_remaining_files_count = $wp_backup->backup_files($backup_job->backup_id,$source_uploads_root,'uploads');
	WPBackItUp_LoggerV2::log_info($backup_logname,$log_function,'Uploads remaining:' .$uploads_remaining_files_count);
	if ( $uploads_remaining_files_count ===false) {
		//ERROR
		WPBackItUp_LoggerV2::log_error($backup_logname,$log_function,'Error backing up uploads.' );
		$backup_job->set_task_error( '122' );
		write_fatal_error_status( '122' );
		end_backup( 122, false );
	} else {
		if ( $uploads_remaining_files_count > 0 ) {
			//CONTINUE
			WPBackItUp_LoggerV2::log_info($backup_logname,$log_function,'Continue backing up uploads.' );
			$backup_job->set_task_queued();

		} else {
			//COMPLETE
			WPBackItUp_LoggerV2::log_info($backup_logname,$log_function,'All uploads backed up.' );
			set_status( 'backup_uploads', $complete, false );
			$backup_job->set_task_complete();
			WPBackItUp_LoggerV2::log($backup_logname,'**END BACKUP UPLOADS TASK**' );
		}
	}

	return;
}

//Backup all the other content in the wp-content root
if ('task_backup_other'==$current_task) {
	WPBackItUp_LoggerV2::log($backup_logname,'**BACKUP OTHER TASK**' );
	write_response_processing( "Backup other files " );
	set_status( 'backup_other', $active, true );

	$others_remaining_files_count = $wp_backup->backup_files($backup_job->backup_id,WPBACKITUP__CONTENT_PATH,'others');
	WPBackItUp_LoggerV2::log_info($backup_logname,$log_function,'Others remaining:' .$others_remaining_files_count);
	if ( $others_remaining_files_count ===false) {
		//ERROR
		WPBackItUp_LoggerV2::log_error($backup_logname,$log_function,'Error backing up others.' );
		$backup_job->set_task_error( '123' );

		write_fatal_error_status( '123' );
		//cleanup_on_failure( $wp_backup->backup_project_path );
		end_backup( 123, false );
	} else {
		if ( $others_remaining_files_count > 0 ) {
			//CONTINUE
			WPBackItUp_LoggerV2::log_info($backup_logname,$log_function,'Continue backing up others.' );
			$backup_job->set_task_queued();
		} else {
			//COMPLETE
			WPBackItUp_LoggerV2::log_info($backup_logname,$log_function,'All others backed up.' );

			set_status( 'backup_other', $complete, false );
			$backup_job->set_task_complete();
			WPBackItUp_LoggerV2::log($backup_logname,'**END BACKUP OTHER TASK**' );
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
	WPBackItUp_LoggerV2::log($backup_logname,'**VALIDATE CONTENT**');

	write_response_processing( "Validating Backup " );
	set_status( 'validate_backup', $active, true );

	$set_validate_backup_error = false;
	$set_validate_backup_job_queue = true;
	$db = new WPBackItUp_DataAccess();

	$plugin_validation_meta = $backup_job->get_job_meta('task_multistep_validate_plugins');
	$theme_validation_meta = $backup_job->get_job_meta('task_multistep_validate_themes');
	$upload_validation_meta = $backup_job->get_job_meta('task_multistep_validate_uploads');
	$other_validation_meta = $backup_job->get_job_meta('task_multistep_validate_others');

	$validation_meta=false;
	$validation_task=false;
	if( $plugin_validation_meta != "completed" ) {
		$validation_task='plugins';
		$validation_meta=$plugin_validation_meta;
	} elseif( $theme_validation_meta != "completed" ) {
		$validation_task='themes';
		$validation_meta=$theme_validation_meta;
	} elseif( $upload_validation_meta != "completed" )  {
		$validation_task='uploads';
		$validation_meta=$upload_validation_meta;
	} elseif( $other_validation_meta != "completed" )  {
		$validation_task='others';
		$validation_meta=$other_validation_meta;
	} else {
		$set_validate_backup_job_queue = false;
	}

	if( $validation_meta !==false ) {
		$meta_task = sprintf( 'task_multistep_validate_%s', $validation_task );
		$batch_ids = $db->get_batch_ids( $backup_job->backup_id, $validation_task );
		WPBackItUp_LoggerV2::log_info( $backup_logname, $log_function, sprintf('%s Batch Ids: %s',$validation_task,var_export( $batch_ids, true )));
		//$plugin_validation_batch_ids will never be empty

		$array_index = 0;
		if ( is_numeric( $validation_meta ) ) {
			$array_index = intval( $validation_meta );
			$array_index ++;
		}


		if ( array_key_exists( $array_index, $batch_ids ) ) {
			$batch_id        = $batch_ids[ $array_index ];//get batch ID
			$validate_result = $wp_backup->validate_backup_files_by_batch_id( $backup_job->backup_id, $validation_task, $batch_id );
			if ( $validate_result === false ) {
				$set_validate_backup_error = true;
			} else {
				$backup_job->update_job_meta( $meta_task, $array_index );
				WPBackItUp_LoggerV2::log_info( $backup_logname, $log_function, sprintf('%s Content, Batch ID: %s Validated Successfully!',$validation_task,$batch_id ));
			}
		} else {
			//task is done
			$backup_job->update_job_meta( $meta_task, 'completed' );
			WPBackItUp_LoggerV2::log_info( $backup_logname, $log_function, sprintf('%s Content Validated Successfully!',$validation_task));
		}
	}

	//if error set error message
	if($set_validate_backup_error) {
		//ERROR
		WPBackItUp_LoggerV2::log_error($backup_logname,$log_function,'Content Validation ERROR.' );
		$backup_job->set_task_error( '126' );

		write_fatal_error_status( '126' );
		end_backup( 123, false );
	} elseif($set_validate_backup_job_queue === false) {
		//validation completed, delete all options
		$keys = array (
			'task_multistep_validate_plugins',
			'task_multistep_validate_themes',
			'task_multistep_validate_uploads',
			'task_multistep_validate_others'
		);
		foreach ($keys as $key) {
			delete_post_meta($backup_job->get_job_id(),$key);
		}
		//set backup status to success
		set_status( 'validate_backup', $complete, false );
		$backup_job->set_task_complete();

		WPBackItUp_LoggerV2::log_info($backup_logname,$log_function,'All Content Validated Successfully!' );
		WPBackItUp_LoggerV2::log($backup_logname,'**END VALIDATE CONTENT**' );

	} elseif ($set_validate_backup_job_queue === true) {
		$backup_job->set_task_queued();
	}

	return;
}

//Zip up the backup folder
if ('task_finalize_backup'==$current_task) {
	WPBackItUp_LoggerV2::log($backup_logname,'**FINALIZE BACKUP**' );
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

	//Take an inventory of the zip files created

	$file_system = new WPBackItUp_FileSystem($backup_logname);
	$zip_files = $file_system->get_fileonly_list($wp_backup->backup_project_path, 'zip');
	$wp_backup->save_file_list_inventory(WPBACKITUP__SQL_BULK_INSERT_SIZE,$backup_job->backup_id,'backups',$wp_backup->backup_project_path,$zip_files);

	//Combine the zip files into one file
//	$zip_remaining_files_count = $wp_backup->backup_files( $backup_job->backup_id, $wp_backup->backup_project_path, 'backup-files', 'combined' );

	set_status( 'finalize_backup', $complete, false );
	$backup_job->set_task_complete();

	WPBackItUp_LoggerV2::log($backup_logname,'**END FINALIZE BACKUP**' );

}


//If we get this far we have a finalized backup so change the path
$wp_backup->set_final_backup_path();


if ($backup_job->get_job_status()=='complete') {
	//SUCCESS- End Job!

	$wp_backup->update_job_control_complete($backup_job->backup_id);

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
    global $WPBackitup,$wp_backup,$backup_logname,$backup_job;
	WPBackItUp_LoggerV2::log_info($backup_logname,__METHOD__,'Begin');

	WPBackItUp_LoggerV2::log_info($backup_logname,__METHOD__,'Zip up all the logs.');
	//Zip up all the logs in the log folder
	$logs_path = WPBACKITUP__PLUGIN_PATH .'logs';
	$zip_file_path = $wp_backup->backup_project_path .'logs_' .$backup_job->backup_id . '.zip';

	//copy WP debug file
	$wpdebug_file_path = WPBACKITUP__CONTENT_PATH . '/debug.log';
	WPBackItUp_LoggerV2::log_info($backup_logname,__METHOD__,'Save WP Debug: ' .$wpdebug_file_path);
	if (file_exists($wpdebug_file_path)) {
		$debug_log = sprintf('%s/wpdebug_%s.log',$logs_path,$backup_job->backup_id);
		copy( $wpdebug_file_path, $debug_log );
		WPBackItUp_LoggerV2::log_info($backup_logname,__METHOD__,'WP Debug file saved: ' .$debug_log);
	}else{
		WPBackItUp_LoggerV2::log_info($backup_logname,__METHOD__,'NO WP Debug file: ' .$wpdebug_file_path);
	}

	$zip = new WPBackItUp_Zip($backup_logname,$zip_file_path);
	$zip->zip_files_in_folder($logs_path,$backup_job->backup_id,'*.log');
	$zip->close();

	WPBackItUp_Backup::end(); //Release the lock
	$current_datetime = current_time( 'timestamp' );
	$WPBackitup->set_backup_lastrun_date($current_datetime);


    $util = new WPBackItUp_Utility($backup_logname);
    $seconds = $util->timestamp_diff_seconds($backup_job->get_job_start_time(),$backup_job->get_job_end_time());

    $processing_minutes = round($seconds / 60);
    $processing_seconds = $seconds % 60;

	WPBackItUp_LoggerV2::log_info($backup_logname,__METHOD__,'Script Processing Time:' .$processing_minutes .' Minutes ' .$processing_seconds .' Seconds');

    if (true===$success) WPBackItUp_LoggerV2::log_info($backup_logname,__METHOD__,'Backup completed: SUCCESS');
	if (false===$success) WPBackItUp_LoggerV2::log_info($backup_logname,__METHOD__,'Backup completed: ERROR');

	WPBackItUp_LoggerV2::log($backup_logname,'*** END BACKUP ***');

	//Send Notification email
	WPBackItUp_LoggerV2::log_info($backup_logname,__METHOD__,'Send Email notification');
	$logs_attachment = array( $zip_file_path  );
	send_backup_notification_email($err, $success,$logs_attachment);

    $logFileName = WPBackItUp_LoggerV2::getLogFileName($backup_logname);
    $logFilePath = WPBackItUp_LoggerV2::getLogFilePath($backup_logname);

    //COPY the log if it exists
    $newlogFilePath = $wp_backup->backup_project_path .$logFileName;
    if (null!=$success && file_exists($logFilePath)){
	    copy($logFilePath,$newlogFilePath);
    }

	WPBackItUp_LoggerV2::close($backup_logname);
    echo('Backup has completed');
    exit(0);
}

function send_backup_notification_email($err, $success,$logs=array()) {
	global $WPBackitup, $wp_backup, $backup_logname,$status_array,$backup_job;
	WPBackItUp_LoggerV2::log_info($backup_logname,__METHOD__,'Begin');

	$start_timestamp = $backup_job->get_job_start_time();
	$end_timestamp = $backup_job->get_job_end_time();

    $utility = new WPBackItUp_Utility($backup_logname);
    $seconds = $utility->timestamp_diff_seconds($start_timestamp,$end_timestamp);

    $processing_minutes = round($seconds / 60);
    $processing_seconds = $seconds % 60;

    $status_description = array(
        'preparing'=> __('Preparing for backup...Done', WPBACKITUP__NAMESPACE),
        'backupdb'=> __('Backing up database...Done', WPBACKITUP__NAMESPACE),
        'infofile'=> __('Creating backup information file...Done',WPBACKITUP__NAMESPACE),
        'backup_themes'=> __('Backing up themes...Done', WPBACKITUP__NAMESPACE),
        'backup_plugins'=> __('Backing up plugins...Done', WPBACKITUP__NAMESPACE),
        'backup_uploads'=> __('Backing up uploads...Done', WPBACKITUP__NAMESPACE),
        'backup_other'=> __('Backing up miscellaneous files...Done', WPBACKITUP__NAMESPACE),
        'finalize_backup'=> __('Finalizing backup...Done', WPBACKITUP__NAMESPACE),
        'validate_backup'=> __('Validating backup...Done', WPBACKITUP__NAMESPACE),
        'cleanup'=> __('Cleaning up...Done', WPBACKITUP__NAMESPACE)
    );

	if($success)
	{
		//Don't send logs on success unless debug is on.
		if (WPBACKITUP__DEBUG!==true){
			$logs=array();
		}

        $subject = sprintf(__('%s - Backup completed successfully.', WPBACKITUP__NAMESPACE), get_bloginfo());
        $message = '<b>' . __('Your backup completed successfully.', WPBACKITUP__NAMESPACE) . '</b><br/><br/>';

    } else  {
        $subject = sprintf(__('%s - Backup did not complete successfully.', WPBACKITUP__NAMESPACE), get_bloginfo());
        $message = '<b>' . __('Your backup did not complete successfully.', WPBACKITUP__NAMESPACE) . '</b><br/><br/>';
    }

	$local_start_datetime = get_date_from_gmt(date( 'Y-m-d H:i:s',$start_timestamp));
	$local_end_datetime = get_date_from_gmt(date( 'Y-m-d H:i:s',$end_timestamp));
	$message .= sprintf(__('WordPress Site: <a href="%s" target="_blank"> %s </a><br/>', WPBACKITUP__NAMESPACE), home_url(), home_url());
    $message .= __('Backup date:', WPBACKITUP__NAMESPACE) . ' ' . $local_start_datetime . '<br/>';
	$message .= __('Number of backups completed with WP BackItUp:', WPBACKITUP__NAMESPACE) . ' ' . $WPBackitup->backup_count() . '<br/>';

	$message .= __('Completion Code:', WPBACKITUP__NAMESPACE) . ' ' . $backup_job->backup_id .'-'. $processing_minutes .'-' .$processing_seconds .'<br/>';
	$message .= __('WP BackItUp Version:', WPBACKITUP__NAMESPACE) . ' '  . WPBACKITUP__VERSION . '<br/>';
    $message .= '<br/>';


	//Add the completed steps on success
	if($success) {
	    $message .='<b>' . __('Steps Completed', WPBACKITUP__NAMESPACE) . '</b><br/>';

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
      $message .='<br/><br/>' . sprintf(__('Checkout %s for info about WP BackItUp and our other products.', WPBACKITUP__NAMESPACE), $WPBackitup->get_anchor_with_utm('www.wpbackitup.com', '', 'notification+email', $term) ) . '<br/>';


	$notification_email = $WPBackitup->get_option('notification_email');
	if($notification_email)
		$utility->send_email($notification_email,$subject,$message,$logs);

	WPBackItUp_LoggerV2::log_info($backup_logname,__METHOD__,'End');
}

function cleanup_on_failure($path){
	global $backup_logname;
    global $wp_backup;

	if (WPBACKITUP__DEBUG===true){
		WPBackItUp_LoggerV2::log_info($backup_logname,__METHOD__,'Cleanup On Fail suspended: debug on.');
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
	global $backup_logname;

	$status_file_path = WPBACKITUP__PLUGIN_PATH .'/logs/backup_status.log';
	$filesystem = new WPBackItUp_FileSystem($backup_logname);
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
    global $WPBackitup,$wp_backup,$backup_logname;

    $jsonResponse = new stdClass();
	$jsonResponse->backupStatus = 'success';
    $jsonResponse->backupMessage = 'success';
    $jsonResponse->backupName = $wp_backup->backup_name;
    $jsonResponse->backupLicense = $WPBackitup->license_active();
    $jsonResponse->backupRetained = $wp_backup->backup_retained_number;

	$jsonResponse->logFileExists = file_exists(WPBackItUp_LoggerV2::getLogFilePath($backup_logname));

	write_response_file($jsonResponse);
}

//write Response Log
function write_response_file($JSON_Response) {
	global $backup_logname;

	$json_response = json_encode($JSON_Response);
	WPBackItUp_LoggerV2::log_info($backup_logname,__METHOD__,'Write response file:' . $json_response);

	$fh=get_response_file();
	fwrite($fh, $json_response);
	fclose($fh);
}

//Get Response Log
function get_response_file() {
    global $backup_logname;

    $response_file_path = WPBACKITUP__PLUGIN_PATH .'logs/backup_response.log';
    $filesytem = new WPBackItUp_FileSystem($backup_logname);
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
		'101' => __('(101) Unable to create a new directory for backup. Please check your CHMOD settings of your wp-backitup backup directory', WPBACKITUP__NAMESPACE),
		'102'=> __('(102) Cannot create backup directory. Please check the CHMOD settings of your wp-backitup plugin directory', WPBACKITUP__NAMESPACE),
		'103'=> __('(103) Unable to backup your files. Please try again', WPBACKITUP__NAMESPACE),
		'104'=> __('(104) Unable to export your database. Please try again', WPBACKITUP__NAMESPACE),
		'105'=> __('(105) Unable to export site information file. Please try again', WPBACKITUP__NAMESPACE),
		'106'=> __('(106) Unable to cleanup your backup directory', WPBACKITUP__NAMESPACE),
		'107'=> __('(107) Unable to compress(zip) your backup. Please try again', WPBACKITUP__NAMESPACE),
		'108'=> __('(108) Unable to backup your site data files. Please try again', WPBACKITUP__NAMESPACE),
		'109'=> __('(109) Unable to finalize backup. Please try again', WPBACKITUP__NAMESPACE),
		'114'=> __('(114) Your database was accessible but an export could not be created. Please contact support by clicking the get support link on the right. Please let us know who your host is when you submit the request', WPBACKITUP__NAMESPACE),
		'120'=> __('(120) Unable to backup your themes. Please try again', WPBACKITUP__NAMESPACE),
		'121'=> __('(121) Unable to backup your plugins. Please try again', WPBACKITUP__NAMESPACE),
		'122'=> __('(122) Unable to backup your uploads. Please try again', WPBACKITUP__NAMESPACE),
		'123'=> __('(123) Unable to backup your miscellaneous files. Please try again', WPBACKITUP__NAMESPACE),
		'125'=> __('(125) Unable to compress your backup because there is no zip utility available.  Please contact support', WPBACKITUP__NAMESPACE),
		'126'=> __('(126) Unable to validate your backup. Please try again', WPBACKITUP__NAMESPACE),
		'127'=> __('(127) Unable to create inventory of files to backup. Please try again', WPBACKITUP__NAMESPACE),
		'128'=> __('(128) Unable to create job control record. Please try again', WPBACKITUP__NAMESPACE),

		'2101' => __('(2101) Unable to create a new directory for backup. Please check your CHMOD settings of your wp-backitup backup directory', WPBACKITUP__NAMESPACE),
		'2102'=> __('(2102) Cannot create backup directory. Please check the CHMOD settings of your wp-backitup plugin directory', WPBACKITUP__NAMESPACE),
		'2103'=> __('(2103) Unable to backup your files. Please try again', WPBACKITUP__NAMESPACE),
		'2104'=> __('(2104) Unable to export your database. Please try again', WPBACKITUP__NAMESPACE),
		'2105'=> __('(2105) Unable to export site information file. Please try again', WPBACKITUP__NAMESPACE),
		'2106'=> __('(2106) Unable to cleanup your backup directory', WPBACKITUP__NAMESPACE),
		'2107'=> __('(2107) Unable to compress(zip) your backup. Please try again', WPBACKITUP__NAMESPACE),
		'2108'=> __('(2108) Unable to backup your site data files. Please try again', WPBACKITUP__NAMESPACE),
		'2109'=> __('(2109) Unable to finalize backup. Please try again', WPBACKITUP__NAMESPACE),
		'2114'=> __('(2114) Your database was accessible but an export could not be created. Please contact support by clicking the get support link on the right. Please let us know who your host is when you submit the request', WPBACKITUP__NAMESPACE),
		'2120'=> __('(2120) Unable to backup your themes. Please try again', WPBACKITUP__NAMESPACE),
		'2121'=> __('(2121) Unable to backup your plugins. Please try again', WPBACKITUP__NAMESPACE),
		'2122'=> __('(2122) Unable to backup your uploads. Please try again', WPBACKITUP__NAMESPACE),
		'2123'=> __('(2123) Unable to backup your miscellaneous files. Please try again', WPBACKITUP__NAMESPACE),
		'2125'=> __('(2125) Unable to compress your backup because there is no zip utility available.  Please contact support', WPBACKITUP__NAMESPACE),
		'2126'=> __('(2126) Unable to validate your backup. Please try again', WPBACKITUP__NAMESPACE),
		'2127'=> __('(2127) Unable to create inventory of files to backup. Please try again', WPBACKITUP__NAMESPACE),
		'2128'=> __('(2128) Unable to create job control record. Please try again', WPBACKITUP__NAMESPACE),
	);

	$error_message = __('(999) Unexpected error', WPBACKITUP__NAMESPACE);
	if (array_key_exists($error_code,$error_message_array)) {
		$error_message = $error_message_array[ $error_code ];
	}

	return $error_message;
}
