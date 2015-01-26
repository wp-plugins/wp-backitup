<?php if (!defined ('ABSPATH')) die('No direct access allowed');

/**
 * WP BackItUp  - Recursive iterator Class
 *
 * @package WP BackItUp
 * @author  Chris Simmons <chris.simmons@wpbackitup.com>
 * @link    http://www.wpbackitup.com
 *
 */


class WPBackItUp_RecursiveFilter_Iterator extends RecursiveFilterIterator {
        
    private $filters=array();
    private $logger;

    //Set the ignore list
    function set_filter ($ignore) {
        $this->filters = $ignore;
    }

    public function accept() {
        $logger = new WPBackItUp_Logger(false);
        $accept = !in_array(
            $this->current()->getFilename(),
            $this->filters,
            true);

        $logger->log('(WPBackItUp_RecursiveFilter_Iterator) accept:' . $this->current()->getFilename() . ":" .$accept);
        return $accept;
    }

}