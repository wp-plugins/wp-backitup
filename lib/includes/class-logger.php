<?php if (!defined ('ABSPATH')) die('No direct access allowed (logger)');

/**
 * WP BackItUp  - Logger System Class
 *
 * @package WP BackItUp
 * @author  Chris Simmons <chris.simmons@wpbackitup.com>
 * @link    http://www.wpbackitup.com
 *
 */

class WPBackItUp_Logger {

	private $dfh;
	private $logging;

	public 	$logFileName;
	public 	$logFilePath;

	public function __construct($delete_log, $path=null, $file_name=null, $debugOverride=false) {
		global $WPBackitup;

		$this->logging = $WPBackitup->logging();

        //If override debug flag then turn logging on.
        if (true===$debugOverride) $this->logging=true;

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
       $this->close_file();
   }

    public function close_file() {
        try {
            if (!is_null($this->dfh) && is_resource($this->dfh)){
                fwrite($this->dfh, "** Close LOG File ** ". PHP_EOL);
                fclose($this->dfh);
            }
        } catch(Exception $e) {
            //Dont do anything
            print $e;
        }

        $this->dfh=null;
    }

	function log($message) {
		try{
			if (true===$this->logging){	
				if (!is_null($this->dfh) && is_resource($this->dfh)){
					$date = date_i18n('Y-m-d H:i:s',current_time( 'timestamp' ));
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

    //Log Errors
    public function log_info($function,$message, $additional_message=null) {
        $function='(' . $function . ') INFO: ' . $additional_message;
        if( is_array( $message ) || is_object( $message ) ){
            $this->log($function);
            $this->log($message);
        } else {
            $this->log($function . $message);
        }
    }

    //Log Errors
    public function log_error($function,$message,$additional_message=null) {
        $function='(' . $function . ') ERROR: ' . $additional_message;
        if( is_array( $message ) || is_object( $message ) ){
            $this->log($function);
            $this->log($message);
        } else {
            $this->log($function .$message);
        }
    }

	//Log warning
	public function log_warning($function,$message,$additional_message=null) {
		$function='(' . $function . ') WARNING: ' . $additional_message;
		if( is_array( $message ) || is_object( $message ) ){
			$this->log($function);
			$this->log($message);
		} else {
			$this->log($function .$message);
		}
	}

	function log_sysinfo() {
	global $wpdb,$WPBackitup;
		try{
			if (true===$this->logging){

				$this->log("\n**SYSTEM INFO**");

				$this->log("\n--WP BackItUp Info--");

				$this->log("WPBACKITUP License Active: " . ($WPBackitup->license_active() ? 'true' : 'false'));
				$prefix='WPBACKITUP';
				foreach (get_defined_constants() as $key=>$value)
				{
					if (substr($key,0,strlen($prefix))==$prefix) {
						$this->log($key . ':' . $value);
					}
				}

				$this->log("\n--Site Info--");
				$this->log('Site URL:' . site_url());
				$this->log('Home URL:' . home_url());
				$this->log('Multisite:' . ( is_multisite() ? 'Yes' : 'No' )) ;

				$this->log("\n--Wordpress Info--");
				$this->log("Wordpress Version:" . get_bloginfo( 'version'));
				$this->log('Language:' . ( defined( 'WPLANG' ) && WPLANG ? WPLANG : 'en_US' ));
				$this->log('Table Prefix:' . 'Length: ' . strlen( $wpdb->prefix ) . '   Status: ' . ( strlen( $wpdb->prefix ) > 16 ? 'ERROR: Too long' : 'Acceptable' ));
				$this->log('WP_DEBUG:' . ( defined( 'WP_DEBUG' ) ? WP_DEBUG ? 'Enabled' : 'Disabled' : 'Not set' ));
				$this->log('Memory Limit:' . WP_MEMORY_LIMIT );

				$this->log("\n--WordPress Active Plugins--");
				// Check if get_plugins() function exists. This is required on the front end of the
				// site, since it is in a file that is normally only loaded in the admin.
				if ( ! function_exists( 'get_plugins' ) ) {
					require_once ABSPATH . 'wp-admin/includes/plugin.php';
				}

				$plugins = get_plugins();
				$active_plugins = get_option( 'active_plugins', array() );
				foreach( $plugins as $plugin_path => $plugin ) {
					if( !in_array( $plugin_path, $active_plugins ) ) continue;

					$this->log( $plugin['Name'] . ': ' . $plugin['Version']);
				}

				// WordPress inactive plugins
				$this->log("\n" . '--WordPress Inactive Plugins--');

				foreach( $plugins as $plugin_path => $plugin ) {
					if( in_array( $plugin_path, $active_plugins ) )
						continue;

					$this->log($plugin['Name'] . ': ' . $plugin['Version']);
				}

				$this->log("\n--Server Info--");
				$this->log('PHP Version:' . PHP_VERSION);
				$this->log('Webserver Info:' . $_SERVER['SERVER_SOFTWARE']);
				$this->log('MySQL Version:' . $wpdb->db_version());

				$this->log("\n--PHP Info--");
				$this->log("PHP Info:" . phpversion());
				$this->log("Operating System:" .  php_uname());

				if ( @ini_get('safe_mode') || strtolower(@ini_get('safe_mode')) == 'on' ){
					$this->log("PHP Safe Mode: On");
				} else{
					$this->log("PHP Safe Mode: Off");
				}

				if ( @ini_get('sql.safe_mode') || strtolower(@ini_get('sql.safe_mode')) == 'on' ){
                    $this->log("SQL Safe Mode: On");
				} else{
					$this->log("SQL Safe Mode: Off");
				}
				$this->log("Script Max Execution Time:" .  ini_get('max_execution_time'));
				$this->log('Memory Limit:' . ini_get( 'memory_limit' ));
				$this->log('Upload Max Size:' . ini_get( 'upload_max_filesize' ));
				$this->log('Post Max Size:' . ini_get( 'post_max_size' ));
				$this->log('Upload Max Filesize:' . ini_get( 'upload_max_filesize' ));
				$this->log('Max Input Vars:' . ini_get( 'max_input_vars' ));
				$this->log('Display Errors:' . ( ini_get( 'display_errors' ) ? 'On (' . ini_get( 'display_errors' ) . ')' : 'N/A' ));
				$this->log('Curl Installed:' . (function_exists('curl_version') ?'True' : 'False'));

				$this->log("\n**END SYSTEM INFO**");
			}
		} catch(Exception $e) {
			//Dont do anything
			print $e;
		}
	}
}