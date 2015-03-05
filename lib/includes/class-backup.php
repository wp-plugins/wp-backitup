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

			$backup_project_path = WPBACKITUP__BACKUP_PATH .'/TMP_'. $backup_name .'/';

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

    public function cleanup_backups_by_prefix($prefix) {
        $this->logger->log_info( __METHOD__, 'Begin' );
        $backup_root_path=$this->backup_folder_root;

        //get a list of all the temps
        $work_folder_list = glob($backup_root_path. $prefix .'*', GLOB_ONLYDIR);
        $file_system = new WPBackItUp_FileSystem($this->logger);
        foreach($work_folder_list as $folder) {
            $file_system->recursive_delete($folder);
        }

        $this->logger->log_info( __METHOD__, 'End' );
    }

    public function cleanup_old_backups() {
        $this->logger->log_info( __METHOD__, 'Begin' );

        //  --PURGE BACKUP FOLDER
        //Purge logs in backup older than N days
        $backup_root_path=$this->backup_folder_root;
        $file_system = new WPBackItUp_FileSystem($this->logger);

        //check retention limits
        $file_system->purge_folders($backup_root_path,'*',$this->backup_retained_number);

        $this->logger->log_info( __METHOD__, 'End' );
    }


    public function cleanup_unfinished_backups_OLD(){
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
        $work_files = array_filter(glob($this->backup_project_path. '*.{txt,sql}',GLOB_BRACE), 'is_file');

        if(!$fileSystem ->delete_files($work_files)) {
            $this->logger->log_error(__METHOD__,'Work files could not be deleted');
            return false;
        }

        $this->logger->log_info(__METHOD__,'End - Work Files Deleted');
        return true;
    }

    public function delete_site_data_files(){
        $path = $this->backup_project_path;
        $this->logger->log_info(__METHOD__, 'Begin - Cleanup Backup Folder:' . $path);

        $fileSystem = new WPBackItUp_FileSystem($this->logger);
        $work_files = array_filter(glob($this->backup_project_path. '*.{txt,sql}',GLOB_BRACE), 'is_file');

        if(!$fileSystem ->delete_files($work_files)) {
            $this->logger->log_error(__METHOD__,'Work files could not be deleted');
            return false;
        }

        $this->logger->log_info(__METHOD__,'End - Work Files Deleted');
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
                $this->logger->log_info(__METHOD__,'File created successfully.');
                return true;
            }

        }catch(Exception $e) {
            $this->this->logger->log_error(__METHOD__,' Exception: ' .$e);
        }

        $this->logger->log_error(__METHOD__,'Site Info File NOT Created.');
        return false;
    }

	public function get_plugins_file_list() {
		$this->logger->log_info( __METHOD__, 'Begin' );

		$file_system = new WPBackItUp_FileSystem($this->logger);
		$plugins_file_list = $file_system->get_recursive_file_list(WPBACKITUP__PLUGINS_ROOT_PATH. '/*' );
		$file_system=null;//release resources.
		$this->logger->log_info( __METHOD__, 'Plugin File Count: ' .count($plugins_file_list));

		return  $plugins_file_list;
	}

	public function get_themes_file_list() {
		$this->logger->log_info( __METHOD__, 'Begin' );

		$file_system = new WPBackItUp_FileSystem($this->logger);
		$themes_root_path = WPBACKITUP__THEMES_ROOT_PATH;
		$themes_file_list = $file_system->get_recursive_file_list($themes_root_path. '/*' );
		$file_system=null;//release resources.
		$this->logger->log_info( __METHOD__, 'Themes File Count: ' .count($themes_file_list));

		return $themes_file_list;
	}

    public function get_uploads_file_list() {
        $this->logger->log_info( __METHOD__, 'Begin' );

        $upload_array = wp_upload_dir();
        $uploads_root_path = $upload_array['basedir'];

        //ignore these folders under uploads
        $ignore = explode(',',WPBACKITUP__BACKUP_IGNORE_LIST);

        $uploads_folderlist = glob($uploads_root_path. '/*',GLOB_ONLYDIR|GLOB_NOSORT);
        $uploads_file_list=array();

        $file_system = new WPBackItUp_FileSystem($this->logger);
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

        $this->logger->log_info( __METHOD__, 'Uploads File Count: ' .count($uploads_file_list));

        return $uploads_file_list;
    }

    public function get_other_file_list() {
        $this->logger->log_info( __METHOD__, 'Begin' );

        $wpcontent_path = WPBACKITUP__CONTENT_PATH;

        $upload_array = wp_upload_dir();
        $uploads_folder = basename ($upload_array['basedir']);
        $themes_folder = basename (WPBACKITUP__THEMES_ROOT_PATH);
        $plugins_folder = basename (WPBACKITUP__PLUGINS_ROOT_PATH);

        //ignore these folders
        $wpback_ignore = explode(',',WPBACKITUP__BACKUP_IGNORE_LIST);
        $wpcontent_ignore=array($uploads_folder, $themes_folder, $plugins_folder);
        $ignore = array_merge($wpback_ignore,$wpcontent_ignore);

        $wpcontent_folderlist = glob($wpcontent_path. '/*',GLOB_ONLYDIR|GLOB_NOSORT);

        $other_file_list=array();
        $file_system = new WPBackItUp_FileSystem($this->logger);
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

        $this->logger->log_info( __METHOD__, 'Other File Count: ' .count($other_file_list));

        return $other_file_list;
    }


    //BackUp
	public function backup_file_list($source_root,$target_root,$suffix,$file_list,$batch_size,$ignore=''){
		$this->logger->log_info(__METHOD__,'Begin - Item Count: '. count($file_list));
        $this->logger->log_info(__METHOD__,'Items in Backup List: ');
        $this->logger->log($file_list);

        if (! is_array($file_list)) {
            $this->logger->log_error(__METHOD__,'Array expected in file list:');
            $this->logger->log($file_list);
            return 'error';
        }

		$zip_file_path = $this->backup_project_path . $this->backup_name .'-'.$suffix .'.tmp';
		$zip = new WPBackItUp_Zip($this->logger,$zip_file_path);

		foreach($file_list as $item) {
            $this->logger->log_info( __METHOD__, 'File:' . $item );

            //skip it if in ignore
            if ( !empty($ignore) && false!== strpos($item,$ignore)) {
                $this->logger->log_info( __METHOD__, 'Skip File:' . $item );
                array_shift($file_list); //remove from list
                continue;
            }

            //skip it if folder
            if ( is_dir( $item ) ) {
                $this->logger->log_info( __METHOD__, 'Skip folder:' . $item );
                array_shift( $file_list ); //remove from list
                continue;
            }


			//replace the source path with the target
			$target_item_path = str_replace(rtrim($source_root, '/'),rtrim($target_root,'/'),$item);

            $this->logger->log_info( __METHOD__, 'Add File:' .$target_item_path );
            if ( $zip->add_file($item,$target_item_path)) {
                array_shift($file_list);
                $this->logger->log_info( __METHOD__, 'File Added:' . $target_item_path );
                $this->logger->log_info( __METHOD__, 'Zip file count:' . $zip->get_zip_file_count() . '>=' . $batch_size);

                //If we have added X# of files or hit the size limit then lets close the zip and finish on the next pass
                if( $zip->get_zip_file_count()>=$batch_size){

                    $zip->close();//close the zip

                    //check the compressed file size
                    $compressed_zip_file_size = $zip->get_zip_actual_size();
                    $this->logger->log_info( __METHOD__, 'Zip Actual Size after close:' . $zip->get_zip_actual_size());

                    //if the zip is too big we need to rename it
                    $threshold = $zip->get_max_zip_size(.8);
                    if ($compressed_zip_file_size >= $threshold) {
                        $this->logger->log_info(__METHOD__,'Zip hit max size threshold:'.$compressed_zip_file_size .'>' .$threshold );
                        if (! $this->add_zip_suffix($zip_file_path)){
                            return 'error';
                        }
                    }

                    $this->logger->log_info(__METHOD__,'End - Item Count:' . count($file_list));
                    return $file_list;
                }
            } else {
                $this->logger->log_error( __METHOD__, 'File NOT added:' . $target_item_path );
                return 'error';
            }
        }


        //if we get here then close the zip
        $zip->close();//close the zip

		//if there are no more files to add then rename the zip
        //Check to see if the file exists, it is possible that it does not if only empty folders were contained
            if(count($file_list)==0 && file_exists($zip_file_path) ){
            $this->logger->log_info( __METHOD__, 'Zip Actual Size after close:' . $zip->get_zip_actual_size());
			if (! $this->add_zip_suffix($zip_file_path)){
				return 'error';
			}
		}

		$this->logger->log_info(__METHOD__,'End - Item Count:' . count($file_list));
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

	private function add_zip_suffix($zip_file_path){
		$this->logger->log_info(__METHOD__,'Begin');

		$file_extension = pathinfo($zip_file_path, PATHINFO_EXTENSION);
		$this->logger->log_info(__METHOD__,'File Extension:'.$file_extension);
		if ($file_extension!='zip'){
			$file_system = new WPBackItUp_FileSystem($this->logger);
			$new_zip_name = str_replace('.' . $file_extension,'-'.time() .'.zip',$zip_file_path);
			if ( !$file_system->rename_file($zip_file_path,$new_zip_name)){
				$this->logger->log_error(__METHOD__,'Zip could not be renamed.');
				return false;
			}
		}

		//if we get here the file was renamed or was .zip already
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

	//Create manifest file
	public function create_backup_manifest(){
        $this->logger->log_info(__METHOD__,'Begin');

		//get a list of all the zips
		$backup_files_path = array_filter(glob($this->backup_project_path. '*.zip'), 'is_file');
		$this->logger->log_error(__METHOD__,'Zip files found:'. var_export($backup_files_path,true));
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
                $zip = new WPBackItUp_Zip($this->logger,$zip_file_path);
                $target_item_path = str_replace(rtrim($this->backup_project_path, '/'),rtrim('site-data','/'),$manifest_file);
                if ($zip->add_file($manifest_file,$target_item_path)) {
                    $zip->close();//close the zip
                    $this->logger->log_info(__METHOD__,'End -  Manifest created.');
                    return true;
                }
             }else{
	            $this->logger->log_error(__METHOD__,'Main zip not found.');
            }
		}

        $this->logger->log_error(__METHOD__,'End -  Manifest not created.');
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
        $this->logger->log_info(__METHOD__,'Begin');

        $backup_project_path = $this->backup_project_path;
        //remove the 4 character prefix
        $new_backup_path = str_replace('TMP_','',$backup_project_path);

        $file_system = new WPBackItUp_FileSystem($this->logger);
        if (! $file_system->rename_file($backup_project_path,$new_backup_path)){
            $this->logger->log_error(__METHOD__,'Folder could not be renamed');
            return false;
        }

        $this->set_final_backup_path();

        $this->logger->log_info(__METHOD__,'End');
        return true;
    }

    //this is needed because it is set to TMP until finalization then needed a way to know where the current path is
    public function set_final_backup_path(){
        $this->logger->log_info(__METHOD__,'Begin');

        $backup_project_path = $this->backup_project_path;
        $new_backup_path = str_replace('TMP_','',$backup_project_path);

        //set the path to the new path
        $this->backup_project_path=$new_backup_path;

        $this->logger->log_info(__METHOD__,'End');
    }

}