<?php if (!defined ('ABSPATH')) die('No direct access allowed');

    $page_title = $this->friendly_name . ' Restore';
    $namespace = $this->namespace;

    //Path Variables
    $backup_folder_root = WPBACKITUP__BACKUP_PATH .'/';

?>  

<script type="text/javascript">var __namespace = '<?php echo $namespace; ?>';</script>
<div class="wrap">
  <div id="wp-backitup-icon" class="icon32"><img src="<?php echo plugin_dir_url(dirname(__FILE__)); ?>images/icon32.png" alt="WP Backitup Icon" height="32" width="32" /></div>
  <h2><?php echo $page_title; ?></h2>
  <div id="content">

    <!--Available Backups section-->
    <div class="widget">
      <h3><i class="fa fa-cloud-download"></i> <?php _e('Available Backups', $namespace); ?></h3>
      <table class="widefat" id="datatable">
        <?php
        //Get Zip File List
        $zipFileList = glob($backup_folder_root . "*.zip");

        //Sort by Date Time			
        usort($zipFileList, create_function('$a,$b', 'return filemtime($b) - filemtime($a);'));

        if (glob($backup_folder_root . "*.zip"))
        {
          $i = 0;
          foreach ($zipFileList as $file)
          {
            $filename = basename($file);
            $fileDateTime = get_date_from_gmt(date('Y-m-d H:i:s', filemtime($file)), 'Y-m-d g:i a'); //Local Date Time
            $class = $i % 2 == 0 ? 'class="alternate"' : '';
            ?>
            <tr <?php echo $class ?> id="row<?php echo $i; ?>">
              <td><?php echo $filename ?></td>
              <td><a href="<?php echo WPBACKITUP__BACKUP_URL ?>/<?php echo $filename; ?>">Download</a></td>
              <td><a href="#" title="<?php echo $filename; ?>" class="deleteRow" id="deleteRow<?php echo $i; ?>">Delete</a></td>
              <?php
              if ($this->license_active())
              {
                echo '<td><a href="#" title="' . $filename . '" class="restoreRow" id="restoreRow' . $i . '">Restore</a></td>';
              }
              ?>
            </tr>
            <?php
            $i++;
          }
        }
        else
        {
          echo '<tr id="nofiles"><td colspan="3">No export file available for download. Please create one.</td></tr>';
        }
        ?>
      </table>  

      <form id="restore-form" method="post" action="admin-post.php">
        <?php global $current_user; ?>
        <input type="hidden" name="user_id" value="<?php echo $current_user->ID; ?>" />
        <input type="hidden" name="is_selected" id="is_selected" value="0" />
        <input type="hidden" name="selected_file" id="selected_file" value="" />
      </form>

      <?php
      //Display restore note for lite customers
      if (!$this->license_active())
      {
        echo '<p>* The automated restore feature is only available to licensed customers.  Please visit <a href="' . WPBACKITUP__SITE_URL .'" target="_blank">'. WPBACKITUP__SITE_URL .'</a> to get license WP BackItUp risk free for 30 days.</p>';
      }
      ?>
    </div>		

    <!--Disable upload form if the user has not activated-->
    <?php
    if ($this->license_active())
    {
      ?>
      <div class="widget">
        <h3><i class="fa fa-upload"></i> <?php _e('Upload', $namespace); ?></h3>
        <iframe id="upload_target" name="upload_target" src="">
        </iframe>
        <p><b><?php _e('Upload a WP BackItUp zip file to add it to your list of available backups.', $namespace); ?></b></p>
        <?php
        $max_upload = (int) (ini_get('upload_max_filesize'));
        $max_post = (int) (ini_get('post_max_size'));
        $memory_limit = (int) (ini_get('memory_limit'));
        $upload_mb = min($max_upload, $max_post, $memory_limit);
        $upload_bytes = $upload_mb * 1048576;
        ?>
        <p>
        <?php _e('The maximum file size your hosting provider allows you to upload is ', $namespace);?>
        <span style="color:green"><?php echo $upload_mb . 'MB.'; ?></span>
        </p>
        <form id="upload-form" name="upload-form" method="post" enctype="multipart/form-data">
          <?php wp_nonce_field($namespace . "-upload-file"); ?>
          <input type="hidden" id="maxfilesize" value="<?php echo $upload_bytes; ?>"/>
          <p><input name="uploaded-zip" id="wpbackitup-zip" type="file" /></p>
          <p><input type="submit" class="restore-button button-primary" name="Upload" id="upload-button" value="<?php _e("Upload", $namespace) ?>" /><img class="upload-icon status-icon" src="<?php echo WPBACKITUP__PLUGIN_URL . "/images/loader.gif"; ?>" height="16" width="16" /></p>
        </form>
      </div>
<?php } ?>
    <!--End of Upload form-->


    <div id="status" class="widget">
      <h3><i class="fa fa-check-square-o"></i> <?php _e('Status', $namespace); ?></h3>        

      <!--default status message-->
      <ul class="default-status">
        <li><?php _e('Nothing to report', $namespace); ?></li>
      </ul>

      <!--Upload status messages-->
      <ul class="upload-status">
          <li><span class='upload-status'></span></li>
      </ul>

      <!--restore status messages-->
      <ul class="restore-status">
        <li class="preparing"><?php _e('Preparing for restore', $namespace); ?>...<span class='status-icon'><img class="preparing-icon" src="<?php echo WPBACKITUP__PLUGIN_URL . "/images/loader.gif"; ?>" height="16" width="16" /></span><span class='status'><?php _e('Done', $namespace); ?></span><span class='fail error'><?php _e('Failed', $namespace); ?></span></li>
        <li class="unzipping"><?php _e('Unzipping backup file', $namespace); ?>...<span class='status-icon'><img class="unzipping-icon" src="<?php echo WPBACKITUP__PLUGIN_URL . "/images/loader.gif"; ?>" height="16" width="16" /></span><span class='status'><?php _e('Done', $namespace); ?></span><span class='fail error'><?php _e('Failed', $namespace); ?></span></li>
        <li class="validation"><?php _e('Validating backup file', $namespace); ?>...<span class='status-icon'><img class="validation-icon" src="<?php echo WPBACKITUP__PLUGIN_URL . "/images/loader.gif"; ?>" height="16" width="16" /></span><span class='status'><?php _e('Done', $namespace); ?></span><span class='fail error'><?php _e('Failed', $namespace); ?></span></li>
        <li class="restore_point"><?php _e('Creating checkpoint', $namespace); ?>...<span class='status-icon'><img class="restore_point-icon" src="<?php echo WPBACKITUP__PLUGIN_URL . "/images/loader.gif"; ?>" height="16" width="16" /></span><span class='status'><?php _e('Done', $namespace); ?></span><span class='fail error'><?php _e('Failed', $namespace); ?></span></li>
        <li class="database"><?php _e('Restoring database', $namespace); ?>...<span class='status-icon'><img class="database-icon" src="<?php echo WPBACKITUP__PLUGIN_URL . "/images/loader.gif"; ?>" height="16" width="16" /></span><span class='status'><?php _e('Done', $namespace); ?></span><span class='fail error'><?php _e('Failed', $namespace); ?></span></li>
        <li class="wpcontent"><?php _e('Restoring plugins, themes and uploads', $namespace); ?>...<span class='status-icon'><img class="wpcontent-icon" src="<?php echo WPBACKITUP__PLUGIN_URL . "/images/loader.gif"; ?>" height="16" width="16" /></span><span class='status'><?php _e('Done', $namespace); ?></span><span class='fail error'><?php _e('Failed', $namespace); ?></span></li>
        <li class="cleanup"><?php _e('Cleaning up restore files', $namespace); ?>...<span class='status-icon'><img class="cleanup-icon" src="<?php echo WPBACKITUP__PLUGIN_URL . "/images/loader.gif"; ?>" height="16" width="16" /></span><span class='status'><?php _e('Done', $namespace); ?></span><span class='fail error'><?php _e('Failed', $namespace); ?></span></li>
      </ul>
      <p>

        <!--restore error messages-->
      <div class="restore-errors">
        <span class="error201"><div class='isa_error'><?php _e('Error 201: No file selected', $namespace); ?>.</div></span>
        <span class="error202"><div class='isa_error'><?php _e('Error 202: Your file could not be uploaded', $namespace); ?>.</div></span>
        <span class="error203"><div class='isa_error'><?php _e('Error 203: Your backup zip file could not be unzipped', $namespace); ?>.</div></span>
        <span class="error204"><div class='isa_error'><?php _e('Error 204: Your backup zip file appears to be invalid. Please ensure you selected a valid backup zip file', $namespace); ?>.</div></span>
        <span class="error205"><div class='isa_error'><?php _e('Error 205: Cannot create restore point', $namespace); ?>.</div></span>
        <span class="error206"><div class='isa_error'><?php _e('Error 206: Unable to connect to your database', $namespace); ?>.</div></span>
        <span class="error207"><div class='isa_error'><?php _e('Error 207: Unable to get current site URL from database. Please try again', $namespace); ?>.</div></span>
        <span class="error208"><div class='isa_error'><?php _e('Error 208: Unable to get current home URL from database. Please try again', $namespace); ?>.</div></span>
        <span class="error209"><div class='isa_error'><?php _e('Error 209: Unable to get current user ID from database. Please try again', $namespace); ?>.</div></span>
        <span class="error210"><div class='isa_error'><?php _e('Error 210: Unable to get current user password from database. Please try again', $namespace); ?>.</div></span>
        <span class="error211"><div class='isa_error'><?php _e('Error 211: Unable to get current user email from database. Please try again', $namespace); ?>.</div></span>
        <span class="error212"><div class='isa_error'><?php _e('Error 212: Unable to import your database. This may require importing the file manually', $namespace); ?>.</div></span>
        <span class="error213"><div class='isa_warning'><?php _e('Error 213: Unable to update your current site URL value. This may require importing the file manually', $namespace); ?>.</div></span>
        <span class="error214"><div class='isa_warning'><?php _e('Error 214: Unable to update your current home URL value. This may require importing the file manually', $namespace); ?>.</div></span>
        <span class="error215"><div class='isa_warning'><?php _e('Error 215: Unable to update your user information. This may require importing the file manually', $namespace); ?>.</div></span>
        <span class="error216"><div class='isa_error'><?php _e('Error 216: Database not detected in import file', $namespace); ?>.</div></span>
        <span class="error217"><div class='isa_warning'><?php _e('Error 217: Unable to remove existing wp-content directory for import. Please check your CHMOD settings in /wp-content/', $namespace); ?>.</div></span>
        <span class="error218"><div class='isa_error'><?php _e('Error 218: Unable to create new wp-content directory for import. Please check your CHMOD settings in /wp-content/', $namespace); ?>.</div></span>
        <span class="error219"><div class='isa_warning'><?php _e('Error 219: Unable to import wp-content. Please try again', $namespace); ?>.</div></span>
        <span class="error220"><div class='isa_warning'><?php _e('Warning 220: Unable to cleanup import directory. No action is required.', $namespace); ?>.</div></span>
        <span class="error221"><div class='isa_warning'><?php _e('Warning 221: Table prefix value in wp-config.php is different from backup. This MUST be corrected in your wp-config.php file before your site will function', $namespace); ?>.</div></span>
        <span class='error222'><div class='isa_error'><?php _e('Error 222: Unable to create restore folder', $namespace); ?>.</div></span>
        <span class='error223'><div class='isa_error'><?php _e('Error 223: An error occurred during the restore.  We attempted to restore the database to its previous state but were unsuccessful.  Please contact WP BackItUp customer support and do not attempt to perform any further restores', $namespace); ?>.</div></span>
        <span class='error224'><div class='isa_error'><?php _e('Error 224: An error occurred during the restore, however, we have successfully restored your database to the previous state', $namespace); ?>.</div></span>
        <span class='error225'><div class='isa_error'><?php _e('Error 225: Restore option is only available to licensed WP BackItUp users', $namespace); ?>.</div></span>
      </div>

      <!--restore success messages-->
      <div class="restore-success">
        <span class='finalinfo'><div class='isa_success'><?php _e('Restore completed successfully. Please refresh the page and login to the site again (with your current username and password)', $namespace); ?></div></span>
      </div>                

    </div>  

    <!--Debug Widget-->
    <?php if (WP_DEBUG===true) :?>  
      <div class="widget">
        <h3><i class="fa fa-wrench"></i> <?php _e('Debug', $namespace); ?></h3>
        <div id="php"><p>Debugging is turned on in your wp-config.php file and should only be used when troubleshooting issues on your site.</p></div>
      </div>      
    <?php endif; ?>
  </div>

</div>