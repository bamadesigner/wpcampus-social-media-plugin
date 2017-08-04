<?php
/**
 * Plugin Name:       WPCampus Social Media
 * Plugin URI:        https://github.com/wpcampus/wpcampus-social-media-plugin
 * Description:       Holds social media functionality for the WPCampus websites.
 * Version:           1.0.0
 * Author:            WPCampus
 * Author URI:        https://wpcampus.org
 * Text Domain:       wpcampus
 * Domain Path:       /languages
 */

// We only need you in the admin.
if ( is_admin() ) {
	require_once wpcampus_social_media()->plugin_dir . 'inc/wpcampus-social-media-admin.php';
}

/**
 * The main class for all WPCampus media types.
 *
 * Class    WPCampus_Social_Media
 */
class WPCampus_Social_Media {

	/**
	 * Holds the absolute URL to
	 * the main plugin directory.
	 *
	 * And the directory path
	 * to the main plugin directory.
	 *
	 * @access  public
	 * @var     string
	 */
	public $plugin_url;
	public $plugin_dir;

	/**
	 * Holds the class instance.
	 *
	 * @access  private
	 * @var     WPCampus_Social_Media
	 */
	private static $instance;

	/**
	 * Returns the instance of this class.
	 *
	 * @access  public
	 * @return  WPCampus_Social_Media
	 */
	public static function instance() {
		if ( ! isset( self::$instance ) ) {
			$class_name = __CLASS__;
			self::$instance = new $class_name;
		}
		return self::$instance;
	}

	/**
	 * Warming things up.
	 *
	 * @access  protected
	 */
	protected function __construct() {

		// Store the plugin URL and DIR.
		$this->plugin_url = plugin_dir_url( __FILE__ );
		$this->plugin_dir = plugin_dir_path( __FILE__ );

		// Load our textdomain.
		add_action( 'init', array( $this, 'textdomain' ) );

	}

	/**
	 * Method to keep our instance from
	 * being cloned or unserialized.
	 *
	 * @access	private
	 * @return	void
	 */
	private function __clone() {}
	private function __wakeup() {}

	/**
	 * Internationalization FTW.
	 * Load our textdomain.
	 *
	 * @TODO Add language files
	 *
	 * @access  public
	 * @return  void
	 */
	public function textdomain() {
		load_plugin_textdomain( 'wpcampus', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );
	}
}

/**
 * Returns the instance of our main WPCampus_Social_Media class.
 *
 * Will come in handy when we need to access the
 * class to retrieve data throughout the plugin.
 *
 * @return  WPCampus_Social_Media
 */
function wpcampus_social_media() {
	return WPCampus_Social_Media::instance();
}

// Get the instance going.
wpcampus_social_media();
