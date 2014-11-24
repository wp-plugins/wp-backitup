<?php if (!defined ('ABSPATH')) die('No direct access allowed');
/**
 * WP Backitup Backup Class
 * 
 * @package WP Backitup
 * 
 * @author cssimmon
 *
 */

/*** Includes ***/
// include file system class
if( !class_exists( 'WPBackItUp_Filesystem' ) ) {
    include_once 'class-filesystem.php';
}



class WPBackItUp_Backup {

	private $logger;

	//Public Properties
	public  $backup_name;
	public  $backup_filename;
	public  $backup_project_path;
	public  $backup_folder_root;
	public  $restore_folder_root;
	public  $backup_retained_number;
    public  $backup_retained_days;
	public  $backup_batch_size;

    //scheduled,manual,none
    public  $backup_type;

    private static $lockFileName;
    private static $lockFile;


	//-------------STATIC FUNCTIONS-------------------//



	//-------------END STATIC FUNCTIONS-------------------//

	function __construct($logger,$backup_name, $backup_type) {
		global $WPBackitup;
		try {
			$this->logger = $logger;

            $this->backup_type=$backup_type;

			$this->backup_batch_size=1; //manual backups
			if ('scheduled'==$this->backup_type){
				$this->backup_batch_size=$WPBackitup->backup_batch_size(); //Scheduled
			}
			$this->backup_name=$backup_name;
			$this->backup_filename=$backup_name . '.tmp';

			$backup_project_path = WPBACKITUP__BACKUP_PATH .'/'. $backup_name .'/';

			$backup_folder_root =WPBACKITUP__BACKUP_PATH  .'/';
			$restore_folder_root = WPBACKITUP__RESTORE_FOLDER;

			$this->backup_project_path=$backup_project_path;
			$this->backup_folder_root=$backup_folder_root;
			$this->restore_folder_root=$restore_folder_root;

			$this->backup_retained_number = $WPBackitup->backup_retained_number();
            $this->backup_retained_days = WPBACKITUP__BACKUP_RETAINED_DAYS; //Prob need to move this to main propery

		} catch(Exception $e) {
            $this->logger->log_error(__METHOD__,'Constructor Exception: ' .$e);
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
	    $logger = new WPBackItUp_Logger(false,null,'debug_lock');
         try {
	        self::$lockFileName = WPBACKITUP__PLUGIN_PATH .'logs/wpbackitup_lock.lock';
	        $logger->log_info(__METHOD__,'Begin - Lock File:' . self::$lockFileName);

	        self::$lockFile = fopen(self::$lockFileName ,"w"); // open it for WRITING ("w")
            if (flock( self::$lockFile, LOCK_EX | LOCK_NB)) {
                $logger->log_info(__METHOD__,'Process LOCK acquired');
                return true;
            } else {
                $logger->log_info(__METHOD__,'Process LOCK failed');
                return false;
            }

        } catch(Exception $e) {
            $logger->log_info(__METHOD__,'Process Lock error: ' .$e);
            return false;
      }
    }

    /**
     * End Backup Process
     * @return bool
     */
    public static function end (){
        //$this->logger->log_info(__METHOD__,'Begin - Unlock File:' . $this->lockFileName);

        try{
            //$this->logger->log_info(__METHOD__,'LOCK released -  backup ending');
            flock( self::$lockFile, LOCK_UN); // unlock the file
            return true;

        }catch(Exception $e) {
            //$this->logger->log_error(__METHOD__,'Cant unlock file: ' .$e);
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

    public function cleanup_unfinished_backups(){
        $dir=$this->backup_folder_root;
        $this->logger->log_info(__METHOD__,'Begin:'.$dir);
        $ignore = array('cgi-bin','.','..','._');
        if( is_dir($dir) ){
            if($dh = opendir($dir)) {
                while( ($file = readdir($dh)) !== false ) {
                    $ext = pathinfo($file, PATHINFO_EXTENSION);
                    if (!in_array($file, $ignore) && substr($file, 0, 1) != '.' && $ext!="zip" && $ext!="log") { //Check the file is not in the ignore array
                        if(!is_dir($dir .'/'. $file)) {
                            unlink($dir .'/'. $file);
                        } else {
                            $fileSystem = new WPBackItUp_FileSystem($this->logger);
                            $fileSystem->recursive_delete($dir.'/'. $file, $ignore);
                        }
                    }
                }
            }
            closedir($dh);
        }
        $this->logger->log_info(__METHOD__,'End');
        return true;
    }

    public function cleanup_current_backup(){
        $path = $this->backup_project_path;
        $this->logger->log_info(__METHOD__, 'Begin - Cleanup Backup Folder:' . $path);

        $fileSystem = new WPBackItUp_FileSystem($this->logger);
        if(!$fileSystem ->recursive_delete($path)) {
            $this->logger->log_error(__METHOD__,'Backup Folder could not be deleted');
            return false;
        }

        $this->logger->log_info(__METHOD__,'End - Backup Folder Deleted');
        return true;
    }

    public function purge_old_files(){
        $this->logger->log_info(__METHOD__,'Begin');
        $fileSystem = new WPBackItUp_FileSystem( $this->logger);

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
	    $fileSystem->purge_files($logs_path,'Backup_*.log',$this->backup_retained_days);

	    //Purge debug logs in logs older than 5 days
	    $fileSystem->purge_files($logs_path,'*debug*.log',$this->backup_retained_days);

	    //Purge upload logs in logs older than 5 days
	    $fileSystem->purge_files($logs_path,'*upload*.log',$this->backup_retained_days);

	    //Purge cleanup logs in logs older than 5 days
	    $fileSystem->purge_files($logs_path,'*cleanup*.log',$this->backup_retained_days);

	    //Purge Zipped logs in logs older than 5 days
	    $fileSystem->purge_files($logs_path,'logs_*.zip',$this->backup_retained_days);

	    //Purge restore logs in logs older than 5 days
	    $fileSystem->purge_files($logs_path,'*restore*.log',$this->backup_retained_days);

        $this->logger->log_info(__METHOD__,'End');

    }

    //Make sure the root backup folder wpbackitup_backups exists
    public function backup_root_folder_exists(){
        $this->logger->log_info(__METHOD__,'Begin: ' .$this->backup_folder_root);
        $fileSystem = new WPBackItUp_FileSystem($this->logger);
        if(!$fileSystem->create_dir($this->backup_folder_root)) {
            $this->logger->log_error(__METHOD__,' Cant create backup folder :'. $this->backup_folder_root);
            return false;
        }

	    $fileSystem->secure_folder($this->backup_folder_root);

	    //Make sure logs folder is secured
	    $logs_dir = WPBACKITUP__PLUGIN_PATH .'/logs/';
	    $fileSystem->secure_folder( $logs_dir);


        $this->logger->log_info(__METHOD__,'End');
        return true;
    }

    //Create the root folder for the current backup
    public function create_current_backup_folder(){
        $this->logger->log_info(__METHOD__,'Begin: ' .$this->backup_project_path);
        $fileSystem = new WPBackItUp_FileSystem($this->logger);
        if(!$fileSystem->create_dir($this->backup_project_path)) {
            $this->logger->log_error(__METHOD__,'Cant create backup folder :'. $this->backup_project_path);
            return false;
        }

        $this->logger->log_info(__METHOD__,'End');
        return true;
    }

    //Check to see if the directory exists and is writeable
    public function backup_folder_exists(){
        $path=$this->backup_project_path;
        $this->logger->log_info(__METHOD__,'Is folder writeable: ' .$path);
        if(is_writeable($path)) {
          $this->logger->log_info(__METHOD__,'Folder IS writeable');
          return true;
        }

        $this->logger->log_error(__METHOD__,'Folder NOT writeable');
        return false;
    }

    //Export the SQL database
    public function export_database(){
        $sql_file_name=$this->backup_project_path . WPBACKITUP__SQL_DBBACKUP_FILENAME;
        $sqlUtil = new WPBackItUp_SQL($this->logger);
        $this->logger->log_info(__METHOD__,'Begin - Export Database: ' .$sql_file_name);

        //Try SQLDump First
        $this->logger->log_info(__METHOD__,'Export DB with MYSQLDUMP');
        if(!$sqlUtil->mysqldump_export($sql_file_name) ) {

            $this->logger->log_info(__METHOD__,'Export DB with MYSQLDUMP/PATH');
            if(!$sqlUtil->mysqldump_export($sql_file_name,true) ) {

                $this->logger->log_info(__METHOD__,'Export DB with Manual SQL EXPORT');
                if(!$sqlUtil->manual_export($sql_file_name) ) {
                    $this->logger->log_error(__METHOD__,'SQL EXPORT FAILED');
                    return false;
                }
            }
        }
        $this->logger->log_info(__METHOD__,'Database Exported successfully');

	    //  Uncomment when encryption is added
//      backup wp.config
//		$from_path = get_home_path() .'/wp-config.php';
//		$to_path = $this->backup_project_path .'/wp-config.bak';
//		$file_system = new WPBackItUp_FileSystem($this->logger);
//		$file_system->copy_file($from_path,$to_path);

        return true;
    }

    //Create siteinfo in project dir
    public function create_siteinfo_file(){
        global $table_prefix; //from wp-config
        $path=$this->backup_project_path;
        $siteinfo = $path ."backupsiteinfo.txt";

        $this->logger->log_info(__METHOD__,'Create Site Info File:'.$siteinfo);
        try {
            $handle = fopen($siteinfo, 'w+');
            if (false===$handle){
                $this->logger->log_error(__METHOD__,'Cant open file.');
                return false;
            }

            //Write Site URL
            $entry = site_url( '/' ) ."\n";
            fwrite($handle, $entry);

            //Write Table Prefix
            $entry = $table_prefix ."\n" ;
            fwrite($handle, $entry);

            //write WP version
            $entry =get_bloginfo( 'version')."\n"  ;
            fwrite($handle, $entry);
            fclose($handle);

            if (file_exists($siteinfo)){
                $this->logger->log_info(__METHOD__,'File created successfully.');
                return true;
            }

        }catch(Exception $e) {
            $this->this->logger->log_error(__METHOD__,' Exception: ' .$e);
        }

        $this->logger->log_error(__METHOD__,'Site Info File NOT Created.');
        return false;
    }

	//BackUp plugins
	public function backup_plugins(){
		$this->logger->log_info(__METHOD__,'Begin');

		$plugins_root_path = WPBACKITUP__PLUGINS_ROOT_PATH .'/';
		$target_plugin_root = 'wp-content-plugins';
		$zip_file_path = $this->backup_folder_root . $this->backup_filename;
		$zip = new WPBackItUp_Zip($this->logger,$zip_file_path);

		//Get a list of files/folders in the plugins root
		$plugin_copied=false;
		$backup_item_count=0;
		foreach(glob($plugins_root_path. '*',GLOB_ONLYDIR ) as $dir){
			$source_plugin_folder=$dir .'/';
			//This is the root target - needs to be hardcoded because we need to know where to find it on the restore.
			$target_plugin_folder =$target_plugin_root . '/' .basename($dir);

			//If target plugin doesnt exist backitup
			if (!$zip->folder_exists($target_plugin_folder)) {
				//If a plugin has already been backed up then this means there is more
				if ($plugin_copied && ($backup_item_count>=$this->backup_batch_size))	return 'continue';

				$this->logger->log_info(__METHOD__,'Backing up plugin:' .$target_plugin_folder);

				//Backup the plugin folder
				if (!$zip->compress_folder($source_plugin_folder,$target_plugin_folder)) {
					$this->logger->log_error(__METHOD__,'Plugin NOT backed up successfully.');
					return 'error';
				}else{
					$backup_item_count++;
					$this->logger->log_info(__METHOD__,'Plugin backed up successfully:' .$backup_item_count);
					$plugin_copied=true;
				}
			}
		}


		//If we get here then there are no more folders left to backup
		$this->logger->log_info(__METHOD__,'Backup all files in plugin root');
		$files = array_filter(glob($plugins_root_path. '*'), 'is_file');
		foreach ($files as $file){
			$this->logger->log_info(__METHOD__,'Backup file:' . $file);
			if (!$zip->zip_file($file,$target_plugin_root)){
				$this->logger->log_error(__METHOD__,'Plugin NOT backed up successfully.');
				return 'error';
			}
		}

		$this->logger->log_info(__METHOD__,'All Plugins backed up successfully.');
		return 'complete';
	}

	//BackUp Themes
	public function backup_themes(){
		$this->logger->log_info(__METHOD__,'Begin');

		$themes_root_path = WPBACKITUP__THEMES_ROOT_PATH .'/';
		$target_theme_root = 'wp-content-themes';

		$zip_file_path = $this->backup_folder_root . $this->backup_filename;
		$zip = new WPBackItUp_Zip($this->logger,$zip_file_path);

		//Get a list of files/folders in the themes root
		$theme_copied=false;
		$backup_item_count=0;
		foreach(glob($themes_root_path. '*',GLOB_ONLYDIR ) as $dir){
			$source_theme_folder=$dir .'/';
			$target_theme_folder = $target_theme_root . '/' .basename($dir);

			//If target theme doesnt exist backitup
			if (!$zip->folder_exists($target_theme_folder)){
				//If a theme has already been backed up then this means there is more
				if ($theme_copied && ($backup_item_count>=$this->backup_batch_size)) return 'continue';

				$this->logger->log_info(__METHOD__,'Backing up theme:' .$target_theme_folder);

				if (!$zip->compress_folder($source_theme_folder,$target_theme_folder))  {
					$this->logger->log_error(__METHOD__,'Theme NOT backed up successfully.');
					return 'error';
				}else{
					$backup_item_count++;
					$this->logger->log_info(__METHOD__,'Theme backed up successfully:' .$backup_item_count);
					$theme_copied=true;
				}
			}
		}


		//If we get here then there are no more folders left to backup
		$this->logger->log_info(__METHOD__,'Backup all files in theme root');
		$files = array_filter(glob($themes_root_path. '*'), 'is_file');
		foreach ($files as $file){
			$this->logger->log_info(__METHOD__,'Backup file:' . $file);
			if (!$zip->zip_file($file,$target_theme_root)) {
				$this->logger->log_error(__METHOD__,'Theme NOT backed up successfully.');
				return 'error';
			}
		}


		$this->logger->log_info(__METHOD__,'All Themes backed up successfuly.');
		return 'complete';
	}


	//BackUp Uploads
	public function backup_uploads(){
		$this->logger->log_info(__METHOD__,'Begin');

		$upload_array = wp_upload_dir();
		$uploads_root_path = $upload_array['basedir'] .'/';

		$target_uploads_root = 'wp-content-uploads';
		$zip_file_path = $this->backup_folder_root . $this->backup_filename;
		$zip = new WPBackItUp_Zip($this->logger,$zip_file_path);

		//Get a list of files/folders in the uploads root
		$upload_copied=false;
		$backup_item_count=0;
		$this->logger->log_info(__METHOD__,'GLOB:' .$uploads_root_path);
		foreach(glob($uploads_root_path. '*',GLOB_ONLYDIR ) as $dir){
			$source_upload_folder=$dir .'/';
			$target_upload_folder = $target_uploads_root .'/' .basename($dir);

			//If target upload doesnt exist backitup
			if (!$zip->folder_exists($target_upload_folder)) {
				//If an upload has already been backed up then this means there is more
				if ($upload_copied && ($backup_item_count>=$this->backup_batch_size)) 	return 'continue';

				$this->logger->log_info(__METHOD__,'Backing up upload:' .$target_upload_folder);

				if (!$zip->compress_folder($source_upload_folder,$target_upload_folder)) {
					$this->logger->log_error(__METHOD__,'Upload NOT backed up successfully.');
					return 'error';
				}else{
					$backup_item_count++;
					$this->logger->log_info(__METHOD__,'Upload backed up successfully:'.$backup_item_count);
					$upload_copied=true;
				}
			}
		}


		//If we get here then there are no more folders left to backup
		$this->logger->log_info(__METHOD__,'Backup all files in upload root');
		$files = array_filter(glob($uploads_root_path. '*'), 'is_file');
		foreach ($files as $file){
			$this->logger->log_info(__METHOD__,'Backup file:' . $file);
			if (!$zip->zip_file($file,$target_uploads_root)){
				$this->logger->log_error(__METHOD__,'Upload NOT backed up successfully.');
				return 'error';
			}
		}


		$this->logger->log_info(__METHOD__,'All Uploads backed up successfully.');
		return 'complete';
	}


	//Backup everything else
	public function backup_other(){
		$this->logger->log_info(__METHOD__,'Begin');

		$wpcontent_path = WPBACKITUP__CONTENT_PATH .'/';
		$upload_array = wp_upload_dir();
		$uploads_folder = basename ($upload_array['basedir']);
		$themes_folder = basename (WPBACKITUP__THEMES_ROOT_PATH);
		$plugins_folder = basename (WPBACKITUP__PLUGINS_ROOT_PATH);

		$target_other_root = 'wp-content-other';

		$wpback_ignore = explode(',',WPBACKITUP__BACKUP_IGNORE_LIST);
		$wpcontent_ignore=array($uploads_folder, $themes_folder, $plugins_folder);
		$ignore = array_merge($wpback_ignore,$wpcontent_ignore);

		$this->logger->log_info(__METHOD__,'Ignore:');
		$this->logger->log($ignore);

		$zip_file_path = $this->backup_folder_root . $this->backup_filename;
		$zip = new WPBackItUp_Zip($this->logger,$zip_file_path);

		$other_copied=false;
		$backup_item_count=0;
		$this->logger->log_info(__METHOD__,'Content Root Path:' .$wpcontent_path);
		foreach(glob($wpcontent_path. '*',GLOB_ONLYDIR ) as $dir){
			$source_other_folder=$dir .'/';
			$target_other_folder = $target_other_root .'/' .basename($dir);

			//If target other doesnt exist backitup
			if( !$zip->folder_exists($target_other_folder) && !in_array(basename($dir), $ignore) ) {
				//If a other has already been backed up then this means there is more
				if ($other_copied && ($backup_item_count>=$this->backup_batch_size)) 	return 'continue';

				$this->logger->log_info(__METHOD__,'Backing up other:' .$target_other_folder);

				if (!$zip->compress_folder($source_other_folder,$target_other_folder)) {
					$this->logger->log_error(__METHOD__,'Other NOT backed up successfully.');
					return 'error';
				}else{
					$backup_item_count++;
					$this->logger->log_info(__METHOD__,'Other backed up successfully:' .$backup_item_count);
					$other_copied=true;
				}
			}
		}

		//If we get here then there are no more folders left to backup
		$this->logger->log_info(__METHOD__,'Backup all files in wpcontent root');
		$files = array_filter(glob($wpcontent_path. '*'), 'is_file');
		foreach ($files as $file){
			$this->logger->log_info(__METHOD__,'Backup file:' . $file);
			if (!$zip->zip_file($file,$target_other_root)) {
				$this->logger->log_error(__METHOD__,'Other NOT backed up successfully.');
				return 'error';
			}
		}

		$this->logger->log_info(__METHOD__,'All Others backed up successfully.');
		return 'complete';
	}

	//backup all files in the site-data folder
	public function backup_site_data(){
		$this->logger->log_info(__METHOD__, 'Begin - Compress backup folder items:'.$this->backup_project_path);

		$target_other_root = 'site-data';

		$zip_file_path = $this->backup_folder_root . $this->backup_filename;
		$zip = new WPBackItUp_Zip($this->logger,$zip_file_path);

		$this->logger->log_info(__METHOD__,'Backup all files in root of backup folder.');
		$files = array_filter(glob($this->backup_project_path. '*'), 'is_file');
		foreach ($files as $file){
			$this->logger->log_info(__METHOD__,'Backup file:' . $file);
			if (!$zip->zip_file($file,$target_other_root)){
				return false;
			}
		}


		$this->logger->log_info(__METHOD__, 'End - Compress backup folder items.');
		return true;
	}


    public function validate_backup(){
        $this->logger->log_info(__METHOD__,'Begin - Validate backup');

        $source_dir_path = WPBACKITUP__CONTENT_PATH ;
        $target_dir_path = $this->backup_project_path;

        $this->logger->log_info(__METHOD__,'Validate content folder FROM:' .$target_dir_path);
	    $this->logger->log_info(__METHOD__,'Validate content folder TO:' .$source_dir_path);



	    $zip_file_path = $this->backup_folder_root . $this->backup_filename;
	    $zip = new WPBackItUp_Zip($this->logger,$zip_file_path);

	    //Validate plugins
	    //Check the plugins folder
	    $plugins_root_path = WPBACKITUP__PLUGINS_ROOT_PATH;
	    $target_plugin_root = 'wp-content-plugins';
        if(! $zip->validate_folder($plugins_root_path, $target_plugin_root)) {
            $this->logger->log_error(__METHOD__,'Plugins Validation:FAIL');
        }else{
	        $this->logger->log_info(__METHOD__,'Plugins Validation:SUCCESS');
        }

	    //Validate Themes
	    $themes_root_path = WPBACKITUP__THEMES_ROOT_PATH .'/';
	    $target_theme_root = 'wp-content-themes';
	    if(! $zip->validate_folder($themes_root_path, $target_theme_root)) {
		    $this->logger->log_error(__METHOD__,'Themes Validation:FAIL');
	    }else{
		    $this->logger->log_info(__METHOD__,'Themes Validation:SUCCESS');
	    }

	    //Validate Uploads
	    $upload_array = wp_upload_dir();
	    $uploads_root_path = $upload_array['basedir'] .'/';
	    $target_uploads_root = 'wp-content-uploads';
	    if(! $zip->validate_folder($uploads_root_path, $target_uploads_root)) {
		    $this->logger->log_error(__METHOD__,'Uploads Validation:FAIL');
	    }else{
		    $this->logger->log_info(__METHOD__,'Uploads Validation:SUCCESS');
	    }

		//Validate everything on the that was in the backup temp folder
	    $site_data_root_path = $this->backup_project_path .'/';
	    $target_site_data_root = 'site-data';
	    if(! $zip->validate_folder($site_data_root_path, $target_site_data_root)) {
		    $this->logger->log_error(__METHOD__,'Site Data Validation:FAIL');
	    }else{
		    $this->logger->log_info(__METHOD__,'Site Data Validation:SUCCESS');
	    }

		//Validate Other
	    $wpback_ignore = explode(',',WPBACKITUP__BACKUP_IGNORE_LIST);
	    $wpcontent_ignore=array(basename($uploads_root_path), basename($themes_root_path), basename($plugins_root_path));
	    $ignore = array_merge($wpback_ignore,$wpcontent_ignore);

	    $wpcontent_path = WPBACKITUP__CONTENT_PATH .'/';
	    $target_other_root = 'wp-content-other';

	    $this->logger->log_info(__METHOD__,'IGNORE:');
	    $this->logger->log($ignore);

	    //Validate the other folders
	    foreach(glob($wpcontent_path. '*',GLOB_ONLYDIR ) as $dir){
		    if( ! in_array(basename($dir), $ignore)){
			    $source_other_folder = $dir . '/';
		        $target_other_folder = $target_other_root . '/' . basename( $dir );

			    if(! $zip->validate_folder($source_other_folder, $target_other_folder)) {
			        $this->logger->log_error(__METHOD__,'Other Validation:FAIL - ' .basename( $dir ));
			    }else{
				    $this->logger->log_info(__METHOD__,'Other Validation:SUCCESS - '.basename( $dir ));
			    }
	        }
	    }

	    //Validate the other files
	    $files = array_filter(glob($wpcontent_path. '*'), 'is_file');
	    $file_validation=true;
	    foreach ($files as $file){
		    $target_other_file = $target_other_root . '/' . basename( $file );
		    if (false===$zip->validate_file($target_other_file)){
			    $this->logger->log_error(__METHOD__,'DIFF File:' .$target_other_file);
			    $file_validation=false;
		    };
	    }

	    // Write the other file validation results
	    if(! $file_validation) {
		    $this->logger->log_error(__METHOD__,'Other File Validation:FAIL');
	    }else{
		    $this->logger->log_info(__METHOD__,'Other File Validation:SUCCESS');
	    }

        $this->logger->log_info(__METHOD__,'End - Validate backup');
	    return true;
    }

	public function finalize_zip_file() {
		$this->logger->log_info(__METHOD__,'Begin - Finalize the zip.');

		$zip_file_path = $this->backup_folder_root . $this->backup_filename;
		$new_zip_name = str_replace('.tmp','.zip',$zip_file_path);

		$file_system = new WPBackItUp_FileSystem($this->logger);
		if (! $file_system->rename_file($zip_file_path,$new_zip_name)){
			$this->logger->log_error(__METHOD__,'Zip could not be finalized.');
			return false;
		}

		//Change the file name property moving forward
		$this->set_zip_extension();

		$this->logger->log_info(__METHOD__,'End - Zip Finalized successfully.');
		return true;
	}

	//Set zip extension to zip
	public function set_zip_extension() {
		$this->logger->log_info(__METHOD__,'Begin - change zip extension to zip');

		$this->backup_filename = substr_replace($this->backup_filename, '.zip', -4);
		//$this->backup_filename=str_replace('.tmp','.zip',$this->backup_filename);

		$this->logger->log_info(__METHOD__,'End - Zip extension changed:' . $this->backup_filename);

	}


}