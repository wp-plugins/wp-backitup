<?php if (!defined ('ABSPATH')) die('No direct access allowed');

/**
 * WP BackItUp  - Job Class
 *
 * @package WP BackItUp
 * @author  Chris Simmons <chris.simmons@wpbackitup.com>
 * @link    http://www.wpbackitup.com
 *
 */


//Includes
if( !class_exists( 'WPBackItUp_Utility' ) ) {
	include_once 'class-utility.php';
}

class WPBackItUp_Job {

	const JOB_TITLE='wpbackitup_job';

	//job Status values
	const ERROR = 'error';
	const ACTIVE ='Active';
	const COMPLETE ='complete';
	const CANCELLED='cancelled';
	const QUEUED = 'queued';

	private $log_name;
	private $job_id;
	private $instance_id;
	private $allocated_task;

	public  $job_status;
	public  $backup_id;

	private  $job_start_time;
	private  $job_end_time;

	private $lockFile;
	private $lockFilePath;
	private $locked;

	static private $backup_tasks = array(
			1=>'task_preparing',
			2=>'task_backup_db' ,
			3=>'task_backup_siteinfo',
			4=>'task_backup_themes',
			5=>'task_backup_plugins',
			6=>'task_backup_uploads',
			7=>'task_backup_other',
			8=>'task_validate_backup',
			9=>'task_finalize_backup',
	);

	static private $restore_tasks = array(
		1=>'task_preparing',
		2=>'task_unzip_backup_set',
		3=>'task_validate_backup',
		4=>'task_create_checkpoint',
		5=>'task_stage_wpcontent',
		6=>'task_restore_wpcontent',
		8=>'task_restore_database',
	);

	static private $cleanup_tasks = array(
		1=>'task_scheduled_cleanup'
	);

	static private $cloud_upload_tasks = array(
		1=>'task_validate_cloud_credentials',
		2=>'task_upload_files_to_cloud',
		3=>'task_update_backup_status'
	);


	function __construct($job) {
		try {
			$this->log_name = 'debug_job';//default log name

			//Load of the class properties from the post object(see wp_post)
			$this->job_id=$job->ID;
			$this->instance_id=time();
			$this->job_status=$job->post_status;
			$this->backup_id=$job->post_name;

			//Deserialize content
			$content = $job->post_content;
			if (!empty($content)){
				$job_info =maybe_unserialize($content);
				if (is_array($job_info)){
					$this->job_start_time=$job_info['start_time'];
				}
			}

		} catch(Exception $e) {
			error_log($e); //Log to debug
			WPBackItUp_LoggerV2::log_error($this->log_name,__METHOD__,'Constructor Exception: ' .$e);
		}
	}

	function __destruct() {
		WPBackItUp_LoggerV2::log_info($this->log_name,__METHOD__,'Begin');
		if ($this->locked) {
			$this->release_lock();
		}
	}

	/**
	 * Get lock of possible
	 *
	 * @param $lock_file_path
	 *
	 * @return bool
	 */
	public function get_lock ($lock_file_path){
		WPBackItUp_LoggerV2::log_info($this->log_name,__METHOD__,'Begin:'.$lock_file_path);

		$this->$lock_file_path = $lock_file_path;
		try {
			$this->lockFile = fopen($this->$lock_file_path ,"w"); // open it for WRITING ("w")
			if (flock( $this->lockFile, LOCK_EX | LOCK_NB)) {
				WPBackItUp_LoggerV2::log_info($this->log_name,__METHOD__,'Process LOCK acquired');
				$this->locked=true;
			} else {
				//This is not an error, just means another process has it allocated
				WPBackItUp_LoggerV2::log_info($this->log_name,__METHOD__,'Process LOCK Failed');
				$this->locked=false;
			}

			return $this->locked;

		} catch(Exception $e) {
			WPBackItUp_LoggerV2::log_error($this->log_name,__METHOD__,'Process Lock error: ' .$e);
			$this->locked=false;
			return $this->locked;
		}
	}

	/**
	 * Release lock
	 *
	 * @return bool
	 */
	public function release_lock (){
		WPBackItUp_LoggerV2::log_info($this->log_name,__METHOD__,'Begin');

		try{
			flock($this->lockFile, LOCK_UN); // unlock the file
			WPBackItUp_LoggerV2::log_info($this->log_name,__METHOD__,'Lock released');
			$this->locked=false;
		}catch(Exception $e) {
			WPBackItUp_LoggerV2::log_error($this->log_name,__METHOD__,'Process UNLOCK error: ' .$e);
		}
	}

	public function is_job_complete() {
		WPBackItUp_LoggerV2::log_info($this->log_name,__METHOD__,'Begin' );

		$tasks = get_post_meta( $this->job_id);
		foreach($tasks as $key=>$value) {
			//Is this a task of job meta data
			if (substr($key, 0, 4)!='task')  continue;

			$task = get_post_meta($this->job_id,$key);

			//Get Task Properties
			$task_id = $task[0]['task_id'];
			$task_status = $task[0]['task_status'];
			$task_allocated_id = $task[0]['task_allocated_id'];
			$task_last_updated = $task[0]['task_last_updated'];

			if ('queued'==$task_status || 'active'==$task_status){
				WPBackItUp_LoggerV2::log_info($this->log_name,__METHOD__,'Active or Queued Task found:' . $key );
				return false;
			}
		}

		//No active or queued tasks were found
		WPBackItUp_LoggerV2::log_info($this->log_name,__METHOD__,'End - No Active or Queued Tasks found' );
		return true;

	}
	//What is the next task in the stack
	public function get_next_task(){
		WPBackItUp_LoggerV2::log_info($this->log_name,__METHOD__,'Begin');

		$this->allocated_task=null; //Set the current task to null;

		//Get the tasks -- DO I NEED TO SORT?
		$tasks = get_post_meta($this->job_id);

		//Enumerate the tasks
		foreach ($tasks as $key => $value) {

			//Is this a task of job meta data
			if (substr($key, 0, 4)!='task')  continue;

			$task = get_post_meta($this->job_id,$key);

			//Get Task Properties
			$task_id = $task[0]['task_id'];
			$task_status = $task[0]['task_status'];
			$task_allocated_id = $task[0]['task_allocated_id'];
			$task_last_updated = $task[0]['task_last_updated'];

			//if next task in stack is queued then its time to get to work
			switch ($task_status) {
				case "queued":
					//Try allocate task
					$queued_task = $this->allocate_task($this->job_id, $key,$task_id);

					//If task was allocated then update the job status to active
					if (false!==$queued_task){
						$this->set_job_status_active();
					}
					return $queued_task[0]['task_id'];

				case "active":
					//Error if >= 3 minutes since the last update
					if (time()>=$task_last_updated+WPBACKITUP__TASK_TIMEOUT_SECONDS){
						$this->update_task_status($this->job_id, $key,$task_id,'error');

						// If timeout error
						WPBackItUp_LoggerV2::log_info($this->log_name,__METHOD__,'Job:' . $key . 'Time Out' );
						
						//Update job to error also
						$this->set_job_status_error();
						return 'error_' . $task_id ;

					}else {

						WPBackItUp_LoggerV2::log_info($this->log_name,__METHOD__,'Job:' . $key . ' is still active' );
						//if its been less than 3 minutes then wait
						return false;
					}

				case "complete":
					//Do nothing - get the next task
					break;

				case "error":
					//Job should already be error but update if not
					//Update job to error also
					$this->set_job_status_error();
					return 'error_' . $task_id ;
			}
		}

		//If no more tasks then job must be done
		$this->set_job_status_complete();

		WPBackItUp_LoggerV2::log_info($this->log_name,__METHOD__,'End - no tasks to allocate');
		return false; //no tasks to allocate now but job should be complete next time
	}

	/**
	 * Allocate the task to this job - will set task status to active
	 *
	 * @param $job_id
	 * @param $key
	 * @param $task_id
	 *
	 * @return bool
	 */
	private function allocate_task($job_id, $key,$task_id){
		WPBackItUp_LoggerV2::log_info($this->log_name,__METHOD__,'Begin');

		//Allocate the task to this process
		$process_uid = uniqid();
		$this->update_task_status($job_id, $key,$task_id,'active',$process_uid);

		//Get updated task and make sure uid is good
		$updated_task = get_post_meta( $this->job_id, $key);
		$updated_task_allocated_id = $updated_task[0]['task_allocated_id'];
		if ($process_uid==$updated_task_allocated_id) {
			$this->allocated_task=$updated_task; // set the jobs allocated task

			WPBackItUp_LoggerV2::log_info($this->log_name,__METHOD__,'End - Task allocated');
			return $updated_task;
		}else{
			$this->allocated_task=null;
			WPBackItUp_LoggerV2::log_info($this->log_name,__METHOD__,'End - Task was not allocated');
			return false;
		}
	}


	/**
	 * Set the allocated task status to queued
	 */
	public function set_task_queued(){
		WPBackItUp_LoggerV2::log_info($this->log_name,__METHOD__,'Begin');

		WPBackItUp_LoggerV2::log_info($this->log_name,__METHOD__,'Task Info:');
		WPBackItUp_LoggerV2::log($this->log_name,$this->allocated_task);

		//Get allocated task Properties
		$task_id = $this->allocated_task[0]['task_id'];
		$this->update_task_status($this->job_id, $task_id,$task_id,'queued');

		WPBackItUp_LoggerV2::log_info($this->log_name,__METHOD__,'End');
	}

	/**
	 * Set the allocated task status to complete
	 */
	public function set_task_complete(){
		WPBackItUp_LoggerV2::log_info($this->log_name,__METHOD__,'Begin');

		WPBackItUp_LoggerV2::log_info($this->log_name,__METHOD__,'Task Info:');
		WPBackItUp_LoggerV2::log($this->log_name,$this->allocated_task);

		//Get allocated task Properties
		$task_id = $this->allocated_task[0]['task_id'];
		$this->update_task_status($this->job_id, $task_id,$task_id,'complete');


		//Check if this was the last task
		if ($this->is_job_complete()){
			$this->set_job_status_complete();
		}

		WPBackItUp_LoggerV2::log_info($this->log_name,__METHOD__,'End');
	}

	/**
	 * Set the allocated task status to error
	 */
	public function set_task_error($error_code){
		WPBackItUp_LoggerV2::log_info($this->log_name,__METHOD__,'Begin');

		WPBackItUp_LoggerV2::log_info($this->log_name,__METHOD__,'Task Info:');
		WPBackItUp_LoggerV2::log($this->log_name,$this->allocated_task);

		//Get allocated task Properties
		$task_id = $this->allocated_task[0]['task_id'];
		$this->update_task_status($this->job_id, $task_id,$task_id,'error','',$error_code);

		$this->set_job_status_error();

		WPBackItUp_LoggerV2::log_info($this->log_name,__METHOD__,'End');
	}


	private function update_task_status($job_id,$task_name,$task_id, $task_status, $task_allocated_id='', $task_error_code=''){
		WPBackItUp_LoggerV2::log_info($this->log_name,__METHOD__,'Begin');

		$meta_value = array(
			'task_id'           => $task_id,
			'task_status'       => $task_status,
			'task_allocated_id'   => $task_allocated_id,
			'task_error_code'   => $task_error_code,
			'task_last_updated' => time()
		);

		WPBackItUp_LoggerV2::log_info($this->log_name,__METHOD__,'End - Task Updated:' .$job_id .'-'. $task_name .'-'. $task_status);
		return update_post_meta( $job_id, $task_name, $meta_value );
	}


	public function update_job_meta($meta_name,$meta_value){
		WPBackItUp_LoggerV2::log_info($this->log_name,__METHOD__,'Begin - Update job meta:' .$this->job_id .'-'. $meta_name);

		//Encode the array values
		if (is_array($meta_value)){
			array_walk_recursive($meta_value, 'WPBackItUp_Utility::encode_items');
		}

		return update_post_meta( $this->job_id, $meta_name,$this->wpb_slash($meta_value));
	}

	/**
	 * Add slashes to a string or array of strings.
	 *
	 * This should be used when preparing data for core API that expects slashed data.
	 * This should not be used to escape data going directly into an SQL query.
	 *
	 * @since 3.6.0
	 *
	 * @param string|array $value String or array of strings to slash.
	 * @return string|array Slashed $value
	 */
	private function wpb_slash( $value ) {
		//only use on strings and arrays
		if(! is_array($value) && ! is_string($value)){
			return $value;
		}

		//only available 3.6 or later
		if (function_exists('wp_slash')) return wp_slash($value);

		if ( is_array( $value ) ) {
			foreach ( $value as $k => $v ) {
				if ( is_array( $v ) ) {
					$value[$k] = $this->wpb_slash( $v );
				} else {
					$value[ $k ] = addslashes( $v );
				}
			}
		} else {
			$value = addslashes( $value );
		}

		return $value;
	}

	/**
	 * Remove slashes from a string or array of strings.
	 *
	 * This should be used to remove slashes from data passed to core API that
	 * expects data to be unslashed.
	 *
	 * @since 3.6.0
	 *
	 * @param string|array $value String or array of strings to unslash.
	 * @return string|array Unslashed $value
	 */
	function wpb_unslash( $value ) {
        return stripslashes_deep( $value );
	}

	public function get_job_meta($meta_name){
		WPBackItUp_LoggerV2::log_info($this->log_name,__METHOD__,'Begin' .$this->job_id .'-'. $meta_name);

		$job_meta = get_post_meta($this->job_id,$meta_name,true);

		//Decode the array values
		if (is_array($job_meta)){
			array_walk_recursive($job_meta, 'WPBackItUp_Utility::decode_items');
		}

		return  $job_meta;

	}

	/**
	 * Set job status to active
	 */
	public function set_job_status_active( ) {
		$status=self::ACTIVE;
		if ($this->update_job_status($status)){
			$this->job_status = $status;
		}

		//Set job end Time
		$this->set_job_start_time();
	}

	/**
	 * Set job status to error
	 */
	public function set_job_status_error( ) {
		$status=self::ERROR;
		if ($this->update_job_status($status)){
			$this->job_status = $status;
		}

		//Set job end Time
		$this->set_job_end_time();
	}

	/**
	 * Set job status to complete
	 */
	public function set_job_status_complete( ) {
		$status=self::COMPLETE;

		if ($this->update_job_status($status)){
			$this->job_status = $status;
		}

		//Set job end Time
		$this->set_job_end_time();
	}

	/**
	 * Set job status to cancelled
	 */
	public function set_job_status_cancelled( ) {
		$status=self::CANCELLED;

		if ($this->update_job_status($status)){
			$this->job_status = $status;
		}

		//Set job end Time
		$this->set_job_end_time();
	}


	/**
	 * Update job status
	 *
	 * @param $status
	 *
	 * @return bool
	 */
	private function update_job_status($status) {
		WPBackItUp_LoggerV2::log_info($this->log_name,__METHOD__,'Begin');

		$job = array(
			'ID'            => $this->job_id,
			'post_status'   => $status
		);

		// update the job
		$job_id = wp_update_post($job );

		if (0!=$job_id) {
			WPBackItUp_LoggerV2::log_info($this->log_name,__METHOD__,'End - Backup Job status set to:' .$job_id .'-' . $status );
			return true;
		} else{
			WPBackItUp_LoggerV2::log_error($this->log_name,__METHOD__,'End - Backup Job status NOT set.');
			return false;
		}

	}

	/**
	 * Set job start time
	 *
	 * @return bool
	 */
	private function set_job_start_time() {
		WPBackItUp_LoggerV2::log_info($this->log_name,__METHOD__,'Begin');

		$this->job_start_time= time();
		$job_info = array(
			'start_time'            => $this->job_start_time,
		);

		$job = array(
			'ID'            => $this->job_id,
			'post_content'   => serialize($job_info)
		);

		// update the job info
		$job_id = wp_update_post($job );

		if (0!=$job_id) {
			WPBackItUp_LoggerV2::log_info($this->log_name,__METHOD__,'End - Backup Job start time set');
			return true;
		} else{
			WPBackItUp_LoggerV2::log_info($this->log_name,__METHOD__,'End - Backup Job start time NOT set.');
			return false;
		}

	}

	/**
	 * Set job end time
	 *
	 * @return bool
	 */
	private function set_job_end_time() {
		WPBackItUp_LoggerV2::log_info($this->log_name,__METHOD__,'Begin');

		$this->job_end_time=time();
		$job_info = array(
			'start_time'            => $this->job_start_time,
			'end_time'            => $this->job_end_time,
		);

		$job = array(
			'ID'            => $this->job_id,
			'post_content'   => serialize($job_info)
		);

		// update the job info
		$job_id = wp_update_post($job );

		if (0!=$job_id) {
			WPBackItUp_LoggerV2::log_info($this->log_name,__METHOD__,'End - Backup Job end time set');
			return true;
		} else{
			WPBackItUp_LoggerV2::log_error($this->log_name,__METHOD__,'End - Backup Job end time NOT set.');
			return false;
		}

	}

	/**---------STATICS---------***/

	/**
	 * Is there at least 1 job queued or active?
	 *
	 * @param $job_name
	 *
	 * @return bool
	 */
	public static function is_job_queued($job_name) {
		$job_logname='debug_job';
		WPBackItUp_LoggerV2::log_info($job_logname,__METHOD__,'Begin - Check Job Queue:' . $job_name);

		//Get top 1
		$args = array(
			'posts_per_page'   => 1,
			'post_type'        => $job_name,
			'post_status'      => array('queued','active'),
			'orderby'          => 'post_date',
			'order'            => 'ASC',
			'suppress_filters' => true
		);
		$jobs = get_posts( $args );
		WPBackItUp_LoggerV2::log($job_logname,$jobs);

		if (is_array($jobs) && count($jobs)>0) {
			WPBackItUp_LoggerV2::log_info($job_logname,__METHOD__,'Jobs found:' . count($jobs) );
			return true;
		}

		WPBackItUp_LoggerV2::log_info($job_logname,__METHOD__,'No jobs found:' . $job_name);
		WPBackItUp_LoggerV2::log_info($job_logname,__METHOD__,'End');
		return false;
	}

	/**
	 * get completed jobs
	 *      - complete, cancelled, error
	 *
	 * @param $job_name
	 * @param int $count
	 *
	 * @return bool
	 */
	public static function get_completed_jobs($job_name,$count=25) {
		$job_logname='debug_job';
		WPBackItUp_LoggerV2::log_info($job_logname,__METHOD__,'Begin');

		$save_last = 10;
		$count+=$save_last; //always leave the last n

		$args = array(
			'posts_per_page'   => $count,
			'post_type'        => $job_name,
			'post_status'      => array('complete','cancelled','error'),
			'orderby'          => 'post_date',
			'order'            => 'ASC',
			'suppress_filters' => true
		);
		$jobs = get_posts( $args );

		if (is_array($jobs) && count($jobs)>$save_last) {
			WPBackItUp_LoggerV2::log_info($job_logname,__METHOD__,'Jobs found:' . count($jobs));

			$diff=count($jobs)-$save_last;

			//pull off the last N
			$rtn_val = array_slice($jobs,0,$diff);
			return $rtn_val;
		}

		WPBackItUp_LoggerV2::log_info($job_logname,__METHOD__,'No jobs found:' . $job_name);
		WPBackItUp_LoggerV2::log_info($job_logname,__METHOD__,'End');
		return false;
	}

	/**
	 * Cancel all queued or active jobs
	 *
	 * @return bool
	 */
	public static function cancel_all_jobs() {
		$job_logname='debug_job';
		WPBackItUp_LoggerV2::log_info($job_logname,__METHOD__,'Begin - Cancel all jobs.');

		while (self::is_job_queued('backup')){
			$backup_job = self::get_job('backup');
			if (false!== $backup_job) {
				$backup_job->set_job_status_cancelled();
				WPBackItUp_LoggerV2::log_info($job_logname,__METHOD__,'Backup job Cancelled:' . $backup_job->get_job_id());
			}
		}

		while (self::is_job_queued('cleanup')){
			$cleanup_job = self::get_job('cleanup');
			if (false!== $cleanup_job) {
				$cleanup_job->set_job_status_cancelled();
				WPBackItUp_LoggerV2::log_info($job_logname,__METHOD__,'Cleanup job Cancelled:' . $cleanup_job->get_job_id());
			}
		}

		WPBackItUp_LoggerV2::log_info($job_logname,__METHOD__,'End - All jobs cancelled');
	}

	/**
	 * purge old jobs
	 *
	 * @param $job_name *
	 * @param int $count
	 *
	 * @return bool
	 */
	public static function purge_old_jobs($job_name,$count=25) {
		$job_logname='debug_job';
		WPBackItUp_LoggerV2::log_info($job_logname,__METHOD__,'Begin - Purge Jobs.');

		$jobs = self::get_completed_jobs($job_name,$count);
		$purge_count=0;
		foreach($jobs as $job){
			WPBackItUp_LoggerV2::log_info($job_logname,__METHOD__,'Delete Job:'.$job->ID .':' .$job->post_type .":" .$job->post_title .':' .$job->post_date);
			wp_delete_post( $job->ID, true );
			$purge_count+=1;
		}
		WPBackItUp_LoggerV2::log_info($job_logname,__METHOD__,'End - job purge complete');
		return $purge_count;
	}

	/**
	 * Gets the queued or active job on top of the stack
	 *
	 * @param $job_name
	 *
	 * @return bool|WPBackItUp_Job
	 */
	public static function get_job($job_name) {
		$job_logname='debug_job';
		WPBackItUp_LoggerV2::log_info($job_logname,__METHOD__,'Begin - Job Name: ' .$job_name);

		//Get backup on top
		$args = array(
			'posts_per_page'   => 1,
			'post_type'        => $job_name,
			'post_status'      => array('queued','active'),
			'orderby'          => 'post_date',
			'order'            => 'ASC',
		);
		$jobs = get_posts( $args );
		WPBackItUp_LoggerV2::log($job_logname,$jobs);

		if (is_array($jobs) && count($jobs)>0) {
			WPBackItUp_LoggerV2::log_info($job_logname,__METHOD__,'Job found:' . count($jobs));

			$backup_job =  new WPBackItUp_Job($jobs[0]);
			if (self::QUEUED==$backup_job->job_status){
				$backup_job->set_job_status_active();
			}
			return $backup_job;
		}

		WPBackItUp_LoggerV2::log_info($job_logname,__METHOD__,'No jobs found.');
		WPBackItUp_LoggerV2::log_info($job_logname,__METHOD__,'End');
		return false;
	}

	/**
	 * Gets a job by id
	 *
	 * @param $id
	 *
	 * @return bool|WPBackItUp_Job
	 */
	public static function get_job_by_id($id) {
		$job_logname='debug_job';
		WPBackItUp_LoggerV2::log_info($job_logname,__METHOD__,'Begin');

		$job = get_post( $id, 'OBJECT');
		WPBackItUp_LoggerV2::log($job_logname,$job);

		if (null!=$job) {
			WPBackItUp_LoggerV2::log_info($job_logname,__METHOD__,'Job found:' .$id);
			return new WPBackItUp_Job($job);
		}

		WPBackItUp_LoggerV2::log_info($job_logname,__METHOD__,'No job found with id.' . $id);
		WPBackItUp_LoggerV2::log_info($job_logname,__METHOD__,'End');
		return false;
	}

	/**
	 * Queue a job
	 *
	 * @param $job_name
	 *
	 * @return bool|WPBackItUp_Job
	 */
	public static function queue_job($job_name){
		$job_logname='debug_job';
		WPBackItUp_LoggerV2::log_info($job_logname,__METHOD__,'Begin -  Job:'. $job_name);

		$new_job = array(
			'post_title'    => self::JOB_TITLE,
			'post_name'     => time(),
			'post_status'   => 'queued',
			'post_type'     => $job_name
		);

		// Insert the post into the database
		$job_id = wp_insert_post($new_job );
		WPBackItUp_LoggerV2::log_info($job_logname,__METHOD__,'Job Created:' .$job_id);

		switch ($job_name) {
			case "restore":
				//add the tasks
				if ( false === self::create_tasks( $job_id,self::$restore_tasks ) ) {
					WPBackItUp_LoggerV2::log_info($job_logname,__METHOD__,'Restore tasks not Created - deleting job:' . $job_id );
					wp_delete_post( $job_id, true );
					return false;
				}

				break;

			case "backup":
				//add the tasks
				if ( false === self::create_tasks( $job_id,self::$backup_tasks ) ) {
					WPBackItUp_LoggerV2::log_info($job_logname,__METHOD__,'Backup tasks not Created - deleting job:' . $job_id );
					wp_delete_post( $job_id, true );
					return false;
				}

				break;

			case "cleanup":
				//add the tasks
				if ( false === self::create_tasks( $job_id,self::$cleanup_tasks ) ) {
					WPBackItUp_LoggerV2::log_info($job_logname,__METHOD__,'Cleanup tasks not Created - deleting job:' . $job_id );
					wp_delete_post( $job_id, true );
					return false;
				}
				break;

			case "cloud_upload":
				//add the tasks
				if ( false === self::create_tasks( $job_id,self::$cloud_upload_tasks ) ) {
					WPBackItUp_LoggerV2::log_info($job_logname,__METHOD__,'Cloud upload tasks not Created - deleting job:' . $job_id );
					wp_delete_post( $job_id, true );
					return false;
				}
				break;

			default://Job type not defined
				WPBackItUp_LoggerV2::log_info($job_logname,__METHOD__,'Job type not defined - deleting job:' . $job_name );
				wp_delete_post( $job_id, true );
				return false;
		}

		WPBackItUp_LoggerV2::log_info($job_logname,__METHOD__,'End');
		return self::get_job_by_id($job_id);
	}

	/**
	 * Create all the tasks for a job
	 *
	 * @param $job_id
	 *
	 * @param $tasks
	 *
	 * @return bool
	 */
	private static function create_tasks($job_id,  $tasks){
		$job_logname='debug_job';
		WPBackItUp_LoggerV2::log_info($job_logname,__METHOD__,'Begin');

		//Create the job tasks
		$last_updated_time=time();
		foreach ($tasks as $key => $value){
			$task_name = $value;
			$task_data = array(
				'task_id'     => $task_name,
				'task_status' => 'queued',
				'task_allocated_id'=>'',
				'task_last_updated'=>$last_updated_time
			);
			$task_created = update_post_meta( $job_id, $task_name, $task_data );

			if (false===$task_created){
				WPBackItUp_LoggerV2::log_error($job_logname,__METHOD__,'Tasks NOT created');
				return false;
			}
			WPBackItUp_LoggerV2::log_info($job_logname,__METHOD__,'task created:' . $task_created .':'. $task_name);
		}

		WPBackItUp_LoggerV2::log_info($job_logname,__METHOD__,'End');
		return true;

	}

	/**
	 * @return mixed
	 */
	public function get_job_start_time() {
		return $this->job_start_time;
	}

	/**
	 * @return mixed
	 */
	public function get_job_end_time() {
		return $this->job_end_time;
	}

	/**
	 * Get Job status
	 * @return mixed
	 */
	public function get_job_status() {
		return $this->job_status;
	}

	/**
	 * Get job id
	 * @return mixed
	 */
	public function get_job_id() {
		return $this->job_id;
	}

	/**
	 * @return int
	 */
	public function getInstanceId() {
		return $this->instance_id;
	}
}

