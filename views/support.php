<?php if (!defined ('ABSPATH')) die('No direct access allowed');

/**
 * WP BackItUp  - Support View
 *
 * @package WP BackItUp
 * @author  Chris Simmons <chris.simmons@wpbackitup.com>
 * @link    http://www.wpbackitup.com
 *
 */

	//Check the license
	do_action( 'wpbackitup_check_license');

    $namespace = $this->namespace;
    $page_title = sprintf(__("%s Support", $namespace), $this->friendly_name );

    $license_active = $this->license_active();
    $is_lite_registered = $this->is_lite_registered();

	$support_email =$this->support_email();
	if (empty($support_email)){
		$support_email =$this->license_customer_email();
	}

	//Force registration for support
	$disabled='';
    if (!$license_active && !$is_lite_registered){
        $disabled='disabled';
    }

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
            <h3 class="promo"><i class="fa fa-envelope"></i> <?php _e('Email Logs to Support', $namespace) ?></h3>
            <p><b><?php _e('This form should be used to send log files to support only.', $namespace) ?></b></p>
            <p><?php _e('Please make sure to open a support ticket via WP BackItUp <a href="https://www.wpbackitup.com/support" target="_blank">support portal.</a> before using this form.', $namespace) ?></p>
            <p><em><?php _e('The ticket id you receive from your support request should be entered in the ticket id field below.', $namespace); ?></em></p>
            <p><input <?php echo($disabled) ; ?> type="text" name="support_email" value="<?php echo $support_email; ?>" size="30" placeholder="<?php _e('your email address',$namespace)?>">
	            <?php
	            if ( false !== ( $msg = get_transient('error-support-email') ) && $msg)
	            {
		            echo '<span class="error">'.$msg.'</span>';
		            delete_transient('error-support-email');
	            }
	            ?>
            </p>

            <p><input <?php echo($disabled) ; ?> type="text" name="support_ticket_id" value="" size="30" placeholder="<?php _e('support ticket id',$namespace)?>">
              <?php
              if ( false !== ( $msg = get_transient('error-support-ticket') ) && $msg)
              {
                echo '<span class="error">'.$msg.'</span>';
                delete_transient('error-support-ticket');
              }
              ?>
            </p>

            <p><textarea <?php echo($disabled) ; ?> name="support_body" rows="4" cols="50" style="width:450px;height:150px;" placeholder="<?php _e('problem description or additional information',$namespace)?>"><?php echo get_transient('support_body'); ?></textarea>
	            <?php
	            if ( false !== ( $msg = get_transient('error-support-body') ) && $msg)
	            {
		            echo '<span class="error">'.$msg.'</span>';
		            delete_transient('error-support-body');
	            }
	            ?>

            </p>

	        <div class="submit"><input <?php echo($disabled) ; ?> type="submit" name="send_ticket" class="button-primary" value="<?php _e("Send", $namespace) ?>" />

	        <?php if (!$license_active && !$is_lite_registered) : ?>
		        <span style="color:red">* <?php _e('Please register your version of WP BackItUp for access to support.', $namespace) ?></span>
            <?php endif; ?>

           <?php if (!$license_active && $is_lite_registered) : ?>
		        * <?php _e('Premium customers receive priority support.', $namespace) ?>
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