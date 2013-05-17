/**
 * WP Backitup Admin Control Panel JavaScripts
 * 
 * @version 1.2.1
 * @since 1.0.1
 */

(function($){
	
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
			var xmlObj = $(response);
                        xmlObj.each(function(){
                            var attributename = "." + $(this).attr('code');
                            $(attributename).find(".currentStatus").html($(this).text());
                            if($(this).attr('code') == "finalinfo" || $(this).attr('code') == "errorMessage")
                            {
                                clearInterval(window.intervalDefine);
                            }
                        });
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
                var htmlvals = '<div class="upload">Uploading file: <span class="currentStatus">Pending</span></div><div class="unzipping">Unzipping Files: <span class="currentStatus">Pending</span></div><div class="validation">Validating Zip File: <span class="currentStatus">Pending</span></div><div class="wpcontent">Replacing WP-CONTENT Directory: <span class="currentStatus">Pending</span></div><div class="database">Restoring Database: <span class="currentStatus">Pending</span></div><div class="infomessage"><span class="currentStatus"></span></div><div class="errorMessage"><span class="currentStatus"></span></div>';
                
		$("#status").html(htmlvals);
                $(".upload").find('.currentStatus').html('In Progress');
		$('.restore-icon').css('visibility','visible');  
		window.intervalDefine = setInterval(display_log, 1000);
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