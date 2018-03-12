<?php
/**
 * The class that sets up
 * global plugin functionality.
 *
 * This class is initiated on every page
 * load and does not have to be instantiated.
 *
 * @class       WPCampus_Social_Media_Global
 * @package     WPCampus Social Media
 */
final class WPCampus_Social_Media_Global {

	/**
	 * We don't need to instantiate this class.
	 */
	protected function __construct() {}

	/**
	 * Registers all of our hooks and what not.
	 */
	public static function register() {
		$plugin = new self();

		// Load our text domain.
		add_action( 'plugins_loaded', array( $plugin, 'textdomain' ) );

	}

	/**
	 * Internationalization FTW.
	 * Loads our text domain.
	 */
	public function textdomain() {
		load_plugin_textdomain( 'wpcampus-social', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );
	}
}
WPCampus_Social_Media_Global::register();
