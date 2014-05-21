<?php
/**
 * WP Backitup Functions
 * 
 * @package WP Backitup
 * 
 * @author jcpeden
 * @version 1.4.2
 * @since 1.0.2
 */

// localize the plugin
function lang_setup() {
	global $WPBackitup;
    load_plugin_textdomain($WPBackitup->namespace, false, dirname(plugin_basename(__FILE__)) . '/lang/');
} 
add_action('after_setup_theme', 'lang_setup');

// include recurseZip class
if( !class_exists( 'recurseZip' ) ) {
	include_once 'includes/recurse_zip.php';
}

// include recursive iterator
if( !class_exists( 'RecursiveFilter_Iterator' ) ) {
	include_once 'includes/RecursiveFilter_Iterator.php';
}
// retrieve our license key from the DB
$license_key = trim( $this->get_option( 'license_key' ) );


//Feth the debug status and set 
global $WPBACKITUP_DEBUG;
$WPBACKITUP_DEBUG=false;
if ('enabled'==$this->get_option( 'logging' )){
	$WPBACKITUP_DEBUG= true;
}

//Check the license key
function license_active(){
	global $WPBackitup;
	$license_key = $WPBackitup->get_option( 'license_key' );
	$license_status = $WPBackitup->get_option( 'status' );

	if(false !== $license_key && false !== $license_status  && 'valid'== $license_status)
	{
		return true;
	}	
	return false;
}

//define dbSize function
function dbSize($dbname) {
	mysqli_select_db($dbname);
	$result = mysqli_query("SHOW TABLE STATUS");
	$dbsize = 0;
	while($row = mysqli_fetch_array($result)) {
	    $dbsize += $row["Data_length"] + $row["Index_length"];
	}
	return $dbsize;
}

//define formatFileSize function
function formatFileSize($bytes) 
	{
    if ($bytes >= 1073741824)
    {
        $bytes = number_format($bytes / 1073741824, 2) . ' GB';
    }
    elseif ($bytes >= 1048576)
    {
        $bytes = number_format($bytes / 1048576, 2) . ' MB';
    }
    elseif ($bytes >= 1024)
    {
        $bytes = number_format($bytes / 1024, 2) . ' KB';
    }
    elseif ($bytes > 1)
    {
        $bytes = $bytes . ' bytes';
    }
    elseif ($bytes == 1)
    {
        $bytes = $bytes . ' byte';
    }
    else
    {
        $bytes = '0 bytes';
    }

    return $bytes;
}

//define dirSize function
function dirSize($directory) {
    $size = 0;
    foreach(new RecursiveIteratorIterator(new RecursiveDirectoryIterator($directory)) as $file){
		$size+=$file->getSize();
    }
    return $size;
} 

//load backup function
function backup() {
	include_once 'includes/backup.php';
}
add_action('wp_ajax_backup', 'backup');

function deletefile()
{
	$fileToDel = str_replace('deleteRow', '', $_POST['filed']);
	$restore_path =  WPBACKITUP_CONTENT_PATH .WPBACKITUP_BACKUP_FOLDER .'/' . $fileToDel;
	_log('(functions.deletefile) Delete File:' . $restore_path);
	if (unlink($restore_path))
		echo 'deleted';
	else
		echo 'problem';
	exit(0);
}

add_action('wp_ajax_deletefile', 'deletefile');


//load restore_path function
function restore_path() {
	include_once 'includes/restore_from_path.php';
}
add_action('wp_ajax_restore_path', 'restore_path');

//load download function
//function download() {
//	if(glob(WPBACKITUP_DIRNAME . "/backups/*.zip")) {
//		foreach (glob(WPBACKITUP_DIRNAME . "/backups/*.zip") as $file) {
//			$filename = basename($file);
//			echo 'Download your last backup:</br><a href="' .WPBACKITUP_URLPATH. '/backups/' .$filename .'">' .$filename .'</a>'; 
//		}
//	} else {
//		echo 'No export file available for download. Please create one.';
//	}
//	die();
//}
//add_action('wp_ajax_download', 'download');

//Get Status Log
function getStatusLog() {
	$log = WPBACKITUP_DIRNAME .'/logs/status.log';
	if (file_exists($log)){
		unlink($log);
	}
	$fh = fopen($log, 'w') or die( "Can't write to log file" );
	return $fh;
}

//load logreader function
function logreader() {
	$log = WPBACKITUP_DIRNAME .'/logs/status.log';
	if(file_exists($log) ) {
		readfile($log);
	}
	die();
}
add_action('wp_ajax_logreader', 'logreader');

//load status check function
function statusreader() {
	$log = WPBACKITUP_DIRNAME .'/logs/restore_status.log';
	if(file_exists($log) ) {
		readfile($log);
	}
	die();
}
add_action('wp_ajax_statusreader', 'statusreader');

//define create_dir function
if(!function_exists('create_dir')) {
	function create_dir($dir) {
		if( !is_dir($dir) ) {
			@mkdir($dir, 0755);
		}
		_log('(functions.create_dir) Directory created: ' .$dir);
		return true;
	}
}

//Define recusive_copy function
if(!function_exists('recursive_copy')) {
	function recursive_copy($dir, $target_path, $ignore = array( 'cgi-bin','..','._' ) ) {
		_log('(functions.recursive_copy) IGNORE:');
		_log($ignore);
		if( is_dir($dir) ) { //If the directory exists
			if ($dh = opendir($dir) ) {
				while(($file = readdir($dh)) !== false) { //While there are files in the directory
					if ( !in_array($file, $ignore) && substr($file, 0, 1) != '.') { //Check the file is not in the ignore array
								if (!is_dir( $dir.$file ) ) {
								$fsrc = fopen($dir .$file,'r');
								$fdest = fopen($target_path .$file,'w+');
								$len = stream_copy_to_stream($fsrc,$fdest);
								fclose($fsrc);
								fclose($fdest);
						} else { //If $file is a directory
							$destdir = $target_path .$file; //Modify the destination dir
							if(!is_dir($destdir)) { //Create the destdir if it doesn't exist
								_log('(functions.recursive_copy) Create Folder: ' .$destdir);
								@mkdir($destdir, 0755);
						} 	
							recursive_copy($dir .$file .'/', $target_path .$file .'/', $ignore);
						}
					}
				}
				closedir($dh);
			}
		}		
		_log('(functions.recursive_copy) Recursive FROM: ' .$dir);
		_log('(functions.recursive_copy) Recursive Copy TO: '.$target_path);
	return true;
	}	
}

//Generate SQL to rename existing WP tables.
if(!function_exists('db_rename_wptables')) {
	function db_rename_wptables($sql_file_path,$table_prefix) {
		_log('(db_rename_wptables)Manually Create SQL Backup File:'.$sql_file_path);
		
		$mysqli = db_get_sqlconnection();
		if (false===$mysqli) {
		 	return false;
		}

		$tables = array() ; 

		// Exploring what tables this database has
		$sql = "SHOW TABLES WHERE `Tables_in_" .DB_NAME ."` NOT LIKE 'save_%'";
		$result = $mysqli->query($sql);

		// Cycle through "$result" and put content into an array
		while ($row = $result->fetch_row()) {
			$tables[] = $row[0] ;
		}
		
		// Close the connection
		$mysqli->close() ;

		// Cycle through each  table
		$firstPass=true;
		foreach($tables as $table) { 
			$source_table=$table;
			$target_table='save_'. $source_table;
		
			if($firstPass){
				$firstPass=false;

				// Script Header Information
				$return = '';
				$return .= "--\n";
				$return .= "-- WP Backitup Rename WP Tables \n";
				$return .= "--\n";
				$return .= '-- Created: ' . date("Y/m/d") . "\n";
				$return .= "--\n";
				$return .= "-- Database : " . DB_NAME . "\n";
				$return .= "--\n";
				$return .= "-- --------------------------------------------------\n";
				$return .= "-- ---------------------------------------------------\n";
				$return .= 'SET AUTOCOMMIT = 0 ;' ."\n" ;
				$return .= "--\n" ;
				$return	.= 'RENAME TABLE '. "\n"; 	
			}
			else{
				$return	.= "\n"  . ',';	
			}

			$return	.= '`'.$source_table.'` TO `' . $target_table  . '` ';
		}

		$return	.=';' . "\n" ;
		$return .= 'COMMIT ; '  . "\n" ;
		$return .= 'SET AUTOCOMMIT = 1 ; ' . "\n"  ; 
		
		//save file if there were any tables in the database
		if (count($tables)>0){
			$handle = fopen($sql_file_path,'w+');
			fwrite($handle,$return);
			fclose($handle);
			_log('(db_rename_wptables)SQL Backup File Created:' .$sql_file_path);	
		}

		if (!file_exists($sql_file_path)){
	    	_log('(db_rename_wptables) SQL file doesnt exist: ' .$sql_file_path);
        	return false;
		} 

	    return true;
	}
}

if(!function_exists('db_get_hostonly')) {
	function db_get_hostonly($db_host) {
 		//Check for port
        $host_array = explode(':',$db_host);
        if (is_array($host_array)){
			return $host_array[0];
        }
        return $db_host;
	}
}
//Get rid of the port if its in the hostname
if(!function_exists('db_get_portonly')) {
	function db_get_portonly($db_host) {
 		//Check for port
        $host_array = explode(':',$db_host);
        if (is_array($host_array) && count($host_array)>1){
			return $host_array[1];
        }
        
        return false;
	}
}

//Get SQL connection
if(!function_exists('db_get_sqlconnection')) {
	function db_get_sqlconnection() {
		_log('(functions.db_get_sqlconnection)Get SQL connection to database.');
		$db_name = DB_NAME; 
        $db_user = DB_USER;
        $db_pass = DB_PASSWORD; 
        $db_host = db_get_hostonly(DB_HOST);
        $db_port = db_get_portonly(DB_HOST);

        _log('(functions.db_get_sqlconnection)Host:' . $db_host);
        _log('(functions.db_get_sqlconnection)Port:' . $db_port);     
      
      	if (false===$db_port){
      		$mysqli = new mysqli($db_host , $db_user , $db_pass , $db_name);
      	}
        else {
			$mysqli = new mysqli($db_host , $db_user , $db_pass , $db_name,$db_port);
        }
		
		if ($mysqli->connect_errno) {
			_log('(functions.db_get_sqlconnection)Cannot connect to database.' . $mysqli->connect_error);
		   	return false;
		}
		return $mysqli;
    }
}

//Define DB backup function
if(!function_exists('db_backup')) {
	function db_backup($sql_file_path) {
		_log('(functions.db_backup)Manually Create SQL Backup File:'.$sql_file_path);
		
		$mysqli = db_get_sqlconnection();
		$mysqli->set_charset('utf8');

		if (false===$mysqli) {
		 	return false;
		}

		// Script Header Information
		$return  = '';
		$return .= "-- ------------------------------------------------------\n";
		$return .= "-- ------------------------------------------------------\n";
		$return .= "--\n";
		$return .= "-- WP BackItUp Manual Database Backup \n";
		$return .= "--\n";
		$return .= '-- Created: ' . date("Y/m/d") . ' on ' . date("h:i") . "\n";
		$return .= "--\n";
		$return .= "-- Database : " . DB_NAME . "\n";
		$return .= "--\n";
		$return .= "-- ------------------------------------------------------\n";
		$return .= "-- ------------------------------------------------------\n";
		$return .= 'SET AUTOCOMMIT = 0 ;' ."\n" ;
		$return .= 'SET FOREIGN_KEY_CHECKS=0 ;' ."\n" ;
		$return .= "\n";

        $return .= '/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;' ."\n" ;
        $return .= '/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;' ."\n" ;
        $return .= '/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;' ."\n" ;
        $return .= '/*!40101 SET NAMES utf8 */;' ."\n" ;

		$tables = array() ; 

		// Exploring what tables this database has
		$result = $mysqli->query('SHOW TABLES' ) ; 

		// Cycle through "$result" and put content into an array
		while ($row = $result->fetch_row()) {
			$tables[] = $row[0] ;
		}

		// Cycle through each  table
		foreach($tables as $table) { 
			// Get content of each table
			$result = $mysqli->query('SELECT * FROM '. $table) ; 

			// Get number of fields (columns) of each table
			$num_fields = $mysqli->field_count  ;
			
			// Add table information
			$return .= "--\n" ;
			$return .= '-- Table structure for table `' . $table . '`' . "\n" ;
			$return .= "--\n" ;
			$return.= 'DROP TABLE  IF EXISTS `'.$table.'`;' . "\n" ; 
			
			// Get the table-shema
			$shema = $mysqli->query('SHOW CREATE TABLE '.$table) ;
			
			// Extract table shema 
			$tableshema = $shema->fetch_row() ; 
			
			// Append table-shema into code
			$return.= $tableshema[1].";" . "\n\n" ; 
			
			// Cycle through each table-row
			while($rowdata = $result->fetch_row()) { 
			
				// Prepare code that will insert data into table 
				/*Script to take the backup of complete wordpress database tables with there strucutre and data
				* @author - rajeev sharma @ matrix
				*/
				$return.= 'INSERT INTO '.$table.' VALUES(';
				for($j=0; $j<$num_fields; $j++){
				        $rowdata[$j] = addslashes($rowdata[$j]);
						$rowdata[$j] = str_replace("\n","\\n",$rowdata[$j]);

						if (isset($rowdata[$j])) {
							 $return.= '"'.$rowdata[$j].'"' ;
						 } else {
						 	if (is_null($rowdata[$j])) {
						 		$return.= 'NULL';//Dont think this is working but not causing issues	
						 	} else {
						 		$return.= '""';
						 	}
						  }
				  		
				        if ($j<($num_fields-1)) { $return.= ','; }
				}
				$return.= ");\n";
			} 
			$return .= "\n\n" ; 
		}

		// Close the connection
		$mysqli->close() ;
		$return .= 'SET FOREIGN_KEY_CHECKS = 1 ; '  . "\n" ; 
		$return .= 'COMMIT ; '  . "\n" ;
		$return .= 'SET AUTOCOMMIT = 1 ; ' . "\n"  ; 
		
		//save file
		$handle = fopen($sql_file_path,'w+');
		fwrite($handle,$return);
		fclose($handle);
		clearstatcache();

		//Did the export work
		if (!file_exists($sql_file_path) || filesize($sql_file_path)<=0) {
			_log('(functions.db_backup) Failure: SQL Export file was empty or didnt exist.');
			return false;
		}

		_log('(functions.db_backup)SQL Backup File Created:'.$sql_file_path);		
	    return true;
	}
}

if(!function_exists('db_SQLDump')) {
	function db_SQLDump($sql_file_path) { 

			_log('(functions.db_SQLDump) SQL Dump: ' .$sql_file_path);

            $db_name = DB_NAME; 
            $db_user = DB_USER;
            $db_pass = DB_PASSWORD; 
            $db_host = db_get_hostonly(DB_HOST);
        	$db_port = db_get_portonly(DB_HOST);
			
			//This is to ensure that exec() is enabled on the server           
			if(exec('echo EXEC') == 'EXEC') {
				try {
					$process = 'mysqldump';

		             $command = $process
		        	 . ' --host=' . $db_host;
					
					//Check for port
		        	 if (false!==$db_port){
		        	 	$command .=' --port=' . $db_port;
		        	 }	

		        	 $command .=
		        	   ' --user=' . $db_user
		        	 . ' --password=' . $db_pass	        	 
		        	 .=' ' . $db_name		        	 
		        	 . ' > "' . $sql_file_path .'"';

					//_log('(functions.db_SQLDump)Execute command:' . $command);

            		exec($command,$output,$rtn_var);
		            _log('(functions.db_SQLDump)Execute output:');
		            _log($output);
		            _log('Return Value:' .$rtn_var);

		            //0 is success
		            if ($rtn_var>0){
		            	return false;
		            }

            		//Did the export work
            		clearstatcache();
	           		if (!file_exists($sql_file_path) || filesize($sql_file_path)<=0) {
	           			_log('(functions.db_SQLDump) Failure: Dump was empty or missing.');
	           			return false;
	           		}	
	           	} catch(Exception $e) {
                 	_log('(functions.db_SQLDump) Exception: ' .$e);
                 	return false;
                }
            }
            else
            {
            	_log('(functions.db_SQLDump) Failure: Exec() disabled.');
            	return false;
            }

            _log('(functions.db_SQLDump) SQL Dump completed.');
            return true;
	}
}
//define db_import function
if(!function_exists('db_run_sql')) {
	function db_run_sql($sql_file) {
		_log('(functions.db_run_sql)SQL Execute:' .$sql_file);

		//Is the backup sql file empty
		if (!file_exists($sql_file) || filesize($sql_file)<=0) {
			_log('(functions.db_run_sql) Failure: SQL File was empty:' .$sql_file);
			return false;
		} 

		//This is to ensure that exec() is enabled on the server           
		if(exec('echo EXEC') != 'EXEC') {
	    	_log('(functions.db_run_sql) Failure: Exec() disabled.');
           	return false;
		}

		try {

			$db_name = DB_NAME;
            $db_user = DB_USER;
            $db_pass = DB_PASSWORD; 
            $db_host = db_get_hostonly(DB_HOST);
        	$db_port = db_get_portonly(DB_HOST);

            $process = 'mysql';
            $command = $process
        	. ' --host=' . $db_host
        	. ' --user=' . $db_user 
        	. ' --password=' . $db_pass 
        	. ' --database=' . $db_name
        	. ' --execute="SOURCE ' . $sql_file .'"';

			_log('(functions.db_run_sql)Execute command:' . $command);

            $output = shell_exec($command);
            exec($command,$output,$rtn_var);
            _log('(functions.db_run_sql)Execute output:');
            _log($output);
            _log('Return Value:' .$rtn_var);

            //0 is success
            if ($rtn_var>0){
            	return false;
            }

    	}catch(Exception $e) {
 			_log('(functions.db_run_sql) Exception: ' .$e);
 			return false;
        }

		//Success   
		_log('(functions.db_run_sql)SQL Executed successfully:' .$sql_file);
		return true;
	}
}

//Define the create_siteinfo function
if(!function_exists('create_siteinfo')) {
	function create_siteinfo($path, $table_prefix) {
		$siteinfo = $path ."backupsiteinfo.txt"; 
		$handle = fopen($siteinfo, 'w+');
		$entry = site_url( '/' ) ."\n$table_prefix";
		fwrite($handle, $entry); 
		fclose($handle);
		_log('(funtions.create_siteinfo) Site Info created:'.$siteinfo); 
		return true;
	}
}

if(!function_exists('delete_allbutzips')){
	function delete_allbutzips($dir){		  
		$ignore = array('cgi-bin','.','..','._');
		if( is_dir($dir) ){
			if($dh = opendir($dir)) {
				while( ($file = readdir($dh)) !== false ) {
					$ext = pathinfo($file, PATHINFO_EXTENSION);
					if (!in_array($file, $ignore) && substr($file, 0, 1) != '.' && $ext!="zip") { //Check the file is not in the ignore array
						if(!is_dir($dir .'/'. $file)) {
							unlink($dir .'/'. $file);
						} else {
							recursive_delete($dir.'/'. $file, $ignore);
						}
					}
				}
			}
			@rmdir($dir);	
			closedir($dh);
		}
		_log('(funtions.delete_allbutzips) Delete all but zips completed:'.$dir);
	return true;
	}
}


//Recursively delete all children
if(!function_exists('delete_children_recursive')){
	function delete_children_recursive($path, $ignore = array('cgi-bin','._'))
	{
	    if (is_dir($path))
	    {
	    	_log('(delete_children_recursive) Ignore:');
	    	_log($ignore);
	    	$iterator = new RecursiveDirectoryIterator($path);
	    	$iterator->setFlags(RecursiveDirectoryIterator::SKIP_DOTS);
	    	$filter = new RecursiveFilter_Iterator($iterator);
	    	$filter->set_filter($ignore);

	    	$all_files  = new RecursiveIteratorIterator($filter,RecursiveIteratorIterator::CHILD_FIRST);
	 
	        foreach ($all_files as $file)
	        {
	            if ($file->isDir())
	            {
	            	_log('(delete_recursive_new) delete folder:'.$file);
		            rmdir($file->getPathname());
	            }
	            else
	            {
	            	_log('(delete_recursive_new) delete file:'.$file);
	                unlink($file->getPathname());
	            }
	        }
	    }
	    return true;
	}
}

//Define recursive_delete function
if(!function_exists('recursive_delete')){
	function recursive_delete($dir, $ignore = array('cgi-bin','.','..','._') ){		  
		if( is_dir($dir) ){
			if($dh = opendir($dir)) {
				while( ($file = readdir($dh)) !== false ) {
					if (!in_array($file, $ignore) && substr($file, 0, 1) != '.') { //Check the file is not in the ignore array
						if(!is_dir($dir .'/'. $file)) {
							unlink($dir .'/'. $file);
						} else {
							recursive_delete($dir.'/'. $file, $ignore);
						}
					}
				}
			}
			_log('(functions.recursive_delete) Folder Deleted:' .$dir);
			@rmdir($dir);	
			closedir($dh);
		}
	return true;
	}
}

//Define zip function
function zip($source, $destination, $ignore) {
    if (is_string($source)) $source_arr = array($source); // convert it to array
    if (!extension_loaded('zip')) {
        return false;
    }
    $zip = new ZipArchive();
    if (!$zip->open($destination, ZIPARCHIVE::CREATE)) {
        return false;
    }
    foreach ($source_arr as $source) {
        if (!file_exists($source)) continue;
		$source = str_replace('\\', '/', realpath($source));
		if (is_dir($source) === true) {
			$files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($source), RecursiveIteratorIterator::SELF_FIRST);
			foreach ($files as $file) {
					if (!preg_match($ignore, $file)) {
					$file = str_replace('\\', '/', realpath($file));
					if (is_dir($file) === true) {
						$zip->addEmptyDir(str_replace($source . '/', '', $file . '/'));
					} else if (is_file($file) === true) {
						$zip->addFromString(str_replace($source . '/', '', $file), file_get_contents($file));
					}
				}
			}
		} else if (is_file($source) === true) {
			$zip->addFromString(basename($source), file_get_contents($source));
		}
    }
    return $zip->close();
}


//Disable presstrends v 1.6.1
function load_presstrends() {
	// global $WPBackitup;
	// _log('Load Presstrends');
	// if($WPBackitup->get_option( 'presstrends' ) == 'enabled') {
	// 	_log('Presstrends enabled');
	// 	// PressTrends Account API Key
	// 	$api_key = '7s4lfc8du5we4cjcdcw7wv3bedn596gjxmgy';
	// 	$auth    = 'uu8dz66bqreltwdq66hjculnyqkkwofy5';

	// 	// Start of Metrics
	// 	global $wpdb;
	// 	$data = get_transient( 'presstrends_cache_data' );
	// 	if ( !$data || $data == '' ) {
	// 		_log('Presstrends cached data:' . $data);
	// 		$api_base = 'http://api.presstrends.io/index.php/api/pluginsites/update/auth/';
	// 		$url      = $api_base . $auth . '/api/' . $api_key . '/';

	// 		$count_posts    = wp_count_posts();
	// 		$count_pages    = wp_count_posts( 'page' );
	// 		$comments_count = wp_count_comments();

	// 		// wp_get_theme was introduced in 3.4, for compatibility with older versions, let's do a workaround for now.
	// 		if ( function_exists( 'wp_get_theme' ) ) {
	// 			$theme_data = wp_get_theme();
	// 			$theme_name = urlencode( $theme_data->Name );
	// 		} else {
	// 			$theme_data = get_theme_data( get_stylesheet_directory() . '/style.css' );
	// 			$theme_name = $theme_data['Name'];
	// 		}

	// 		$plugin_name = '&';
	// 		foreach ( get_plugins() as $plugin_info ) {
	// 			$plugin_name .= $plugin_info['Name'] . '&';
	// 		}
	// 		// CHANGE __FILE__ PATH IF LOCATED OUTSIDE MAIN PLUGIN FILE
	// 		$plugin_data         = get_plugin_data( __FILE__ );
	// 		$posts_with_comments = $wpdb->get_var( "SELECT COUNT(*) FROM $wpdb->posts WHERE post_type='post' AND comment_count > 0" );
	// 		$data                = array(
	// 			'url'             => stripslashes( str_replace( array( 'http://', '/', ':' ), '', site_url() ) ),
	// 			'posts'           => $count_posts->publish,
	// 			'pages'           => $count_pages->publish,
	// 			'comments'        => $comments_count->total_comments,
	// 			'approved'        => $comments_count->approved,
	// 			'spam'            => $comments_count->spam,
	// 			'pingbacks'       => $wpdb->get_var( "SELECT COUNT(comment_ID) FROM $wpdb->comments WHERE comment_type = 'pingback'" ),
	// 			'post_conversion' => ( $count_posts->publish > 0 && $posts_with_comments > 0 ) ? number_format( ( $posts_with_comments / $count_posts->publish ) * 100, 0, '.', '' ) : 0,
	// 			'theme_version'   => $plugin_data['Version'],
	// 			'theme_name'      => $theme_name,
	// 			'site_name'       => str_replace( ' ', '', get_bloginfo( 'name' ) ),
	// 			'plugins'         => count( get_option( 'active_plugins' ) ),
	// 			'plugin'          => urlencode( $plugin_name ),
	// 			'wpversion'       => get_bloginfo( 'version' ),
	// 		);

	// 		foreach ( $data as $k => $v ) {
	// 			$url .= $k . '/' . $v . '/';
	// 		}
	// 		_log('Prestrends URL' . $url);
	// 		//wp_remote_get( $url );  - disable usage tracking
	// 		set_transient( 'presstrends_cache_data', $data, 60 * 60 * 24 );
	// 	}
	// }
}

// PressTrends WordPress Action
add_action('admin_init', 'load_presstrends');

//Get Status Log
if(!function_exists('deleteDebugLog')){
	function deleteDebugLog() {
		global $WPBACKITUP_DEBUG;
		if ($WPBACKITUP_DEBUG===true){
			$date = date_i18n('Y-m-d',current_time( 'timestamp' ));
			$mydebugLog = WPBACKITUP_DIRNAME ."/logs/debug_" .$date .".log";	
			if (file_exists($mydebugLog)){
				try{
					unlink($mydebugLog);
				} catch(Exception $e) {
					//Dont do anything
				}
			}

			$debugLog = WPBACKITUP_CONTENT_PATH ."debug.log";	
			if (file_exists($debugLog)){
				try{
					unlink($debugLog);
				} catch(Exception $e) {
					//Dont do anything
				}
			}	
		}
	}
}

//Get debug Log
if(!function_exists('getDebugLogFile')){
	function getDebugLogFile() {
		try {
			global $WPBACKITUP_DEBUG;
			if ($WPBACKITUP_DEBUG===true){
				//Check to see if File exists
				$date = date_i18n('Y-m-d',current_time( 'timestamp' ));
				$debugLog = WPBACKITUP_DIRNAME ."/logs/debug_" .$date .".log";	
				$dfh = fopen($debugLog, 'a');	
				return $dfh;		
			}
		} catch(Exception $e) {
			//Dont do anything
		}
	}
}

// //load logWriter function
if(!function_exists('_log')){
	function _log($message) {
		//Is debug ON
		global $WPBACKITUP_DEBUG;
		try{
			if ($WPBACKITUP_DEBUG===true){
				$dfh = getDebugLogFile(); //Get File
				if (!is_null($dfh)){
					$date = date_i18n('Y-m-d Hi:i:s',current_time( 'timestamp' ));
					if( is_array( $message ) || is_object( $message ) ){
						fwrite($dfh, $date ." " .print_r( $message, true ) . PHP_EOL);
				     } else {
				     	fwrite($dfh, $date ." " .$message . PHP_EOL);			        
				     }	
				     fclose($dfh);					
				}
			}
		} catch(Exception $e) {
				//Dont do anything
		}
	}
}

//Log all the constants
if(!function_exists('_log_constants')){
	function _log_constants() {
		_log("**SYSTEM CONSTANTS**");
		_log("WPBackItUp License Active: " . (license_active() ? 'true' : 'false'));	
		_log("Wordpress Version:" . get_bloginfo( 'version'));
		_log("PHP Version:" . phpversion());		
		_log("Operating System:" .  php_uname());		

		_log("**CONSTANTS**");
		_log("WPBACKITUP_VERSION:" . WPBACKITUP_VERSION);
		_log("WPBACKITUP_DIRNAME:" . WPBACKITUP_DIRNAME);
		_log("WPBACKITUP_DIR_PATH:" . WPBACKITUP_DIRNAME);
		_log("WPBACKITUP_CONTENT_PATH:" . WPBACKITUP_CONTENT_PATH);
		_log("WPBACKITUP_BACKUP_FOLDER:" . WPBACKITUP_BACKUP_FOLDER);
		_log("WPBACKITUP_RESTORE_FOLDER:" . WPBACKITUP_RESTORE_FOLDER);
		_log("WPBACKITUP_URLPATH:" . WPBACKITUP_URLPATH);
		_log("WPBACKITUP_BACKUPFILE_URLPATH:" . WPBACKITUP_BACKUPFILE_URLPATH );
		_log("IS_AJAX_REQUEST:" . IS_AJAX_REQUEST );
		_log("WPBACKITUP_SITE_URL:" . WPBACKITUP_SITE_URL ); 
		_log("WPBACKITUP_ITEM_NAME:" . WPBACKITUP_ITEM_NAME ); 
		_log("WPBACKITUP_PLUGIN_FOLDER:" . WPBACKITUP_PLUGIN_FOLDER );
		_log("WPBACKITUP_SQL_DBBACKUP_FILENAME:" . WPBACKITUP_SQL_DBBACKUP_FILENAME);
		_log("WPBACKITUP_SQL_TABLE_RENAME_FILENAME:" . WPBACKITUP_SQL_TABLE_RENAME_FILENAME);
		_log("WPBACKITUP_PLUGINS_PATH:" . WPBACKITUP_PLUGINS_PATH);
		_log("WPBACKITUP_PLUGINS_FOLDER:" . WPBACKITUP_PLUGINS_FOLDER);
		_log("WPBACKITUP_THEMES_PATH:" . WPBACKITUP_THEMES_PATH);	
		_log("WPBACKITUP_THEMES_FOLDER:" . WPBACKITUP_THEMES_FOLDER);		
		_log("** END CONSTANTS**");
	}
}

// if(!function_exists('redo_to_checkpoint')) {
// 	function redo_to_checkpoint($checkpoint) {

//             if($checkpoint == "db")
//             {
//                 if( glob($restoration_dir_path . "*.cur") ) {
//                     //collect connection information from form
//                     fwrite($fh, '<div class="database">In Progress</div>');
//                     include_once WP_DIR_PATH .'/wp-config.php';
//                     //Add user to DB in v1.0.5
//                     $user_id = $_POST['user_id'];
//                     //Connect to DB
//                     $output = db_import($restoration_dir_path, $import_siteurl, $current_siteurl, $table_prefix, $import_table_prefix, $dbc); 
//                 }

//             }
            
// 	}
// }