<?php

/**
 * WP BackItUp File Upload Handler
 * 
 * @package WP BackItUp Pro
 * 
 * @author cssimmon
 * @version 1.0.0
 * @since 1.0.1
 * 
 */

/*** Includes ***/
// Define WP_DIR_PATH - required for constants include
if (!defined('WP_DIR_PATH')) define('WP_DIR_PATH',dirname(dirname(dirname(dirname(dirname(dirname(__FILE__)))))));
include_once WP_DIR_PATH . '/wp-config.php';
include_once dirname(dirname( __FILE__ )) . '/constants.php';


/*** Globals ***/
$backup_folder_root = WPBACKITUP_CONTENT_PATH .WPBACKITUP_BACKUP_FOLDER .'/';


//*****************//
//*** MAIN CODE ***//
//*****************//

//Handle the file upload
if (!empty($_FILES['uploaded-zip']) && is_uploaded_file($_FILES['uploaded-zip']['tmp_name']))
{
	$restore_file_name = $_FILES['uploaded-zip']['name'];
	_log("File Uploaded: " .$restore_file_name);
	
	$destination = $backup_folder_root . $restore_file_name;
	_log("Destination: " .$destination);
	if (move_uploaded_file($_FILES['uploaded-zip']['tmp_name'], $destination))
	{
		$response['file'] = $restore_file_name;
		$response['link'] = WPBACKITUP_BACKUPFILE_URLPATH  . '/' . $restore_file_name;

		echo json_encode($response);
		die();
	}
}