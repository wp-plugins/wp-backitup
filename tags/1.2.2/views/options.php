<script type="text/javascript" src="http://code.jquery.com/jquery.js"></script>
<div class="wrap">
    <div id="wp-backitup-icon" class="icon32"><img src="<?php echo plugin_dir_url(dirname(__FILE__) ); ?>images/icon32.png" alt="WP Backitup Icon" height="32" width="32" /></div>
    <h2><?php echo $page_title; ?></h2>
    <div id="content">
        <h3><?php _e('Backup', $namespace );?></h3>
        <p><?php _e('Create a backup file of this site\'s content and settings.', $namespace ) ;?></p>
        <p><a href="#" class="backup-button button-primary"><?php _e( "Backup", $namespace ) ?></a><img class="backup-icon status-icon" src="<?php echo WPBACKITUP_URLPATH. "/images/loader.gif"; ?>" height="16" width="16" /></p>
        <h3><?php _e('Download', $namespace );?></h3>
        <p id="download-link"></p>
        <h3><?php _e('Status', $namespace );?></h3>
        <p><div id="status"><?php _e('Nothing to report', $namespace );?></div></p>
        <?php if (site_url() == 'http://localhost/wpbackitup') {
            echo '<p><div id="php">PHP messages here</div></p>'; 
        } ?>
    </div>
    <div id="sidebar">
        <div class="widget" id="restore-widget">
            <h3 class="promo">Go Pro!</h3>
            <p>Upgrade to <a href="http://www.wpbackitup.com/wp-backitup-pro/">WP Backitup Pro</a> and restore your backups with a few simple clicks!</p>
            <p><a class="button-primary" href="http://www.wpbackitup.com/wp-backitup-pro/">Read more</a></p>
        </div>
        <div class="widget">
            <h3 class="promo">Need support?</h3>
            <p>If you are having problems with this plugin please talk about them in the <a href="http://wordpress.org/support/plugin/wp-backitup">support forum</a>.</p>
        </div>
        <div class="widget">
            <h3 class="promo">Spread the Word!</h3>
            <p>Why not <a href="http://wordpress.org/extend/plugins/wp-backitup/">rate the plugin 5&#9733; on Wordpress.org</a>?</p>
        </div>
        <div class="widget">
            <h3 class="promo">Presstrends</h3>
            <form action="" method="post" id="<?php echo $namespace; ?>-form">
            <?php wp_nonce_field( $namespace . "-update-options" ); ?>
            <p><input type="radio" name="data[presstrends]" value="enabled" <?php if($this->get_option( 'presstrends' ) == 'enabled') echo 'checked'; ?>> <label>Enable</label></p>
        <p><input type="radio" name="data[presstrends]" value="disabled" <?php if($this->get_option( 'presstrends' ) == 'disabled') echo 'checked'; ?>> <label>Disable</label></p>
            <p>Help to improve Easy Webtrends by enabling <a href="http://www.presstrends.io" target="_blank">Presstrends</a>.</p>
            <p class="submit"><input type="submit" name="Submit" class="button-primary" value="<?php _e( "Save", $namespace ) ?>" /></p>
            </form>
        </div>
    </div>
</div>