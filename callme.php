<?php
/*
Plugin Name: CallMe 
Description: Twilio Powered calling to the blog owner
Version: 0.1
Author: Aaron Frank
Author URI: http://www.arfrank.com
License: GPL2
*/
$twilio_found = true;
register_activation_hook(__FILE__, 'callme_activate');

//This is to make sure it loads the twilio helper libraries, was getting alot of problems on certain platforms (dotcloud)
function load_twilio_library(){
	if (file_exists(WP_PLUGIN_DIR.'/'. dirname( plugin_basename(__FILE__) ).'/php/TwilioLibrary/Services/Twilio.php')) {
		require_once(WP_PLUGIN_DIR.'/'. dirname( plugin_basename(__FILE__) ).'/php/TwilioLibrary/Services/Twilio.php');
		return true;
	}elseif(file_exists('/'.dirname( plugin_basename(__FILE__)).'/php/TwilioLibrary/Services/Twilio.php')){
		//HACK FOR DOTCLOUD
		require_once('/'.dirname( plugin_basename(__FILE__) ).'/php/TwilioLibrary/Services/Twilio.php');
		return true;
	}elseif(file_exists('./php/TwilioLibrary/Services/Twilio.php')){
		require_once('./php/TwilioLibrary/Services/Twilio.php');
		return true;
	}else{
		return false;
	}
	
}

function callme_activate(){
	add_option('callme_settings',array(), '', 'yes');
	$tw = load_twilio_library();
	if (!$tw) {
		throw new Exception("UNABLE TO FIND TWILIO LIBRARY", 1);
		
	}
}

$twilio_found = load_twilio_library();
if (true or $twilio_found) {
	$callme_settings = get_option('callme_settings',false);
	$callme_settings_changed = false;

	if (!$callme_settings) {
		add_option('callme_settings',array(), '', 'yes');
	}

	add_filter( 'plugin_action_links', 'callme_plugin_action_links',10,2);
	add_action( 'admin_menu', 'callme_config_page' );
	add_action( 'wp_print_scripts', 'WPCallMe_Scripts');
	add_action( 'wp_print_styles', 'WPCallMe_Styles');
	add_action( 'loop_start', 'WPCallMe_HTML');
	add_action('wp_dashboard_setup', 'callme_wp_dashboard_setup');


	if (isset($callme_settings['twilio']['sid']) && isset($callme_settings['twilio']['token'])){
		$twilio_client = new Services_Twilio($callme_settings['twilio']['sid'], $callme_settings['twilio']['token']);
		if(!isset($callme_settings['twilio']['app_sid'])) {
			$app = $twilio_client->account->applications->create('callme_app',
								array(
									'ApiVersion'=>'2010-04-01',
									'VoiceUrl'=>trailingslashit( get_bloginfo('wpurl') ).PLUGINDIR.'/'.'CallMe'.'/php/app_landing.php',
									'VoiceMethod'=>'GET',
									'StatusCallback'=>trailingslashit( get_bloginfo('wpurl') ).PLUGINDIR.'/'.'CallMe'.'/php/callback_landing.php'
									)
								);
			$callme_settings['twilio']['app_sid'] = $app->sid;
			$callme_settings_changed = true;
		}
		if (!isset($callme_settings['twilio']['subaccount_sid'])) {
			$sub_account = $twilio_client->accounts->create(array('FriendlyName'=>'callme_subaccount'));
			$callme_settings['twilio']['subaccount_sid'] = $sub_account->sid;
			$callme_settings_changed = true;
		}
	}

	function callme_app_page(){
	
	}

	//Add the settings pages
	function callme_config_page() {
		if ( function_exists('add_submenu_page') )
			add_submenu_page('plugins.php', __('CallMe Configuration'), __('CallMe Configuration'), 'manage_options', 'callme-config', 'callme_conf');
	}

	//Add the settings link to plugin row
	function callme_plugin_action_links( $links, $file ) {
		if ( $file == plugin_basename( dirname(__FILE__).'/callme.php' ) ) {
			$links[] = '<a href="plugins.php?page=callme-config">'.__('Settings').'</a>';
		}
		return $links;
	}
	
	/////////////////////////////////////////////////////////////////////////////////////////////////
	//Begin option handling
	function validate_number(){
	
	}

	if ($_POST['twilio_sid'] or $_POST['twilio_token']) {
		if (!isset($callme_settings['twilio'])) {
			$callme_settings['twilio'] = array();
		}
		if ($_POST['twilio_sid']) {
			$callme_settings['twilio']['sid'] = $_POST['twilio_sid'];
		}
		if ($_POST['twilio_token'] && $_POST['twilio_token'] !='') {
			$callme_settings['twilio']['token'] = $_POST['twilio_token'];
		}
		$callme_settings_changed = true;
	}
	if ($_POST['callme_type']) {
		if (!isset($callme_settings['widget'])) {
			$callme_settings['widget'] = array();
		}
		switch ($_POST['callme_type']) {
			case 'callme':
				if (!isset($callme_settings['callme'])) {
					$callme_settings['callme'] = array();
				}
				$callme_settings['widget']['type'] = 'callme';
				$callme_settings['callme']['widget_text'] = $_POST['widget_text'];
				if ($_POST['your_number']) {
					$callme_settings['callme']['your_number'] = $_POST['your_number'];
				}
				break;
			
			case 'conference':
				if (!isset($callme_settings['conference'])) {
					$callme_settings['conference'] = array();
				}
				$callme_settings['widget']['type'] = 'conference';
				$callme_settings['conference']['widget_text'] = $_POST['widget_text'];
				$callme_settings['conference']['conference_text'] = $_POST['conference_text'];
				$callme_settings['conference']['length'] = (is_numeric($_POST['conference_length']) && (int) $_POST['conference_length'] > 0) ? (int) $_POST['conference_length'] : 0;
				$callme_settings['conference']['autoconnect'] = ($_POST['conference_autoconnect'] == 'yes'?true:false);
				break;
			
			case 'voicemail':
				if (!isset($callme_settings['voicemail'])) {
					$callme_settings['voicemail'] = array();
				}
				$callme_settings['widget']['type'] = 'voicemail';
				$callme_settings['voicemail']['widget_text'] = $_POST['widget_text'];
				$callme_settings['voicemail']['welcome'] = $_POST['voicemail_welcome'];
				$callme_settings['voicemail']['length'] = $_POST['voicemail_length'];
				
				break;
		}
		$callme_settings_changed = true;
	}
	if ($_POST['widget_location']) {
		if (!isset($callme_settings['widget'])) {
			$callme_settings['widget'] = array();
		}
		switch ($_POST['widget_location']) {
			case 'topleft':
				$callme_settings['widget']['location'] = 'topleft';
				break;
			case 'topright':
				$callme_settings['widget']['location'] = 'topright';
				break;
			case 'bottomleft':
				$callme_settings['widget']['location'] = 'bottomleft';
				break;
		
			default:
				$callme_settings['widget']['location'] = 'bottomright';
				break;
		}
		$callme_settings_changed = true;
	}
	
	if ($_POST['widget_stylesheet']) {
		if (!isset($callme_settings['widget'])) {
			$callme_settings['widget'] = array();
		}
		$callme_settings['widget']['stylesheet'] = $_POST['widget_stylesheet'];
		$callme_settings_changed = true;
	}
	
	if ($_POST['twilio_number']) {
		if (!isset($callme_settings['callme'])) {
			$callme_settings['callme'] = array();
		}
		$callme_settings['callme']['twilio_number'] = $_POST['twilio_number'];
		$callme_settings_changed = true;
	}
	if ($callme_settings_changed) {
		update_option('callme_settings', $callme_settings);
	}

	//End option handling
	/////////////////////////////////////////////////////////////////////////////////////////////////
	//Utility functions
	function phoneNumberFormat($number){
		if(  preg_match( '/^\+\d(\d{3})(\d{3})(\d{4})$/', $number,  $matches ) )
		{
		    $result = $matches[1] . '-' .$matches[2] . '-' . $matches[3];
		    return $result;
		}
	}
	/////////////////////////////////////////////////////////////////////////////////////////////////
	
	
	
	
	//Functions below for page loading things

	//Page for admin settings
	function callme_conf(){
		global $callme_settings, $twilio_client;
		//Get a list of usable from numbers to make a phone number clal from.
		$caller_ids = $twilio_client->account->outgoing_caller_ids->getPage();

		$twilio_numbers = $twilio_client->account->incoming_phone_numbers->getPage();
		
		$phone_numbers = array();
		foreach ($caller_ids->outgoing_caller_ids as $oci) {
			$phone_numbers[] = $oci->phone_number;
		}
		foreach ($twilio_numbers->incoming_phone_numbers as $ipn) {
			$phone_numbers[] = $ipn->phone_number;
		}
		?>
			<div>
				<h1>CallMe Config Page</h1>
				<p>This plug allows you to easily add a call me, conference with other readers, or leave me a voicemail button to your blog.</p>
				<div id="" class="">
					<h2>General Settings</h2>
					<h3>Twilio Credentials</h3>
					<form action="#" method="post" accept-charset="utf-8">
						<p><label>Twilio Account SID:<input type="text" name="twilio_sid" value="<?php echo (isset($callme_settings['twilio']['sid']) ? $callme_settings['twilio']['sid']:""); ?>"></label></p>
						<p><label>Twilio Auth Token: <input type="password" name="twilio_token" value=""></label><?php echo isset($callme_settings['twilio']['token'])? " <b>Token Saved</b>":""; ?></p>
					<h3>Widget Settings</h3>
					<p>
						<label>Location: <select name="widget_location">
							<option value="bottomright" <?php echo ((isset($callme_settings['widget']['location']) && $callme_settings['widget']['location']=='bottomright' ) ? 'selected':''); ?>>Bottom Right</option>
							<option value="topright" <?php echo ((isset($callme_settings['widget']['location']) && $callme_settings['widget']['location']=='topright') ?'selected':''); ?>>Top Right</option>
							<option value="topleft" <?php echo ((isset($callme_settings['widget']['location']) && $callme_settings['widget']['location']=='topleft' ) ? 'selected':''); ?>>Top Left</option>
							<option value="bottomleft" <?php echo ((isset($callme_settings['widget']['location']) && $callme_settings['widget']['location']=='bottomleft' ) ? 'selected':''); ?>>Bottom Left</option>
						</select></label>
					</p>
					<p>
						<label>Stylize: <textarea name="widget_stylesheet"><?php echo (isset($callme_settings['widget']['stylesheet'])? $callme_settings['widget']['stylesheet']: '' ); ?></textarea></label>
						<div class="callme_caption">Please enter valid css above in order to correctly stylize the widget.</div>
					</p>
					<input type="submit" name="save_settings" value="Save Settings">
				</form>
				
				</div>
				<div>
					<?php
					$callme_widget_type = (isset($callme_settings['widget']['type']) ? $callme_settings['widget']['type']:"callme")
					?>
					<h2>Customize CallMe Widget</h2>
					<select id="callme_widget_type" name="callme_widget_type">
						<option value="callme" <?php echo ($callme_widget_type=='callme'? 'selected':''); ?>>Call Me!</option>
						<option value="conference"  <?php echo ($callme_widget_type=='conference'? 'selected':''); ?>>Conference</option>
						<option value="voicemail"  <?php echo ($callme_widget_type=='voicemail'? 'selected':''); ?>>Voicemail</option>
					</select>
					<div id="callme" class="selected_widget_inputs"  <?php echo ($callme_widget_type!='callme'? 'style="display:none"':''); ?>>
						<h3>Call Me!</h3>
						<p>
							Visitors will be able to directly call you from your blog.  In order to call you, you will need a Twilio number to call from.
						</p>
						<form action="#" method="post" accept-charset="utf-8">
							<input type="hidden" name="callme_type" value="callme">
							<p>
								<label>Widget Text: <input type="text" name="widget_text" value="<?php echo (isset($callme_settings['callme']['widget_text']) ? $callme_settings['callme']['widget_text']:'Call Me!'); ?>"></label>
							</p>
							<p>
								<label>Twilio Number:
								<?php if (count($phone_numbers)) { ?>
										<select name="twilio_number">
											<?php foreach ($phone_numbers as $key=>$pn) {
												?><option value="<?php echo  $pn; ?>"><?php echo phoneNumberFormat($pn); ?></option>
											<?php } ?>
											
										</select>
								<?php
									}else{
										?>You do not have any numbers setup with Twilio. In order to use the CallMe feature you must either purchase a number from Twilio, or validate a number.<?php
									}
								?>
								
								</label>
								<div class="callme_caption">
									Select a number to display when receiving numbers from the widget.
								</div>
							</p>
							<p>
								<label>Number to reach you at: 	<input type="text" name="your_number" value="<?php echo (isset($callme_settings['callme']['your_number']) ? $callme_settings['callme']['your_number']:''); ?>"></label>
							</p>
						
							<input type="submit" value="Save">
						</form>
					</div>
					<div id="conference"  <?php echo ($callme_widget_type!='conference'? 'style="display:none"':''); ?> class="selected_widget_inputs">
						<h3>Conference Call</h3>
						<p>
							Visitors will be able to join a conference call with other visitors to you're blog.
						</p>
						<form action="#" method="post" accept-charset="utf-8">
							<p>
								<label>Widget Text: <input type="text" name="widget_text" value="<?php echo (isset($callme_settings['conference']['widget_text']) ? $callme_settings['conference']['widget_text']:'Talk to other readers!')	; ?>"></label>
							</p>
							<p>
								<label>Conference Call Welcome Message: <textarea name="conference_text"><?php echo (isset($callme_settings['conference']['conference_text']) ? $callme_settings['conference']['conference_text'] : 'Welcome to the group!'); ?></textarea></label>
							</p>
							<p>
								<label>Auto-connect conference call on connection: <input type="checkbox" name="conference_autoconnect" value="yes" <?php echo ((isset($callme_settings['conference']['autoconnect']) && $callme_settings['conference']['autoconnect']) ? "checked":"" ); ?>></label>
							</p>
							<p>
								<label>Limit call length: <input type="text" name="conference_length" value="<?php echo (isset($callme_settings['conference']['length']) && $callme_settings['conference']['length']) ? $callme_settings['conference']['length'] : '' ?>"> seconds</label>
							</p>
							<input type="hidden" name="callme_type" value="conference">
							<input type="submit" value="Save">
						</form>
					</div>
					<div id="voicemail"  <?php echo ($callme_widget_type!='voicemail'? 'style="display:none"':''); ?> class="selected_widget_inputs">
						<h3>Voicemail</h3>
						<p>
							When set to this, visitors will be able to leave you voicemails and we'll automatically have them forward to you're email with a recording.
						</p>
						<form action="#" method="post" accept-charset="utf-8">
							<p>
								<label>Widget Text: <input type="text" name="widget_text" value="<?php echo (isset($callme_settings['voicemail']['widget_text']) ? $callme_settings['voicemail']['widget_text']:'Leave me a voicemail!'); ?>"></label>
							</p>
							<p>
								<label for="voicemail_welcome">Message for voicemail: <textarea name="voicemail_welcome" rows="8" cols="40"><?php echo (isset($callme_settings['voicemail']['welcome']) ? $callme_settings['voicemail']['welcome']:''); ?></textarea></label>
							</p>
							<p>
								<label for="voicemail_lenght">Voicemail Length: <input type="text" name="voicemail_length" value="<?php echo (isset($callme_settings['voicemail']['length'])? $callme_settings['voicemail']['length'] : '60'); ?>" id="voicemail_length"> seconds</label>
							</p>
							<input type="hidden" name="callme_type" value="voicemail">
							<input type="submit" value="Save">
						</form>
					</div>
				</div>
			</div>
		
		<?php
	}

	//Styles to inject into WP main site
	function WPCallMe_Styles(){
		$callme_plugin_url = trailingslashit( get_bloginfo('wpurl') ).PLUGINDIR.'/CallMe';
		if (!is_admin()) {
			wp_enqueue_style( 'callme_public_style', $callme_plugin_url.'/css/callme.css',false,rand(),'screen');
		}else{
			wp_enqueue_style('callme_admin_style', $callme_plugin_url.'/css/callme.css', false,rand(),'screen');
		}
	}

	//Scripts to inject
	function WPCallMe_Scripts(){
		$callme_plugin_url = trailingslashit( get_bloginfo('wpurl') ).PLUGINDIR.'/CallMe';
		//Main site scripts
		 if (!is_admin()){
			wp_enqueue_script('jquery');
			wp_enqueue_script('jquery-form');
			wp_enqueue_script('callme_twilio_script','http://static.twilio.com/libs/twiliojs/1.0/twilio.min.js');
			wp_enqueue_script('callme_public_script', $callme_plugin_url.'/js/callme.js?id='.rand(), array('jquery', 'jquery-form','callme_twilio_script'));

		}else{
		//Admin site scripts
			  wp_enqueue_script('jquery');
			  wp_enqueue_script('jquery-form');
			  wp_enqueue_script('callme_admin_script', $callme_plugin_url.'/js/admin.js', array('jquery', 'jquery-form'),1,true);
		}
	}

	//HTML for main site to inject
	function WPCallMe_HTML(){
		global $callme_settings;
		if (!is_admin()) {
			// put your Twilio API credentials here
			if (isset($callme_settings['twilio']['sid']) && isset($callme_settings['twilio']['token']) && isset($callme_settings['widget']) && isset($callme_settings['widget']['type']) && isset($callme_settings['twilio']['app_sid'])) {
				$capability = new Services_Twilio_Capability($callme_settings['twilio']['sid'], $callme_settings['twilio']['token']);
				$capability->allowClientOutgoing($callme_settings['twilio']['app_sid']);
				$token = $capability->generateToken();
				if (isset($callme_settings['widget']['type'])) {
					$callme_widget_text = $callme_settings[$callme_settings['widget']['type']]['widget_text'];
				}
				?>
				<script>
				<?php
					if (isset($callme_settings['widget']['type']) and $callme_settings['widget']['type'] == 'conference') {
						?>var autoconnect_conference = <?php echo(isset($callme_settings['conference']['autoconnect']) ? $callme_settings['conference']['autoconnect'] : 'false'); ?>;
						<?php
					}else{
						?>var autoconnect_conference = false;
						<?php
					}
				?>
				var token = '<?php echo $token; ?>';
				</script>
				<div id="callme_widget" class="callme_<?php echo (isset($callme_settings['widget']['location']) ? $callme_settings['widget']['location']:'bottomright'); ?>" <?php if (isset($callme_settings['widget']) && isset($callme_settings['widget']['stylesheet'])) {
						echo 'style="'.$callme_settings['widget']['stylesheet'].'"';
					} ?>>
					<?php echo $callme_widget_text; ?>
				</div>
				<?php
			}
		}
	}
	/* FOR ADDING DASHBBOARD WIDGET */
	/**
	 * Content of Dashboard-Widget
	 */
	function callme_dashboard() {
		global $callme_settings, $twilio_client;
		if (isset($callme_settings['widget']['type']) && $callme_settings['widget']['type'] == 'voicemail') {
			$entries = $twilio_client->account->recordings->getPage();
		}else{
			$entries = array();
		}
		echo '<h2>Recent Activity</h2>';
		?>
		<h3><?php
		if (isset($callme_settings['widget']['type'])) {
			switch ($callme_settings['widget']['type']) {
				case 'voicemail':
					echo "Recordings";
					break;
				case 'conference':
					echo "Conferences";
				default:
					echo "Calls";
					break;
			}
		}
		?></h4>

		<div>Right now we don't collect the information from a response, so we are limited in what we can show here. Currently only recordings are supported, but it is known not to be perfect.  Whence Twilio supports filtering by Application we will quickly support seeing all calls, conferences and voicemails made with 100% accuracy.</div>

		<table style="width:100%">
				<thead>
					<tr>
						<th>Time</th>
						<th>Length</th>
						<th>Link</th>
					</tr>
				</thead>
				<tbody>
					<?php foreach ($entries->recordings as $key => $value) {
						$created = new DateTime($value->date_created);
						?>
								<td><?php echo $created->format('m-d-Y H:i:s'); ?> PST</td>
								<td><?php echo $value->duration ?> Seconds</td>
								<td><a href="https://api.twilio.com<?php echo $value->Uri ?>.mp3">Link</a></td>
							</tr>
							
						<?php
					} ?>
				</tbody>
			</table>
		<?php
	}
	/**
	 * add Dashboard Widget via function wp_add_dashboard_widget()
	 */
	function callme_wp_dashboard_setup() {
		wp_add_dashboard_widget( 'callme_dashboard', __( 'CallMe History' ), 'callme_dashboard' );
	}
	/**
	 * use hook, to integrate new widget
	 */
}

?>