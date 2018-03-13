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

defined( 'ABSPATH' ) or die();

$plugin_dir = wpcampus_social_media()->get_plugin_dir();

require_once $plugin_dir . 'inc/class-wpcampus-social-media-global.php';

if ( is_admin() ) {
	require_once $plugin_dir . 'inc/class-wpcampus-social-media-admin.php';
}

/**
 * Class that manages and returns plugin data.
 *
 * @class       WPCampus_Social_Media
 * @package     WPCampus Social Media
 */
final class WPCampus_Social_Media {

	/**
	 * Holds the plugin version.
	 *
	 * @var string
	 */
	private $version = '1.0.0';

	/**
	 * Holds the absolute URL and
	 * the directory path to the
	 * main plugin directory.
	 *
	 * @var string
	 */
	private $plugin_url;
	private $plugin_dir;

	/**
	 * Holds the class instance.
	 *
	 * @var WPCampus_Social_Media
	 */
	private static $instance;

	/**
	 * Returns the instance of this class.
	 *
	 * @return WPCampus_Social_Media
	 */
	public static function instance() {
		if ( ! isset( self::$instance ) ) {
			$class_name     = __CLASS__;
			self::$instance = new $class_name;
		}
		return self::$instance;
	}

	/**
	 * Magic method to output a string if
	 * trying to use the object as a string.
	 *
	 * @return string
	 */
	public function __toString() {
		// translators: Basic name of the plugin
		return sprintf( __( '%s: Social Media', 'wpcampus-social' ), 'WPCampus' );
	}

	/**
	 * Method to keep our instance
	 * from being cloned or unserialized
	 * and to prevent a fatal error when
	 * calling a method that doesn't exist.
	 *
	 * @return void
	 */
	public function __clone() {}
	public function __wakeup() {}
	public function __call( $method = '', $args = array() ) {}

	/**
	 * Start your engines.
	 */
	protected function __construct() {

		// Store the plugin URL and DIR.
		$this->plugin_url = plugin_dir_url( __FILE__ );
		$this->plugin_dir = plugin_dir_path( __FILE__ );

	}

	/**
	 * Returns the plugin version.
	 *
	 * @return string
	 */
	public function get_version() {
		return $this->version;
	}

	/**
	 * Returns the absolute URL to
	 * the main plugin directory.
	 *
	 * @return string
	 */
	public function get_plugin_url() {
		return $this->plugin_url;
	}

	/**
	 * Returns the directory path
	 * to the main plugin directory.
	 *
	 * @return string
	 */
	public function get_plugin_dir() {
		return $this->plugin_dir;
	}
}

/**
 * Returns the instance of our WPCampus_Social_Media class.
 *
 * Use this function and class methods
 * to retrieve plugin data.
 *
 * @return  WPCampus_Social_Media
 */
function wpcampus_social_media() {
	return WPCampus_Social_Media::instance();
}
