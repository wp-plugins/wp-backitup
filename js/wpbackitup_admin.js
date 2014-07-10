/**
 * WP Backitup Admin Control Panel JavaScripts
 * 
 * @version 1.4.0
 * @since 1.0.1
 */

(function($){
    var namespace = 'wp-backitup';

    //Add View Log Click event to backup page
    add_viewlog_onclick();

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

    /* define logreader variables */
    var response_reader = {
        action: get_action_name('response_reader')
    };

      /* define logreader variables */
      var status_reader = {
        action: get_action_name('status_reader')
      };

  function add_viewlog_onclick(){
        $(".viewloglink").click(function(){
            var href = $(this).attr("href");
            $("#viewlog_log").val(href);
            $("#viewlog").submit();
            return false;
        });
   }
  /* define display status function */
  function display_status() {    
    $.post(ajaxurl, status_reader, function(response) {
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

    /* define backup response_reader function */
    function get_backup_response() {
    //This function is required because of 504 gateway timeouts

        var jqxhr = $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {action: get_action_name('response_reader')},
            dataType: "json"
        });

        jqxhr.always(function(jsonData, textStatus, errorThrown) {
            console.log("Backup Response:" + JSON.stringify(errorThrown));
            console.log("Backup Response text status:" + textStatus);

            if (jsonData) {
                if (jsonData.message=='success') {
                    console.log("JSON response received.");
                    processRow_backup(jsonData);
                    $('.backup-success').show();

                } else { //Error
                    console.log("JSON error response received.");
                    status_message='An unexpected error has occurred: &nbsp;' + jsonData.message;

                    //$('.backup-status').hide();

                    var unexpected_error= $('.backup-unexpected-error');
                    unexpected_error.html(status_message);
                    unexpected_error.addClass("isa_error");
                    unexpected_error.show();

                }

            } else { //Didnt get any json back
                console.log("NON JSON response received.");
                status_message='An unexpected error has occurred: &nbsp;' + textStatus + ':' + JSON.stringify(errorThrown);

                $('.backup-status').hide();

                var unexpected_error= $('.backup-unexpected-error');
                unexpected_error.html(status_message);
                unexpected_error.addClass("isa_error");
                unexpected_error.show();
            }
        });
    }

  /*BACKUP button click */
  $(".backup-button").click(function(e) {
    e.preventDefault();

    $("#backup-button").attr('disabled', 'disabled'); //Disable button

      var jqxhr = $.ajax({
          url: ajaxurl,
          type: 'POST',
          data: {action: get_action_name('backup')},
          cache: false,
          dataType: "json",

      beforeSend: function(jqXHR, settings) {
          console.log("BeforeSend:Nothing to report.");

          /* display processing icon */
          $('.backup-icon').css('visibility', 'visible');
          $('.backup-icon').show();

          /* hide default message, restore status and restore errors */
          $('.backup-success').hide();
          $('.default-status').hide();
          $('.backup-unexpected-error').hide();

          /* hide the status just incase this is the second run */
          $("ul.backup-status").children().children().hide();
          $(".backup-errors").children().children().hide();
          $(".backup-success").children().children().hide();

          /* show backup status, backup errors */
          $('.backup-status').show();
          window.intervalDefine = setInterval(display_status, 3000);
      }
    });

      //Fetch the JSON response file if it exists
      jqxhr.always(function(data, textStatus, errorThrown) {
          console.log("Backup Button Click - Always");
          clearInterval(window.intervalDefine);
          display_status(); //Fetch status one last time manually
          get_backup_response(); //fetch the response too
          $('.backup-icon').fadeOut(1000);
          $("#backup-button").removeAttr("disabled"); //enable button
      });

  });

    /* RESTORE button click */
    $('#datatable').on('click', 'a.restoreRow', function(e) {
        e.preventDefault();

        if (confirm('Are you sure you want to restore your site?'))
        {

            var filename = this.title;
            var row = this.id.replace('restoreRow', 'row');
            userid = $('input[name=user_id]').val();

            $.ajax({
                url: ajaxurl,
                type: 'post',
                data: {action: get_action_name('restore'), selected_file: filename,user_id: userid},
                success: function(response) {
                   /* Return PHP messages, used for development */
                    $("#php").html(response);

                    //clearInterval(window.intervalDefine);
                     var data = $.parseJSON(response);

                },
                beforeSend: function () {
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

                    window.intervalDefine = setInterval(display_status, 3000);
                }
            });
        }
    });

    /*Upload form button*/
    $("#upload-form").submit(function() {

        //e.preventDefault();

        //CHECK ERRORS ON USER SIDE, IF TRUE, END OPERATIONS.
        if (upload_errors()){
            return false;
        }

        var formData = new FormData();
        jQuery.each($('#wpbackitup-zip')[0].files, function(i, file) {
            formData.append('uploadFile-'+i, file);
        });
        formData.append('action', get_action_name('upload'));
        formData.append('_wpnonce', $('#_wpnonce').val());
        formData.append('_wp_http_referer',$("[name='_wp_http_referer']").val());

        jQuery.ajax({
            url: ajaxurl,
            type: 'POST',
            cache: false,
            contentType: false,
            processData: false,
            dataType: "json",

            //MODIFIED - From ajaxData to formData
            data: formData,

            beforeSend: function(jqXHR, settings){
                //console.log("Haven't entered server side yet.");
                /* display processing icon */
                $('.upload-icon').css('visibility', 'visible');

                /* hide default message, backup status and backup errors */
                $('.default-status, .restore-status, .restore-errors').hide();
                $("ul.restore-status").children().children().hide();
                $(".restore-errors").children().children().hide();
                $(".restore-success").children().children().hide();

                /* show restore status messages */
                $('.upload-status').toggle();

                $("#wpbackitup-zip").attr("disabled", "disabled"); //Disable upload
                $("#upload-button").attr("disabled", "disabled"); //Disable upload

            },
            dataFilter: function(data, type){
                //Check the response before sending to success
                //Possible that is isnt json so just forward it to success in a json object
                try {
                    $("#php").html(data);
                    var response = $.parseJSON(data);
                    console.log("JSON string echoed back from server side:" + response);
                    return data;
                } catch (e) {
                    console.log("NON JSON string echoed back from server side:" +  type + ':' + data);
                    var rtnData = new Object();
                    rtnData.success = "";
                    rtnData.error = data;
                    return JSON.stringify(rtnData)
                }


            },
            success: function(data, textStatus, jqXHR){
                console.log("Back from server-side:" + data);
                //Checking errors that may have been caught on the server side that
                // normally wouldn't display in the error Ajax function.

                if (data.msg == 'success')
                {
                    status_message=data.file + ' file was uploaded successfully...';
                    processRow_restore(data);
                    $('.upload-status').addClass("isa_success");
                }else{
                    status_message='Error: &nbsp;' + data.error;
                    $('.upload-status').addClass("isa_error");
                }

                $('.upload-icon').fadeOut(1000);
                $('.upload-status').show();
                $('.upload-status').html(status_message);

            },
            error: function(jqXHR, textStatus, errorThrown){
                console.log("A JS error has occurred." + textStatus +':' +errorThrown);
            },
            complete: function(jqXHR, textStatus){
                console.log("Ajax is finished.");
            }
        });

        return false;
    });


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
        data: {action: get_action_name('delete_file'), filed: filename},
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


  function processRow_backup(data)
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

      var viewColumn = '<td>&nbsp;</td>\n';
      if (typeof data.log_link !== 'undefined') {
          viewColumn = '<td><a class="viewloglink" href="' + data.log_link + '">View Log</a></td>\n';
      }

      var newRow =
        '<tr ' + css_class + ' id="row' + cur_row + '">\n\
          <td>New Backup!</td>\n\
          <td><i class="fa fa-long-arrow-right"></i>' + data.file +'</td>\n\
          <td><a href="' + data.zip_link + '">Download</a></td>\n';
        newRow +=viewColumn;
        newRow +='<td><a href="#" title="' + data.file + '" class="deleteRow" id="deleteRow' + cur_row + '">Delete</a></td>\n';
        newRow +='</tr>';

      if ($('#nofiles'))
        $('#nofiles').remove();

      var total_rows = $('#datatable tr').length;
      $('#datatable').prepend(newRow);
      $('#datatable tr:first').hide().show('slow'); // just an animation to show newly added row
      
      if(total_rows >= data.retained)
        $('#datatable tr:last').hide();

        add_viewlog_onclick();

    }
  }

    function processRow_restore(data)
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

    function upload_errors()
    {
        if ($('#wpbackitup-zip').val() == '')
        {
            alert('No file(s) selected. Please choose a backup file to upload.');
            return true;
        }
        if ($('#wpbackitup-zip').val() != '')
        {
            var ext = $('#wpbackitup-zip').val().split('.').pop().toLowerCase();
            if($.inArray(ext, ['zip']) == -1)
            {
                alert('Invalid file type. Please choose a ZIP file to upload.');
                return true;
            }
        }
        return false;
    }

    function get_action_name(action) {
        return namespace + '_' + action;
    }

})(jQuery);