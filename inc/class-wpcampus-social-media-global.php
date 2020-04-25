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
	protected function __construct() { }

	/**
	 * Registers all of our hooks.
	 */
	public static function register() {
		$plugin = new self();

		$plugin->helper = wpcampus_social_media();

		// Runs on activation and deactivation.
		register_activation_hook( __FILE__, [ $plugin, 'activate' ] );
		register_deactivation_hook( __FILE__, [ $plugin, 'deactivate' ] );

		// Load our text domain.
		add_action( 'plugins_loaded', [ $plugin, 'textdomain' ] );

		// Register our social media feeds.
		add_filter( 'query_vars', [ $plugin, 'add_query_vars' ] );
		add_action( 'init', [ $plugin, 'add_feeds' ] );
		add_filter( 'template_include', [ $plugin, 'use_social_feed_template' ], 100 );

		add_filter( 'posts_pre_query', [ $plugin, 'modify_social_posts_pre_query' ], 100, 2 );
		add_filter( 'posts_request', [ $plugin, 'modify_social_posts_request' ], 100, 2 );
		add_filter( 'the_posts', [ $plugin, 'modify_social_posts' ], 100, 2 );

		// Adding for the conference schedules plugin.
		// @TODO have to enable for during the event. Add setting?
		//add_filter( 'wpcampus_social_end_date_time', array( $plugin, 'filter_schedule_social_end_date_time' ), 100, 3 );

		// Filter the tweets.
		add_filter( 'wpcampus_social_message', [ $plugin, 'filter_social_message' ], 10, 3 );

		// Manage the REST API.
		add_action( 'rest_api_init', [ $plugin, 'register_rest_routes' ] );

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
	 * @param WP_Query   $query The WP_Query instance (passed by reference).
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
	 * @param WP_Query $query   The WP_Query instance (passed by reference).
	 *
	 * @return string
	 */
	public function modify_social_posts_request( $request, $query ): string {

		if ( ! $this->helper->is_social_feed( $query ) ) {
			return $request;
		}

		return "SELECT 0";
	}

	/**
	 * Needs to be in site timezone.
	 *
	 * @TODO - Be able to compose and store a tweet for each session thats’ pre event (promo), during event (livestream) and post event video
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
		$tweet_max_length = $this->helper->get_max_message_length( $platform );

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
			$prefix_length = strlen( $message_prefix );
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

	/**
	 * Register our custom REST routes.
	 */
	public function register_rest_routes() {

		register_rest_route(
			'wpcampus',
			'/tweets/',
			[
				'methods'  => 'GET',
				'callback' => [ $this, 'rest_response_tweets' ],
			]
		);
	}

	/**
	 * Return latest tweets as REST response.
	 *
	 * @return WP_REST_Response
	 */
	public function rest_response_tweets() {
		return new WP_REST_Response( $this->get_latest_tweets() );
	}

	/**
	 * Get the latest tweets.
	 *
	 * First checks cache. Then requests from Twitter.
	 *
	 * @return array|WP_Error
	 */
	private function get_latest_tweets() {

		$stored_tweets_transient = $this->get_latest_tweets_transient_name();

		$stored_tweets = get_transient( $stored_tweets_transient );

		// Means the cache is valid.
		if ( false !== $stored_tweets ) {
			return $stored_tweets;
		}

		/*
		 * We have to request fresh tweets.
		 *
		 * Start with getting a token.
		 */
		$token = $this->get_stored_twitter_token();

		/*
		 * Keep track of whether or not we used a fresh token
		 * so we can try the request again with a fresh token.
		 */
		$used_fresh_token = false;
		if ( empty( $stored_token ) ) {
			$used_fresh_token = true;
			$token = $this->request_fresh_twitter_token();
		}

		$tweets = $this->request_latest_tweets( $token );

		if ( is_wp_error( $tweets ) ) {

			// Try again with a fresh token.
			if ( ! $used_fresh_token ) {

				$token = $this->request_fresh_twitter_token();

				$tweets = $this->request_latest_tweets( $token );

				if ( is_wp_error( $tweets ) ) {
					$tweets = [];
				}
			} else {
				$tweets = [];
			}
		}

		// If empty, see if we have some stored tweets to use for now.
		if ( empty( $tweets ) ) {

			// Check the transient value and use as backup data.
			$stored_tweets_value = $this->get_latest_tweets_transient_value();
			if ( ! empty( $stored_tweets_value ) ) {
				return $stored_tweets_value;
			}

			return [];
		}

		// Update stored tweets.
		set_transient( $stored_tweets_transient, $tweets, HOUR_IN_SECONDS );

		return $tweets;
	}

	/**
	 * Return the transient name for our latest tweets.
	 *
	 * @return string
	 */
	private function get_latest_tweets_transient_name() {
		return 'wpc_latest_tweets';
	}

	/**
	 * Get the latest tweets transient value.
	 *
	 * @return mixed
	 */
	private function get_latest_tweets_transient_value() {
		return get_option( '_transient_' . $this->get_latest_tweets_transient_name() );
	}

	/**
	 * Get the stored Twitter token.
	 *
	 * @return mixed
	 */
	private function get_stored_twitter_token() {
		return get_option( 'wpc_twitter_token' );
	}

	/**
	 * Update the stored Twitter token.
	 *
	 * @param $token - string
	 *
	 * @return mixed
	 */
	private function store_twitter_token( $token ) {
		return update_option( 'wpc_twitter_token', $token );
	}

	/**
	 * Request latest tweets from Twitter's API.
	 *
	 * @param $token
	 *
	 * @return array|WP_Error
	 */
	private function request_latest_tweets( $token ) {

		if ( empty( $token ) ) {
			return new WP_Error( 'wpcampus_get_tweets', "You're missing a Twitter authentication token." );
		}

		$url = 'https://api.twitter.com/1.1/statuses/user_timeline.json';

		$screen_name = 'wpcampusorg';

		$url_args = [
			'screen_name' => $screen_name,
			'count'       => 10,
			'tweet_mode'  => 'extended',
			'include_rts' => 'false',
		];

		$url = add_query_arg( $url_args, $url );

		$response = wp_safe_remote_get(
			$url,
			[
				'headers' => [
					'Authorization' => 'Bearer ' . $token,
				],
			]
		);

		$response_code = wp_remote_retrieve_response_code( $response );

		if ( 401 === $response_code ) {
			return new WP_Error( 'wpcampus_get_tweets', 'The request was unauthorized.' );
		}

		if ( 200 !== $response_code ) {
			return new WP_Error( 'wpcampus_get_tweets', 'The response was invalid.' );
		}

		$body = wp_remote_retrieve_body( $response );

		if ( empty( $body ) ) {
			return [];
		}

		$headers = wp_remote_retrieve_headers( $response );

		// Convert to JSON.
		if ( ! empty( $headers['content-type'] ) && 0 === strpos( $headers['content-type'], 'application/json' ) ) {
			$body = json_decode( $body );
		}

		if ( empty( $body ) ) {
			return [];
		}

		return $body;
	}

	/**
	 * Request new authorization token from Twitter.
	 *
	 * @return string|WP_Error
	 */
	private function request_fresh_twitter_token() {

		$url = 'https://api.twitter.com/oauth2/token';

		if ( ! defined( 'WPC_TWITTER_API_KEY' ) || empty( WPC_TWITTER_API_KEY ) ) {
			$error = new WP_Error( 'wpcampus_get_twitter_token', 'The Twitter API key is undefined.' );
		}

		if ( ! defined( 'WPC_TWITTER_API_SECRET' ) || empty( WPC_TWITTER_API_SECRET ) ) {
			$error = new WP_Error( 'wpcampus_get_twitter_token', 'The Twitter API secret is undefined.' );
		}

		$response = wp_safe_remote_post(
			$url,
			[
				'body'    => 'grant_type=client_credentials',
				'headers' => [
					'Content-Type'  => 'application/x-www-form-urlencoded',
					'Authorization' => 'Basic ' . base64_encode( WPC_TWITTER_API_KEY . ':' . WPC_TWITTER_API_SECRET ),
				],
			]
		);

		$error = new WP_Error( 'wpcampus_get_twitter_token', 'The response was invalid.' );

		if ( 200 !== wp_remote_retrieve_response_code( $response ) ) {
			return $error;
		}

		$body = wp_remote_retrieve_body( $response );

		if ( empty( $body ) ) {
			return $error;
		}

		$headers = wp_remote_retrieve_headers( $response );

		// Convert to JSON.
		if ( ! empty( $headers['content-type'] ) && 0 === strpos( $headers['content-type'], 'application/json' ) ) {
			$body = json_decode( $body );
		}

		if ( empty( $body ) ) {
			return $error;
		}

		if ( empty( $body->token_type ) || 'bearer' != $body->token_type ) {
			return $error;
		}

		if ( empty( $body->access_token ) ) {
			return $error;
		}

		$token = $body->access_token;

		// Update stored token.
		$this->store_twitter_token( $token );

		return $token;
	}
}

WPCampus_Social_Media_Global::register();
