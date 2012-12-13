<?php
//Define variables
$wp_backitup_path = dirname(dirname(__FILE__));	
$wp_content_dirname = basename(dirname(dirname(dirname(dirname(__FILE__)))));
$wp_backitup_dirname = basename($wp_backitup_path);

//build download link
if(glob($wp_backitup_path . "/backups/*.zip")) {
	echo '<ul>';
	foreach (glob($wp_backitup_path . "/backups/*.zip") as $file) {
		$filename = basename($file);
		echo '<li>Download most recent export file: <a href="' .site_url() .'/' .$wp_content_dirname .'/' .'plugins/' .$wp_backitup_dirname. '/backups/' .$filename .'">' .$filename .'</a></li>'; 
	}
	echo '</ul>';
} else {
	echo '<p>No export file available for download. Please create one.</p>';
}
die();