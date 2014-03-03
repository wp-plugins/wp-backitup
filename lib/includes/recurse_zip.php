<?php /** * WP Backitup Recurse Zip Function *  * @package WP Backitup *  * @author jcpeden * @version 1.4.0 * @since 1.0.1 */class recurseZip {	private function recurse_zip($src,&$zip,$path) {		$dir = opendir($src);		while(false !== ( $file = readdir($dir)) ) {			if (( $file != '.' ) && ( $file != '..' )) {				if ( is_dir($src . '/' . $file) ) {					$this->recurse_zip($src . '/' . $file,$zip,$path);				}				else {					$zip->addFile($src . '/' . $file,substr($src . '/' . $file,$path));				}			}		}		closedir($dir);	}	public function compress($src,$dst='') {		_log('(recurseZip.compressZip) Compress the backup folder FROM:'.$src);		_log('(recurseZip.compressZip) Compress the backup folder TO:'.$dst);				if(substr($src,-1)==='/'){$src=substr($src,0,-1);}		if(substr($dst,-1)==='/'){$dst=substr($dst,0,-1);}		$path=strlen(dirname($src).'/');		$filename=substr($src,strrpos($src,'/')+1).'.zip';		$dst=empty($dst)? $filename : $dst.'/'.$filename;		@unlink($dst);		$zip = new ZipArchive;		$res = $zip->open($dst, ZipArchive::CREATE);		if($res !== TRUE){				echo 'Error: Unable to create zip file';				exit;}		if(is_file($src)){$zip->addFile($src,substr($src,$path));}		else{				if(!is_dir($src)){					 $zip->close();					 @unlink($dst);					 echo 'Error: File not found';					 exit;}		$this->recurse_zip($src,$zip,$path);}		$zip->close();		return $dst;	}}