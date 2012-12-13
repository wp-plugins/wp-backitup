jQuery(document).ready(function($) {
	//define backup variables
	var wpBackitupBackup = {
		action: 'wpbackitup_backup',
		beforeSend: function() {
			$('#wp-backitup-backup .status-icon').css('visibility','visible');
			$("#wp-backitup-status").empty();
			setInterval(logreader, 1000); 
		}
	};
	
	//define download variables
	var wpBackitupDownload = {
		action: 'wpbackitup_download'
	};
	
	//define logreader variables
	var wpBackitupLogReader = {
		action: 'wpbackitup_logreader'
	};
	
	//define logreader function
	function logreader() {		
		$.post(ajaxurl, wpBackitupLogReader, function(response) {
			$("#wp-backitup-status").html(response);
		});
	}
	
	//define download function
	function download() {
		$.post(ajaxurl, wpBackitupDownload, function(response) {
			$("#wp-backitup-download-status").html(response);
		});
	}
	
	//execute download (on page load/refresh)
	download();
	
	//execute backup on button click
    $("#wpBackitupBackup").click( function() {
        $.post(ajaxurl, wpBackitupBackup, function(response) {
			download(); //Build download link
			clearInterval(logreader); //Stop checking for status updates
			$('#wp-backitup-backup .status-icon').fadeOut(1000); //Fade process indicator
			$("#wp-backitup-php").html(response); //Return PHP messages, used for development
        });   
    })
});