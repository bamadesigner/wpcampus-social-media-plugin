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

	/**
	 * Holds a message for our post meta filter to pick up.
	 *
	 * This allows us to see our message as a preview
	 * without actually editing the post meta table.
	 */
	private $filter_message = '';

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

		// Add needed styles and scripts.
		add_action( 'admin_enqueue_scripts', array( $plugin, 'enqueue_styles_scripts' ) );

		// Add and populate custom columns.
		add_filter( 'manage_posts_columns', array( $plugin, 'add_columns' ), 10, 2 );
		add_action( 'manage_posts_custom_column', array( $plugin, 'populate_columns' ), 10, 2 );

		// Add meta boxes.
		add_action( 'add_meta_boxes', array( $plugin, 'add_meta_boxes' ) );

		// Save meta box data.
		add_action( 'save_post', array( $plugin, 'save_meta_boxes' ), 10, 3 );

		// Filter post meta for social update preview.
		add_filter( 'get_post_metadata', array( $plugin, 'filter_post_meta' ), 10, 4 );

		// Add AJAX to update social previews.
		add_action( 'wp_ajax_wpcampus_social_update_preview', array( $plugin, 'ajax_get_message_for_post' ) );

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
		wp_enqueue_script( 'wpcampus-social-edit', $assets_url . 'js/wpcampus-social-edit.min.js', array( 'jquery' ), null, true );

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
		if ( ! in_array( $post_type, wpcampus_social_media()->get_share_post_types() ) ) {
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
	 * @param   $column - string - The name of the column to display.
	 * @param   $post_id - int - The current post ID.
	 */
	public function populate_columns( $column, $post_id ) {

		switch ( $column ) {

			case 'wpc_social':

				// See if we have a Twitter and Facebook message.
				$twitter_message  = wpcampus_social_media()->get_custom_message_for_post( $post_id, 'twitter' );
				$facebook_message = wpcampus_social_media()->get_custom_message_for_post( $post_id, 'facebook' );

				$images_url = wpcampus_social_media()->get_plugin_url() . 'assets/images/';

				if ( ! empty( $twitter_message ) ) {
					?>
					<img style="width:auto;height:25px;margin:5px 5px 5px 0;" src="<?php echo $images_url; ?>twitter-logo.svg" alt="<?php printf( esc_attr__( 'This post has a %s message.', 'wpcampus-social' ), 'Twitter' ); ?>" title="<?php esc_attr( $twitter_message ); ?>">
					<?php
				} else {
					?>
					<span style="display:block;"><em><?php printf( __( 'Needs %s message', 'wpcampus-social' ), 'Twitter' ); ?></em></span>
					<?php
				}

				if ( ! empty( $facebook_message ) ) {
					?>
					<img style="width:auto;height:25px;margin:5px 5px 5px 0;" src="<?php echo $images_url; ?>facebook-logo.svg" alt="<?php printf( esc_attr__( 'This post has a %s message.', 'wpcampus-social' ), 'Facebook' ); ?>" title="<?php esc_attr( $facebook_message ); ?>">
					<?php
				} else {
					?>
					<span style="display:block;"><em><?php printf( __( 'Needs %s message', 'wpcampus-social' ), 'Facebook' ); ?></em></span>
					<?php
				}

				break;
		}
	}

	/**
	 * Add our various admin meta boxes.
	 *
	 * @return  void
	 */
	public function add_meta_boxes() {

		$share_post_types = wpcampus_social_media()->get_share_post_types();

		if ( empty( $share_post_types ) ) {
			return;
		}

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
		switch ( $metabox['id'] ) {
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

		$max_message_length = $user_can_edit ? wpcampus_social_media()->get_max_message_length() : array();

		if ( $user_can_edit ) :
			?>
			<div class="wpcampus-social-pre"><p><em><?php _e( 'The following allows you to compose and preview the social media message that will be shared for this content. Another plugin will automatically schedule the post.', 'wpcampus-social' ); ?></em></p></div>
			<?php
		else :
			?>
			<div class="wpcampus-social-pre"><p><em><?php _e( 'The following allows you to preview the social media posts that will be shared for this content. You must have specific user permissions to edit the messages. Another plugin will automatically schedule the post.', 'wpcampus-social' ); ?></em></p></div>
			<?php
		endif;

		$a11y_message = sprintf( __( '%1$sFor accessibility:%2$s be mindful of using phrases like "listen to the podcast" that might imply how a user can or can\'t consume the content.', 'wpcampus-social' ), '<strong>', '</strong>' );

		?>
		<div class="wpcampus-social-preview-wrapper twitter">
			<h3>Twitter</h3>
			<?php

			// Only those with the capabilities can edit social information.
			if ( $user_can_edit ) :

				$max_twitter_length = ! empty( $max_message_length['twitter'] ) ? $max_message_length['twitter'] : 0;
				$twitter_message    = wpcampus_social_media()->get_custom_message_for_post( $post->ID, 'twitter' );

				?>
				<p><?php printf( __( 'Use this field to write a custom tweet for this post. %1$sOur social media service will automatically add the link to the post AND will add the "%2$s" hashtag if you don\'t add it yourself.%3$s The max is set at %4$d characters.', 'wpcampus-social' ), '<strong>', '#WPCampus', '</strong>', $max_twitter_length ); ?></p>
				<p class="highlight"><?php echo $a11y_message; ?></p>
				<textarea required class="wpcampus-social-update" data-network="twitter" data-preview="wpcampus-social-preview-twitter" name="wpc_twitter_message" placeholder="" rows="4" maxlength="<?php echo $max_twitter_length; ?>"><?php echo esc_textarea( $twitter_message ); ?></textarea>
				<?php
			endif;

			?>
			<div id="wpcampus-social-preview-twitter">
				<?php $this->print_social_media_edit_preview( $post, 'twitter' ); ?>
			</div>
		</div>
		<div class="wpcampus-social-preview-wrapper facebook">
			<h3>Facebook</h3>
			<?php

			// Only those with the capabilities can edit social information.
			if ( $user_can_edit ) :

				$max_facebook_length = ! empty( $max_message_length['facebook'] ) ? $max_message_length['facebook'] : 0;
				$facebook_message    = wpcampus_social_media()->get_custom_message_for_post( $post->ID, 'facebook' );

				?>
				<p><?php printf( __( 'Use this field to write a custom %1$s message for this post. %2$sOur social media service will automatically add the link to the post AND will add the "%3$s" hashtag if you don\'t add it yourself.%4$s The max is set at %5$d characters.', 'wpcampus-social' ), 'Facebook', '<strong>', '#WPCampus', '</strong>', $max_facebook_length ); ?></p>
				<p class="highlight"><?php echo $a11y_message; ?></p>
				<textarea required class="wpcampus-social-update" data-network="facebook" data-preview="wpcampus-social-preview-facebook" name="wpc_facebook_message" placeholder="" rows="4" maxlength="<?php echo $max_facebook_length; ?>"><?php echo esc_textarea( $facebook_message ); ?></textarea>
				<?php
			endif;

			?>
			<div id="wpcampus-social-preview-facebook">
				<?php $this->print_social_media_edit_preview( $post, 'facebook' ); ?>
			</div>
		</div>
		<?php

		// Add a nonce field so we can check for it when saving the data.
		wp_nonce_field( 'wpc_social_save_messages', 'wpc_social_save_messages_nonce' );

	}

	/**
	 * Prints the HTML markup for social media previews in the admin.
	 *
	 * @args    $post - WP_Post - the post object.
	 * @args    $network - string - e.g. 'facebook' or 'twitter'.
	 * @return  void
	 */
	public function print_social_media_edit_preview( $post, $network ) {

		$message_info = wpcampus_social_media()->get_message_for_post( $post, $network );

		?>
		<p class="wpcampus-social-preview">
			<?php

			if ( empty( $message_info['message'] ) ) :

				if ( 'twitter' == $network ) :
					?>
					<em><?php _e( 'No tweet has been generated for this post.', 'wpcampus-social' ); ?></em>
					<?php
				elseif ( 'facebook' == $network ) :
					?>
					<em><?php printf( __( 'No %s message has been generated for this post.', 'wpcampus-social' ), 'Facebook' ); ?></em>
					<?php
				endif;
			else :
				echo $message_info['message'];
			endif;

			?>
		</p>
		<?php

		if ( 'twitter' == $network ) :

			$intent_url = wpcampus_social_media()->get_tweet_intent_url( array(
				'text' => $message_info['message'],
			));

			if ( ! empty( $intent_url ) ) :
				?>
				<a class="wpcampus-social-button" target="_blank" href="<?php echo $intent_url; ?>"><?php _e( 'Open tweet in Twitter intent', 'wpcampus-social' ); ?></a>
				<?php
			endif;
		endif;
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
				if ( isset( $_POST['wpc_twitter_message'] ) ) {

					// Sanitize the value.
					$message = trim( sanitize_text_field( $_POST['wpc_twitter_message'] ) );

					// Trim to max length.
					$max_message_length = wpcampus_social_media()->get_max_message_length( 'twitter' );
					if ( $max_message_length > 0 ) {
						$message = substr( $message, 0, $max_message_length );
					}

					// Update/save value.
					update_post_meta( $post_id, 'wpc_twitter_message', $message );

				}

				// Update the Facebook data.
				if ( isset( $_POST['wpc_facebook_message'] ) ) {

					// Sanitize the value.
					$message = trim( sanitize_text_field( $_POST['wpc_facebook_message'] ) );

					// Trim to max length.
					$max_message_length = wpcampus_social_media()->get_max_message_length( 'facebook' );
					if ( $max_message_length > 0 ) {
						$message = substr( $message, 0, $max_message_length );
					}

					// Update/save value.
					update_post_meta( $post_id, 'wpc_facebook_message', $message );

				}
			}
		}
	}

	/**
	 * Return/print the message for a post via AJAX.
	 */
	public function ajax_get_message_for_post() {

		$post_id = ! empty( $_GET['post_id'] ) ? (int) $_GET['post_id'] : 0;
		$network = ! empty( $_GET['network'] ) ? $_GET['network'] : '';
		$message = ! empty( $_GET['message'] ) ? strip_tags( $_GET['message'] ) : '';

		// Return/echo the post message.
		if ( $post_id > 0 && ! empty( $network ) && ! empty( $message ) ) {

			/*
			 * Store our new message for the filter to pick up.
			 *
			 * This allows us to see our message as a preview
			 * without actually editing the post meta table.
			 */
			$this->filter_message = trim( stripslashes( $message ) );

			$this->print_social_media_edit_preview( get_post( $post_id ), $network );

		}

		wp_die();
	}

	/**
	 * Filter post meta so we can intercept values
	 * for social update preview without actually
	 * updating the post meta value in the DB.
	 *
	 * @param   $value - string - the meta value we're filtering.
	 * @param   $object_id - int - Object ID.
	 * @param   $meta_key - string - Meta key.
	 * @param   $single - bool - Whether to return only the first value of the specified $meta_key.
	 * @return  string - the filtered value.
	 */
	public function filter_post_meta( $value, $object_id, $meta_key, $single ) {

		// We only want to filter our meta.
		if ( ! in_array( $meta_key, array( 'wpc_facebook_message', 'wpc_twitter_message' ) ) ) {
			return $value;
		}

		// We only want to filter when have a message set.
		if ( ! empty( $this->filter_message ) ) {
			return $this->filter_message;
		}

		return $value;
	}
}
WPCampus_Social_Media_Admin::register();
