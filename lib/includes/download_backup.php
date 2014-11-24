<?php if (!defined ('ABSPATH')) die('No direct access allowed (viewlog)');


if ( isset($_REQUEST['_wpnonce']) && !empty($_REQUEST['_wpnonce'])
    && isset($_REQUEST['backup_name']) && !empty($_REQUEST['backup_name']) ){

        if ( wp_verify_nonce($_REQUEST['_wpnonce'],WPBACKITUP__NAMESPACE .'-download_backup')) {

	        // make sure .zip isnt included
	        $backup_filename = rtrim($_REQUEST['backup_name'], '.zip');

	        //Add zip
            $backup_filename = $backup_filename. '.zip';
            $backup_path = WPBACKITUP__BACKUP_PATH .'/' .$backup_filename ;

            if(file_exists($backup_path) ) {

	            header("Cache-Control: no-cache, must-revalidate"); // HTTP/1.1
	            header("Expires: Sat, 26 Jul 1997 05:00:00 GMT"); // Date in the past

	            header('Content-type: application/zip');
                header("Content-Disposition: attachment; filename=$backup_filename");
                header("Content-Length: ".filesize($backup_path));
                ob_get_clean();
                readfile($backup_path);
                if (ob_get_level()>1) ob_end_flush();
                die();
            }
        }
}

//Return empty file
header ('Content-type: octet/stream');
header("Content-Disposition: attachment; filename=nobackup.log");
header("Content-Length: ' .100");
ob_get_clean();
echo('No backup found.'. PHP_EOL);
//echo($_REQUEST['_wpnonce']. PHP_EOL);
//echo($_REQUEST['viewlog_log']. PHP_EOL);
if (ob_get_level()>1) ob_end_flush();








