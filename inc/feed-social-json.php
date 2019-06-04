<?php
/**
 * RSS2 Feed Template for displaying our tweets.
 */

header( 'Content-Type: application/json' );

global $wp_query;

$helper = wpcampus_social_media();

$feed_platform = $helper->get_query_feed_platform( $wp_query );

$feed_items = $helper->get_social_feed( $feed_platform );

if ( empty( $feed_items ) ) {
	echo json_encode( [] );
	exit;
}

echo json_encode( $feed_items );
exit;
