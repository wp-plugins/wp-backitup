<?php if (!defined ('ABSPATH')) die('No direct access allowed');

/**
 * WP BackItUp  - File System Class
 *
 * @package WP BackItUp
 * @author  Chris Simmons <chris.simmons@wpbackitup.com>
 * @link    http://www.wpbackitup.com
 *
 */

/*** Includes ***/
// include backup class
if( !class_exists( 'WPBackItUp_RecursiveFilter_Iterator' ) ) {
    include_once 'class-recursiveFilter_Iterator.php';
}

class WPBackItUp_FileSystem {

	private $logger;

	function __construct($logger=null) {
		try {
			if (null==$logger){
				$this->logger = new WPBackItUp_Logger(true,null,'debug_filesystem');
			} else{
				$this->logger = $logger;
			}
		} catch(Exception $e) {
			//Dont do anything
			print $e;
		}
   }

   function __destruct() {
   		
   }

    public function create_dir($dir) {
   		$this->logger->log('(FileSytem.create_dir) Create Directory: ' .$dir);
		if( !is_dir($dir) ) {
			@mkdir($dir, 0755);
		}
		$this->logger->log('(FileSytem.create_dir) Directory created: ' .$dir);
		return true;
	}

	public function recursive_delete($dir, $ignore = array('') ){
		$this->logger->log('(FileSystem.recursive_delete) Recursively Delete: ' .$dir);

        $this->logger->log('(FileSystem.recursive_delete) Ignore:');
        $this->logger->log($ignore);

        if( is_dir($dir) ){
            //Make sure the folder is not in the ignore array
            if (!$this->delete_ignore($dir,$ignore)){
                if($dh = opendir($dir)) {
                    while( ($file = readdir($dh)) !== false ) {
                        if (!$this->delete_ignore($file,$ignore)) { //Check the file is not in the ignore array
                            if(!is_dir($dir .'/'. $file)) {
                                unlink($dir .'/'. $file); //delete the file
                                $this->logger->log('(FileSytem.recursive_delete) File Deleted:' .$dir .'/'. $file);
                            } else {
                                //This is a dir so delete the files first
                                $this->recursive_delete($dir.'/'. $file, $ignore);
                            }
                        }
                    }
                }
                //Remove the directory
                @rmdir($dir);
                $this->logger->log('(FileSystem.recursive_delete) Folder Deleted:' .$dir);
                closedir($dh);
            }
		}
		$this->logger->log('(FileSystem.recursive_delete) Recursive Delete Completed.');
		return true;
	}

    public function recursive_copy($dir, $target_path, $ignore = array('') ) {
        $this->logger->log('(FileSystem.recursive_copy) Recursive copy FROM: ' .$dir);
        $this->logger->log('(FileSystem.recursive_copy) Recursive Copy TO: '.$target_path);
        $this->logger->log('(FileSystem.recursive_copy) IGNORE:');
        $this->logger->log($ignore);

        if( is_dir($dir) ) { //If the directory exists
            //Exclude all the OTHER backup folders under wp-content
            //Will create the folders but NOT the contents
            if (!$this->ignore($dir,$ignore) && !$this->is_backup_folder($dir) ){
                if ($dh = opendir($dir) ) {
                    while(($file = readdir($dh)) !== false) { //While there are files in the directory
                        if (!$this->ignore($file,$ignore)) { //Check the file is not in the ignore array
                            if (!is_dir( $dir.$file ) ) {
                                try {
                                    $fsrc = fopen($dir .$file,'r');
                                    $fdest = fopen($target_path .$file,'w+');
                                    stream_copy_to_stream($fsrc,$fdest);
                                    fclose($fsrc);
                                    fclose($fdest);
                                } catch(Exception $e) {
                                    $this->logger->log('(FileSystem.recursive_copy) File Copy Exception: ' .$e);
                                    return false;
                                }
                            } else { //If $file is a directory
                                $destdir = $target_path .$file; //Modify the destination dir
                                if(!is_dir($destdir)) { //Create the destdir if it doesn't exist
                                    $this->logger->log('(FileSytem.recursive_copy) Create Folder: ' .$destdir);
                                    try {
                                        @mkdir($destdir, 0755);
                                    } catch(Exception $e) {
                                        $this->logger->log('(FileSystem.recursive_copy)Create Folder Exception: ' .$e);
                                        return false;
                                    }
                                }
                                $this->recursive_copy($dir .$file .'/', $target_path .$file .'/', $ignore);
                            }
                        }
                    }
                    closedir($dh);
                }
            }
        }

        $this->logger->log('(FileSystem.recursive_copy) Completed');
        return true;
    }

    public function recursive_validate($source_path, $target_path, $ignore = array('') ) {
//        $this->logger->log('(FileSystem.recursive_validate) Recursive validate FROM: ' .$source_path);
//        $this->logger->log('(FileSystem.recursive_validate) Recursive validate TO: '.$target_path);
//        $this->logger->log('(FileSystem.recursive_validate) IGNORE:');
//        $this->logger->log($ignore);

        $rtnVal=true;
        if( is_dir($source_path) ) { //If the directory exists
            if (!$this->ignore($source_path,$ignore)){
                if ($dh = opendir($source_path) ) {
                    while(($file = readdir($dh)) !== false) { //While there are files in the directory
                        if ( !$this->ignore($file,$ignore)) { //Check the file is not in the ignore array
                            if (!is_dir( $source_path.$file ) ) {
                                try {
                                    $source_file = $source_path .$file;
                                    $target_file = $target_path .$file;

                                    if (!file_exists($target_file))  {
                                        $this->logger->log('(FileSystem.recursive_validate) Files DIFF - Target file doesnt exist:' . $target_file);
                                        $rtnVal=false;
                                        continue;
                                    }

                                    $source_file_size = filesize ($source_file);
                                    $target_file_size = filesize ($target_file);

                                    if ($source_file_size != $target_file_size){
                                        $this->logger->log('(FileSystem.recursive_validate) Files DIFF Source:' . $source_file);
                                        $this->logger->log('(FileSystem.recursive_validate) Files DIFF Target:' . $target_file);
                                        $this->logger->log('(FileSystem.recursive_validate) Files DIFF Size:' . $source_file_size .':' . $target_file_size);
                                        $rtnVal=false;
                                        continue;
                                    }

                                } catch(Exception $e) {
                                    $this->logger->log('(FileSystem.recursive_validate) Exception: ' .$e);
                                    return false;
                                }
                            } else { //If $file is a directory
                                $destdir = $target_path .$file; //Modify the destination dir
                                if(!is_dir($destdir)) {
                                    $this->logger->log('(FileSytem.recursive_validate) DIFF Folder doesnt exist: ' .$destdir);
                                    $rtnVal= false;
                                }else{
                                    $dir_rtnVal=$this->recursive_validate($source_path .$file .'/', $target_path .$file .'/', $ignore);
                                    //Don't want to set to true as its the default on all calls
                                    if (!$dir_rtnVal) $rtnVal = false;
                                }
                            }
                        }
                    }
                    closedir($dh);
                }
            }
        }

        //$this->logger->log('(FileSystem.recursive_validate) Completed:' . ($rtnVal ? 'true' : 'false'));
        return $rtnVal;
    }

    private function ignore($file, $ignoreList){

        //Exclude these files and folders from the delete
        if (in_array(basename($file), $ignoreList) ||
            substr($file, 0, 1) == '.'   ||
            ($file == "." ) ||
            ($file == ".." ) ||
            ($file == "._" ) ||
            ($file == "cgi-bin" ))  {

            //$this->logger->log('(FileSystem.ignore) IGNORE:'.$file);
            return true;
        }

        return false;
    }

	private function delete_ignore($file, $ignoreList){

		//Exclude these files and folders from the delete
		if (in_array(basename($file), $ignoreList) ||
		    //substr($file, 0, 1) == '.'   ||
		    ($file == "." ) ||
		    ($file == ".." ))
		    //($file == "._" )
		    //($file == "cgi-bin" ))
		{
			//$this->logger->log('(FileSystem.ignore) IGNORE:'.$file);
			return true;
		}

		return false;
	}

    //Check for backup folders
    private function is_backup_folder($dir){
        if  (
            strpos(strtolower($dir),'/wp-content/backup')!== false ||
            strpos(strtolower($dir),'/wp-content/updraft')!== false ||
            strpos(strtolower($dir),'/wp-content/wp-clone')!== false ||
            strpos(strtolower($dir),'/wp-content/uploads/backwpup')!== false ||
            strpos(strtolower($dir),'/wp-content/uploads/backupwordpress')!== false
            ){

                $this->logger->log('(FileSystem.is_backup_folder) SKIP Backup Folder: ' .$dir);
                return true;

            }else{
                return false;
            }

    }


//    function delete_children_recursive($path, $ignore = array('cgi-bin','._'))
//    {   //The filters are not working on this method
//        return false;
//        if (is_dir($path))
//        {
//            $this->logger->log('(FileSystem_delete_children_recursive) Ignore:');
//            $this->logger->log($ignore);
//
//            $iterator = new RecursiveDirectoryIterator($path);
//            $iterator->setFlags(RecursiveDirectoryIterator::SKIP_DOTS);
//            $filter = new WPBackItUp_RecursiveFilter_Iterator($iterator);
//            $filter->set_filter($ignore);
//
//            $all_files  = new RecursiveIteratorIterator($filter,RecursiveIteratorIterator::CHILD_FIRST);
//
//            foreach ($all_files as $file)
//            {
//                if ($file->isDir())
//                {
//                    $this->logger->log('(delete_recursive_new) delete folder:'.$file);
//                    rmdir($file->getPathname());
//                }
//                else
//                {
//                    $this->logger->log('(delete_recursive_new) delete file:'.$file);
//                    unlink($file->getPathname());
//
//                }
//
//                $this->logger->log('(FileSystem_delete_children_recursive) Deleted:' . $file);
//            }
//        }
//        return true;
//    }


	public function purge_FilesByDate($number_Files_Allowed,$path)
	{
		$this->logger->log('(FileSytem.purge_FilesByDate) Purge files by date:' .$number_Files_Allowed .':'.$path);

		if (is_numeric($number_Files_Allowed) && $number_Files_Allowed> 0){
			$FileList = glob($path . "*.zip");

			//Sort by Date Time			
			usort($FileList, create_function('$a,$b', 'return filemtime($b) - filemtime($a);'));
			  	 
			$i = 1;
			foreach ($FileList as $key => $val)
			{
                $this->logger->log_info(__METHOD__,' File:'.$val);
                $this->logger->log_info(__METHOD__,' File Date Time:'.filemtime($val));

			  if($i <= $number_Files_Allowed)
			  {
			    $i++;
			    continue;
			  }
			  else{
                $log_file_path = str_replace('.zip','.log',$val);
                if (file_exists($val)) unlink($val);
                if (file_exists($log_file_path)) unlink($log_file_path);
                $this->logger->log('(FileSytem.purge_FilesByDate) Delete File:)' .$val);

			  }
			}
		}
		$this->logger->log('(FileSytem.purge_FilesByDate) Completed.');
	}

    public function purge_files($path, $file_pattern, $days)
    {
        $this->logger->log('(FileSytem.purge_files) Purge files days:' . $days);
        $this->logger->log('(FileSytem.purge_files) Purge files path:' . $path);
        $this->logger->log('(FileSytem.purge_files) Purge files extension:' . $file_pattern);

        //Check Parms
        if (empty($path) ||  empty($file_pattern) || !is_numeric($days)){
            $this->logger->log('(FileSytem.purge_files) Invalid Parm values');
            return false;
        }

        $FileList = glob($path . $file_pattern);

        //Sort by Date Time oldest first so can break when all old files are deleted
        usort($FileList, create_function('$a,$b', 'return filemtime($a) - filemtime($b);'));

        foreach ($FileList as $key => $file)
        {
            $this->logger->log_info(__METHOD__,' File:'.$file);
            $this->logger->log_info(__METHOD__,' File Date Time:'.filemtime($file));

            $current_date = new DateTime('now');
            $file_mod_date = new DateTime(date('Y-m-d',filemtime($file)));

            //PHP 5.3 only
            //$date_diff = $current_date->diff($file_mod_date);
            //$date_diff_days = $date_diff->days;

            $util = new WPBackItUp_Utility( $this->logger);
            $date_diff_days=$util->date_diff_days($file_mod_date,$current_date);

            if($date_diff_days>=$days){
                if (file_exists($file)) unlink($file);
                $this->logger->log('Delete:' . $file);
            }
            else{
                break; //Exit for
            }
        }
        $this->logger->log('(FileSytem.purge_files) Completed.');
        return true;
    }


	/**
     * Purge the backups that exceed the retained number setting
     *
     * @param $path
     * @param $pattern
     * @param $retention_limit
     *
     * @return bool
     */
    public function purge_folders($path, $pattern, $retention_limit)
    {
        $this->logger->log_info(__METHOD__,' Purge folders retained number:' . $retention_limit);
        $this->logger->log_info(__METHOD__,' Purge folder path:' . $path);
        $this->logger->log_info(__METHOD__,' Purge pattern:' . $pattern);

        //Check Parms
        if (empty($path) ||  empty($pattern) || !is_numeric($retention_limit)){
            $this->logger->log_error(__METHOD__,' Invalid Parm values');
            return false;
        }

        $folder_list = glob($path . $pattern, GLOB_ONLYDIR);

        //Sort by Date Time so oldest is deleted first
        //usort($folder_list, create_function('$a,$b', 'return filemtime($b) - filemtime($a);'));

        $backup_count=0;
        foreach (array_reverse($folder_list) as $key => $folder)
        {
            $this->logger->log_info(__METHOD__,' Folder:'.$folder);
            $this->logger->log_info(__METHOD__,' Folder Date Time:'.filemtime($folder));

            ++$backup_count;
            if($backup_count>$retention_limit){
                if (file_exists($folder)) {
                    $this->recursive_delete($folder);
                }
            }
        }
        $this->logger->log_info(__METHOD__,'End');
        return true;
    }

	public function delete_files($file_list)
	{
		$this->logger->log_info(__METHOD__,'Begin');

		foreach ($file_list as $key => $file)
		{
			if (file_exists($file)){
				unlink($file);
				$this->logger->log('Deleted:' . $file);
			}
		}
		$this->logger->log_info(__METHOD__,'End');
		return true;
	}


	function get_file_handle($path,$newFile=false) {
        $this->logger->log('(FileSytem.get_file_handle) Path:' . $path);

        try {

            if ($newFile && file_exists($path)){
                if (unlink($path)){
                    $this->logger->log('(FileSytem.get_file_handle) Deleted:' . $path);
                }
                else{
                    $this->logger->log('(FileSytem.get_file_handle) File could not be deleted:');
                    $this->logger->log(error_get_last());
                }
            }

            $fh= fopen($path, 'w');
            if (false===$fh){
                $this->logger->log('(FileSytem.get_file_handle) File could not be opened:');
                $this->logger->log(error_get_last());
                return false;
            }

            return $fh;

        } catch(Exception $e) {
            $this->logger->log('(FileSytem.get_file_handle) Exception:' . $e);
            return false;
        }
    }

	/**
	 * Copy single file
	 * @param $from_file
	 * @param $to_file
	 *
	 * @return bool
	 */
	function copy_file($from_file,$to_file) {
		$this->logger->log('(FileSystem.copy_file) FROM Path:' . $from_file);
		$this->logger->log('(FileSystem.copy_file) TO Path:' . $to_file);

		try {
			if (file_exists($from_file)){
				if (copy($from_file,$to_file)){
					$this->logger->log('(FileSystem.copy_file) File copied successfully.');
					return true;
				}
				else{
					$this->logger->log('(FileSystem.copy_file) File could not be copied:');
					$this->logger->log(error_get_last());
					return false;
				}
			}
			else{
				$this->logger->log('(FileSystem.copy_file) FROM File doesnt exist');
				return false;
			}

		} catch(Exception $e) {
			$this->logger->log('(FileSystem.copy_file) Exception:' . $e);
			return false;
		}
	}

	/**
	 * Rename single file
	 * @param $from_file
	 * @param $to_file_name
	 *
	 * @return bool
	 */
	function rename_file($from_file,$to_file_name) {
		$this->logger->log_info(__METHOD__,' FROM Path:' . $from_file);
		$this->logger->log_info(__METHOD__,' TO Path:' . $to_file_name);

		try {
			if (file_exists($from_file)){
				if (rename($from_file,$to_file_name)){
					$this->logger->log_info(__METHOD__,'File renamed successfully.');
					return true;
				}
				else{
					$this->logger->log_error(__METHOD__,'File could not be renamed:');
					$this->logger->log(error_get_last());
					return false;
				}
			}
			else{
				$this->logger->log_error(__METHOD__,'FROM File doesnt exist');
				return false;
			}

		} catch(Exception $e) {
			$this->logger->log_error(__METHOD__,' Exception:' . $e);
			return false;
		}
	}

	/**
	 * Make sure that htaccess/web.config files exist in folder
	 * If folder doesnt exist then create it.
	 * @param $path
	 */
	function secure_folder($path){
		$this->logger->log_info(__METHOD__,'Begin');

		$path = rtrim($path,"/");

		if( !is_dir($path) ) {
			@mkdir($path, 0755);
			$this->logger->log_info(__METHOD__,'Folder Created:' .$path);
		}

		if (!is_file($path.'/index.html')) @file_put_contents($path.'/index.html',"<html><body><a href=\"http://www.wpbackitup.com\">WP BackItUp - The simplest way to backup WordPress</a></body></html>");
		if (!is_file($path.'/.htaccess')) @file_put_contents($path.'/.htaccess','deny from all');
		if (!is_file($path.'/web.config')) @file_put_contents($path.'/web.config', "<configuration>\n<system.webServer>\n<authorization>\n<deny users=\"*\" />\n</authorization>\n</system.webServer>\n</configuration>\n");
		$this->logger->log_info(__METHOD__,'Secure files exist or were created.');


		$this->logger->log_info(__METHOD__,'End');
	}


	public function get_recursive_file_list($pattern) {
		//$this->logger->log_info( __METHOD__, 'Begin: ' .$pattern );

		return $this->glob_recursive($pattern);
	}

	private function glob_recursive($pattern, $flags = 0) {
        //$this->logger->log_info( __METHOD__, 'Begin' );

		//The order here is important because the folders must be in the list before the files.
		$files = glob($pattern, $flags); //everything in the root
        //$this->logger->log_info( __METHOD__, 'Files Count:' . count($files));

        //Get the folders and append all the files in the folder
		foreach (glob(dirname($pattern).'/*', GLOB_ONLYDIR) as $dir)
		{
            //Get the contents of the folder
            $current_folder = $this->glob_recursive($dir.'/'.basename($pattern), $flags);

            if (is_array($current_folder)){
			    $files = array_merge($files,$current_folder );
            }
		}

		return $files;
	}


 }

