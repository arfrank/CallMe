<?php
if (!function_exists('add_action')) {
    $wp_root = '../../../..';
    if (file_exists($wp_root.'/wp-load.php')) {
        require_once($wp_root.'/wp-load.php');
   } else {
        require_once($wp_root.'/wp-config.php');
    }
}
/*
$token = 'YYYYYYYYYYYYYYYYYYYYYYYYYYYYYYYY';

$validator = new Twilio_Services_RequestValidator($token);

$url = "http://www.example.com/request/url";
$postVars = array();
$signature = "X-Twilio-Signature header value";

if ($validator->validate($signature, $url, $postVars)) {
    echo "Confirmed to have come from Twilio.";
} else {
    echo "NOT VALID.  It might have been spoofed!";
}
*/
//do some twilio security validation up here
include 'TwilioLibrary/Services/Twilio/Twiml.php';
$twiml = new Services_Twilio_Twiml();
$callme_settings = get_option('callme_settings');
if ($callme_settings) {
	//print_r($callme_settings);
	switch ($callme_settings['widget']['type']) {
		case 'callme':
			$twiml->dial($callme_settings['callme']['number']);
			break;
		case 'conference':
			$twiml->say($callme_settings['conference']['conference_text']);
			$twiml->conference($callme_settings['conference']['widget_text']);
			break;
		case 'voicemail':
			$twiml->record();
			break;
	}
}

print $twiml;

