<script type="text/javascript">var __namespace = '<?php echo $namespace; ?>';</script>
<div class="wrap">
    <div id="wp-backitup-icon" class="icon32"><img src="<?php echo plugin_dir_url(dirname(__FILE__) ); ?>images/icon32.png" alt="WP Backitup Icon" height="32" width="32" /></div>
    <h2>WPBackItUp Options</h2>
    <div id="content">

      <!--Backup section-->
      <div class="widget">
        <h3><?php _e('Backup', $namespace );?></h3>
        <p><?php _e('Click the backup button to create a zipped backup file of this site\'s database, plugins, themes and settings.', $namespace ) ;?></p>
        <p>
    		Once your backup file has been created it will appear in the available backups section below. This file can remain on your hosting providers server but we recommend that you download and save it somewhere safe.
        </p>
		<p> WPBackitUp Pro users can use these backup files to perform an automated restore of their site.</p>        
        <p><input type="submit" id="backup-button" class="backup-button button-primary"               value="<?php _e( "Backup", $namespace ) ?>"</><img class="backup-icon status-icon" src="<?php echo WPBACKITUP_URLPATH. "/images/loader.gif"; ?>" height="16" width="16" /></p>
          				
		<?php
		//Display a note for lite customers
		if(!license_active()) 
		{
			echo '<p> * WPBackItUp Lite customers may use these backup files to manually restore their site.  Please visit  <a href="http://www.wpbackitup.com/wp-backitup-pro/" target="_blank">www.wpbackitup.com</a> for manual restore instructions.</p>';
		}				
		?>
      </div>
	
	 <!--Available Backups section-->
	 <div class="widget">
	 <h3><?php _e('Available Backups', $namespace); ?></h3>
		<table class="widefat" id="datatable">
		  <?php
		
		  //Get Zip File List
		  $backup_folder_root = WPBACKITUP_CONTENT_PATH .WPBACKITUP_BACKUP_FOLDER .'/';
		  $zipFileList = glob($backup_folder_root ."*.zip");
		  
            //Sort by Date Time			
    	   usort($zipFileList, create_function('$a,$b', 'return filemtime($b) - filemtime($a);'));
		
		  if (glob($backup_folder_root ."*.zip"))
		  {
      		$i = 0;
		  	foreach ($zipFileList as $file)
      		{
      			$filename = basename($file);						
		  			$fileDateTime=get_date_from_gmt(date( 'Y-m-d H:i:s', filemtime($file)), 'Y-m-d g:i a' ); //Local Date Time
      			$class = $i % 2 == 0 ? 'class="alternate"' : '';
		  ?>
			<tr <?php echo $class ?> id="row<?php echo $i; ?>">
				<td><?php echo $filename?></td>
				<td><a href="<?php echo WPBACKITUP_BACKUPFILE_URLPATH ?>/<?php echo $filename; ?>">Download</a></td>
				<td><a href="#" title="<?php echo $filename; ?>" class="deleteRow" id="deleteRow<?php echo $i; ?>">Delete</a></td>
                <?php
                    if (license_active()) {
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

            <form id="restore-form" method="post" action="<?php echo WPBACKITUP_URLPATH . '/lib/includes/restore.php'; ?>"> 
                <?php global $current_user; ?>
                <input type="hidden" name="user_id" value="<?php echo $current_user->ID; ?>" />
                <input type="hidden" name="is_selected" id="is_selected" value="0" />
                <input type="hidden" name="selected_file" id="selected_file" value="" />
            </form>
		    
				<?php 
						//Display restore note for lite customers
						if (!license_active()) {
							 echo '<p>* The automated restore feature is only available to Pro customers.  Please visit <a href="http://www.wpbackitup.com/wp-backitup-pro/" target="_blank">www.wpbackitup.com</a> to get WPBackItUp Pro risk free for 30 days.</p>'	;
						}
				?>
		</div>		
		
				<!--Disable upload form if the user has not activated-->
        <?php 
						if( license_active()) 
						{ ?>
						<div class="widget">
							<h3>
								<?php _e('Upload', $namespace );?>
							</h3>
							<iframe id="upload_target" name="upload_target" src=""></iframe>
							<p>
								<?php _e('Upload a WP BackItUp zip file to add it to your list of available backups.', $namespace );?>
							</p>
							<?php
									$max_upload = (int)(ini_get('upload_max_filesize'));
									$max_post = (int)(ini_get('post_max_size'));
									$memory_limit = (int)(ini_get('memory_limit'));
									$upload_mb = min($max_upload, $max_post, $memory_limit);
							 ?>
							<p>
								<?php _e( 'The maximum file size your hosting provider allows you to upload is ', $namespace ); 
								echo $upload_mb .'MB.'; ?>
                            </p>
							<form id="upload-form" method="post" enctype="multipart/form-data" action="<?php echo WPBACKITUP_URLPATH . '/lib/includes/upload.php'; ?>">
								<p><input name="uploaded-zip" id="wpbackitup-zip" type="file" /></p> 
								<p><input type="submit" class="restore-button button-primary" name="Upload" id="upload-button" value="<?php _e("Upload", $namespace) ?>" /><img class="upload-icon status-icon" src="<?php echo WPBACKITUP_URLPATH . "/images/loader.gif"; ?>" height="16" width="16" /></p>
							</form>
						</div>
        <?php } ?>
      <!--End of Upload form-->


      <div id="status" class="widget">
        <h3><?php _e('Status', $namespace );?></h3>        

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

            <!--Upload status messages-->
            <ul class="upload-status">
                <li><span class='upload-status'></span></li>
            </ul>

            <!--restore status messages-->
            <ul class="restore-status">
                <li class="preparing"><?php _e('Preparing for restore', $namespace );?>...<span class='status-icon'><img class="preparing-icon" src="<?php echo WPBACKITUP_URLPATH . "/images/loader.gif"; ?>" height="16" width="16" /></span><span class='status'><?php _e('Done', $namespace );?></span><span class='fail error'><?php _e('Failed', $namespace );?></span></li>
                <li class="unzipping"><?php _e('Unzipping backup file', $namespace );?>...<span class='status-icon'><img class="unzipping-icon" src="<?php echo WPBACKITUP_URLPATH . "/images/loader.gif"; ?>" height="16" width="16" /></span><span class='status'><?php _e('Done', $namespace );?></span><span class='fail error'><?php _e('Failed', $namespace );?></span></li>
                <li class="validation"><?php _e('Validating backup file', $namespace );?>...<span class='status-icon'><img class="validation-icon" src="<?php echo WPBACKITUP_URLPATH . "/images/loader.gif"; ?>" height="16" width="16" /></span><span class='status'><?php _e('Done', $namespace );?></span><span class='fail error'><?php _e('Failed', $namespace );?></span></li>
                <li class="restore_point"><?php _e('Creating checkpoint', $namespace );?>...<span class='status-icon'><img class="restore_point-icon" src="<?php echo WPBACKITUP_URLPATH . "/images/loader.gif"; ?>" height="16" width="16" /></span><span class='status'><?php _e('Done', $namespace );?></span><span class='fail error'><?php _e('Failed', $namespace );?></span></li>
                <li class="database"><?php _e('Restoring database', $namespace );?>...<span class='status-icon'><img class="database-icon" src="<?php echo WPBACKITUP_URLPATH . "/images/loader.gif"; ?>" height="16" width="16" /></span><span class='status'><?php _e('Done', $namespace );?></span><span class='fail error'><?php _e('Failed', $namespace );?></span></li>
                <li class="wpcontent"><?php _e('Restoring /wp-content/ directory', $namespace );?>...<span class='status-icon'><img class="wpcontent-icon" src="<?php echo WPBACKITUP_URLPATH . "/images/loader.gif"; ?>" height="16" width="16" /></span><span class='status'><?php _e('Done', $namespace );?></span><span class='fail error'><?php _e('Failed', $namespace );?></span></li>
                <li class="cleanup"><?php _e('Cleaning up restore files', $namespace );?>...<span class='status-icon'><img class="cleanup-icon" src="<?php echo WPBACKITUP_URLPATH . "/images/loader.gif"; ?>" height="16" width="16" /></span><span class='status'><?php _e('Done', $namespace );?></span><span class='fail error'><?php _e('Failed', $namespace );?></span></li>
            </ul>
            <p>

            <!--restore error messages-->
            <div class="restore-errors">
                <span class="error201"><div class='isa_error'><?php _e('Error: No file selected', $namespace ); ?>.</div></span>
                <span class="error202"><div class='isa_error'><?php _e('Error: Your file could not be uploaded', $namespace ); ?>.</div></span>
                <span class="error203"><div class='isa_error'><?php _e('Error: Your backup zip file could not be unzipped', $namespace ); ?>.</div></span>
                <span class="error204"><div class='isa_error'><?php _e('Error: Your backup zip file appears to be invalid. Please ensure you selected a valid backup zip file', $namespace ); ?>.</div></span>
                <span class="error205"><div class='isa_error'><?php _e('Error: Cannot create restore point', $namespace ); ?>.</div></span>
                <span class="error206"><div class='isa_error'><?php _e('Error: Unable to connect to your database', $namespace ); ?>.</div></span>
                <span class="error207"><div class='isa_error'><?php _e('Error: Unable to get current site URL from database. Please try again', $namespace ); ?>.</div></span>
                <span class="error208"><div class='isa_error'><?php _e('Error: Unable to get current home URL from database. Please try again', $namespace ); ?>.</div></span>
                <span class="error209"><div class='isa_error'><?php _e('Error: Unable to get current user ID from database. Please try again', $namespace ); ?>.</div></span>
                <span class="error210"><div class='isa_error'><?php _e('Error: Unable to get current user password from database. Please try again', $namespace ); ?>.</div></span>
                <span class="error211"><div class='isa_error'><?php _e('Error: Unable to get current user email from database. Please try again', $namespace ); ?>.</div></span>
                <span class="error212"><div class='isa_error'><?php _e('Error: Unable to import your database. This may require importing the file manually', $namespace ); ?>.</div></span>
                <span class="error213"><div class='isa_warning'><?php _e('Error: Unable to update your current site URL value. This may require importing the file manually', $namespace ); ?>.</div></span>
                <span class="error214"><div class='isa_warning'><?php _e('Error: Unable to update your current home URL value. This may require importing the file manually', $namespace ); ?>.</div></span>
                <span class="error215"><div class='isa_warning'><?php _e('Error: Unable to update your user information. This may require importing the file manually', $namespace ); ?>.</div></span>
                <span class="error216"><div class='isa_error'><?php _e('Error: Database not detected in import file', $namespace ); ?>.</div></span>
                <span class="error217"><div class='isa_warning'><?php _e('Error: Unable to remove existing wp-content directory for import. Please check your CHMOD settings in /wp-content/', $namespace ); ?>.</div></span>
                <span class="error218"><div class='isa_error'><?php _e('Error: Unable to create new wp-content directory for import. Please check your CHMOD settings in /wp-content/', $namespace ); ?>.</div></span>
                <span class="error219"><div class='isa_warning'><?php _e('Error: Unable to import wp-content. Please try again', $namespace ); ?>.</div></span>
                <span class="error220"><div class='isa_warning'><?php _e('Warning: Unable to cleanup import directory. No action is required.', $namespace ); ?>.</div></span>
                <span class="error221"><div class='isa_warning'><?php _e('Warning: Table prefix value in wp-config.php is different from backup. This MUST be corrected in your wp-config.php file before your site will function', $namespace ); ?>.</div></span>
                <span class='error222'><div class='isa_error'><?php _e('Error: Unable to create restore folder', $namespace ); ?>.</div></span>
                <span class='error223'><div class='isa_error'><?php _e('Error: An error occurred during the restore.  We attempted to restore the database to its previous state but were unsuccessful.  Please contact wpbackitup customer support and do not attempt to perform any further restores', $namespace ); ?>.</div></span>
                <span class='error224'><div class='isa_error'><?php _e('Error: An error occurred during the restore, however, we have successfully restored your database to the previous state', $namespace ); ?>.</div></span>
                <span class='error225'><div class='isa_error'><?php _e('Error: Restore option is only available to WP BackItUp Pro users', $namespace ); ?>.</div></span>
            </div>

            <!--restore success messages-->
            <div class="restore-success">
                <span class='finalinfo'><div class='isa_success'><?php _e('Restore completed successfully. Please refresh the page and login to the site again (with your current username and password)', $namespace ); ?></div></span>
            </div>                
 
        </div>   
        <?php 
            global $WPBACKITUP_DEBUG;
            if ($WPBACKITUP_DEBUG===true) {
            echo '<p><div id="php">Logging messages</p></div>'; 
        } ?>
    </div>

    <div id="sidebar">

        <!-- Display opt-in form if the user is unregistered -->
        <?php 
                if(!license_active()) { ?>
                    <div class="widget">
                        <h3 class="promo"><?php _e('Get a license key', $namespace ); ?></h3>
                        <p><?php _e('Tired of messing with FTP, MySQL and PHPMyAdmin? Restore your backups from this page in minutes or your money back', $namespace ); ?>.</p>
                        <a href="http://www.wpbackitup.com/plugins/wp-backitup-pro/" target="blank"><?php _e('Purchase a license key for WP Backitup Pro', $namespace ); ?></a>
                    </div>
                <?php } ?>

        <?php /*<div class="widget">
            <h3 class="promo"><?php _e('Site Information', $namespace ); ?></h3>
            <p>Backup Size: <?php echo formatFileSize(totalSize(WPBACKITUP_CONTENT_PATH) ); ?></p>
            <p>WP Backitup is not recommended for sites larger than 50MB.</p>
        </div> */ ?>

        <!-- Display license key widget -->
        <form action="" method="post" id="<?php echo $namespace; ?>-form">
        <?php wp_nonce_field( $namespace . "-update-options" ); ?>
        <div class="widget">
            <h3 class="promo"><?php _e('License Key v '.WPBACKITUP_VERSION, $namespace); ?></h3>
            <?php 
                $license = $this->get_option( 'license_key' );
                if(!license_active()) { ?>
                    <p><?php _e('Enter your license key to activate Pro features.', $namespace ); ?>.</p>
                <?php } ?>
                <p><input type="text" name="data[license_key]" id="license_key" value="<?php echo $license; ?>" />
                <?php 
                    if(license_active() ) { ?>
                        <span style="color:green;"><?php _e('Pro License Active', $namespace); ?></span></p>
                        <p class="submit"><input type="submit" name="Submit" class="button-secondary" value="<?php _e( "Update", $namespace ) ?>" /></p>
                    <?php } else { ?>
                        <span style="color:red;"><?php _e('Pro License Inactive', $namespace); ?></span></p>
                        <p class="submit"><input type="submit" name="Submit" class="button-secondary" value="<?php _e( "Activate", $namespace ) ?>" /></p>
                        <p><?php _e('Purchase a  <a href="http://www.wpbackitup.com/plugins/wp-backitup-pro/" target="blank">no-risk </a>license using the purchase link above',$namespace); ?>.</p>
                    <?php } 
                ?>
        </div>             
        
        <!-- Display links widget -->
        <div class="widget">
            <h3 class="promo"><?php _e('Useful Links', $namespace ); ?></h3>
            <ul>
                <?php
                    if(license_active()) { ?>
                        <li><a href="http://www.wpbackitup.com/your-account/" target="_blank"><?php _e('Your account',$namespace); ?></a></li>
                        <li><a href="http://www.wpbackitup.com/plugins/wp-backitup-pro/" target="_blank"><?php _e('Upgrade your license',$namespace); ?></a></li>
                    <?php }
                ?>
                <li><a href="http://www.wpbackitup.com/support" target="_blank"><?php _e('Get support',$namespace); ?></a></li>
								<li><a href="http://www.wpbackitup.com/feature-request" target="_blank"><?php _e('Feature Request',$namespace); ?></a></li>
								<li>Have a suggestion? Why not submit a feature request.</li>
            </ul>
        </div>

        <div class="widget">
            <h3 class="promo">Turn on logging?</h3>
                <p><input type="radio" name="data[logging]" value="enabled" <?php if($this->get_option( 'logging' ) == 'enabled') echo 'checked'; ?>> <label><?php _e('Yes', $namespace ); ?></label></p>
                <p><input type="radio" name="data[logging]" value="disabled" <?php if($this->get_option( 'logging' ) == 'disabled') echo 'checked'; ?>> <label><?php _e('No', $namespace ); ?></label></p>
                <p><?php _e('This option should only be turned on when troubleshooting issues with WPBackItUp support.', $namespace ); ?></p>
                <p class="submit"><input type="submit" name="Submit" class="button-secondary" value="<?php _e( "Save", $namespace ) ?>" /></p>
        </div>

        <!--
        <div class="widget">
            <h3 class="promo">Allow Usage Tracking?</h3>
                <p><input type="radio" name="data[presstrends]" value="enabled" <?php if($this->get_option( 'presstrends' ) == 'enabled') echo 'checked'; ?>> <label><?php _e('Yes', $namespace ); ?></label></p>
                <p><input type="radio" name="data[presstrends]" value="disabled" <?php if($this->get_option( 'presstrends' ) == 'disabled') echo 'checked'; ?>> <label><?php _e('No', $namespace ); ?></label></p>
                <p><?php _e('Allow WPBackItUp to track how this plugin is used so we can make it better. We only track usage data related to this plugin and will never share this data.', $namespace ); ?></p>
                <p class="submit"><input type="submit" name="Submit" class="button-secondary" value="<?php _e( "Save", $namespace ) ?>" /></p>
        </div>
        -->
        </form>
    </div>
</div>