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

	private $helper;

	/**
	 * We don't need to instantiate this class.
	 */
	protected function __construct() {}

	/**
	 * Registers all of our hooks.
	 */
	public static function register() {
		$plugin = new self();

		$plugin->helper = wpcampus_social_media();

		// Runs on activation and deactivation.
		register_activation_hook( __FILE__, array( $plugin, 'activate' ) );
		register_deactivation_hook( __FILE__, array( $plugin, 'deactivate' ) );

		// Load our text domain.
		add_action( 'plugins_loaded', array( $plugin, 'textdomain' ) );

		// Register our social media feeds.
		add_filter( 'query_vars', array( $plugin, 'add_query_vars' ) );
		add_action( 'init', array( $plugin, 'add_feeds' ) );
		add_filter( 'template_include', array( $plugin, 'use_social_feed_template' ), 100 );

		add_filter( 'posts_pre_query', array( $plugin, 'modify_social_posts_pre_query' ), 100, 2 );
		add_filter( 'posts_request', array( $plugin, 'modify_social_posts_request' ), 100, 2 );
		add_filter( 'the_posts', array( $plugin, 'modify_social_posts' ), 100, 2 );

		// Adding for the conference schedules plugin.
		add_filter( 'wpcampus_social_end_date_time', array( $plugin, 'filter_schedule_social_end_date_time' ), 100, 3 );

		// Filter the tweets.
		add_filter( 'wpcampus_social_message', array( $plugin, 'filter_social_message' ), 10, 3 );

	}

	/**
	 * This method runs when the plugin is activated.
	 *
	 * @access  public
	 * @return  void
	 */
	public function activate() {
		flush_rewrite_rules( true );
	}

	/**
	 * This method runs when the plugin is deactivated.
	 *
	 * @access  public
	 * @return  void
	 */
	public function deactivate() {
		flush_rewrite_rules( true );
	}

	/**
	 * Internationalization FTW.
	 * Loads our text domain.
	 */
	public function textdomain() {
		load_plugin_textdomain( 'wpcampus-social', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );
	}

	/**
	 * Add custom query vars.
	 *
	 * @access  public
	 */
	public function add_query_vars( $vars ) {
		$vars[] = $this->helper->get_feed_query_var();
		return $vars;
	}

	/**
	 * Add our RSS feeds.
	 *
	 * @access public
	 * @return void
	 */
	public function add_feeds() {

		$query_var = $this->helper->get_feed_query_var();
		$feed_default = $this->helper->get_feed_default();

		add_rewrite_rule( '^feed/social/([a-z]+)/?', 'index.php?' . $query_var . '=$matches[1]', 'top' );
		add_rewrite_rule( '^feed/social/?', 'index.php?' . $query_var . '=' . $feed_default, 'top' );
	}

	/**
	 * Print our social media feeds.
	 *
	 * @access public
	 * @return void
	 */
	public function use_social_feed_template( $template ) {
		global $wp_query;
		if ( $this->helper->is_social_feed( $wp_query ) ) {
			return $this->helper->get_plugin_dir() . 'inc/feed-social-json.php';
		}
		return $template;
	}

	/**
	 * Make sure the main query doesnt return posts. We query them later.
	 */
	public function modify_social_posts( $posts, $query ) {

		if ( ! $this->helper->is_social_feed( $query ) ) {
			return $posts;
		}

		return [];
	}

	/**
	 * Return 0 to bypass WordPress's default post queries.
	 *
	 * @param array|null $posts Return an array of post data to short-circuit WP's query,
	 *                          or null to allow WP to run its normal queries.
	 * @param WP_Query   $query  The WP_Query instance (passed by reference).
	 *
	 * @return int
	 */
	public function modify_social_posts_pre_query( $posts, $query ) {

		if ( ! $this->helper->is_social_feed( $query ) ) {
			return $posts;
		}

		return 0;
	}

	/**
	 * Make the main query blank so we can run our own query in the feed file.
	 *
	 * @param string   $request The complete SQL query.
	 * @param WP_Query $query    The WP_Query instance (passed by reference).
	 *
	 * @return string
	 */
	public function modify_social_posts_request( $request, $query ) : string {

		if ( ! $this->helper->is_social_feed( $query ) ) {
			return $request;
		}

		return "SELECT 0";
	}

	/**
	 * Needs to be in site timezone.
	 */
	public function filter_schedule_social_end_date_time( $end_date_time, $post_id, $platform ) {
		if ( 'schedule' != get_post_type( $post_id ) ) {
			return $end_date_time;
		}

		$event_date = get_post_meta( $post_id, 'conf_sch_event_date', true );

		if ( empty( $event_date ) ) {
			return $end_date_time;
		}

		$time = get_post_meta( $post_id, 'conf_sch_event_start_time', true );

		if ( empty( $time ) ) {
			$time = get_post_meta( $post_id, 'conf_sch_event_end_time', true );
		}

		if ( empty( $time ) ) {
			return $end_date_time;
		}

		$end_date_time = new DateTime( $event_date . ' ' . $time, $this->helper->get_site_timezone() );

		return $end_date_time->format( $this->helper->get_format_date_time() );
	}

	/**
	 * Filter tweets to auto add:
	 *  - The #WPCampus hashtag
	 *
	 * @TODO auto add permalink?
	 *
	 * Below, we automatically add the "#WPCampus" hashtag if not
	 * included in the custom tweet.
	 */
	public function filter_social_message( $message, $post_id, $platform ) {

		// @TODO dont include if blank messages?
		if ( empty( $message ) ) {
			return $message;
		}

		$post = get_post( $post_id );

		$current_tweet_length = strlen( $message );
		$tweet_max_length     = $this->helper->get_max_message_length( $platform );

		$ellipses = '...';

		// A URL of any length will be altered to 23 characters.
		$url_length = 23;

		$url_placeholder = '{url}';
		$url_placeholder_length = strlen( $url_placeholder );
		$has_url_placeholder = ( false !== strpos( $message, $url_placeholder ) );

		// If the tweet is blank other than a {url} placeholder.
		$is_blank = ! $current_tweet_length || ( $has_url_placeholder && $current_tweet_length == $url_placeholder_length );

		// URL length - what we have for bit.ly shortcode - space
		$space_for_url = $url_length - $url_placeholder_length - 1;

		// Adjust length for what we need for URL.
		if ( $has_url_placeholder ) {
			$current_tweet_length -= $space_for_url;
		}

		// If the tweet is blank, compose something simple.
		if ( $is_blank ) {

			switch ( $post->post_type ) {

				case 'podcast':
					// translators: A prefix for the WPCampus podcast.
					$message_prefix = sprintf( __( 'The %s Podcast:', 'wpcampus-social' ), 'WPCampus' ) . ' ' . $post->post_title;
					break;

				// Prefix with the title.
				default:
					$message_prefix = $post->post_title;
					break;
			}

			// Make sure what we want to prefix isn't too long.
			$prefix_length     = strlen( $message_prefix );
			$prefix_max_length = $has_url_placeholder ? ( $tweet_max_length - $url_placeholder_length - 1 ) : $tweet_max_length;

			/*
			 * Trim if needed and add ellipses.
			 *
			 * @TODO:
			 *  - This doesn't account for if it it ends with period.
			 */
			if ( $prefix_length > $prefix_max_length ) {

				// Trim the prefix.
				$message_prefix = substr( $message, 0, $prefix_max_length - strlen( $ellipses ) );

				// Add the ellipses.
				$message_prefix . $ellipses;

			}

			if ( ! empty( $message_prefix ) ) {
				if ( empty( $message ) ) {
					$message = $message_prefix;
				} else {
					$message = $message_prefix . ' ' . $message;
				}
			}
		}

		// Does the tweet have our name?
		$has_wpcampus = strpos( $message, 'WPCampus' );

		// Add #WPCampus if not in the tweet.
		if ( false === $has_wpcampus ) {

			$wpcampus_hashtag_add = ' #WPCampus';

			// If tweet is too long to add the hashtag, trim and add ellipses.
			if ( $current_tweet_length > ( $tweet_max_length - strlen( $wpcampus_hashtag_add ) ) ) {

				/*
				 * Make sure we're making room for our link.
				 * Remove URL placeholder for now.
				 */
				if ( $has_url_placeholder ) {
					$message = str_replace( $url_placeholder, '', $message );
				}

				// Trim the tweet.
				$message = substr( $message, 0, $tweet_max_length - strlen( $wpcampus_hashtag_add ) - strlen( $ellipses ) );

				/*
				 * @TODO:
				 *  - Make sure it doesn't end in a "."?
				 *    The revive plugin seems to already trim
				 *    but doesn't add a "." or a "...".
				 */

				// Add the ellipses.
				$message .= $ellipses;

				// Add back our link.
				if ( $has_url_placeholder ) {
					$message .= " {$url_placeholder}";
				}
			}

			// Add the hashtag.
			$message .= $wpcampus_hashtag_add;

		} elseif ( ! preg_match( '/((\s\#WPCampus)|(\#WPCampus\s?))/i', $message ) ) {

			/*
			 * This means we have "WPCampus" but not "#WPCampus",
			 * but lets only add the hashtag if enough room.
			 */
			if ( $current_tweet_length < ( $tweet_max_length - 1 ) ) {

				// We only want to add the hashtag once so replace first occurrence.
				$first_wpcampus = strpos( $message, 'WPCampus' );
				if ( false !== $first_wpcampus ) {
					$message = substr_replace( $message, '#WPCampus', $first_wpcampus, strlen( 'WPCampus' ) );
				}
			}
		}

		// Add the link.
		if ( $has_url_placeholder ) {

			$permalink = get_permalink( $post_id );

			if ( ! empty( $permalink ) ) {
				$message = str_replace( $url_placeholder, $permalink, $message );
			}
		}

		return $message;
	}
}
WPCampus_Social_Media_Global::register();
