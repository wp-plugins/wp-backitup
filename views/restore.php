<?php if (!defined ('ABSPATH')) die('No direct access allowed');

/**
 * WP BackItUp  - Restore View
 *
 * @package WP BackItUp
 * @author  Chris Simmons <chris.simmons@wpbackitup.com>
 * @link    http://www.wpbackitup.com
 *
 */

    $page_title = $this->friendly_name . ' Restore';
    $namespace = $this->namespace;

    //Path Variables
    $backup_folder_root = WPBACKITUP__BACKUP_PATH .'/';

    $license_active = $this->license_active();

    //Make sure backup folder exists
    $backup_dir = WPBACKITUP__CONTENT_PATH . '/' . WPBACKITUP__BACKUP_FOLDER;
    $backup_folder_exists=false;
    if( !is_dir($backup_dir) ) {
        if (@mkdir($backup_dir, 0755)){
            $backup_folder_exists=true;
        }
    }else{
        $backup_folder_exists=true;
    }

    //Check restore folder folders
    $restore_dir = WPBACKITUP__CONTENT_PATH . '/' . WPBACKITUP__RESTORE_FOLDER;
    $restore_folder_exists=false;
    if( !is_dir($restore_dir) ) {
        if (@mkdir($restore_dir, 0755)){
            $restore_folder_exists=true;
        }
    }else{
        $restore_folder_exists=true;
    }

    $backup_list = $this->get_backup_list();


  $chunk_size = min(wp_max_upload_size()-1024, 1024*1024*2);
  $plupload_config = array(
      'runtimes' => 'html5,flash,silverlight,html4',
      'browse_button' => 'plupload-browse-button',
      'container' => 'plupload-upload-ui',
      'drop_element' => 'drag-drop-area',
      'file_data_name' => 'async-upload',
      'multiple_queues' => true,
      'max_file_size' => '100Gb',
      'chunk_size' => $chunk_size.'b',
      'url' => admin_url('admin-ajax.php'),
      'filters' => array(array('title' => __('Zip Files'), 'extensions' => 'zip')),
      'multipart' => true,
      'multi_selection' => true,
      'urlstream_upload' => true,
      'multipart_params' => array(
          '_wpnonce' => wp_create_nonce($namespace . '-upload'),
          'action' => 'wp-backitup_plupload_action')
  );

?>

<?php
//Fatal Error - no backup folder
if (!$backup_folder_exists) {
echo '<div class="error"><p><strong>Error: Backup folder does not exist. Please contact ';
            echo($this->get_anchor_with_utm('support','support' ,'restore+error','no+backup+folder'));
            echo ' for assistance.</strong></p></div>';
}

//Fatal Error - no restore folder
if (!$restore_folder_exists) {
    echo '<div class="error"><p><strong>Error: Restore folder does not exist. Please contact ';
    echo($this->get_anchor_with_utm('support','support' ,'restore+error','no+restore+folder'));
    echo ' for assistance.</strong></p></div>';
}
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

        if ($backup_list!=false)
        {
          $i = 0;
          foreach ($backup_list as $file)
          {
            $backup_name = $file["backup_name"];
            $backup_datetime = get_date_from_gmt(date('Y-m-d H:i:s', $file["date_time"]), 'Y-m-d g:i a'); //Local Date Time
            $log_exists    = $file["log_exists"];
            $class = $i % 2 == 0 ? 'class="alternate"' : '';
            ?>
            <tr <?php echo $class ?> id="row<?php echo $i; ?>">
              <td><?php echo $backup_name ?></td>
              <td>&nbsp;</td>
              <td><a href="#" title="<?php echo $backup_name; ?>" class="deleteRow" id="deleteRow<?php echo $i; ?>">Delete</a></td>
              <?php
              if ($this->license_active())
              {
                echo '<td><a href="#" title="' . $backup_name . '" class="restoreRow" id="restoreRow' . $i . '">Restore</a></td>';
              }
              ?>
            </tr>
            <?php
            $i++;
          }
        }
        else
        {
          echo '<tr id="nofiles"><td colspan="3">No backup archives found.</td></tr>';
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
        <h3>
            <i class="fa fa-upload"></i> <?php _e('Upload', $namespace); ?>
            <i id="upload-backups-accordian" style="float:right" class="fa fa-angle-double-down"></i>
        </h3>
        <p><b><?php _e('Upload WP BackItUp archive(zip) files to add to your list of available backups.', $namespace); ?></b></p>
        <?php
        $max_upload = (int) (ini_get('upload_max_filesize'));
        $max_post = (int) (ini_get('post_max_size'));
        $memory_limit = (int) (ini_get('memory_limit'));
        $upload_mb = min($max_upload, $max_post, $memory_limit);
        $upload_bytes = $upload_mb * 1048576;
        ?>
        <p>

        </p>

        <script type="text/javascript">
          var wpbackitup_plupload_config=<?php echo json_encode($plupload_config); ?>;
          var site_url="<?php echo get_site_url(); ?>";
        </script>

        <div id="wpbackitup-plupload-modal" title="<?php _e('WP BackItUp - Upload backup files',$namespace); ?>" style="width: 75%; margin: 16px; display:none; margin-left: 100px;">
          <p style="max-width: 610px;"><em><?php _e("Backup files may be uploaded into WP BackItUp with this form." ,$namespace);?> <?php echo htmlspecialchars(__('They may also be uploaded manually into the WP BackItUp directory (wp-content/wpbackitup_backups) using FTP. When done uploading all backup files refresh this page.',$namespace));?></em></p>
          <?php
          global $wp_version;
          if (version_compare($wp_version, '3.3', '<')) {
            echo '<em>'.sprintf(__('This feature requires %s version %s or later', $namespace), 'WordPress', '3.3').'</em>';
          } else {
            ?>
            <div id="plupload-upload-ui" class ="drag-drop" style="width: 100%;">
              <div id="drag-drop-area">
                <div class="drag-drop-inside">
                  <p class="drag-drop-info"><?php _e('Drop backup files here', $namespace); ?></p>
                  <p><?php _ex('or', 'Uploader: Drop backup files here - or - Select Files'); ?></p>
                  <p class="drag-drop-buttons"><input id="plupload-browse-button" type="button" value="<?php esc_attr_e('Select Files'); ?>" class="button" /></p>
                </div>
              </div>
            </div>
            <p style="max-width: 100%;"><em><?php _e("* Reload this page when done uploading to see new backups appear in the Available Backups section above. " ,$namespace);?> </em></p>

            <div id="filelist" class="media-item" style="width: 100%;"></div>

          <?php } ?>

        </div>


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
        <li class="unzipping"><?php _e('Unzipping backup set', $namespace); ?>...<span class='status-icon'><img class="unzipping-icon" src="<?php echo WPBACKITUP__PLUGIN_URL . "/images/loader.gif"; ?>" height="16" width="16" /></span><span class='status'><?php _e('Done', $namespace); ?></span><span class='fail error'><?php _e('Failed', $namespace); ?></span></li>
        <li class="validation"><?php _e('Validating backup file', $namespace); ?>...<span class='status-icon'><img class="validation-icon" src="<?php echo WPBACKITUP__PLUGIN_URL . "/images/loader.gif"; ?>" height="16" width="16" /></span><span class='status'><?php _e('Done', $namespace); ?></span><span class='fail error'><?php _e('Failed', $namespace); ?></span></li>
        <li class="deactivate_plugins"><?php _e('Deactivating plugins', $namespace); ?>...<span class='status-icon'><img class="deactivate_plugins-icon" src="<?php echo WPBACKITUP__PLUGIN_URL . "/images/loader.gif"; ?>" height="16" width="16" /></span><span class='status'><?php _e('Done', $namespace); ?></span><span class='fail error'><?php _e('Failed', $namespace); ?></span></li>
        <li class="restore_point"><?php _e('Creating database restore point', $namespace); ?>...<span class='status-icon'><img class="restore_point-icon" src="<?php echo WPBACKITUP__PLUGIN_URL . "/images/loader.gif"; ?>" height="16" width="16" /></span><span class='status'><?php _e('Done', $namespace); ?></span><span class='fail error'><?php _e('Failed', $namespace); ?></span></li>
        <li class="stage_wpcontent"><?php _e('Staging content files', $namespace); ?>...<span class='status-icon'><img class="stage_wpcontent-icon" src="<?php echo WPBACKITUP__PLUGIN_URL . "/images/loader.gif"; ?>" height="16" width="16" /></span><span class='status'><?php _e('Done', $namespace); ?></span><span class='fail error'><?php _e('Failed', $namespace); ?></span></li>
	    <li class="restore_wpcontent"><?php _e('Restoring content files', $namespace); ?>...<span class='status-icon'><img class="stage_wpcontent-icon" src="<?php echo WPBACKITUP__PLUGIN_URL . "/images/loader.gif"; ?>" height="16" width="16" /></span><span class='status'><?php _e('Done', $namespace); ?></span><span class='fail error'><?php _e('Failed', $namespace); ?></span></li>
        <li class="restore_database"><?php _e('Restoring database', $namespace); ?>...<span class='status-icon'><img class="restore_database-icon" src="<?php echo WPBACKITUP__PLUGIN_URL . "/images/loader.gif"; ?>" height="16" width="16" /></span><span class='status'><?php _e('Done', $namespace); ?></span><span class='fail error'><?php _e('Failed', $namespace); ?></span></li>
        <li class="update_user"><?php _e('Updating current user info', $namespace); ?>...<span class='status-icon'><img class="update_user-icon" src="<?php echo WPBACKITUP__PLUGIN_URL . "/images/loader.gif"; ?>" height="16" width="16" /></span><span class='status'><?php _e('Done', $namespace); ?></span><span class='fail error'><?php _e('Failed', $namespace); ?></span></li>
        <li class="update_site_info"><?php _e('Updating site URL', $namespace); ?>...<span class='status-icon'><img class="update_site_info-icon" src="<?php echo WPBACKITUP__PLUGIN_URL . "/images/loader.gif"; ?>" height="16" width="16" /></span><span class='status'><?php _e('Done', $namespace); ?></span><span class='fail error'><?php _e('Failed', $namespace); ?></span></li>
        <li class="activate_plugins"><?php _e('Activating plugins', $namespace); ?>...<span class='status-icon'><img class="activate_plugins-icon" src="<?php echo WPBACKITUP__PLUGIN_URL . "/images/loader.gif"; ?>" height="16" width="16" /></span><span class='status'><?php _e('Done', $namespace); ?></span><span class='fail error'><?php _e('Failed', $namespace); ?></span></li>
        <li class="update_permalinks"><?php _e('Updating permalinks', $namespace); ?>...<span class='status-icon'><img class="update_permalinks-icon" src="<?php echo WPBACKITUP__PLUGIN_URL . "/images/loader.gif"; ?>" height="16" width="16" /></span><span class='status'><?php _e('Done', $namespace); ?></span><span class='fail error'><?php _e('Failed', $namespace); ?></span></li>
      </ul>
      <p>

        <!--restore error messages-->
      <div class="restore-errors">
        <span class="error201"><div class='isa_error'><?php _e('Error 201: No file selected', $namespace); ?>.</div></span>
        <span class="error202"><div class='isa_error'><?php _e('Error 202: Your file could not be uploaded', $namespace); ?>.</div></span>
        <span class="error203"><div class='isa_error'><?php _e('Error 203: Your backup could not be unzipped', $namespace); ?>.</div></span>
        <span class="error204"><div class='isa_error'><?php _e('Error 204: Your backup appears to be invalid. Please ensure you selected a valid backup', $namespace); ?>.</div></span>
        <span class="error205"><div class='isa_error'><?php _e('Error 205: Cannot create restore point', $namespace); ?>.</div></span>
        <span class="error206"><div class='isa_error'><?php _e('Error 206: Unable to connect to your database', $namespace); ?>.</div></span>
        <span class="error207"><div class='isa_error'><?php _e('Error 207: Unable to get current site URL from database. Please try again', $namespace); ?>.</div></span>
        <span class="error208"><div class='isa_error'><?php _e('Error 208: Unable to get current home URL from database. Please try again', $namespace); ?>.</div></span>
        <span class="error209"><div class='isa_error'><?php _e('Error 209: Unable to get current user ID from database. Please try again', $namespace); ?>.</div></span>
        <span class="error210"><div class='isa_error'><?php _e('Error 210: Unable to get current user password from database. Please try again', $namespace); ?>.</div></span>
        <span class="error211"><div class='isa_error'><?php _e('Error 211: Unable to get current user email from database. Please try again', $namespace); ?>.</div></span>
        <span class="error212"><div class='isa_error'><?php _e('Error 212: Unable to import your database. This may require importing the file manually', $namespace); ?>.</div></span>
        <span class="warning213"><div class='isa_warning'><?php _e('Warning 213: Unable to update your site URL value. Please check your Wordpress general settings to make sure your Site and Wordpress URLs are correct', $namespace); ?>.</div></span>
        <span class="warning214"><div class='isa_warning'><?php _e('Warning 214: Unable to update your home URL value. Please check your Wordpress general settings to make sure your Site and Wordpress URLs are correct', $namespace); ?>.</div></span>
        <span class="warning215"><div class='isa_warning'><?php _e('Warning 215: Unable to update your user information. This may require you to login with the admin username and password that was used when the backup was created', $namespace); ?>.</div></span>
        <span class="error216"><div class='isa_error'><?php _e('Error 216: Database not found in backup', $namespace); ?>.</div></span>
        <span class="warning217"><div class='isa_warning'><?php _e('Warning 217: Unable to remove existing wp-content directory', $namespace); ?>.</div></span>
        <span class="error218"><div class='isa_error'><?php _e('Error 218: Unable to create new wp-content directory. Please check your CHMOD settings in /wp-content/', $namespace); ?>.</div></span>
        <span class="error219"><div class='isa_error'><?php _e('Error 219: Unable to import wp-content. Please try again', $namespace); ?>.</div></span>
        <span class="warning220"><div class='isa_warning'><?php _e('Warning 220: Unable to cleanup import directory. No action is required', $namespace); ?>.</div></span>
        <span class="error221"><div class='isa_error'><?php _e('Error 221: Table prefix value in wp-config.php is different from backup. This MUST be changed in your wp-config.php file before you will be able to restore your site.  See www.wpbackitup.com <a href ="http://www.wpbackitup.com/documentation/faqs/wordpress-table-prefix" target="_blank" > FAQs</a> for more info.', $namespace); ?>.</div></span>
        <span class='error222'><div class='isa_error'><?php _e('Error 222: Unable to create restore folder', $namespace); ?>.</div></span>
        <span class='error223'><div class='isa_error'><?php _e('Error 223: An error occurred during the restore.  WP BackItUp attempted to restore the database to its previous state but were unsuccessful.  Please contact WP BackItUp customer support and do not attempt to perform any further restores', $namespace); ?>.</div></span>
        <span class='error224'><div class='isa_error'><?php _e('Error 224: An error occurred during the restore, however, we have successfully restored your database to the previous state', $namespace); ?>.</div></span>
        <span class='error225'><div class='isa_error'><?php _e('Error 225: Restore option is only available to licensed WP BackItUp users', $namespace); ?>.</div></span>
        <span class='error226'><div class='isa_error'><?php _e('Error 226: Restore cannot proceed because your backup was created using a different version of Wordpress', $namespace); ?>.</div></span>
        <span class='error227'><div class='isa_error'><?php _e('Error 227: Restore cannot proceed because your backup was created using a different version of WP BackItUp.  Please contact WP BackItUp support to help convert this backup to the current version', $namespace); ?>.</div></span>
        <span class="error230"><div class='isa_error'><?php _e('Error 230: WP BackItUp ran into unexpected errors during the database restore.  However, we were able to successfully revert the database back to its original state . This error may require importing the database manually', $namespace); ?>.</div></span>
	    <span class="error235"><div class='isa_error'><?php _e('Error 235: WP BackItUp is not able to proceed because there is no zip utility available.  Please contact support', $namespace); ?>.</div></span>
	    <span class="error250"><div class='isa_error'><?php _e('Error 250: WP BackItUp is unable to begin the restore because a backup is running.  Please wait for the backup to complete and then try again', $namespace); ?>.</div></span>

        <span class="error251"><div class='isa_error'><?php _e('Error 251: WP BackItUp is unable to begin the restore because the backup manifest is empty', $namespace); ?>.</div></span>
        <span class="error252"><div class='isa_error'><?php _e('Error 252: At least one zip file is missing from your backup set.  Please make sure to upload all zip files that were part of this backup', $namespace); ?>.</div></span>
        <span class="error253"><div class='isa_error'><?php _e('Error 253: Backup set contains a zip file that is not in the manifest.  Please make sure to upload only zip files that were part of this backup', $namespace); ?>.</div></span>

        <span class="warning300"><div class='isa_warning'><?php _e('Warning 300: Unable to restore all Wordpress content. Please review your restore logs to see what WP BackItUp was unable to restore', $namespace); ?>.</div></span>
        <span class="warning305"><div class='isa_warning'><?php _e('Warning 305: Unable to restore all plugins. Please review your restore logs to see what WP BackItUp was unable to restore', $namespace); ?>.</div></span>

        <span class="error2001"><div class='isa_error'><?php _e('Error 2001: Unable to prepare site for restore', $namespace); ?>.</div></span>
        <span class="error2002"><div class='isa_error'><?php _e('Error 2002: Unable to unzip backup', $namespace); ?>.</div></span>
        <span class="error2003"><div class='isa_error'><?php _e('Error 2003: Unable to validate backup', $namespace); ?>.</div></span>
        <span class="error2004"><div class='isa_error'><?php _e('Error 2004: Unable to create restore point', $namespace); ?>.</div></span>
        <span class="error2005"><div class='isa_error'><?php _e('Error 2005: Unable to stage wp-content', $namespace); ?>.</div></span>
        <span class="error2006"><div class='isa_error'><?php _e('Error 2006: Unable to restore content files', $namespace); ?>.</div></span>
        <span class="error2007"><div class='isa_error'><?php _e('Error 2007: Unable to restore database', $namespace); ?>.</div></span>
        <span class="error2999"><div class='isa_error'><?php _e('Error 2999: Unexpected error encountered', $namespace); ?>.</div></span>


      </div>

      <!--restore success messages-->
      <div class="restore-success">
        <span class='finalinfo'><div class='isa_success'><?php _e('Restore completed successfully. If you are prompted to login please do so with your current username and password', $namespace); ?>.</div></span>
      </div>                

    </div>  

  </div>

</div>