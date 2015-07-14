<?php if (!defined ('ABSPATH')) die('No direct access allowed');

/**
 * Class for Database access
 *
 * @package     WPBackItUp Database Class
 * @copyright   Copyright (c) 2015, Chris Simmons
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 *
 */

class WPBackItUp_DataAccess {

	//Status values
	const BATCH_ACTIVE = 0;
	const BATCH_COMPLETE =1;
	const BATCH_ERROR =-1;

	//Record Types
	const JOB_CONTROL_RECORD = "J";
	const JOB_ITEM_RECORD ="I";

	private $logger;

	/**
	 * Class constructor.
	 *
	 * @access public
	 * @param $batch_id
	 */
	function __construct() {
		global $wpdb,$table_prefix;

		try {
			$this->logger = new WPBackItUp_Logger(false,null,'debug_database');

			//Add tables to WPDB
			$wpdb->wpbackitup_job = $table_prefix . 'wpbackitup_job';


		} catch(Exception $e) {
			error_log($e); //Log to debug
		}
	}

	/**
	 * Save Batch of SQL values to inventory table
	 * @param $sql_values
	 *
	 * @return bool
	 */
	public function insert_job_items($sql_values) {
		$this->logger->log_info(__METHOD__,'Begin');
		global $wpdb;

		$sql_insert = "INSERT INTO $wpdb->wpbackitup_job
        (job_id, group_id, item, size_kb, create_date)
         VALUES " ;

		//Get rid of last comma and replace with  semicolon
		$sql = $sql_insert . substr_replace($sql_values, ";",-1);

		//If inserts return false
		$sql_rtn = $this->query($sql);
		if (false=== $sql_rtn ||  $sql_rtn==0 ) return false;
		else return true;

	}

	/**
	 * Create job control record
	 *
	 * @param $job_id
	 *
	 * @return bool
	 */
	public function create_job_control($job_id) {
		$this->logger->log_info(__METHOD__,'Begin');
		global $wpdb;

		$sql = $wpdb->prepare(
			"INSERT  $wpdb->wpbackitup_job
			(job_id, record_type,create_date)
		     VALUES(%d,%s,NOW())
		    ",$job_id,self::JOB_CONTROL_RECORD);

		$sql_rtn = $this->query($sql);
		if (false=== $sql_rtn ||  $sql_rtn==0 ) return false;
		else return true;

	}

	/**
	 * Update batch status record as successfully completed
	 *
	 * @param $job_id
	 *
	 * @return bool
	 */
	public function update_job_control_complete($job_id) {
		$this->logger->log_info(__METHOD__,'Begin');

		return $this->update_job_control_status($job_id,self::BATCH_COMPLETE);

	}

	/**
	 * Update batch status record
	 *
	 * @param $job_id
	 *
	 * @param $status
	 *
	 * @return bool
	 */
	private function update_job_control_status($job_id,$status) {
		$this->logger->log_info(__METHOD__,'Begin');
		global $wpdb;

		$sql = $wpdb->prepare(
			"UPDATE  $wpdb->wpbackitup_job
				set status=%s
		         ,update_date=NOW()
		     WHERE
		     	  record_type=%s
				  && job_id=%d
		    ",$status,self::JOB_CONTROL_RECORD,$job_id);

		//If query errors return false
		$sql_rtn = $this->query($sql);
		if (false=== $sql_rtn ||  $sql_rtn==0 ) return false;
		else return true;

	}


	/**
	 * Fetch batch control records older than date threshhold
	 * @param $days
	 *
	 * @return mixed
	 */
	function get_old_batch_control($days){
		global $wpdb;
		$this->logger->log_info(__METHOD__,'Begin');

		$sql_select = $wpdb->prepare(
			"SELECT * FROM $wpdb->wpbackitup_job
			          WHERE
						  record_type=%s
			          	  && create_date <= DATE(DATE_SUB(NOW(), INTERVAL %d DAY))
					  ",self::JOB_CONTROL_RECORD,$days);

		return $this->get_rows($sql_select);
	}


	/**
	 *
	 * Get all open task items (status 0 or -1) and mark them with batch id
	 *
	 * @param $batch_id
	 * @param $batch_size
	 * @param $job_id
	 * @param $group_id
	 *
	 * @return mixed
	 */
	function get_batch_open_tasks($batch_id,$batch_size,$job_id,$group_id){
		global $wpdb;
		$this->logger->log_info(__METHOD__,'Begin');

		$sql_update = $wpdb->prepare(
			"UPDATE  $wpdb->wpbackitup_job
				set batch_id=%d
				 ,retry_count=retry_count + 1
		         ,update_date=NOW()
		     WHERE
		     	  record_type=%s
				  && job_id=%d
				  && group_id=%s
				  && retry_count < 3
				  && (status=%d || status=%d)
				  LIMIT %d
		    ",$batch_id,self::JOB_ITEM_RECORD,$job_id,$group_id,self::BATCH_ACTIVE,self::BATCH_ERROR,$batch_size);

		//If no updates return false else # updated
		$sql_rtn = $this->query($sql_update);
		if (false=== $sql_rtn ||  $sql_rtn==0 ) return $sql_rtn;

		$sql_select = $wpdb->prepare(
			"SELECT * FROM $wpdb->wpbackitup_job
			          WHERE
						  record_type=%s
			              && batch_id=%d
					  ORDER BY id
					  ",self::JOB_ITEM_RECORD,$batch_id);

		return $this->get_rows($sql_select);
	}

	/**
	 *
	 * Get all completed tasks for a group
	 *
	 * @param $batch_id
	 * @param $job_id
	 * @param $group_id
	 *
	 * @return mixed
	 */
	function get_completed_tasks($job_id,$group_id){
		global $wpdb;
		$this->logger->log_info(__METHOD__,'Begin');

		$sql_select = $wpdb->prepare(
			"SELECT * FROM $wpdb->wpbackitup_job
			          WHERE
		              	record_type=%s
			          	&& job_id=%d
			          	&& group_id=%s
						&& status=%d
					  ORDER BY id
					  ",self::JOB_ITEM_RECORD,$job_id,$group_id,self::BATCH_COMPLETE);

		return $this->get_rows($sql_select);
	}

	/**
	 *
	 * delete all job records by job id
	 *
	 * @param $job_id
	 *
	 * @return mixed
	 */
	function delete_job_records($job_id){
		global $wpdb;
		$this->logger->log_info(__METHOD__,'Begin');

		$sql_update = $wpdb->prepare(
			"DELETE FROM $wpdb->wpbackitup_job
		     WHERE
				job_id=%d
		    ",$job_id);

		//If no deletes return false else # updated
		$sql_rtn = $this->query($sql_update);
		if (false=== $sql_rtn ||  $sql_rtn==0 ) return false;
		else return true;

	}

	/**
	 * Get all open task items (status 0 or -1) and mark them with batch id
	 *
	 * @param $job_id
	 * @param $group_id
	 *
	 * @return mixed
	 */
	function get_open_task_count($job_id,$group_id){
		global $wpdb;
		$this->logger->log_info(__METHOD__,'Begin');

		$sql = $wpdb->prepare(
			"SELECT count(*) as task_count FROM $wpdb->wpbackitup_job
		     WHERE
		     	  record_type=%s
				  && job_id=%d
				  && group_id=%s
				  && retry_count < 3
				  && (status=%d || status=%d)
		    ",self::JOB_ITEM_RECORD,$job_id,$group_id,self::BATCH_ACTIVE,self::BATCH_ERROR);

		$row=$this->get_row($sql);
		$this->logger->log_info(__METHOD__,'Results:'.var_export($row,true));

		return $row->task_count;
	}

	/**
	 * Set Job batch to success
	 *
	 * @param $job_id
	 * @param $batch_id
	 *
	 * @return bool
	 */
	function update_batch_complete($job_id,$batch_id){
		global $wpdb;
		$this->logger->log_info(__METHOD__,'Begin');

		$sql = $wpdb->prepare(
			"UPDATE  $wpdb->wpbackitup_job
                set status=%d
                ,update_date=NOW()
                where
                job_id=%d
                && batch_id=%d;
		    ",self::BATCH_COMPLETE,$job_id,$batch_id);

		$sql_rtn = $this->query($sql);
		if (false=== $sql_rtn ||  $sql_rtn==0 ) return false;
		else return true;
	}

	/**
	 *
	 *   PRIVATES
	 *
	 */


	/**
	 * Query (Update/Insert Sql statements)
	 *
	 * @param $sql
	 * @return mixed
	 *
	 */
	private function query($sql){
		global $wpdb;
		$this->logger->log_info(__METHOD__,'Begin');

		$wpdb_result = $wpdb->query($sql);
		$last_query = $wpdb->last_query;
		$last_error = $wpdb->last_error;

		$this->logger->log_info(__METHOD__,'Last Query:' .var_export( $last_query,true ) );
		$this->logger->log_info(__METHOD__,'Query Result: ' .($wpdb_result=== FALSE?'Query Error': $wpdb_result));

		if ($wpdb_result === FALSE && !empty($last_error)) {
			$this->logger->log_error(__METHOD__,'Last Error:' .var_export( $last_error,true ) );
		}

		return $wpdb_result;
	}

	/**
	 * Get single row
	 *
	 * @param $sql
	 * @return mixed
	 */
	private function get_row($sql){
		global $wpdb;
		$this->logger->log_info(__METHOD__,'Begin');

		$wpdb_result = $wpdb->get_row($sql);
		$last_query = $wpdb->last_query;
		$last_error = $wpdb->last_error;

		$this->logger->log_info(__METHOD__,'Last Query:' .var_export( $last_query,true ));
		$this->logger->log_info(__METHOD__,'Query Result: ' .($wpdb_result==null?'NULL': $wpdb->num_rows));

		if (null == $wpdb_result && !empty($last_error)) {
			$this->logger->log_error(__METHOD__,'Last Error:' .var_export( $last_query,true ));
		}

		return $wpdb_result;

	}

	/**
	 * Get multiple rows
	 *
	 * @param $sql
	 * @return mixed
	 */
	private function get_rows($sql){
		global $wpdb;
		$this->logger->log_info(__METHOD__,'Begin');

		$wpdb_result = $wpdb->get_results($sql);
		$last_query = $wpdb->last_query;
		$last_error = $wpdb->last_error;

		$this->logger->log_info(__METHOD__,'Last Query:' .var_export( $last_query,true ));
		$this->logger->log_info(__METHOD__,'Query Result: ' .($wpdb_result==null?'NULL': $wpdb->num_rows));

		if (null == $wpdb_result && ! empty($last_error)) {
			$this->logger->log_error(__METHOD__,'Last Error:' .var_export( $last_error,true ));
		}

		return $wpdb_result;

	}
}