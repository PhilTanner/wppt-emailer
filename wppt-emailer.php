<?php
	/*
		Plugin Name: Phil Tanner's Emailer 
		Plugin URI:  https://github.com/PhilTanner/wppt_emailer
		Description: Resolution of continual email woes
		Version:     0.1
		Author:      Phil Tanner
		Author URI:  https://github.com/PhilTanner
		License:     GPL3
		License URI: http://www.gnu.org/licenses/gpl.html
		Domain Path: /languages
		Text Domain: wppt_emailer

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
	$version    = "0.1";
	update_option( "wppt_emailer_version",    $version,    true );
	
	// Location that we're going to store our log files in
	define( 'WPPT_EMAILER_LOG_DIR',     WP_CONTENT_DIR.DIRECTORY_SEPARATOR.'logs'.DIRECTORY_SEPARATOR.'wppt_emailer'.DIRECTORY_SEPARATOR );
	define( 'WPPT_EMAILER_TEST_TO',     'wppt_emailer_'.time() );
	define( 'WPPT_EMAILER_TEST_TO_ADDR', WPPT_EMAILER_TEST_TO.'@email.ghostinspector.com' );
	define( 'WPPT_EMAILER_TEST_SUBJECT','wppt_emailer Test email' );
	define( 'WPPT_EMAILER_TEST_MESSAGE','This is a test email from the email system. Success!' );
	
	// Some custom exceptions for error handing
	class wppt_emailer_Exception                          extends Exception {}
		class wppt_emailer_Exception_Remote               extends wppt_emailer_Exception {}
			class wppt_emailer_Exception_Remote_Refused               extends wppt_emailer_Exception_Remote {}
			class wppt_emailer_Exception_Remote_Incorrect_Credentials               extends wppt_emailer_Exception_Remote {}
			class wppt_emailer_Exception_Remote_Require_Authentication               extends wppt_emailer_Exception_Remote {}
				class wppt_emailer_Exception_Remote_Unknown_Auth               extends wppt_emailer_Exception_Remote_Require_Authentication {}
	/* 
	 * This section holds our WordPress Plugin management functions
	 */
	// User "activates" the plugin in the dashboard
	function wppt_emailer_activate(){
		// Create our log file directory
		wp_mkdir_p( WPPT_EMAILER_LOG_DIR );
		// Create an htaccess file to prevent it being accessed from the web
		if( !file_exists( WPPT_EMAILER_LOG_DIR . '/.htaccess' ) ) {
			$fp = fopen(WPPT_EMAILER_LOG_DIR . '/.htaccess', 'w');
			fwrite($fp, 'Options -Indexes' );
			fwrite($fp, '<Files "*.log">'."\n");
			fwrite($fp, '	Order Allow,Deny'."\n");
			fwrite($fp, '	Deny from all'."\n");
			fwrite($fp, '</Files>'."\n");
			fclose($fp);
		}
		// Update our plugin version to this one
		update_option( "wppt_emailer_version", $version );
	}
	register_activation_hook( __FILE__, 'wppt_emailer_activate' );

	// User "deactivates" the plugin in the dashboard
	function wppt_emailer_deactivate() {
		//global $wpdb;

	}
	register_deactivation_hook( __FILE__, 'wppt_emailer_deactivate' );

	// Plugin deleted
	function wppt_emailer_uninstall() {
		// If uninstall is not called from WordPress (i.e. is called via URL or command line)
		if ( !defined( 'WP_UNINSTALL_PLUGIN' ) ) {
			wp_die();
		}

		// Remove all our settings
		delete_option( "wppt_emailer_version"   );
		delete_option( "wppt_emailer_smtpdebug" );
		delete_option( "wppt_emailer_smtp_host" );
		delete_option( "wppt_emailer_smtp_auth" );
		delete_option( "wppt_emailer_port"      );
		delete_option( "wppt_emailer_username"  );
		delete_option( "wppt_emailer_password"  );
		delete_option( "wppt_emailer_smtpsecure");

		// Remove our log files
		rmdir( WPPT_EMAILER_LOG_DIR, true );

	}
	register_uninstall_hook( __FILE__, 'wppt_emailer_uninstall' );
	
	// Load our JS scripts - we're gonna use jQuery & jQueryUI Dialog boxes
	// Taken from https://developer.wordpress.org/reference/functions/wp_enqueue_script/
	function wppt_emailer_load_admin_scripts($hook) {
		wp_enqueue_script( 'jquery' );
		wp_enqueue_script( 'jquery-effects-core' );
		wp_enqueue_script( 'jquery-ui-core' );
		wp_enqueue_script( 'jquery-ui-dialog' );
		wp_enqueue_script( 'jquery-ui-widget' );
	}
	add_action('admin_enqueue_scripts', 'wppt_emailer_load_admin_scripts');
	
	// Load our style sheets
	function wppt_emailer_load_admin_styles($hook) {
		global $wp_scripts;
		
		wp_register_style( 
			'wppt_emailer_admin',	
			plugins_url( '/css/admin.css', __FILE__ ), 
			false,	
			date("ymd-Gis", filemtime( plugin_dir_path( __FILE__ ) .'/css/admin.css' )  )
		);
		wp_enqueue_style ( 'wppt_emailer_admin' );

		// Grab a generic jQueryUI stylesheet
		wp_enqueue_style(
			'jquery-ui-redmond',
			'//ajax.googleapis.com/ajax/libs/jqueryui/'.$wp_scripts->registered['jquery-ui-core']->ver.'/themes/redmond/jquery-ui.min.css');
	
	}
	add_action('admin_enqueue_scripts', 'wppt_emailer_load_admin_styles');


	/* 
	 * This section handles our custom functions
	 */

	// If we're an admin, load our admin console.
	if ( is_admin() ) {
		// We are in admin mode
		require_once( dirname(__FILE__).'/admin/admin.php' );
	}

	// Log any errors for our later perusal
	function wppt_emailer_log_error( $logfile, $err ){
		$fn = WPNZCFCN_LOG_DIR . $logfile . '.log';
		$fp = fopen($fn, 'a');
		fputs($fp, date('c')."\t" . json_encode($err) ."\n");
		fclose($fp);
	}

	// Take over our PHPMailer settings
	function wppt_emailer_phpmailer_settings( $phpmailer ) {
		// Set our debug level (Default to 'off' for production cases)
		$phpmailer->SMTPDebug=  get_option( 'wppt_emailer_smtpdebug',     0 );
		// We're always going to use SMTP
		$phpmailer->isSMTP();
		// What SMTP host are we going to be sending out mail through?
		$phpmailer->Host     = get_option( 'wppt_emailer_smtp_host' );
		// Do we need authorisation to access this email host?
		$phpmailer->SMTPAuth = get_option( 'wppt_emailer_smtp_auth', false );
		// What SMTP port do we want to access?
		$phpmailer->Port     = get_option( 'wppt_emailer_port',          25 );
		// We're only going to give it auth credentials if we're going to auth
		if( $phpmailer->SMTPAuth ) {
			$phpmailer->Username = get_option( 'wppt_emailer_username' );
			$phpmailer->Password = get_option( 'wppt_emailer_password' );
		}
		$phpmailer->SMTPSecure = get_option( 'wppt_emailer_smtpsecure' );

		// We *know* some stuff for some hosts, so we'll override any stupid 
		// settings
		switch( $phpmailer->Host ) {
			// Gmail wants the below
			/*
			case 'smtp.gmail.com':
				$phpmailer->SMTPSecure = 'tls';
				$phpmailer->Port       = 465;
				$phpmailer->SMTPAuth   = true;
				break;
				*/
		}
	}
	// Nice high priority, so it should be run last, overriding any other plugin
	// settings
	add_action( 'phpmailer_init', 'wppt_emailer_phpmailer_settings', 9999 );

	// Trap any WordPress email failures
	function wppt_emailer_log_mailer_errors( $mailer ){
		// First off, throw the contents into a logfile for us.
		wppt_emailer_log_error( 'mail', $mailer );
		// Then, see if we can say what went wrong to the end user
	}
	add_action('wp_mail_failed', 'wppt_emailer_log_mailer_errors', 10, 1);

	// www.gnuterrypratchett.com
	function wppt_emailer_add_header_xua() {
		header( 'X-Clacks-Overhead: GNU Terry Pratchett' );
	}
	add_action( 'send_headers', 'wppt_emailer_add_header_xua' );