/**
 * WP Backitup Admin Control Panel JavaScripts
 * 
 * @version 1.4.0
 * @since 1.0.1
 */

(function($){
    var namespace = 'wp-backitup';

    //Add View Log Click event to backup page
    wpbackitup_add_viewlog_onclick();

    //Add download backup Click event to backup page
    wpbackitup_add_downloadbackup_onclick();

    /* define logreader variables */
    var wpbackitup_restore_status_reader = {
        action: wpbackitup_get_action_name('restore_status_reader')
    };

    var wpbackitup_backup_status_reader = {
        action: wpbackitup_get_action_name('backup_status_reader')
    };

    //UPLOADS
    try {
        if(typeof wpbackitup_plupload_config !== 'undefined') {
            plupload_init(wpbackitup_plupload_config);
        }
    } catch (err) {
        console.log(err);
    }

    $( "#scheduled-backups-accordian" ).click(function() {

        scheduled_backups=$("#scheduled-backups");
        scheduled_backups_button = $( "#scheduled-backups-accordian");

        if ($(this).is(".fa-angle-double-down")){
            scheduled_backups.fadeIn( "slow" )
            scheduled_backups_button.toggleClass( "fa-angle-double-down", false);
            scheduled_backups_button.toggleClass( "fa-angle-double-up", true);
        } else{
            scheduled_backups_button.toggleClass( "fa-angle-double-down", true);
            scheduled_backups_button.toggleClass( "fa-angle-double-up", false);
            scheduled_backups.fadeOut( "slow" )
        }

    });

    $( "#upload-backups-accordian" ).click(function() {

        upload_backups=$("#wpbackitup-plupload-modal");
        upload_backups_button = $( "#upload-backups-accordian");

        if ($(this).is(".fa-angle-double-down")){
            upload_backups.fadeIn( "slow" )
            upload_backups_button.toggleClass( "fa-angle-double-down", false);
            upload_backups_button.toggleClass( "fa-angle-double-up", true);
        } else{
            upload_backups_button.toggleClass( "fa-angle-double-down", true);
            upload_backups_button.toggleClass( "fa-angle-double-up", false);
            upload_backups.fadeOut( "slow" )
        }

    });

    //binds to onchange event of the upload file input field
    $('#wpbackitup-zip').bind('change', function() {

        //this.files[0].size gets the size of your file.
        var upload_file_size = this.files[0].size;
        var max_file_size = $('#maxfilesize').val();

        //IF Not supported by browser just check on server
        if (upload_file_size == 'undefined' ||
            max_file_size == 'undefined' ||
            upload_file_size == '' ||
            max_file_size =='')
        {
            return;
        }

        if (upload_file_size > max_file_size){
            alert('The backup you have selected exceeds what your host allows you to upload.');
            $("#wpbackitup-zip").val("");
        }
    });



  function wpbackitup_add_viewlog_onclick(){
        $(".viewloglink").click(function(){
            var href = $(this).attr("href");
            $("#backup_name").val(href);
            $("#viewlog").submit();
            return false;
        });
   }

    function wpbackitup_add_downloadbackup_onclick(){
        $(".downloadbackuplink").click(function(){
            var href = $(this).attr("href");
            $("#backup_file").val(href);
            $("#download_backup").submit();
            return false;
        });
    }

  /* get restore status */
  function wpbackitup_get_restore_status() {
    $.post(ajaxurl, wpbackitup_restore_status_reader, function(response) {
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

    /* get backup status */
    function wpbackitup_get_backup_status() {
        $.post(ajaxurl, wpbackitup_backup_status_reader, function(response) {
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

                    $(attributename).find(".status-icon").fadeOut(200);
                    $(attributename).find(".status").fadeIn(1500);

                }

                //Fatal Error
                if ( $(this).html() == -1 ) {

                    $(attributename).find(".status-icon").fadeOut(200);
                    $(attributename).find(".fail").fadeIn(1500);


                    /*  Stop status reader */
                    clearInterval(window.intervalDefine);

                    //Show error status
                    wpbackitup_get_backup_response();
                }

                //Warning
                if ( $(this).html() == -2 ) {

                    $(attributename).find(".status-icon").fadeOut(200);
                    $(attributename).find(".wpbackitup-warning").fadeIn(1500);

                }

                //success
                if ( $(this).html() == 99 ) {

                    /* If status returns 1, display 'Done' or show detailed message */
                    $(attributename).find(".status-icon").fadeOut(200);
                    $(attributename).find(".status").fadeIn(1500);

                    /*  Stop statusreader */
                    clearInterval(window.intervalDefine);

                    //Show error status
                    wpbackitup_get_backup_response();

                }

            });
        });
    }

    //global to track backup response.
    var backup_response=false;
    /* define backup response_reader function */
    function wpbackitup_get_backup_response() {
    //This function is required because of 504 gateway timeouts

        var jqxhr = $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {action: wpbackitup_get_action_name('backup_response_reader')},
            dataType: "json"
        });

        jqxhr.always(function(jsonData, textStatus, errorThrown) {
            console.log("Backup Response:" + JSON.stringify(jsonData));

            if (typeof  jsonData.backupStatus !== 'undefined' && typeof  jsonData.backupMessage !== 'undefined')
            {
                console.log("JSON Backup Status:" + jsonData.backupStatus);
                console.log("JSON Backup Message:" + jsonData.backupMessage);

                //If response aleady received
                if (backup_response) return;
                backup_response=true;

                switch (jsonData.backupStatus) {
                    case 'success':
                        console.log("JSON success response received.");
                        //fade out all of the spinners
                        $('.status-icon').fadeOut(200);
                        $("#backup-button").removeAttr("disabled"); //enable button

                        $('.isa_success').show;
                        $('.backup-success').fadeIn(1500);

                        wpbackitup_processRow_backup(jsonData);

                        //Are there any warnings?
                        if (typeof  jsonData.backupWarnings !== 'undefined'){
                            var warning = $('.backup-warning');

                            var $warningMessages = jsonData.backupWarnings;
                            $warningMessages.forEach(function(obj) {
                                var  warningMessage = obj.warningMessage;
                                warning.append('<li class="isa_warning">Warning: '+ warningMessage + '</li>');
                            });

                            warning.fadeIn(1500);
                        }

                        break;

                    case 'error':
                        console.log("JSON error response received.");

                        var msg="(JS997) Unexpected error";
                        if (typeof  jsonData.backupMessage !== 'undefined'){
                            msg= jsonData.backupMessage;
                        }
                        var status_message='Error: &nbsp;' + msg;

                        var backup_error= $('.backup-error');
                        backup_error.html(status_message);
                        backup_error.addClass("isa_error");
                        backup_error.fadeIn(1500);

                        //fade out all of the spinners
                        $('.status-icon').fadeOut(200);
                        $("#backup-button").removeAttr("disabled"); //enable button

                        break;

                    default:
                        console.log("Unexpected JSON response status received.");

                        var msg="(JS998) Unexpected error";
                        if (typeof  jsonData.backupMessage !== 'undefined'){
                            msg= jsonData.backupMessage;
                        }
                        var status_message='Error(JS998) : &nbsp;' + msg;

                        var unexpected_error= $('.backup-error');
                        unexpected_error.html(status_message);
                        unexpected_error.addClass("isa_error");
                        unexpected_error.fadeIn(1500);

                        //fade out all of the spinners
                        $('.status-icon').fadeOut(200);
                        $("#backup-button").removeAttr("disabled"); //enable button

                        break;

                }

            } else { //Didnt get any json back
                console.log("NON JSON response received.");
                console.log("Backup Response:" + errorThrown);
                status_message='(JS999) An unexpected error has occurred: &nbsp;';
                status_message+='</br>Response: &nbsp;' + JSON.stringify(jsonData);
                status_message+='</br>Status: &nbsp;' + textStatus;
                status_message+='</br>Error: &nbsp;' +  JSON.stringify(errorThrown);

                $('.backup-status').hide();

                var unexpected_error= $('.backup-error');
                unexpected_error.html(status_message);
                unexpected_error.addClass("isa_error");
                unexpected_error.show();

                $('.status-icon').fadeOut(200);
            }
        });
    }

    //Save Schedule CLICK
    $("#wp-backitup-notification-close").click(function() {
        wpbackitup_dismiss_message();
    });


    //Save Schedule CLICK
    $("#wp-backitup-save_schedule_form").submit(function() {

        var formData = new FormData();
        formData.append('action', wpbackitup_get_action_name('update-schedule'));
        formData.append('_wpnonce', $('#wp-backitup_nonce-update-schedule').val());
        formData.append('_wp_http_referer',$("[name='_wp_http_referer']").val());

        var days_selected = [];
        $.each($("input[name='dow']:checked"), function(){
            days_selected.push($(this).val());
        });

        formData.append('days_selected', days_selected);

        jQuery.ajax({
            url: ajaxurl,
            type: 'POST',
            cache: false,
            contentType: false,
            processData: false,
            dataType: "json",
            data: formData,

            success: function(data, textStatus, jqXHR){
                response=data.message;
                console.log("Success:" + response);

                //Turn on the notification bar
                switch (response)
                {
                case 'success':
                    wpbackitup_show_success_message("Scheduled has been saved.");
                    break;
                case 'error':
                    wpbackitup_show_error_message("Scheduled was not saved.");
                    break;
                default:

                }

            },
            error: function(jqXHR, textStatus, errorThrown){
                console.log("Error." + textStatus +':' +errorThrown);
            },
            complete: function(jqXHR, textStatus){
                console.log("Complete");
            }
        });

        return false;

    });

  // BACKUP button click
  $(".backup-button").click(function(e) {
    e.preventDefault();

    $("#backup-button").attr('disabled', 'disabled'); //Disable button

      var jqxhr = $.ajax({
          url: ajaxurl,
          type: 'POST',
          data: {action: wpbackitup_get_action_name('backup')},
          cache: false,
          dataType: "json",

      beforeSend: function(jqXHR, settings) {
          console.log("BeforeSend:Nothing to report.");
          wpbackitup_show_backup();
      }
    });

      //Fetch the JSON response file if it exists
      jqxhr.always(function(data, textStatus, errorThrown) {
          console.log("Backup Button Click - Always");
          console.log(data.message);//backup queued?
      });
  });

    function wpbackitup_show_backup(){
        /* display processing icon */
        $('.backup-icon').css('visibility', 'visible');
        $('.backup-icon').show();

        /* hide default message */
        $('.backup-success').hide();
        $('.default-status').hide();
        $('.backup-error').hide();

        /* hide the status just incase this is the second run */
        $("ul.backup-status").children().children().hide();
        $(".backup-errors").children().children().hide();
        $(".backup-success").children().children().hide();

        /* show backup status, backup errors */
        $('.backup-status').show();
        backup_response=false;
        window.intervalDefine = setInterval(wpbackitup_get_backup_status, 5000);
    }


    //RESTORE button click
    $('#datatable').on('click', 'a.restoreRow', function(e) {
        e.preventDefault();

        if (confirm('Are you sure you want to restore your site?'))
        {
            var filename = this.title;
            var row = this.id.replace('restoreRow', 'row');
            userid = $('input[name=user_id]').val();

            var jqxhr = $.ajax({
                url: ajaxurl,
                type: 'post',
                data: {action: wpbackitup_get_action_name('restore'), selected_file: filename,user_id: userid},
                cache: false,
                dataType: "json",

                //success: function(response) {
                //   /* Return PHP messages, used for development */
                //    $("#php").html(response);
                //
                //    //clearInterval(window.intervalDefine);
                //     var data = $.parseJSON(response);
                //
                //},
                beforeSend: function () {
                    console.log("BeforeSend:Nothing to report.");
                    wpbackitup_show_restore();
                }
            });

            //Fetch the JSON response file if it exists
            jqxhr.always(function(data, textStatus, errorThrown) {
                console.log("Restore Button Click - Always");
                //console.log("Response:" + data);
            });
        }
    });


    function wpbackitup_show_restore(){
        /* display processing icon */
        $('.restore-icon').css('visibility', 'visible');

        /* hide default message, backup status and backup errors */
        $('.default-status, .upload-status').hide();

        $("ul.restore-status").children().children().hide();
        $(".restore-errors").children().children().hide();
        $(".restore-success").children().children().hide();

        /* show restore status messages */
        $('.restore-status, .restore-errors, .restore-success').show();
        $('.preparing-icon').css('visibility', 'visible');
        $('.preparing').find(".status-icon").fadeIn(1500);

        window.intervalDefine = setInterval(wpbackitup_get_restore_status, 5000);
    }

  // DELETE file action
  $('#datatable').on('click', 'a.deleteRow', function(e) {

    e.preventDefault();
    if (confirm('Are you sure ?'))
    {
      var filename = this.title;
      var row = this.id.replace('deleteRow', 'row');
      $.ajax({
        url: ajaxurl,
        type: 'post',
        data: {action: wpbackitup_get_action_name('delete_file'), filed: filename},
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

  //UPLOADS
  //http://www.plupload.com/example_events.php
  function plupload_init(plupload_config) {

        var uploader = new plupload.Uploader(plupload_config);
        uploader.init();

        //File Added event
        uploader.bind('FilesAdded', function(up, files){
            plupload.each(files, function(file){
                //add some file name validation here?
                $('#filelist').append(
                    '<div id="media-item-' + file.id + '" class="media-item child-of-0">' +
                    '<img class="pinkynail" alt="" src="' + site_url + '/wp-includes/images/media/archive.png">' +
                    '<div class="filename new" id="' + file.id + '">' +
                    file.name + ' (<span>' + plupload.formatSize(0) + '</span> of ' + plupload.formatSize(file.size) + ') ' +
                    '<div class="progress" style="width: 0%;"></div></div></div>');
            });

            up.refresh();
            up.start();
        });

        //File Progress Event
        uploader.bind('UploadProgress', function(up, file) {
            $('#' + file.id + " .progress").width((file.percent *.15 )+ "%");
            $('#' + file.id + " span").html(plupload.formatSize(parseInt(file.size * file.percent / 100)));
        });

      //Chunked upload
      uploader.bind(' ChunkUploaded', function(up, file,info) {
          console.log('Chunk Uploaded:');
          console.log(info);

          if (is_plupload_error(info,up,file)){
              console.log('chunk error');
          }

      });

        //Error Event
        uploader.bind('Error', function(up, error) {
            console.log('PlUpload Error:');
            console.log(error);

            var error_div = "error-item-"+ error.file.id;

            //If error div NOT exists then add it
            //Chunk and file uploaded both will call this routine
            if (! $('#'+ error_div).length){
                $('#filelist').append(
                    '<div class="error-div error" id="' + error_div + '" >' +
                    '<strong>' + error.file.name + ' has failed to upload due to error:&nbsp;</strong> <span>' + error.message + '</span> ' +
                    '</div>'
                );
            }


        });


        // a file was uploaded
        uploader.bind('FileUploaded', function(up, file, response) {
            console.log('File Uploaded');
            console.log(response);


            if (is_plupload_error(response,up,file)){
                console.log('uploaded error')
            }else{
                console.log('uploaded success')
                $('#' + file.id + " .progress").css("background-color", "green");
            }

    });
  }

  function is_plupload_error(response,uploader,file){
      if (response.status == '200') {
          try {
              response_json = jQuery.parseJSON(response.response);

              if (response_json.error) {
                  plupload_error (uploader,file, 100, response_json.error)
                  return true;
              }

              //success
              return false;


          } catch (err) {
              console.log('Unexpected JSON Error' + err);
              plupload_error (uploader,file, 998, response.response)
              return true;
          }

      } else {
          alert('Unknown server response status: '+response.code);
          console.log(response);
          plupload_error (uploader,file, 999, response.response)
          return true;
      }

  }
  function plupload_error (uploader,file, code, error_message){

          file.status = plupload.FAILED;
          uploader.trigger("Error", {
              code: code,
              message: error_message,
              file: file
          });

          //remove upload from list
          $('#media-item-' + file.id).hide();
  }

  function wpbackitup_processRow_backup(data)
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
    if (typeof data !== 'undefined')
    {
      //var restoreColumn = '<td><a href="#" title="' + data.backupFile + '" class="restoreRow" id="restoreRow' + cur_row + '">Restore</a></td>\n';

      var viewColumn = '<td>&nbsp;</td>\n';
      if (typeof data.logFileExists !== 'undefined' && data.logFileExists==true) {
          viewColumn = '<td><a class="viewloglink" href="' + data.backupName + '">View Log</a></td>\n';
      }

      var newRow =
        '<tr ' + css_class + ' id="row' + cur_row + '">\n\
          <td>New Backup!</td>\n\
          <td><a href="#TB_inline?width=600&height=550&inlineId=new_backup" class="thickbox" title="' + data.backupName + '">Download</a></td>\n';
        newRow +=viewColumn;
        newRow +='<td><a href="#" title="' + data.backupName + '" class="deleteRow" id="deleteRow' + cur_row + '">Delete</a></td>\n';
        newRow +='</tr>';

      if ($('#nofiles'))
        $('#nofiles').remove();

      var total_rows = $('#datatable tr').length;
      $('#datatable').prepend(newRow);
      $('#datatable tr:first').hide().show('slow'); // just an animation to show newly added row
      
      //if(total_rows >= data.backupRetained)
      //  $('#datatable tr:last').hide();

        wpbackitup_add_viewlog_onclick();

        wpbackitup_add_downloadbackup_onclick();

    }
  }

    function wpbackitup_processRow_restore(data)
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
            var newRow =
                '<tr ' + css_class + ' id="row' + cur_row + '">\n\
          <td>Uploaded Backup<i class="fa fa-long-arrow-right"></i>' + data.file +'</td>\n\
          <td><a href="' + data.zip_link + '">Download</a></td>\n\
          <td><a href="#" title="' + data.file + '" class="deleteRow" id="deleteRow' + cur_row + '">Delete</a></td>\n\
          <td><a href="#" title="' + data.file + '" class="restoreRow" id="restoreRow' + cur_row + '">Restore</a></td>\n\
         </tr>';

            if ($('#nofiles'))
                $('#nofiles').remove();

            var total_rows = $('#datatable tr').length;
            $('#datatable').prepend(newRow);
            $('#datatable tr:first').hide().show('slow'); // just an animation to show newly added row

            if(total_rows >= data.retained)
                $('#datatable tr:last').hide();
        }
    }


    function wpbackitup_get_action_name(action) {
        return namespace + '_' + action;
    }

    function wpbackitup_dismiss_message(){
        notification_bar = $( "#wp-backitup-notification-parent");
        notification_bar.fadeOut( "slow" )
    }

    function wpbackitup_show_success_message(message){
        notification_bar_message = $( "#wp-backitup-notification-message");
        notification_bar_message.html("<p>" + message + "</p>");

        notification_bar = $( "#wp-backitup-notification-parent");
        notification_bar.toggleClass("error",false);
        notification_bar.toggleClass("updated",true);

        notification_bar.show();
        $('html, body').animate({ scrollTop: 0 }, 'slow');
    }

    function wpbackitup_show_error_message(message){
        notification_bar_message = $( "#wp-backitup-notification-message");
        notification_bar_message.html("<p>" + message + "</p>");

        notification_bar = $( "#wp-backitup-notification-parent");
        notification_bar.toggleClass("updated",false);
        notification_bar.toggleClass("error",true);

        notification_bar.show();
        $('html, body').animate({ scrollTop: 0 }, 'slow');
    }

    //**TEST METHODS**//

    //wpbackitup_show_restore();
    //wpbackitup_show_backup();

})(jQuery);