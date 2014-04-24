<?php
@set_time_limit(900);// 15 minutes per image should be PLENTY

/**
 * WP Backitup Restore Functions
 * 
 * @package WP Backitup Pro
 * 
 * @author cssimmon
 * @version 1.4.0
 * @since 1.0.1
 */

/*** Includes ***/
// Define WP_DIR_PATH - required for constants include
if (!defined('WP_DIR_PATH')) define('WP_DIR_PATH',dirname(dirname(dirname(dirname(dirname(dirname(__FILE__)))))));
include_once WP_DIR_PATH . '/wp-config.php';
include_once dirname(dirname( __FILE__ )) . '/constants.php';

/*** Globals ***/
$backup_folder_root = WPBACKITUP_CONTENT_PATH .WPBACKITUP_BACKUP_FOLDER .'/'; //wpbackitup_backups
$restore_folder_root = WPBACKITUP_CONTENT_PATH .WPBACKITUP_RESTORE_FOLDER .'/';//wpbackitup_restore
$backup_file_name; //name of the backup file
$backup_file_path; //full path to zip file on server

$inactive=0;
$active=1;
$complete=2;
$failure=-1;
$warning=-2;
$success=99;

//setup the status array
$status_array = array(
	'preparing' =>$inactive ,
	'unzipping' =>$inactive ,
	'validation'=>$inactive,
	'restore_point'=>$inactive,
	'database'=>$inactive,
	'wpcontent'=>$inactive,
	'cleanup'=>$inactive
 ); 


//*****************//
//*** MAIN CODE ***//
//*****************//
deleteDebugLog();
_log('***BEGIN RESTORE.PHP***');
_log_constants();

if (!license_active()){
	_log('Restore is not available because pro license is not active.');
	write_fatal_error_status('error225');
 	die();
 }

//--Get form post values 
$backup_file_name = $_POST['selected_file'];//Get the backup file name
if( $backup_file_name == '') {
	write_fatal_error_status('error201');
	die();
}

//Get user ID
$user_id = $_POST['user_id'];
if( $user_id  == '') {
	write_fatal_error_status('error201');
	die();
}

set_status('preparing',$active,true);

//set path to backup file
$backup_file_path = $backup_folder_root .$backup_file_name ; 

delete_restore_folder();

create_restore_folder($restore_folder_root);
set_status('preparing',$complete,false);

set_status('unzipping',$active,true);
unzip_backup($backup_file_path,$restore_folder_root);
set_status('unzipping',$complete,false);

set_status('validation',$active,true);
$restoration_dir_path=validate_restore_folder($restore_folder_root);

$backupSQLFile = $restoration_dir_path . WPBACKITUP_SQL_DBBACKUP_FILENAME;

validate_SQL_exists($restore_folder_root,$backupSQLFile);

$dbc = get_sql_connection($restoration_dir_path);

$siteurl =  get_siteurl($dbc,$restoration_dir_path);

$homeurl = get_homeurl($dbc,$restoration_dir_path);

$user_login = get_user_login($dbc,$restoration_dir_path,$user_id );

$user_pass = get_user_pass($dbc,$restoration_dir_path,$user_id );

$user_email = get_user_email($dbc,$restoration_dir_path,$user_id);

//Collect previous backup site url start
_log('Get backupsiteinfo.txt values...');
$import_siteinfo_lines = file($restoration_dir_path .'backupsiteinfo.txt');
$import_siteurl = trim($import_siteinfo_lines[0]);
$current_siteurl = trim($siteurl ,'/');
$import_table_prefix = $import_siteinfo_lines[1];
_log($import_siteinfo_lines);

//Check table prefix values FATAL
if($table_prefix !=$import_table_prefix) {
	_log('Error: Table prefix different from restore.');
	write_warning_status('error221');
}


//Create restore point for DB
set_status('validation',$complete,false);
set_status('restore_point',$active,true);
$RestorePoint_SQL = backup_database($backup_folder_root); //Save in backup folder 

//create_table_rename_sql($restore_folder_root,$table_prefix);
//Rename the old tables - not sure i want to do this anymore
//$renameSQLFile = $restore_folder_root.WPBACKITUP_SQL_TABLE_RENAME_FILENAME;
//rename_SQL_tables($renameSQLFile);

set_status('restore_point',$complete,false);

//Import the backed up database
set_status('database',$active,true);
import_backedup_database($backupSQLFile);

//FAILURES AFTER THIS POINT SHOULD REQUIRE ROLLBACK OF DB
update_user_credentials($dbc,$restoration_dir_path,$import_table_prefix,$user_login,$user_pass,$user_email,$user_id);

update_siteurl($dbc,$restoration_dir_path,$import_table_prefix,$current_siteurl);

update_homeurl($dbc,$restoration_dir_path,$import_table_prefix,$homeurl);

//Done with DB restore
set_status('database',$complete,false);

//Disconnect database
mysqli_close($dbc); 

//***DEAL WITH WPCONTENT NOW ***
set_status('wpcontent',$active,true);
delete_plugins_content(WPBACKITUP_PLUGINS_PATH,$restoration_dir_path);

delete_themes_content(WPBACKITUP_THEMES_PATH,$restoration_dir_path);

//delete whatever is left
$wpcontent_folder=WP_CONTENT_DIR;
delete_wpcontent_content($wpcontent_folder,$restoration_dir_path);

restore_wpcontent($restoration_dir_path);
set_status('wpcontent',$complete,false);

set_status('cleanup',$active,true);
cleanup_restore_folder($restoration_dir_path);
set_status('cleanup',$complete,false);
set_status_success();

_log('***END RESTORE.PHP***');
die();

/******************/
/*** Functions ***/
/******************/

//Get Status Log
function get_restore_Log() {
	$log = WPBACKITUP_DIRNAME .'/logs/restore_status.log';
	if (file_exists($log)){
		unlink($log);
	}
	$fh = fopen($log, 'w+') or die( "Can't write to log file" );
	return $fh;
}

function write_fatal_error_status($status_code) {
	global $status_array,$inactive,$active,$complete,$failure,$warning,$success;
	
	//Find the active status and set to failure
	foreach ($status_array as $key => $value) {
		if ($value==$active){
			$status_array[$key]=$failure;	
		}
	}

	//Add failure to array
	$status_array[$status_code]=$failure;
	write_restore_status();
}

function write_warning_status($status_code) {
	global $status_array,$inactive,$active,$complete,$failure,$warning,$success;
		
	//Add warning to array
	$status_array[$status_code]=$warning;
	write_restore_status();
}

function write_restore_status() {
	global $status_array;
	$fh=get_restore_Log();
	
	foreach ($status_array as $key => $value) {
		fwrite($fh, '<div class="' . $key . '">' . $value .'</div>');		
	}
	fclose($fh);
}

function set_status($process,$status,$flush){
	global $status_array;
	$status_array[$process]=$status;
	
	if ($flush) write_restore_status(); 
}

function set_status_success(){
	global $status_array,$inactive,$active,$complete,$failure,$warning,$success;
	global $active;

	$status_array['finalinfo']=$success;
	write_restore_status();
}

//Create an empty restore folder
function create_restore_folder($path) {
	_log('Create restore folder:' .$path);
	if(!create_dir($path)) {
		_log('Error: Cant create restore folder :'. $path);
		write_fatal_error_status('error222');
		die();
	}
	_log('Restore folder created:' .$path);
}

//Delete restore folder and contents
function delete_restore_folder() {
	global $restore_folder_root;
	//Delete the existing restore directory
	_log('Delete existing restore folder:' .$restore_folder_root);
	return recursive_delete($restore_folder_root);
	_log('Existing restore folder deleted:' .$restore_folder_root);
}

//Unzip the backup to the restore folder
function unzip_backup($backup_file_path,$restore_folder_root){	
	//unzip the upload
	_log('Unzip the backup file source:' .$backup_file_path);
	_log('Unzip the backup file target:' .$restore_folder_root);
	$zip = new ZipArchive;
	$res = $zip->open($backup_file_path);
	if ($res === TRUE) {
		$zip->extractTo($restore_folder_root);
		$zip->close();
	} else {
		_log('Error: Cant unzip backup:'.$backup_file_path);
		write_fatal_error_status('error203');
		delete_restore_folder();
		die();
	}
	_log('Backup file unzipped: ' .$restore_folder_root);
}

//Validate the restore folder 
function validate_restore_folder($restore_folder_root){
	$restoration_dir_path='';

	_log('Identify the restoration directory in restore folder: ' .$restore_folder_root.'*');
	if ( count( glob( $restore_folder_root.'*', GLOB_ONLYDIR ) ) == 1 ) {
		foreach( glob($restore_folder_root .'*', GLOB_ONLYDIR ) as $dir) {
			$restoration_dir_path = $dir .'/';
		}
	}
	_log('Restoration directory: ' .$restoration_dir_path);

	//Validate the restoration
	_log('Validate restoration directory: ' . $restoration_dir_path .'backupsiteinfo.txt');
	if(!glob($restoration_dir_path .'backupsiteinfo.txt') ){
		_log('Error: Restore directory INVALID: ' .$restoration_dir_path);
		write_fatal_error_status('error204');		
		delete_restore_folder(); //delete the restore folder if bad
		die();
	}
	_log('Restoration directory validated: ' .$restoration_dir_path);
	return $restoration_dir_path;
}

// Backup the current database try dump first
function backup_database($restore_folder_root){
	$date = date_i18n('Y-m-d-Hi',current_time( 'timestamp' ));
	$backup_file = $restore_folder_root . 'db-backup-' . $date .'.cur';
	_log('Backup the current database: ' .$backup_file);
	 if(!db_SQLDump($backup_file)) {	 	
		//Try a manual restore since dump didnt work
		if(!db_backup($backup_file)) {
			_log('Error: Cant backup database:'.$backup_file);
			write_fatal_error_status('error205');
			delete_restore_folder();
			die();
		}
	}
	_log('Current database backed up: ' .$backup_file);
	return $backup_file;
}

//Generate a script to rename the tables
function create_table_rename_sql($restore_folder_root,$table_prefix){
	$sql_file_path=	$restore_folder_root  .'db-rename-tables.sql';
	_log('Generate a script to rename the tables.' .$sql_file_path);
	 if(!db_rename_wptables($sql_file_path,$table_prefix)) {
	 	_log('Error: Cant generate rename script:'.$sql_file_path);
	 	write_fatal_error_status('error205');
	 	delete_restore_folder();
	 	die();		
	 }
	 _log('SQL Script to rename tables generated.' .$sql_file_path);
}

//Make sure there IS a backup to restore
function validate_SQL_exists($restore_folder_root,$backupSQLFile){
	_log('Check for database backup file:' . $backupSQLFile);

	if(!file_exists($backupSQLFile) && !empty($backupSQLFile)) {
		_log('Error: NO Database backups in backup.');
		write_fatal_error_status('error216');
		delete_restore_folder();
		die();	
	}
	_log('Database backup file exist:' . $backupSQLFile);	
}

//Get SQL Connection
function get_sql_connection($restoration_dir_path){
	//Connect to DB
	$dbc = db_get_sqlconnection();
	if ( !$dbc ) {
		_log('Error: Cant connect to database.');
		write_fatal_error_status('error206');
		delete_restore_folder();
		die();
	}
	return $dbc;
}

//Get SQL scalar value
function get_sql_scalar($dbc,$sql){
	$value='';
	if ($result = mysqli_query($dbc, $sql)) {
		while ($row = mysqli_fetch_row($result)) {
			$value = $row[0];
		}
		mysqli_free_result($result);
	}	
	return $value;	
}

//Run SQL command
function run_SQL_command($dbc, $sql){
	if(!mysqli_query($dbc, $sql) ) {
		_log('Error:SQL Command Failed:' .$sql);
		return false;			
	}		
	return true;
}

//Rename the existing tables to have the save_ prefix
function rename_SQL_tables($renameSQLFile){
	_log('Rename existing tables to contain save_ prefix:' .$renameSQLFile);
	if(!db_run_sql($renameSQLFile)) {
		_log('Error: Table rename error.');
		write_fatal_error_status('error205');
		delete_restore_folder();
		die();	
	}
	_log('Tables renamed to contain save_ prefix.');
}

//Restore DB
function restore_database(){
	global $RestorePoint_SQL;
	_log('Restore the DB to previous state.' . $RestorePoint_SQL);
	if(!db_run_sql($RestorePoint_SQL)) {
		_log('Error: Database could not be restored.' .$RestorePoint_SQL);
		write_fatal_error_status('error223');			
		delete_restore_folder();
		die();	
	}
	write_fatal_error_status('error224');			
	_log('Database restored to previous state.');
}

//Run DB restore
function import_backedup_database($backupSQLFile){
	_log('Import the backed up database.');
	//Try SQL Import first
	if(!db_run_sql($backupSQLFile)) {
		//Do it manually if the import doesnt work
		if(!db_run_sql_manual($backupSQLFile)) { 
			_log('Error: Database import error.');
			write_fatal_error_status('error212');			
			delete_restore_folder();
			die();	
		}
	}
	_log('Backed up database imported.');
}

//get siteurl
function get_siteurl($dbc,$restoration_dir_path){
	global $table_prefix;
	$sql = "SELECT option_value FROM " .$table_prefix ."options WHERE option_name ='siteurl';";
	$siteurl = get_sql_scalar($dbc,$sql);
	if (empty($siteurl)) {
		_log('Error: Siteurl not found.');
		write_fatal_error_status('error207');
		mysqli_close($dbc);
		delete_restore_folder();
		die();
	}
	_log('Siteurl found.');
	return $siteurl;
}

//get homeurl
function get_homeurl($dbc,$restoration_dir_path){
	global $table_prefix;
	$sql = "SELECT option_value FROM " .$table_prefix ."options WHERE option_name ='home';";
	$homeurl = get_sql_scalar($dbc,$sql);
	if (empty($homeurl)) {
		_log('Error: Homeurl not found.');
		write_fatal_error_status('error208');
		mysqli_close($dbc);
		delete_restore_folder();
		die();	
	}
	_log('homeurl found.');
	return $homeurl;
}

//get user login
function get_user_login($dbc,$restoration_dir_path,$user_id ){
	global $table_prefix;
	$sql = "SELECT user_login FROM ". $table_prefix ."users WHERE ID=" .$user_id .";";
	$user_login = get_sql_scalar($dbc,$sql);
	if (empty($user_login)) {
		_log('Error: user_login not found.');
		write_fatal_error_status('error209');
		mysqli_close($dbc);
		delete_restore_folder();
		die();
	}
	_log('user_login found.');
	return $user_login;
}

//get user pass
function get_user_pass($dbc,$restoration_dir_path,$user_id ){
	global $table_prefix;
	$sql = "SELECT user_pass FROM ". $table_prefix ."users WHERE ID=" .$user_id .";";
	$user_pass = get_sql_scalar($dbc,$sql);
	if (empty($user_pass)) {
		_log('Error: user_pass not found.');
		write_fatal_error_status('error210');
		mysqli_close($dbc);
		delete_restore_folder();			
		die();
	}
	_log('user_pass found.');
	return $user_pass;
}

//get user email
function get_user_email($dbc,$restoration_dir_path,$user_id ){
	global $table_prefix;
	$sql = "SELECT user_email FROM ". $table_prefix ."users WHERE ID=" .$user_id ."";
	$user_email = get_sql_scalar($dbc,$sql);
	if (empty($user_email)) {
		_log('Error: user_email not found.');
		write_fatal_error_status('error211');
		mysqli_close($dbc);
		delete_restore_folder();
		die();
	}
	_log('user_email found.');
	return $user_email;
}

//Update user crentials
function update_user_credentials($dbc,$restoration_dir_path,$table_prefix,$user_login,$user_pass,$user_email,$user_id){
	$sql = "UPDATE ". $table_prefix ."users SET user_login='" .$user_login ."', user_pass='" .$user_pass ."', user_email='" .$user_email ."' WHERE ID='" .$user_id ."'";
	if (!run_SQL_command($dbc, $sql)){
		_log('Error: User Credential database update failed..');
		write_warning_status('error215');
		mysqli_close($dbc); 
		restore_database();
		delete_restore_folder();
		die();
	}
	_log('User Credential updated in database.');
}

//update the site URL in the restored database
function update_siteurl($dbc,$restoration_dir_path,$table_prefix,$current_siteurl){
	$sql = "UPDATE ". $table_prefix ."options SET option_value='" .$current_siteurl ."' WHERE option_name='siteurl'";
	if (!run_SQL_command($dbc, $sql)){
		_log('Error: SiteURL updated failed.');
		write_warning_status('error213');
		mysqli_close($dbc); 
		restore_database();
		delete_restore_folder();		
		die();
	}
	_log('SiteURL updated in database.');
}

//Update homeURL
function update_homeurl($dbc,$restoration_dir_path,$table_prefix,$homeurl){
	$sql = "UPDATE ". $table_prefix ."options SET option_value='" .$homeurl ."' WHERE option_name='home'";
	if (!run_SQL_command($dbc, $sql)){
		_log('Error: HomeURL database update failed..');
		write_warning_status('error214');
		mysqli_close($dbc); 
		restore_database();
		delete_restore_folder();	
		die();
	}	
	_log('HomeURL updated in database.');
}

//Delete wp-content content
function delete_wpcontent_content($root_folder,$restoration_dir_path){
	_log('Delete the wp_content contents:' .$root_folder);
	$ignore = array( 'cgi-bin','._', WPBACKITUP_PLUGIN_FOLDER,WPBACKITUP_RESTORE_FOLDER,WPBACKITUP_BACKUP_FOLDER,WPBACKITUP_THEMES_FOLDER, WPBACKITUP_PLUGINS_FOLDER,'debug.log');
	if(!delete_children_recursive($root_folder,$ignore)) {
		_log('Error: Cant delete WPContent:' .$root_folder);	
		write_warning_status('error217');
		restore_database();
		delete_restore_folder();
		die();
	}
	_log('wp-content has been deleted:' .$root_folder);
}

//Delete plugins content
function delete_plugins_content($plugins_folder,$restoration_dir_path){
	_log('Delete the plugins contents:' .$plugins_folder);
	$ignore = array( 'cgi-bin','._', WPBACKITUP_PLUGIN_FOLDER,WPBACKITUP_RESTORE_FOLDER,WPBACKITUP_BACKUP_FOLDER);
	if(!delete_children_recursive($plugins_folder,$ignore)) {
		_log('Error: Cant delete old WPContent:' .$plugins_folder  );	
		write_warning_status('error217');
		restore_database();
		delete_restore_folder();
		die();
	}
	_log('Plugins content deleted:' .$plugins_folder);
}


//Delete themes content
function delete_themes_content($themes_folder,$restoration_dir_path){
	_log('Delete the themes contents:' .$themes_folder);
	$ignore=array( 'cgi-bin','._', WPBACKITUP_PLUGIN_FOLDER,WPBACKITUP_RESTORE_FOLDER,WPBACKITUP_BACKUP_FOLDER,'debug.log' );
	if(!delete_children_recursive($themes_folder  , $ignore)) {
		_log('Error: Cant delete old WPContent:' .$themes_folder  );	
		write_warning_status('error217');
		restore_database();
		delete_restore_folder();
		die();
	}
	_log('Themes content deleted:' .$themes_folder);
}

//Restore all wp content from zip
function restore_wpcontent($restoration_dir_path){
	_log('Copy content folder from:' .$restoration_dir_path);
	_log('Copy content folder to:' .WP_CONTENT_DIR);
	$ignore =  array( 'cgi-bin', '.', '..','._', WPBACKITUP_PLUGIN_FOLDER, 'status.log','debug.log', WPBACKITUP_SQL_DBBACKUP_FILENAME, 'backupsiteinfo.txt');
	if(!recursive_copy($restoration_dir_path,WP_CONTENT_DIR. '/',$ignore)) {
		_log('Error: Content folder was not copied successfully');
		write_warning_status('error219');
		restore_database();
		delete_restore_folder();
		die();
	}
	_log('Content folder copied successfully');
}

//Delete the restoration directory
function cleanup_restore_folder($restoration_dir_path){
	_log('Cleanup the restore folder: ' .$restoration_dir_path);
	if(!delete_restore_folder()) {
		_log('Error: Cleanup restore folder failed: ' .$restoration_dir_path);
		write_warning_status('error220'); //NOT fatal
	} else {
		_log('Restore folder cleaned successfully: ' .$restoration_dir_path);
	}	
}