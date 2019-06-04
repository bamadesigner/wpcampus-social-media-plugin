(function( $ ) {
	'use strict';

	// When the document is ready...
	$(document).ready(function() {

		// Do we have any text areas to update?
		var $update_texts = $( 'textarea.wpcampus-social-update' );
		if ( $update_texts.length > 0 ) {

			// Add change event to update.
			var changeTimeout;
			$update_texts.on( 'change.wpcSocialUpdate keyup.wpcSocialUpdate', function(e){
				var $update_text = $(this);

				// This lets us throttle the calls.
				clearTimeout( changeTimeout );
				changeTimeout = setTimeout(function() {
					$update_text.wpcampus_social_update_preview();
				}, 700 );
			});
		}
	});

	// Invoked by textarea.
	$.fn.wpcampus_social_update_preview = function() {
		var $textarea = $(this),
			platform = $textarea.data( 'platform' );

		if ( ! platform || $.inArray( platform, [ 'facebook', 'twitter', 'slack' ] ) < 0 ) {
			return;
		}

		var previewID = $textarea.data( 'preview' ),
			$preview = $( '#' + previewID ),
			postID = $( '#post_ID' ).val();

		if ( ! postID || ! $preview.length ) {
			return;
		}

		// Get the new message.
		var message = $textarea.val();

		// Sanitize the message.
		message = $('<div></div>').append(message).text();

		// Get a preview of the updated message.
		$.ajax({
			url: ajaxurl,
			type: 'GET',
			dataType: 'html',
			async: true, // TODO: keep to true?
			cache: false, // TODO: set to true?
			data: {
				action: 'wpcampus_social_update_preview',
				post_id: postID,
				platform: platform,
				message: message
			},
			success: function( new_preview_message ) {
				if ( ! new_preview_message || '' == new_preview_message ) {
					return;
				}

				// Update preview text and fade in/out for attention.
				$preview.fadeOut( 1000, function() {

					// Update message.
					$preview.html( new_preview_message );

					// Fade back in.
					$preview.fadeIn( 1000 );

                });
			}
		});
	};
})(jQuery);
