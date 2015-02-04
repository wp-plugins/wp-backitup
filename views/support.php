<?php if (!defined ('ABSPATH')) die('No direct access allowed');

/**
 * WP BackItUp  - Support View
 *
 * @package WP BackItUp
 * @author  Chris Simmons <chris.simmons@wpbackitup.com>
 * @link    http://www.wpbackitup.com
 *
 */

    $page_title = $this->friendly_name . ' Support';
    $namespace = $this->namespace;

    $license_active = $this->license_active();
    $is_lite_registered = $this->is_lite_registered();

	$support_email =$this->support_email();

	$disabled='';
//    if (!$license_active && !$is_lite_registered){
//        $disabled='disabled';
//    }

?>
<?php if (!empty($_GET["s"]) && '1' == $_GET["s"]) : ?>
	<div class="updated">
		<p><?php _e( 'Support email sent successfully!', $namespace ); ?></p>
	</div>
<?php endif; ?>

<div class="wrap">
  <h2><?php echo $page_title; ?></h2>
  <div id="content">


        <!-- Display Settings widget -->
        <form action="admin-post.php" method="post" id="<?php echo $namespace; ?>-support-form">
          <?php wp_nonce_field($namespace . "-support-form"); ?>
          <div class="widget">
            <h3 class="promo"><i class="fa fa-envelope"></i> Email Logs to Support</h3>
            <p><b>This form should be used to send log files to support only.</b></p>
            <p>Please make sure to open a support ticket via WP BackItUp <a href="https://www.wpbackitup.com/support" target="_blank">support portal.</a> before using this form.</p>
            <p><em><?php _e('The ticket id you receive from your support request should be entered in the ticket id field below.', $namespace); ?></em></p>
            <p><input <?php echo($disabled) ; ?> type="text" name="support_email" value="<?php echo $support_email; ?>" size="30" placeholder="your email address">
	            <?php
	            if ( false !== ( $msg = get_transient('error-support-email') ) && $msg)
	            {
		            echo '<span class="error">'.$msg.'</span>';
		            delete_transient('error-support-email');
	            }
	            ?>
            </p>

            <p><input <?php echo($disabled) ; ?> type="text" name="support_ticket_id" value="" size="30" placeholder="support ticket id">
              <?php
              if ( false !== ( $msg = get_transient('error-support-ticket') ) && $msg)
              {
                echo '<span class="error">'.$msg.'</span>';
                delete_transient('error-support-ticket');
              }
              ?>
            </p>

            <p><textarea <?php echo($disabled) ; ?> name="support_body" rows="4" cols="50" style="width:450px;height:150px;" placeholder="problem description or additional information"><?php echo get_transient('support_body'); ?></textarea>
	            <?php
	            if ( false !== ( $msg = get_transient('error-support-body') ) && $msg)
	            {
		            echo '<span class="error">'.$msg.'</span>';
		            delete_transient('error-support-body');
	            }
	            ?>

            </p>
            <input <?php echo($disabled) ; ?> type="checkbox" name="support_include_logs" id="support_include_logs" value="1" checked> <label for="support_include_logs">send logs</label><br>

	        <div class="submit"><input <?php echo($disabled) ; ?> type="submit" name="send_ticket" class="button-primary" value="<?php _e("Send", $namespace) ?>" />
			<?php if (!$license_active) : ?>
                * Premium customers receive priority support.
            <?php endif; ?>
            </div>

            <?php
            if ( false !== ( $msg = get_transient('settings-error-email') ) && $msg)
            {
              echo '<p class="error">'.$msg.'</p>';
              delete_transient('settings-error-email');
            }
            ?>
          </div>

    </form>
  </div>
</div>