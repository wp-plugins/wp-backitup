<?php //Define the create_dir function
if(!function_exists('create_dir')) {
	function create_dir($dir) {
		if( !is_dir($dir) ) {
			@mkdir($dir, 0755);
		}
		return true;
	}
}

//Define recusive_copy function
if(!function_exists('recursive_copy')) {
	function recursive_copy($dir, $target_path, $ignore = array( 'cgi-bin','..','._' ) ) {
		if( is_dir($dir) ) { //If the directory exists
			if ($dh = opendir($dir) ) {
				while(($file = readdir($dh)) !== false) { //While there are files in the directory
					if ( !in_array($file, $ignore) && substr($file, 0, 1) != '.') { //Check the file is not in the ignore array
						if (!is_dir( $dir.$file ) ) {
								//Copy files to destination directory
								$fsrc = fopen($dir .$file,'r');
								$fdest = fopen($target_path .$file,'w+');
								$len = stream_copy_to_stream($fsrc,$fdest);
								fclose($fsrc);
								fclose($fdest);
						} else { //If $file is a directory
							$destdir = $target_path .$file; //Modify the destination dir
							if(!is_dir($destdir)) { //Create the destdir if it doesn't exist
								@mkdir($destdir, 0755);
							} 	
							recursive_copy($dir .$file .'/', $target_path .$file .'/', $ignore);
						}
					}
				}
				closedir($dh);
			}
		}
	return true;
	}	
}

//Define DB backup function
if(!function_exists('db_backup')) {
	function db_backup($path) { 
		global $wpdb;
		$row = $wpdb->get_results('SHOW TABLES', ARRAY_N);
		$tables = array();
		foreach($row as $value) {
			$tables[] = $value[0];
		}
		$handle = fopen($path .'db-backup.sql', 'w+');
		foreach($tables as $table) {
			$result = $wpdb->get_results('SELECT * FROM '.$table,ARRAY_N);
			$testing = $wpdb->get_row('SELECT * FROM '.$table,ARRAY_N);
			$num_fields=count($testing);
			$return = '';
			$return.= 'DROP TABLE IF EXISTS '.$table.';';
			$row2 = $wpdb->get_row('SHOW CREATE TABLE '.$table,ARRAY_N);
			$return.= "\n\n".$row2[1].";\n\n";
			foreach($result as $row) {
				$return.= 'INSERT INTO '.$table.' VALUES(';
				for($j=0; $j<$num_fields; $j++) {
					$row[$j] = addslashes($row[$j]);
					$row[$j] = ereg_replace("\n", "\\n",$row[$j]);
					if (isset($row[$j])) { 
						$return .= '"' .$row[$j] .'"'; 
					} else { 
						$return .= '"'; 
					}
					if ($j<($num_fields-1)) { $return.= ', '; }
				}
				$return.= ");\n";
			}
			$return.="\n\n\n";
			fwrite($handle, $return);
		}
		fclose($handle);	
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