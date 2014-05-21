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
		beforeSend: function () {

			/* display processing icon */
 		  $('.backup-icon').css('visibility', 'visible');
		  $('.backup-icon').show();

			/* hide default message, restore status and restore errors */
			$('.default-status, .restore-status, .restore-errors, .restore-success').hide();

		  /* hide the status just incase this is the second run */
			$("ul.backup-status").children().children().hide();
			$("ul.backup-errors").children().children().hide();

		  /* show backup status, backup errors */
			$('.backup-status, .backup-errors').show();
		  window.intervalDefine = setInterval(display_log, 3000);

		}
	};

	/* define logreader variables */
	var logreader = {
		action: 'logreader'
	};

  /* define logreader variables */
  var statusreader = {
    action: 'statusreader'
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

            	/* Select correct status */
              if ($(this).html() == 0) {

                /* If status returns 0, display 'Failed' */
                $('.backup-icon').fadeOut(200);
               $(attributename).find(".fail").fadeIn(1500);
               clearInterval(window.intervalDefine);

              } else {

                /* If status returns 1, display 'Done' or show detailed message */
                $(attributename).find(".status").fadeIn(500);

              }

              /*  If is final or error */
              if (attributename == ".finalinfo") {
                  /*  Stop logreader */
                  clearInterval(window.intervalDefine);
              }

          });
		});
	}

  /* define display status function */
  function display_status() {    
    $.post(ajaxurl, statusreader, function(response) {

      /* Get response from log reader */
      var xmlObj = $(response);

           /* For each response */
            xmlObj.each(function() {

              /* Select correct status */
              var attributename = "." + $(this).attr('class');
              var icon_attributename = "." + $(this).attr('class') + '-icon';


              //Hide all
              if ( $(this).html() == 0 ) {
  
                $(attributename).find(".status").hide();
                $(attributename).find(".status-icon").hide();                    

              } 

              //Processing
              if ( $(this).html() == 1 ) {
  
                $(icon_attributename).css('visibility', 'visible');
                $(attributename).find(".status").fadeOut(200);
                $(attributename).find(".status-icon").fadeIn(1500); 

              } 

              //Done
              if ( $(this).html() == 2 ) {
                
                /* If status returns 1, display 'Done' or show detailed message */
                $(attributename).find(".status-icon").fadeOut(200);
                $(attributename).find(".status").fadeIn(1500);
                
              }

              //Fatal Error
              if ( $(this).html() == -1 ) {
  
                $(attributename).find(".status-icon").fadeOut(200);
                $(attributename).find(".fail").fadeIn(1500); 
                $(attributename).find(".isa_error").fadeIn(1500);

                 /*  Stop status reader */
                 clearInterval(window.intervalDefine);                 

              } 

              //Warning
              if ( $(this).html() == -2 ) {
  
                $(attributename).find(".isa_warning").fadeIn(1500);

              } 

              //success
              if ( $(this).html() == 99 ) {

                $(attributename).find(".isa_success").fadeIn(1500);

                /*  Stop statusreader */
                clearInterval(window.intervalDefine);

              } 

            });
    });
  }

 /* execute backup on button click */
  $(".backup-button").click(function(e) {
    e.preventDefault();

    $("#backup-button").attr('disabled', 'disabled'); //Disable button 
    
    $.post(ajaxurl, backup, function(response) {

      /* Return PHP messages, used for development */
      $("#php").html(response);

      //clearInterval(window.intervalDefine);
      var data = $.parseJSON(response);
      processRow(data);
      /* fade out status icon */
      $('.backup-icon').fadeOut(1000);
      $("#backup-button").removeAttr("disabled"); //enable button 
      
    });
  });


  //Restore file action
  $('#datatable').on('click', 'a.restoreRow', function(e) {
    e.preventDefault();
    if (confirm('Are you sure you want to restore your site?'))
    {
      var filename = this.title;
      var row = this.id.replace('restoreRow', 'row');
      $('#is_selected').val(1);
      $('#selected_file').val(filename);

      //Submit the restore
      $('#restore-form').submit();
    }
  });

  // Delete file action
  $('#datatable').on('click', 'a.deleteRow', function(e) {

    e.preventDefault();
    if (confirm('Are you sure ?'))
    {
      var filename = this.title;
      var row = this.id.replace('deleteRow', 'row');
      $.ajax({
        url: ajaxurl,
        type: 'post',
        data: {action: "deletefile", filed: filename},
        success: function(data) {
          if (data === 'deleted')
          {
            $('#' + row).remove();
          }
          else
          {
            alert('This file cannot be delete!');
          }
        }
      });
    }
    else
    {
      return;
    }
  });

  function processRow(data)
  {
    // decide class of row to be inserted dynamically
    var css_class;
    css_class = '';

    if (!$('#datatable tr').first().hasClass('alternate'))
      css_class = 'class="alternate"';
    // decided class of row to be inserted dynamically

    // build id of the row to be inserted dynamically
    var  cur_row = ($('#datatable tr:last')[0].id.replace('row', ''));
    cur_row++;

    // built id of the row to be inserted dynamically
    if (data != undefined)
    {
      var restoreColumn = '<td><a href="#" title="' + data.file + '" class="restoreRow" id="restoreRow' + cur_row + '">Restore</a></td>\n';
      var newRow = '<tr ' + css_class + ' id="row' + cur_row + '">\n\
          <td>' + data.file +'</td>\n\
          <td><a href="' + data.link + '">Download</a></td>\n\
          <td><a href="#" title="' + data.file + '" class="deleteRow" id="deleteRow' + cur_row + '">Delete</a></td>\n';
      
      if (true==data.license)  
        newRow +=restoreColumn;

      newRow +='</tr>';

      if ($('#nofiles'))
        $('#nofiles').remove();

      $('#datatable').prepend(newRow);
      $('#datatable tr:last').hide().show('slow'); // just an animation to show newly added row
    }
  }

  /* execute restore on button click */
  $("#restore-form").submit(function() {
    /* display processing icon */
    $('.restore-icon').css('visibility', 'visible');

    /* hide default message, backup status and backup errors */
    $('.default-status, .backup-status, .backup-errors').hide();

    /* show restore status messages */
    $('.restore-status, .restore-errors, .restore-success').show();
    $('.preparing-icon').css('visibility', 'visible');
    $('.preparing').find(".status-icon").fadeIn(1500); 


    window.intervalDefine = setInterval(display_status, 3000);
    $("#restore-form").attr("target", "upload_target");
    $("#upload_target").load(function() {
      upload_file();
    });
  });

  /* define upload function */
  function upload_file() {
    
    /* process upload */
    var ret = frames['upload_target'].document.getElementsByTagName("body")[0].innerHTML;

    /* Return PHP messages, used for development */
    $("#php").html(ret);
    clearInterval(display_log);
    $('.upload-icon').fadeOut(1000);
    return ret;
  }


  /*Upload form button*/
  $("#upload-form").submit(function() {

    /* display processing icon */
    $('.upload-icon').css('visibility', 'visible');

    /* hide default message, backup status and backup errors */
    $('.default-status, .backup-status, .backup-errors').hide();

    /* show restore status messages */
    $('.upload-status, .upload-errors').toggle();

    window.intervalDefine = setInterval(display_log, 3000);
    $("#upload-form").attr("target", "upload_target");
    $("#upload_target").load(function() {
      var response = upload_file();

      var status_message;
      try{
        var data = $.parseJSON(response);
        processRow(data);
        status_message=data.file + ' was uploaded successfully...';
      }
      catch(err)
      {
        status_message=response;
      }
      
      //Update the status and disable the button
      $("#wpbackitup-zip").attr("disabled", "disabled"); //Disable upload    
      $("#upload-button").attr("disabled", "disabled"); //Disable upload      
      $('.upload-status').show();
      $('.upload-status').html(status_message);

    });
  });

})(jQuery);