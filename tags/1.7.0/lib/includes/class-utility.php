<?php if (!defined ('ABSPATH')) die('No direct access allowed');
/**
 * WP Backitup Utility Class
 * 
 * @package WP Backitup
 * 
 * @author cssimmon
 *
 */
class WPBackItUp_Utility {

	private $logger;

	function __construct($logger) {
		try {
			$this->logger = $logger;
		} catch(Exception $e) {
			//Dont do anything
			print $e;
		}
   }

   function __destruct() {
   		
   }
   

	function send_email($to,$subject,$message)
	{
		try {
			//global $WPBackitup;
			if($to) {
                $from_email = get_bloginfo('admin_email');
                $headers[] = 'Content-type: text/html';
				$headers[] = 'From: WP BackItUp '. '<'. $from_email .'>';

				wp_mail($to, $subject, $message, $headers);
                $this->logger->log('(send_email)EMail Sent from:' .$from_email);
                $this->logger->log('(send_email)EMail Sent to:' .$to);
			}

		} catch(Exception $e) {
				//Dont do anything
            $this->logger->log('(send_email)Send Email Exception:'.$e);
		}

	}

}	

