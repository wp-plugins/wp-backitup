<?php if (!defined ('ABSPATH')) die('No direct access allowed');


class WPBackItUp_Task {

	//Task Status values
	const ERROR = 'error';
	const ACTIVE ='active';
	const COMPLETE ='complete';
	const CANCELLED='cancelled';
	const QUEUED = 'queued';
	const RESUME = 'resume';


	private $log_name;

	private $job_id;
	private $name; //task name

	private $id;
	private $status;

	private $allocated_id=null;
	private $error_code=null;
	private $last_updated=null;
	private $retry_count=0;

	public function __construct($job_id,$task_name,$task_info) {
		try {
			$this->log_name = 'debug_tasks';//default log name

			if ( empty( $job_id ) ||
			     empty( $task_name ) ||
			     empty( $task_info['task_id'] ) ||
			     empty( $task_info['task_status'] )
			) {

				throw new exception( 'Cant create task object, missing parameter in constructor.' );
			}


			//Task Key Info
			$this->job_id = $job_id;
			$this->name   = $task_name;
			$this->id     = $task_info['task_id'];
			$this->status = $task_info['task_status'];

			if ( ! empty( $task_info['task_allocated_id'] ) ) {
				$this->allocated_id = $task_info['task_allocated_id'];
			}

			if ( ! empty( $task_info['task_last_updated'] ) ) {
				$this->last_updated = $task_info['task_last_updated'];
			}

			if ( ! empty( $task_info['task_retry_count'] ) ) {
				$this->retry_count = $task_info['task_retry_count'];
			}
		} catch(Exception $e) {
			error_log($e);
			WPBackItUp_LoggerV2::log_error($this->log_name,__METHOD__,'Constructor Exception: ' .$e);
		}
	}

	function __destruct() {

	}


	/**
	 * Increment the task retry count
	 */
	public function increment_retry_count(){
		WPBackItUp_LoggerV2::log_info($this->log_name,__METHOD__,'Begin');
		$this->retry_count++;
		return $this->save();
	}

	/**
	 * Save the task info to the
	 *
	 * @return mixed
	 * Returns Returns true on success and false on failure.
	 * NOTE: If the meta_value(Task Info) passed to this function is the same as the value that is already in the database, this function returns false.
	 *
	 */
	private function save(){
		WPBackItUp_LoggerV2::log_info($this->log_name,__METHOD__,'Begin');

		$meta_value = array(
			'task_id'           => $this->id,
			'task_status'       => $this->status,
			'task_allocated_id' => $this->allocated_id,
			'task_error_code'   => $this->error_code,
			'task_retry_count'  => $this->retry_count,
			'task_last_updated' => time()
		);
		WPBackItUp_LoggerV2::log_info($this->log_name,__METHOD__,'Task Info:' .var_export($meta_value,true));

		$rtn_status =update_post_meta( $this->job_id, $this->name, $meta_value );
		WPBackItUp_LoggerV2::log_info($this->log_name,__METHOD__,'Task Saved:' .$rtn_status);
		return $rtn_status;
	}


	/**
	 * @return mixed
	 */
	public function getId() {
		return $this->id;
	}

	/**
	 * @return mixed
	 */
	public function getStatus() {
		return $this->status;
	}

	/**
	 * @return mixed
	 */
	public function getAllocatedId() {
		return $this->allocated_id;
	}

	/**
	 * @return mixed
	 */
	public function getLastUpdated() {
		return $this->last_updated;
	}

	/**
	 * @return int
	 */
	public function getRetryCount() {
		return $this->retry_count;
	}


}



