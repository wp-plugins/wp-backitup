<?php 
//define constants
define("WP_BACKITUP_DIRNAME", basename(dirname(dirname(__FILE__))) );
define("BACKUP_PATH", dirname(dirname(__FILE__)) .'/backups/' );
define('WP_CONTENT_PATH', dirname(dirname(dirname(dirname(__FILE__)))));

//create log file
$log = BACKUP_PATH . "status.log";
$fh = fopen($log, 'w') or die("can't open file");
fwrite($fh, '<ul>');

//include functions
require('functions.php');

// 5 minutes per image should be PLENTY
	@set_time_limit(900);


//Delete any zips in the upload directory first
foreach (glob(BACKUP_PATH .'*.zip') as $file) {
	unlink($file);
} 

//Move the uploaded zip to the plugin directory
fwrite($fh, "<li>Uploading restoration file...");
$restore_file_name = basename( $_FILES['wpbackitup-zip']['name']);
$restore_path = BACKUP_PATH . $restore_file_name; 
if(move_uploaded_file($_FILES['wpbackitup-zip']['tmp_name'], $restore_path)) {
	fwrite($fh, "Done!</li>");
} else {
	fwrite($fh, "</li><li class=\"error\">Your file could not be uploaded.</li></ul>");
	recursive_delete($restoration_dir_path);
	unlink(BACKUP_PATH . $restore_file_name);
	fclose($fh);
	die();
	
}

//Unzip the uploaded restore file	 
fwrite($fh, "<li>Unzipping...");
$zip = new ZipArchive;
$res = $zip->open(BACKUP_PATH . $restore_file_name);
if ($res === TRUE) {
	$zip->extractTo(BACKUP_PATH);
	$zip->close();
	fwrite($fh, "Done!</li>");		
} else {
	fwrite($fh, "</li><li class=\"error\">Your restoration file could not be unzipped.</li></ul>");
	recursive_delete($restoration_dir_path);
	unlink(BACKUP_PATH . $restore_file_name);
	fclose($fh);
	die();
}

//Identify the restoration directory
fwrite($fh, "<li>Validating zip...");
if(count(glob(BACKUP_PATH . "*", GLOB_ONLYDIR)) == 1) { //does this need wilcard?
	foreach(glob(BACKUP_PATH . "*", GLOB_ONLYDIR) as $dir) { //does this need wilcard?
		$restoration_dir_path = $dir;
	}
}
if(glob($restoration_dir_path .'/backupsiteinfo.txt') ){
	fwrite($fh, "Done!</li>");
} else {
	fwrite($fh, "</li><li class=\"error\">Your zip file does not contain backupsiteinfo.txt. Please choose another file.</li></ul>");
	recursive_delete($restoration_dir_path);
	unlink(BACKUP_PATH . $restore_file_name);
	fclose($fh);
	die();
}

//If themes dir is present, restore it to wp-content
if(glob($restoration_dir_path . "/themes")) {
	fwrite($fh, "<li>Restoring themes...");
	$themes_dir = WP_CONTENT_PATH .'/themes';
	if(!recursive_delete($themes_dir)) {
		fwrite($fh, "</li><li class=\"error\">Unable to remove existing themes directory for import. Please check your CHMOD settings in /wp-content/themes.</li></ul>");
		recursive_delete($restoration_dir_path);
		unlink(BACKUP_PATH . $restore_file_name);
		fclose($fh);
		die();
	}
	if(!create_dir($themes_dir)) {
		fwrite($fh, "</li><li class=\"error\">Unable to create new themes directory for import. Please check your CHMOD settings in /wp-content/themes.</li></ul>");
		recursive_delete($restoration_dir_path);
		unlink(BACKUP_PATH . $restore_file_name);
		fclose($fh);
		die();
	}
	if(recusive_copy($restoration_dir_path .'/themes', $themes_dir, array( 'cgi-bin', '.', '..','._', $restore_file_name )) ) {
		fwrite($fh, "Done!</li>");
	} else {
		fwrite($fh, "</li><li class=\"error\">Unable to import themes. Please try again.</li></ul>");
		recursive_delete($restoration_dir_path);
		unlink(BACKUP_PATH . $restore_file_name);
		fclose($fh);
		die();
	}
} else {
	fwrite($fh, "<li class=\"error\">Warning: Themes directory not detected in import file.</li>");
}

//If uploads dir is present, restore it to wp-content
if(glob($restoration_dir_path . "/uploads")) {
	fwrite($fh, "<li>Restoring uploads...");
	$uploads_dir = WP_CONTENT_PATH .'/uploads';
	if(!recursive_delete($uploads_dir) ){
		fwrite($fh, "</li><li class=\"error\">Unable to create new uploads directory for import. Please check your CHMOD settings in /wp-content/uploads.</li></ul>");
		recursive_delete($restoration_dir_path);
		unlink(BACKUP_PATH . $restore_file_name);
		fclose($fh);
		die();
	}
	if(!create_dir($uploads_dir) ) {
		fwrite($fh, "</li><li class=\"error\">Unable to create new uploads directory for import. Please check your CHMOD settings in /wp-content/uploads.</li></ul>");
		recursive_delete($restoration_dir_path);
		unlink(BACKUP_PATH . $restore_file_name);
		fclose($fh);
		die();
	}
	if (recusive_copy($restoration_dir_path .'/uploads', $uploads_dir, array( 'cgi-bin', '.', '..','._', $restore_file_name )) ) {
		fwrite($fh, "Done!</li>");
	} else {
		fwrite($fh, "</li><li class=\"error\">Unable to import uploads. Please try again.</li></ul>");
		recursive_delete($restoration_dir_path);
		unlink(BACKUP_PATH . $restore_file_name);
		fclose($fh);
		die();
	}
} else {
	fwrite($fh, "<li class=\"error\">Warning: Uploads directory not detected in import file.</li>");
}

//If plugins dir is present, restore it to wp-content (exclude wp-backitup)
if(glob($restoration_dir_path . "/plugins")) {
	fwrite($fh, "<li>Restoring plugins...");
	$plugins_dir = WP_CONTENT_PATH .'/plugins';	
	if(!recursive_delete($plugins_dir, array('cgi-bin','.','..','._', WP_BACKITUP_DIRNAME) ) ) { 
		fwrite($fh, "</li><li class=\"error\">Unable to create new plugins directory for import. Please check your CHMOD settings in /wp-content/plugins.</li></ul>");
		recursive_delete($restoration_dir_path);
		unlink(BACKUP_PATH . $restore_file_name);
		fclose($fh);
		die();
	}
	if(!create_dir($plugins_dir) ){
		fwrite($fh, "</li><li class=\"error\">Unable to create new plugins directory for import. Please check your CHMOD settings in /wp-content/plugins.</li></ul>");
		recursive_delete($restoration_dir_path);
		unlink(BACKUP_PATH . $restore_file_name);
		fclose($fh);
		die();
	}
	if( recusive_copy($restoration_dir_path .'/plugins', $plugins_dir, array( 'cgi-bin', '.', '..','._', $restore_file_name )) ) {
		fwrite($fh, "Done!</li>");
	} else {
		fwrite($fh, "</li><li class=\"error\">Unable to import plugins. Please try again.</li></ul>");
		recursive_delete($restoration_dir_path);
		unlink(BACKUP_PATH . $restore_file_name);
		fclose($fh);
		die();
	}
} else {
		fwrite($fh, "<li class=\"error\">Warning: Plugins directory not detected in import file.</li>");
}

//if there is a database dump to restore
if(glob($restoration_dir_path . "/*.sql")) {
	//collect connection information from form
	fwrite($fh, "<li>Restoring database...");
	$db_name = $_POST['db_name'];
	$db_user = $_POST['db_user'];
	$db_pass = $_POST['db_pass'];
	$db_host = $_POST['db_host'];
	$table_prefix = $_POST['table_prefix'];
	$user_id = $_POST['user_id'];
	//Connect to DB
	$dbc = mysqli_connect($db_host, $db_user, $db_pass, $db_name); //OR die ('Could not connect to your database: ' .  );
	if ( !$dbc ) {
		fwrite($fh, "</li><li class=\"error\">Unable to connect to your current database: " .mysqli_connect_error() ."</li></ul>");
		recursive_delete($restoration_dir_path);
		unlink(BACKUP_PATH . $restore_file_name);
		fclose($fh);
		die();
	}
	//get siteurl
	$q1 = "SELECT option_value FROM " .$table_prefix ."options WHERE option_name =\"siteurl\";";
	if ($result = mysqli_query($dbc, $q1)) {
		while ($row = mysqli_fetch_row($result)) {
			$siteurl = $row[0];
		}
		mysqli_free_result($result);
	} else {
		fwrite($fh, "</li><li class=\"error\">Unable to get current site URL from database. Please try again.</li></ul>");
		recursive_delete($restoration_dir_path);
		unlink(BACKUP_PATH . $restore_file_name);
		fclose($fh);
		die();
	}
	//get homeurl
	$q2 = "SELECT option_value FROM " .$table_prefix ."options WHERE option_name =\"home\";";
	if ($result = mysqli_query($dbc, $q2)) {
		while ($row = mysqli_fetch_row($result)) {
			$homeurl = $row[0];
		}
		mysqli_free_result($result);
	} else {
		fwrite($fh, "</li><li class=\"error\">Unable to get current home URL from database. Please try again.</li></ul>");
		recursive_delete($restoration_dir_path);
		unlink(BACKUP_PATH . $restore_file_name);
		fclose($fh);
		die();
	}
	//get user login
	$q3 = "SELECT user_login FROM ". $table_prefix ."users WHERE ID=" .$user_id .";";
	if ($result = mysqli_query($dbc, $q3)) {
		while ($row = mysqli_fetch_row($result)) {
			$user_login = $row[0];
		}
		mysqli_free_result($result);
	} else {
		fwrite($fh, "</li><li class=\"error\">Unable to get current user ID from database. Please try again.</li></ul>");
		recursive_delete($restoration_dir_path);
		unlink(BACKUP_PATH . $restore_file_name);
		fclose($fh);
		die();
	}
	//get user pass
	$q4 = "SELECT user_pass FROM ". $table_prefix ."users WHERE ID=" .$user_id .";";
	if ($result = mysqli_query($dbc, $q4)) {
		while ($row = mysqli_fetch_row($result)) {
			$user_pass = $row[0];
		}
		mysqli_free_result($result);
	} else {
		fwrite($fh, "</li><li class=\"error\">Unable to get current user password from database. Please try again.</li></ul>");
		recursive_delete($restoration_dir_path);
		unlink(BACKUP_PATH . $restore_file_name);
		fclose($fh);
		die();
	}
	//get user email
	$q5 = "SELECT user_email FROM ". $table_prefix ."users WHERE ID=" .$user_id ."";
	if ($result = mysqli_query($dbc, $q5)) {
		while ($row = mysqli_fetch_row($result)) {
			$user_email = $row[0];
		}
		mysqli_free_result($result);
	} else {
		fwrite($fh, "</li><li class=\"error\">Unable to get current user email from database. Please try again.</li></ul>");
		recursive_delete($restoration_dir_path);
		unlink(BACKUP_PATH . $restore_file_name);
		fclose($fh);
		die();
	}
	//Collect previous backup site url start
	$import_siteinfo_lines = file($restoration_dir_path .'/backupsiteinfo.txt');
	$import_siteurl = trim($import_siteinfo_lines[0]);
	$current_siteurl = trim($siteurl ,'/');
	$import_table_prefix = $import_siteinfo_lines[1];
	//import the database
	if(!db_import($restoration_dir_path, $import_siteurl, $current_siteurl, $table_prefix, $import_table_prefix, $dbc)) {
		fwrite($fh, "</li><li class=\"error\">Unable to get import your database. This may require importing the file manually.</li></ul>");
		recursive_delete($restoration_dir_path);
		unlink(BACKUP_PATH . $restore_file_name);
		fclose($fh);
		die();
	}
	//update the database
	$q6 = "UPDATE ". $table_prefix ."options SET option_value=\"" .$current_siteurl ."\" WHERE option_name=\"siteurl\"";
	$q7 = "UPDATE ". $table_prefix ."options SET option_value=\"" .$homeurl ."\" WHERE option_name=\"home\"";
	$q8 = "UPDATE ". $table_prefix ."users SET user_login=\"" .$user_login ."\", user_pass=\"" .$user_pass ."\", user_email=\"" .$user_email ."\" WHERE ID=\"" .$user_id ."\"";
	if(!mysqli_query($dbc, $q6) ) {
		fwrite($fh, "</li><li class=\"error\">Unable to update your current site URL value. This may require importing the file manually.</li></ul>");
		recursive_delete($restoration_dir_path);
		unlink(BACKUP_PATH . $restore_file_name);
		fclose($fh);
		die();
	}
	if(!mysqli_query($dbc, $q7) ) {
		fwrite($fh, "</li><li class=\"error\">Unable to update your current home URL value. This may require importing the file manually.</li></ul>");
		recursive_delete($restoration_dir_path);
		unlink(BACKUP_PATH . $restore_file_name);
		fclose($fh);
		die();
	}	
	if(!mysqli_query($dbc, $q8) ) {
		fwrite($fh, "</li><li class=\"error\">Unable to update your user information. This may require importing the file manually.</li></ul>");
		recursive_delete($restoration_dir_path);
		unlink(BACKUP_PATH . $restore_file_name);
		fclose($fh);
		die();
	}
	fwrite($fh, "Done!</li>");	
} else {
	fwrite($fh, "<li class=\"error\">Warning: Database not detected in import file.</li>");
}

//Disconnect
mysqli_close($dbc);

//Delete the restoration directory
recursive_delete($restoration_dir_path);

//Delete zip
unlink(BACKUP_PATH . $restore_file_name);

//close log file
fwrite($fh, '<li>Restoration complete. Please refresh the page.</li>');
fwrite($fh, '</ul>');
fclose($fh);

//End backup function
die();