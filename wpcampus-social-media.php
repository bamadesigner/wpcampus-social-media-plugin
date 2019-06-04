<?php
/**
 * Plugin Name:       WPCampus: Social Media
 * Plugin URI:        https://github.com/wpcampus/wpcampus-social-media-plugin
 * Description:       Manages social media functionality for the WPCampus websites.
 * Version:           1.0.0
 * Author:            WPCampus
 * Author URI:        https://wpcampus.org
 * Text Domain:       wpcampus-social
 * Domain Path:       /languages
 */

/*
 * @TODO:
 *  - Add button to tweet immediately.
 *  - Remove usage of TOP or revive social
 */

defined( 'ABSPATH' ) or die();

/*
 * Load plugin files.
 */
$plugin_dir = plugin_dir_path( __FILE__ );

// Load the main class and global functionality.
require_once $plugin_dir . 'inc/class-wpcampus-social-media.php';
require_once $plugin_dir . 'inc/class-wpcampus-social-media-global.php';

// Load admin functionality in the admin.
if ( is_admin() ) {
	require_once $plugin_dir . 'inc/wpcampus-social-media-fields.php';
	require_once $plugin_dir . 'inc/class-wpcampus-social-media-admin.php';
}

/**
 * Returns the instance of our main WPCampus_Social_Media class.
 *
 * Use this function and class methods to retrieve plugin data.
 *
 * @return object - WPCampus_Social_Media
 */
function wpcampus_social_media() {
	return WPCampus_Social_Media::instance();
}
