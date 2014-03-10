<?php 
/**
 * WP Backitup Recurse Iterator
 * 
 * @package WP Backitup
 * 
 * @author cssimmon
 * @version 1.4.0
 * @since 1.0.1
 */


class RecursiveFilter_Iterator extends RecursiveFilterIterator {
        
    private $filters=array();

    //Set the ignore list
    function set_filter ($ignore) {
        $this->filters = $ignore;
    }

    public function accept() {        
        return !in_array(
            $this->current()->getFilename(),
            $this->filters,
            true
        );
    }

}