<?php

/**
 * WP Backitup Restore Functions
 * 
 * @package WP Backitup Pro
 * 
 * @author jcpeden
 * @version 1.4.0
 * @since 1.0.1
 */

//define constants
if( !defined( 'WP_DIR_PATH' ) ) define( 'WP_DIR_PATH', dirname ( dirname ( dirname ( dirname ( dirname ( dirname ( __FILE__ ) ) ) ) ) ) );

if( !defined( 'WPBACKITUP_DIR_PATH' ) ) define( 'WPBACKITUP_DIR_PATH', dirname( dirname( dirname( __FILE__ ) ) ) );

if( !defined( 'WPBACKITUP_DIRNAME' ) ) define( 'WPBACKITUP_DIRNAME', basename(WPBACKITUP_DIR_PATH) );

if( !defined( 'WP_CONTENT_PATH' ) ) define( 'WP_CONTENT_PATH', dirname( dirname( WPBACKITUP_DIR_PATH ) ) ) ;

// Include recurse_zip.php
include_once 'recurse_zip.php';

//define create_dir function
if(!function_exists('create_dir')) {
	function create_dir($dir) {
		if( !is_dir($dir) ) {
			@mkdir($dir, 0755);
		}
		return true;
	}
}

if(!function_exists('redo_to_checkpoint')) {
	function redo_to_checkpoint($checkpoint) {

            if($checkpoint == "db")
            {
                if( glob($restoration_dir_path . "*.cur") ) {
                    //collect connection information from form
                    fwrite($fh, '<div class="database">In Progress</div>');
                    include_once WP_DIR_PATH .'/wp-config.php';
                    //Add user to DB in v1.0.5
                    $user_id = $_POST['user_id'];
                    //Connect to DB
                    $output = db_import($restoration_dir_path, $import_siteurl, $current_siteurl, $table_prefix, $import_table_prefix, $dbc); 
                }

            }
            
	}
}

if(!function_exists('db_backup')) {
	function db_backup($path) { 
            $handle = fopen($path .'db-backup.cur', 'w+');
            $path_sql = $path .'db-backup.cur';
            $db_name = DB_NAME; 
            $db_user  = DB_USER;
            $db_pass = DB_PASSWORD; 
            $db_host = DB_HOST;
            $output = shell_exec("mysqldump --user $db_user --password=$db_pass $db_name > '$path_sql'");
            fwrite($handle,$output);
            fclose($handle);
            return true;
	}
}

//Define recusive_copy function
if(!function_exists('recursive_copy')) {
	function recursive_copy($dir, $target_path, $ignore = array( 'cgi-bin','..','._' ) ) {
		if( is_dir($dir) ) { //If the directory exists
			if ($dh = opendir($dir) ) {
				while(($file = readdir($dh)) !== false) { //While there are files in the directory
					if ( !in_array($file, $ignore) && substr($file, 0, 1) != '.') { //Check the file is not in the ignore array
						if (!is_dir( $dir.$file ) ) {
								//Copy files to destination directory
								//echo 'Copying ' .$dir .$file . ' to ' .$target_path .$file .'<br />';
								$fsrc = fopen($dir .$file,'r');
								$fdest = fopen($target_path .$file,'w+');
								$len = stream_copy_to_stream($fsrc,$fdest);
								fclose($fsrc);
								fclose($fdest);
						} else { //If $file is a directory
							$destdir = $target_path .$file; //Modify the destination dir
							if(!is_dir($destdir)) { //Create the destdir if it doesn't exist
								@mkdir($destdir, 0755);
							} 	
							recursive_copy($dir .$file .'/', $target_path .$file .'/', $ignore);
						}
					}
				}
				closedir($dh);
			}
		}
	return true;
	}	
}

//Define recursive_delete function
if(!function_exists('recursive_delete')){
	function recursive_delete($dir, $ignore = array('cgi-bin','.','..','._') ){		  
		if( is_dir($dir) ){
			if($dh = opendir($dir)) {
				while( ($file = readdir($dh)) !== false ) {
					if (!in_array($file, $ignore) && substr($file, 0, 1) != '.') { //Check the file is not in the ignore array
						if(!is_dir($dir .'/' .$file)) {
							//echo 'Deleting ' .$dir .'/' .$file '<br />';
							unlink($dir .'/' .$file);
						} else {
							recursive_delete($dir .'/' .$file, $ignore);
						}
					}
				}
			}
			@rmdir($dir);	
			closedir($dh);
		}
	return true;
	}
}

//define db_import function
if(!function_exists('db_import')) {
	function db_import($restoration_dir_path, $import_siteurl, $current_siteurl, $table_prefix, $import_table_prefix, $dbc) {
		//13-4-13: John C Peden [mail@johncpeden.com] This was incomplete, I've updated to make it work
		foreach(glob($restoration_dir_path . "*.sql") as $sql_file) {
            $db_name = DB_NAME; 
            $db_user  = DB_USER;
            $db_pass = DB_PASSWORD; 
            $db_host = DB_HOST;
            $command = "mysql --user='$db_user' --password='$db_pass' --host='$db_host' $db_name < '$sql_file'";
            $output = shell_exec(($command));
		}
	return true;
	}
}

//create log file
$log = WPBACKITUP_DIR_PATH .'/logs/status.log';
unlink($log);
$fh = fopen($log, 'w') or die( "Can't write to log file" );

// 15 minutes per image should be PLENTY
@set_time_limit(900);

//Delete the existing backup directory
recursive_delete( WPBACKITUP_DIR_PATH .'/backups/' );

//Re-create and empty backup dir
if(!create_dir( WPBACKITUP_DIR_PATH .'/backups/' )) {
	fwrite($fh, '<div class="error201">1</div>');
	fclose($fh);
	die();
}
 
//Move the uploaded zip to the restoration directory
$restore_file_name = basename( $_FILES['wpbackitup-zip']['name']);
if( $restore_file_name == '') {
	fwrite($fh, '<div class="error201">1</div>');
	fclose($fh);
	die();
}

$restore_path = WPBACKITUP_DIR_PATH .'/backups/' . $restore_file_name; 
if(move_uploaded_file($_FILES['wpbackitup-zip']['tmp_name'], $restore_path)) {
	fwrite($fh, '<div class="upload">1</div>');
} else {
	fwrite($fh, '<div class="error203">1</div>');
	fclose($fh);
	die();
}

//unzip the upload
$zip = new ZipArchive;
$res = $zip->open(WPBACKITUP_DIR_PATH .'/backups/' . $restore_file_name);
if ($res === TRUE) {
	$zip->extractTo(WPBACKITUP_DIR_PATH .'/backups/');
	$zip->close();
	fwrite($fh, '<div class="unzipping">1</div>');
} else {
	fwrite($fh, '<div class="error204">1</div>');
	unlink(WPBACKITUP_DIR_PATH .'/backups/' . $restore_file_name);
	fclose($fh);
	die();
}

//Identify the restoration directory
if ( count( glob( WPBACKITUP_DIR_PATH .'/backups/*', GLOB_ONLYDIR ) ) == 1 ) {
	foreach( glob(WPBACKITUP_DIR_PATH .'/backups/*', GLOB_ONLYDIR ) as $dir) {
		$restoration_dir_path = $dir .'/';
	}
}

//Validate the restoration
if(glob($restoration_dir_path .'backupsiteinfo.txt') ){
	fwrite($fh, '<div class="validation">1</div>');
} else {
	fwrite($fh, '<div class="error204">1</div>');
	recursive_delete($restoration_dir_path);
	unlink(WPBACKITUP_DIR_PATH .'/backups/' . $restore_file_name);
	fclose($fh);
	die();
}

// Backup the current database
if( db_backup($restoration_dir_path) ) {
	fwrite($fh, '<div class="restore_point">1</div>');
} else {
	fwrite($fh, '<div class="error205">1</div>');
	fclose($fh);
	die();
}

//if there is a database dump to restore
if( glob($restoration_dir_path . "*.sql") ) {
	
	//Collect connection information from form 
	include_once WP_DIR_PATH .'/wp-config.php';
	
	//Add user to DB in v1.0.5
	$user_id = $_POST['user_id'];
	
	//Connect to DB
	$dbc = mysqli_connect(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME);
	if ( !$dbc ) {
		fwrite($fh, '<div class="error206">1</div>');

		recursive_delete($restoration_dir_path);
		unlink(WPBACKITUP_DIR_PATH .'/backups/' . $restore_file_name);
		fclose($fh);
		die();
	}

	//get siteurl
	$q1 = "SELECT option_value FROM " .$table_prefix ."options WHERE option_name ='siteurl';";
	if ($result = mysqli_query($dbc, $q1)) {
		while ($row = mysqli_fetch_row($result)) {
			$siteurl = $row[0];
		}
		mysqli_free_result($result);
	} else {
		fwrite($fh, '<div class="error207">1</div>');
		recursive_delete($restoration_dir_path);
		unlink(WPBACKITUP_DIR_PATH .'/backups/' . $restore_file_name);
		fclose($fh);
		die();
	}

	//get homeurl
	$q2 = "SELECT option_value FROM " .$table_prefix ."options WHERE option_name ='home';";
	if ($result = mysqli_query($dbc, $q2)) {
		while ($row = mysqli_fetch_row($result)) {
			$homeurl = $row[0];
		}
		mysqli_free_result($result);
	} else {
		fwrite($fh, '<div class="error208">1</div>');
		recursive_delete($restoration_dir_path);
		unlink(WPBACKITUP_DIR_PATH .'/backups/' . $restore_file_name);
		fclose($fh);
		die();
	}

	//get user login
	$q3 = "SELECT user_login FROM ". $table_prefix ."users WHERE ID=" .$user_id .";";
	if ($result = mysqli_query($dbc, $q3)) {
		while ($row = mysqli_fetch_row($result)) {
			$user_login = $row[0];
		}
		mysqli_free_result($result);
	} else {
		fwrite($fh, '<div class="error209">1</div>');
		recursive_delete($restoration_dir_path);
		unlink(WPBACKITUP_DIR_PATH .'/backups/' . $restore_file_name);
		fclose($fh);
		die();
	}

	//get user pass
	$q4 = "SELECT user_pass FROM ". $table_prefix ."users WHERE ID=" .$user_id .";";
	if ($result = mysqli_query($dbc, $q4)) {
		while ($row = mysqli_fetch_row($result)) {
			$user_pass = $row[0];
		}
		mysqli_free_result($result);
	} else {
		fwrite($fh, '<div class="error210">1</div>');
		recursive_delete($restoration_dir_path);
		unlink(WPBACKITUP_DIR_PATH .'/backups/' . $restore_file_name);
		fclose($fh);
		die();
	}

	//get user email
	$q5 = "SELECT user_email FROM ". $table_prefix ."users WHERE ID=" .$user_id ."";
	if ($result = mysqli_query($dbc, $q5)) {
		while ($row = mysqli_fetch_row($result)) {
			$user_email = $row[0];
		}
		mysqli_free_result($result);
	} else {
		fwrite($fh, '<div class="error211">1</div>');
		recursive_delete($restoration_dir_path);
		unlink(WPBACKITUP_DIR_PATH .'/backups/' . $restore_file_name);
		fclose($fh);
		die();
	}

	//Collect previous backup site url start
	$import_siteinfo_lines = file($restoration_dir_path .'backupsiteinfo.txt');
	$import_siteurl = trim($import_siteinfo_lines[0]);
	$current_siteurl = trim($siteurl ,'/');
	$import_table_prefix = $import_siteinfo_lines[1];

	//import the database
	if(!db_import($restoration_dir_path, $import_siteurl, $current_siteurl, $table_prefix, $import_table_prefix, $dbc)) {
		fwrite($fh, '<div class="error212">1</div>');
		recursive_delete($restoration_dir_path);
		unlink(WPBACKITUP_DIR_PATH .'/backups/' . $restore_file_name);
		fclose($fh);
		die();
	}

	//update the database
	$q6 = "UPDATE ". $table_prefix ."options SET option_value='" .$current_siteurl ."' WHERE option_name='siteurl'";
	$q7 = "UPDATE ". $table_prefix ."options SET option_value='" .$homeurl ."' WHERE option_name='home'";
	$q8 = "UPDATE ". $table_prefix ."users SET user_login='" .$user_login ."', user_pass='" .$user_pass ."', user_email='" .$user_email ."' WHERE ID='" .$user_id ."'";
	if(!mysqli_query($dbc, $q6) ) {
		fwrite($fh, '<div class="error213">1</div>');
		recursive_delete($restoration_dir_path);
		unlink(WPBACKITUP_DIR_PATH .'/backups/' . $restore_file_name);
		fclose($fh);
		die();
	}
	if(!mysqli_query($dbc, $q7) ) {
		fwrite($fh, '<div class="error214">1</div>');
		recursive_delete($restoration_dir_path);
		unlink(WPBACKITUP_DIR_PATH .'/backups/' . $restore_file_name);
		fclose($fh);
		die();
	}	
	if(!mysqli_query($dbc, $q8) ) {
		fwrite($fh, '<div class="error215">1</div>');
		recursive_delete($restoration_dir_path);
		unlink(WPBACKITUP_DIR_PATH .'/backups/' . $restore_file_name);
		fclose($fh);
		die();
	}
	fwrite($fh, '<div class="database">1</div>');
} else {
	fwrite($fh, '<div class="error216">1</div>');
}

//Disconnect
mysqli_close($dbc); 

//Restore wp-content directories
if(!recursive_delete(WP_CONTENT_PATH, array( 'cgi-bin','.','..','._', WPBACKITUP_DIRNAME ))) {
	fwrite($fh, '<div class="error217">1</div>');
	recursive_delete($restoration_dir_path);
	unlink(WPBACKITUP_DIR_PATH .'/backups/' . $restore_file_name);
	fclose($fh);
	die();
}
if(!create_dir(WP_CONTENT_PATH)) {
	fwrite($fh, '<div class="error218">1</div>');
	recursive_delete($restoration_dir_path);
	unlink(WPBACKITUP_DIR_PATH .'/backups/' . $restore_file_name);
	fclose($fh);
	die();
}
if(recursive_copy($restoration_dir_path, WP_CONTENT_PATH .'/', array( 'cgi-bin', '.', '..','._', $restore_file_name, 'status.log', 'db-backup.sql', 'backupsiteinfo.txt')) ) {
	fwrite($fh, '<div class="wpcontent">1</div>');
} else {
	fwrite($fh, '<div class="error219">1</div>');
	recursive_delete($restoration_dir_path);
	unlink(WPBACKITUP_DIR_PATH .'/backups/' . $restore_file_name);
	fclose($fh);
	die();
}

//Delete the restoration directory
if(!recursive_delete($restoration_dir_path)) {
	fwrite($fh, '<div class="error220">1</div>');
	fclose($fh);
} else {
	fwrite($fh, '<div class="cleanup">1</div>');
}

//Delete zip
unlink(WPBACKITUP_DIR_PATH .'/backups/' . $restore_file_name);

//close log file
fwrite($fh, '<div class="finalinfo">1</div>');
fclose($fh);

//End backup function
die();