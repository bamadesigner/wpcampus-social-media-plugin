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
		// @TODO add back when we want preview functionality.
		//add_action( 'admin_enqueue_scripts', array( $plugin, 'enqueue_styles_scripts' ) );

		// Add and populate custom columns.
		add_filter( 'manage_posts_columns', array( $plugin, 'add_columns' ), 10, 2 );

		add_action( 'manage_pages_custom_column', array( $plugin, 'populate_columns' ), 10, 2 );
		add_action( 'manage_posts_custom_column', array( $plugin, 'populate_columns' ), 10, 2 );

		// Add meta boxes.
		// @TODO add back when we want preview functionality.
		//add_action( 'add_meta_boxes', array( $plugin, 'add_meta_boxes' ) );

		// @TODO maybe remove since we replaced with ACF.
		//add_action( 'save_post', array( $plugin, 'save_meta_boxes' ), 10, 3 );

		// Add AJAX to update social previews.
		// @TODO add back when we want preview functionality.
		//add_action( 'wp_ajax_wpcampus_social_update_preview', array( $plugin, 'ajax_get_message_for_post' ) );

	}

	/**
	 * Enqueue admin styles and scripts.

	public function enqueue_styles_scripts( $hook ) {
		global $post_type;

		// We only need to load our CSS on edit screens.
		if ( ! in_array( $hook, array( 'post.php', 'post-new.php' ) ) ) {
			return;
		}

		// And only for our post types.
		if ( ! in_array( $post_type, $this->helper->get_share_post_types() ) ) {
			return;
		}

		$assets_url = $this->helper->get_plugin_url() . 'assets/';

		wp_enqueue_style( 'wpcampus-social-edit', $assets_url . 'css/wpcampus-social-edit.min.css', array(), null );
		wp_enqueue_script( 'wpcampus-social-edit', $assets_url . 'js/wpcampus-social-edit.min.js', array( 'jquery' ), null, true );

	}*/

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
		$new_columns = array();

		$columns_to_add = array(
			'wpc_social' => __( 'Social', 'wpcampus-social' ),
		);

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

				$is_deactivated = $this->helper->is_social_deactivated( $post_id );

				if ( $is_deactivated ) {
					?>
					<span style="display:block;"><em><?php _e( 'This post is deactivated', 'wpcampus-social' ); ?></em></span>
					<?php
					break;
				}

				$twitter_excluded = $this->helper->is_excluded_post( $post_id, 'twitter' );
				$facebook_excluded = $this->helper->is_excluded_post( $post_id, 'facebook' );

				if ( $twitter_excluded && $facebook_excluded ) :
					?>
					<span style="display:block;"><em><?php _e( 'This post is disabled for automatic sharing.', 'wpcampus-social' ); ?></em></span>
					<?php
					break;

				endif;

				// See if we have a Twitter and Facebook message.
				$twitter_message  = $this->helper->get_social_media_message( $post_id, 'twitter' );
				$facebook_message = $this->helper->get_social_media_message( $post_id, 'facebook' );

				$images_url = $this->helper->get_plugin_url() . 'assets/images/';

				if ( ! empty( $twitter_message ) ) :
					?>
					<img style="width:auto;height:25px;margin:5px 5px 5px 0;" src="<?php echo $images_url; ?>twitter-logo.svg" alt="<?php printf( esc_attr__( 'This post has a %s message.', 'wpcampus-social' ), 'Twitter' ); ?>" title="<?php echo esc_attr( $twitter_message ); ?>">
					<?php
				else :

					$twitter_label = 'Twitter';

					if ( $twitter_excluded ) :
						?>
						<span style="display:block;"><em><?php printf( __( 'This post is disabled for automatic sharing to %s.', 'wpcampus-social' ), $twitter_label ); ?></em></span>
						<?php
					else :
						?>
						<span style="display:block;"><em><?php printf( __( 'Needs %s message', 'wpcampus-social' ), $twitter_label ); ?></em></span>
						<?php
					endif;
				endif;

				if ( ! empty( $facebook_message ) ) :
					?>
					<img style="width:auto;height:25px;margin:5px 5px 5px 0;" src="<?php echo $images_url; ?>facebook-logo.svg" alt="<?php printf( esc_attr__( 'This post has a %s message.', 'wpcampus-social' ), 'Facebook' ); ?>" title="<?php echo esc_attr( $facebook_message ); ?>">
					<?php
				else :

					$facebook_label = 'Facebook';

					if ( $facebook_excluded ) :
						?>
						<span style="display:block;"><em><?php printf( __( 'This post is disabled for automatic sharing to %s.', 'wpcampus-social' ), $facebook_label ); ?></em></span>
						<?php
					else :
						?>
						<span style="display:block;"><em><?php printf( __( 'Needs %s message', 'wpcampus-social' ), $facebook_label ); ?></em></span>
						<?php
					endif;
				endif;

				break;
		}
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
			array( $this, 'print_meta_boxes' ),
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
		$buttons = array();

		if ( 'twitter' == $platform ) {

			$intent_url = $this->helper->get_tweet_intent_url( array(
				'text' => $message,
			));

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

		$twitter_meta_key  = $this->helper->get_meta_key_social_twitter();
		$facebook_meta_key = $this->helper->get_meta_key_social_facebook();

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
