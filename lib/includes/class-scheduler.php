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


    private $logger;

    /**
     * Constructor
     */
    function __construct() {

        $this->logger = new WPBackItUp_Logger(false,null,'debug_scheduler');

    }

    /**
     * Destructor
     */
    function __destruct() {
        $this->logger->close_file();
    }


    /**
     * Check to see if task is ready to run
     *
     * @param $task
     * @return bool
     */
    public function isTaskScheduled($task){
        $this->logger->log('Check schedule for task: ' . $task);
        switch ($task) {
            case "backup":
                return $this->check_backup_schedule();
                break;
            case "cleanup":
                return $this->check_cleanup_schedule();
                break;
        }

        $this->logger->log('Task not found:' . $task);
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
        $this->logger->log('**Check Backup Schedule**');

        try {

            ///ONLY active premium get this feature
            if (!$WPBackitup->license_active() || 'expired'== $WPBackitup->license_status()){
                $this->logger->log('License is not active');
                return false;
            }

            //Get days scheduled to run on.
            $scheduled_dow = $WPBackitup->backup_schedule();
            $this->logger->log('Scheduled Days of week: ' .$scheduled_dow); //1=monday, 2=tuesday..

            //What is the current day of the week
            $current_datetime = current_time( 'timestamp' );
            $current_date = date("Ymd",$current_datetime);
            $current_dow = date("N",$current_datetime); //1=monday

            $this->logger->log('Current Date time:' . date( 'Y-m-d H:i:s',$current_datetime));
            $this->logger->log('Current Day of Week:' . $current_dow );

            //Get Last RUN date
            $lastrun_datetime = $WPBackitup->backup_lastrun_date();

            $lastrun_date = date("Ymd",$lastrun_datetime);
            $lastrun_dow =0;//0=none
            if ($lastrun_datetime!=-2147483648){// 1901-12-13:never run
                $lastrun_dow = date("N",$lastrun_datetime);
            }

            $this->logger->log('Last Run Date Time:' . date( 'Y-m-d H:i:s',$lastrun_datetime));
            $this->logger->log('Last Run Day of Week:' . $lastrun_dow);

            //Did backup already run today
            if ($current_date==$lastrun_date){
                $this->logger->log('Backup already ran today');
                return false;
            }

            //Should it run on this day of the week
            if (false===strpos($scheduled_dow,$current_dow)){
                $this->logger->log('Not scheduled for: ' .$current_dow);
                return false;
            }

            $this->logger->log('Backup should be run now.');
            return true;

        }catch(Exception $e) {
            $this->logger->log_error(__METHOD__,'Exception: ' .$e);
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
        $this->logger->log('**Check Cleanup Schedule**');
        try {

            //What is the current day of the week
            $current_datetime = current_time( 'timestamp' );
            $current_date = date("Ymd",$current_datetime);

            $this->logger->log('Current Date time:' . date( 'Y-m-d H:i:s',$current_datetime));

            //Get Last RUN date
            $lastrun_datetime = $WPBackitup->cleanup_lastrun_date();

            $lastrun_date = date("Ymd",$lastrun_datetime);
            $this->logger->log('Last Run Date Time:' . date( 'Y-m-d H:i:s',$lastrun_datetime));

            //Has it been at least an hour since the last cleanup?

	        $next_run_datetime=$lastrun_datetime+3600; //1 hour
	        $this->logger->log('Next Run Date Time:' . date( 'Y-m-d H:i:s',$next_run_datetime));

	        $this->logger->log('TimeToRun:' . $current_datetime . ':'.$next_run_datetime );
            if ($current_datetime>=$next_run_datetime){
	            $this->logger->log('Cleanup should be run now.');
                return true;
            }

	        $this->logger->log('Not yet time to run Cleanup.');
            return false;

        }catch(Exception $e) {
            $this->logger->log_error(__METHOD__,'Exception: ' .$e);
            return false;
        }

    }
} 