<?php if (!defined ('ABSPATH')) die('No direct access allowed');

/**
 * WP BackItUp -  Backup Class
 *
 * @package WP BackItUp
 * @author  Chris Simmons <chris.simmons@wpbackitup.com>
 * @link    http://www.wpbackitup.com
 *
 */

/*** Includes ***/
// include file system class
if( !class_exists( 'WPBackItUp_Filesystem' ) ) {
    include_once 'class-filesystem.php';
}



class WPBackItUp_Backup {

	private $log_name;

	//Public Properties
	public  $backup_name;
	//public  $backup_filename;
	public  $backup_project_path;
	public  $backup_folder_root;
	public  $restore_folder_root;
	public  $backup_retained_number;
    public  $backup_retained_days;

    //scheduled,manual,none
    public  $backup_type;

    private static $lockFileName;
    private static $lockFile;


	//-------------STATIC FUNCTIONS-------------------//



	//-------------END STATIC FUNCTIONS-------------------//

	function __construct($log_name,$backup_name, $backup_type) {
		global $WPBackitup;
		try {
			$this->log_name = 'debug_backup';//default log name
			if (is_object($log_name)){
				//This is for the old logger
				$this->log_name = $log_name->getLogFileName();
			} else{
				if (is_string($log_name) && isset($log_name)){
					$this->log_name = $log_name;
				}
			}

            $this->backup_type=$backup_type;


			$this->backup_name=$backup_name;
			//$this->backup_filename=$backup_name . '.tmp';

			$backup_project_path = WPBACKITUP__BACKUP_PATH .'/TMP_'. $backup_name .'/';

			$backup_folder_root =WPBACKITUP__BACKUP_PATH  .'/';
			$restore_folder_root = WPBACKITUP__RESTORE_FOLDER;

			$this->backup_project_path=$backup_project_path;
			$this->backup_folder_root=$backup_folder_root;
			$this->restore_folder_root=$restore_folder_root;

			$this->backup_retained_number = $WPBackitup->backup_retained_number();
            $this->backup_retained_days = WPBACKITUP__BACKUP_RETAINED_DAYS; //Prob need to move this to main propery

		} catch(Exception $e) {
			error_log($e);
			WPBackItUp_LoggerV2::log_error($this->log_name,__METHOD__,'Constructor Exception: ' .$e);
            throw $e;
		}
   }

   function __destruct() {
       //Call end just in case
       $this->end();
   }


    /**
     * Begin backup process - Only one may be running at a time
     * @return bool
     */
    public static function start (){
	    $lockfile_logname='debug_lock';

         try {
	        self::$lockFileName = WPBACKITUP__PLUGIN_PATH .'logs/wpbackitup_lock.lock';
	        WPBackItUp_LoggerV2::log_info($lockfile_logname,__METHOD__,'Begin - Lock File:' . self::$lockFileName);

	        self::$lockFile = fopen(self::$lockFileName ,"w"); // open it for WRITING ("w")
            if (flock( self::$lockFile, LOCK_EX | LOCK_NB)) {
	            WPBackItUp_LoggerV2::log_info($lockfile_logname,__METHOD__,'Process LOCK acquired');
                return true;
            } else {
	            WPBackItUp_LoggerV2::log_info($lockfile_logname,__METHOD__,'Process LOCK failed');
                return false;
            }

        } catch(Exception $e) {
	         WPBackItUp_LoggerV2::log_error($lockfile_logname,__METHOD__,'Process Lock error: ' .$e);
            return false;
      }
    }

    /**
     * End Backup Process
     * @return bool
     */
    public static function end (){
        //WPBackItUp_LoggerV2::log_info($this->log_name,__METHOD__,'Begin - Unlock File:' . $this->lockFileName);

        try{
            //WPBackItUp_LoggerV2::log_info($this->log_name,__METHOD__,'LOCK released -  backup ending');
            flock( self::$lockFile, LOCK_UN); // unlock the file
            return true;

        }catch(Exception $e) {
            //WPBackItUp_LoggerV2::log_error($this->log_name,__METHOD__,'Cant unlock file: ' .$e);
            return false;
        }
    }

    /**
     * Check lock status
     * @return bool
     */
    public function check_lock_status (){
        //Check for 5 minutes then give up
        for ($i = 1; $i <= 100; $i++) {
            if ($this->start()){
                $this->end();
                return true;
            }
            else{
                sleep(3); //sleep for 3 seconds
            }
        }
        return false;
    }

    public function isScheduled(){

        return true;
    }

    public function cleanup_backups_by_prefix($prefix) {
	    WPBackItUp_LoggerV2::log_info($this->log_name,__METHOD__,'Begin' );
        $backup_root_path=$this->backup_folder_root;

        //get a list of all the temps
        $work_folder_list = glob($backup_root_path. $prefix .'*', GLOB_ONLYDIR);
        $file_system = new WPBackItUp_FileSystem($this->log_name);
        foreach($work_folder_list as $folder) {
            $file_system->recursive_delete($folder);
        }

	    WPBackItUp_LoggerV2::log_info($this->log_name,__METHOD__,'End' );
    }

    public function cleanup_old_backups() {
	    WPBackItUp_LoggerV2::log_info($this->log_name,__METHOD__,'Begin' );

        //  --PURGE BACKUP FOLDER
        //Purge logs in backup older than N days
        $backup_root_path=$this->backup_folder_root;
        $file_system = new WPBackItUp_FileSystem($this->log_name);

        //check retention limits
        $file_system->purge_folders($backup_root_path,'*',$this->backup_retained_number);

	    WPBackItUp_LoggerV2::log_info($this->log_name,__METHOD__,'End' );
    }


    public function cleanup_unfinished_backups_OLD(){
        $dir=$this->backup_folder_root;
	    WPBackItUp_LoggerV2::log_info($this->log_name,__METHOD__,'Begin:'.$dir);
        $ignore = array('cgi-bin','.','..','._');
        if( is_dir($dir) ){
            if($dh = opendir($dir)) {
                while( ($file = readdir($dh)) !== false ) {
                    $ext = pathinfo($file, PATHINFO_EXTENSION);
                    if (!in_array($file, $ignore) && substr($file, 0, 1) != '.' && $ext!="zip" && $ext!="log") { //Check the file is not in the ignore array
                        if(!is_dir($dir .'/'. $file)) {
                            unlink($dir .'/'. $file);
                        } else {
                            $fileSystem = new WPBackItUp_FileSystem($this->log_name);
                            $fileSystem->recursive_delete($dir.'/'. $file, $ignore);
                        }
                    }
                }
            }
            closedir($dh);
        }
	    WPBackItUp_LoggerV2::log_info($this->log_name,__METHOD__,'End');
        return true;
    }

    public function cleanup_current_backup(){
        $path = $this->backup_project_path;
	    WPBackItUp_LoggerV2::log_info($this->log_name,__METHOD__,'Begin - Cleanup Backup Folder:' . $path);

        $fileSystem = new WPBackItUp_FileSystem($this->log_name);
	    $work_files = $fileSystem->get_fileonly_list($path, 'txt|sql');

        if(!$fileSystem ->delete_files($work_files)) {
	        WPBackItUp_LoggerV2::log_error($this->log_name,__METHOD__,'Work files could not be deleted');
            return false;
        }

	    WPBackItUp_LoggerV2::log_info($this->log_name,__METHOD__,'End - Work Files Deleted');
        return true;
    }

    public function purge_old_files(){
	    WPBackItUp_LoggerV2::log_info($this->log_name,__METHOD__,'Begin');
        $fileSystem = new WPBackItUp_FileSystem( $this->log_name);

        //Check the retention
        $fileSystem->purge_FilesByDate($this->backup_retained_number,$this->backup_folder_root);

	    //      --PURGE BACKUP FOLDER
        //Purge logs in backup older than N days
	    $backup_path = WPBACKITUP__BACKUP_PATH .'/';
        $fileSystem->purge_files($backup_path,'*.log',$this->backup_retained_days);

	    //Purge restore DB checkpoints older than 5 days
	    $fileSystem->purge_files($backup_path,'db*.cur',$this->backup_retained_days);

	    //      --PURGE LOGS FOLDER
	    $logs_path = WPBACKITUP__PLUGIN_PATH .'/logs/';

	    //Purge logs in logs older than 5 days
	    $fileSystem->purge_files($logs_path,'*.log',$this->backup_retained_days);

        //Purge Zipped logs in logs older than 5 days
	    $fileSystem->purge_files($logs_path,'*.zip',$this->backup_retained_days);

        //Purge logs in logs older than 5 days
//        $fileSystem->purge_files($logs_path,'Backup_*.log',$this->backup_retained_days);

//	    //Purge debug logs in logs older than 5 days
//	    $fileSystem->purge_files($logs_path,'*debug*.log',$this->backup_retained_days);
//
//	    //Purge upload logs in logs older than 5 days
//	    $fileSystem->purge_files($logs_path,'*upload*.log',$this->backup_retained_days);
//
//	    //Purge cleanup logs in logs older than 5 days
//	    $fileSystem->purge_files($logs_path,'*cleanup*.log',$this->backup_retained_days);
//
//	    //Purge Zipped logs in logs older than 5 days
//	    $fileSystem->purge_files($logs_path,'logs_*.zip',$this->backup_retained_days);
//
//	    //Purge restore logs in logs older than 5 days
//	    $fileSystem->purge_files($logs_path,'*restore*.log',$this->backup_retained_days);

	    WPBackItUp_LoggerV2::log_info($this->log_name,__METHOD__,'End');

    }

    //Make sure the root backup folder wpbackitup_backups exists
    public function backup_root_folder_exists(){
	    WPBackItUp_LoggerV2::log_info($this->log_name,__METHOD__,'Begin: ' .$this->backup_folder_root);
        $fileSystem = new WPBackItUp_FileSystem($this->log_name);
        if(!$fileSystem->create_dir($this->backup_folder_root)) {
	        WPBackItUp_LoggerV2::log_error($this->log_name,__METHOD__,'Cant create backup folder :'. $this->backup_folder_root);
            return false;
        }

	    $fileSystem->secure_folder($this->backup_folder_root);

	    //Make sure logs folder is secured
	    $logs_dir = WPBACKITUP__PLUGIN_PATH .'/logs/';
	    $fileSystem->secure_folder( $logs_dir);


	    WPBackItUp_LoggerV2::log_info($this->log_name,__METHOD__,'End');
        return true;
    }

    //Create the root folder for the current backup
    public function create_current_backup_folder(){
	    WPBackItUp_LoggerV2::log_info($this->log_name,__METHOD__,'Begin: ' .$this->backup_project_path);
        $fileSystem = new WPBackItUp_FileSystem($this->log_name);
        if(!$fileSystem->create_dir($this->backup_project_path)) {
	        WPBackItUp_LoggerV2::log_error($this->log_name,__METHOD__,'Cant create backup folder :'. $this->backup_project_path);
            return false;
        }

	    WPBackItUp_LoggerV2::log_info($this->log_name,__METHOD__,'End');
        return true;
    }

    //Check to see if the directory exists and is writeable
    public function backup_folder_exists(){
        $path=$this->backup_project_path;
	    WPBackItUp_LoggerV2::log_info($this->log_name,__METHOD__,'Is folder writeable: ' .$path);
        if(is_writeable($path)) {
	        WPBackItUp_LoggerV2::log_info($this->log_name,__METHOD__,'Folder IS writeable');
            return true;
        }

	    WPBackItUp_LoggerV2::log_error($this->log_name,__METHOD__,'Folder NOT writeable');
        return false;
    }

    //Export the SQL database
    public function export_database(){
        $sql_file_name=$this->backup_project_path . WPBACKITUP__SQL_DBBACKUP_FILENAME;
        $sqlUtil = new WPBackItUp_SQL($this->log_name);
	    WPBackItUp_LoggerV2::log_info($this->log_name,__METHOD__,'Begin - Export Database: ' .$sql_file_name);

	    //log database size
	    $db_size = $sqlUtil->get_table_rows();
	    WPBackItUp_LoggerV2::log_info($this->log_name,__METHOD__,$db_size,"Table Size");

        //Try SQLDump First
	    WPBackItUp_LoggerV2::log_info($this->log_name,__METHOD__,'Export DB with MYSQLDUMP');
        if(!$sqlUtil->mysqldump_export($sql_file_name) ) {

	        WPBackItUp_LoggerV2::log_info($this->log_name,__METHOD__,'Export DB with MYSQLDUMP/PATH');
            if(!$sqlUtil->mysqldump_export($sql_file_name,true) ) {

	            WPBackItUp_LoggerV2::log_info($this->log_name,__METHOD__,'Export DB with Manual SQL EXPORT');
                if(!$sqlUtil->manual_export($sql_file_name) ) {
	                WPBackItUp_LoggerV2::log_error($this->log_name,__METHOD__,'SQL EXPORT FAILED');
                    return false;
                }
            }
        }
	    WPBackItUp_LoggerV2::log_info($this->log_name,__METHOD__,'Database Exported successfully');
        return true;
    }

    //Create siteinfo in project dir
    public function create_siteinfo_file(){
        global $table_prefix; //from wp-config
        $path=$this->backup_project_path;
        $siteinfo = $path ."backupsiteinfo.txt";

	    WPBackItUp_LoggerV2::log_info($this->log_name,__METHOD__,'Create Site Info File:'.$siteinfo);
        try {
            $handle = fopen($siteinfo, 'w+');
            if (false===$handle){
	            WPBackItUp_LoggerV2::log_error($this->log_name,__METHOD__,'Cant open file.');
                return false;
            }

            //Probably should change to json format

            //Write Site URL
            $entry = site_url( '/' ) ."\n";
            fwrite($handle, $entry);

            //Write Table Prefix
            $entry = $table_prefix ."\n" ;
            fwrite($handle, $entry);

            //write WP version
            $entry =get_bloginfo( 'version')."\n"  ;
            fwrite($handle, $entry);

            //write WP BackItUp
            $entry =WPBACKITUP__VERSION."\n"  ;
            fwrite($handle, $entry);

            fclose($handle);


            if (file_exists($siteinfo)){
	            WPBackItUp_LoggerV2::log_info($this->log_name,__METHOD__,'File created successfully.');
                return true;
            }

        }catch(Exception $e) {
	        WPBackItUp_LoggerV2::log_error($this->log_name,__METHOD__,' Exception: ' .$e);
        }

	    WPBackItUp_LoggerV2::log_error($this->log_name,__METHOD__,'Site Info File NOT Created.');
        return false;
    }

	public function get_plugins_file_list() {
		WPBackItUp_LoggerV2::log_info($this->log_name,__METHOD__,'Begin' );

		$file_system = new WPBackItUp_FileSystem($this->log_name);
		$plugins_file_list = $file_system->get_recursive_file_list(WPBACKITUP__PLUGINS_ROOT_PATH. '/*' );
		$file_system=null;//release resources.
		WPBackItUp_LoggerV2::log_info($this->log_name,__METHOD__,'Plugin File Count: ' .count($plugins_file_list));

		return  $plugins_file_list;
	}

	//Search array(needle) for value(haystack) starting in position 1
	function strposa0($haystack, $needle, $offset=0) {
		if(!is_array($needle)) $needle = array($needle);
		foreach($needle as $query) {
			$pos = strpos($haystack, $query, $offset);
			//looking for position 0 - string must start at the beginning
			if($pos === 0) return true; // stop on first true result
		}
		return false;
	}

	public function create_job_control($job_id) {
		WPBackItUp_LoggerV2::log_info($this->log_name,__METHOD__,'Begin' );

		try {
			$db = new WPBackItUp_DataAccess();
			return $db->create_job_control($job_id);

		} catch ( Exception $e ) {
			WPBackItUp_LoggerV2::log_error($this->log_name,__METHOD__,'Exception: ' . $e );
			return false;
		}
	}

	public function update_job_control_complete($job_id) {
		WPBackItUp_LoggerV2::log_info($this->log_name,__METHOD__,'Begin' );

		try {
			$db = new WPBackItUp_DataAccess();
			return $db->update_job_control_complete($job_id);

		} catch ( Exception $e ) {
			WPBackItUp_LoggerV2::log_error($this->log_name,__METHOD__,'Exception: ' . $e );
			return false;
		}
	}


	/**
	 * Save inventory of folder to database
	 *
	 * @param $batch_insert_size
	 * @param $job_id
	 * @param $group_id
	 * @param $root_path
	 * @param null $exclude
	 *
	 * @return bool
	 */
	public function save_folder_inventory($batch_insert_size,$job_id,$group_id,$root_path,$exclude=null) {
		WPBackItUp_LoggerV2::log_info($this->log_name,__METHOD__,'Begin:' .$group_id);

		//create a separate log file for inventory
		$inventory_logname = sprintf('debug_inventory_%s_%s',$group_id,$job_id);
		WPBackItUp_LoggerV2::log_info($inventory_logname,__METHOD__, '**BEGIN**');
		WPBackItUp_LoggerV2::log_info($inventory_logname,__METHOD__, 'Root Path: ' .$root_path);
		WPBackItUp_LoggerV2::log_info($inventory_logname,__METHOD__, 'Exclude: ' .var_export($exclude,true));
		WPBackItUp_LoggerV2::log_info($inventory_logname,__METHOD__, '***');
		try {
			$batch_counter = 0;
			$total_counter=0;
			$directory_iterator=new RecursiveDirectoryIterator($root_path, 4096 | 8192 | RecursiveIteratorIterator::CATCH_GET_CHILD);
			$item_iterator = new RecursiveIteratorIterator($directory_iterator,RecursiveIteratorIterator::SELF_FIRST);

			$datetime1 = new DateTime('now');
			$sql="";
			$db = new WPBackItUp_DataAccess();

			while ($item_iterator->valid()) {
				//Skip the item if its in the exclude array
				//This is a string compare starting in position 1

				//Fix the path to use backslash
				$file_path = str_replace('\\', "/",$item_iterator->getSubPathname());

				//Remove special characters
				$file_path = esc_sql($file_path);

				if ($this->strposa0($file_path, $exclude)===true) {
					WPBackItUp_LoggerV2::log_info($inventory_logname,__METHOD__, 'Skip: ' .$file_path);
				} else {
					if ( $item_iterator->isFile()) {
						if ($batch_counter>=$batch_insert_size){
							WPBackItUp_LoggerV2::log_info($inventory_logname,__METHOD__, '*Try Write Batch*');
							if (! $db->insert_job_items($sql)) {
								return false;
							}
							WPBackItUp_LoggerV2::log_info($inventory_logname,__METHOD__, '*Write Batch SUCCESS*');
							$sql="";
							$batch_counter=0;
						}
						$total_counter++;
						$batch_counter++;
						$file_size=ceil($item_iterator->getSize()/1024);//round up
						WPBackItUp_LoggerV2::log_info($inventory_logname,__METHOD__, 'Add File: ' .$batch_counter . ' ' .$file_path);
						$sql.= "(".$job_id .", '" .$group_id."', '" .utf8_encode($file_path) ."', ".$file_size .",now() ),";
					}
				}
				$item_iterator->next();
			}

			if ($batch_counter>0) {
				WPBackItUp_LoggerV2::log_info($inventory_logname,__METHOD__, '*Try Write Batch*');
				if (! $db->insert_job_items($sql)) {
					return false;
				}
				WPBackItUp_LoggerV2::log_info($inventory_logname,__METHOD__, '*Write Batch SUCCESS*');
			}

			$datetime2 = new DateTime('now');

			WPBackItUp_LoggerV2::log_info($inventory_logname, __METHOD__, '**END**');

            if(method_exists($datetime2, 'diff')) {
                $interval = $datetime1->diff($datetime2);
	            WPBackItUp_LoggerV2::log_info($this->log_name,__METHOD__,'File Count/Time: ' .$total_counter . '-' . $interval->format('%s seconds'));
            } else {
                $util = new WPBackItUp_Utility($this->log_name);
                $interval = $util->date_diff_array($datetime1, $datetime2);
	            WPBackItUp_LoggerV2::log_info($this->log_name,__METHOD__,'File Count/Time: ' .$total_counter . '-' . $interval['second'] . ' seconds');
            }


			return true;

		} catch(Exception $e) {
			WPBackItUp_LoggerV2::log_error($this->log_name,__METHOD__,'Exception: ' .$e);
			return false;
		}
	}

	/**
	 * Save inventory of array list to database
	 *
	 * @param $batch_insert_size
	 * @param $job_id
	 * @param $group_id
	 * @param $file_list
	 *
	 * @return bool
	 * @internal param $root_path
	 */
	public function save_file_list_inventory($batch_insert_size,$job_id,$group_id,$root_path,$file_list) {
		WPBackItUp_LoggerV2::log_info($this->log_name,__METHOD__,'Begin:' .var_export($file_list,true));

		//check is array list
		if (! is_array($file_list)) {
			WPBackItUp_LoggerV2::log_error($this->log_name,__METHOD__,'Array expected in file list:');
			return false;
		}

		try {
			$batch_counter = 0;
			$total_counter=0;

			$datetime1 = new DateTime('now');
			$sql="";
			$db = new WPBackItUp_DataAccess();
			foreach($file_list as $file_path) {

				//skip if folder
				if ( is_dir( $file_path ) ) {
					WPBackItUp_LoggerV2::log_info($this->log_name,__METHOD__,'Skip folder:' . $file_path );
					continue;
				}


				if ($batch_counter>=$batch_insert_size){
					if (! $db->insert_job_items($sql)) {
						return false;
					}
					$sql="";
					$batch_counter=0;
				}
				$total_counter++;
				$batch_counter++;
				$file_size=ceil(filesize($file_path) /1024);//round up

				//get rid of root path and utf8 encode
				$file_path = utf8_encode(str_replace($root_path,'',$file_path));

				WPBackItUp_LoggerV2::log_info($this->log_name,__METHOD__,'Add File: ' .$batch_counter . ' ' .$file_path);
				$sql.= "(".$job_id .", '" .$group_id."', '" .$file_path ."', ".$file_size .",now() ),";
			}

			if ($batch_counter>0) {
				if (! $db->insert_job_items($sql)) {
					return false;
				}
			}

			$datetime2 = new DateTime('now');

            if(method_exists($datetime2, 'diff')) {
                $interval = $datetime1->diff($datetime2);
	            WPBackItUp_LoggerV2::log_info($this->log_name,__METHOD__,'File Count/Time: ' .$total_counter . '-' . $interval->format('%s seconds'));
            } else {
                $util = new WPBackItUp_Utility($this->log_name);
                $interval = $util->date_diff_array($datetime1, $datetime2);
	            WPBackItUp_LoggerV2::log_info($this->log_name,__METHOD__,'File Count/Time: ' .$total_counter . '-' . $interval['second'] . ' seconds');
            }

            return true;

		} catch(Exception $e) {
			WPBackItUp_LoggerV2::log_error($this->log_name,__METHOD__,'Exception: ' .$e);
			return false;
		}
	}

	public function get_themes_file_list() {
		WPBackItUp_LoggerV2::log_info($this->log_name,__METHOD__,'Begin' );

		$file_system = new WPBackItUp_FileSystem($this->log_name);
		$themes_root_path = WPBACKITUP__THEMES_ROOT_PATH;
		$themes_file_list = $file_system->get_recursive_file_list($themes_root_path. '/*' );
		$file_system=null;//release resources.
		WPBackItUp_LoggerV2::log_info($this->log_name,__METHOD__,'Themes File Count: ' .count($themes_file_list));

		return $themes_file_list;
	}

    public function get_uploads_file_list() {
	    WPBackItUp_LoggerV2::log_info($this->log_name,__METHOD__,'Begin' );

        $upload_array = wp_upload_dir();
        $uploads_root_path = $upload_array['basedir'];

        //ignore these folders under uploads
        $ignore = explode(',',WPBACKITUP__BACKUP_IGNORE_LIST);

        $uploads_folderlist = glob($uploads_root_path. '/*',GLOB_ONLYDIR|GLOB_NOSORT);
        $uploads_file_list=array();

        $file_system = new WPBackItUp_FileSystem($this->log_name);
        foreach ( $uploads_folderlist as $folder ) {
            if (! $this->strposa(basename($folder), $ignore)){
                array_push($uploads_file_list,$folder);
                $file_list = $file_system->get_recursive_file_list($folder. '/*' );
                if (is_array($file_list))  {
                    $uploads_file_list = array_merge($uploads_file_list,$file_list);
                }
            }
        }
	    $file_system=null;//release resources.

        //Need to grab the files in the root also
        $files_only = array_filter(glob($uploads_root_path. '/*'), 'is_file');
        if (is_array($files_only) && count($files_only)>0){
            $uploads_file_list = array_merge($uploads_file_list,$files_only);
        }

	    WPBackItUp_LoggerV2::log_info($this->log_name,__METHOD__,'Uploads File Count: ' .count($uploads_file_list));

        return $uploads_file_list;
    }

    public function get_other_file_list() {
	    WPBackItUp_LoggerV2::log_info($this->log_name,__METHOD__,'Begin' );

        $wpcontent_path = WPBACKITUP__CONTENT_PATH;

        $upload_array = wp_upload_dir();
        $uploads_folder = basename ($upload_array['basedir']);
        $themes_folder = basename (WPBACKITUP__THEMES_ROOT_PATH);
        $plugins_folder = basename (WPBACKITUP__PLUGINS_ROOT_PATH);

        //ignore these folders
        $wpback_ignore = explode(',',WPBACKITUP__BACKUP_OTHER_IGNORE_LIST);
        $wpcontent_ignore=array($uploads_folder, $themes_folder, $plugins_folder);
        $ignore = array_merge($wpback_ignore,$wpcontent_ignore);

        $wpcontent_folderlist = glob($wpcontent_path. '/*',GLOB_ONLYDIR|GLOB_NOSORT);

        $other_file_list=array();
        $file_system = new WPBackItUp_FileSystem($this->log_name);
        foreach ( $wpcontent_folderlist as $folder ) {
            if (!$this->strposa(basename($folder), $ignore)){
                array_push($other_file_list,$folder);
                $file_list = $file_system->get_recursive_file_list($folder. '/*' );
                if (is_array($file_list)) {
                    $other_file_list = array_merge($other_file_list,$file_list);
                }
            }
        }
	    $file_system=null;//release resources.

        //Need to grab the files in the root also
        $files_only = array_filter(glob($wpcontent_path. '/*'), 'is_file');
        if (is_array($files_only) && count($files_only)>0){

            //Get rid of the debug.log file - dont want to restore it
            $debug_log_index = $this->search_array('debug.log', $files_only);
            if (false!==$debug_log_index) {
                unset($files_only[$debug_log_index]);
            }

            if (is_array($files_only)) {
                $other_file_list = array_merge($other_file_list,$files_only);
            }
        }

	    WPBackItUp_LoggerV2::log_info($this->log_name,__METHOD__,'Other File Count: ' .count($other_file_list));

        return $other_file_list;
    }



	/**
	 *
	 * Fetch batch of files from DB and add to zip
	 *
	 * @param $job_id
	 * @param $source_root
	 * @param $content_type
	 *
	 * @return bool|mixed
	 */
	public function backup_files($job_id,$source_root,$content_type){
        global $WPBackitup;
		WPBackItUp_LoggerV2::log_info($this->log_name,__METHOD__,'Begin ');

		//get files to backup
		$db = new WPBackItUp_DataAccess();

		switch($content_type)
		{
			case 'themes';
				$target_root='wp-content-themes';
				$batch_size=$WPBackitup->backup_themes_batch_size();
				break;
			case 'plugins';
				$target_root='wp-content-plugins';
				$batch_size=$WPBackitup->backup_plugins_batch_size();
				break;
			case 'uploads';
				$target_root='wp-content-uploads';
				$batch_size=$WPBackitup->backup_uploads_batch_size();
				break;
			case 'others';
				$target_root='wp-content-other';
				$batch_size=$WPBackitup->backup_others_batch_size();
				break;
			default:
				WPBackItUp_LoggerV2::log_error($this->log_name,__METHOD__,'Content type not recognized:'.$content_type);
				return false;

		}

		WPBackItUp_LoggerV2::log_info($this->log_name,__METHOD__,'Batch Size: '. $batch_size);

		//get a timestamp for the batch id
		$batch_id=current_time( 'timestamp' );
		$file_list = $db->get_batch_open_tasks($batch_id,$batch_size,$job_id,$content_type);

		//It is possible that there are no file to backup so return count or false
		if($file_list == false || $file_list==0) return $file_list;

		//$zip_file_path = $this->backup_project_path . $this->backup_name .'-'.$content_type .'.zip';
		$zip_file_path = sprintf('%s%s-%s-%s.zip',$this->backup_project_path,$this->backup_name,$content_type,$batch_id);
		WPBackItUp_LoggerV2::log_info($this->log_name,__METHOD__,'Zip file path: '. $zip_file_path);
		if (! $this->backup_files_to_zip($source_root,$target_root,$file_list,$zip_file_path)){
			return false;
		}

        // Clears file status cache
        clearstatcache();

        //Check to see if the file exists, it is possible that it does not if only empty folders were contained
        if(! file_exists($zip_file_path) ) {
	        WPBackItUp_LoggerV2::log_error($this->log_name,__METHOD__,'Zip File NOT found:'.$zip_file_path);

	        $file_system = new WPBackItUp_FileSystem($this->log_name);
	        $files_in_temp_directory = $file_system->get_fileonly_list($this->backup_project_path, 'zip');
	        WPBackItUp_LoggerV2::log_error($this->log_name,__METHOD__,'Files In Temp Folder:');
	        WPBackItUp_LoggerV2::log_error($this->log_name,__METHOD__,$files_in_temp_directory);
	        return false;
        }

		WPBackItUp_LoggerV2::log_info($this->log_name,__METHOD__,'Zip file FOUND:'.$zip_file_path);

		//update the batch as done.
		$db->update_batch_complete($job_id,$batch_id);

		//get count of remaining
		$remaining_count = $db->get_open_task_count($job_id,$content_type);

		//return count;
        return $remaining_count;
	}

	/**
	 *
	 * Validate backup files
	 *
	 * @param $job_id
	 * @param $source_root
	 * @param $target_root
	 * @param $content_type
	 *
	 * @return bool|mixed
	 */
//	public function validate_backup_files($job_id,$content_type){
//		WPBackItUp_LoggerV2::log_info($this->log_name,__METHOD__,'Begin: '.$content_type);
//
//		//get files to backup
//		$db = new WPBackItUp_DataAccess();
//
//		switch($content_type)
//		{
//			case 'themes';
//				$target_root='wp-content-themes';
//				break;
//			case 'plugins';
//				$target_root='wp-content-plugins';
//				break;
//			case 'uploads';
//				$target_root='wp-content-uploads';
//				break;
//			case 'others';
//				$target_root='wp-content-other';
//				break;
//			//ADD exception when other
//		}
//
//		$file_list = $db->get_completed_tasks($job_id,$content_type);
//
//		//It is possible that there were no files backed up
//		if($file_list == false || $file_list==0) {
//			WPBackItUp_LoggerV2::log_info($this->log_name,__METHOD__,'No files found to validate.');
//			return true;
//		}
//
//		$current_zip_file=null;
//		$zip=null;
//		$file_counter=0;
//
//        // Checking zip file existance
//		WPBackItUp_LoggerV2::log_info($this->log_name,__METHOD__,'All Zip files Path');
//
//		foreach($file_list as $file) {
//			$batch_id = $file->batch_id;
//			$item     = $target_root .'/' .utf8_decode( $file->item );
//
//			//get zip path
//			$zip_file_path = sprintf('%s-%s-%s.zip',$this->backup_project_path . $this->backup_name, $content_type,$batch_id);
//
//            // logging zip file path.
//			WPBackItUp_LoggerV2::log_info($this->log_name,__METHOD__,'zip file path: '. $zip_file_path);
//
//            if ($current_zip_file!=$zip_file_path){
//				//WPBackItUp_LoggerV2::log_info($this->log_name,__METHOD__,'Zip File:' . $zip_file_path );
//				if (! file_exists($zip_file_path)){
//					WPBackItUp_LoggerV2::log_error($this->log_name,__METHOD__,'Zip File not found:' . $zip_file_path );
//                    // Scanning Temp Directory.
//                    $files_on_temp_directory = scandir($this->backup_project_path);
//					WPBackItUp_LoggerV2::log_info($this->log_name,__METHOD__,'Begin::Files on TMP Directory');
//					WPBackItUp_LoggerV2::log_info($this->log_name,__METHOD__,$files_on_temp_directory);
//					WPBackItUp_LoggerV2::log_info($this->log_name,__METHOD__,'End::Files on TMP Directory');
//					return false;
//				}
//				$current_zip_file = $zip_file_path;
//				if (null!=$zip) $zip->close();
//				$zip = new WPBackItUp_Zip($this->log_name,$current_zip_file);
//				WPBackItUp_LoggerV2::log_info($this->log_name,__METHOD__,'Current Zip File:' . $current_zip_file );
//			}
//
//			//validate file exists in zip
//			if (false===$zip->validate_file($item)) {
//				WPBackItUp_LoggerV2::log_error($this->log_name,__METHOD__,'File NOT found in zip :' . $item );
//				$zip->close();
//				return false;
//			}
//			$file_counter++;
//		}
//
//		WPBackItUp_LoggerV2::log_info($this->log_name,__METHOD__,'Validation Successful:'.$content_type . '(' .$file_counter .')');
//		if (null!=$zip) $zip->close();
//		return true;
//	}

	/**
	 *  Validate backup files by batch ID
	 *  A batch will typically be one zip file.
	 *
	 * @param $job_id
	 * @param $content_type
	 * @param $batch_id
	 *
	 * @return bool
	 */
	public function validate_backup_files_by_batch_id($job_id,$content_type,$batch_id){
        WPBackItUp_LoggerV2::log_info($this->log_name,__METHOD__,'Begin: '.$content_type . ' Batch ID: ' . $batch_id);

        //get files to backup
        $db = new WPBackItUp_DataAccess();

        switch($content_type)
        {
            case 'themes';
                $target_root='wp-content-themes';
                break;
            case 'plugins';
                $target_root='wp-content-plugins';
                break;
            case 'uploads';
                $target_root='wp-content-uploads';
                break;
            case 'others';
                $target_root='wp-content-other';
                break;
            //ADD exception when other
        }

        $file_list = $db->get_completed_tasks_by_batch_id($job_id,$content_type,$batch_id);

        //It is possible that there were no files backed up
        if( $file_list == false || $file_list==0 ) {
            WPBackItUp_LoggerV2::log_info($this->log_name,__METHOD__, 'No files found to validate.');
            return true;
        }

        $current_zip_file=null;
        $zip=null;
        $file_counter=0;

        //get zip path
        $zip_file_path = sprintf('%s-%s-%s.zip',$this->backup_project_path . $this->backup_name, $content_type,$batch_id);
        WPBackItUp_LoggerV2::log_info($this->log_name,__METHOD__,'Zip file: '. $zip_file_path);

        if ( ! file_exists($zip_file_path) ) {
            WPBackItUp_LoggerV2::log_error($this->log_name,__METHOD__,'Zip File not found:' . $zip_file_path );
            // Scanning Temp Directory.
            $files_on_temp_directory = scandir($this->backup_project_path);
            WPBackItUp_LoggerV2::log_info($this->log_name,__METHOD__,'Begin::Files on TMP Directory');
            WPBackItUp_LoggerV2::log_info($this->log_name,__METHOD__,$files_on_temp_directory);
            WPBackItUp_LoggerV2::log_info($this->log_name,__METHOD__,'End::Files on TMP Directory');
            return false;
        }

        $current_zip_file = $zip_file_path;
        $zip = new WPBackItUp_Zip($this->log_name,$current_zip_file);
        WPBackItUp_LoggerV2::log_info($this->log_name,__METHOD__,'Current Zip File:' . $current_zip_file );

        foreach($file_list as $file) {
            $item     = $target_root .'/' .utf8_decode( $file->item );

            //validate file exists in zip
            if (false===$zip->validate_file($item)) {
                WPBackItUp_LoggerV2::log_error($this->log_name,__METHOD__,'File NOT found in zip :' . $item );
                $zip->close();
                return false;
            }
            $file_counter++;
        }

        WPBackItUp_LoggerV2::log_info($this->log_name,__METHOD__,'Validation Successful:'.$content_type . '-' .$file_counter);
        if (null!=$zip) $zip->close();
        return true;
    }

	/**
	 *
	 * Add files in file list to zip file
	 *
	 *
	 * @param $source_root
	 * @param $target_root
	 * @param $file_list (object collection)
	 * @param $zip_file_path
	 *
	 * @return bool
	 */
	private function backup_files_to_zip($source_root,$target_root,$file_list, $zip_file_path){
		WPBackItUp_LoggerV2::log_info($this->log_name,__METHOD__,'Begin ');

        if (empty($file_list) || !isset($file_list)) {
	        WPBackItUp_LoggerV2::log_error($this->log_name,__METHOD__,'File list is not valid:');
	        WPBackItUp_LoggerV2::log($this->log_name,var_export($file_list,true));
            return false;
        }

		WPBackItUp_LoggerV2::log_info($this->log_name,__METHOD__,'Begin - Item Count: '. count($file_list));
		$zip = new WPBackItUp_Zip($this->log_name,$zip_file_path);

		$file_count=0;
		foreach($file_list as $file) {
			$item = $source_root. '/' .utf8_decode($file->item);
			WPBackItUp_LoggerV2::log_info($this->log_name,__METHOD__,'File:' .$item);

			//skip it if folder
			if ( is_dir( $item ) ) {
				WPBackItUp_LoggerV2::log_info($this->log_name,__METHOD__,'Skip folder:' . $item );
				continue;
			}

			//replace the source path with the target & fix any pathing issues
			$target_item_path = str_replace(rtrim($source_root, '/'),rtrim($target_root,'/'),$item);
			$target_item_path= str_replace('//','/',$target_item_path);
			$target_item_path= str_replace('\\','/',$target_item_path);

			//WPBackItUp_LoggerV2::log_info($this->log_name,__METHOD__,'Add File:' .$target_item_path );
			if ( $zip->add_file($item,$target_item_path)) {
				WPBackItUp_LoggerV2::log_info($this->log_name,__METHOD__,sprintf('(%s)File Added:%s', $zip->get_zip_file_count(),$target_item_path));
			} else {
				WPBackItUp_LoggerV2::log_error($this->log_name,__METHOD__,'File NOT added:' . $target_item_path );
				return false;
			}
		}

		//if we get here then close the zip
		WPBackItUp_LoggerV2::log_info($this->log_name,__METHOD__,sprintf('Zip File Status before Close:%s', $zip->get_zip_status()));
		WPBackItUp_LoggerV2::log_info($this->log_name,__METHOD__,sprintf('Number of files in zip:%s', $zip->get_files_in_zip()));
		$rtn_value = $zip->close();//close the zip & return the status

		WPBackItUp_LoggerV2::log_info($this->log_name,__METHOD__,'End');
		return $rtn_value;
	}


	/**
	 *
	 * Backup files in array to list
	 *
	 * @param $source_root
	 * @param $target_root
	 * @param $suffix
	 * @param $file_list
	 * @param $batch_size
	 *
	 * @return array|bool
	 */
	public function backup_file_list($source_root,$target_root,$suffix,$file_list,$batch_size){
		WPBackItUp_LoggerV2::log_info($this->log_name,__METHOD__,'Begin');

		if (! is_array($file_list)) {
			WPBackItUp_LoggerV2::log_error($this->log_name,__METHOD__,'Array expected in file list:');
			WPBackItUp_LoggerV2::log($this->log_name,var_export($file_list,true));
			return false;
		}

		$batch_id=current_time( 'timestamp' );

		//$zip_file_path = $this->backup_project_path . $this->backup_name .'-'.$suffix .'.tmp';
		$zip_file_path = sprintf('%s%s-%s-%s.zip',$this->backup_project_path,$this->backup_name,$suffix,$batch_id);
		$zip = new WPBackItUp_Zip($this->log_name,$zip_file_path);
		foreach($file_list as $item) {
			$item = utf8_decode($item);
			WPBackItUp_LoggerV2::log_info($this->log_name,__METHOD__,'File:' . $item );

			//skip it if folder
			if ( is_dir( $item ) ) {
				WPBackItUp_LoggerV2::log_info($this->log_name,__METHOD__,'Skip folder:' . $item );
				array_shift( $file_list ); //remove from list
				continue;
			}

			//replace the source path with the target
			$target_item_path = str_replace(rtrim($source_root, '/'),rtrim($target_root,'/'),$item);
			WPBackItUp_LoggerV2::log_info($this->log_name,__METHOD__,'Add File:' .$target_item_path );
			if ( $zip->add_file($item,$target_item_path)) {
				array_shift($file_list);
				WPBackItUp_LoggerV2::log_info($this->log_name,__METHOD__,'File Added:' . $target_item_path );
				//If we have added X# of files or hit the size limit then lets close the zip and finish on the next pass
				if( $zip->get_zip_file_count()>=$batch_size){
					$zip->close();//close the zip
					WPBackItUp_LoggerV2::log_info($this->log_name,__METHOD__,'End - Item Count:' . count($file_list));
					return $file_list;
				}
			} else {
				WPBackItUp_LoggerV2::log_error($this->log_name,__METHOD__,'File NOT added:' . $target_item_path );
				return false;
			}
		}

		//if we get here then close the zip
		$zip->close();//close the zip

		//if there are no more files to add then rename the zip
		//Check to see if the file exists, it is possible that it does not if only empty folders were contained
		if(count($file_list)==0 || ! file_exists($zip_file_path) ){
			//if (! $this->add_zip_suffix($batch_id,$zip_file_path)){
				return false;
			//}
		}

		WPBackItUp_LoggerV2::log_info($this->log_name,__METHOD__,'End - Item Count:' . count($file_list));
		return $file_list;
	}

    private function strposa($haystack, $needle) {
        if(!is_array($needle)) $needle = array($needle);

        foreach($needle as $query) {
            //If wildcard on end then compare
            if ('*' == substr($query, -1) && strpos( $haystack, rtrim($query,"*")) !== false) {
                return true;
            } else {
                if ( $haystack==$query ) {
                    return true;
                }
            }
        }
        return false;
    }

	private function add_zip_suffix($batch_id,$zip_file_path){
		WPBackItUp_LoggerV2::log_info($this->log_name,__METHOD__,'Begin');

		$file_extension = pathinfo($zip_file_path, PATHINFO_EXTENSION);
		WPBackItUp_LoggerV2::log_info($this->log_name,__METHOD__,'File Extension:'.$file_extension);
		if ($file_extension!='zip'){
			$file_system = new WPBackItUp_FileSystem($this->log_name);
			$new_zip_name = str_replace('.' . $file_extension,'-'.$batch_id .'.zip',$zip_file_path);
			if ( !$file_system->rename_file($zip_file_path,$new_zip_name)){
				WPBackItUp_LoggerV2::log_error($this->log_name,__METHOD__,'Zip could not be renamed.');
				return false;
			}
		}

		//if we get here the file was renamed or was .zip already
		return true;
	}

//	public function finalize_zip_file() {
//		WPBackItUp_LoggerV2::log_info($this->log_name,__METHOD__,'Begin - Finalize the zip.');
//
//		$zip_file_path = $this->backup_folder_root . $this->backup_filename;
//		$new_zip_name = str_replace('.tmp','.zip',$zip_file_path);
//
//		$file_system = new WPBackItUp_FileSystem($this->log_name);
//		if (! $file_system->rename_file($zip_file_path,$new_zip_name)){
//			WPBackItUp_LoggerV2::log_error($this->log_name,__METHOD__,'Zip could not be finalized.');
//			return false;
//		}
//
//		//Change the file name property moving forward
//		$this->set_zip_extension();
//
//		WPBackItUp_LoggerV2::log_info($this->log_name,__METHOD__,'End - Zip Finalized successfully.');
//		return true;
//	}

	//Set zip extension to zip
//	public function set_zip_extension() {
//		WPBackItUp_LoggerV2::log_info($this->log_name,__METHOD__,'Begin - change zip extension to zip');
//
//		$this->backup_filename = substr_replace($this->backup_filename, '.zip', -4);
//		//$this->backup_filename=str_replace('.tmp','.zip',$this->backup_filename);
//
//		WPBackItUp_LoggerV2::log_info($this->log_name,__METHOD__,'End - Zip extension changed:' . $this->backup_filename);
//
//	}

	//Create manifest file
	public function create_backup_manifest(){
		WPBackItUp_LoggerV2::log_info($this->log_name,__METHOD__,'Begin');

		//get a list of all the zips
		$backup_files_path = array_filter(glob($this->backup_project_path. '*.zip'), 'is_file');
		WPBackItUp_LoggerV2::log_info($this->log_name,__METHOD__,'Zip files found:'. var_export($backup_files_path,true));
		if (is_array($backup_files_path) && count($backup_files_path)>0){
			//get rid of the path.
			$backup_files = str_replace($this->backup_project_path,'',$backup_files_path);
			$manifest_file=$this->backup_project_path . 'backupmanifest.txt';
			file_put_contents($manifest_file,json_encode($backup_files));

            //Find the main zip in the array to get the path
            $main_zip_index = $this->search_array('-main-', $backup_files_path);

            //add it to the main zip file
            if ($main_zip_index!==false){
                $zip_file_path = $backup_files_path[$main_zip_index];
                $zip = new WPBackItUp_Zip($this->log_name,$zip_file_path);
                $target_item_path = str_replace(rtrim($this->backup_project_path, '/'),rtrim('site-data','/'),$manifest_file);
                if ($zip->add_file($manifest_file,$target_item_path)) {
                    $zip->close();//close the zip
	                WPBackItUp_LoggerV2::log_info($this->log_name,__METHOD__,'End -  Manifest created.');
                    return true;
                }
             }else{
	            WPBackItUp_LoggerV2::log_error($this->log_name,__METHOD__,'Main zip not found.');
            }
		}

		WPBackItUp_LoggerV2::log_error($this->log_name,__METHOD__,'End -  Manifest not created.');
		return false;
	}

    private function search_array($search, $array)
    {
        foreach($array as $key => $value)
        {
            if (stristr($value, $search))
            {
                return $key;
            }
        }
        return false;
    }


    public function rename_backup_folder() {
	    WPBackItUp_LoggerV2::log_info($this->log_name,__METHOD__,'Begin');

        $backup_project_path = $this->backup_project_path;
        //remove the 4 character prefix
        $new_backup_path = str_replace('TMP_','',$backup_project_path);

        $file_system = new WPBackItUp_FileSystem($this->log_name);
        if (! $file_system->rename_file($backup_project_path,$new_backup_path)){
	        WPBackItUp_LoggerV2::log_error($this->log_name,__METHOD__,'Folder could not be renamed');
            return false;
        }

        $this->set_final_backup_path();

	    WPBackItUp_LoggerV2::log_info($this->log_name,__METHOD__,'End');
        return true;
    }

    //this is needed because it is set to TMP until finalization then needed a way to know where the current path is
    public function set_final_backup_path(){
	    WPBackItUp_LoggerV2::log_info($this->log_name,__METHOD__,'Begin');

        $backup_project_path = $this->backup_project_path;
        $new_backup_path = str_replace('TMP_','',$backup_project_path);

        //set the path to the new path
        $this->backup_project_path=$new_backup_path;

	    WPBackItUp_LoggerV2::log_info($this->log_name,__METHOD__,'End');
    }

}
