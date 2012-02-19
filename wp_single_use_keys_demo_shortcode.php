<?php

/*
Plugin Name: WordPress Single Use Keys Example Shortcode
Plugin URI: https://beezee.github.com/WP-single-use-links.html
Description: [single_use_demo_form] Shortcode to generate a form which sends out single use links with 4 minute expiration to demo use of WP single use keys plugin - QUICK AND DIRTY
Version: 0.1
Author: Brian Zeligson
Author URI: http://beezee.github.com
License: GPL2
*/

add_action('wp_head', 'bz_single_use_link_process', 99);

function bz_single_use_link_process()
{
    ?>
    <script type="text/javascript" src="https://ajax.googleapis.com/ajax/libs/jquery/1.7.1/jquery.min.js"></script>
    <script type="text/javascript">
        jQuery('document').ready(function($) {
           if ($('#single_use_key_demo_form_submit').length) {
                $('#single_use_key_demo_form_submit').click(function() {
                    var pattern = new RegExp(/^(("[\w-+\s]+")|([\w-+]+(?:\.[\w-+]+)*)|("[\w-+\s]+")([\w-+]+(?:\.[\w-+]+)*))(@((?:[\w-+]+\.)*\w[\w-+]{0,66})\.([a-z]{2,6}(?:\.[a-z]{2})?)$)|(@\[?((25[0-5]\.|2[0-4][0-9]\.|1[0-9]{2}\.|[0-9]{1,2}\.))((25[0-5]|2[0-4][0-9]|1[0-9]{2}|[0-9]{1,2})\.){2}(25[0-5]|2[0-4][0-9]|1[0-9]{2}|[0-9]{1,2})\]?$)/i);
		    if (!pattern.test($('#single_use_key_demo_email').val())) { alert('Please enter a valid email address.'); return;}
                    var data = {
                        action : 'process_single_use_link_demo_form',
                        email : $('#single_use_key_demo_email').val()
                    }
                    $.post('<?php echo get_bloginfo('url'); ?>/wp-admin/admin-ajax.php', data, function(response) {
                        alert('Thanks! Check your email for an email with subject "Your single use link..." - maybe even check your spam folder too.');
                    })
                });
           }
        });
    </script>
    <?php
    if ($_GET['single_use_key'])
    {
        $consumer = new SingleUseKey(array('store' => false, "invalid_message' => 'Whoa there sparky! Looks like that link ain't legit..."));
        $valid = $consumer->consume($_GET['single_use_key']);
        if ($valid == 'valid') $valid = 'Hey good work! That key has been consumed and this link won\'t work again';
        ?>
        <script type="text/javascript">
            alert('<?php echo $valid; ?>');
        </script>
        <?php
    }
}

add_action('wp_ajax_process_single_use_link_demo_form', 'bz_process_single_use_demo_link_form');
add_action('wp_ajax_nopriv_process_single_use_link_demo_form', 'bz_process_single_use_demo_link_form');

function bz_process_single_use_demo_link_form()
{
    $key = new SingleUseKey(array('expires' => '4 minutes', 'expired_message' => 'What took you so long? That link has totally moved on... try making another?'));
    wp_mail($_POST['email'], 'Your single use link...', 'Here it is '.get_bloginfo('url').'?single_use_key='.$key->key);
    die();
}

function write_single_use_link_demo_form( $atts ){
 return '<input id="single_use_key_demo_email" type="text" /><br /><input type="button" value="Send me a single use link" id="single_use_key_demo_form_submit" />';
}
add_shortcode( 'single_use_demo_form', 'write_single_use_link_demo_form' );

