<script type="text/javascript">var __namespace = '<?php echo $namespace; ?>';</script>
<div class="wrap">
    <div id="wp-backitup-icon" class="icon32"><img src="<?php echo plugin_dir_url(dirname(__FILE__) ); ?>images/icon32.png" alt="WP Backitup Icon" height="32" width="32" /></div>
    <h2><?php echo $page_title; ?></h2>
    <div id="content">
        <h3><?php _e('Backup', $namespace );?></h3>
        <p><?php _e('Create a backup file of this site\'s content and settings', $namespace ) ;?></p>
        <p><a href="#" class="backup-button button-primary"><?php _e( "Backup", $namespace ) ?></a><img class="backup-icon status-icon" src="<?php echo WPBACKITUP_URLPATH. "/images/loader.gif"; ?>" height="16" width="16" /></p>
        <h3><?php _e('Download', $namespace );?></h3>
        <p id="download-link"></p>
        
        <!--Disable restoration form if the user has not activated-->
        <?php $status = $this->get_option( 'status' );
            if( $status !== false && $status == 'valid' ) { ?>
        <h3><?php _e('Restore', $namespace );?></h3>
        <iframe id="upload_target" name="upload_target" src=""></iframe>
        <p><?php _e('Restore a WP Backitup zip file and overwrite this site\'s content, themes, plugins, uploads and settings', $namespace );?></p>
        <?php $max_upload = (int)(ini_get('upload_max_filesize'));
            $max_post = (int)(ini_get('post_max_size'));
            $memory_limit = (int)(ini_get('memory_limit'));
            $upload_mb = min($max_upload, $max_post, $memory_limit); ?>
        <p><?php _e( 'The maximum filesize you can upload is ', $namespace ); 
            echo $upload_mb .'MB.'; ?>
        </p>
        <form id="restore-form" method="post" enctype="multipart/form-data" action="<?php echo WPBACKITUP_URLPATH .'/lib/includes/restore.php'; ?>"> 
           <?php global $current_user; ?>
            <input type="hidden" name="user_id" value="<?php echo $current_user->ID; ?>" />
            <input type="hidden" name="maximum" id="maximum" value="<?php echo $upload_mb; ?>" />
            <p><input name="wpbackitup-zip" id="wpbackitup-zip" type="file" /></p> 
            <p><input type="submit" class="restore-button button-primary" name="restore" value="<?php _e( "Restore", $namespace ) ?>" /><img class="restore-icon status-icon" src="<?php echo WPBACKITUP_URLPATH. "/images/loader.gif"; ?>" height="16" width="16" /></p>
        </form>
        <?php } ?>
        <!--End of restoration form-->
        
        <h3><?php _e('Status', $namespace );?></h3>
        <div id="status">

            <!--default status message-->
            <ul class="default-status">
                <li><?php _e('Nothing to report', $namespace );?></li>
            </ul>

            <!--backup status messages-->
            <ul class="backup-status">
                <li class='prerequisites'><?php _e('Preparing to backup', $namespace );?>...<span class='status'><?php _e('Done', $namespace );?></span><span class='fail error'><?php _e('Failed', $namespace );?></span></li>
                <li class='backupdb'><?php _e('Backing-up database', $namespace );?>...<span class='status'><?php _e('Done', $namespace );?></span><span class='fail error'><?php _e('Failed', $namespace );?></span></li>
                <li class='backupfiles'><?php _e('Backing-up /wp-content/', $namespace );?>...<span class='status'><?php _e('Done', $namespace );?></span><span class='fail error'><?php _e('Failed', $namespace );?></span></li>
                <li class='infofile'><?php _e('Creating backup information file', $namespace );?>...<span class='status'><?php _e('Done', $namespace );?></span><span class='fail error'><?php _e('Failed', $namespace );?></span></li>
                <li class='zipfile'><?php _e('Zipping backup directory', $namespace );?>...<span class='status'><?php _e('Done', $namespace );?></span><span class='fail error'><?php _e('Failed', $namespace );?></span></li>
                <li class='cleanup'><?php _e('Cleaning up', $namespace );?>...<span class='status'><?php _e('Done', $namespace );?></span><span class='fail error'><?php _e('Failed', $namespace );?></span></li>
                <li class='finalinfo'><span class='status'><?php _e('Backup file created successfully. You can download your backup file using the link above', $namespace ); ?></span></li>
            </ul>

            <!--backup error messages-->
            <ul class="backup-errors">
                <li class="error101"><span class='status error'><?php _e('Error: Unable to create new directory for backup. Please check your CHMOD settings of your wp-backitup plugin directory' , $namespace ); ?>.</span></li>
                <li class="error102"><span class='status error'><?php _e('Error: Cannot create backup directory. Please check the CHMOD settings of your wp-backitup plugin directory', $namespace ); ?>.</span></li>
                <li class="error103"><span class='status error'><?php _e('Error: Unable to backup your files. Please try again', $namespace ); ?>.</span></li>
                <li class="error104"><span class='status error'><?php _e('Error: Unable to backup your database. Please try again', $namespace ); ?>.</span></li>
                <li class="error114"><span class='status error'><?php _e('Error: Your database was accesible but a dump could not be created. Please contact support by clicking the link on the right, stating your web host when you submit the form.', $namespace ); ?>.</span></li>
                <li class="error105"><span class='status error'><?php _e('Error: Unable to create site information file. Please try again', $namespace ); ?>.</span></li>
                <li class="error106"><span class='status error'><?php _e('Warning: Unable to cleanup your backup directory', $namespace ); ?>.</span></li>
            </ul>

            <!--restore status messages-->
            <ul class="restore-status">
                <li class="upload"><?php _e('Uploading restoration zip', $namespace );?>...<span class='status'><?php _e('Done', $namespace );?></span><span class='fail error'><?php _e('Failed', $namespace );?></span></li>
                <li class="unzipping"><?php _e('Unzipping', $namespace );?>...<span class='status'><?php _e('Done', $namespace );?></span><span class='fail error'><?php _e('Failed', $namespace );?></span></li>
                <li class="validation"><?php _e('Validating restoration zip', $namespace );?>...<span class='status'><?php _e('Done', $namespace );?></span><span class='fail error'><?php _e('Failed', $namespace );?></span></li>
                <!--<li class="restore_point"><?php _e('Setting checkpoint', $namespace );?>...<span class='status'><?php _e('Done', $namespace );?></span><span class='fail error'><?php _e('Failed', $namespace );?></span></li>-->
                <li class="database"><?php _e('Importing database', $namespace );?>...<span class='status'><?php _e('Done', $namespace );?></span><span class='fail error'><?php _e('Failed', $namespace );?></span></li>
                <li class="wpcontent"><?php _e('Importing /wp-content/ directory', $namespace );?>...<span class='status'><?php _e('Done', $namespace );?></span><span class='fail error'><?php _e('Failed', $namespace );?></span></li>
                <li class="cleanup"><?php _e('Cleaning up', $namespace );?>...<span class='status'><?php _e('Done', $namespace );?></span><span class='fail error'><?php _e('Failed', $namespace );?></span></li>
                <li class='finalinfo'><span class='status'><?php _e('Restoration completed successfully. Please refresh the page and login to the site again (with your current username and password)', $namespace ); ?></span></li>
            </ul>

            <!--restore error messages-->
            <ul class="restore-errors">
                <li class="error201"><span class='status error'><?php _e('Error: No file selected', $namespace ); ?>.</span></li>
                <li class="error202"><span class='status error'><?php _e('Error: Your file could not be uploaded', $namespace ); ?>.</span></li>
                <li class="error203"><span class='status error'><?php _e('Error: Your restoration file could not be unzipped', $namespace ); ?>.</span></li>
                <li class="error204"><span class='status error'><?php _e('Error: Your zip file appears to be invalid. Please ensure you chose the correct zip file', $namespace ); ?>.</span></li>
                <li class="error205"><span class='status error'><?php _e('Error: Cannot create restore point', $namespace ); ?>.</span></li>
                <li class="error206"><span class='status error'><?php _e('Error: Unable to connect to your database', $namespace ); ?>.</span></li>
                <li class="error207"><span class='status error'><?php _e('Error: Unable to get current site URL from database. Please try again', $namespace ); ?>.</span></li>
                <li class="error208"><span class='status error'><?php _e('Error: Unable to get current home URL from database. Please try again', $namespace ); ?>.</span></li>
                <li class="error209"><span class='status error'><?php _e('Error: Unable to get current user ID from database. Please try again', $namespace ); ?>.</span></li>
                <li class="error210"><span class='status error'><?php _e('Error: Unable to get current user password from database. Please try again', $namespace ); ?>.</span></li>
                <li class="error211"><span class='status error'><?php _e('Error: Unable to get current user email from database. Please try again', $namespace ); ?>.</span></li>
                <li class="error212"><span class='status error'><?php _e('Error: Unable to get import your database. This may require importing the file manually', $namespace ); ?>.</span></li>
                <li class="error213"><span class='status error'><?php _e('Error: Unable to update your current site URL value. This may require importing the file manually', $namespace ); ?>.</span></li>
                <li class="error214"><span class='status error'><?php _e('Error: Unable to update your current home URL value. This may require importing the file manually', $namespace ); ?>.</span></li>
                <li class="error215"><span class='status error'><?php _e('Error: Unable to update your user information. This may require importing the file manually', $namespace ); ?>.</span></li>
                <li class="error216"><span class='status error'><?php _e('Error: Warning: Database not detected in import file', $namespace ); ?>.</span></li>
                <li class="error217"><span class='status error'><?php _e('Error: Unable to remove existing wp-content directory for import. Please check your CHMOD settings in /wp-content/', $namespace ); ?>.</span></li>
                <li class="error218"><span class='status error'><?php _e('Error: Unable to create new wp-content directory for import. Please check your CHMOD settings in /wp-content/', $namespace ); ?>.</span></li>
                <li class="error219"><span class='status error'><?php _e('Error: Unable to import wp-content. Please try again', $namespace ); ?>.</span></li>
                <li class="error220"><span class='status error'><?php _e('Warning: Unable to cleanup import directory', $namespace ); ?>.</span></li>
            </ul>
        </div>   
        <?php if (site_url() == 'http://localhost/wpbackitup') {
            echo '<p><div id="php">PHP messages here</p></div>'; 
        } ?>
    </div>

    <div id="sidebar">

        <!-- Display opt-in form if the user is unregistered -->
        <?php $license = $this->get_option( 'license_key' );
                $status = $this->get_option( 'status' );
                if( $status != 'valid' ) { ?>
                    <div class="widget">
                        <h3 class="promo"><?php _e('Get a license key', $namespace ); ?></h3>
                        <p><?php _e('Restore your backups from Wordpress in minutes or your money back', $namespace ); ?>.</p>
                        <a href="http://www.wpbackitup.com/plugins/wp-backitup-pro/"><?php _e('Purchase a license key for WP Backitup', $namespace ); ?></a>
                    </div>
                <?php } ?>

        <?php /*<div class="widget">
            <h3 class="promo"><?php _e('Site Information', $namespace ); ?></h3>
            <p>Backup Size: <?php echo formatFileSize(totalSize(WPBACKITUP_CONTENT_PATH) ); ?></p>
            <p>WP Backitup is not recommended for sites larger than 50MB. Why not try <a href="http://ithemes.com/purchase/backupbuddy/">Backup Buddy</a>?</p>
        </div> */ ?>

        <!-- Display license key widget -->
        <form action="" method="post" id="<?php echo $namespace; ?>-form">
        <?php wp_nonce_field( $namespace . "-update-options" ); ?>
        <div class="widget">
            <h3 class="promo"><?php _e('License Key', $namespace ); ?></h3>
            <?php $license = $this->get_option( 'license_key' );
                $status = $this->get_option( 'status' );
                if( $status != 'valid' ) { ?>
                    <p><?php _e('Enter your license key to activate restoration functionality', $namespace ); ?>.</p>
                <?php } ?>
                <p><input type="text" name="data[license_key]" id="license_key" value="<?php echo $license; ?>" />
                <?php if( false !== $license ) { 
                    if( $status !== false && $status == 'valid' ) { ?>
                        <span style="color:green;"><?php _e('Active', $namespace); ?></span></p>
                        <p class="submit"><input type="submit" name="Submit" class="button-secondary" value="<?php _e( "Update", $namespace ) ?>" /></p>
                        <p><a href="http://www.wpbackitup.com/plugins/wp-backitup-pro/"><?php _e('Upgrade your license',$namespace); ?></a></p>
                    <?php } else { ?>
                        <span style="color:red;"><?php _e('Inactive', $namespace); ?></span></p>
                        <p class="submit"><input type="submit" name="Submit" class="button-secondary" value="<?php _e( "Activate", $namespace ) ?>" /></p>
                        <p><?php _e('Purchase a no-risk license using the link above',$namespace); ?>.</p>
                    <?php } 
                } ?>
        </div>             
        
        <!-- Display links widget -->
        <div class="widget">
            <h3 class="promo"><?php _e('Useful Links', $namespace ); ?></h3>
            <ul>
                <?php if( false !== $license ) { 
                    if( $status !== false && $status == 'valid' ) { ?>
                        <li><a href="http://www.wpbackitup.com/your-account/"><?php _e('Your account',$namespace); ?></a></li>
                        <li><a href="http://www.wpbackitup.com/plugins/wp-backitup-pro/"><?php _e('Upgrade your license',$namespace); ?></a></li>
                    <?php }
                } ?>
                <li><a href="http://wordpress.org/support/plugin/wp-backitup"><?php _e('Get support',$namespace); ?></a></li>
            </ul>
        </div>

        <div class="widget">
            <h3 class="promo">Presstrends</h3>
                <p><input type="radio" name="data[presstrends]" value="enabled" <?php if($this->get_option( 'presstrends' ) == 'enabled') echo 'checked'; ?>> <label><?php _e('Enable', $namespace ); ?></label></p>
                <p><input type="radio" name="data[presstrends]" value="disabled" <?php if($this->get_option( 'presstrends' ) == 'disabled') echo 'checked'; ?>> <label><?php _e('Disable', $namespace ); ?></label></p>
                <p><?php _e('Help to improve Easy Webtrends by enabling', $namespace ); ?> <a href="http://www.presstrends.io" target="_blank">Presstrends</a>.</p>
                <p class="submit"><input type="submit" name="Submit" class="button-primary" value="<?php _e( "Save", $namespace ) ?>" /></p>
        </div>
        </form>
    </div>
</div>