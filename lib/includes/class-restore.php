<?php if (!defined ('ABSPATH')) die('No direct access allowed');

/**
 * WP BackItUp  - Restore Class
 *
 * @package WP BackItUp
 * @author  Chris Simmons <chris.simmons@wpbackitup.com>
 * @link    http://www.wpbackitup.com
 *
 */

class WPBackItUp_Restore {

	private $logger;

	//Public Properties
	private $backup_id;
	private $backup_name;
    private $backup_folder_path;
	private $restore_root_folder_path;
	private $restore_staging_suffix;

	const SITEDATAPATH =  'site-data';
	const PLUGINSPATH   =  'wp-content-plugins';
	const THEMESPATH    =  'wp-content-themes';
	const OTHERPATH     =  'wp-content-other';
	const UPLOADPATH   =  'wp-content-uploads';

	function __construct($logger, $backup_name, $backup_id) {
		//global $WPBackitup;

		try {
			$this->logger = $logger;

			$this->backup_name=$backup_name;
			$this->backup_folder_path = WPBACKITUP__BACKUP_PATH  .'/' .$backup_name;

            $this->restore_root_folder_path = WPBACKITUP__RESTORE_PATH;

			$this->backup_id=$backup_id;
			$this->restore_staging_suffix = '_' .$backup_id;


		} catch(Exception $e) {
            $this->logger->log($e);
			print $e;
		}
   }

   function __destruct() {
   		
   }

	public function delete_restore_folder() {
		$this->logger->log_info( __METHOD__, 'Begin delete restore folders.' );

		//get a list of all the folders
		$item_list = glob($this->restore_root_folder_path .'/*');
		return $this->delete_folders($item_list);
	}

	public function delete_staged_folders() {
		$this->logger->log_info( __METHOD__, 'Begin delete staged folders.' );

		//get a list of all the staged folders
		$item_list = glob(WPBACKITUP__CONTENT_PATH .'/*'.$this->restore_staging_suffix .'*');
		return $this->delete_folders($item_list);

	}

	private function delete_folders($item_list) {
		$this->logger->log_info( __METHOD__, 'Begin' );

		$this->logger->log_info( __METHOD__, 'Folders to be deleted:' );
		$this->logger->log($item_list);

		$file_system = new WPBackItUp_FileSystem($this->logger);
		foreach($item_list as $item) {
			if (is_dir($item)) {
				if (! $file_system->recursive_delete( $item )){
					$this->logger->log_error( __METHOD__, 'Folder could NOT be deleted:' . $item);
					return false;
				}
			}else{
				if (! unlink($item)){
					$this->logger->log_error( __METHOD__, 'File could NOT be deleted:' . $item);
					return false;
				}
			}
		}

		$this->logger->log_info( __METHOD__, 'End' );
		return true;
	}


	//Create an empty restore folder
	public function create_restore_root_folder() {
		$this->logger->log_info(__METHOD__,'Create restore folder.' . $this->restore_root_folder_path);

		$fileSystem = new WPBackItUp_FileSystem($this->logger);
		if( $fileSystem->create_dir($this->restore_root_folder_path)) {
			//Secure restore folder
			$fileSystem->secure_folder( $this->restore_root_folder_path);
			return true;

		} else{
			return false;
		}

	}

	//Unzip the backup to the restore folder
	function unzip_archive_file($backup_set_list){
		$this->logger->log_info(__METHOD__,'Begin');

		if (! is_array($backup_set_list) || count($backup_set_list)<=0) return false;

		$backup_file_path = $backup_set_list[0];
		$this->logger->log_info(__METHOD__,'Begin -  Unzip Backup File:' .$backup_file_path);
		try {
			$zip = new ZipArchive;
			$res = $zip->open($backup_file_path);
			if ($res === TRUE) {
				if (true===$zip->extractTo($this->restore_root_folder_path)){
					$zip->close();
				} else {
					$zip->close();
					$this->logger->log_error(__METHOD__,'Cant unzip backup:'.$backup_file_path);
					return false;
				}
			} else {
				$this->logger->log_error(__METHOD__,'Cant open backup archive:'.$backup_file_path);
				return false;
			}

			$this->logger->log_info(__METHOD__,'Backup file unzipped: ' .$backup_file_path);

		} catch(Exception $e) {
			$this->logger->log_error(__METHOD__,'An Unexpected Error has happened: ' .$e);
			return false;
		}

		return true;
	}

	//Validate the restore folder
	function validate_restore_folder(){
		$this->logger->log_info(__METHOD__,'Begin');

		$restore_folder_root=$this->restore_root_folder_path . '/';
		$this->logger->log_info(__METHOD__,'Validate restore folder : ' .$restore_folder_root);


		//Do we have at least 4 folders - other may sometimes not be there
		if ( count( glob( $restore_folder_root.'*', GLOB_ONLYDIR ) ) < 4 ) {
			$this->logger->log_error(__METHOD__,'Restore directory INVALID: ' .$restore_folder_root);
			return false;
		}


		$site_data_folder = $restore_folder_root .self::SITEDATAPATH;
		if(!is_dir($site_data_folder) ){
			$this->logger->log_error(__METHOD__,'site-data missing from restore folder:' .$site_data_folder);
			return false;
		}

		$plugins_folder = $restore_folder_root .self::PLUGINSPATH;
		if(!is_dir($plugins_folder) ){
			$this->logger->log_error(__METHOD__,'wp-content-plugins missing from restore folder:' .$plugins_folder);
			return false;
		}

		$themes_folder = $restore_folder_root .self::THEMESPATH;
		if(!is_dir($themes_folder) ){
			$this->logger->log_error(__METHOD__,'wp-content-themes missing from restore folder:' .$themes_folder);
			return false;
		}

		//Not an error
		$other_folder = $restore_folder_root .self::OTHERPATH;
		if(!is_dir($other_folder) ){
			$this->logger->log_info(__METHOD__,'wp-content-other missing from restore folder:' .$other_folder);
		}

		$uploads_folder = $restore_folder_root .self::UPLOADPATH;
		if(!is_dir($uploads_folder) ){
			$this->logger->log_error(__METHOD__,'wp-content-uploads missing from restore folder:' .$uploads_folder);
			return false;
		}

		$this->logger->log_info(__METHOD__,'End - Restoration directory validated: ' .$restore_folder_root);
		return true;
	}

	//Validate the restore folder
	function validate_siteinfo_file(){
		$this->logger->log_info(__METHOD__,'Begin');

		$site_info_path = $this->restore_root_folder_path . '/' .self::SITEDATAPATH .'/backupsiteinfo.txt';
		$this->logger->log_info(__METHOD__,'Validate Site info file: ' . $site_info_path);
		if(! file_exists($site_info_path) || empty($site_info_path)) {
			$this->logger->log_error(__METHOD__,'backupsiteinfo.txt missing or empty ' .$site_info_path);
			return false;
		}


		//Get file values
		$this->logger->log_info(__METHOD__, 'GET Site Info data' );
		$import_siteinfo_lines = file( $site_info_path);
		if (!is_array($import_siteinfo_lines) || count($import_siteinfo_lines)<3){
			$this->logger->log_error(__METHOD__,'Site Data file NOT valid.' );
			return false;
		} else {
			$restore_siteurl                = str_replace( "\n", '', trim( $import_siteinfo_lines[0] ) );
			$restore_table_prefix           = str_replace( "\n", '', $import_siteinfo_lines[1] );
			$restore_wp_version             = str_replace( "\n", '', $import_siteinfo_lines[2] );
			$restore_wpbackitup_version     = str_replace( "\n", '', $import_siteinfo_lines[3] );

			$site_data = array (
				'restore_siteurl'=>$restore_siteurl,
				'restore_table_prefix'=>$restore_table_prefix,
				'restore_wp_version'=>$restore_wp_version,
				'restore_wpbackitup_version'=>$restore_wpbackitup_version,
			);

			$this->logger->log_info(__METHOD__,'Site Data:' );
			$this->logger->log($site_data);
			return $site_data;
		}

	}

	//Validate the manifest
	function validate_manifest_file($backup_set_list, &$error_code){
		$this->logger->log_info(__METHOD__,'Begin');

		$manifest_file_path = $this->restore_root_folder_path . '/' .self::SITEDATAPATH .'/backupmanifest.txt';
		$this->logger->log_info(__METHOD__,'Validate backup manifest: ' . $manifest_file_path);

		if(! file_exists($manifest_file_path) || empty($manifest_file_path)) {
			$this->logger->log_info(__METHOD__,'No manifest found.');
			return true;  //Old backups will not have a manifest - OK
		}

		$manifest_data_string = file_get_contents($manifest_file_path);
		if (false===$manifest_data_string || empty($manifest_data_string)){
			$this->logger->log_error(__METHOD__,'Manifest empty.');
			$error_code=1;
			return false;
		}

		// make sure all the files in the manifest are part of the set
		$manifest_data_array = json_decode($manifest_data_string,true);
		foreach($manifest_data_array as $zip_file)
		{
			//does this file exist in the set
			if (false===$this->search_array($zip_file, $backup_set_list)){
				$this->logger->log_error(__METHOD__,'Zip File Missing:' .$zip_file);
				$error_code=2;
				return false;
			}
		}

		// Do we have any extra zip files
		foreach($backup_set_list as $zip_file)
		{
			//does this file exist in the manifest
			if (false===$this->search_array(basename($zip_file), $manifest_data_array)){
				$this->logger->log_error(__METHOD__,'Zip File Not in manifest:' .$zip_file);
				$error_code=3;
				return false;
			}
		}

		$this->logger->log_info(__METHOD__,'End' );
		return true;

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

	//Make sure there IS a backup to restore
	function validate_SQL_exists(){
		$this->logger->log_info(__METHOD__,'Begin');

		$backup_sql_file = $this->restore_root_folder_path . '/' .self::SITEDATAPATH . '/' . WPBACKITUP__SQL_DBBACKUP_FILENAME;
		$this->logger->log_info(__METHOD__,'Check for database backup file' . $backup_sql_file);
		if(!file_exists($backup_sql_file) || empty($backup_sql_file)) {
			$this->logger->log_error(__METHOD__,'Database backup file NOT FOUND.');
			return false;
		}

		$this->logger->log_info(__METHOD__,'Database backup file exists');
		return true;
	}

	public function export_database(){
		$this->logger->log_info(__METHOD__,'Begin');

		$date = date_i18n('Y-m-d-Hi',current_time( 'timestamp' ));
		$backup_file = $this->backup_folder_path .'/'. 'db-backup-' . $date .'.cur';

		$sqlUtil = new WPBackItUp_SQL($this->logger);
		$this->logger->log_info(__METHOD__,'Begin - Export Database: ' .$backup_file);

		//Try SQLDump First
		$this->logger->log_info(__METHOD__,'Export DB with MYSQLDUMP');
		if(!$sqlUtil->mysqldump_export($backup_file) ) {

			$this->logger->log_info(__METHOD__,'Export DB with MYSQLDUMP/PATH');
			if(!$sqlUtil->mysqldump_export($backup_file,true) ) {

				$this->logger->log_info(__METHOD__,'Export DB with Manual SQL EXPORT');
				if(!$sqlUtil->manual_export($backup_file) ) {
					$this->logger->log_error(__METHOD__,'SQL EXPORT FAILED');
					return false;
				}
			}
		}
		$this->logger->log_info(__METHOD__,'Database Exported successfully');

		return true;
	}


	public function rename_folder($from_folder_name,$to_folder_name){
		$this->logger->log_info(__METHOD__,'Begin');
		$this->logger->log_info(__METHOD__,'Rename from folder name:' . $from_folder_name);
		$this->logger->log_info(__METHOD__,'Rename to folder name: '. $to_folder_name);

		$file_system = new WPBackItUp_FileSystem($this->logger);
		if ( !$file_system->rename_file($from_folder_name,$to_folder_name)) {
			$this->logger->log_error(__METHOD__,'Folder could not be renamed');
			return false;
		}

		$this->logger->log_info(__METHOD__,'End');

		return true;
	}

	// Restore everything but plugins
	public function restore_wpcontent(){
		$this->logger->log_info(__METHOD__,'Begin');
		$error_folders = array();
		$error_files = array();

		//Create the archive folder
		$archive_folder = $this->restore_root_folder_path .'/Archive'.$this->restore_staging_suffix;
		if (! is_dir($archive_folder)){
			mkdir($archive_folder);
		}

		//Get all staged directories and rename them
		//Plugins, backup & restore folders wereent staged
		$wpcontent_folder_list = glob(WPBACKITUP__CONTENT_PATH .'/*'.$this->restore_staging_suffix ,GLOB_ONLYDIR);
		foreach ( $wpcontent_folder_list as $from_folder_name ) {

			$folder_name_only = basename( $from_folder_name );
			$this->logger->log_info(__METHOD__,'Folder name:' . $folder_name_only);

			$to_folder_name = WPBACKITUP__CONTENT_PATH . '/' . str_replace( $this->restore_staging_suffix, '', $folder_name_only );

			//rename the existing folder to OLD if exists
			$archive_success=true;
			if (is_dir($to_folder_name)){
				//try to rename it 5 times
				$archive_folder_name = $archive_folder .'/' .str_replace( $this->restore_staging_suffix, '', $folder_name_only );
				for ($i = 1; $i <= 5; $i++) {
					$this->logger->log_info(__METHOD__,'Archive attempt:' . $i);
					if ( $this->rename_folder($to_folder_name,$archive_folder_name)) {
						$archive_success=true;
						break; // break out if rename successful
					}else{
						$archive_success=false;
						sleep(1); //give it a second
					}
				}
			}

			$rename_success=false;
			//Rename the staged folder
			if ($archive_success) {
				for ($i = 1; $i <= 5; $i++) {
					$this->logger->log_info(__METHOD__,'Restore attempt:' . $i);
					if (  $this->rename_folder( $from_folder_name, $to_folder_name ) ) {
						$rename_success=true;
						break; // break out if rename successful
					}else{
						$rename_success=false;
						sleep(1); //give it a second
					}
				}
			}

			//keep going on failure
			if (! $rename_success){
				array_push($error_folders,$from_folder_name);
				$this->logger->log_error(__METHOD__, 'Cant restore folder.' .$from_folder_name );
			}
		}


		if ( is_array($error_folders) && count($error_folders)>0){
			$this->logger->log_error(__METHOD__,'End - Error Folders:');
			$this->logger->log($error_folders);
			return $error_folders;
		}

		$this->logger->log_info(__METHOD__,'End Restont WPContent Folders- No errors');

		//NOW restore the files
		$wpcontent_files_only = array_filter(glob($this->restore_root_folder_path .'/' .self::OTHERPATH .'/*'), 'is_file');
		foreach ( $wpcontent_files_only as $from_file_name ) {
			$file_name_only = basename( $from_file_name );

			$this->logger->log_info(__METHOD__,'WPContent File name:' . $file_name_only);

			//Archive the old file
			$to_file_name = WPBACKITUP__CONTENT_PATH .'/' . $file_name_only;
			$archive_success=true;
			if (file_exists($to_file_name)){

				//try to rename it 5 times
				$archive_file_name = $archive_folder .'/' . $file_name_only;
				for ($i = 1; $i <= 5; $i++) {
					$this->logger->log_info(__METHOD__,'Archive attempt:' . $i);
					if ( $this->rename_folder($to_file_name,$archive_file_name)) {
						$archive_success=true;
						break; // break out if rename successful
					}else{
						$archive_success=false;
						sleep(1); //give it a second
					}
				}
			}

			$rename_success=false;
			if ($archive_success){
				//Restore the file
				for ($i = 1; $i <= 5; $i++) {
					$this->logger->log_info(__METHOD__,'Restore attempt:' . $i);
					if (  $this->rename_folder( $from_file_name, $to_file_name ) ) {
						$rename_success=true;
						break; // break out if rename successful
					}else{
						$rename_success=false;
						sleep(1); //give it a second
					}
				}

			}

			//keep going on failure but add file to list
			if (!$rename_success){
				array_push($error_files,$from_folder_name);
				$this->logger->log_error(__METHOD__, 'Cant restore file.' .$from_file_name );

			}
		}


		if ( is_array($error_files) && count($error_files)>0) {
			$this->logger->log_error(__METHOD__,'End - Error Files:');
			$this->logger->log($error_files);
			return $error_folders;
		} else{
			$this->logger->log_info(__METHOD__,'End Restore WPContent - No errors');
			return true;
		}

	}

	public function restore_plugins(){
		$this->logger->log_info(__METHOD__,'Begin');
		$error_folders = array();
		$error_files = array();

		//Create the archive folder if it doesnt exist
		$archive_folder = $this->restore_root_folder_path .'/Archive'.$this->restore_staging_suffix;
		if (! is_dir($archive_folder)){
			mkdir($archive_folder);
		}

		//Create the plugins archive
		$plugin_archive_folder = $archive_folder .'/' . basename(WPBACKITUP__PLUGINS_ROOT_PATH);
		if (! is_dir($plugin_archive_folder)){
			 mkdir($plugin_archive_folder);
		}


		//Move the folders ONLY
		$plugins_folder_list = glob($this->restore_root_folder_path .'/' .self::PLUGINSPATH .'/*' ,GLOB_ONLYDIR);
		foreach ( $plugins_folder_list as $from_folder_name ) {
			$folder_name_only = basename( $from_folder_name );

			//Dont restore wp backitup plugin
			$this->logger->log_info(__METHOD__,'Plugin Folder name:' . $folder_name_only);
			if ( $folder_name_only != WPBACKITUP__PLUGIN_FOLDER) {

				//Archive the old plugin
				$to_folder_name = WPBACKITUP__PLUGINS_ROOT_PATH .'/' . $folder_name_only;
				$archive_success=true;
				if (is_dir($to_folder_name)){

					//try to rename it 5 times
					$archive_folder_name = $plugin_archive_folder .'/' . $folder_name_only;
					for ($i = 1; $i <= 5; $i++) {
						$this->logger->log_info(__METHOD__,'Archive attempt:' . $i);
						if ( $this->rename_folder($to_folder_name,$archive_folder_name)) {
							$archive_success=true;
							break; // break out if rename successful
						}else{
							$archive_success=false;
							sleep(1); //give it a second
						}
					}
				}

				$rename_success=false;
				if ($archive_success){
					//Restore the plugin
					for ($i = 1; $i <= 5; $i++) {
						$this->logger->log_info(__METHOD__,'Restore attempt:' . $i);
						if (  $this->rename_folder( $from_folder_name, $to_folder_name ) ) {
							$rename_success=true;
							break; // break out if rename successful
						}else{
							$rename_success=false;
							sleep(1); //give it a second
						}
					}

				}

				//keep going on failure but add folder to list
				if (!$rename_success){
					array_push($error_folders,$from_folder_name);
					$this->logger->log_error(__METHOD__, 'Cant restore plugin folder.' .$from_folder_name );
				}
			}
		}


		//If error on folders then return
		if (is_array($error_folders) && count($error_folders)>0){
			$this->logger->log_error(__METHOD__,'End - Error Folders:');
			$this->logger->log($error_folders);
			return $error_folders;
		}
		$this->logger->log_info(__METHOD__, 'End restore plugin folders.');


		//NOW move the files
		$plugins_files_only = array_filter(glob($this->restore_root_folder_path .'/' .self::PLUGINSPATH .'/*'), 'is_file');
		foreach ( $plugins_files_only as $from_file_name ) {
			$file_name_only = basename( $from_file_name );

			$this->logger->log_info(__METHOD__,'Plugin File name:' . $file_name_only);

			//Archive the old file
			$to_file_name = WPBACKITUP__PLUGINS_ROOT_PATH .'/' . $file_name_only;
			$archive_success=true;
			if (file_exists($to_file_name)){

				//try to rename it 5 times
				$archive_file_name = $plugin_archive_folder .'/' . $file_name_only;
				for ($i = 1; $i <= 5; $i++) {
					$this->logger->log_info(__METHOD__,'Archive attempt:' . $i);
					if ( $this->rename_folder($to_file_name,$archive_file_name)) {
						$archive_success=true;
						break; // break out if rename successful
					}else{
						$archive_success=false;
						sleep(1); //give it a second
					}
				}
			}

			$rename_success=false;
			if ($archive_success){
				//Restore the plugin
				for ($i = 1; $i <= 5; $i++) {
					$this->logger->log_info(__METHOD__,'Restore attempt:' . $i);
					if (  $this->rename_folder( $from_file_name, $to_file_name ) ) {
						$rename_success=true;
						break; // break out if rename successful
					}else{
						$rename_success=false;
						sleep(1); //give it a second
					}
				}

			}

			//keep going on failure but add file to list
			if (!$rename_success){
				array_push($error_files,$from_folder_name);
				$this->logger->log_error(__METHOD__, 'Cant restore plugin file.' .$from_file_name );

			}
		}


		if (is_array($error_files) && count($error_files)>0) {
			$this->logger->log_error(__METHOD__,'End - Error Files:');
			$this->logger->log($error_files);
			return $error_folders;
		} else{
			$this->logger->log_info(__METHOD__,'End Restore Plugins - No errors');
			return true;
		}


	}

	public function restore_database(){
		$this->logger->log_info(__METHOD__,'Begin -  restore database.');

		$backup_sql_file = $this->restore_root_folder_path . '/' .self::SITEDATAPATH . '/' . WPBACKITUP__SQL_DBBACKUP_FILENAME;
		return $this->run_sql_from_file($backup_sql_file);

	}

	private function run_sql_from_file($sql_file_path){
		$this->logger->log_info(__METHOD__,'Begin - SQL: '. $sql_file_path);


		$dbc = new WPBackItUp_SQL($this->logger);
		if(!$dbc->run_sql_exec($sql_file_path)) {
			//try with sql path on this time
			if(!$dbc->run_sql_exec($sql_file_path,true)) {
				//Try manually
				if ( ! $dbc->run_sql_manual( $sql_file_path ) ) {
					$this->logger->log_error( __METHOD__, 'Database import error.' );
					return false;
				}
			}
		}

		$this->logger->log_info(__METHOD__,'End');
		return true;
	}

	public function activate_plugins(){
		$this->logger->log_info(__METHOD__,'Begin');

		$plugins = get_plugins();
		foreach ( $plugins as $plugin => $value ) {
			//Activate plugin if NOT already active
			if (! is_plugin_active($plugin) ) {
				$result = activate_plugin($plugin);
				if ( is_wp_error( $result ) ) {
					$this->logger->log_error(__METHOD__,'Plugin could NOT be activated:' .$plugin);
				} else{
					$this->logger->log_info(__METHOD__,'Plugin activated:' .$plugin);
				}
			}
		}

		$this->logger->log_info(__METHOD__,'End');
		return true;
	}

	public function deactivate_plugins(){
		$this->logger->log_info(__METHOD__,'Begin');

		$plugins = get_option('active_plugins');
		foreach ($plugins as $plugin) {
			//dont deactivate wp-backitup
			if ('wp-backitup/wp-backitup.php' != $plugin){
				deactivate_plugins($plugin);
				$this->logger->log_info(__METHOD__,'Plugin Deactivated:' . $plugin);
			}
		}

		$this->logger->log_info(__METHOD__,'End');
	}

	//get siteurl
	public function get_siteurl(){
		global $table_prefix;
		$this->logger->log_info(__METHOD__,'Begin');

		$sql = "SELECT option_value FROM " .$table_prefix ."options WHERE option_name ='siteurl';";
		$dbc = new WPBackItUp_SQL($this->logger);
		$siteurl = $dbc->get_sql_scalar($sql);
		if (empty($siteurl)) {
			$this->logger->log_error(__METHOD__,'Siteurl not found');
			return false;
		}

		$this->logger->log_info(__METHOD__,'End - Siteurl found:' .$siteurl);
		return $siteurl;
	}

	//get homeurl
	function get_homeurl(){
		global $table_prefix;
		$this->logger->log_info(__METHOD__,'Begin');

		$sql = "SELECT option_value FROM " .$table_prefix ."options WHERE option_name ='home';";
		$dbc = new WPBackItUp_SQL($this->logger);
		$homeurl = $dbc->get_sql_scalar($sql);
		if (empty($homeurl)) {
			$this->logger->log_error(__METHOD__,' Homeurl not found.');
			return false;
		}
		$this->logger->log_info(__METHOD__,'End - homeurl found:' . $homeurl);
		return $homeurl;
	}

	//get user login
	function get_user_login($user_id){
		global $table_prefix;
		$this->logger->log_info(__METHOD__,'Begin');

		$sql = "SELECT user_login FROM ". $table_prefix ."users WHERE ID=" .$user_id .";";

		$dbc = new WPBackItUp_SQL($this->logger);
		$user_login = $dbc->get_sql_scalar($sql);
		if (empty($user_login)) {
			$this->logger->log_error(__METHOD__,'User_login not found.');
			return false;
		}

		$this->logger->log_info(__METHOD__,'End - User_login found.');
		return $user_login;
	}

	//get user pass
	function get_user_pass($user_id){
		global $table_prefix;
		$this->logger->log_info(__METHOD__,'Begin');

		$sql = "SELECT user_pass FROM ". $table_prefix ."users WHERE ID=" .$user_id .";";

		$dbc = new WPBackItUp_SQL($this->logger);
		$user_pass = $dbc->get_sql_scalar($sql);
		if (empty($user_pass)) {
			$this->logger->log_error(__METHOD__,'User_pass not found.');
			return false;
		}
		$this->logger->log_info(__METHOD__,'End - User_pass found.');
		return $user_pass;
	}

	//get user email
	function get_user_email($user_id){
		global $table_prefix;
		$this->logger->log_info(__METHOD__,'Begin');

		$sql = "SELECT user_email FROM ". $table_prefix ."users WHERE ID=" .$user_id ."";
		$dbc = new WPBackItUp_SQL($this->logger);
		$user_email = $dbc->get_sql_scalar($sql);
		if (empty($user_email)) {
			$this->logger->log_error(__METHOD__,'User_email not found.');
			return false;
		}
		$this->logger->log_info(__METHOD__,'End - User_email found.');
		return $user_email;
	}


	//Update user credentials
	function update_user_credentials($user_id, $user_login, $user_pass_hash, $user_email,$table_prefix){
		$this->logger->log_info(__METHOD__,'Begin');

		//prefer SQL update because password hash is safer than plain text update
		$sql = "UPDATE ". $table_prefix ."users SET user_login='" .$user_login ."', user_pass='" .$user_pass_hash ."', user_email='" .$user_email ."' WHERE ID='" .$user_id ."'";
		$dbc = new WPBackItUp_SQL($this->logger);
		if (!$dbc->run_sql_command($sql)){
			$this->logger->log_error(__METHOD__,'User Credential database update failed.');
			return false;
		}
		$this->logger->log_info(__METHOD__,'End - User Credential updated in database.');
		return true;
	}

	//Create user
	function create_user_XXX($current_user){
		$this->logger->log_info(__METHOD__,'Begin');

		$user_id = wp_insert_user( $current_user ) ;
		if( is_wp_error($user_id) ) {
			$this->logger->log_error(__METHOD__,'User was not created:' .$user_id->get_error_message());
			return false;
		}

		$this->logger->log_info(__METHOD__,'End - New user created:' . $user_id);
		return true;
	}


	//update credentials
	function update_credentials($user_id, $user_full_name, $user_login, $user_pass_hash, $user_email,$table_prefix){
		$this->logger->log_info(__METHOD__,'Begin');

		//prefer SQL update because password hash is safer than plain text update

		$dbc = new WPBackItUp_SQL($this->logger);

		//Fetch the user
		$sql = "SELECT id from " . $table_prefix ."users where user_login = '" .$user_login ."'";
		$query_result = $dbc->get_sql_scalar($sql);
		$this->logger->log_info(__METHOD__,'Fetch user by login:' .$query_result);

		if (!empty($query_result)) {
			$this->logger->log_info(__METHOD__,'Update User Credentials.');
			//update the user
			$sql = "UPDATE ". $table_prefix ."users SET user_login='" .$user_login ."', user_pass='" .$user_pass_hash ."', user_email='" .$user_email ."' WHERE ID='" .$user_id ."'";
			$dbc = new WPBackItUp_SQL($this->logger);
			if (!$dbc->run_sql_command($sql)){
				$this->logger->log_error(__METHOD__,'User Credential database update failed.');
				return false;
			}
			$this->logger->log_info(__METHOD__,'End - User Credential updated in database.');
			return true;


		} else {
			$this->logger->log_info(__METHOD__,'Create User Credentials.');
			//Create the user
			$sql = "INSERT INTO ". $table_prefix ."users (user_login, user_nicename, display_name, user_pass, user_email, user_registered, user_status) values('" .$user_login ."','" .$user_full_name ."','"  .$user_full_name ."','" .$user_pass_hash ."','" .$user_email ."', NOW() ,'0')";
			if (!$dbc->run_sql_command($sql)){
				$this->logger->log_error(__METHOD__,'User insert failed.');
				return false;
			}else{
				$this->logger->log_info(__METHOD__,'User inserted in database successfully.');
			}

			//Get the new user ID
			$sql = "SELECT id from " . $table_prefix ."users where user_login = '" .$user_login ."'";
			$user_id = $dbc->get_sql_scalar($sql);
			$this->logger->log_info(__METHOD__,'Fetch user by id:' .$user_id);

			$capabilities = $table_prefix . "capabilities";
			$sql = "INSERT INTO ". $table_prefix ."usermeta (user_id, meta_key, meta_value) values(" .$user_id .",'" . $capabilities . "', 'a:1:{s:13:\"administrator\";s:1:\"1\";}')";
			if (!$dbc->run_sql_command($sql)){
				$this->logger->log_error(__METHOD__,'user capabilities insert failed.');
				return false;
			}else {
				$this->logger->log_info(__METHOD__,'User capabilities inserted successfully.');
			}

			$user_level = $table_prefix . 'user_level';
			$sql = "INSERT INTO ". $table_prefix ."usermeta (user_id, meta_key, meta_value) values(" .$user_id .",'" . $user_level . "', '10')";
			if (!$dbc->run_sql_command($sql)){
				$this->logger->log_error(__METHOD__,'User level insert failed');
				return false;
			}else{
				$this->logger->log_info(__METHOD__,'User level inserted successfully.');
			}

			$this->logger->log_info(__METHOD__,'End - User created in database successfully.');
			return true;
		}

	}

		//update the site URL in the restored database
	function update_siteurl($table_prefix, $current_siteurl){
		$this->logger->log_info(__METHOD__,'Begin');

		$sql = "UPDATE ". $table_prefix ."options SET option_value='" .$current_siteurl ."' WHERE option_name='siteurl'";
		$dbc = new WPBackItUp_SQL($this->logger);
		if (!$dbc->run_sql_command($sql)){
			$this->logger->log('Error: SiteURL updated failed.');
			return false;
		}
		$this->logger->log_info(__METHOD__,'End - SiteURL updated in database:' .$current_siteurl);
		return true;
	}

	//Update homeURL
	function update_homeurl($table_prefix, $homeurl){
		$this->logger->log_info(__METHOD__,'Begin');

		$sql = "UPDATE ". $table_prefix ."options SET option_value='" .$homeurl ."' WHERE option_name='home'";
		$dbc = new WPBackItUp_SQL($this->logger);
		if (!$dbc->run_sql_command($sql)){
			$this->logger->log(__METHOD__,'HomeURL database update failed..');
			return false;
		}
		$this->logger->log_info(__METHOD__,'End - HomeURL updated in database:'.$homeurl);
		return true;
	}

	function update_permalinks(){
		global $wp_rewrite;
		$this->logger->log_info(__METHOD__,'Begin');

		try {
			$wp_rewrite->flush_rules( true );//Update permalinks -  hard flush

		}catch(Exception $e) {
			$this->logger->log_error(__METHOD__,'Exception: ' .$e);
			return false;
		}
		$this->logger->log_info(__METHOD__,'End - Permalinks updated.');
		return true;
	}

	public function zip_logs(){
		$this->logger->log_info(__METHOD__,'Begin');

		//Zip up all the logs in the log folder
		$logs_path = WPBACKITUP__PLUGIN_PATH .'logs';
		$zip_file_path = $logs_path .'/Restore_Logs_' .$this->backup_id . '.zip';

		//copy/replace WP debug file
		$wpdebug_file_path = WPBACKITUP__CONTENT_PATH . '/debug.log';
		$this->logger->log_info(__METHOD__,"Copy WP Debug: " .$wpdebug_file_path);
		if (file_exists($wpdebug_file_path)) {
			copy( $wpdebug_file_path, $logs_path .'/wpdebug.log' );
		}

		$zip = new WPBackItUp_Zip($this->logger,$zip_file_path);
		$zip->zip_files_in_folder($logs_path,$this->backup_id,'*.log');
		$zip->close();

		$this->logger->log_info(__METHOD__,'End');

		return $zip_file_path;

	}

	function send_notification_email($err, $success,$logs=array(),$notification_email) {
		global $logger,$status_array,$backup_job;
		$logger->log_info(__METHOD__,"Begin");

		$utility = new WPBackItUp_Utility($logger);

		if($success)
		{
			//Don't send logs on success unless debug is on.
			if (WPBACKITUP__DEBUG!==true){
				$logs=array();
			}

			$subject = get_bloginfo() . ' - Restore completed successfully.';
			$message = '<b>Your site was restored successfully.</b><br/><br/>';

		} else  {
			$subject = get_bloginfo() .' - Backup did not complete successfully.';
			$message = '<b>Your restore did not complete successfully.</b><br/><br/>';
		}

		$local_datetime = get_date_from_gmt(date( 'Y-m-d H:i:s',current_time( 'timestamp' )));
		$message .= 'WordPress Site: <a href="'  . home_url() . '" target="_blank">' . home_url() .'</a><br/>';
		$message .= 'Restore date: '  . $local_datetime . '<br/>';

		//$message .= 'Completion Code: ' . $backup_job->backup_id .'-'. $processing_minutes .'-' .$processing_seconds .'<br/>';
		$message .= 'WP BackItUp Version: '  . WPBACKITUP__VERSION . '<br/>';
		$message .= '<br/>';


		//Add the completed steps on success
//		if($success) {
//			$message .='<b>Steps Completed</b><br/>';
//
//			//Add the completed statuses
//			foreach ($status_array as $status_key => $status_value) {
//				if ($status_value==2) {
//					foreach ($status_description as $msg_key => $msg_value) {
//						if ($status_key==$msg_key) {
//							$message .=  $msg_value . '<br/>';
//							break;
//						}
//					}
//				}
//			}
//		} else  {
//			//Error occurred
//			$message .= '<br/>';
//			$message .= 'Errors:<br/>' . get_error_message($err);
//		}

//		$term='success';
//		if(!$success)$term='error';
//		$message .='<br/><br/>Checkout '. $WPBackitup->get_anchor_with_utm('www.wpbackitup.com', '', 'notification+email', $term) .' for info about WP BackItUp and our other products.<br/>';


		if($notification_email)
			$utility->send_email($notification_email,$subject,$message,$logs);

		$logger->log_info(__METHOD__,"End");
	}

	function save_process_status($process,$status){
		$this->logger->log_info(__METHOD__,"Begin");

		//Write status to JSON file - cant use database because it will e restored
		$local_datetime = get_date_from_gmt(date( 'Y-m-d H:i:s',current_time( 'timestamp' )));
		$process_status = array(
			'status'    => $status,
			'start_time'=>$local_datetime,
			'end_time'  =>''
		);

		$log_file_path = WPBACKITUP__PLUGIN_PATH .'logs/restore_' .$this->backup_id .'.log';
		$restore_status_string=false;
		if (file_exists($log_file_path)){
			$restore_status_string = file_get_contents($log_file_path);
		}

		if (false===$restore_status_string || empty($restore_status_string)){
			$restore_status_array=array($process=>$process_status);
		} else{
			$restore_status_array = json_decode($restore_status_string,true);

			//Does the process already exist
			if (! array_key_exists ($process,$restore_status_array)){
				//Add to existing array
				$restore_status_array[$process]=$process_status;
			}else{
				//update the end time
				$restore_status_array[$process]['end_time'] = $local_datetime;
				$restore_status_array[$process]['status'] = $status;
			}

		}

		$restore_status_string = json_encode($restore_status_array);
		file_put_contents($log_file_path, $restore_status_string);

		$this->logger->log_info(__METHOD__,"End");
	}





	/** GETTERS */

	/**
	 * @return string
	 */
	public function get_backup_folder_path() {
		return $this->backup_folder_path;
	}

	/**
	 * @return string
	 */
	public function get_restore_root_folder_path() {
		return $this->restore_root_folder_path;
	}

	/**
	 * @return string
	 */
//	public function get_restore_folder_path() {
//		return $this->restore_folder_path;
//	}

	/**
	 * @return mixed
	 */
	public function get_backup_name() {
		return $this->backup_name;
	}

	/**
	 * @return string
	 */
	public function get_restore_staging_suffix() {
		return $this->restore_staging_suffix;
	}

	/**
	 * @return mixed
	 */
	public function get_backup_id() {
		return $this->backup_id;
	}

}