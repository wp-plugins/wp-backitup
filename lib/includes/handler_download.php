<?php if (!defined ('ABSPATH')) die('No direct access allowed (viewlog)');
@set_time_limit(WPBACKITUP__SCRIPT_TIMEOUT_SECONDS);

/**
 * WP BackItUp  - Download handler
 *
 * @package WP BackItUp
 * @author  Chris Simmons <chris.simmons@wpbackitup.com>
 * @link    http://www.wpbackitup.com
 *
 */

//Turn off output buffering if it was on.
while (@ob_end_clean());

// required for IE, otherwise Content-disposition is ignored
//@apache_setenv('no-gzip', 1); //Causes failure on siteground...research
@ini_set('zlib.output_compression', 'Off');

global $logger;
$logger = new WPBackItUp_Logger(true,null,'debug_download');

$logger->log_info(__METHOD__,$_REQUEST);

if ( isset($_REQUEST['_wpnonce']) && !empty($_REQUEST['_wpnonce'])
    && isset($_REQUEST['backup_file']) && !empty($_REQUEST['backup_file']) ) {

    if ( wp_verify_nonce( $_REQUEST['_wpnonce'], WPBACKITUP__NAMESPACE . '-download_backup' ) ) {
        $logger->log_info( __METHOD__, 'nonce verified' );

        //strip off the suffix IF one exists
        $folder_name = rtrim( $_REQUEST['backup_file'], '.zip' );;
        if ( ( $str_pos = strpos( $folder_name, '-main-' ) ) !== false ) {
            $suffix      = substr( $folder_name, $str_pos );
            $folder_name = str_replace( $suffix, '', $folder_name );
        }

        if ( ( $str_pos = strpos( $folder_name, '-others-' ) ) !== false ) {
            $suffix      = substr( $folder_name, $str_pos );
            $folder_name = str_replace( $suffix, '', $folder_name );
        }

        if ( ( $str_pos = strpos( $folder_name, '-plugins-' ) ) !== false ) {
            $suffix      = substr( $folder_name, $str_pos );
            $folder_name = str_replace( $suffix, '', $folder_name );
        }

        if ( ( $str_pos = strpos( $folder_name, '-themes-' ) ) !== false ) {
            $suffix      = substr( $folder_name, $str_pos );
            $folder_name = str_replace( $suffix, '', $folder_name );
        }

        if ( ( $str_pos = strpos( $folder_name, '-uploads-' ) ) !== false ) {
            $suffix      = substr( $folder_name, $str_pos );
            $folder_name = str_replace( $suffix, '', $folder_name );
        }

        $backup_filename = $_REQUEST['backup_file'];
        $backup_path     = WPBACKITUP__BACKUP_PATH . '/' . $folder_name . '/' . $backup_filename;
        $logger->log_info( __METHOD__, 'Backup file path:' . $backup_path );

        if ( !empty($backup_filename) && file_exists( $backup_path ) ) {
            $file_name=basename( $backup_path );
            $file_size = filesize($backup_path);
            $chunksize = 1024*1024; // how many bytes per chunk
            $buffer = '';
            $cnt =0;
            $handle = fopen($backup_path, 'rb');
            if ($handle !== false) {
                //Have the headers already been sent for some reason
                if (headers_sent()) {
                    $logger->log_error( __METHOD__, 'Headers already sent.' );
                }

                //Output Headers
                header("Pragma: public");
                header("Expires: -1");
                header("Cache-Control: public, must-revalidate, post-check=0, pre-check=0");

                header("Content-Disposition: attachment; filename=\"".urlencode($file_name)."\"");
//              header("Content-Disposition: attachment; filename=\"$file_name\"");
                header("Content-type: application/zip");
                header("Content-Length: ".$file_size);



                while (!feof($handle) &&  (connection_status()==0) ) {
                    $buffer = fread($handle, $chunksize);
                    echo $buffer;
                    @ob_flush();
                    @flush();
                }

                fclose($handle);
                $logger->log_info( __METHOD__, 'Download complete' );
                exit();

            } else {
                $logger->log_error( __METHOD__, 'File Not found' );
            }
        } else {
            $logger->log_error( __METHOD__, 'Backup file doesnt exist:' . $backup_path );
        }
    } else {
        $logger->log_error( __METHOD__, 'Bad Nonce');
    }
} else {
    $logger->log_error( __METHOD__, 'Form data missing');
}

//Return empty file
header ('Content-type: octet/stream');
header("Content-Disposition: attachment; filename=empty.log");
header("Content-Length: 100");
ob_get_clean();
echo('No backup file found.'. PHP_EOL);
if (ob_get_level()>1) ob_end_flush();





