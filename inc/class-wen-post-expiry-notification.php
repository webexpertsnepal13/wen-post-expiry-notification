<?php

class WEN_Post_Expiry_Notification {

	public function __construct() {

		$timezone_string = get_option( 'timezone_string' );
		if ( !empty( $timezone_string ) ) {
			$timezone_name = $timezone_string;
		} else {
			$timezone_offset = get_option( 'gmt_offset' );
			$timezone_name = timezone_name_from_abbr( '', ( $timezone_offset ) * 3600, 0 );
		}
		date_default_timezone_set( $timezone_name );

		add_action( 'plugins_loaded', array( $this, 'wpen_includes' ) );

		add_action( 'wpen_check_daily_for_post_expiry', array( $this, 'wpen_do_this_on_expiry' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'wpen_admin_scripts' ) );

	}

	public function wpen_includes() {
		if( is_admin() ) {
			require_once( WEN_EXPIRY_PLUGIN_PATH . 'inc/class-wpen-admin-settings.php' );
			require_once( WEN_EXPIRY_PLUGIN_PATH . 'inc/post-expiry-meta-boxes.php' );
		}
	}

	public function wpen_do_this_on_expiry() {
		require_once( WEN_EXPIRY_PLUGIN_PATH . 'inc/template/post-expiry-email-template.php' );
	}

	public function wpen_admin_scripts(){
		wp_enqueue_style( 'wpen-jquery-ui', WEN_EXPIRY_DIR_URI . '/assets/css/jquery-ui.css' );
		wp_enqueue_style( 'post-expiry', WEN_EXPIRY_DIR_URI . '/assets/css/post-expiry-custom.css' );

		wp_enqueue_script( 'jquery-ui-datepicker' );
		wp_enqueue_script( 'post-expiry-js', WEN_EXPIRY_DIR_URI . '/assets/js/post-expiry-custom.js' , array( 'jquery' ), '', true );
	}

}

new WEN_Post_Expiry_Notification();