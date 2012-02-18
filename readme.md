#WP Single Use Keys

One of those things that you don't want to code twice, so here's a plugin that takes care of it once and for all. Provides you with a class to easily generate
single use, and optionally expiring keys (generally for use in links) to provide to visitors or registered users of your site.

##Basic Usage

Create a non-expiring single use key and do something with it

    <?php
        $key = new SingleUseKey();
        wp_mail('email@address.com', 'Email subject', 'Here is your one time link - http://www.mysite.com?key='.$key->key);
    ?>
    
Consume a key later

    <?php
        add_action('init', 'consume_key');
        
        function consume_key()
        {
            if ($_GET['key'])
            {
                $consumer = new SingleUseKey(array('store' => false));
                $status = $consumer->consume($_GET['key']);
                if ($status == 'valid') echo 'Thanks! Your key was accepted.';
                else echo $status;
            }
        }
        
##Options

When you create a new key, there are a number of options that can be passed which will affect the behavior of the key you create.

###secret

This is combined with the time of key generation to provide a unique md5 hash that is used as the key string. Defaults to a static md5 of 'ramalamadingdong'. To specify a custom secret, you'd do something like
this:

    <?php
        $key = new SingleUseKey(array('secret' => 'myAwesomeCustomSecret'));
    ?>
    
###store

Determines whether or not to store the the key upon instantiation. Defaults to true. As in the basic use example, you'll normally set this to false when validating
a key, like so:

    <?php
        $key = new SingleUseKey(array('store' => false));
    ?>

###expires

Sets optional expiration time of the key. Defaults to never. To specify an expiration, pass in a valid string accepted by PHP's [strtotime](http://php.net/manual/en/function.strtotime.php)
If the string is not parsable, the key will not be created, and a WP_Error object will be returned. The code below attempts to set an expiration 3 days from now, and
if the expriation string is not parsable will then create a key with no expiration.

    <?php
        $key = new SingleUseKey(array('expires' => '3 days'));
        if ( is_wp_error($key) ) $key = new SingleUseKey();
    ?>
    
###invalid_message

Sets the message that will be returned when attempting to validate or consume an invalid key. Defaults to 'Sorry, looks like this is an invalid key'. This is taken from the key object used to do the consumption/validation. Here's
how you would display a custom message if a key is invalid:

    <?php
        $key = $_GET['key'];
        $consumer = new SingleUseKey(array('store' => false, 'invalid_message' => 'Slow down sparky! That's not a legit key...'));
        $valid = $consumer->consume($key); //$valid now equals 'Slow down sparky! etc etc'
    ?>
    
###expired_message

Sets the message that will be returned when attempting to validate or consume a key that has expired. Defaults to 'Sorry, looks like this key has expired'. This is taken from the stored key object that is being consumed or validated. Here's
how you would set a custom expiration message when storing a new key:

    <?php
        $key = new SingleUseKey(array('expires' => 'tomorrow', 'expired_message' => 'Yo slowsky! This key is mad old... maybe try getting a new one?'));
    ?>
    
If the following code was run to consume this key 2 days later:

    <?php
        $key = $_GET['key'];
        $consumer = new $SingleUseKey(array('store' => false));
        $valid = $consumer->consume($key);
    ?>
    
$valid would now equal 'Yo slowsky! etc etc'

##Action Hooks

In case you want to use your own storage methods or do any additional processing during storage and loading of your keys, two action hooks are available.

###load_stored_single_use_keys

This action is fired before the stored keys are loaded into memory by a consumer or validator. If you've changed the storage for your keys this is where you would read from your new storage location and ensure the data is formatted to be readable
by the plugin. Setting the stored_keys property on the key object passed to this hook will automatically override the plugins default loading behavior.
Here's an example of loading the stored keys from a global variable:

    <?php
        add_action('load_stored_single_use_keys', 'load_keys_from_global');
        
        function load_keys_from_global($key_obj)
        {
            global $stored_keys;
            $key_obj->stored_keys = $stored_keys;
        }
    ?>
    
###store_single_use_keys

This action is fired right before the plugins default storage behavior. By default the custom storage method will be run *in addition* to the default storage method. To override the default storage behavior, set the storage_override property to true
on the key object passed to the hook function. The code below will do just that, and store keys in the same global variable referenced in the custom load example above. While this does not offer much in the way of persistence,
this code example along with the previous do comprise a fully functional demonstration of custom storage and retreival of keys.

    <?php
        add_action('store_single_use_keys', 'store_keys_in_global', 10 , 2);
        
        function store_keys_in_global($stored, $key_obj)
        {
            global $stored_keys;
            $stored_keys = $stored;
            $key_obj->store_override = true;
        }
    ?>
    
##Methods

###store

This method stores the current key. If you need to do additional processing with a key before storing, you can instantiate with the store option set to false, and then store when ready by calling the method, as follows:

    <?php
        $key = new SingleUseKey(array('store' => false));
        //do additional stuff with key
        $key->store();
    ?>

###consume

This method accepts a key string and returns one of three string values: 'valid', invalid_message (as described above), or expired_message (as described above). If the key string matches a valid stored key, that key is removed
from storage and will no longer be usable.

    <?php
        $key = $_GET['key'];
        $consumer = new SingleUseKey(array('store' => false));
        $valid = $consumer->consume($key); //if key string matches a stored key, $valid equals 'valid', and the key has been removed from storage, and cannot be used again
    ?>

###validate

This method accepts a key string, and returns any of the same three values returned by the consume method, however no further processing is done beyond this. The key is left in storage and can be validated again, or consumed at a later time.

    <?php
        $key = $_GET['key'];
        $validator = new SingleUseKey(array('store' => false));
        $valid = $validator->validate($key); //if key string matches a stored key, $valid equals 'valid', and the key remains in storage and can be used again, or consumed at a later time.