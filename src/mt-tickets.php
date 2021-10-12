<?php
/**
 * Ticket display and verification handlers.
 *
 * @category Core
 * @package  My Tickets
 * @author   Joe Dolson
 * @license  GPLv2 or later
 * @link     https://www.joedolson.com/my-tickets/
 */

add_filter( 'template_redirect', 'mt_ticket', 10, 1 );
/**
 * If ticket_id is set and valid, load ticket template. Else, redirect to purchase page.
 */
function mt_ticket() {
	$options = array_merge( mt_default_settings(), get_option( 'mt_settings', array() ) );
	$id      = ( '' !== $options['mt_tickets_page'] ) ? $options['mt_tickets_page'] : false;
	if ( $id && ( is_single( $id ) || is_page( $id ) ) ) {
		if ( ! isset( $_GET['multiple'] ) ) {
			if ( isset( $_GET['ticket_id'] ) && mt_verify_ticket( sanitize_text_field( $_GET['ticket_id'] ) ) ) {
				$template = locate_template( 'tickets.php' );
				if ( $template ) {
					load_template( $template );
				} else {
					load_template( dirname( __FILE__ ) . '/templates/tickets.php' );
				}
			} else {
				wp_safe_redirect( get_permalink( $options['mt_purchase_page'] ) );
				exit;
			}
		} else {
			if ( isset( $_GET['receipt_id'] ) ) {
				$template = locate_template( 'bulk-tickets.php' );
				if ( $template ) {
					load_template( $template );
				} else {
					load_template( dirname( __FILE__ ) . '/templates/bulk-tickets.php' );
				}
			} else {
				wp_safe_redirect( get_permalink( $options['mt_purchase_page'] ) );
				exit;
			}
		}
		exit;
	}
}

/**
 * Verify that ticket is valid. (Does not check whether ticket is for current or future event.)
 *
 * @param string $ticket_id Ticket ID.
 * @param string $return type of data to return.
 *
 * @return array|bool
 */
function mt_verify_ticket( $ticket_id = false, $return = 'boolean' ) {
	if ( $ticket_id ) {
		$ticket = mt_get_ticket( $ticket_id );
	} else {
		$ticket = mt_get_ticket();
	}
	if ( $ticket ) {
		$data = get_post_meta( $ticket->ID, '_' . $ticket_id, true );
		if ( empty( $data ) ) {
			// This ticket does not exist.
			return false;
		}
		$purchase_id = $data['purchase_id'];
		$status      = get_post_meta( $purchase_id, '_is_paid', true );
		$gateway     = get_post_meta( $purchase_id, '_gateway', true );
		if ( 'Completed' === $status || ( 'Pending' === $status && 'offline' === $gateway ) ) {
			return ( 'full' === $return ) ? array(
				'status' => true,
				'ticket' => $ticket,
			) : true;
		}
	}

	return ( 'full' === $return ) ? array(
		'status' => false,
		'ticket' => false,
	) : false;
}

/**
 * Get ticket object for use in ticket template if ticket ID is set and valid.
 *
 * @param bool|string $ticket_id Ticket ID.
 *
 * @return bool
 */
function mt_get_ticket( $ticket_id = false ) {
	global $wpdb;

	$ticket_id = isset( $_GET['ticket_id'] ) ? $_GET['ticket_id'] : $ticket_id;
	// sanitize ticket id.
	$ticket_id = strtolower( preg_replace( '/[^a-z0-9\-]+/i', '', $ticket_id ) );
	$ticket    = false;
	if ( $ticket_id ) {
		$post_id = $wpdb->get_var( $wpdb->prepare( "SELECT post_id FROM $wpdb->postmeta WHERE meta_key = '_ticket' AND meta_value = %s", $ticket_id ) );
		$post    = get_post( $post_id );
		$ticket  = ( $post ) ? $post : false;
	}

	return $ticket;
}


add_filter( 'mt_default_ticketed_events', 'mt_get_ticket_ids', 10, 2 );
/**
 * Get an array of IDs for live ticketed events.
 *
 * @param array  $atts Array of attributes passed to [tickets] shortcode.
 * @param string $content Contained content wrapped in [tickets] shortcode.
 *
 * @return array
 */
function mt_get_ticket_ids( $atts, $content ) {
	// fetch posts with meta data for event sales.
	$settings = array_merge( mt_default_settings(), get_option( 'mt_settings', array() ) );
	// only show limit of 20 events.
	$args  =
		array(
			'post_type'      => $settings['mt_post_types'],
			'posts_per_page' => apply_filters( 'mt_get_events_count', 20 ),
			'post_status'    => array( 'publish' ),
			'fields'         => 'ids',
			'meta_query'     => array(
				'relation' => 'AND',
				'queries'  => array(
					'key'     => '_mc_event_date',
					'value'   => mt_current_time(),
					'compare' => '>',
				),
			),
		);
	$args  = apply_filters( 'mt_get_ticket_ids', $args );
	$query = new WP_Query( $args );
	$posts = $query->posts;

	return $posts;
}

add_filter( 'after_setup_theme', 'my_tickets_ticket_image_size' );
/**
 * Add a custom thumbnail size for use by My Tickets.
 */
function my_tickets_ticket_image_size() {
	add_image_size( 'my-tickets-logo', 300, 300, true );
}
