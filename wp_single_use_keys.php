<?php
/*
Plugin Name: WordPress Single Use Links
Plugin URI: https://github.com/beezee/WP-single-use-links
Description: Generate single use links, (optionally with expiration date) for your visitors or registered users.
Version: 0.1
Author: Brian Zeligson
Author URI: http://beezee.github.com
License: GPL2
*/


class SingleUseLink
{
    private $_key;
    
    public function __construct($params = array())
    {
        $default_params = array('secret' => '162d6d38e50244b727bc303d04f369eb', 'store' => true);
        $link_params = (is_array($params)) ? array_merge($default_params, $params) : $default_params;
        $this->_key = md5(time().$link_params['secret']);
        $this->generate();
        if ($link_params['store']) $this->store();
    }
    
    private function generate()
    {
        
    }
    
    public function store()
    {
        do_action('store_single_use_link', $link);
    }
    
    public function validate()
    {
        
    }
    
    public function consume()
    {
        $response = $this->validate();
        //consume it if valid
        return $response;
    }
}