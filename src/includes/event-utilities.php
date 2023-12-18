<?php
/**
 * Utilities for fetching ticketed events.
 *
 * @category Events
 * @package  My Tickets
 * @author   Joe Dolson
 * @license  GPLv2 or later
 * @link     https://www.joedolson.com/my-tickets/
 */

/**
 * Fetch a group of events on a taxonomy term.
 *
 * @param string|array $term A term slug or array of term slugs.
 * @param string       $taxonomy A taxonomy name. Optional; default 'mt-event-group'.
 * @param array        $types An array of post type names. Optiona; default all enabled types.
 *
 * @return array
 */
function mt_get_events_by_term( $term, $taxonomy = 'mt-event-group', $types = array() ) {
	$options = mt_get_settings();
	$types   = ( empty( $types ) ) ? $options['mt_post_types'] : $types;
	$args    = array(
		'post_type'      => $types,
		'tax_query'      => array(
			array(
				'taxonomy' => $taxonomy,
				'field'    => 'slug',
				'terms'    => $term,
			),
		),
		'fields'         => 'ids',
		'posts_per_page' => -1,
	);

	$posts = get_posts( $args );

	return $posts;
}

/**
 * Fetch a group of events using a My Calendar group ID. Argument is an event ID, gets all events in the same group as that event.
 *
 * @param string $event_id A My Calendar event ID.
 *
 * @return array
 */
function mt_get_events_by_group_id( $event_id ) {
	$events = array();
	if ( function_exists( 'mc_get_data' ) ) {
		$group_id = mc_get_data( 'event_group_id', $event_id );
		$events   = mc_get_grouped_events( $group_id );
	}

	return $events;
}
