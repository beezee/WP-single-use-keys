<?php
// Include the test framework
require_once(dirname(__FILE__).'/../wp_single_use_keys.php');
$wp_load_path = '/var/www/';
if (!defined('ABSPATH')) require_once($wp_load_path.'wp-load.php');
include('EnhanceTestFramework.php');

class testUtilities
{
    public function clearOption()
    {
        update_option('bz_single_use_keys', array());
    }
    
    public function getOption()
    {
        return(get_option('bz_single_use_keys'));
    }

}

class ExampleClassTests extends \Enhance\TestFixture
{
        private $utility;

        public function setUp()
        {
                $this->utility = new testUtilities();
        }

        public function testBuiltInStorage()
        {
            $this->utility->clearOption();
            $key = new SingleUseKey();
            $option = $this->utility->getOption();
            \Enhance\Assert::areIdentical(count($option), 1);
            $key2 = new SingleUseKey();
            $option = $this->utility->getOption();
            \Enhance\Assert::areIdentical(count($option), 2);
            \Enhance\Assert::areIdentical($key->settings['key'], $option[$key->settings['key']]['key']);
            \Enhance\Assert::areIdentical($key2->settings['key'], $option[$key2->settings['key']]['key']);
            $dontstore = new SingleUseKey(array('store' => false));
            $option = $this->utility->getOption();
            \Enhance\Assert::areIdentical(count($option), 2);
        }
        
        public function testExpiration()
        {
            $this->utility->clearOption();
            $key = new SingleUseKey(array('expires' => '2 minutes'));
            \Enhance\Assert::isTrue('1 minute' < $key->settings['expires']);
            \Enhance\Assert::isTrue(strtotime('3 minutes') > $key->settings['expires']);
            $expired_key = new SingleUseKey(array('expires' => '1 hour ago'));
            $validate = new SingleUseKey(array('store' => false));
            $valid = $validate->validate($expired_key->settings['key']);
            \Enhance\Assert::areIdentical($expired_key->settings['expired_message'], $valid);
        }
        
        public function testValidation()
        {
            $this->utility->clearOption();
            $key = new SingleUseKey(array('store' => false));
            $validate = new SingleUseKey(array('store' => false, 'invalid_message' => 'weeblewobble'));
            $valid = $validate->validate($key->settings['key']);
            \Enhance\Assert::areIdentical($valid, 'weeblewobble');
        }
        
        public function testConsume()
        {
            $this->utility->clearOption();
            $key = new SingleUseKey();
            $key2 = new SingleUseKey();
            $invalid = new SingleUseKey(array('store' => false));
            $consumer = new SingleUseKey(array('store' => false, 'invalid_message' => 'woowoowooowoo'));
            $option = $this->utility->getOption();
            \Enhance\Assert::areIdentical(2, count($option));
            $valid = $consumer->consume($key->key);
            $option = $this->utility->getOption();
            \Enhance\Assert::areIdentical(1, count($option));
            \Enhance\Assert::areIdentical($valid, 'valid');
            $valid = $consumer->consume($key->key);
            \Enhance\Assert::areIdentical($valid, 'woowoowooowoo');
        }
        
        public function testLoadOverride()
        {
            $this->utility->clearOption();
            add_action('load_stored_single_use_keys', 'dummy_data_load');
            $validator = new SingleUseKey(array('store' => false, 'invalid_message' => 'invalid'));
            $key = new SingleUseKey();
            $valid = $validator->validate('meep');
            \Enhance\Assert::areIdentical($valid, 'valid');
            $option = $this->utility->getOption();
            \Enhance\Assert::areIdentical($option[$key->key]['key'], $key->key);
            $invalid = $validator->validate($key->key);
            \Enhance\Assert::areIdentical('invalid', $invalid);
        }
        
        public function testStoreOverride()
        {
            $this->utility->clearOption();
            remove_action('load_stored_single_use_keys', 'dummy_data_load');
            add_action('store_single_use_keys', 'dummy_store_override', 10 , 2);
            $validator = new SingleUseKey(array('store' => false, 'invalid_message' => 'invalid'));
            $key = new SingleUseKey();
            $option = $this->utility->getOption();
            \Enhance\Assert::areIdentical(0, count($option));
            \Enhance\Assert::areIdentical($key->key, $key->funny_stuff[$key->key]['key']);
        }
        
        public function customPersistence()
        {
            remove_action('load_stored_single_use_keys', 'dummy_data_load');
            remove_action('store_single_use_keys', 'dummy_store_override');
            add_action('load_stored_single_use_keys', 'load_keys_from_global');
            add_action('store_single_use_keys', 'store_keys_in_global', 10 , 2);
        }
        
        public function testCustomStorage()
        {
            global $stored_keys;
            $stored_keys = array();
            $this->utility->clearOption();
            $key = new SingleUseKey();
            $option = $this->utility->getOption();
            \Enhance\Assert::areIdentical(count($option), 0);
            \Enhance\Assert::areIdentical(count($stored_keys), 1);
            $key2 = new SingleUseKey();
            $option = $this->utility->getOption();
            \Enhance\Assert::areIdentical(count($option), 0);
            \Enhance\Assert::areIdentical(count($stored_keys), 2);
            \Enhance\Assert::areIdentical($key->settings['key'], $stored_keys[$key->key]['key']);
            \Enhance\Assert::areIdentical($key2->settings['key'], $stored_keys[$key2->key]['key']);
            $dontstore = new SingleUseKey(array('store' => false));
            $option = $this->utility->getOption();
            \Enhance\Assert::areIdentical(count($stored_keys), 2);
        }
        
        public function testCustomStorageExpiration()
        {
            $this->utility->clearOption();
            global $stored_keys;
            $stored_keys = array();
            $key = new SingleUseKey(array('expires' => '2 minutes'));
            \Enhance\Assert::isTrue('1 minute' < $key->settings['expires']);
            \Enhance\Assert::isTrue(strtotime('3 minutes') > $key->settings['expires']);
            $expired_key = new SingleUseKey(array('expires' => '1 hour ago'));
            $validate = new SingleUseKey(array('store' => false));
            $valid = $validate->validate($expired_key->settings['key']);
            \Enhance\Assert::areIdentical($expired_key->settings['expired_message'], $valid);
        }
        
        public function testCustomStorageValidation()
        {
            $this->utility->clearOption();
            global $stored_keys;
            $stored_keys = array();
            $key = new SingleUseKey(array('store' => false));
            $validate = new SingleUseKey(array('store' => false, 'invalid_message' => 'weeblewobble'));
            $valid = $validate->validate($key->key);
            \Enhance\Assert::areIdentical($valid, 'weeblewobble');
        }
        
        public function testCustomStorageConsume()
        {
            $this->utility->clearOption();
            global $stored_keys;
            $stored_keys = array();
            $key = new SingleUseKey();
            $key2 = new SingleUseKey();
            $invalid = new SingleUseKey(array('store' => false));
            $consumer = new SingleUseKey(array('store' => false, 'invalid_message' => 'woowoowooowoo'));
            \Enhance\Assert::areIdentical(2, count($stored_keys));
            $valid = $consumer->consume($key->key);
            \Enhance\Assert::areIdentical(1, count($stored_keys));
            \Enhance\Assert::areIdentical($valid, 'valid');
            $valid = $consumer->consume($key->key);
            \Enhance\Assert::areIdentical($valid, 'woowoowooowoo');
        }
}

function load_keys_from_global($key_obj)
{
    global $stored_keys;
    $key_obj->stored_keys = $stored_keys;
    //echo '<pre>'.print_r($stored_keys, true).'</pre>';
}

function store_keys_in_global($stored, $key_obj)
{
    global $stored_keys;
    $stored_keys = $stored;
    $key_obj->store_override = true;
}

function dummy_data_load($key_obj)
{
    $key_obj->stored_keys = array('meep' => array('key' => 'meep', 'expires' => 'never'));
}

function dummy_store_override($stored, $key_obj)
{
    $key_obj->store_override = true;
    $key_obj->funny_stuff = $stored;
}

// Find the tests - '.' is the current folder
\Enhance\Core::discoverTests('.');
// Run the tests
\Enhance\Core::runTests();
?>