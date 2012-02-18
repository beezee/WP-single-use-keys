<?php
/*
Plugin Name: WordPress Single Use Keys
Plugin URI: https://github.com/beezee/WP-single-use-links
Description: Generate single use keys, (optionally with expiration date) for one-time links to give your visitors or registered users.
Version: 0.1
Author: Brian Zeligson
Author URI: http://beezee.github.com
License: GPL2
*/


class SingleUseKey
{
    private $_key;
    private $_expires;
    private $_invalid_message;
    private $_expired_message;
    private $_stored_keys;
    public $stored_keys;
    public $load_override = false;
    public $store_override = false;  
    
    public function __construct($options = array())
    {
        $this->_load_stored_keys();
        $default_options = array(
            'secret' => '162d6d38e50244b727bc303d04f369eb',
            'store' => true,
            'expires' => 'never',
            'invalid_message' => 'Sorry, looks like this is an invalid key',
            'expired_message' => 'Sorrey, looks like this key has expired');
        $link_options = (is_array($options)) ? array_merge($default_options, $options) : $default_options;
        $this->_key = md5(time().$link_options['secret']);
        foreach($link_options as $option_name => $option_value)
        {
            if ($option_name == 'secret' or $option_name == 'store') continue;
            $keyname = '_'.$option_name;
            $this->$keyname = $option_value;
        }
        if ($link_params['store']) $this->store();
        return $this->_key;
    }
    
    private function _load_stored_keys()
    {
        do_action('load_stored_keys', $this);
        if ($this->load_override)
        {
            $this->_stored_keys = $this->stored_keys;
            return;
        }
        $this->_stored_keys = get_option('bz_single_use_keys');
        if (!is_array($this->_stored_keys)) $this->_stored_keys = array();
    }
    
    public function store()
    {
        do_action('store_single_use_key', $this);
        if ($this->store_override) return;
        $this->_stored_keys[$this->_key] = $this;
        update_option('bz_single_use_keys', $this->_stored_keys);
    }
    
    public function validate($key)
    {
        
    }
    
    public function consume()
    {
        $foundKey = $this->validate();
        //consume it if it exists
        return $foundKey;
    }
}