<?php if (!defined ('ABSPATH')) die('No direct access allowed');

/**
 * WP Backitup Utility Class
 * 
 * @package WP Backitup
 * 
 * @author cssimmon
 *
 */
/*** Includes ***/
// include backup class
if( !class_exists( 'WPBackItUp_RecursiveFilter_Iterator' ) ) {
    include_once 'class-recursiveFilter_Iterator.php';
}

class WPBackItUp_FileSystem {

	private $logger;

	function __construct($logger) {
		try {
			$this->logger = $logger;
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
            if (!$this->ignore($dir,$ignore)){
                if($dh = opendir($dir)) {
                    while( ($file = readdir($dh)) !== false ) {
                        if (!$this->ignore($file,$ignore)) { //Check the file is not in the ignore array
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
            if (!$this->ignore($dir,$ignore)){
                if ($dh = opendir($dir) ) {
                    while(($file = readdir($dh)) !== false) { //While there are files in the directory
                        if ( !$this->ignore($file,$ignore)) { //Check the file is not in the ignore array
                            if (!is_dir( $dir.$file ) ) {
                                try {
                                    $fsrc = fopen($dir .$file,'r');
                                    $fdest = fopen($target_path .$file,'w+');
                                    stream_copy_to_stream($fsrc,$fdest);
                                    fclose($fsrc);
                                    fclose($fdest);
                                } catch(Exception $e) {
                                    $this->logger->log('(FileSystem.recursive_copy) Exception: ' .$e);
                                    return false;
                                }
                            } else { //If $file is a directory
                                $destdir = $target_path .$file; //Modify the destination dir
                                if(!is_dir($destdir)) { //Create the destdir if it doesn't exist
                                    $this->logger->log('(FileSytem.recursive_copy) Create Folder: ' .$destdir);
                                    @mkdir($destdir, 0755);
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
        $this->logger->log('(FileSystem.recursive_validate) Recursive validate FROM: ' .$source_path);
        $this->logger->log('(FileSystem.recursive_validate) Recursive validate TO: '.$target_path);
        $this->logger->log('(FileSystem.recursive_validate) IGNORE:');
        $this->logger->log($ignore);

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

        $this->logger->log('(FileSystem.recursive_validate) Completed:' . ($rtnVal ? 'true' : 'false'));
        return $rtnVal;
    }

    private function ignore($file, $ignoreList){
        $ignore = false;

        //Exclude these files and folders from the delete
        if (in_array(basename($file), $ignoreList) ||
            substr($file, 0, 1) == '.'   ||
            ($file == "." ) ||
            ($file == ".." ) ||
            ($file == "._" ) ||
            ($file == "cgi-bin" ))  {
            $ignore = true;

            $this->logger->log('(FileSystem.recursive_delete) IGNORE:'.$file);
        }

        return $ignore;
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
			  if($i <= $number_Files_Allowed)
			  {
			    $i++;
			    continue;
			  }
			  else{
                $log_file_path = str_replace('.zip','.log',$val);
			    unlink($val);
                unlink($log_file_path);
                $this->logger->log('(FileSytem.purge_FilesByDate) Delete File:)' .$val);

			  }
			}
		}
		$this->logger->log('(FileSytem.purge_FilesByDate) Completed.)');
	}

    public function purge_files($path, $file_extension, $days)
    {
        $this->logger->log('(FileSytem.purge_files) Purge files days:' . $days);
        $this->logger->log('(FileSytem.purge_files) Purge files path:' . $path);
        $this->logger->log('(FileSytem.purge_files) Purge files extension:' . $file_extension);

        //Check Parms
        if (empty($path) ||  empty($file_extension) || !is_numeric($days)){
            $this->logger->log('(FileSytem.purge_files) Invalid Parm values');
            return false;
        }

        $FileList = glob($path . '*.' . $file_extension);
        //Sort by Date Time
        usort($FileList, create_function('$a,$b', 'return filemtime($a) - filemtime($b);'));

        foreach ($FileList as $key => $file)
        {
            $current_date = new DateTime('now');
            $file_mod_date = new DateTime(date('Y-m-d',filemtime($file)));
            $date_diff = $current_date->diff($file_mod_date);

            if($date_diff->days>=$days){
                unlink($file);
                $this->logger->log('Delete:' . $file);
            }
            else{
                break; //Exit for
            }
        }
        $this->logger->log('(FileSytem.purge_files) Completed.');
        return true;
    }

    function get_file_handle($path,$newFile) {
        $this->logger->log('(FileSytem.get_file_handle) Path:' . $path);

        if ($newFile && file_exists($path)){
            unlink($path);
            $this->logger->log('(FileSytem.get_file_handle) Deleted:' . $path);
        }

        return fopen($path, 'w');
    }


 }