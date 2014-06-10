<?php if (!defined ('ABSPATH')) die('No direct access allowed');
/**
 * WP Backitup Backup Class
 * 
 * @package WP Backitup
 *
 * @author cssimmon
 *
 */
class WPBackItUp_Restore {

	private $logger;

	//Public Properties
    public $backup_folder_path;
	public $restore_folder_path;

	function __construct($logger) {
		global $WPBackitup;

		try {
			$this->logger = $logger;

            $this->backup_folder_path = WPBACKITUP__BACKUP_PATH  .'/';
            $this->restore_folder_path = WPBACKITUP__RESTORE_PATH .'/';


		} catch(Exception $e) {
            $this->logger->log($e);
			print $e;
		}
   }

   function __destruct() {
   		
   }




}