<?php if (!defined ('ABSPATH')) die('No direct access allowed');

/**
 * WP BackItUp  - Settings View
 *
 * @package WP BackItUp
 * @author  Chris Simmons <chris.simmons@wpbackitup.com>
 * @link    http://www.wpbackitup.com
 *
 */

    $page_title = $this->friendly_name . ' Settings';
    $namespace = $this->namespace;

    $license_active = $this->license_active();
    $is_lite_registered = $this->is_lite_registered();

	$backup_batch_size=$this->backup_batch_size();

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
            <h3 class="promo"><i class="fa fa-envelope"></i> Email Notifications</h3>
            <p><b>Please enter your email address if you would like to receive backup email notifications.</b></p>
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
        <h3 class="promo"><i class="fa fa-trash-o"></i> Backup Retention</h3>
        <p><b>Enter the number of backup archives that you would like to remain on the server.</b></p>
        <p>Many hosts limit the amount of space that you can take up on their servers. This option tells 
          WP BackItUp the maximum number of backup archives that should remain on your hosts server.  Don't worry, we will 
          always remove the oldest backup archives first.</p>
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
        <h3 class="promo"><i class="fa fa-file-text-o"></i> Turn on logging?</h3>
        <p><input type="radio" name="data[logging]" value="true" <?php if ($this->logging()) echo 'checked'; ?>> <label><?php _e('Yes', $namespace); ?></label></p>
        <p><input type="radio" name="data[logging]" value="false" <?php if (!$this->logging()) echo 'checked'; ?>> <label><?php _e('No', $namespace); ?></label></p>
        <p><?php _e('This option should only be turned on when troubleshooting issues with WPBackItUp support.', $namespace); ?></p>
        <p class="submit"><input type="submit" name="Save_Logging" class="button-primary" value="<?php _e("Save", $namespace) ?>" /></p>
      </div>

     <div class="widget">
	    <h3 class="promo"><i class="fa fa-wrench"></i> Advanced Settings</h3>
		<p><b>These options should only be changed when working with WP BackItUp support.</b></p>
		<input name="data[backup_batch_size]" id="wpbackitup_batch_size" type="text" size="2" value="<?php echo $backup_batch_size; ?>"/>
		<label> Backup batch size</label>
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

      <!--Debug Widget-->
      <?php if (WP_DEBUG===true) :?> 
        <div class="widget">
              <h3><i class="fa fa-wrench"></i> <?php _e('Debug', $namespace); ?></h3>
              <div id="php"><p>Debugging is turned on in your wp-config.php file and should only be used when troubleshooting issues on your site.</p></div>
        </div>
      <?php endif; ?>   

    </form>
  </div>
</div>