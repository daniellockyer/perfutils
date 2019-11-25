<?php
/*
Plugin Name: PerfUtils Profiler
Text Domain: perfutils-profiler
Description: Quickly profile your WordPress site.
Author: Daniel Lockyer
Author URI: https://daniellockyer.com
License: GPLv2 or later
Version: 0.0.1
*/

defined('ABSPATH') OR exit;

define('PUP_FILE', __DIR__ . '/data.xt');

// Hooks
add_action('plugins_loaded', '__pup_loaded');
register_activation_hook(__FILE__, '__pup_check_xdebug_installed');

function __pup_loaded() {
	__pup_check_xdebug_installed();

	add_action('admin_bar_menu', '__pup_add_admin_links');
	add_action('init', '__pup_process_clear_request', 0);
}

function __pup_process_clear_request($data) {
	if ( empty($_GET['_perfutils']) OR $_GET['_perfutils'] !== 'profiler' ) {
    		return;
	}

	if ( empty($_GET['_wpnonce']) OR ! wp_verify_nonce($_GET['_wpnonce'], '_perfutils__profiler_nonce') ) {
    		return;
	}

	if ( ! is_admin_bar_showing() ) {
    		return;
	}

	add_action('wp_loaded', '__pup_on_finish', 100);
	xdebug_start_trace(PUP_FILE, XDEBUG_TRACE_COMPUTERIZED | XDEBUG_TRACE_NAKED_FILENAME);
}

function __pup_on_finish() {
	xdebug_stop_trace();

	if ( ! is_readable(PUP_FILE) ) {
		return;
	}

	$file = @fopen(PUP_FILE, 'r' );
	$file_size = filesize(PUP_FILE);
	$file_data = fread($file, $file_size);

	$response = wp_remote_post("https://app.perfutils.com/upload", array(
		'method' => 'POST',
		'timeout' => 10,
		'blocking' => true,
		'body' => $file_data,
		'headers' => array(
			'accept'        => 'application/json',
			'content-type'  => 'application/binary',
		),
	));

	if ( is_wp_error( $response ) ) {
	   	$error_message = $response->get_error_message();
	   	echo "Something went wrong: $error_message";
	} else {
	   	print_r( $response['body'] );
	}

	unlink(PUP_FILE);

	// show OK message
}

function __pup_check_xdebug_installed() {
	if (extension_loaded('xdebug') === false) {
    		show_message(sprintf('<div class="error"><p>%s</p></div>', sprintf(__('<b>%s</b> requires Xdebug in order to work.', 'perfutils-profiler'), 'PerfUtils Profiler')));
	}
}

function __pup_add_admin_links($wp_admin_bar) {
	if ( ! is_admin_bar_showing() ) {
    		return;
	}

	$wp_admin_bar->add_menu(
    		array(
			'id'     => 'perfutils-profiler',
			'href'   => wp_nonce_url( add_query_arg('_perfutils', 'profiler'), '_perfutils__profiler_nonce'),
			'parent' => 'top-secondary',
			'title'	 => '<span class="ab-item">'.esc_html__('PerfUtils Profiler', 'perfutils-profiler').'</span>',
			'meta'   => array( 'title' => esc_html__('PerfUtils Profiler', 'perfutils-profiler') )
    		)
	);
}
