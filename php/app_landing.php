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
if (!function_exists('getallheaders')) 
{
    function getallheaders() 
    {
       foreach ($_SERVER as $name => $value) 
       {
           if (substr($name, 0, 5) == 'HTTP_') 
           {
               $headers[str_replace(' ', '-', ucwords(strtolower(str_replace('_', ' ', substr($name, 5)))))] = $value;
           }
       }
       return $headers;
    }
}
require_once('TwilioLibrary/Services/Twilio.php');
//require_once('TwilioLibrary/Services/Twilio/RequestValidator.php');
$callme_settings = get_option('callme_settings');

if ($callme_settings) {

	$token = $callme_settings['twilio']['token'];

	$validator = new Services_Twilio_RequestValidator($token);

	$callme_plugin_url = trailingslashit( get_bloginfo('wpurl') ).PLUGINDIR.'/CallMe';

	$url = $callme_plugin_url.$_SERVER['REQUEST_URI'];
	$postVars = $_POST;
	$headers = getallheaders();
	$signature = (isset($headers['X-Twilio-Signature']) ? $headers['X-Twilio-Signature']:'');

//	if ($validator->validate($signature, $url, $postVars)) {

		//do some twilio security validation up here

		
		$twiml = new Services_Twilio_Twiml();
		//print_r($callme_settings);
		switch ($callme_settings['widget']['type']) {
			case 'callme':
			$twiml->dial($callme_settings['callme']['your_number'], array('callerId'=>$callme_settings['callme']['twilio_number']));
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
				$twiml->say($callme_settings['voicemail']['welcome']);
				$twiml->record(array('action'=>$callme_plugin_url.'/php/callback_landing.php','maxLength'=>(is_int($callme_settings['voicemail']['length']) ? (int) $callme_settings['voicemail']['length'] : 60)));
			break;
		}
		print $twiml;
//	}else {
//		echo "NOT VALID.  It might have been spoofed!";
//	}


}