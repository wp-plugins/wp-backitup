<?php if (!defined ('ABSPATH')) die('No direct access allowed (upload)');
/**
 * WP BackItUp File Upload Handler
 * 
 * @package WP BackItUp Pro
 * 
 * @author cssimmon
 *
 */

    /*** Includes ***/


    /*** Globals ***/
    global $logger;
    $logger = new WPBackItUp_Logger(true,null,'debug_upload');
    $backup_folder_root = WPBACKITUP__BACKUP_PATH .'/';

    //*****************//
    //*** MAIN CODE ***//
    //*****************//
    $logger->log('***BEGIN UPLOAD***');

    //Initialize return class
    $rtnData = new stdClass();
    $rtnData->file = '';
    $rtnData->zip_link  = '';
    $rtnData->msg = '';
    $rtnData->error = '';


    if ( !wp_verify_nonce($_REQUEST['_wpnonce'],WPBACKITUP__NAMESPACE .'-upload-file') || !check_admin_referer( WPBACKITUP__NAMESPACE .'-upload-file', '_wpnonce' )) {
        $rtnData->error ='Invalid Nonce';

    }else{
        foreach ($_FILES as $key => $value)
        {
            //GET FILE CONTENT
            $logger->log("File Uploaded Key: " . $key);
            $logger->log("File Uploaded Value");
            $logger->log($value);

            $temp_file_path= $value['tmp_name'];
            $original_file_name = $value['name'];
            $save_to_file_path = $backup_folder_root . $original_file_name;
            $error = $value['error'];
            $size = $value['size'];
            $error_message = get_error_message($error);

            $logger->log("Temp File Uploaded: " .  $temp_file_path);
            $logger->log("Original File Name: " .$original_file_name);
            $logger->log("Save to File path: " .$save_to_file_path);
            $logger->log("Error: " .$error);
            $logger->log("Size: " .$size);
            $logger->log("Error Message:" .  $error_message);

            //Handle the file upload
            if (is_uploaded_file($value['tmp_name'])) {
                if (move_uploaded_file($value['tmp_name'], $save_to_file_path)) {
                    $rtnData->msg = 'success';
                    $rtnData->file = $original_file_name;
                    $rtnData->zip_link = WPBACKITUP__BACKUP_URL .'/' .$original_file_name;
                } else {
                    $error_message='File could not be saved to backup folder.';
                }
            }

            $rtnData->error = $error_message;
            break; //Only one file is ever uploaded
        }
    }

    echo json_encode($rtnData);

    $logger->log('Upload completed successfully');
    $logger->log('***END UPLOAD***');
    die();

    /******************/
    /*** Functions ***/
    /******************/
    function get_error_message($code)
    {
        if (0==$code) return '';

        switch ($code) {
            case UPLOAD_ERR_INI_SIZE:
                $message = "The uploaded file exceeds the upload_max_filesize directive in php.ini";
                break;
            case UPLOAD_ERR_FORM_SIZE:
                $message = "The uploaded file exceeds the MAX_FILE_SIZE directive that was specified in the HTML form";
                break;
            case UPLOAD_ERR_PARTIAL:
                $message = "The uploaded file was only partially uploaded";
                break;
            case UPLOAD_ERR_NO_FILE:
                $message = "No file was uploaded";
                break;
            case UPLOAD_ERR_NO_TMP_DIR:
                $message = "Missing a temporary folder";
                break;
            case UPLOAD_ERR_CANT_WRITE:
                $message = "Failed to write file to disk";
                break;
            case UPLOAD_ERR_EXTENSION:
                $message = "File upload stopped by extension";
                break;
            default:
                $message = "Unknown upload error";
                break;
        }
        return $message;
    }
