<?php

global $WPBackitup;

$logger = new WPBackItUp_Logger(false);

$backupCount = $WPBackitup->backup_count();
$logger->log('Count: ' .$backupCount);
$WPBackitup->increment_backup_count();


$logger->log('HERE');

    //do_action('wpbackitup_resume_backup',$WPBackitup->backup_count());
    $current_time =  time();
    //Add backup scheduled if doesnt exist
    wp_schedule_single_event( time()+30, 'wpbackitup_resume_backup');





