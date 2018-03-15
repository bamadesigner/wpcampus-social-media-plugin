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

	/**
	 * Return the user capability string.
	 */
	public function get_user_cap_string() {
		return 'wpc_manage_social_media';
	}

	/**
	 * Get the share post types assigned
	 * for the Revive Social plugin.
	 */
	public function get_share_post_types() {
		return get_option( 'top_opt_post_type' ) ?: array();
	}

	/**
	 * Get the max message length depending on network.
	 *
	 * If no network is passed, will get max lengths
	 * for all networks.
	 *
	 * @args    $network - e.g. 'facebook' or 'twitter'.
	 * @return  int|array - if network, returns length for network. Array of all otherwise.
	 */
	public function get_max_message_length( $network = '' ) {

		// Holds the default numbers.
		$allowed_networks = array(
			'facebook' => 400,
			'twitter'  => 280,
		);

		// Get the data stored by Revive Social plugin.
		$formats = get_option( 'top_opt_post_formats' );

		if ( ! empty( $network ) ) {

			if ( ! empty( $allowed_networks[ $network ] ) ) {

				// Return length stored in options.
				if ( ! empty( $formats[ $network . '_top_opt_tweet_length'] ) ) {
					return (int) $formats[ $network . '_top_opt_tweet_length' ];
				}

				// If no set length in options, return default length.
				return (int) $allowed_networks[ $network ];
			}

			return 0;
		}

		// If no specific network, get all of them.
		$max_lengths = array();

		foreach( $allowed_networks as $network_key => $network_length ) {

			// Set length stored in options.
			if ( ! empty( $formats[ $network_key . '_top_opt_tweet_length'] ) ) {
				$max_lengths[ $network_key ] = (int) $formats[ $network_key . '_top_opt_tweet_length' ];
			}

			// If no set length in options, set default length.
			$max_lengths[ $network_key ] = (int) $network_length;
		}

		return $max_lengths;
	}

	/**
	 * Returns the message that Revive Social
	 * plugin generates for a post.
	 *
	 * @args    $post - the post object.
	 * @args    $network - the social media network, e.g. "facebook" or "twitter".
	 * @return  array - info for the post, including link and message.
	 */
	public function get_message_for_post( $post, $network ) {
		global $CWP_TOP_Core;
		if ( class_exists( 'CWP_TOP_Core' ) && method_exists( $CWP_TOP_Core, 'generateTweetFromPost' ) ) {
			return $CWP_TOP_Core->generateTweetFromPost( $post, $network );
		}
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
