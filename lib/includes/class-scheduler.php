<?php if (!defined ('ABSPATH')) die('No direct access allowed');

/**
 * WP BackItUp  - Scheduler Class
 *
 * @package WP BackItUp
 * @author  Chris Simmons <chris.simmons@wpbackitup.com>
 * @link    http://www.wpbackitup.com
 *
 */

class WPBackItUp_Scheduler {

    private $log_name;

    /**
     * Constructor
     */
    function __construct() {
	    try {

	        $this->log_name = 'debug_scheduler';//default log name

	    } catch(Exception $e) {
		    error_log($e);
		    WPBackItUp_LoggerV2::log_error($this->log_name,__METHOD__,'Constructor Exception: ' .$e);
	    }
    }

    /**
     * Destructor
     */
    function __destruct() {

    }


    /**
     * Check to see if task is ready to run
     *
     * @param $task
     * @return bool
     */
    public function isTaskScheduled($task){
	    WPBackItUp_LoggerV2::log_info($this->log_name,__METHOD__,'Check schedule for task: ' . $task);
        switch ($task) {
            case "backup":
                return $this->check_backup_schedule();
                break;
            case "cleanup":
                return $this->check_cleanup_schedule();
                break;
        }

	    WPBackItUp_LoggerV2::log_error($this->log_name,__METHOD__,'Task not found:' . $task);
        return false;
    }

    /**
     * Check the backup schedule to determine if the backup
     * task should be run today.
     *
     * @return bool
     */
    private function check_backup_schedule(){
        global $WPBackitup;
	    WPBackItUp_LoggerV2::log($this->log_name,'**Check Backup Schedule**');

        try {

            ///ONLY active premium get this feature
            if (!$WPBackitup->license_active() || 'expired'== $WPBackitup->license_status()){
	            WPBackItUp_LoggerV2::log_info($this->log_name,__METHOD__,'License is not active');
                return false;
            }

            //Get days scheduled to run on.
            $scheduled_dow = $WPBackitup->backup_schedule();
	        WPBackItUp_LoggerV2::log_info($this->log_name,__METHOD__,'Scheduled Days of week: ' .$scheduled_dow); //1=monday, 2=tuesday..

            //What is the current day of the week
            $current_datetime = current_time( 'timestamp' );
            $current_date = date("Ymd",$current_datetime);
            $current_dow = date("N",$current_datetime); //1=monday

	        WPBackItUp_LoggerV2::log_info($this->log_name,__METHOD__,'Current Date time:' . date( 'Y-m-d H:i:s',$current_datetime));
	        WPBackItUp_LoggerV2::log_info($this->log_name,__METHOD__,'Current Day of Week:' . $current_dow );

            //Get Last RUN date
            $lastrun_datetime = $WPBackitup->backup_lastrun_date();

            $lastrun_date = date("Ymd",$lastrun_datetime);
            $lastrun_dow =0;//0=none
            if ($lastrun_datetime!=-2147483648){// 1901-12-13:never run
                $lastrun_dow = date("N",$lastrun_datetime);
            }

	        WPBackItUp_LoggerV2::log_info($this->log_name,__METHOD__,'Last Run Date Time:' . date( 'Y-m-d H:i:s',$lastrun_datetime));
	        WPBackItUp_LoggerV2::log_info($this->log_name,__METHOD__,'Last Run Day of Week:' . $lastrun_dow);

            //Did backup already run today
            if ($current_date==$lastrun_date){
	            WPBackItUp_LoggerV2::log_info($this->log_name,__METHOD__,'Backup already ran today');
                return false;
            }

            //Should it run on this day of the week
            if (false===strpos($scheduled_dow,$current_dow)){
	            WPBackItUp_LoggerV2::log_info($this->log_name,__METHOD__,'Not scheduled for: ' .$current_dow);
                return false;
            }

	        WPBackItUp_LoggerV2::log_info($this->log_name,__METHOD__,'Backup should be run now.');
            return true;

        }catch(Exception $e) {
	        WPBackItUp_LoggerV2::log_error($this->log_name,__METHOD__,'Exception: ' .$e);
            return false;
        }

    }

    /**
     * Check the cleanup schedule to determine if the task should be run today.
     * Cleanup will be run once per day
     *
     * @return bool
     */
    private function check_cleanup_schedule(){
        global $WPBackitup;
	    WPBackItUp_LoggerV2::log($this->log_name,'**Check Cleanup Schedule**');
        try {

            //What is the current day of the week
            $current_datetime = current_time( 'timestamp' );
            $current_date = date("Ymd",$current_datetime);

	        WPBackItUp_LoggerV2::log_info($this->log_name,__METHOD__,'Current Date time:' . date( 'Y-m-d H:i:s',$current_datetime));

            //Get Last RUN date
            $lastrun_datetime = $WPBackitup->cleanup_lastrun_date();

            $lastrun_date = date("Ymd",$lastrun_datetime);
	        WPBackItUp_LoggerV2::log_info($this->log_name,__METHOD__,'Last Run Date Time:' . date( 'Y-m-d H:i:s',$lastrun_datetime));

            //Has it been at least an hour since the last cleanup?

	        $next_run_datetime=$lastrun_datetime+3600; //1 hour
	        WPBackItUp_LoggerV2::log_info($this->log_name,__METHOD__,'Next Run Date Time:' . date( 'Y-m-d H:i:s',$next_run_datetime));

	        WPBackItUp_LoggerV2::log_info($this->log_name,__METHOD__,'TimeToRun:' . $current_datetime . ':'.$next_run_datetime );
            if ($current_datetime>=$next_run_datetime){
	            WPBackItUp_LoggerV2::log_info($this->log_name,__METHOD__,'Cleanup should be run now.');
                return true;
            }

	        WPBackItUp_LoggerV2::log_info($this->log_name,__METHOD__,'Not yet time to run Cleanup.');
            return false;

        }catch(Exception $e) {
	        WPBackItUp_LoggerV2::log_error($this->log_name,__METHOD__,'Exception: ' .$e);
            return false;
        }

    }
} 