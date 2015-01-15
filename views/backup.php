<?php if (!defined ('ABSPATH')) die('No direct access allowed');

        $page_title = $this->friendly_name . ' Dashboard';
        $namespace = $this->namespace;

        //Path Variables
        $backup_folder_root = WPBACKITUP__BACKUP_PATH;
		$logs_folder_root = WPBACKITUP__PLUGIN_PATH .'/logs';

        //Get license info
        $version = $this->version;
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

		$lite_registration_first_name = $this->lite_registration_first_name();
        $lite_registration_email = $this->lite_registration_email();
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
		$file_list = glob($backup_folder_root . "/*.{zip,log}",GLOB_BRACE);
		//If there are zip files then move them into their own folders
		foreach($file_list as $file) {

			//remove the suffix
			$file_name = substr(basename($file),0,-4);

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
				if (mkdir( $backup_archive_folder, 0755 )){
					//print_r( "Folder Create.." );
				}else{
					//print_r( "Create Failed.." );
				}
			}

			//make sure it exists before you move it
			if ( is_dir( $backup_archive_folder ) ) {
				//move the file to the archive folder
				$target_file = $backup_archive_folder ."/" . basename($file);
				if (rename ($file,$target_file)){
					//print_r( "File Moved.." );
				} else{
					//print_r( "Move Failed.." );
				}

			} else {
				//print_r( "NO FOLDER" );
			}

		}

        $backup_list = $this->get_backup_list();

?>

<?php //Add Notification to UI
if (!$backup_folder_exists) {
    echo(
    '<div style="overflow: hidden;" class="error" id="wp-backitup-notification-parent" class="updated">
        <div style="float:left;" id="wp-backitup-notification-message" ><p><strong>Error:</strong> Backup folder does not exist. Please contact ');

    echo($this->get_anchor_with_utm('support','support','backup+error','no+backup+folder'));
    echo(' for assistance.</p></div>');

    echo('<div style="float:right;"><p><a id="wp-backitup-notification-close"><i style="float:right" class="fa fa-close"> Close</i></a></p></div>
    </div>');
} else{
    echo(
    '<div style="overflow: hidden; display:none" id="wp-backitup-notification-parent" class="updated">
        <div style="float:left;" id="wp-backitup-notification-message" ></div>
        <div style="float:right;"><p><a id="wp-backitup-notification-close"><i style="float:right" class="fa fa-close"> Close</i></a></p></div>
    </div>'
    );
}
?>

<script type="text/javascript">var __namespace = "<?php echo($namespace); ?>";</script>
<div class="wrap">
  <h2><?php echo $page_title; ?></h2>

  <div id="content">

    <!--Manual Backups-->
    <!--Manual Backups-->
    <div class="widget">
      <h3><i class="fa fa-cogs"></i> <?php _e('Backup', $namespace); ?></h3>
      <p><b>Click the backup button to create a zipped backup file of this site's database, plugins, themes and settings.</b></p>
      <p>Once your backup file has been created it will appear in the available backups section below. This file may remain on your hosting providers server but we recommend that you download and save it somewhere safe.</p>
      <p> WP BackItUp premium customers can use these backup files to perform an automated restore of their site.</p>
      <p>
          <?php if ($backup_folder_exists) :?>
            <input type="submit" id="backup-button" class="backup-button button-primary" value="<?php _e("Backup", $namespace) ?>"/><img class="backup-icon status-icon" src="<?php echo WPBACKITUP__PLUGIN_URL . "/images/loader.gif"; ?>" height="16" width="16" /></p>
          <?php endif; ?>
      <?php
      //Display a note for lite customers
      if (!$license_active)
        echo '<p> * WP BackItUp lite customers may use these backup files to manually restore their site.  Please visit ' .$this->get_anchor_with_utm('www.wpbackitup.com','documentation/restore/how-to-manually-restore-your-wordpress-database','backup','manual+restore') .' for manual restore instructions.</p>';
      ?>
    </div>


      <!--Scheduled Backups-->
      <div class="widget">
          <h3><i class="fa fa-clock-o"></i> <?php _e('Backup Schedule', $namespace); ?>
              <i id="scheduled-backups-accordian" style="float:right" class="fa fa-angle-double-down"></i></h3>
              <p><b>Select the days of the week you would like your backup to run.</b></p>
          <div id="scheduled-backups" style="display: none;">
              <p>Backup your site once per week or every day, it's up to you.  If you have email notifications turned on we'll even send you an email when it's done.
              Once your backup file has been created it will appear in the available backups section below. This file may remain on your hosting providers server but we recommend that you download and save it somewhere safe.</p>
              <p>
                  <b>Please make sure to schedule your backup for at least once per week.</b>
              <form action="admin-post.php" method="post" id="<?php echo $namespace; ?>-save_schedule_form">
                  <?php wp_nonce_field($namespace . '-update-schedule',$namespace . '_nonce-update-schedule'); ?>

                  <input <?php _e($schedule_style_disabled); ?> type="checkbox" name="dow" <?php (false!==strpos($backup_schedule,'1'))? _e('checked') :_e(''); ?> value="1">Monday<br>
                  <input <?php _e($schedule_style_disabled); ?> type="checkbox" name="dow" <?php (false!==strpos($backup_schedule,'2'))? _e('checked') :_e(''); ?> value="2">Tuesday<br>
                  <input <?php _e($schedule_style_disabled); ?> type="checkbox" name="dow" <?php (false!==strpos($backup_schedule,'3'))? _e('checked') :_e(''); ?> value="3">Wednesday<br>
                  <input <?php _e($schedule_style_disabled); ?> type="checkbox" name="dow" <?php (false!==strpos($backup_schedule,'4'))? _e('checked') :_e(''); ?> value="4">Thursday<br>
                  <input <?php _e($schedule_style_disabled); ?> type="checkbox" name="dow" <?php (false!==strpos($backup_schedule,'5'))? _e('checked') :_e(''); ?> value="5">Friday<br>
                  <input <?php _e($schedule_style_disabled); ?> type="checkbox" name="dow" <?php (false!==strpos($backup_schedule,'6'))? _e('checked') :_e(''); ?> value="6">Saturday<br>
                  <input <?php _e($schedule_style_disabled); ?> type="checkbox" name="dow" <?php (false!==strpos($backup_schedule,'7'))? _e('checked') :_e(''); ?> value="7">Sunday<br>

                  <br/>
                  <input <?php _e($schedule_style_disabled); ?>  type="submit" id="schedule-button" class="schedule-button button-primary" value="<?php _e("Save Schedule", $namespace) ?>"/>
              </form>
              <?php
              //Display restore note for lite customers
              if (!$license_active || 'expired'== $license_status)
                  echo '<p>* Scheduled backups are only available to WP BackItUp premium customers.  Please visit ' .$this->get_anchor_with_utm('www.wpbackitup.com','pricing-purchase','scheduled+backups','risk+free') . ' to get WP BackItUp risk free for 30 days.</p>';
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

	        $backup_name = $file["backup_name"];
	        $file_datetime = get_date_from_gmt(date('Y-m-d H:i:s', $file["date_time"]), 'Y-m-d g:i a');
	        $log_exists    = $file["log_exists"];

            $class = $i % 2 == 0 ? 'class="alternate"' : '';
            ?>

            <tr <?php echo $class ?> id="row<?php echo $i; ?>">
              <td><?php echo $file_datetime ?></td>

                <!--Download Link-->
              <td>
                  <a href="#TB_inline?width=600&height=550&inlineId=<?php echo preg_replace('/[^A-Za-z0-9\-]/', '', $backup_name) ?>" class="thickbox" title="<?php echo $backup_name ?>">Download</a>
              </td>

              <?php if (($log_exists)):?>
                <td><a class='viewloglink' href="<?php echo $backup_name ?>">View Log</a></td>
              <?php else: ?>
                <td>&nbsp;</td>
              <?php endif; ?>

               <td><a href="#" title="<?php echo $backup_name; ?>" class="deleteRow" id="deleteRow<?php echo $i; ?>">Delete</a></td>
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

      <?php
      //Display restore note for lite customers
      if (!$license_active)
        echo '<p>* The automated restore feature is only available to WP BackItUp premium customers.  Please visit ' .$this->get_anchor_with_utm('www.wpbackitup.com','pricing-purchase','available+backups','risk+free') . ' to get WP BackItUp risk free for 30 days.</p>';
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
		  <li class='isa_success'><?php _e('Backup completed successfully. ', $namespace); ?></li>
	  </ul>

      <ul class="backup-warning">
	      <!--Warning PlaceHolder-->
	  </ul>

    </div>

  </div> <!--content-->

  <div id="sidebar">

    <!-- Display opt-in form if the user is unregistered -->
    <?php if (!$license_active) : ?>
        <?php if (!$is_lite_registered) : ?>
            <div class="widget">
                <h3 class="promo"><?php _e('Register WP BackItUp Lite', $namespace); ?></h3>
                <form action="" method="post" id="<?php echo $namespace; ?>-form">
                <?php wp_nonce_field($namespace . "-register-lite"); ?>
                <p><?php _e('Enter your email address to register WP BackItUp Lite.  Registered users will receive <b>special offers</b> and access to our world class <b>support</b> team.  <br /> <br />Premium customers only need to enter their license key in the section below.  Registration is not required.', $namespace); ?></p>
	            <input type="text" name="first_name" id="first_name" placeholder="first name" value="<?php echo($lite_registration_first_name) ?>" /><br/>
                <input type="text" name="email" id="email" placeholder="email address" value="<?php echo($lite_registration_email) ?>" />
                <div class="submit"><input type="submit" name="Submit" class="button-secondary" value="<?php _e("Register", $namespace) ?>" /></div>
                </form>
            </div>
       <?php else : ?>
          <div class="widget">
            <h3 class="promo"><?php _e('Get a license', $namespace); ?></h3>
            <p><?php _e('Tired of messing with FTP, MySQL and PHPMyAdmin? Restore your backups from this page in minutes or your money back', $namespace); ?>.</p>
            <?php echo($this->get_anchor_with_utm('Purchase a license for WP BackItUp','pricing-purchase','get+license','purchase')) ?>
          </div>
      <?php endif ?>
    <?php endif; ?>

      <!-- Display license key widget -->
      <div class="widget">
        <h3 class="promo"><?php _e('License v ' . $version, $namespace); ?></h3>
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

        if($license_active)
            echo '<p>' . $license_type_description .' License Key</p>';
        else
            echo '<p>Enter your license key to activate features.</p>';
        ?>

        <input type="text" name="data[license_key]" id="license_key" value="<?php _e($license_key, $namespace); ?>" />
        <div style="color:<?php _e($fontColor); ?>"><?php _e($license_message, $namespace); ?></div>
        <div style="color:<?php _e($fontColor); ?>"><?php _e($license_status_message, $namespace); ?></div>

        <?php if ($license_status=='expired'): ?>
          <div>License expired:&nbsp;<span style="color:red"><?php _e($formatted_expired_date, $namespace); ?></span></div>
        <?php endif; ?>

        <?php if ($license_active) : ?>
          <div class="submit"><input type="submit" name="Submit" class="button-secondary" value="<?php _e("Update", $namespace) ?>" /></div>
        <?php endif; ?>

        <?php if (!$license_active) : ?>
          <p class="submit"><input type="submit" name="Submit" class="button-secondary" value="<?php _e("Activate", $namespace) ?>" /></p>
        <?php endif; ?>

        <?php if ($license_status=='invalid' || $license_status==''): ?>
          <p>Purchase a <?php echo($this->get_anchor_with_utm('no-risk','pricing-purchase','license','no+risk'))?>  license using the purchase link above.</p>
        <?php endif; ?>

        <?php if ($license_status=='expired'): ?>
          <div>License expired? <?php echo($this->get_anchor_with_utm('Renew Now ','documentation/faqs/expired-license','license','license+expired'))?> and save 20%.</div>
          <div>* Offer valid for a limited time!</div>
        <?php endif; ?>
        </form>
      </div>

    <!-- Display links widget -->
    <div class="widget">
          <h3 class="promo"><?php _e('Useful Links', $namespace); ?></h3>
          <ul>
              <?php if ($license_active) : ?>
                  <li><?php echo($this->get_anchor_with_utm('Your account','your-account','useful+links','your+account'))?></li>
                  <li><?php echo($this->get_anchor_with_utm('Upgrade your license','pricing-purchase','useful+links','upgrade+license'))?></li>
              <?php endif; ?>
              <li><?php echo($this->get_anchor_with_utm('Documentation','documentation','useful+links','help'))?></li>

              <?php if ($license_active || $is_lite_registered) : ?>
                  <li><?php echo($this->get_anchor_with_utm('Get support','support' ,'useful+links','get+support'))?></li>
              <?php endif; ?>

              <li><?php echo($this->get_anchor_with_utm('Feature request','feature-request' ,'useful+links','feature+request'))?></li>
              <li>Have a suggestion? Why not submit a feature request.</li>
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
	        <h2>WP BackItUp Backup Set</h2>
	        <p>Below are the archive files included in this backup set. Click the link to download.</p>
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
        <h2>WP BackItUp Backup Set</h2>
        <p>Please refresh this page to download your new backup files.</p>
    </div>
</span>
<!--End File download lists-->