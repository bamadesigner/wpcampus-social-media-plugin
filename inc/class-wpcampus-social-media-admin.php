<?php
/**
 * The class that powers admin functionality.
 *
 * This class is initiated on every page in the
 * admin and does not have to be instantiated.
 *
 * @class       WPCampus_Social_Media_Admin
 * @package     WPCampus Social Media
 */
final class WPCampus_Social_Media_Admin {

	CONST POST_KEY_SOCIAL_MEDIA = 'wpc_social_media';

	/**
	 * Holds a message for our post meta filter to pick up.
	 *
	 * This allows us to see our message as a preview
	 * without actually editing the post meta table.
	 *
	 * @TODO add back when we want preview functionality.
	 */
	//private $filter_message = '';

	private $helper;

	/**
	 * We don't need to instantiate this class.
	 */
	protected function __construct() {}

	/**
	 * Registers all of our hooks.
	 *
	 * @return void
	 */
	public static function register() {
		$plugin = new self();

		$plugin->helper = wpcampus_social_media();

		// Add needed styles and scripts.
		add_action( 'admin_enqueue_scripts', [ $plugin, 'enqueue_styles_scripts' ] );

		// Add and populate custom columns.
		add_filter( 'manage_posts_columns', [ $plugin, 'add_columns' ], 10, 2 );

		add_action( 'manage_pages_custom_column', [ $plugin, 'populate_columns' ], 10, 2 );
		add_action( 'manage_posts_custom_column', [ $plugin, 'populate_columns' ], 10, 2 );

		add_action( 'admin_menu', [ $plugin, 'add_pages' ] );

		// Add meta boxes.
		// @TODO add back when we want preview functionality.
		//add_action( 'add_meta_boxes', [ $plugin, 'add_meta_boxes' ] );

		// @TODO maybe remove since we replaced with ACF.
		//add_action( 'save_post', [ $plugin, 'save_meta_boxes' ], 10, 3 );

		// Add AJAX to update social previews.
		// @TODO add back when we want preview functionality.
		//add_action( 'wp_ajax_wpcampus_social_update_preview', [ $plugin, 'ajax_get_message_for_post' ] );

	}

	/**
	 * Enqueue admin styles and scripts.
	 *
	 * @TODO add back preview functionality?
	 */
	public function enqueue_styles_scripts( $hook ) {
		global $post_type;

		$allowed_hooks = [
			'edit.php',
			'tools_page_wpc-social-media-report',
		];

		$share_post_types = $this->helper->get_share_post_types();

		foreach ( $share_post_types as $post_type ) {
			$allowed_hooks[] = $post_type . '_page_wpc-social-media-report-' . $post_type;
		}

		if ( ! in_array( $hook, $allowed_hooks ) ) {
			return;
		}

		$assets_url = $this->helper->get_plugin_url() . 'assets/';

		$assets_ver = '1.0';

		wp_enqueue_style( 'wpcampus-social-edit', $assets_url . 'css/wpcampus-social-edit.min.css', [], $assets_ver );
		wp_enqueue_script( 'wpcampus-social-edit', $assets_url . 'js/wpcampus-social-edit.min.js', [ 'jquery' ], $assets_ver, true );

	}

	/**
	 * Add custom admin columns for profiles.
	 *
	 * @param   $columns - array - An array of column names.
	 * @param   $post_type - string - The post type slug.
	 * @return  array - the filtered columns.
	 */
	public function add_columns( $columns, $post_type ) {

		// Only add to share post types.
		if ( ! in_array( $post_type, $this->helper->get_share_post_types() ) ) {
			return $columns;
		}

		// Store new columns.
		$new_columns = [];

		$columns_to_add = [
			'wpc_social' => __( 'Social', 'wpcampus-social' ),
		];

		foreach ( $columns as $key => $value ) {

			// Add existing column.
			$new_columns[ $key ] = $value;

			// Add custom columns after title.
			if ( 'title' == $key ) {
				foreach ( $columns_to_add as $column_key => $column_value ) {
					$new_columns[ $column_key ] = $column_value;
				}
			}
		}

		return $new_columns;
	}

	/**
	 * Populate our custom profile columns.
	 *
	 * @TODO add Slack
	 *
	 * @param   $column - string - The name of the column to display.
	 * @param   $post_id - int - The current post ID.
	 */
	public function populate_columns( $column, $post_id ) {

		switch ( $column ) {

			case 'wpc_social':

				/*$is_deactivated = $this->helper->is_social_deactivated( $post_id );

				if ( $is_deactivated ) {
					?>
					<span class="wpc-social-col-message"><em><?php _e( 'This post is deactivated', 'wpcampus-social' ); ?></em></span>
					<?php
					break;
				}*/

				$twitter_excluded = $this->helper->is_excluded_post( $post_id, 'twitter' );
				$facebook_excluded = $this->helper->is_excluded_post( $post_id, 'facebook' );

				if ( $twitter_excluded && $facebook_excluded ) :
					?>
					<span class="wpc-social-col-message"><em><?php _e( 'This post is disabled for automatic sharing.', 'wpcampus-social' ); ?></em></span>
					<?php
					break;

				endif;

				// See if we have a Twitter and Facebook message.
				$twitter_message  = $this->helper->get_social_media_message( $post_id, 'twitter' );
				$facebook_message = $this->helper->get_social_media_message( $post_id, 'facebook' );

				$images_url = $this->helper->get_plugin_url() . 'assets/images/';

				$twitter_label = 'Twitter';
				$facebook_label = 'Facebook';

				if ( ! empty( $twitter_message ) ) :
					?>
					<img class="wpc-social-col-logo" src="<?php echo $images_url; ?>twitter-logo.svg" alt="<?php printf( esc_attr__( 'This post has a %s message.', 'wpcampus-social' ), $twitter_label ); ?>" title="<?php echo esc_attr( $twitter_message ); ?>">
					<?php
				else :

					if ( $twitter_excluded ) :
						?>
						<span class="wpc-social-col-message"><em><?php printf( __( 'This post is disabled for automatic sharing to %s.', 'wpcampus-social' ), $twitter_label ); ?></em></span>
						<?php
					else :

						$image_message = sprintf( esc_attr__( 'This post needs a %s message.', 'wpcampus-social' ), $twitter_label );

						?>
						<img class="wpc-social-col-logo deactivated" src="<?php echo $images_url; ?>twitter-logo.svg" alt="<?php echo $image_message; ?>" title="<?php echo $image_message; ?>">
						<?php
					endif;
				endif;

				if ( ! empty( $facebook_message ) ) :
					?>
					<img class="wpc-social-col-logo" src="<?php echo $images_url; ?>facebook-logo.svg" alt="<?php printf( esc_attr__( 'This post has a %s message.', 'wpcampus-social' ), $facebook_label ); ?>" title="<?php echo esc_attr( $facebook_message ); ?>">
					<?php
				else :

					if ( $facebook_excluded ) :
						?>
						<span class="wpc-social-col-message"><em><?php printf( __( 'This post is disabled for automatic sharing to %s.', 'wpcampus-social' ), $facebook_label ); ?></em></span>
						<?php
					else :

						$image_message = sprintf( esc_attr__( 'This post needs a %s message.', 'wpcampus-social' ), $facebook_label );

						?>
						<img class="wpc-social-col-logo deactivated" src="<?php echo $images_url; ?>facebook-logo.svg" alt="<?php echo $image_message; ?>" title="<?php echo $image_message; ?>">
						<?php
					endif;
				endif;

				break;
		}
	}

	/**
	 * @return void
	 */
	public function add_pages() {

		$this->add_report_pages();

	}

	/**
	 *
	 */
	private function add_report_pages() {

		$share_post_types = $this->helper->get_share_post_types();

		if ( ! empty( $share_post_types ) ) {

			foreach ( $share_post_types as $post_type ) {

				$parent_slug = add_query_arg( 'post_type', $post_type, 'edit.php' );

				// @TODO change capability.
				add_submenu_page(
					$parent_slug,
					__( 'Social Media Report', 'wpcampus-social' ),
					__( 'Social Media', 'wpcampus-social' ),
					'manage_options',
					'wpc-social-media-report-' . $post_type,
					[ $this, 'print_post_report_page' ]
				);
			}
		}

		// @TODO change capability?
		add_management_page(
			__( 'Social Media Report', 'wpcampus-social' ),
			__( 'Social Media', 'wpcampus-social' ),
			'manage_options',
			'wpc-social-media-report',
			[ $this, 'print_tools_report_page' ]
		);
	}

	/**
	 *
	 */
	public function print_post_report_page() {

		$post_type = ! empty( $_GET['post_type'] ) ? strtolower( sanitize_text_field( $_GET['post_type'] ) ) : null;

		?>
		<div class="wrap">
			<h1><?php echo esc_html( get_admin_page_title() ); ?></h1>
			<?php

			if ( empty( $post_type ) || ! post_type_exists( $post_type ) ) {

				?>
				<p><?php _e( 'This post type is not valid.', 'wpcampus-social' ); ?></p>
				<?php

			} else {

				$posts = $this->helper->get_posts( [
					'post_type' => $post_type,
				] );

				if ( empty( $posts ) ) {
					?>
					<p><?php _e( 'There are no posts to display.', 'wpcampus-social' ); ?></p>
					<?php
				} else {

					$this->print_stats_table( $posts );

				}
			}

			?>
		</div>
		<?php
	}

	/**
	 *
	 */
	public function print_tools_report_page() {
		?>
		<div class="wrap">
			<h1><?php echo esc_html( get_admin_page_title() ); ?></h1>
			<?php

			$posts = $this->helper->get_posts();

			if ( empty( $posts ) ) {
				?>
				<p><?php _e( 'There are no posts to display.', 'wpcampus-social' ); ?></p>
				<?php
			} else {

				$this->print_stats_table( $posts, [
					'show_post_type' => true,
				] );

			}

			?>
		</div>
		<?php
	}

	/**
	 * //@TODO setup Slack
	 *
	 * @param $posts - array - the post data we're displaying.
	 */
	private function print_stats_table( array $posts, array $args = [] ) {

		// Define the defaults.
		$defaults = array(
			'show_post_type' => false,
		);

		$args = wp_parse_args( $args, $defaults );

		$show_post_type = ! empty( $args['show_post_type'] ) ? true : false;

		?>
		<table class="wpc-social-stats">
			<thead>
			<th class="wpc-social-stats__col--id">ID</th>
			<th class="wpc-social-stats__col--title">Title</th>
			<?php

			if ( $show_post_type ) :
				?>
				<th class="wpc-social-stats__col--posttype">Post Type</th>
				<?php
			endif;

			?>
			<th class="wpc-social-stats__col--status">Status</th>
			<th class="wpc-social-stats__col--platform">Platform(s)</th>
			<th class="wpc-social-stats__col--twitter">Twitter</th>
			<th class="wpc-social-stats__col--facebook">Facebook</th>
			</thead>
			<tbody>
			<?php

			$twitter_label = 'Twitter';
			$facebook_label = 'Facebook';

			$images_url = $this->helper->get_plugin_url() . 'assets/images/';

			foreach ( $posts as $post ) :

				$edit_link = get_edit_post_link( $post->ID );

				$platforms = $this->helper->filter_social_platforms( maybe_unserialize( $post->platforms ), $post->ID );

				$twitter_weight = $this->helper->filter_social_media_weight( (string) $post->weight_twitter, $post->ID, 'twitter' );
				$facebook_weight = $this->helper->filter_social_media_weight( (string) $post->weight_facebook, $post->ID, 'facebook' );
				//$slack_weight = $this->helper->filter_social_media_weight( (string) $post->weight_slack, $post->ID, 'slack' );

				// See if we have a Twitter and Facebook message.
				$twitter_message = $this->helper->filter_social_media_message( (string) $post->message_twitter, $post->ID, 'twitter' );
				$facebook_message = $this->helper->filter_social_media_message( (string) $post->message_facebook, $post->ID, 'facebook' );
				//$slack_message = $this->helper->filter_social_media_message( (string) $post->message_slack, $post->ID, 'slack' );

				$is_deactivated = $this->helper->filter_social_deactivated( $post->deactivate, $post->ID );

				$start_date_time = $this->helper->filter_social_media_start_date_time( (string) $post->start_date_time, $post->ID );
				$end_date_time = $this->helper->filter_social_media_end_date_time( (string) $post->end_date_time, $post->ID );

				$is_expired = $this->helper->filter_social_expired( $start_date_time, $end_date_time, $post->ID );

				$status = $is_deactivated ? 'Deactivated' : ( $is_expired ? 'Expired' : '' );

				?>
				<tr>
					<td class="wpc-social-stats__col--id"><?php echo $post->ID; ?></td>
					<td class="wpc-social-stats__col--title"><a href="<?php echo $edit_link; ?>" aria-label="Edit this post"><?php echo get_the_title( $post->ID ); ?></a></td>
					<?php

					if ( $show_post_type ) :
						?>
						<td class="wpc-social-stats__col--posttype"><?php echo $post->post_type; ?></td>
						<?php
					endif;

					?>
					<td class="wpc-social-stats__col--status"><?php echo $status; ?></td>
					<td class="wpc-social-stats__col--platform"><?php echo implode( '<br>', $platforms ); ?></td>
					<td class="wpc-social-stats__col--twitter">
						<?php

						if ( ! empty( $twitter_message ) ) :
							?>
							<img class="wpc-social-col-logo" src="<?php echo $images_url; ?>twitter-logo.svg" alt="<?php printf( esc_attr__( 'This post has a %s message.', 'wpcampus-social' ), $twitter_label ); ?>" title="<?php echo esc_attr( $twitter_message ); ?>">
							<?php
						else :

							// @TODO this isnt setup to work.
							/*if ( $twitter_excluded ) :
								?>
								<span class="wpc-social-col-message"><em><?php printf( __( 'This post is disabled for automatic sharing to %s.', 'wpcampus-social' ), $twitter_label ); ?></em></span>
								<?php
							else :*/

							$image_message = sprintf( esc_attr__( 'This post needs a %s message.', 'wpcampus-social' ), $twitter_label );

							?>
							<img class="wpc-social-col-logo deactivated" src="<?php echo $images_url; ?>twitter-logo.svg" alt="<?php echo $image_message; ?>" title="<?php echo $image_message; ?>">
							<?php

							//endif;
						endif;

						echo '<br><br>' . $twitter_weight;

						?>
					</td>
					<td class="wpc-social-stats__col--facebook">
						<?php

						if ( ! empty( $facebook_message ) ) :
							?>
							<img class="wpc-social-col-logo" src="<?php echo $images_url; ?>facebook-logo.svg" alt="<?php printf( esc_attr__( 'This post has a %s message.', 'wpcampus-social' ), $facebook_label ); ?>" title="<?php echo esc_attr( $facebook_message ); ?>">
							<?php
						else :

							/*if ( $facebook_excluded ) :
								?>
								<span class="wpc-social-col-message"><em><?php printf( __( 'This post is disabled for automatic sharing to %s.', 'wpcampus-social' ), $facebook_label ); ?></em></span>
								<?php
							else :*/

							$image_message = sprintf( esc_attr__( 'This post needs a %s message.', 'wpcampus-social' ), $facebook_label );

							?>
							<img class="wpc-social-col-logo deactivated" src="<?php echo $images_url; ?>facebook-logo.svg" alt="<?php echo $image_message; ?>" title="<?php echo $image_message; ?>">
							<?php

							//endif;
						endif;

						echo '<br><br>' . $facebook_weight;

						?>
					</td>
				</tr>
				<?php

			endforeach;

			?>
			</tbody>
		</table>
		<?php
	}

	/**
	 * Add our various admin meta boxes.
	 *
	 * @return  void

	public function add_meta_boxes() {

		$share_post_types = $this->helper->get_share_post_types();

		if ( empty( $share_post_types ) ) {
			return;
		}

		// Add meta box for social media posts.
		add_meta_box( 'wpcampus-social-preview-mb',
			sprintf( __( '%s: Social Media Preview', 'wpcampus-social' ), 'WPCampus' ),
			[ $this, 'print_meta_boxes' ],
			$share_post_types,
			'normal',
			'high'
		);
	}*/

	/**
	 * Print our meta boxes.
	 *
	 * @param   array - $post - information about the current post, which is empty because there is no current post on a tools page
	 * @param   array - $metabox - information about the metabox
	 * @return  void

	public function print_meta_boxes( $post, $metabox ) {
		switch ( $metabox['id'] ) {
			case 'wpcampus-social-preview-mb':
				$this->print_social_media_preview_mb( $post );
				break;
		}
	}*/

	/**
	 * Print the social media meta box.
	 *
	 * Users have to have the "wpc_manage_social_media" capability
	 * to edit the social media posts but not to view.
	 *
	 * The background colors are 8% of the main color.
	 *
	 * @TODO add Slack
	 *
	 * @args    $post - the post object.
	 * @return  void

	public function print_social_media_preview_mb( $post ) {

		$twitter_is_excluded  = $this->helper->is_excluded_post( $post->ID, 'twitter' );
		$facebook_is_excluded = $this->helper->is_excluded_post( $post->ID, 'facebook' );

		?>
		<div class="wpcampus-social-preview-wrapper twitter <?php echo $twitter_is_excluded ? 'excluded' : 'active'; ?>">
			<h3>Twitter</h3>
			<div id="wpcampus-social-preview-twitter" class="wpcampus-social-preview-area">
				<?php $this->print_social_media_edit_preview( $post, 'twitter' ); ?>
			</div>
		</div>
		<div class="wpcampus-social-preview-wrapper facebook <?php echo $facebook_is_excluded ? 'excluded' : 'active'; ?>">
			<h3>Facebook</h3>
			<div id="wpcampus-social-preview-facebook" class="wpcampus-social-preview-area">
				<?php $this->print_social_media_edit_preview( $post, 'facebook' ); ?>
			</div>
		</div>
		<?php
	}*/

	/**
	 * Prints the HTML markup for social media previews in the admin.
	 *
	 * @TODO add Slack
	 *
	 * @args    $post - WP_Post - the post object.
	 * @args    $platform - string - e.g. 'facebook' or 'twitter'.
	 * @return  void

	public function print_social_media_edit_preview( $post, $platform ) {

		$user_can_share = current_user_can( $this->helper->get_user_cap_share_string() );

		$message = $this->helper->get_social_media_message( $post->ID, $platform );

		?>
		<h4><?php _e( 'Preview the share:', 'wpcampus-social' ); ?></h4>
		<p class="wpcampus-social-preview">
			<?php

			if ( empty( $message ) ) :

				if ( 'twitter' == $platform ) :
					?>
					<em><?php _e( 'No tweet has been generated for this post.', 'wpcampus-social' ); ?></em>
					<?php
				elseif ( 'facebook' == $platform ) :
					?>
					<em><?php printf( __( 'No %s message has been generated for this post.', 'wpcampus-social' ), 'Facebook' ); ?></em>
					<?php
				endif;
			else :
				echo $message;
			endif;

			?>
		</p>
		<?php

		// Create buttons.
		$buttons = [];

		if ( 'twitter' == $platform ) {

			$intent_url = $this->helper->get_tweet_intent_url( [
				'text' => $message,
			]);

			if ( ! empty( $intent_url ) ) {
				$buttons[] = '<a class="wpcampus-social-button" target="_blank" href="' . $intent_url . '">' . sprintf( __( 'Open tweet in %s intent', 'wpcampus-social' ), 'Twitter' ) . '</a>';
			}
		}

		if ( $user_can_share ) {
			$buttons[] = '<button class="wpcampus-social-button">' . __( 'Share this post now', 'wpcampus-social' ) . '</button>';
		}

		if ( ! empty( $buttons ) ) :
			?>
			<div class="wpcampus-social-buttons">
				<?php echo implode( '', $buttons ); ?>
			</div>
			<?php
		endif;
	}*/

	/**
	 * When the post is saved, saves our custom meta box data.
	 *
	 * @TODO add Slack
	 *
	 * @param   int - $post_id - the ID of the post being saved
	 * @param   WP_Post - $post - the post object
	 * @param   bool - $update - whether this is an existing post being updated or not
	 * @return  void

	function save_meta_boxes( $post_id, $post, $update ) {

		// Disregard on autosave.
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}

		// Not for auto drafts.
		if ( 'auto-draft' == $post->post_status ) {
			return;
		}

		// Make sure user has the capability.
		if ( ! current_user_can( $this->helper->get_user_cap_manage_string() ) ) {
			return;
		}

		// Check if our nonce is set because the 'save_post' action can be triggered at other times.
		if ( ! isset( $_POST['wpc_social_save_messages_nonce'] ) ) {
			return;
		}

		// Verify the nonce.
		if ( ! wp_verify_nonce( $_POST['wpc_social_save_messages_nonce'], 'wpc_social_save_messages' ) ) {
			return;
		}

		if ( ! isset( $_POST[ self::POST_KEY_SOCIAL_MEDIA ] ) ) {
			return;
		}

		$wpc_social = $_POST[ self::POST_KEY_SOCIAL_MEDIA ];

		$twitter_meta_key  = $this->helper->get_meta_key_social_message_twitter();
		$facebook_meta_key = $this->helper->get_meta_key_social_message_facebook();

		// Update the Twitter data.
		if ( isset( $wpc_social[ $twitter_meta_key ] ) ) {
			$this->helper->update_social_media_message( $post_id, $wpc_social[ $twitter_meta_key ], 'twitter' );
		}

		// Update the Facebook data.
		if ( isset( $wpc_social[ $facebook_meta_key ] ) ) {
			$this->helper->update_social_media_message( $post_id, $wpc_social[ $facebook_meta_key ], 'facebook' );
		}
	}*/

	/**
	 * Return/print the message for a post via AJAX.

	public function ajax_get_message_for_post() {

		$post_id = ! empty( $_GET['post_id'] ) ? (int) $_GET['post_id'] : 0;
		$platform = ! empty( $_GET['platform'] ) ? $_GET['platform'] : '';
		$message = ! empty( $_GET['message'] ) ? strip_tags( $_GET['message'] ) : '';

		// Return/echo the post message.
		if ( $post_id > 0 && ! empty( $platform ) && ! empty( $message ) ) {

			*//*
			 * Store our new message for the filter to pick up.
			 *
			 * This allows us to see our message as a preview
			 * without actually editing the post meta table.
			 *//*
			$this->filter_message = trim( stripslashes( $message ) );

			$this->print_social_media_edit_preview( get_post( $post_id ), $platform );

		}

		wp_die();
	}*/
}
WPCampus_Social_Media_Admin::register();
