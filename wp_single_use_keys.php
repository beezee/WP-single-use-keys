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
    public $key;
    public $settings;
    public $stored_keys = false;
    public $store_override = false;  
    
    public function __construct($options = array())
    {
        $this->_load_stored_keys();
        $default_options = array(
            'secret' => '162d6d38e50244b727bc303d04f369eb',
            'store' => true,
            'expires' => 'never',
            'invalid_message' => 'Sorry, looks like this is an invalid key',
            'expired_message' => 'Sorry, looks like this key has expired');
        $key_options = (is_array($options)) ? array_merge($default_options, $options) : $default_options;
        $this->_key = md5(microtime().$key_options['secret']);
        $this->key = $this->_key;
        $key_options['key'] = $this->_key;
        $this->_expires = ($key_options['expires'] == 'never') ? $key_options['expires'] : $this->_set_expiration($key_options['expires']);
        if ( is_wp_error($this->_expires) ) return $this->_expires;
        $this->settings = $key_options;
        $this->settings['expires'] = $this->_expires;
        foreach($key_options as $option_name => $option_value)
        {
            if ($option_name == 'secret' or $option_name == 'store' or $option_name == 'expires') continue;
            $keyname = '_'.$option_name;
            $this->$keyname = $option_value;
        }
        if ($key_options['store']) $this->store();
    }
    
    private function _set_expiration($expires)
    {
        $expiration_time = strtotime($expires);
        if (!$expiration_time) return new WP_Error('time_invalid', 'The specified expiration could not be converted to timestamp. Please see PHP strtotime documentation for supported formats.');
        return $expiration_time;
    }
    
    private function _load_stored_keys()
    {
        do_action('load_stored_single_use_keys', $this);
        if ($this->stored_keys)
        {
            $this->_stored_keys = $this->stored_keys;
            return;
        }
        $this->_stored_keys = get_option('bz_single_use_keys');
        if (!is_array($this->_stored_keys)) $this->_stored_keys = array();
    }
    
    public function store($add_self=true)
    {
        if ($add_self) $this->_stored_keys[$this->_key] = $this->settings;
        $store = $this->_stored_keys;
        do_action('store_single_use_keys', $store, $this);
        if ($this->store_override) return;
        update_option('bz_single_use_keys', $store);
    }
    
    public function validate($key)
    {
        if (!isset($this->_stored_keys[$key])) return $this->_invalid_message;
        $stored_key = $this->_stored_keys[$key];
        if ($stored_key['expires'] != 'never' and $stored_key['expires'] - time() <= 0) return $stored_key['expired_message'];
        return 'valid';
    }
    
    public function consume($key)
    {
        $foundKey = $this->validate($key);
        if (isset($this->_stored_keys[$key])) unset($this->_stored_keys[$key]);
        $this->store(false);
        return $foundKey;
    }
}