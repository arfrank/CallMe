<?php
//This should take a twilio status callback to indicate the... end of calls, a voicemail being left, and conference session ending.

//A hack from another plugin
if (!function_exists('add_action')) {
    $wp_root = '../../../..';
    if (file_exists($wp_root.'/wp-load.php')) {
        require_once($wp_root.'/wp-load.php');
   } else {
        require_once($wp_root.'/wp-config.php');
    }
}
include 'TwilioLibrary/Services/Twilio.php'



?>