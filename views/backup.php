<?php if (!defined ('ABSPATH')) die('No direct access allowed');

/**
 * WP BackItUp  - Backup View
 *
 * @package WP BackItUp
 * @author  Chris Simmons <chris.simmons@wpbackitup.com>
 * @link    http://www.wpbackitup.com
 *
 */
		require_once( WPBACKITUP__PLUGIN_PATH .'/lib/includes/class-filesystem.php' );

		//Check the license
		do_action( 'wpbackitup_check_license');

        $namespace = $this->namespace;
		///TRANSLATORS: %s = plugin name.
		/// This string is in the header of one of my pages and looks like this: WP BackItUp Dashboard
		/// Similar to how WordPress uses the word dashboard at the in the left navigation.
        $page_title = sprintf( __("%s Dashboard",$namespace), $this->friendly_name );

        //Path Variables
        $backup_folder_root = WPBACKITUP__BACKUP_PATH;
		$logs_folder_root = WPBACKITUP__PLUGIN_PATH .'/logs';

		$debug_backup_view_log='debug_backup_view';

        //Get license info - trim off build version if 0
        $version = rtrim ($this->version,'.0');

        $license_key = $this->license_key();
        $license_active = $this->license_active();

        $license_type = $this->license_type();
        $license_type_description = $this->license_type_description();
        if (!empty($license_type_description)){
        $license_type_description = ucfirst($license_type_description);
        }

        $license_status = $this->license_status();
        $license_status_message = $this->license_status_message();

        $license_Expires = $this->license_expires();
        $formatted_expired_date = date('F j, Y',strtotime($license_Expires));

        // get retention number set
        $number_retained_archives = $this->backup_retained_number();

		$license_customer_name = $this->license_customer_name();
        $license_customer_email = $this->license_customer_email();
        $is_lite_registered = $this->is_lite_registered();

        $backup_schedule=$this->backup_schedule();

        $schedule_style_disabled='';
        if (!$license_active || 'expired'== $license_status){
            $schedule_style_disabled='disabled';
        }


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

		//Cleanup old backups - this can be removed in a few months.
		//Get Zip File List
		$file_system = new WPBackItUp_FileSystem($debug_backup_view_log);
		$file_list = $file_system->get_fileonly_list($backup_folder_root, 'zip|log');

		//If there are zip files then move them into their own folders
		WPBackItUp_LoggerV2::log_info($debug_backup_view_log,__METHOD__,'Files in backup folder: ' .var_export($file_list,true));
		WPBackItUp_LoggerV2::log_info($debug_backup_view_log,__METHOD__,'Last Error: ' .var_export(error_get_last(),true));

		if (null != $file_list) {
			foreach ( $file_list as $file ) {

				//remove the suffix
				$file_name = substr( basename( $file ), 0, - 4 );

				//strip off the suffix IF one exists
				$folder_name = $file_name;
				if ( ( $str_pos = strpos( $folder_name, '-main-' ) ) !== false ) {
					$suffix      = substr( $folder_name, $str_pos );
					$folder_name = str_replace( $suffix, '', $folder_name );
				}

				if ( ( $str_pos = strpos( $folder_name, '-others-' ) ) !== false ) {
					$suffix      = substr( $folder_name, $str_pos );
					$folder_name = str_replace( $suffix, '', $folder_name );
				}

				if ( ( $str_pos = strpos( $folder_name, '-plugins-' ) ) !== false ) {
					$suffix      = substr( $folder_name, $str_pos );
					$folder_name = str_replace( $suffix, '', $folder_name );
				}

				if ( ( $str_pos = strpos( $folder_name, '-themes-' ) ) !== false ) {
					$suffix      = substr( $folder_name, $str_pos );
					$folder_name = str_replace( $suffix, '', $folder_name );
				}

				if ( ( $str_pos = strpos( $folder_name, '-uploads-' ) ) !== false ) {
					$suffix      = substr( $folder_name, $str_pos );
					$folder_name = str_replace( $suffix, '', $folder_name );
				}

				//Does folder exist
				$backup_archive_folder = $backup_dir . '/' . $folder_name;
				if ( ! is_dir( $backup_archive_folder ) ) {
					if ( mkdir( $backup_archive_folder, 0755 ) ) {
						//print_r( "Folder Create.." );
					} else {
						//print_r( "Create Failed.." );
					}
				}

				//make sure it exists before you move it
				if ( is_dir( $backup_archive_folder ) ) {
					//move the file to the archive folder
					$target_file = $backup_archive_folder . "/" . basename( $file );
					if ( rename( $file, $target_file ) ) {
						//print_r( "File Moved.." );
					} else {
						//print_r( "Move Failed.." );
					}

				} else {
					//print_r( "NO FOLDER" );
				}

			}
		}

        $backup_list = $this->get_backup_list();

?>

<?php //Add Notification to UI
if (!$backup_folder_exists) {
    echo(
    '<div style="overflow: hidden;" class="error" id="wp-backitup-notification-parent" class="updated">
        <div style="float:left;" id="wp-backitup-notification-message" ><p><strong>' . __('Error',$namespace) . ':</strong> ' .
            sprintf(__('Backup folder does not exist. Please contact %s for assistance.', $namespace), $this->get_anchor_with_utm(__('support',$namespace),'support','backup+error','no+backup+folder')) );
    echo('</p></div>');

    echo('<div style="float:right;"><p><a id="wp-backitup-notification-close"><i style="float:right" class="fa fa-close"> ' . __('Close', $namespace) . '</i></a></p></div>
    </div>');
} else{
    echo(
    '<div style="overflow: hidden; display:none" id="wp-backitup-notification-parent" class="updated">
        <div style="float:left;" id="wp-backitup-notification-message" ></div>
        <div style="float:right;"><p><a id="wp-backitup-notification-close"><i style="float:right" class="fa fa-close"> ' . __('Close', $namespace) . '</i></a></p></div>
    </div>'
    );
}
?>

<script type="text/javascript">var __namespace = "<?php echo($namespace); ?>";</script>
<div class="wrap">
  <h2><?php echo $page_title; ?></h2>

  <div id="content">

    <!--Manual Backups-->
    <div class="widget">
      <h3><i class="fa fa-cogs"></i> <?php _e('Backup', $namespace); ?></h3>
      <p><b><?php _e('Click the backup button to create a zipped backup file of this site\'s database, plugins, themes and settings.',$namespace) ?></b></p>
      <p><?php _e('Once your backup file has been created it will appear in the available backups section below. This file may remain on your hosting providers server but we recommend that you download and save it somewhere safe.', $namespace) ?></p>
      <p> <?php _e('WP BackItUp premium customers can use these backup files to perform an automated restore of their site.', $namespace) ?></p>
      <p>
          <?php if ($backup_folder_exists) :?>
            <input type="submit" id="backup-button" class="backup-button button-primary" value="<?php _e("Backup", $namespace) ?>"/>
            <input type="submit" id="cancel-button" class="cancel-button button-secondary cancel-hidden" value="<?php _e("Cancel", $namespace) ?>"/>
            <img class="backup-icon status-icon" src="<?php echo WPBACKITUP__PLUGIN_URL . "/images/loader.gif"; ?>" height="16" width="16" />
          <?php endif; ?>
      </p>
      <?php
      //Display a note for lite customers
      if (!$license_active)
        echo '<p> * ' . sprintf(__('WP BackItUp lite customers may use these backup files to manually restore their site.  Please visit %s for manual restore instructions.', $namespace), $this->get_anchor_with_utm('www.wpbackitup.com','documentation/restore/how-to-manually-restore-your-wordpress-database','backup','manual+restore')) .'</p>';
      ?>
    </div>


      <!--Scheduled Backups-->
      <div class="widget">
          <h3><i class="fa fa-clock-o"></i> <?php _e('Backup Schedule', $namespace); ?>
              <i id="scheduled-backups-accordian" style="float:right" class="fa fa-angle-double-down"></i></h3>
              <p><b><?php _e('Select the days of the week you would like your backup to run.',$namespace) ?></b></p>
          <div id="scheduled-backups" style="display: none;">
              <p><?php _e('Backup your site once per week or every day, it\'s up to you.  If you have email notifications turned on we\'ll even send you an email when it\'s done.  Once your backup file has been created it will appear in the available backups section below. This file may remain on your hosting providers server but we recommend that you download and save it somewhere safe.', $namespace) ?></p>
              <p>
                  <b><?php _e('Please make sure to schedule your backup for at least once per week.', $namespace) ?></b>
              <form action="admin-post.php" method="post" id="<?php echo $namespace; ?>-save_schedule_form">
                  <?php wp_nonce_field($namespace . '-update-schedule',$namespace . '_nonce-update-schedule'); ?>

                  <input <?php echo($schedule_style_disabled); ?> type="checkbox" name="dow" <?php echo (false!==strpos($backup_schedule,'1'))? 'checked' :''; ?> value="1"><?php _e('Monday',$namespace) ?><br>
                  <input <?php echo($schedule_style_disabled); ?> type="checkbox" name="dow" <?php echo (false!==strpos($backup_schedule,'2'))? 'checked' :''; ?> value="2"><?php _e('Tuesday', $namespace) ?><br>
                  <input <?php echo($schedule_style_disabled); ?> type="checkbox" name="dow" <?php echo (false!==strpos($backup_schedule,'3'))? 'checked' :''; ?> value="3"><?php _e('Wednesday',$namespace) ?><br>
                  <input <?php echo($schedule_style_disabled); ?> type="checkbox" name="dow" <?php echo (false!==strpos($backup_schedule,'4'))? 'checked' :''; ?> value="4"><?php _e('Thursday',$namespace) ?><br>
                  <input <?php echo($schedule_style_disabled); ?> type="checkbox" name="dow" <?php echo (false!==strpos($backup_schedule,'5'))? 'checked' :''; ?> value="5"><?php _e('Friday',$namespace) ?><br>
                  <input <?php echo($schedule_style_disabled); ?> type="checkbox" name="dow" <?php echo (false!==strpos($backup_schedule,'6'))? 'checked' :''; ?> value="6"><?php _e('Saturday',$namespace) ?><br>
                  <input <?php echo($schedule_style_disabled); ?> type="checkbox" name="dow" <?php echo (false!==strpos($backup_schedule,'7'))? 'checked' :''; ?> value="7"><?php _e('Sunday',$namespace) ?><br>

                  <br/>
                  <input <?php _e($schedule_style_disabled); ?>  type="submit" id="schedule-button" class="schedule-button button-primary" value="<?php _e("Save Schedule", $namespace) ?>"/>
              </form>
              <?php
              //Display restore note for lite customers
              if (!$license_active || 'expired'== $license_status)
                  echo '<p>* ' . sprintf(__('Scheduled backups are only available to WP BackItUp premium customers.  Please visit %s to get WP BackItUp risk free for 30 days.', $namespace), $this->get_anchor_with_utm('www.wpbackitup.com','pricing-purchase','scheduled+backups','risk+free') ) . '</p>';
              ?>
          </div>
      </div>

    <!--Available Backups section-->
    <div class="widget">
      <h3><i class="fa fa-cloud-download"></i> <?php _e('Available Backups', $namespace); ?></h3>

    <!--View Log Form-->
    <form id = "viewlog" name = "viewlog" action="admin-post.php" method="post">
        <input type="hidden" name="action" value="viewlog">
        <input type="hidden" id="backup_name" name="backup_name" value="">
        <?php wp_nonce_field($this->namespace . "-viewlog"); ?>
    </form>


    <form id = "download_backup" name = "download_backup" action="admin-post.php" method="post">
	    <input type="hidden" name="action" value="download_backup">
	    <input type="hidden" id="backup_file" name="backup_file" value="">
	    <?php wp_nonce_field($this->namespace . "-download_backup"); ?>
    </form>

      <table class="widefat" id="datatable">
        <?php

        if ($backup_list!=false)
        {
          $i = 0;
          foreach ($backup_list as $file)
          {

            //Only show the number in the retained list
            if($i >= $number_retained_archives ) break;

	        $backup_name = $file["backup_name"];
	        $file_datetime = get_date_from_gmt(date('Y-m-d H:i:s', $file["date_time"]), 'Y-m-d g:i a');
	        $log_exists    = $file["log_exists"];

            $class = $i % 2 == 0 ? 'class="alternate"' : '';
            ?>

            <tr <?php echo $class ?> id="row<?php echo $i; ?>">
              <td><?php echo $backup_name ?></td>

                <!--Download Link-->
              <td>
                  <a href="#TB_inline?width=600&height=550&inlineId=<?php echo preg_replace('/[^A-Za-z0-9\-]/', '', $backup_name) ?>" class="thickbox" title="<?php echo $backup_name ?>"><?php _e('Download', $namespace) ?></a>
              </td>

              <?php if (($log_exists)):?>
                <td><a class='viewloglink' href="<?php echo $backup_name ?>"><?php _e('View Log', $namespace) ?></a></td>
              <?php else: ?>
                <td>&nbsp;</td>
              <?php endif; ?>

               <td><a href="#" title="<?php echo $backup_name; ?>" class="deleteRow" id="deleteRow<?php echo $i; ?>"><?php _e('Delete', $namespace) ?></a></td>
            </tr>

            <?php
              $i++;
          }
        }
        else
        {
          echo '<tr id="nofiles"><td colspan="3">' . __('No backup archives found.',$namespace). '</td></tr>';
        }
        ?>
      </table>  

      <?php
      //Display restore note for lite customers
      if (!$license_active)
        echo '<p>* ' . sprintf(__('The automated restore feature is only available to WP BackItUp premium customers.  Please visit %s to get WP BackItUp risk free for 30 days.', $namespace), $this->get_anchor_with_utm('www.wpbackitup.com','pricing-purchase','available+backups','risk+free')) . '</p>';
      ?>
    </div>		

    <div id="status" class="widget">
      <h3><i class="fa fa-check-square-o"></i> <?php _e('Status', $namespace); ?></h3>        

      <!--default status message-->
      <ul class="default-status">
        <li><?php _e('Nothing to report', $namespace); ?></li>
      </ul>

      <!--backup status messages-->
      <ul class="backup-status">
        <li class="preparing"><?php _e('Preparing for backup', $namespace); ?>...<span class='status-icon'><img class="preparing-icon" src="<?php echo WPBACKITUP__PLUGIN_URL . "/images/loader.gif"; ?>" height="16" width="16" /></span><span class='status'><?php _e('Done', $namespace); ?></span><span class='fail error'><?php _e('Failed', $namespace); ?></span><span class='wpbackitup-warning'><?php _e('Warning', $namespace); ?></span></li>
        <li class='backupdb'><?php _e('Backing up database', $namespace); ?>...<span class='status-icon'><img class="backupdb-icon" src="<?php echo WPBACKITUP__PLUGIN_URL . "/images/loader.gif"; ?>" height="16" width="16" /></span><span class='status'><?php _e('Done', $namespace); ?></span><span class='fail error'><?php _e('Failed', $namespace); ?></span><span class='wpbackitup-warning'><?php _e('Warning', $namespace); ?></span></li>
        <li class='infofile'><?php _e('Creating backup information file', $namespace); ?>...<span class='status-icon'><img class="infofile-icon" src="<?php echo WPBACKITUP__PLUGIN_URL . "/images/loader.gif"; ?>" height="16" width="16" /></span><span class='status'><?php _e('Done', $namespace); ?></span><span class='fail error'><?php _e('Failed', $namespace); ?></span></span><span class='wpbackitup-warning'><?php _e('Warning', $namespace); ?></span></li>
	    <li class='backup_themes'><?php _e('Backing up themes', $namespace); ?>...<span class='status-icon'><img class="backup_themes-icon" src="<?php echo WPBACKITUP__PLUGIN_URL . "/images/loader.gif"; ?>" height="16" width="16" /></span><span class='status'><?php _e('Done', $namespace); ?></span><span class='fail error'><?php _e('Failed', $namespace); ?></span><span class='wpbackitup-warning'><?php _e('Warning', $namespace); ?></span></li>
	    <li class='backup_plugins'><?php _e('Backing up plugins', $namespace); ?>...<span class='status-icon'><img class="backup_plugins-icon" src="<?php echo WPBACKITUP__PLUGIN_URL . "/images/loader.gif"; ?>" height="16" width="16" /></span><span class='status'><?php _e('Done', $namespace); ?></span><span class='fail error'><?php _e('Failed', $namespace); ?></span><span class='wpbackitup-warning'><?php _e('Warning', $namespace); ?></span></li>
	    <li class='backup_uploads'><?php _e('Backing up uploads', $namespace); ?>...<span class='status-icon'><img class="backup_uploads-icon" src="<?php echo WPBACKITUP__PLUGIN_URL . "/images/loader.gif"; ?>" height="16" width="16" /></span><span class='status'><?php _e('Done', $namespace); ?></span><span class='fail error'><?php _e('Failed', $namespace); ?></span><span class='wpbackitup-warning'><?php _e('Warning', $namespace); ?></span></li>
	    <li class='backup_other'><?php _e('Backing up everything else', $namespace); ?>...<span class='status-icon'><img class="backup_other-icon" src="<?php echo WPBACKITUP__PLUGIN_URL . "/images/loader.gif"; ?>" height="16" width="16" /></span><span class='status'><?php _e('Done', $namespace); ?></span><span class='fail error'><?php _e('Failed', $namespace); ?></span><span class='wpbackitup-warning'><?php _e('Warning', $namespace); ?></span></li>
	    <li class='validate_backup'><?php _e('Validating backup', $namespace); ?>...<span class='status-icon'><img class="validate_backup-icon" src="<?php echo WPBACKITUP__PLUGIN_URL . "/images/loader.gif"; ?>" height="16" width="16" /></span><span class='status'><?php _e('Done', $namespace); ?></span><span class='fail error'><?php _e('Failed', $namespace); ?></span><span class='wpbackitup-warning'><?php _e('Warning', $namespace); ?></span></li>
        <li class='finalize_backup'><?php _e('Finalizing backup', $namespace); ?>...<span class='status-icon'><img class="finalize_backup-icon" src="<?php echo WPBACKITUP__PLUGIN_URL . "/images/loader.gif"; ?>" height="16" width="16" /></span><span class='status'><?php _e('Done', $namespace); ?></span><span class='fail error'><?php _e('Failed', $namespace); ?></span><span class='wpbackitup-warning'><?php _e('Warning', $namespace); ?></span></li>
      </ul>

      <!--Error status messages-->
      <ul class="backup-error">
	      <!--Warning PlaceHolder-->
      </ul>

         <!--success messages-->
  	  <ul class="backup-success">
  		  <li class='isa_success'><?php _e('Backup completed successfully', $namespace); ?>.</li>
  	  </ul>

        <ul class="backup-warning">
  	      <!--Warning PlaceHolder-->
  	  </ul>
        
        <!--cancelled messages-->
        <ul class="backup-cancelled">
          <li class='isa_cancelled'><?php _e('Backup Cancelled', $namespace); ?>.</li>
      </ul>


    </div>

  </div> <!--content-->

  <div id="sidebar">


          <div class="widget">
              <h3 class="promo"><?php _e('Backups', $namespace); ?> <span style="float: right"><?php _e('Version ' .$version, $namespace); ?></span></h3>
              <?php if ($this->successful_backup_count()<1) : ?>
                  <p><?php _e('Welcome to WP BackItUp!', $namespace) ?><br/>  <?php _e('The simplest way to backup your WordPress site.', $namespace) ?></p>
                  <p><?php _e('Getting started is easy, just click the backup button on the left side of this page.', $namespace) ?></p>
              <?php endif ?>

              <?php if ($this->successful_backup_count()>=1) : ?>
                <p><?php printf(__('Congratulations! You have performed <span style="font-weight:bold;font-size:medium;color: green">%s</span> successful backups.', $namespace),$this->successful_backup_count()) ?></p>
                <p><span style="font-weight:bold;font-size:medium"><?php _e('Tips', $namespace) ?></span>
                   <br/>1)&nbsp;<?php _e('Backup your site at least once per week',$namespace) ?>
                   <br/>2)&nbsp;<?php _e('Download all your backups and store them somewhere safe', $namespace) ?>
                   <br/>3)&nbsp;<?php _e('Verify your backup files are good by taking a look at what\'s inside', $namespace) ?>
                </p>
              <?php endif ?>

              <?php if (!$license_active && $this->successful_backup_count()>=10) : ?>
                  * <?php printf(__('Why not %s and schedule your backups!', $namespace), $this->get_anchor_with_utm(__('upgrade',$namespace),'pricing-purchase','get+license','purchase')) ?>
              <?php endif ?>
          </div>


    <!-- Display opt-in form if the user is unregistered -->
    <?php if (!$license_active) : ?>
        <?php if (!$is_lite_registered) : ?>
            <div class="widget">
                <h3 class="promo"><span><?php _e('Register WP BackItUp', $namespace); ?></span></h3>
                <form action="" method="post" id="<?php echo $namespace; ?>-form">
                <?php wp_nonce_field($namespace . "-register"); ?>
                <p><?php _e('Enter your name and email address below to receive <b>special offers</b> and access to our world class <b>support</b> team.  <br />
                <br />Enter your license key below to activate it on this site.', $namespace); ?></p>
	            <input type="text" name="license_name" id="license_name" placeholder="<?php _e('name',$namespace)?>" value="<?php echo($license_customer_name) ?>" /><br/>
                <input type="text" name="license_email" id="license_email" placeholder="<?php _e('email address',$namespace)?>" value="<?php echo($license_customer_email) ?>" /><br/>
                <input type="text" name="license_key" id="license_key" placeholder="<?php _e('license key',$namespace)?>" value="<?php if ($license_key != 'lite') { echo $license_key; } ?>" ?> &nbsp;<span style="color:red"><?php printf(__("%s", $namespace), $license_status_message); ?></span>
                <br />* <?php _e('Free plugin customers do not need to enter license key.', $namespace) ?>
                <div class="submit"><input type="submit" name="Submit" class="button-secondary" value="<?php _e("Register", $namespace) ?>" /></div>
                </form>
            </div>
       <?php endif ?>
    <?php endif; ?>

    <?php if ($license_active || $is_lite_registered) : ?>
      <!-- Display license key widget -->
      <div class="widget">
        <h3 class="promo"><span><?php _e('License Info', $namespace); ?></span><span style="float: right"></h3></h3>
        <form action="" method="post" id="<?php echo $namespace; ?>-form">
        <?php wp_nonce_field($namespace . "-update-options"); ?>
        <?php

        $fontColor='green';
        if ($license_status=='valid')
          $fontColor='green';

        if ($license_status=='invalid')
          $fontColor='red';

        if ($license_status=='expired')
          $fontColor='orange';

        $license_message='';
        if (!empty($license_status)) {
            $license_message=' License Status: ' . $license_status;
        }

        if($license_active) {
            echo('<p>');
            echo(__('Name', $namespace) . ': &nbsp;' . $license_customer_name);
            echo('<br/>' . __('Email', $namespace) . ': &nbsp;' . $license_customer_email);
            echo('<br/>' . __('License Type', $namespace) . ': &nbsp;' . $license_type_description);
            echo('<br/>' . __('Expires', $namespace) . ': &nbsp;' . $formatted_expired_date);
            echo('</p>');
        } else {
            echo '<p>' . __('Enter license key to activate on this site.',$namespace) . '</p>';
        }
        ?>

        <input type="text" name="data[license_key]" id="license_key" value="<?php echo $license_key; ?>" />&nbsp;

            <?php if ($license_status=='valid'): ?>
                <span style="color:green"><?php __('License Active', $namespace) ?></span>
            <?php endif; ?>

            <?php if ($license_status=='invalid'): ?>
              <span style="color:<?php echo $fontColor; ?>"><?php printf(__("%s", $namespace), $license_status_message); ?></span>
            <?php endif; ?>

            <?php if ($license_status=='expired'): ?>
            <span style="color:red"><?php _e('License expired', $namespace) ?>:&nbsp;<?php printf(__("%s", $namespace), $formatted_expired_date); ?></span>
            <?php endif; ?>

            <?php if ($license_active) : ?>
              <div class="submit"><input type="submit" name="Submit" class="button-secondary" value="<?php _e("Update", $namespace) ?>" /></div>
            <?php endif; ?>

            <?php if (!$license_active) : ?>
              <p class="submit"><input type="submit" name="Submit" class="button-secondary" value="<?php _e("Activate", $namespace) ?>" /></p>
            <?php endif; ?>

            <?php if ($license_status=='invalid' || $license_status==''): ?>
              <p><?php printf(__("Purchase a %s license using the purchase link above.", $namespace), $this->get_anchor_with_utm(__('no-risk',$namespace),'pricing-purchase','license','no+risk')) ?></p>
            <?php endif; ?>

            <?php if ($license_status=='expired'): ?>
              <div>
	              <?php
	                  printf( __('<a href="%s" target="blank">Renew</a> your license now for another year of <strong>product updates</strong> and <strong>priority support.</strong>',$namespace),
		              esc_url(sprintf('%s/checkout?edd_license_key=%s&download_id=679&nocache=true&utm_medium=plugin&utm_source=wp-backitup&utm_campaign=premium&utm_content=license&utm_term=license+expired', WPBACKITUP__SECURESITE_URL,$license_key)))?>
              </div>
            <?php endif; ?>

        </form>
      </div>
      <?php endif; ?>

    <!-- Display links widget -->
    <div class="widget">
          <h3 class="promo"><?php _e('Useful Links', $namespace); ?></h3>
          <ul>
              <?php if ($license_active) : ?>
                  <li><?php echo($this->get_anchor_with_utm(__('Your account',$namespace),'account','useful+links','your+account')) ?></li>
              <?php endif; ?>

              <?php if (!$license_active) : ?>
                <li><?php echo($this->get_anchor_with_utm(__('Your account',$namespace),'account','useful+links','your+account'))?></li>
              <?php endif; ?>

              <li><?php echo($this->get_anchor_with_utm(__('Documentation',$namespace),'documentation/faqs/wp-backitup-logging','useful+links','help'))?></li>

              <?php if ($license_active || $is_lite_registered) : ?>
                  <li><?php echo( $this->get_anchor_with_utm(__('Get support',$namespace),'support' ,'useful+links','get+support'))?></li>
              <?php endif; ?>

              <li><?php echo($this->get_anchor_with_utm(__('Feature request',$namespace),'contact' ,'useful+links','feature+request'))?></li>

              <li><?php echo($this->get_anchor_with_utm(__('Contact',$namespace) ,'contact','useful+links','contact'))?></li>

          </ul>
    </div>

  </div><!--Sidebar-->

</div> <!--wrap-->

<!--File download lists-->
<span class="hidden">
    <?php  add_thickbox(); ?>
    <!--File download lists-->
    <?php if ($backup_list!=false) : ?>
	    <?php foreach ($backup_list as $folder) :
	    $backup_name = $folder["backup_name"];
	    $zip_files = $folder["zip_files"];
	    $count=0;
	    ?>
	    <div id="<?php echo preg_replace('/[^A-Za-z0-9\-]/', '', $backup_name) ?>" style="display:none;">
	        <h2><?php _e('WP BackItUp Backup Set', $namespace) ?>:</h2>
            <h2><em><?php echo $backup_name ?></em></h2>
	        <p><?php _e('Below are the archive files included in this backup set. Click the link to download.', $namespace) ?></p>
	        <table id="datatable" class="widefat">
	            <tbody>
	            <?php foreach ($zip_files as $file) :
	                ++$count;
	                $class = $count % 2 == 0 ? '' : 'alternate';
	                $row_id="row".$count;
	                $zip_file = basename($file);
	                ?>
	                <tr id="<?php echo $row_id ?>" class="<?php echo $class ?>">
	                    <td><a href="<?php echo $zip_file  ?>" class="downloadbackuplink"><?php echo $zip_file ?></a></td>
	                </tr>
	            <?php endforeach; ?>
	            </tbody>
	        </table>
	        <?php endforeach; ?>
	    </div>
    <?php endif; ?>
    <div id="new_backup" style="display:none;">
        <h2><?php _e('WP BackItUp Backup Set', $namespace) ?></h2>
        <p><?php _e('Please refresh this page to download your new backup files.', $namespace) ?></p>
    </div>
</span>
<!--End File download lists-->