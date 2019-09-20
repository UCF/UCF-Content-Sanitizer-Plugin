<?php
/*
Plugin Name: UCF Content Sanitizer Plugin
Description: Provides filters and utilities for sanitizing WordPress post content.
Version: 0.0.0
Author: UCF Web Communications
License: GPL3
GitHub Plugin URI: UCF/UCF-Content-Sanitizer-Plugin
*/

if ( ! defined( 'WPINC' ) ) {
    die;
}

define( 'UCF_SANITIZER__PLUGIN_URL', plugins_url( basename( dirname( __FILE__ ) ) ) );
define( 'UCF_SANITIZER__PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'UCF_SANITIZER__STATIC_URL', UCF_SANITIZER__PLUGIN_URL . '/static' );
define( 'UCF_SANITIZER__JS_URL', UCF_SANITIZER__STATIC_URL . '/js' );
define( 'UCF_SANITIZER__PLUGIN_FILE', __FILE__ );


require_once 'admin/class-ucf-sanitizer-config.php';
require_once 'admin/class-ucf-sanitizer-admin.php';
require_once 'includes/class-ucf-sanitizer-common.php';

if ( defined( 'WP_CLI' ) && WP_CLI ) {
	require_once 'commands/class-ucf-sanitizer-sanitize-content.php';
	require_once 'commands/class-ucf-sanitizer-run-all.php';

	WP_CLI::add_command( 'ucfsanitizer sanitize content', 'UCF_Sanitizer_Command_Sanitize_Content' );
	WP_CLI::add_command( 'ucfsanitizer sanitize all', 'UCF_Sanitizer_Command_Sanitize_All' );
}


if ( ! function_exists( 'ucf_sanitizer_activation' ) ) {
	/**
	 * Function that runs on plugin activation
	 * @author Jo Dickson
	 * @since 1.0.0
	 */
	function ucf_sanitizer_activation() {
		UCF_Sanitizer_Config::add_options();
	}

	register_activation_hook( UCF_SANITIZER__PLUGIN_FILE, 'ucf_sanitizer_activation' );
}

if ( ! function_exists( 'ucf_sanitizer_deactivation' ) ) {
	/**
	 * Function that runs on plugin deactivation
	 * @author Jo Dickson
	 * @since 1.0.0
	 */
	function ucf_sanitizer_deactivation() {
		UCF_Sanitizer_Config::delete_options();
	}

	register_deactivation_hook( UCF_SANITIZER__PLUGIN_FILE, 'ucf_sanitizer_deactivation' );
}

if ( ! function_exists( 'ucf_sanitizer_init' ) ) {
	/**
	 * Function that runs when all plugins are loaded
	 * @author Jo Dickson
	 * @since 1.0.0
	 */
	function ucf_sanitizer_init() {
		// Add admin menu item
		add_action( 'admin_init', array( 'UCF_Sanitizer_Config', 'settings_init' ), 10, 0 );
		add_action( 'admin_menu', array( 'UCF_Sanitizer_Config', 'add_options_page' ), 10, 0 );

		// Admin assets and other modifications
		add_action( 'admin_enqueue_scripts', array( 'UCF_Sanitizer_Admin', 'admin_enqueue_scripts' ), 10, 1 );
		add_filter( 'tiny_mce_before_init', array( 'UCF_Sanitizer_Admin', 'configure_tinymce' ), 10, 1 );

		// Init actions
		add_action( 'init', array( 'UCF_Sanitizer_Config', 'add_option_formatting_filters' ), 10, 0 );

		// Post save filters
		add_filter( 'wp_insert_post_data', array( 'UCF_Sanitizer_Common', 'add_post_save_content_sanitizers' ), 99, 2 );
	}

	add_action( 'plugins_loaded', 'ucf_sanitizer_init', 10, 0 );
}
