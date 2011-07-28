<?php
/*
Plugin Name: CallMe 
Description: Twilio Powered calling to the blog owner
Version: 0.1
Author: Aaron Frank
Author URI: http://www.arfrank.com
License: GPL2
*/


function callme_config_page() {
	if ( function_exists('add_submenu_page') )
		add_submenu_page('plugins.php', __('CallMe Configuration'), __('CallMe Configuration'), 'manage_options', 'callme-key-config', 'callme_conf');
}

function callme_plugin_action_links( $links, $file ) {
	if ( $file == plugin_basename( dirname(__FILE__).'/callme.php' ) ) {
		$links[] = '<a href="plugins.php?page=callme-key-config">'.__('Settings').'</a>';
	}
	return $links;
}

add_filter( 'plugin_action_links', 'callme_plugin_action_links',10,2);
add_action( 'admin_menu', 'callme_config_page' );
add_action('wp_print_scripts', 'WPCallMe_Scripts');

function callme_conf(){
	?>
		<div>
			<h1>CallMe Config Page</h1>
			<p>This plug allows you to easily add a call me, conference with other readers, or leave me a voicemail button to your blog.</p>
			<div id="" class="">
				<h2>Twilio Credentials</h2>
				<form action="#" method="post" accept-charset="utf-8">
					<p><label>Twilio Account SID:<input type="text" name="twilio_sid" value=""></label></p>
					<p><label>Twilio Auth Token: <input type="text" name="twilio_token" value=""></label></p>
					<input type="submit" name="Save Credentials" value="Save Credentials">
				</form>
			</div>
			<div>
				<h2>Customize CallMe Widget</h2>
				<select id="callme_widget_type" name="callme_widget_type">
					<option value="callme">Call Me!</option>
					<option value="conference">Conference</option>
					<option value="voicemail">Voicemail</option>
				</select>
				<div id="callme" class="selected_widget_inputs">
					<h3>Call Me!</h3>
					<p>
						Visitors will be able to directly call you from your blog.
					</p>
					<p>
						<label>Number to reach you at: <input type="text" name="your_number" value=""></label>
					</p>
				</div>
				<div id="conference" style="display:none" class="selected_widget_inputs">
					<h3>Conference Call</h3>
					<p>
						Visitors will be able to join a conference call with other visitors to you're blog.
					</p>
				</div>
				<div id="voicemail" style="display:none" class="selected_widget_inputs">
					<h3>Voicemail</h3>
					<p>
						When set to this, visitors will be able to leave you voicemails and we'll automatically have them forward to you're email with a recording.
					</p>
				</div>
			</div>
		</div>
		
	<?php
}
function WPCallMe_Scripts(){
	$callme_plugin_url = trailingslashit( get_bloginfo('wpurl') ).PLUGINDIR.'/'. dirname( plugin_basename(__FILE__) );
	 if (!is_admin()){
		  wp_enqueue_script('jquery');
		  wp_enqueue_script('jquery-form');
		  wp_enqueue_script('callme_public_script', $callme_plugin_url.'/js/callme.js', array('jquery', 'jquery-form'));
		 /* wp_localize_script( 'wp_wall_script', 'WPWallSettings', array(
		  	'refreshtime' => 5,
	                'mode' => "auto"
			));*/
	}else{
		  wp_enqueue_script('jquery');
		  wp_enqueue_script('jquery-form');
		  wp_enqueue_script('callme_admin_script', $callme_plugin_url.'/js/admin.js', array('jquery', 'jquery-form'),1,true);
	}
}
?>