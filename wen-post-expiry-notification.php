<?php
/*
 * Plugin Name:  WEN Post Expiry Notification
 * Description:  Set expiry date on posts and custom post types, and send email notification on their expiry.
 * Version:      1.1
 * Author:       Web Experts Nepal
 * Author URI:   https://www.webexpertsnepal.com/
 * Text Domain:  wen-post-expiry-notification
*/

defined('ABSPATH') or die('no');

define( 'WEN_EXPIRY_PLUGIN_PATH', plugin_dir_path( __FILE__ ) );
define( 'WEN_EXPIRY_DIR_URI', plugin_dir_url( __FILE__ ) );

require_once( WEN_EXPIRY_PLUGIN_PATH . '/inc/class-wen-post-expiry-notification.php' );

function wpen_activation() {
	register_uninstall_hook( __FILE__, 'wpen_plugin_uninstall' );

	if( !wp_next_scheduled( 'wpen_check_daily_for_post_expiry' ) ) {
		$wpen_email_options = get_option( 'wpen_email' );
		if( isset( $wpen_email_options['wpen_cron_time'] ) && !empty( $wpen_email_options['wpen_cron_time'] ) ) {
			$cron_run_time = $wpen_email_options['wpen_cron_time'];
		} else {
			$cron_run_time = '08:00:00';
		}
		wp_schedule_event( strtotime( $cron_run_time ), 'daily', 'wpen_check_daily_for_post_expiry' );
	}
}
register_activation_hook( __FILE__, 'wpen_activation' );

function wpen_deactivation() {
	$timestamp = wp_next_scheduled( 'wpen_check_daily_for_post_expiry' );
	wp_unschedule_event( $timestamp, 'wpen_check_daily_for_post_expiry' );
}
register_deactivation_hook ( __FILE__, 'wpen_deactivation' );

function wpen_plugin_uninstall() {
	global $wpdb;

	$plugin_options = $wpdb->get_results( "SELECT option_name FROM $wpdb->options WHERE option_name LIKE 'wpen_%'" );

	foreach( $plugin_options as $option ) {
	    delete_option( $option->option_name );
	}
}