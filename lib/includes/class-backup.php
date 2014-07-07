<?php if (!defined ('ABSPATH')) die('No direct access allowed');
/**
 * WP Backitup Backup Class
 * 
 * @package WP Backitup
 * 
 * @author cssimmon
 *
 */
class WPBackItUp_Backup {

	private $logger;

	//Public Properties
	public $backup_name;
	public $backup_filename;
	public $backup_project_path;
	public $backup_folder_root;
	public $restore_folder_root;
	public $backup_retained_number;
    public $backup_retained_days;

	function __construct($logger,$backup_name) {
		global $WPBackitup;
		try {
			$this->logger = $logger;
			$this->backup_name=$backup_name;
			$this->backup_filename=$backup_name . '.zip';

			$backup_project_path = WPBACKITUP__BACKUP_PATH .'/'. $backup_name .'/';
			//echo('</br>Backup Proj Path:' .$backup_project_path);

			$backup_folder_root =WPBACKITUP__BACKUP_PATH  .'/';
			$restore_folder_root = WPBACKITUP__RESTORE_FOLDER;

			$this->backup_project_path=$backup_project_path;
			$this->backup_folder_root=$backup_folder_root;
			$this->restore_folder_root=$restore_folder_root;

			$this->backup_retained_number = $WPBackitup->backup_retained_number();
            $this->backup_retained_days = 5; //Prob need to move this to main propery

		} catch(Exception $e) {
			//Dont do anything
			print $e;
		}
   }

   function __destruct() {
   		
   }




}