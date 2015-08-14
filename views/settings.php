<?php if (!defined ('ABSPATH')) die('No direct access allowed');

/**
 * WP BackItUp  - Settings View
 *
 * @package WP BackItUp
 * @author  Chris Simmons <chris.simmons@wpbackitup.com>
 * @link    http://www.wpbackitup.com
 *
 */

	//Check the license
	do_action( 'wpbackitup_check_license');

    $namespace = $this->namespace;
    $page_title = sprintf(__('%s Settings', $namespace), $this->friendly_name );

    $license_active = $this->license_active();
    $is_lite_registered = $this->is_lite_registered();

	$backup_plugins_batch_size=$this->backup_plugins_batch_size();
  $backup_themes_batch_size=$this->backup_themes_batch_size();
  $backup_uploads_batch_size=$this->backup_uploads_batch_size();
  $backup_others_batch_size=$this->backup_others_batch_size();

    //Hold off on this for a bit
    $disabled='';
//    if (!$license_active && !$is_lite_registered){
//        $disabled='disabled';
//    }

?> 

<div class="wrap">
  <h2><?php echo $page_title; ?></h2>
  <div id="content">


        <!-- Display Settings widget -->
        <form action="admin-post.php" method="post" id="<?php echo $namespace; ?>-form">
          <?php wp_nonce_field($namespace . "-update-options"); ?>
          <div class="widget">
            <h3 class="promo"><i class="fa fa-envelope"></i> <?php _e('Email Notifications', $namespace)  ?></h3>
            <p><b><?php _e('Please enter your email address if you would like to receive backup email notifications.', $namespace) ?></b></p>
            <p><?php _e('Backup email notifications will be sent for every backup and will contain status information related to the backup.', $namespace); ?></p>
            <p><input type="text" name="data[notification_email]" value="<?php echo $this->notification_email(); ?>" size="30"></p>
            <div class="submit"><input <?php echo($disabled) ; ?> type="submit" name="Save_Email" class="button-primary" value="<?php _e("Save", $namespace) ?>" />
<!--              --><?php //if (!$license_active && !$is_lite_registered) : ?>
<!--                * Please register WP BackItUp to use this feature.-->
<!--             --><?php //endif; ?>
            </div>
            <?php
            if ( false !== ( $msg = get_transient('settings-error-email') ) && $msg)
            {
              echo '<p class="error">'.$msg.'</p>';
              delete_transient('settings-error-email');
            }
            ?>
          </div>


      <div class="widget">
        <h3 class="promo"><i class="fa fa-trash-o"></i> <?php _e('Backup Retention', $namespace) ?></h3>
        <p><b><?php _e('Enter the number of backup archives that you would like to remain on the server.', $namespace) ?></b></p>
        <p><?php _e('Many hosts limit the amount of space that you can take up on their servers. This option tells WP BackItUp the maximum number of backup archives that should remain on your hosts server.  Don\'t worry, we will always remove the oldest backup archives first.', $namespace) ?></p>
        <p><input type="text" name="data[backup_retained_number]" value="<?php echo $this->backup_retained_number(); ?>" size="4"></p>
        <div class="submit"><input type="submit" name="Save_Retention" class="button-primary" value="<?php _e("Save", $namespace) ?>" /></div>
        <?php
        if ( false !== ( $msg = get_transient('settings-error-number') ) && $msg)
        {
          echo '<p class="error">'.$msg.'</p>';
          delete_transient('settings-error-number');
        }

        if ( false !== ( $msg = get_transient('settings-license-error') ) && $msg)
        {
          echo '<p class="error">'.$msg.'</p>';
          delete_transient('settings-license-error');
        }
        ?>
      </div>

      <div class="widget">
        <h3 class="promo"><i class="fa fa-file-text-o"></i> <?php _e('Turn on logging?', $namespace) ?></h3>
        <p><input type="radio" name="data[logging]" value="true" <?php if ($this->logging()) echo 'checked'; ?>> <label><?php _e('Yes', $namespace); ?></label></p>
        <p><input type="radio" name="data[logging]" value="false" <?php if (!$this->logging()) echo 'checked'; ?>> <label><?php _e('No', $namespace); ?></label></p>
        <p><?php _e('This option should only be turned on when troubleshooting issues with WPBackItUp support.', $namespace); ?></p>
        <p class="submit"><input type="submit" name="Save_Logging" class="button-primary" value="<?php _e("Save", $namespace) ?>" /></p>
      </div>

     <div class="widget">
	    <h3 class="promo"><i class="fa fa-wrench"></i> <?php _e('Advanced Settings', $namespace) ?></h3>
  		<p><b><?php _e('These options should only be changed when working with WP BackItUp support.', $namespace) ?></b></p>
  		<p>
        <input name="data[backup_plugins_batch_size]" id="wpbackitup_plugins_batch_size" type="text" size="3" value="<?php echo $backup_plugins_batch_size; ?>"/>
        <label> <?php _e('Plugins Batch Size', $namespace) ?></label>
      </p>

      <p>
        <input name="data[backup_themes_batch_size]" id="wpbackitup_themes_batch_size" type="text" size="3" value="<?php echo $backup_themes_batch_size; ?>"/>
  		  <label> <?php _e('Themes Batch Size', $namespace) ?></label>
      </p>

      <p>
        <input name="data[backup_uploads_batch_size]" id="wpbackitup_uploads_batch_size" type="text" size="3" value="<?php echo $backup_uploads_batch_size; ?>"/>
        <label> <?php _e('Uploads Batch Size', $namespace) ?></label>
      </p>

      <p>
        <input name="data[backup_others_batch_size]" id="wpbackitup_others_batch_size" type="text" size="3" value="<?php echo $backup_others_batch_size; ?>"/>
        <label> <?php _e('Others Batch Size', $namespace) ?></label>
      </p>

	    <p class="submit"><input type="submit" name="Save_AdvancedSettings" class="button-primary" value="<?php _e("Save", $namespace) ?>" />
	     <?php
	     if ( false !== ( $msg = get_transient('batch_size_settings-error-number') ) && $msg)
	     {
		     echo '<p class="error">'.$msg.'</p>';
		     delete_transient('batch_size_settings-error-number');
	     }
	     ?>
	    </p>
 	 </div>


    </form>
  </div>
</div>