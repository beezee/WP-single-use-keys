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
            \Enhance\Assert::fail();
        }
        
        public function testStoreOverride()
        {
            \Enhance\Assert::fail();
        }
}

// Find the tests - '.' is the current folder
\Enhance\Core::discoverTests('.');
// Run the tests
\Enhance\Core::runTests();
?>