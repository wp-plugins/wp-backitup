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

    //scheduled,manual,none
    public  $backup_type;
    public  $backup_start_time;
    public  $backup_end_time;

    private $lockFileName;
    private $lockFile;

	function __construct($logger,$backup_name, $backup_type) {
		global $WPBackitup;
		try {
			$this->logger = $logger;

            $this->backup_type=$backup_type;
			$this->backup_name=$backup_name;
			$this->backup_filename=$backup_name . '.zip';

			$backup_project_path = WPBACKITUP__BACKUP_PATH .'/'. $backup_name .'/';

			$backup_folder_root =WPBACKITUP__BACKUP_PATH  .'/';
			$restore_folder_root = WPBACKITUP__RESTORE_FOLDER;

			$this->backup_project_path=$backup_project_path;
			$this->backup_folder_root=$backup_folder_root;
			$this->restore_folder_root=$restore_folder_root;

            $this->lockFileName = WPBACKITUP__PLUGIN_PATH .'logs/wpbackitup_lock.lock';

			$this->backup_retained_number = $WPBackitup->backup_retained_number();
            $this->backup_retained_days = 5; //Prob need to move this to main propery

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
    public function start (){
         try {
            $this->logger->log_info(__METHOD__,'Begin - Lock File:' . $this->lockFileName);
            $this->backup_start_time= new datetime('now');
            $this->lockFile = fopen( $this->lockFileName ,"w"); // open it for WRITING ("w")
            if (flock( $this->lockFile, LOCK_EX | LOCK_NB)) {
                $this->logger->log_info(__METHOD__,'Process LOCK acquired');
                return true;
            } else {
                $this->logger->log_info(__METHOD__,'Process LOCK failed');
                return false;
            }

        } catch(Exception $e) {
            $this->logger->log_info(__METHOD__,'Process Lock error: ' .$e);
            return false;
      }
    }

    /**
     * End Backup Process
     * @return bool
     */
    public function end (){
        global $WPBackitup;
        $this->logger->log_info(__METHOD__,'Begin - Unlock File:' . $this->lockFileName);

        $current_datetime = current_time( 'timestamp' );
        $WPBackitup->set_backup_lastrun_date($current_datetime);

        $this->backup_end_time= new datetime('now');
        try{
            $this->logger->log_info(__METHOD__,'LOCK released -  backup ending');
            flock( $this->lockFile, LOCK_UN); // unlock the file
            return true;

        }catch(Exception $e) {
            $this->logger->log_error(__METHOD__,'Cant unlock file: ' .$e);
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

        //Purge logs older than 5 days
        $fileSystem->purge_files(WPBACKITUP__BACKUP_PATH .'/','*.log',$this->backup_retained_days);

	    //Purge logs in logs older than 5 days
	    $logs_path = WPBACKITUP__PLUGIN_PATH .'/logs/';
	    $fileSystem->purge_files($logs_path,'Backup_*.log',$this->backup_retained_days);


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

    //BackUp WPContent
    public function backup_wpcontent(){
        $fromFolder = WPBACKITUP__CONTENT_PATH . '/';
        $ignore = array( WPBACKITUP__BACKUP_FOLDER,$this->backup_name,$this->restore_folder_root,'upgrade','cache' );

        $this->logger->log_info(__METHOD__,'Begin');

        $this->logger->log_info(__METHOD__,'Recursive Copy FROM:'.$fromFolder);
        $this->logger->log_info(__METHOD__,'Recursive Copy TO:'.$this->backup_project_path);
        $this->logger->log_info(__METHOD__,$ignore,'Ignore Array');

        $fileSystem = new WPBackItUp_FileSystem($this->logger);
        if(!$fileSystem->recursive_copy($fromFolder, $this->backup_project_path, $ignore) ) {
           $this->logger->log_error(__METHOD__,'Site content was NOT copied successfully.');
           return false;
        }
        $this->logger->log_info(__METHOD__,'Site content copied successfully.');
        return true;
    }

    public function validate_wpcontent(){
        $this->logger->log_info(__METHOD__,'Begin - Validate WPContent');

        $source_dir_path = WPBACKITUP__CONTENT_PATH . '/';
        $target_dir_path = $this->backup_project_path;

        $this->logger->log_info(__METHOD__,'Validate content folder TO:' .$source_dir_path);
        $this->logger->log_info(__METHOD__,'Validate content folder FROM:' .$target_dir_path);

        $ignore = array(WPBACKITUP__PLUGIN_FOLDER,'debug.log','backupsiteinfo.txt','db-backup.sql');
        $filesystem = new WPBackItUp_FileSystem($this->logger);
        if(!$filesystem->recursive_validate($source_dir_path. '/', $target_dir_path . '/',$ignore)) {
            $this->logger->log_error(__METHOD__,'Content folder is not the same as backup.');
        }

        $this->logger->log_info(__METHOD__,'End - Validate WPContent');
    }

    public function compress_backup(){
        $this->logger->log_info(__METHOD__, 'Begin - Compress the backup:'.$this->backup_project_path);

        $zip = new WPBackItUp_Zip($this->logger);
        if (!$zip->compress($this->backup_project_path, $this->backup_folder_root)){
            $this->logger->log_error(__METHOD__, 'Could not compress backup folder');
            return false;
        }

        $this->logger->log_info(__METHOD__, 'End - Compress the backup');
        return true;
    }
}