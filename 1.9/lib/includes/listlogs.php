<?php
    //http://localhost/wp-390/wp-admin/admin-post.php?action=listlogs
    //http://localhost/wp-390/wp-admin/admin-post.php?action=listlogs&log=test

    $backup_folder_root = WPBACKITUP__BACKUP_PATH .'/';
    $plugin_log_folder = WPBACKITUP__PLUGIN_PATH .'/logs/';

?>
<?php

    //List the log files
    if (!isset($_REQUEST['viewlog_log']) && empty($_REQUEST['log']) ){

        //Get Zip File List
        $log_file_list = glob($backup_folder_root . "*.log");
        if (!empty($log_file_list)) {
            foreach ($log_file_list as $file)
            {
                $url='admin-post.php?action=listlogs&log=' .urlencode (basename($file,'.log'));
                $filename = basename($file);
                echo('<a href="' . $url .'" target="_blank">' .$filename .'</a></br>');

            }
        } else {
             echo('No logs in backup folder<br>');
        }

        //Get logs in logs folder
        $log_file_list = glob($plugin_log_folder . "*.log");
        if (!empty($log_file_list)) {
            foreach ($log_file_list as $file)
            {
                $url='admin-post.php?action=listlogs&log=' .urlencode (basename($file,'.log'));
                $filename = basename($file);
                echo('<a href="' . $url .'" target="_blank">' .$filename .'</a></br>');

            }
        } else {
            echo('No logs in logs folder<br>');
        }
        die();
    }

    if (isset($_REQUEST['log']) && !empty($_REQUEST['log']) ){

        //Check backups first
        $log_filename = $_REQUEST['log'] . '.log';
        $log_path = $backup_folder_root .$log_filename;
        if(file_exists($log_path) ) {
            stream_file($log_path, $log_filename);
        } else {
            //Check logs
            $log_path = $plugin_log_folder .$log_filename;;
            if(file_exists($log_path) ) {
                stream_file($log_path, $log_filename);
            } else {
                echo('No log file found in folder<br>');
            }
        }
        die();
    }


//---------------FUNCTIONS ----------------------
    function stream_file($log_path, $log_filename){
        header ('Content-type: octet/stream');
        header("Content-Disposition: attachment; filename=$log_filename");
        header("Content-Length: ".filesize($log_path));
        ob_get_clean();
        readfile($log_path);
        if (ob_get_level()>1) ob_end_flush();
    }
?>
