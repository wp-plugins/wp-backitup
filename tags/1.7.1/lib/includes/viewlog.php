<?php if (!defined ('ABSPATH')) die('No direct access allowed (viewlog)');


if ( isset($_REQUEST['_wpnonce']) && !empty($_REQUEST['_wpnonce'])
    && isset($_REQUEST['viewlog_log']) && !empty($_REQUEST['viewlog_log']) ){

        if ( wp_verify_nonce($_REQUEST['_wpnonce'],WPBACKITUP__NAMESPACE .'-viewlog')) {

            $log_filename = $_REQUEST['viewlog_log']. '.log';
            $log_path = WPBACKITUP__BACKUP_PATH .'/' .$log_filename ;

            if(file_exists($log_path) ) {
                header ('Content-type: octet/stream');
                header("Content-Disposition: attachment; filename=$log_filename");
                header("Content-Length: ".filesize($log_path));
                ob_get_clean();
                readfile($log_path);
                if (ob_get_level()>1) ob_end_flush();
                die();
            }
        }
}

//Return empty file
header ('Content-type: octet/stream');
header("Content-Disposition: attachment; filename=empty.log");
header("Content-Length: 100");
ob_get_clean();
echo('No log file found.'. PHP_EOL);
//echo($_REQUEST['_wpnonce']. PHP_EOL);
//echo($_REQUEST['viewlog_log']. PHP_EOL);
if (ob_get_level()>1) ob_end_flush();








