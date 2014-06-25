<?php if (!defined ('ABSPATH')) die('No direct access allowed (logger)');

/**
 * WP Backitup Logging Class
 * 
 * @package WP Backitup
 * 
 * @author cssimmon
 * 
 */

class WPBackItUp_Logger {

	private $dfh;
	private $logging;

	public 	$logFileName;
	public 	$logFilePath;

	public function __construct($delete_log, $path=null, $file_name=null) {
		global $WPBackitup;

		$this->logging = $WPBackitup->logging();

		//check for optional parms
		if (!is_string($path)){
			$path = WPBACKITUP__PLUGIN_PATH .'/logs';
		}

		if (!is_string($file_name)){
			$file_name='debug';
		}


		$this->logFileName=$file_name .'.log';
		$this->logFilePath= $path .'/'. $this->logFileName;

		try {
			//If debug then open the file handle
			if (true===$this->logging){
				
				//Delete log first
				if ($delete_log && file_exists($this->logFilePath)) {
					unlink($this->logFilePath);
				}

				$this->dfh = fopen($this->logFilePath, 'a');
				fwrite($this->dfh, "** Open LOG File ** ". PHP_EOL);	
			}
		} catch(Exception $e) {
			//Dont do anything
			print $e;
		}
   }

   function __destruct() {
   		try {
	   		//fwrite($this->dfh,"***file closed***" .PHP_EOL);
	   		if (!is_null($this->dfh) && is_resource($this->dfh)){
	   			fwrite($this->dfh, "** Close LOG File ** ". PHP_EOL);		
	   			fclose($this->dfh);	       
	   		}
	   	} catch(Exception $e) {
			//Dont do anything
			print $e;
		}
   }


	function log($message) {
		try{
			if (true===$this->logging){	
				if (!is_null($this->dfh) && is_resource($this->dfh)){
					$date = date_i18n('Y-m-d Hi:i:s',current_time( 'timestamp' ));
					if( is_array( $message ) || is_object( $message ) ){
						fwrite($this->dfh, $date ." " .print_r( $message, true ) . PHP_EOL);
				     } else {
				     	fwrite($this->dfh, $date ." " .$message . PHP_EOL);			        
				     }	
				}
			}
		} catch(Exception $e) {
			//Dont do anything
			print $e;
		}
	}

	function logConstants() {
	global $WPBackitup;
		try{
			if (true===$this->logging){	
				$this->log("**SYSTEM CONSTANTS**");
				
				$this->log("Wordpress Version:" . get_bloginfo( 'version'));
				$this->log("PHP Version:" . phpversion());		
				$this->log("Operating System:" .  php_uname());
                $this->log("Safe Mode:" .  (ini_get('safe_mode') ? 'true' : 'false'));
                $this->log("Script Max Execution Time:" .  ini_get('max_execution_time'));
				$this->log("WPBackItUp License Active: " . ($WPBackitup->license_active() ? 'true' : 'false'));

				$this->log("**WPBACKITUP CONSTANTS**");

				$prefix='WPBACKITUP';
			    foreach (get_defined_constants() as $key=>$value) 
			    {
			        if (substr($key,0,strlen($prefix))==$prefix) {
			        	$this->log($key . ':' . $value); 
			        }
			    }
				$this->log("**END CONSTANTS**");
			}
		} catch(Exception $e) {
			//Dont do anything
			print $e;
		}
	}
}