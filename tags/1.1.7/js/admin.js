/**
 * WP Backitup Admin Control Panel JavaScripts
 * 
 * @version 1.0.9
 * @since 1.0.1
 */

(function($){
	/*//Validate file upload
	document.forms[0].addEventListener('restore', function( evt ) {
	    var upload = document.getElementById('wpbackitup-zip').files[0];
	    if(upload && upload.size < 1) { // 10 MB (this size is in bytes)
	        return true;        
	    } else {
	        $("#status").html('<ul><li class="error">Your upload is too large. Please contact your server administrator to increase your upload limit.</li></ul>'); 
	        return false;
	    }
	}, false);*/

	//define backup variables
	var backup = {
		action: 'backup',
		beforeSend: function() {
			$('.backup-icon').css('visibility','visible');
			$("#status").html();
			setInterval(display_log, 1000);
		}
	};
	//define download variables
	var download = {
		action: 'download'
	};
	//define logreader variables
	var logreader = {
		action: 'logreader'
	};
	//define logreader function
	function display_log() {		
		$.post(ajaxurl, logreader, function(response) {
			$("#status").html(response);
		});
	}
	//define download function
	function download_link() {
		$.post(ajaxurl, download, function(response) {
			$("#download-link").html(response);
		});
	}

	//execute download (on page load/refresh)
	download_link();
	
	//execute backup on button click
    $(".backup-button").click( function() {
        $.post(ajaxurl, backup, function(response) {
			download_link(); 
			clearInterval(display_log); 
			$('.backup-icon').fadeOut(1000); 
			$("#php").html(response); //Return PHP messages, used for development
        });   
    })
    
    //execute restore on button click
	$("#restore-form").submit(function() {
                var maximum = $("#maximum").val();
                var fil = document.getElementById("wpbackitup-zip"); 
                var sizes  = fil.files[0].size; 
                var sizesd = sizes/(1024*1024);
                if(sizesd > maximum)
                {
                    $("#status").html("<span style='color: red'>File size exceeds maxium upload size.</span>");
                    return false; 
                }
                
		$("#status").html();
		$('.restore-icon').css('visibility','visible');  
		setInterval(display_log, 1000);
		$("#restore-form").attr("target","upload_target"); 
		$("#upload_target").load(function (){
			importRestore(); 
		});
	});
	
	//define importRestore function
	function importRestore() {
		var ret = frames['upload_target'].document.getElementsByTagName("body")[0].innerHTML; //process upload
		$("#php").html(ret); //Return PHP messages, used for development
		clearInterval(display_log); 
		$('.restore-icon').fadeOut(1000); 
	}
})(jQuery);