/**
 * WP Backitup Admin Control Panel JavaScripts
 * 
 * @version 1.4.0
 * @since 1.0.1
 */

(function($){
	/* define backup variables */
	var backup = {
		action: 'backup',
		beforeSend: function() {
			/* display processing icon */
			$('.backup-icon').css('visibility','visible');

			/* hide default message, restore status and restore errors */
			$('.default-status, .restore-status, .restore-errors').hide();

			/* show backup status, backup errors */
			$('.backup-status, .backup-errors').toggle();

		    window.intervalDefine = setInterval(display_log, 1000);
		}
	};

	/* define download variables */
	var download = {
		action: 'download'
	};

	/* define logreader variables */
	var logreader = {
		action: 'logreader'
	};

	/* define logreader function */
	function display_log() {		
		$.post(ajaxurl, logreader, function(response) {

			/* Get response from log reader */
			var xmlObj = $(response);

			/* For each response */
            xmlObj.each(function() {

            	/* Select correct status */
                var attributename = "." + $(this).attr('class');
                
                if ( $(this).html() == 0 ) {
    
                	/* If status returns 0, display 'Failed' */
                	$(attributename).find(".fail").fadeIn(500);

                } else {
                	
                	/* If status returns 1, display 'Done' or show detailed message */
                	$(attributename).find(".status").fadeIn(500);
                	
                }

                /*  If is final or error */
                if($(this).attr('code') == "finalinfo") {

                	/*  Stop logreader */
                    clearInterval(window.intervalDefine);
                }
            });
		});
	}

	/* define download function */
	function download_link() {
		$.post(ajaxurl, download, function(response) {
			$("#download-link").html(response);
		});
	}

	/* execute download (on page load/refresh) */
	download_link();
	
	/* execute backup on button click */
    $(".backup-button").click( function() {
        $.post(ajaxurl, backup, function(response) {
			download_link(); 
			clearInterval(display_log); 
			
			/* fade out status icon */
			$('.backup-icon').fadeOut(1000);
			
			/* Return PHP messages, used for development */
			$("#php").html(response);
        });   
    })
    
    /* execute restore on button click */
	$("#restore-form").submit(function() {
	
		/* display processing icon */
		$('.restore-icon').css('visibility','visible'); 

		/* hide default message, backup status and backup errors */
		$('.default-status, .backup-status, .backup-errors').hide();

		/* show restore status messages */
		$('.restore-status, .restore-errors').toggle();	

		window.intervalDefine = setInterval(display_log, 1000);
		$("#restore-form").attr("target","upload_target"); 
		$("#upload_target").load(function (){
			importRestore(); 
		});
	});
	
	/* define importRestore function */
	function importRestore() {

		/* process upload */
		var ret = frames['upload_target'].document.getElementsByTagName("body")[0].innerHTML; 
		
		/* Return PHP messages, used for development */
		$("#php").html(ret); 
		clearInterval(display_log); 
		$('.restore-icon').fadeOut(1000); 
	}
})(jQuery);