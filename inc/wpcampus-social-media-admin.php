<?php
/**
 * The main class that holds all of
 * the admin functionality for the plugin.
 *
 * Class    WPCampus_Social_Media_Admin
 */
class WPCampus_Social_Media_Admin {

	/**
	 * Holds the class instance.
	 *
	 * @access  private
	 * @var     WPCampus_Social_Media_Admin
	 */
	private static $instance;

	/**
	 * Returns the instance of this class.
	 *
	 * @access  public
	 * @return  WPCampus_Social_Media_Admin
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
	protected function __construct() {}

	/**
	 * Method to keep our instance from
	 * being cloned or unserialized.
	 *
	 * @access	private
	 * @return	void
	 */
	private function __clone() {}
	private function __wakeup() {}
}

/**
 * Returns the instance of our main WPCampus_Social_Media_Admin class.
 *
 * Will come in handy when we need to access the
 * class to retrieve data throughout the plugin.
 *
 * @return  WPCampus_Social_Media_Admin
 */
function wpcampus_social_media_admin() {
	return WPCampus_Social_Media_Admin::instance();
}

// Get the instance going.
wpcampus_social_media_admin();
