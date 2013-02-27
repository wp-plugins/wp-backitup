<div class="wrap">
    <div id="wp-backitup-icon" class="icon32"><img src="<?php echo plugin_dir_url(dirname(__FILE__) ); ?>images/icon32.png" alt="WP Backitup Icon" height="32" width="32" /></div>
    <h2><?php echo $page_title; ?></h2>
    <div id="content">
        <h3><?php _e('Backup', $this->namespace ); ?></h3>
        <p><?php _e('Create a backup file of this site\'s content and settings.', $this->namespace ); ?></p>
        <p><a href="#" class="backup-button button-primary"><?php _e( "Backup", $this->namespace ); ?> ) ?></a><img class="backup-icon status-icon" src="<?php echo WPBACKITUP_URLPATH. "/images/loader.gif"; ?>" height="16" width="16" /></p>
        <h3>Download</h3>
        <p><div id="download-link"></div></p>
        <h3>Status</h3>
        <p><div id="status">Nothing to report</div></p>
    </div>
    <div id="sidebar">
        <div class="widget" id="restore-widget">
            <h3 class="promo">Restore Your Backups</h3>
            <p>You can restore your backup zips by importing them manually or by getting WP Backitup Pro.</p>
            <p><a class="button-primary" href="http://www.wpbackitup.com/wp-backitup-pro/">Read more</a></p>
        </div>
        <div class="widget">
            <h3 class="promo">Need support?</h3>
            <p>If you are having problems with this plugin please talk about them in the <a href="http://wordpress.org/support/plugin/wp-backitup">support forum</a>.</p>
            <p>You can also refer to the <a href="http://www.wpbackitup.com/documentation/">WP Backitup documentation</a>.</p>
        </div>
        <div class="widget">
            <h3 class="promo">Spread the Word!</h3>
            <p>Want to help make WP Backitup even better? All donations are used to improve this plugin, so donate $10, $20 or $50 now!</p>
            <p><form action="https://www.paypal.com/cgi-bin/webscr" method="post">
                <input type="hidden" name="cmd" value="_s-xclick">
                <input type="hidden" name="hosted_button_id" value="QSHPK8EDMAW9N">
                <input type="image" src="https://www.paypalobjects.com/en_US/GB/i/btn/btn_donateCC_LG.gif" border="0" name="submit" alt="PayPal — The safer, easier way to pay online.">
                <img alt="" border="0" src="https://www.paypalobjects.com/en_GB/i/scr/pixel.gif" width="1" height="1">
                </form></p>
            <p>Or you could <a href="http://wordpress.org/extend/plugins/wp-backitup/">rate the plugin 5&#9733; on Wordpress.org</a>.</p>
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