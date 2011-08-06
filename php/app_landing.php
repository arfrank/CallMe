<?php

//A hack from another plugin
if (!function_exists('add_action')) {
	$wp_root = '../../../..';
	if (file_exists($wp_root.'/wp-load.php')) {
		require_once($wp_root.'/wp-load.php');
	} else {
		require_once($wp_root.'/wp-config.php');
	}
}
require_once('TwilioLibrary/Services/Twilio.php');
$callme_settings = get_option('callme_settings');
if ($callme_settings) {

	$token = $callme_settings['twilio']['token'];

	$validator = new Twilio_Services_RequestValidator($token);

	$callme_plugin_url = trailingslashit( get_bloginfo('wpurl') ).PLUGINDIR.'/CallMe';

	$url = $callme_plugin_url.'/php/app_landing.php';
	$postVars = $_POST;
	$headers = getallheaders();
	$signature = (isset($headers['X-Twilio-Signature']) ? $headers['X-Twilio-Signature']:'');

	if ($validator->validate($signature, $url, $postVars)) {

		//do some twilio security validation up here

		//error_log(print_r(getallheaders(),1));

		$twiml = new Services_Twilio_Twiml();
		//print_r($callme_settings);
		switch ($callme_settings['widget']['type']) {
			case 'callme':
			$twiml->dial($callme_settings['callme']['your_number'], array('callerId'=>$callme_settings['callme']['twilio_number']));
			//needs a from for dialing out
			break;
			case 'conference':
			$twiml->say($callme_settings['conference']['conference_text']);
			if (isset($callme_settings['conference']['length']) && $callme_settings['conference']['length']) {
				$twiml->dial('', array('timeLimit'=>$callme_settings['conference']['length']))->conference($callme_settings['conference']['widget_text'].$callme_settings['twilio']['app_sid']);
			}else{
				$twiml->dial()->conference($callme_settings['conference']['widget_text'].$callme_settings['twilio']['app_sid']);
			}
			break;
			case 'voicemail':
				$twiml->say($callme_settings['voicemail']['welcome'])
				$twiml->record();
			break;
		}
		print $twiml;
	}else {
		echo "NOT VALID.  It might have been spoofed!";
	}


}