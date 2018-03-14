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
		$plugin = new self();

		// Add needed styles and scripts.
		add_action( 'admin_enqueue_scripts', array( $plugin, 'enqueue_styles_scripts' ) );

		// Add meta boxes.
		add_action( 'add_meta_boxes', array( $plugin, 'add_meta_boxes' ) );

		// Save meta box data.
		add_action( 'save_post', array( $plugin, 'save_meta_boxes' ), 10, 3 );

	}

	/**
	 * Enqueue admin styles and scripts.
	 */
	public function enqueue_styles_scripts( $hook ) {
		global $post_type;

		// We only need to load our CSS on edit screens.
		if ( ! in_array( $hook, array( 'post.php', 'post-new.php' ) ) ) {
			return;
		}

		// And only for our post types.
		if ( ! in_array( $post_type, wpcampus_social_media()->get_share_post_types() ) ) {
			return;
		}

		$assets_url = wpcampus_social_media()->get_plugin_url() . 'assets/build/';

		wp_enqueue_style( 'wpcampus-social-edit', $assets_url . 'css/wpcampus-social-edit.min.css', array(), null );

	}

	/**
	 * Add our various admin meta boxes.
	 *
	 * @return  void
	 */
	public function add_meta_boxes() {

		$share_post_types = wpcampus_social_media()->get_share_post_types();

		// Add meta box for social media posts.
		add_meta_box( 'wpcampus-social-mb',
			sprintf( __( '%s: Social Media', 'wpcampus-social' ), 'WPCampus' ),
			array( $this, 'print_meta_boxes' ),
			$share_post_types,
			'normal',
			'high'
		);
	}

	/**
	 * Print our meta boxes.
	 *
	 * @param   array - $post - information about the current post, which is empty because there is no current post on a tools page
	 * @param   array - $metabox - information about the metabox
	 * @return  void
	 */
	public function print_meta_boxes( $post, $metabox ) {
		switch( $metabox['id'] ) {
			case 'wpcampus-social-mb':
				$this->print_social_media_mb( $post );
				break;
		}
	}

	/**
	 * Print the social media meta box.
	 *
	 * Users have to have the "wpc_manage_social_media" capability
	 * to edit the social media posts but not to view.
	 *
	 * The background colors are 8% of the main color.
	 *
	 * @args    $post - the post object.
	 * @return  void
	 */
	public function print_social_media_mb( $post ) {

		$user_can_edit = current_user_can( wpcampus_social_media()->get_user_cap_string() );

		if ( $user_can_edit ) :
			?>
			<div class="wpcampus-social-pre"><p><em><?php _e( 'The following allows you to compose and preview the social media message that will be shared for this content. Another plugin will automatically schedule the post.', 'wpcampus-social' ); ?></em></p></div>
			<?php
		else :
			?>
			<div class="wpcampus-social-pre"><p><em><?php _e( 'The following allows you to preview the social media posts that will be shared for this content. You must have specific user permissions to edit the messages. Another plugin will automatically schedule the post.', 'wpcampus-social' ); ?></em></p></div>
			<?php
		endif;

		?>
		<div class="wpcampus-social-preview-wrapper twitter">
			<h3>Twitter</h3>
			<?php

			if ( $user_can_edit ) :
				?>
				<p><?php printf( __( 'Use this field to write a custom tweet for this post. %1$sOur social media service will automatically add the link to the post AND will add the "%2$s" hashtag if you don\'t add it yourself.%3$s The max is set at 280 characters.', 'wpcampus-social' ), '<strong>', '#WPCampus', '</strong>' ); ?></p>
				<?php
			endif;

			// Only those with the capabilities can edit social information.
			if ( $user_can_edit ) :

				$tweet_message = get_post_meta( $post->ID, 'wpc_tweet_message', true );

				?>
				<textarea id="" name="wpc_tweet_message" placeholder="" rows="4" maxlength="280"><?php echo esc_textarea( strip_tags( $tweet_message ) ); ?></textarea>
				<?php
			endif;

			?>
			<p class="wpcampus-social-preview">
				<?php

				$tweet_info = wpcampus_social_media()->get_message_for_post( $post, 'twitter' );

				if ( empty( $tweet_info['message'] ) ) :
					?>
					<em><?php _e( 'No tweet has been generated for this post.', 'wpcampus-social' ); ?></em>
					<?php
				else :
					echo $tweet_info['message'];
				endif;

				?>
			</p>
		</div>
		<div class="wpcampus-social-preview-wrapper facebook">
			<h3>Facebook</h3>
			<?php

			if ( $user_can_edit ) :
				?>
				<p><?php printf( __( 'Use this field to write a custom %s message for this post.', 'wpcampus-social' ), 'Facebook' ); ?></p>
				<?php
			endif;

			// Only those with the capabilities can edit social information.
			if ( $user_can_edit ) :

				$fb_message = get_post_meta( $post->ID, 'wpc_fb_message', true );

				?>
				<textarea id="" name="wpc_fb_message" placeholder="" rows="4" maxlength="280"><?php echo esc_textarea( strip_tags( $fb_message ) ); ?></textarea>
				<?php
			endif;

			?>
			<p class="wpcampus-social-preview">
				<?php

				$fb_info = wpcampus_social_media()->get_message_for_post( $post, 'facebook' );

				if ( empty( $fb_info['message'] ) ) :
					?>
					<em><?php printf( __( 'No %s message has been generated for this post.', 'wpcampus-social' ), 'Facebook' ); ?></em>
					<?php
				else :
					echo $fb_info['message'];
				endif;

				?>
			</p>
		</div>
		<?php

		// Add a nonce field so we can check for it when saving the data.
		wp_nonce_field( 'wpc_social_save_messages', 'wpc_social_save_messages_nonce' );

	}

	/**
	 * When the post is saved, saves our custom meta box data.
	 *
	 * @param   int - $post_id - the ID of the post being saved
	 * @param   WP_Post - $post - the post object
	 * @param   bool - $update - whether this is an existing post being updated or not
	 * @return  void
	 */
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
		if ( ! current_user_can( wpcampus_social_media()->get_user_cap_string() ) ) {
			return;
		}

		// Check if our nonce is set because the 'save_post' action can be triggered at other times.
		if ( isset( $_POST['wpc_social_save_messages_nonce'] ) ) {

			// Verify the nonce.
			if ( wp_verify_nonce( $_POST['wpc_social_save_messages_nonce'], 'wpc_social_save_messages' ) ) {

				// Update the Twitter data.
				if ( isset( $_POST['wpc_tweet_message'] ) ) {

					// Sanitize the value.
					$message = sanitize_text_field( $_POST['wpc_tweet_message'] );

					// Trim to max length.
					$message = substr( $message, 0, 280 );

					// Update/save value.
					update_post_meta( $post_id, 'wpc_tweet_message', $message );

				}

				// Update the Facebook data.
				if ( isset( $_POST['wpc_fb_message'] ) ) {

					// Sanitize the value.
					$message = sanitize_text_field( $_POST['wpc_fb_message'] );

					// Update/save value.
					update_post_meta( $post_id, 'wpc_fb_message', $message );

				}
			}
		}
	}
}
WPCampus_Social_Media_Admin::register();
