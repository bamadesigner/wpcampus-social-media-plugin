<?php
/**
 * The class that powers admin functionality.
 *
 * This class is initiated on every page in the
 * load in the admin and does not have to be instantiated.
 *
 * @class       WPCampus_Social_Media_Admin
 * @package     WPCampus Social Media
 */
final class WPCampus_Social_Media_Admin {

	/**
	 * We don't need to instantiate this class.
	 */
	protected function __construct() {}

	/**
	 * Registers all of our hooks and what not.
	 *
	 * @return void
	 */
	public static function register() {
		//$plugin = new self();
	}
}
WPCampus_Social_Media_Admin::register();
