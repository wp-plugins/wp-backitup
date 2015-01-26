<?php if (!defined ('ABSPATH')) die('No direct access allowed (upload)');

/**
 * WP BackItUp  - Upload Handler
 *
 * @package WP BackItUp
 * @author  Chris Simmons <chris.simmons@wpbackitup.com>
 * @link    http://www.wpbackitup.com
 *
 */

    /*** Includes ***/


    /*** Globals ***/
    global $logger;
    $logger = new WPBackItUp_Logger(false,null,'debug_upload');
    $backup_folder_root = WPBACKITUP__BACKUP_PATH .'/';

    //*****************//
    //*** MAIN CODE ***//
    //*****************//
    $logger->log('***BEGIN UPLOAD***');
    $logger->log($_POST);


    //verify nonce
    if ( !wp_verify_nonce($_REQUEST['_wpnonce'],WPBACKITUP__NAMESPACE .'-upload')) {
        $logger->log_error(__METHOD__,'Invalid Nonce');
        echo json_encode( array( 'error' => sprintf( __( 'Invalid Nonce',WPBACKITUP__NAMESPACE ) ) ) );
        exit;

    }

    //Check upload folder
    $upload_path = WPBACKITUP__UPLOAD_PATH;
    if (  !is_dir( $upload_path ) ){
        if ( ! mkdir( $upload_path, 0755 )){
            $logger->log_error(__METHOD__,'Upload directory is not writable, or does not exist.');
            echo json_encode( array( 'error' => sprintf( __( "Upload directory is not writable, or does not exist.", WPBACKITUP__NAMESPACE ) ) ) );
            exit;
        }
    }

    add_filter( 'upload_dir', array( $this, 'upload_dir' ) );
    add_filter( 'sanitize_file_name', array( $this, 'sanitize_file_name' ) );

    $farray = array( 'test_form' => true, 'action' => WPBACKITUP__NAMESPACE . '_plupload_action' );

    $farray['test_type'] = false;
    $farray['ext']       = 'x-gzip';
    $farray['type']      = 'application/octet-stream';

    if ( ! isset( $_POST['chunks'] ) ) {
        $farray['unique_filename_callback'] = array( $this, 'unique_filename_callback' );
    }

    $status = wp_handle_upload(
        $_FILES['async-upload'],
        $farray
    );

    //remove filters
    remove_filter( 'upload_dir', array( $this, 'upload_dir' ) );
    remove_filter( 'sanitize_file_name', array( $this, 'sanitize_file_name' ) );

    if ( isset( $status['error'] ) ) {
        $logger->log_error(__METHOD__,$status['error']);
        echo json_encode( array( 'error' => $status['error'] ) );
        exit;
    }

    if ( isset( $_POST['chunks'] ) && isset( $_POST['chunk'] ) ) {
        $chunk_id       = $_POST['chunk'];
        $zip_file_name  = basename( $_POST['name'] );
        $from_file_path = $status['file'];
        $to_file_path   = $upload_path . '/' . $zip_file_name . '_' . $chunk_id . '.zip.tmp';
        if ( ! rename( $from_file_path, $to_file_path ) ) {
            @unlink( $from_file_path );
            $logger->log_error(__METHOD__,'Cant rename file.');
            echo json_encode( array( 'error' => sprintf( __( 'Error: %s', 'wpbackitup' ), __( 'File could not be uploaded', 'wpbackitup' ) ) ) );
            exit;
        }

        // Combine all chunks when done
        if ( $_POST['chunk'] == $_POST['chunks'] - 1 ) {
            $zip_file_path = $upload_path . '/' . $zip_file_name;
            if ( $zip_file_handle = fopen( $zip_file_path, 'wb' ) ) {
                //fetch chunks in order
                for ( $i = 0; $i < $_POST['chunks']; $i ++ ) {
                    $chunk_file = $upload_path . '/' . $zip_file_name . '_' . $i . '.zip.tmp';
                    if ( $rh = fopen( $chunk_file, 'rb' ) ) {
                        while ( $line = fread( $rh, 32768 ) ) {
                            fwrite( $zip_file_handle, $line );
                        }
                        fclose( $rh );
                        @unlink( $chunk_file );
                    }
                }
                fclose( $zip_file_handle );

                $status['file'] = $zip_file_path;

                //File is finished uploading now move to backup project folder

                //remove the suffix
                $file_name = substr( basename( $zip_file_path ), 0, - 4 );

                //strip off the suffix
                $prefix      = substr( $file_name, 0, 6 );
                $suffix      = '';
                $folder_name = '';

                if ( ( $str_pos = strpos( $file_name, '-main-' ) ) !== false ) {
                    $suffix      = substr( $file_name, $str_pos );
                    $folder_name = str_replace( $suffix, '', $file_name );
                }

                if ( ( $str_pos = strpos( $file_name, '-others-' ) ) !== false ) {
                    $suffix      = substr( $file_name, $str_pos );
                    $folder_name = str_replace( $suffix, '', $file_name );
                }

                if ( ( $str_pos = strpos( $file_name, '-plugins-' ) ) !== false ) {
                    $suffix      = substr( $file_name, $str_pos );
                    $folder_name = str_replace( $suffix, '', $file_name );
                }

                if ( ( $str_pos = strpos( $file_name, '-themes-' ) ) !== false ) {
                    $suffix      = substr( $file_name, $str_pos );
                    $folder_name = str_replace( $suffix, '', $file_name );
                }

                if ( ( $str_pos = strpos( $file_name, '-uploads-' ) ) !== false ) {
                    $suffix      = substr( $file_name, $str_pos );
                    $folder_name = str_replace( $suffix, '', $file_name );
                }

                //Is this a BackItUp archive
                if ( empty( $folder_name ) || empty( $suffix ) || 'Backup' != $prefix ) {
                    $logger->log_error(__METHOD__,'Upload does not appear to be a WP BackItUp backup archive');
                    echo json_encode( array( 'error' => sprintf( __( "Upload does not appear to be a WP BackItUp backup archive file.",WPBACKITUP__NAMESPACE ) ) ) );
                    unlink( $zip_file_path );//get rid of it
                    exit;
                }

                //Does folder exist
                $backup_archive_folder = WPBACKITUP__BACKUP_PATH . '/' . $folder_name;
                if ( ! is_dir( $backup_archive_folder ) ) {
                    if ( ! mkdir( $backup_archive_folder, 0755 ) ) {
                        $logger->log_error(__METHOD__,'Upload directory is not writable');
                        echo json_encode( array( 'error' => sprintf( __( "Upload directory is not writable, or does not exist.", WPBACKITUP__NAMESPACE ) ) ) );
                        exit;
                    }
                }

                //move the file to the archive folder
                //will overwrite if exists
                $target_file = $backup_archive_folder . "/" . basename( $zip_file_path );
                if ( ! rename( $zip_file_path, $target_file ) ) {
                    $logger->log_error(__METHOD__,'Cant move zip file to backup folder');
                    echo json_encode( array( 'error' => sprintf( __( "Could not import file into WP BackItUp backup set.",WPBACKITUP__NAMESPACE ) ) ) );
                    exit;
                }
            }
        }
    }

    // send the uploaded file url in response
    $response['success'] = $status['url'];
    echo json_encode( $response );
    exit;


    /******************/
    /*** Functions ***/
    /******************/
