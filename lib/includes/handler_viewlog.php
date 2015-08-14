<?php if (!defined ('ABSPATH')) die('No direct access allowed (viewlog)');

// Checking safe mode is on/off and set time limit
if( ini_get('safe_mode') ){
   @ini_set('max_execution_time', WPBACKITUP__SCRIPT_TIMEOUT_SECONDS);
}else{
   @set_time_limit(WPBACKITUP__SCRIPT_TIMEOUT_SECONDS);
}


/**
 * WP BackItUp  - View Log Handler
 *
 * @package WP BackItUp
 * @author  Chris Simmons <chris.simmons@wpbackitup.com>
 * @link    http://www.wpbackitup.com
 *
 */


if ( isset($_REQUEST['_wpnonce']) && !empty($_REQUEST['_wpnonce'])
    && isset($_REQUEST['backup_name']) && !empty($_REQUEST['backup_name']) ){

        if ( wp_verify_nonce($_REQUEST['_wpnonce'],WPBACKITUP__NAMESPACE .'-viewlog')) {

            $backup_folder = $_REQUEST['backup_name'];
            $log_filename = $_REQUEST['backup_name']. '.log';
            $log_path = WPBACKITUP__BACKUP_PATH .'/' .$backup_folder .'/' .$log_filename ;

            if(file_exists($log_path) ) {

                header("Content-Disposition: attachment; filename=\"" . basename( $log_path ) . "\";" );
                header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
                header("Expires: Sat, 26 Jul 1997 05:00:00 GMT" );
                header("Content-type: octet/stream");
                header("Content-Length: ".filesize($log_path));

                ob_get_clean();
                readfile($log_path);
                if (ob_get_level()>1) ob_end_flush();
                exit();
            }
        }
}

//Return empty file
header ('Content-type: octet/stream');
header("Content-Disposition: attachment; filename=empty.log");
header("Content-Length: 100");
ob_get_clean();
echo('No log file found.'. PHP_EOL);
if (ob_get_level()>1) ob_end_flush();








