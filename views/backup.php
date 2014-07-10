<?php if (!defined ('ABSPATH')) die('No direct access allowed');

      $page_title = $this->friendly_name . ' Backup';
      $namespace = $this->namespace;

      //Path Variables
      $backup_folder_root = WPBACKITUP__BACKUP_PATH;

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
      $retain_archives = $this->backup_retained_number();

      $lite_registration_email = $this->lite_registration_email();
      $is_lite_registered = $this->is_lite_registered();

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

?>

<?php

//Fatal Error - no backup folder
if (!$backup_folder_exists) {
    echo '<div class="error"><p><strong>Error: Backup folder does not exist. Please contact ';
    echo($this->get_anchor_with_utm('support','support','backup+error','no+backup+folder'));
    echo ' for assistance.</strong></p></div>';
}
?>

<script type="text/javascript">var __namespace = "<?php echo($namespace); ?>";</script>
<div class="wrap">
  <h2><?php echo $page_title; ?></h2>
  <div id="content">
    <div class="widget">
      <h3><i class="fa fa-cogs"></i> <?php _e('Backup', $namespace); ?></h3>
      <p><b>Click the backup button to create a zipped backup file of this site's database, plugins, themes and settings.</b></p>
      <p>Once your backup file has been created it will appear in the available backups section below. This file can remain on your hosting providers server but we recommend that you download and save it somewhere safe.</p>
      <p> Licensed WP BackItUp users can use these backup files to perform an automated restore of their site.</p>        
      <p>
          <?php if ($backup_folder_exists) :?>
            <input type="submit" id="backup-button" class="backup-button button-primary" value="<?php _e("Backup", $namespace) ?>"/><img class="backup-icon status-icon" src="<?php echo WPBACKITUP__PLUGIN_URL . "/images/loader.gif"; ?>" height="16" width="16" /></p>
          <?php endif; ?>
      <?php
      //Display a note for lite customers
      if (!$license_active)
        echo '<p> * WP BackItUp Lite customers may use these backup files to manually restore their site.  Please visit ' .$this->get_anchor_with_utm(WPBACKITUP__SITE_URL,'documentation/restore/how-to-manually-restore-your-wordpress-database','backup','manual+restore') .' for manual restore instructions.</p>';
      ?>
    </div>

    <!--Available Backups section-->
    <div class="widget">
      <h3><i class="fa fa-cloud-download"></i> <?php _e('Available Backups', $namespace); ?></h3>

    <!--View Log Form-->
    <form id = "viewlog" name = "viewlog" action="admin-post.php" method="post">
        <input type="hidden" name="action" value="viewlog">
        <input type="hidden" id="viewlog_log" name="viewlog_log" value="test">
        <?php wp_nonce_field($this->namespace . "-viewlog"); ?>
    </form>

      <table class="widefat" id="datatable">
        <?php
        
        //Get Zip File List       
        $zip_filelist = glob($backup_folder_root . "/*.zip");
        $log_filelist = glob($backup_folder_root . "/*.log");
    
        if (glob($backup_folder_root . "/*.zip"))
        {
          //Sort by Date Time     
          usort($zip_filelist, create_function('$a,$b', 'return filemtime($b) - filemtime($a);'));

          $i = 0;
          foreach ($zip_filelist as $zip_file)
          {
            if( $retain_archives && $retain_archives == $i)
              break;

            $zip_filename = basename($zip_file);
            //Local Date Time
            $zip_datetime = get_date_from_gmt(date('Y-m-d H:i:s', filemtime($zip_file)), 'Y-m-d g:i a'); 

            //Check for log
            $log_file = str_replace('.zip','.log',$zip_file);
            $log_filename = basename($log_file);

            $logExists=false;
            if (is_array($log_filelist) && in_array($log_file,$log_filelist)) $logExists=true;

            $class = $i % 2 == 0 ? 'class="alternate"' : '';
            ?>

            <tr <?php echo $class ?> id="row<?php echo $i; ?>">
              <td><?php echo $zip_datetime ?></td>
              <td><?php echo $zip_filename ?></td>
              <td><a href="<?php echo WPBACKITUP__BACKUP_URL ?>/<?php echo $zip_filename; ?>">Download</a></td>
              <?php if ($logExists) :?>
                <td><a class='viewloglink' href="<?php echo basename($log_filename, ".log") ?>">View Log</a></td>
              <?php else: ?>
                <td>&nbsp;</td>
              <?php endif; ?>
               <td><a href="#" title="<?php echo $zip_filename; ?>" class="deleteRow" id="deleteRow<?php echo $i; ?>">Delete</a></td>

            </tr>

            <?php
              $i++;
          }
        }
        else
        {
          echo '<tr id="nofiles"><td colspan="3">No backup archives available for download.</td></tr>';
        }
        ?>
      </table>  

      <?php
      //Display restore note for lite customers
      if (!$license_active)
        echo '<p>* The automated restore feature is only available to licensed WP BackItUp customers.  Please visit ' .$this->get_anchor_with_utm(WPBACKITUP__SITE_URL,'pricing-purchase','available+backups','risk+free') . ' to get WP BackItUp risk free for 30 days.</p>';
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
        <li class="preparing"><?php _e('Preparing for backup', $namespace); ?>...<span class='status-icon'><img class="preparing-icon" src="<?php echo WPBACKITUP__PLUGIN_URL . "/images/loader.gif"; ?>" height="16" width="16" /></span><span class='status'><?php _e('Done', $namespace); ?></span><span class='fail error'><?php _e('Failed', $namespace); ?></span></li>
        <li class='backupdb'><?php _e('Backing-up database', $namespace); ?>...<span class='status-icon'><img class="backupdb-icon" src="<?php echo WPBACKITUP__PLUGIN_URL . "/images/loader.gif"; ?>" height="16" width="16" /></span><span class='status'><?php _e('Done', $namespace); ?></span><span class='fail error'><?php _e('Failed', $namespace); ?></span></li>
        <li class='infofile'><?php _e('Creating backup information file', $namespace); ?>...<span class='status-icon'><img class="infofile-icon" src="<?php echo WPBACKITUP__PLUGIN_URL . "/images/loader.gif"; ?>" height="16" width="16" /></span><span class='status'><?php _e('Done', $namespace); ?></span><span class='fail error'><?php _e('Failed', $namespace); ?></span></li>
        <li class='backupfiles'><?php _e('Backing up plugins, themes, and uploads', $namespace); ?>...<span class='status-icon'><img class="backupfiles-icon" src="<?php echo WPBACKITUP__PLUGIN_URL . "/images/loader.gif"; ?>" height="16" width="16" /></span><span class='status'><?php _e('Done', $namespace); ?></span><span class='fail error'><?php _e('Failed', $namespace); ?></span></li>
        <li class='zipfile'><?php _e('Zipping backup directory', $namespace); ?>...<span class='status-icon'><img class="zipfile-icon" src="<?php echo WPBACKITUP__PLUGIN_URL . "/images/loader.gif"; ?>" height="16" width="16" /></span><span class='status'><?php _e('Done', $namespace); ?></span><span class='fail error'><?php _e('Failed', $namespace); ?></span></li>
        <li class='cleanup'><?php _e('Cleaning up', $namespace); ?>...<span class='status-icon'><img class="cleanup-icon" src="<?php echo WPBACKITUP__PLUGIN_URL . "/images/loader.gif"; ?>" height="16" width="16" /></span><span class='status'><?php _e('Done', $namespace); ?></span><span class='fail error'><?php _e('Failed', $namespace); ?></span></li>
      </ul>

      <!--backup error messages-->
      <div class="backup-errors">
        <span class="error101"><div class='isa_error'><?php _e('Error 101: Unable to create a new directory for backup. Please check your CHMOD settings of your wp-backitup backup directory', $namespace); ?>.</div></span>
        <span class="error102"><div class='isa_error'><?php _e('Error 102: Cannot create backup directory. Please check the CHMOD settings of your wp-backitup plugin directory', $namespace); ?>.</div></span>
        <span class="error103"><div class='isa_error'><?php _e('Error 103: Unable to backup your files. Please try again', $namespace); ?>.</div></span>
        <span class="error104"><div class='isa_error'><?php _e('Error 104: Unable to backup your database. Please try again', $namespace); ?>.</div></span>
        <span class="error105"><div class='isa_error'><?php _e('Error 105: Unable to create site information file. Please try again', $namespace); ?>.</div></span>
        <span class="error106"><div class='isa_warning'><?php _e('Warning 106: Unable to cleanup your backup directory', $namespace); ?>.</div></span>
        
        <span class="error114"><div class='isa_error'><?php _e('Error 114: Your database was accessible but an export could not be created. Please contact support by clicking the get support link on the right. Please let us know who your host is when you submit the request', $namespace); ?>.</div></span>
      </div>

      <!--Upload status messages-->
      <ul class="backup-unexpected-error">
          <span class='error999'><div class='isa_error'><?php _e('An unexpected error has occurred. ', $namespace); ?></div></span>
      </ul>

       <!--success messages-->
      <div class="backup-success">
        <span class='finalinfo'><div class='isa_success'><?php _e('Backup completed successfully. ', $namespace); ?></div></span>
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



  <div id="sidebar">
    <!-- Display opt-in form if the user is unregistered -->
    <?php if (!$license_active) : ?>
        <?php if (!$is_lite_registered) : ?>
            <form action="" method="post" id="<?php echo $namespace; ?>-form">
              <?php wp_nonce_field($namespace . "-register-lite"); ?>
            <div class="widget">
                <h3 class="promo"><?php _e('Register WP BackItUp', $namespace); ?></h3>
                <p><?php _e('Enter your email address to register your version of WP BackItUp.  Registered users will receive <b>special offers</b> and access to our world class <b>support</b> team.', $namespace); ?></p>
                <input type="text" name="email" id="email" placeholder="email address" value="<?php echo($lite_registration_email) ?>" />
                <div class="submit"><input type="submit" name="Submit" class="button-secondary" value="<?php _e("Register", $namespace) ?>" /></div>
            </div>
           </form>
       <?php else : ?>
          <div class="widget">
            <h3 class="promo"><?php _e('Get a license', $namespace); ?></h3>
            <p><?php _e('Tired of messing with FTP, MySQL and PHPMyAdmin? Restore your backups from this page in minutes or your money back', $namespace); ?>.</p>
            <?php echo($this->get_anchor_with_utm('Purchase a license for WP BackItUp','pricing-purchase','get+license','purchase')) ?>
          </div>
      <?php endif ?>
    <?php endif; ?>


    <!-- Display license key widget -->
    <form action="" method="post" id="<?php echo $namespace; ?>-form">
      <?php wp_nonce_field($namespace . "-update-options"); ?>
      <div class="widget">
        <h3 class="promo"><?php _e('License v ' . $version, $namespace); ?></h3>
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
    </form>

  </div>
</div>

