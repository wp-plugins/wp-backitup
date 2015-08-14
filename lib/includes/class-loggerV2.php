<?php if (!defined ('ABSPATH')) die('No direct access allowed (logger)');

/**
 * WP BackItUp  - Logger System Class V2
 *
 * @package WP BackItUp
 * @author  Chris Simmons <chris.simmons@wpbackitup.com>
 * @link    http://www.wpbackitup.com
 *
 */

class WPBackItUp_LoggerV2 {

	private static $logger;

	/**
	 *  Write messages to the log
	 *
	 * @param $log_name Log Name
	 * @param $message Log Message (Array or object)	 *
	 */
	public static function log($log_name,$message) {
		try {

			$logger = self::getLogger($log_name);
			$logger->log("V2 ".var_export($message,true));

		}catch(Exception $e) {
			error_log( $e );
		}
	}

	/**
	 *  Write informational messages to the log
	 *
	 * @param $log_name Log Name
	 * @param $function Name of calling function(__METHOD__)
	 * @param $message Log Message (Array or object)
	 * @param null $additional_message  (string)
	 */
	public static function log_info($log_name, $function, $message, $additional_message = null ) {

		try {

			$logger = self::getLogger($log_name);
			$logger->log_info( "V2 ".$function, $message, $additional_message );

		}catch(Exception $e) {
			error_log( $e );
		}
	}

	/**
	 *  Write error messages to the log
	 *
	 * @param $log_name Log Name
	 * @param $function Name of calling function(__METHOD__)
	 * @param $message Log Message (Array or object)
	 * @param null $additional_message  (string)
	 */
	public static function log_error($log_name, $function,$message,$additional_message=null) {

		try {
			$logger = self::getLogger($log_name);
			$logger->log_error( "V2 ".$function, $message, $additional_message );
		}catch(Exception $e) {
			error_log( $e );
		}
	}

	/**
	 *  Write warning messages to the log
	 *
	 * @param $log_name Log Name
	 * @param $function Name of calling function(__METHOD__)
	 * @param $message Log Message (Array or object)
	 * @param null $additional_message  (string)
	 */
	public static function log_warning($log_name, $function,$message,$additional_message=null) {

		try {

			$logger = self::getLogger($log_name);
			$logger->log_warning( "V2 ".$function, $message, $additional_message );

		} catch(Exception $e) {
			error_log( $e );
		}
	}

	/**
	 *  Write system information to the log
	 *
	 * @param $log_name Log Name
	 */
	public static function log_sysinfo($log_name) {
		try {

			$logger = self::getLogger($log_name);
			$logger->log_sysinfo();

		}catch(Exception $e) {
			error_log( $e );
		}
	}


	/**
	 *  Get Logger instance
	 *
	 * @param $log_name
	 *
	 * @return mixed
	 */
	private static function getLogger($log_name) {
		try{

			if (! isset( self::$logger[$log_name])) {
				self::$logger[$log_name] = new WPBackItUp_Logger( false, null, $log_name );
			}

			return self::$logger[$log_name];

		}catch(Exception $e) {
			error_log( $e );
		}
	}

	/**                             PUBLIC METHODS                              	**/

	/**
	 *  close Log file name
	 *
	 * @param $log_name
	 *
	 * @return mixed
	 */
	public static function close($log_name) {
		try{

			$logger = self::getLogger($log_name);
			$logger->close();
			self::$logger[$log_name] = null;
			unset(self::$logger[$log_name]);

		}catch(Exception $e) {
			error_log( $e );
		}
	}


	/**
	 *  Get Log file name
	 *
	 * @param $log_name
	 *
	 * @return mixed
	 */
	public static function getLogFileName($log_name) {
		try{

			$logger = self::getLogger($log_name);
			return $logger->getLogFileName();

		}catch(Exception $e) {
			error_log( $e );
		}
	}

	/**
	 *  Get Logger instance
	 *
	 * @param $log_name
	 *
	 * @return mixed
	 */
	public static function getLogFilePath($log_name) {
		try{

			$logger = self::getLogger($log_name);
			return $logger->getLogFilePath();

		}catch(Exception $e) {
			error_log( $e );
		}
	}
}