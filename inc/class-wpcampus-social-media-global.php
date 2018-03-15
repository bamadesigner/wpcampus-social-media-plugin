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

		// Filter the tweets.
		add_filter( 'rop_override_tweet', array( $plugin, 'override_old_post_tweet' ), 10, 3 );

	}

	/**
	 * Internationalization FTW.
	 * Loads our text domain.
	 */
	public function textdomain() {
		load_plugin_textdomain( 'wpcampus-social', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );
	}

	/**
	 * Filters the tweets created by the Revive Social plugin.
	 *
	 * Had to hack the core apply_filters() to add $network as parameter.
	 *
	 * Make sure you update the "top_opt_post_formats" option
	 * saved from the plugin to change the max tweet length from
	 * 140 to 280.
	 *
	 * We have post meta named "wpc_twitter_message" that is
	 * added to posts to give us a space to compose a custom tweet.
	 *
	 * Below, we automatically add the "#WPCampus" hashtag if not
	 * included in the custom tweet.
	 */
	public function override_old_post_tweet( $final_tweet, $post, $network = '' ) {

		/*
		 * If no network is passed, set to Twitter
		 * since this argument was a hack anyway.
		 */
		$network = $network ?: 'twitter';

		$current_tweet_length = strlen( $final_tweet );
		$tweet_max_length     = wpcampus_social_media()->get_max_message_length( $network );

		$ellipses = '...';

		// A URL of any length will be altered to 23 characters.
		$url_length = 23;

		$bitly        = '[bit.ly]';
		$bitly_length = strlen( $bitly );
		$has_bitly    = ( false !== strpos( $final_tweet, $bitly ) );

		// If the tweet is blank other than a bitly link.
		$is_blank = ! $current_tweet_length || ( $has_bitly && $current_tweet_length == $bitly_length );

		// URL length - what we have for bit.ly shortcode - space
		$space_for_bitly = $url_length - $bitly_length - 1;

		// Adjust length for what we need for URL.
		if ( $has_bitly ) {
			$current_tweet_length -= $space_for_bitly;
		}

		// If the tweet is blank, compose something simple.
		if ( $is_blank ) {

			switch ( $post->post_type ) {

				case 'podcast':
					// translators: A prefix for the WPCampus podcast.
					$final_tweet_prefix = sprintf( __( 'The %s Podcast:', 'wpcampus-social' ), 'WPCampus' ) . ' ' . $post->post_title;
					break;

				// Prefix with the title.
				default:
					$final_tweet_prefix = $post->post_title;
					break;
			}

			// Make sure what we want to prefix isn't too long.
			$prefix_length     = strlen( $final_tweet_prefix );
			$prefix_max_length = $has_bitly ? ( $tweet_max_length - $bitly_length - 1 ) : $tweet_max_length;

			/*
			 * Trim if needed and add ellipses.
			 *
			 * @TODO:
			 *  - This doesn't account for if it it ends with period.
			 */
			if ( $prefix_length > $prefix_max_length ) {

				// Trim the prefix.
				$final_tweet_prefix = substr( $final_tweet, 0, $prefix_max_length - strlen( $ellipses ) );

				// Add the ellipses.
				$final_tweet_prefix . $ellipses;

			}

			if ( ! empty( $final_tweet_prefix ) ) {
				if ( empty( $final_tweet ) ) {
					$final_tweet = $final_tweet_prefix;
				} else {
					$final_tweet = $final_tweet_prefix . ' ' . $final_tweet;
				}
			}
		}

		// Does the tweet have our name?
		$has_wpcampus = strpos( $final_tweet, 'WPCampus' );

		// Add #WPCampus if not in the tweet.
		if ( false === $has_wpcampus ) {

			$wpcampus_hashtag_add = ' #WPCampus';

			// If tweet is too long to add the hashtag, trim and add ellipses.
			if ( $current_tweet_length > ( $tweet_max_length - strlen( $wpcampus_hashtag_add ) ) ) {

				// Make sure we're making room for our link.
				if ( $has_bitly ) {

					// Remove bitly shortcode for now.
					$final_tweet = str_replace( $bitly, '', $final_tweet );

				}

				// Trim the tweet.
				$final_tweet = substr( $final_tweet, 0, $tweet_max_length - strlen( $wpcampus_hashtag_add ) - strlen( $ellipses ) );

				/*
				 * @TODO:
				 *  - Make sure it doesn't end in a "."?
				 *    The revive plugin seems to already trim
				 *    but doesn't add a "." or a "...".
				 */

				// Add the ellipses.
				$final_tweet .= $ellipses;

				// Add back our link.
				if ( $has_bitly ) {
					$final_tweet .= " {$bitly}";
				}
			}

			// Add the hashtag.
			$final_tweet .= $wpcampus_hashtag_add;

		} elseif ( ! preg_match( '/((\s\#WPCampus)|(\#WPCampus\s?))/i', $final_tweet ) ) {

			/*
			 * This means we have "WPCampus" but not "#WPCampus",
			 * but lets only add the hashtag if enough room.
			 */
			if ( $current_tweet_length < ( $tweet_max_length - 1 ) ) {

				// We only want to add the hashtag once so replace first occurrence.
				$first_wpcampus = strpos( $final_tweet, 'WPCampus' );
				if ( false !== $first_wpcampus ) {
					$final_tweet = substr_replace( $final_tweet, '#WPCampus', $first_wpcampus, strlen( 'WPCampus' ) );
				}
			}
		}

		// Keeping this code here in case tweet lengths become an issue with images.
		/*// global $CWP_TOP_Core;
		if ( class_exists( 'CWP_TOP_Core' ) && method_exists( $CWP_TOP_Core, 'isPostWithImageEnabled' ) ) {
			if ( $CWP_TOP_Core->isPostWithImageEnabled( 'twitter' ) ) {
				$final_tweet .= ' [IMAGE]';
			}
		}*/

		return $final_tweet;
	}
}
WPCampus_Social_Media_Global::register();
