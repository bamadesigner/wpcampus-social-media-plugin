<?php

if ( ! function_exists( 'acf_add_local_field_group' ) ) {
	return;
}

$helper = wpcampus_social_media();

$location = [];
$post_types = $helper->get_share_post_types();

if ( empty( $post_types ) ) {
	return;
}

foreach ( $post_types as $post_type ) {
	$location[] = [
		[
			'param'    => 'post_type',
			'operator' => '==',
			'value'    => $post_type,
		]
	];
}

$weight_default = $helper->get_feed_weight_default();

acf_add_local_field_group( array(
	'key' => 'group_5cf4242673897',
	'title' => 'WPCampus: Social Media',
	'fields' => array(
		array(
			'key' => 'field_5cf42479025f0',
			'label' => 'Deactivate from social media',
			'name' => 'wpc_social_deactivate',
			'type' => 'true_false',
			'instructions' => '',
			'required' => 0,
			'conditional_logic' => 0,
			'message' => 'Deactivate from sharing this post on social media',
			'default_value' => 0,
			'ui' => 0,
			'ui_on_text' => '',
			'ui_off_text' => '',
		),
		array(
			'key' => 'field_5cf42434025ef',
			'label' => 'Platforms',
			'name' => 'wpc_social_platform',
			'type' => 'checkbox',
			'instructions' => '',
			'required' => 0,
			'conditional_logic' => array(
				array(
					array(
						'field' => 'field_5cf42479025f0',
						'operator' => '!=',
						'value' => '1',
					),
				),
			),
			'choices' => array(
				'twitter' => 'Twitter',
				'facebook' => 'Facebook',
				'slack' => 'Slack',
			),
			'allow_custom' => 0,
			'default_value' => array(
				0 => 'twitter',
			),
			'layout' => 'horizontal',
			'toggle' => 0,
			'return_format' => 'value',
			'save_custom' => 0,
		),
		array(
			'key' => 'field_5cf425267b7a5',
			'label' => 'Start date / time',
			'name' => 'wpc_social_start_date_time',
			'type' => 'date_time_picker',
			'instructions' => 'Leave the start and end date blank to always display the notification.',
			'required' => 0,
			'conditional_logic' => array(
				array(
					array(
						'field' => 'field_5cf42479025f0',
						'operator' => '!=',
						'value' => '1',
					),
				),
			),
			'display_format' => 'Y-m-d H:i:s',
			'return_format' => 'Y-m-d H:i:s',
			'first_day' => 1,
		),
		array(
			'key' => 'field_5cf425427b7a6',
			'label' => 'End date / time',
			'name' => 'wpc_social_end_date_time',
			'type' => 'date_time_picker',
			'instructions' => 'Leave the start and end date blank to always display the notification.',
			'required' => 0,
			'conditional_logic' => array(
				array(
					array(
						'field' => 'field_5cf42479025f0',
						'operator' => '!=',
						'value' => '1',
					),
				),
			),
			'display_format' => 'Y-m-d H:i:s',
			'return_format' => 'Y-m-d H:i:s',
			'first_day' => 1,
		),
		array(
			'key' => 'field_5df4167cba3ce',
			'label' => 'Twitter weight',
			'name' => 'wpc_social_message_twitter_weight',
			'type' => 'number',
			'instructions' => sprintf( 'By default, all posts have the same weight of %d. If you want this post to have more or less weight, increase or decease the number.', $weight_default ),
			'required' => 1,
			'conditional_logic' => array(
				array(
					array(
						'field' => 'field_5cf42434025ef',
						'operator' => '==',
						'value' => 'twitter',
					),
				),
			),
			'default_value' => $weight_default,
		),
		array(
			'key' => 'field_5cf4265bba5ce',
			'label' => 'Twitter message',
			'name' => 'wpc_social_message_twitter',
			'type' => 'textarea',
			'instructions' => 'Use this field to write a custom tweet for this post. Our social media service will automatically add the link to the post AND will add the "#WPCampus" hashtag if you don\'t add it yourself. The max is set at 280 characters.
			
			Be mindful of using phrases like "listen to the podcast" that might imply how a user can or can\'t consume the content. Also, these tweets will be shared over and over again so think about past, present, and future tense.',
			'required' => 1,
			'conditional_logic' => array(
				array(
					array(
						'field' => 'field_5cf42434025ef',
						'operator' => '==',
						'value' => 'twitter',
					),
				),
			),
			'default_value' => '',
			'placeholder' => 'What do you want to say to Twitter?',
			'maxlength' => 280,
			'rows' => 8,
			'new_lines' => '',
		),
		array(
			'key' => 'field_5be41682ba2cf',
			'label' => 'Facebook weight',
			'name' => 'wpc_social_message_facebook_weight',
			'type' => 'number',
			'instructions' => sprintf( 'By default, all posts have the same weight of %d. If you want this post to have more or less weight, increase or decease the number.', $weight_default ),
			'required' => 1,
			'conditional_logic' => array(
				array(
					array(
						'field' => 'field_5cf42434025ef',
						'operator' => '==',
						'value' => 'facebook',
					),
				),
			),
			'default_value' => $weight_default,
		),
		array(
			'key' => 'field_5cf42692ba5cf',
			'label' => 'Facebook message',
			'name' => 'wpc_social_message_facebook',
			'type' => 'textarea',
			'instructions' => 'Use this field to write a custom Facebook message for this post. Our social media service will automatically add the link to the post AND will add the "#WPCampus" hashtag if you don\'t add it yourself. The max is set at 400 characters.
			
			Be mindful of using phrases like "listen to the podcast" that might imply how a user can or can\'t consume the content. Also, these posts will be shared over and over again so think about past, present, and future tense.',
			'required' => 1,
			'conditional_logic' => array(
				array(
					array(
						'field' => 'field_5cf42434025ef',
						'operator' => '==',
						'value' => 'facebook',
					),
				),
			),
			'default_value' => '',
			'placeholder' => 'What do you want to say to Facebook?',
			'maxlength' => 400,
			'rows' => 8,
			'new_lines' => '',
		),
		array(
			'key' => 'field_5cf426a5ba5d0',
			'label' => 'Slack message',
			'name' => 'wpc_social_message_slack',
			'type' => 'textarea',
			'instructions' => 'Be mindful of using phrases like "listen to the podcast" that might imply how a user can or can\'t consume the content. Also, these posts will be shared over and over again so think about past, present, and future tense.',
			'required' => 1,
			'conditional_logic' => array(
				array(
					array(
						'field' => 'field_5cf42434025ef',
						'operator' => '==',
						'value' => 'slack',
					),
				),
			),
			'default_value' => '',
			'placeholder' => 'What do you want to say to Slack?',
			'maxlength' => '',
			'rows' => 8,
			'new_lines' => '',
		),
	),
	'location' => $location,
	'menu_order' => 0,
	'position' => 'normal',
	'style' => 'default',
	'label_placement' => 'left',
	'instruction_placement' => 'field',
	'hide_on_screen' => '',
	'active' => true,
	'description' => '',
));
