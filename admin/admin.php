<?php
	/*
		Plugin Name: Phil Tanner's Emailer 
		Plugin URI:  https://github.com/PhilTanner/wppt_emailer

		Copyright (C) 2017 Phil Tanner

		This program is free software: you can redistribute it and/or modify
		it under the terms of the GNU General Public License as published by
		the Free Software Foundation, either version 3 of the License, or
		(at your option) any later version.

		This program is distributed in the hope that it will be useful,
		but WITHOUT ANY WARRANTY; without even the implied warranty of
		MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
		GNU General Public License for more details.

		You should have received a copy of the GNU General Public License
		along with this program. If not, see <http://www.gnu.org/licenses/>.
	*/

	// Create our own Emailer menu items
	// Taken from:https://codex.wordpress.org/Adding_Administration_Menus
	function wppt_emailer_menu_add(){
		// Add a new main menu level for CadetNet Admin
		add_menu_page( 
			__("Phil's Emailer", 'wppt_emailer'), // Page title
			__("Phil's Emailer", 'wppt_emailer'),	// Menu text
			"manage_options", // Capability required (Needed to save option changes to system)
			"wppt_emailer_menu", // Menu slug (unique name)
			"wppt_emailer_menu", // Function to be called when displaying content
			"dashicons-email-alt" // The url to the icon to be used for this menu. This parameter is optional.
		);
	}
	add_action( 'admin_menu', 'wppt_emailer_menu_add' );

	// Output our page contents
	function wppt_emailer_menu() {
		// User must be an admin to access
		if ( !current_user_can( "manage_options" ) )	{
			wp_die( __( "You do not have sufficient permissions to access this page." ) );
		}
		
		// So, start our page proper
		echo "<h2>" . __("Phil's Emailer v".get_option('wppt_emailer_version'), "wppt_emailer") . " </h2>";
		
		// We've got some settings to save, do so before we output them again
		if( isset($_POST['action']) ) {
			try {
				if( $_POST['action'] == 'test' ) {
					// First off, grab what our settings are now
					$settings = array();
					$settings["wppt_emailer_smtpdebug"]  = get_option("wppt_emailer_smtpdebug" );
					$settings["wppt_emailer_smtp_host"]  = get_option("wppt_emailer_smtp_host" );
					$settings["wppt_emailer_smtp_auth"]  = get_option("wppt_emailer_smtp_auth" );
					$settings["wppt_emailer_port"]       = get_option("wppt_emailer_port"      );
					$settings["wppt_emailer_username"]   = get_option("wppt_emailer_username"  );
					$settings["wppt_emailer_password"]   = get_option("wppt_emailer_password"  );
					$settings["wppt_emailer_smtpsecure"] = get_option("wppt_emailer_smtpsecure");
					
					// Then update them to what we've asked for
					update_option("wppt_emailer_smtpdebug",  4 ); // Except we always want most output while testing
					update_option("wppt_emailer_smtp_host",  $_POST["wppt_emailer_smtp_host"] );
					update_option("wppt_emailer_smtp_auth",  $_POST["wppt_emailer_smtp_auth"] );
					update_option("wppt_emailer_port",       $_POST["wppt_emailer_port"]      );
					update_option("wppt_emailer_username",   $_POST["wppt_emailer_username"]  );
					update_option("wppt_emailer_password",   $_POST["wppt_emailer_password"]  );
					update_option("wppt_emailer_smtpsecure", $_POST["wppt_emailer_smtpsecure"]);
					
					// Then start capturing our output
					ob_start();
					$mail = wp_mail( WPPT_EMAILER_TEST_TO_ADDR, WPPT_EMAILER_TEST_SUBJECT, WPPT_EMAILER_TEST_MESSAGE );
					$mailoutput = ob_get_contents();
					ob_end_clean();
					
					// Then reset our options to what they were (we were testing, not saving after all)
					update_option("wppt_emailer_smtpdebug",  $settings["wppt_emailer_smtpdebug"] );
					update_option("wppt_emailer_smtp_host",  $settings["wppt_emailer_smtp_host"] );
					update_option("wppt_emailer_smtp_auth",  $settings["wppt_emailer_smtp_auth"] );
					update_option("wppt_emailer_port",       $settings["wppt_emailer_port"]      );
					update_option("wppt_emailer_username",   $settings["wppt_emailer_username"]  );
					update_option("wppt_emailer_password",   $settings["wppt_emailer_password"]  );
					update_option("wppt_emailer_smtpsecure", $settings["wppt_emailer_smtpsecure"]);
					
					// Now, see if we can see any issues we can help you start debugging
					if( strpos($mailoutput, '10061') !== false || strpos($mailoutput, 'No connection could be made because the target machine actively refused it.') !== false ) {
						throw new wppt_emailer_Exception_Remote_Refused(sprintf(__('The remote server actively refused our connection. SMTP Host "%s" is not listening on port %d. Check your settings and try again.','wppt_emailer'), $_POST["wppt_emailer_smtp_host"], $_POST["wppt_emailer_port"]));
					}
					if( strpos($mailoutput, '530-5.5.1 Authentication Required.') !== false ) {
						throw new wppt_emailer_Exception_Remote_Require_Authentication(sprintf(__('Couldn\'t connec to SMTP Host "%s" on port %d. Username and password is required.','wppt_emailer'), $_POST["wppt_emailer_smtp_host"], $_POST["wppt_emailer_port"]));
					}
					if( strpos($mailoutput, '535-5.7.8 Username and Password not accepted.') !== false ) {
						throw new wppt_emailer_Exception_Remote_Incorrect_Credentials(sprintf(__('Couldn\'t connec to SMTP Host "%s" on port %d. Username and password is incorrect.','wppt_emailer'), $_POST["wppt_emailer_smtp_host"], $_POST["wppt_emailer_port"]));
					}
					if( strpos($mailoutput, '534 5.7.14  https://support.google.com/mail/answer/78754') !== false ) {
						throw new wppt_emailer_Exception_Remote_Unknown_Auth(__('Unknown authentication method. Try TLS? Or enable less secure apps: https://support.google.com/accounts/answer/6010255.','wppt_emailer'));
					}
					
					// Something went wrong, but we've no idea what. 
					if( strpos($mailoutput, 'SMTP Error: Could not connect to SMTP host') !== false ) {
						throw new wppt_emailer_Exception(__('Unknown error. Check your port number matches your encryption type?','wppt_emailer'));
					}
				} elseif( $_POST['action'] == 'save' ) {
					update_option("wppt_emailer_smtpdebug",  $_POST["wppt_emailer_smtpdebug"] );
					update_option("wppt_emailer_smtp_host",  $_POST["wppt_emailer_smtp_host"] );
					update_option("wppt_emailer_smtp_auth",  $_POST["wppt_emailer_smtp_auth"] );
					update_option("wppt_emailer_port",       $_POST["wppt_emailer_port"]      );
					update_option("wppt_emailer_username",   $_POST["wppt_emailer_username"]  );
					update_option("wppt_emailer_password",   $_POST["wppt_emailer_password"]  );
					update_option("wppt_emailer_smtpsecure", $_POST["wppt_emailer_smtpsecure"]);
				}
			} catch( wppt_emailer_Exception_Remote_Refused $Ex ) {
				echo '<div class="ui-state-error">';
				echo '<p>'.$Ex.'</p>';
				echo '</div>';
				wppt_emailer_log_error( 'AdminUpdates', $Ex );
			} catch( Exception $Ex ) {
				echo '<div class="ui-state-error">';
				echo '<p>'.__('Server reported:','wppt_emailer').'</p>';
				echo '<pre>'.$Ex.'</pre>';
				echo '</div>';
				wppt_emailer_log_error( 'AdminUpdates', $Ex );
			} 		
		}
		
		?>
		
		<form style="margin-right:2em;" method="post">			
			<fieldset style="float:left; width: 50%;">
				<legend>Email Settings</legend>
				
				<p>
					<label for="wppt_emailer_smtpdebug">Debug level<br/>(0 = Off, 3=Max)</label>
					<input name="wppt_emailer_smtpdebug" id="wppt_emailer_smtpdebug" type="number" value="<?=get_option("wppt_emailer_smtpdebug",0);?>" min="0" max="4" step="1" required="required" />
					<em>Note: This should be <strong>0</strong> for live web sites!</em>
				</p>
				
				<p>
					<label for="wppt_emailer_smtp_host">SMTP Host</label>
					<input name="wppt_emailer_smtp_host" id="wppt_emailer_smtp_host" placeholder="e.g. smtp.gmail.com" value="<?=get_option('wppt_emailer_smtp_host');?>" required="required" />
					<label for="wppt_emailer_port" style="width:auto">Port</label>
					<input name="wppt_emailer_port" id="wppt_emailer_port" type="number" value="<?=get_option('wppt_emailer_port', 25);?>" required="required" />
				</p>
				
				<p>
					Use username/password to sign in?<br />
					<label for="wppt_emailer_smtp_auth_y">Yes</label>
					<input type="radio" name="wppt_emailer_smtp_auth" id="wppt_emailer_smtp_auth_y" value="1" required="required"<?=(get_option('wppt_emailer_smtp_auth')?' checked="checked"':'');?> />
					<label for="wppt_emailer_smtp_auth_n">No</label>
					<input type="radio" name="wppt_emailer_smtp_auth" id="wppt_emailer_smtp_auth_n" value="0" required="required"<?=(get_option('wppt_emailer_smtp_auth')?'':' checked="checked"');?> />
				</p>
				
				<div id="auth"<?=(get_option('wppt_emailer_smtp_auth')?'':' style="display:none;"');?>>
					<p>
						<label for="wppt_emailer_username">Username</label>
						<input name="wppt_emailer_username" id="wppt_emailer_username" value="<?=get_option('wppt_emailer_username');?>" required="required" />
					</p>
					<p>
						<label for="wppt_emailer_password">Password</label>
						<input name="wppt_emailer_password" id="wppt_emailer_username" type="password" value="<?=get_option('wppt_emailer_password');?>" required="required" />
					</p>
					<p>
						<label for="wppt_emailer_smtpsecure">Encrypted sign in?</label>
						<select name="wppt_emailer_smtpsecure" id="wppt_emailer_smtpsecure" required="required">
							<option value='none'<?=(get_option('wppt_emailer_smtpsecure')==''?' selected="selected"':'');?>>No</option>
							<option value='tls'<?=(get_option('wppt_emailer_smtpsecure')=='tls'?' selected="selected"':'');?>>Yes - using <strong>TLS</strong></option>
							<option value='ssl'<?=(get_option('wppt_emailer_smtpsecure')=='ssl'?' selected="selected"':'');?>>Yes - using <strong>SSL</strong></option>
						</select>
					</p>
				</div>
				<button type="submit" value="test" name="action">Test</button>
				<button type="submit" value="save" name="action">Save</button>
			</fieldset>
			<fieldset style="float:left; width: 50%;">
				<legend>Email test results</legend>
				<?php
				if( $_POST['action'] == 'test' ) {
				?>
					<iframe style="width: 100%;" src="https://email.ghostinspector.com/<?=WPPT_EMAILER_TEST_TO;?>/latest"></iframe>
					<pre style="overflow:scroll; width:100%; height:16em;background-color:Silver;border:1px solid black; white-space: pre-wrap;"><?=$mailoutput?></pre>
				<?php } else { ?>
					<p> No test running... </p>
				<?php } ?>
			</fieldset>
			
		</form>
		
		<!--
			<fieldset style="clear:left;">
				<legend>Log Files</legend>
				<ul>
					<?php
						$logs = scandir(WPPT_EMAILER_LOG_DIR);
						
						foreach( $logs as $log ){
							if( strtolower(substr($log, -4)) == ".log" ) {
								echo '<li> <a href="javascript:showlog(\''.$log.'\');">'.$log.'</a> </li>';
							}
						}
					?>
				</ul>
			</fieldset>
		-->
		
		<script defer="defer">
			jQuery(document).ready( function($){
				// As and when we say we want auth, show the options
				jQuery('input[name="wppt_emailer_smtp_auth"]').change(function(){
					if( jQuery('#wppt_emailer_smtp_auth_y').prop('checked') ) {
						jQuery('#auth').show();
					} else jQuery('#auth').hide();
				});
				
				// If we select secure SMTP, see if we want to change the port
				jQuery('#wppt_emailer_smtpsecure').change(function() {
					var defaultPorts = {none: 25, ssl: 465, tls: 587 };
					var currPort = jQuery('#wppt_emailer_port').val();
					var suggestedPort = eval('defaultPorts.'+jQuery('#wppt_emailer_smtpsecure').val());
					
					if( currPort != suggestedPort ) {
						jQuery('<div></div>').html('<p>The default SMTP port for this encryption type is <strong>'+
							suggestedPort+'</strong>, but you have requested port <strong>'+currPort+'</strong>.</p>'+
							'<p>Do you want to update your settings to use Port <strong>'+suggestedPort+'</strong> instead?</p>').dialog({
							modal:true,
							title:'Change port',
							buttons: [
								{ text: 'Yes', click: function(){ jQuery('#wppt_emailer_port').val(suggestedPort); jQuery(this).dialog('close'); } },
								{ text: 'No', click: function(){ jQuery(this).dialog('close'); } }
							]
						});
					}
				});
				
				// Prettify our buttons
				jQuery('form button[type="submit"]').button({ icons:{ primary: 'ui-icon-wrench' } }).filter('[value="save"]').button({icons: { secondary: 'ui-icon-disk'}}).css({float:'right'});
			});
		</script>
		<?php
	}
	