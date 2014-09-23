<?php if (!defined ('ABSPATH')) die('No direct access allowed');/** * WP Backitup Zip Function *  * @package WP Backitup *  * @author cssimmon * */class WPBackItUp_Zip {	private $logger;    private $zip_file_count;    private $max_file_count=1000;	function __construct($logger) {		try {			$this->logger = $logger;            $this->zip_file_count=0;		} catch(Exception $e) {			//Dont do anything			print $e;		}   }   function __destruct() {   		   }		public function compress($src,$dst='') {		$this->logger->log('(Zip.compress) Compress the backup folder FROM:'.$src);		$this->logger->log('(Zip.compress) Compress the backup folder TO:'.$dst);				$src = rtrim($src, '/');		if(substr($src,-1)==='/'){$src=substr($src,0,-1);}		if(substr($dst,-1)==='/'){$dst=substr($dst,0,-1);}		$path=strlen(dirname($src).'/');		$filename=substr($src,strrpos($src,'/')+1).'.zip';		$dst=empty($dst)? $filename : $dst.'/'.$filename;		@unlink($dst);		$zip = new ZipArchive;		$res = $zip->open($dst, ZipArchive::CREATE);		if($res !== TRUE){				$this->logger->log('(Zip.compress) Unable to create zip file');				return false;        }		if(is_file($src)){            if (!$zip->addFile($src,substr($src,$path))){                return false;            }        }		else{            if(!is_dir($src)){                 $zip->close();                 @unlink($dst);                 $this->logger->log('(Zip.compress) File not found:' . $dst);                 return false;        }        $rtnVal = $this->recurse_zip($src,$dst,$zip,$path);}		$zip->close();		$this->logger->log('(Zip.compress) Backup folder compressed successfully.');		return $rtnVal;	}	private function recurse_zip($src,$dst,&$zip,$path) {		$this->logger->log('(Zip.recurse_zip) Compress backup folder.' .$src);		$dir = opendir($src);        //Reopen the zip when you get to max file count        if($this->zip_file_count>=$this->max_file_count){            $zip->close();            $this->zip_file_count=0;            $zip = new ZipArchive;            $res = $zip->open($dst,ZIPARCHIVE::CREATE);            //Check for error            if($res !== TRUE){                $this->logger->log('(Zip.recurse_zip) Zip open cant be opened:' .$res);                return false;            }        }		while(false !== ( $file = readdir($dir)) ) {			if (( $file != '.' ) && ( $file != '..' )) {                $zipFilePath = substr($src . '/' . $file,$path);				if ( is_dir($src . '/' . $file) ) {                    if (!$zip->addEmptyDir($zipFilePath)){                        $this->logger->log('(Zip.recurse_zip) Cant add empty directory' .$zipFilePath);                        return false;                    }                    $this->zip_file_count++;                    if (!$this->recurse_zip($src . '/' . $file,$dst,$zip,$path)){                        $this->logger->log('(Zip.recurse_zip)Recursive zip error');                        return false;                    }				}				else {					if (!$zip->addFile($src . '/' . $file,$zipFilePath)){                        $this->logger->log('(Zip.recurse_zip)Cant add file to zip');                        return false;                    }                    $this->zip_file_count++;				}			}		}		$this->logger->log('(Zip.recurse_zip) Backup folder compressed.' .$src);		closedir($dir);        return true;	}}