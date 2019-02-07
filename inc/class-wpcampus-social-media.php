<?php

/**
 * PHP class that holds the main/administrative
 * functionality for the plugin.
 *
 * @category    Class
 * @package     WPCampus: Social Media
 */
final class WPCampus_Social_Media {

	/**
	 * Holds the absolute URL to
	 * the main plugin directory.
	 * Used for assets.
	 *
	 * @var string
	 */
	private $plugin_url;

	/**
	 * Holds the directory path
	 * to the main plugin directory.
	 * Used to require PHP files.
	 *
	 * @var string
	 */
	private $plugin_dir;

	/**
	 * Holds the relative "path"
	 * to the main plugin file.
	 *
	 * @var string
	 */
	private $plugin_basename;

	/**
	 *
	 */
	private $site_timezone;

	/**
	 * The names of our social media formats.
	 *
	 * @var array
	 */
	private $social_media_formats = array(
		'twitter',
	);

	/**
	 * The names of our feeds.
	 *
	 * @var array
	 */
	private $social_feeds = array(
		'feed/social',
		'feed/social/twitter',
	);

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
	 *
	 * @return void
	 */
	protected function __construct() {}

	/**
	 * Returns the absolute URL to
	 * the main plugin directory.
	 *
	 * @return string
	 */
	public function get_plugin_url() {
		if ( isset( $this->plugin_url ) ) {
			return $this->plugin_url;
		}
		$this->plugin_url = plugin_dir_url( dirname( __FILE__ ) );
		return $this->plugin_url;
	}

	/**
	 * Returns the directory path
	 * to the main plugin directory.
	 *
	 * @return string
	 */
	public function get_plugin_dir() {
		if ( isset( $this->plugin_dir ) ) {
			return $this->plugin_dir;
		}
		$this->plugin_dir = plugin_dir_path( dirname( __FILE__ ) );
		return $this->plugin_dir;
	}

	/**
	 * Returns the relative "path"
	 * to the main plugin file.
	 *
	 * @return string
	 */
	public function get_plugin_basename() {
		if ( isset( $this->plugin_basename ) ) {
			return $this->plugin_basename;
		}
		$this->plugin_basename = 'wpcampus-social-media-plugin/wpcampus-social-media.php';
		return $this->plugin_basename;
	}

	/**
	 * @return DateTimeZone

	public function get_site_timezone() {
		if ( isset( $this->site_timezone ) ) {
			return $this->site_timezone;
		}

		$timezone = get_option( 'timezone_string' );
		if ( empty( $timezone ) ) {
			$timezone = 'UTC';
		}

		return $this->site_timezone = new DateTimeZone( $timezone );
	}*/

	/**
	 * Return the format for a specific feed.
	 *
	 * @param $query - WP_Query object
	 * @return string - the format.

	public function get_query_feed_format( $query ) {
		switch ( $query->get( 'feed' ) ) {

			case 'feed/social':
			case 'feed/social/twitter':
				return 'twitter';
				break;

		}

		return '';
	}*/

	/**
	 * Return an array of social media formats.
	 *
	 * @return array of formats

	public function get_social_media_formats() {
		return $this->social_media_formats;
	}*/

	/**
	 * Return an array of social media feeds.
	 *
	 * @return array of feeds

	public function get_social_feeds() {
		return $this->social_feeds;
	}*/

	/**
	 * Get a social media message
	 * depending on format.
	 *
	 * @param $post_id - int - the post ID.
	 * @param $format - string - the format name.
	 * @return string - the message.

	public function get_social_media_message( $post_id, $format ) {

		if ( ! in_array( $format, $this->get_social_media_formats() ) ) {
			return '';
		}

		$message = get_post_meta( $post_id, "{$format}_message", true );

		return trim( apply_filters( 'wpcampus_social_message', $message, $post_id, $format ) );
	}*/

	/**
	 * Return the user capability
	 * string to manage social media.

	public function get_user_cap_manage_string() {
		return 'wpc_manage_social_media';
	}*/

	/**
	 * Return the user capability
	 * string to share social media.

	public function get_user_cap_share_string() {
		return 'wpc_share_social_media';
	}*/

	/**
	 * Get the share post types assigned
	 * for the Revive Social plugin.
	 *
	 * @TODO:
	 *   - look into usage of get_option()
	 *     and see if I can cache.

	public function get_share_post_types() {
		return get_option( 'top_opt_post_type' ) ?: array();
	}*/

	/**
	 * Creating tweet intent URL.
	 *
	 * @param   $args - array - the arguments for the URL.
	 * @return  string - the URL.

	public function get_tweet_intent_url( $args ) {

		// Build arguments.
		$final_args = array();

		if ( ! empty( $args['url'] ) ) {
			$final_args['url'] = urlencode( trim( strip_tags( $args['url'] ) ) );
		}

		if ( ! empty( $args['via'] ) ) {
			$final_args['via'] = urlencode( trim( strip_tags( $args['via'] ) ) );
		}

		if ( ! empty( $args['text'] ) ) {
			$final_args['text'] = urlencode( trim( strip_tags( $args['text'] ) ) );
		}

		if ( ! empty( $args['hashtags'] ) ) {
			if ( is_string( $args['hashtags'] ) ) {
				$final_args['hashtags'] = trim( strip_tags( $args['hashtags'] ) );
			} elseif ( is_array( $args['hashtags'] ) ) {
				$args['hashtags'] = array_map( 'strip_tags', $args['hashtags'] );
				$args['hashtags'] = array_map( 'trim', $args['hashtags'] );
				$args['hashtags'] = urlencode( implode( ',', $args['hashtags'] ) );
			}
		}

		return add_query_arg( $final_args, 'https://twitter.com/intent/tweet' );
	}*/

	/**
	 * Get the max message length depending on network.
	 *
	 * If no network is passed, will get max lengths
	 * for all networks.
	 *
	 * @args    $network - e.g. 'facebook' or 'twitter'.
	 * @return  int|array - if network, returns length for network. Array of all otherwise.

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
				if ( ! empty( $formats[ $network . '_top_opt_tweet_length' ] ) ) {
					return (int) $formats[ $network . '_top_opt_tweet_length' ];
				}

				// If no set length in options, return default length.
				return (int) $allowed_networks[ $network ];
			}

			return 0;
		}

		// If no specific network, get all of them.
		$max_lengths = array();

		foreach ( $allowed_networks as $network_key => $network_length ) {

			// Set length stored in options.
			if ( ! empty( $formats[ $network_key . '_top_opt_tweet_length' ] ) ) {
				$max_lengths[ $network_key ] = (int) $formats[ $network_key . '_top_opt_tweet_length' ];
			}

			// If no set length in options, set default length.
			$max_lengths[ $network_key ] = (int) $network_length;
		}

		return $max_lengths;
	}*/

	/**
	 * Return the custom message saved in our post meta.
	 *
	 * @args    $post_id - int - the post ID.
	 * @args    $network - string - e.g. 'facebook' or 'twitter'.
	 * @return  string - the custom message.

	public function get_custom_message_for_post( $post_id, $network ) {

		switch ( $network ) {
			case 'twitter':
				$message = get_post_meta( $post_id, 'wpc_twitter_message', true );
				break;
			case 'facebook':
				$message = get_post_meta( $post_id, 'wpc_facebook_message', true );
				break;
			default:
				$message = '';
				break;
		}

		if ( empty( $message ) ) {
			return '';
		}

		// Sanitize the message.
		return trim( strip_tags( $message ) );
	}*/

	/**
	 * Returns the message that Revive Social
	 * plugin generates for a post.
	 *
	 * @args    $post - the post object.
	 * @args    $network - the social media network, e.g. "facebook" or "twitter".
	 * @return  array - info for the post, including link and message.

	public function get_message_for_post( $post, $network ) {
		global $CWP_TOP_Core;
		if ( class_exists( 'CWP_TOP_Core' ) && method_exists( $CWP_TOP_Core, 'generateTweetFromPost' ) ) {
			return $CWP_TOP_Core->generateTweetFromPost( $post, $network );
		}
		return '';
	}*/

	/**
	 * Returns the IDs of excluded posts.
	 *
	 * @args    $network - the social media network, e.g. "facebook" or "twitter".
	 * @return  array - array of post IDs.

	public function get_excluded_posts( $network ) {
		global $CWP_TOP_Core;
		if ( class_exists( 'CWP_TOP_Core' ) && method_exists( $CWP_TOP_Core, 'getExcludedPosts' ) ) {
			$excluded_posts = $CWP_TOP_Core->getExcludedPosts( $network );
			if ( ! empty( $excluded_posts ) ) {
				if ( ! is_array( $excluded_posts ) ) {
					$excluded_posts = explode( ',', $excluded_posts );
				}
				return array_map( 'intval', $excluded_posts );
			}
		}
		return array();
	}*/

	/**
	 * Will return true if post is an
	 * excluded post on a specific network.
	 *
	 * @args    $post_id - int - the post ID.
	 * @args    $network  - string - e.g. 'facebook' or 'twitter'.

	public function is_excluded_post( $post_id, $network ) {
		return in_array( $post_id, $this->get_excluded_posts( $network ) );
	}*/

	/**
	 * Shares a post on a social network immediately.
	 *
	 * @args    $post - WP_Post - the post object.
	 * $args    $network - string - .e.g 'facebook' or 'twitter'.

	public function share_post( $post, $network ) {
		global $CWP_TOP_Core;

		if ( class_exists( 'CWP_TOP_Core' )
		     || ! method_exists( $CWP_TOP_Core, 'generateTweetFromPost' )
		     || ! method_exists( $CWP_TOP_Core, 'tweetPost' ) ) {
			return false;
		}

		$message = $CWP_TOP_Core->generateTweetFromPost( $post, $network );

		if ( empty( $message ) ) {
			return false;
		}

		echo $message;

		//$CWP_TOP_Core->tweetPost( $message, $network, $post );

	}*/
}
