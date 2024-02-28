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
	$options = mt_get_settings();
	$id      = ( '' !== $options['mt_tickets_page'] ) ? $options['mt_tickets_page'] : false;
	if ( $id && ( is_single( $id ) || is_page( $id ) ) ) {
		if ( ! isset( $_GET['multiple'] ) ) {
			if ( isset( $_GET['ticket_id'] ) && mt_verify_ticket( sanitize_text_field( $_GET['ticket_id'] ) ) ) {
				$template = locate_template( 'tickets.php' );
				if ( $template ) {
					load_template( $template );
				} else {
					load_template( __DIR__ . '/templates/tickets.php' );
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
					load_template( __DIR__ . '/templates/bulk-tickets.php' );
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
 * @param string $return_type type of data to return.
 *
 * @return array|bool
 */
function mt_verify_ticket( $ticket_id = false, $return_type = 'boolean' ) {
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
			return ( 'full' === $return_type ) ? array(
				'status' => true,
				'ticket' => $ticket,
			) : true;
		}
	}

	return ( 'full' === $return_type ) ? array(
		'status' => false,
		'ticket' => false,
	) : false;
}

/**
 * Get ticket object for use in ticket template if ticket ID is set and valid.
 *
 * @param bool|string $ticket_id Ticket ID.
 *
 * @return bool|object
 */
function mt_get_ticket( $ticket_id = false ) {
	global $wpdb;

	$ticket_id = isset( $_GET['ticket_id'] ) ? sanitize_text_field( $_GET['ticket_id'] ) : $ticket_id;
	// sanitize ticket id.
	$ticket_id = strtolower( preg_replace( '/[^a-z0-9\-]+/i', '', $ticket_id ) );
	$ticket    = false;
	if ( $ticket_id ) {
		$event_id = $wpdb->get_var( $wpdb->prepare( "SELECT post_id FROM $wpdb->postmeta WHERE meta_key = '_ticket' AND meta_value = %s", $ticket_id ) );
		$event    = get_post( $event_id );
		$ticket   = ( $event ) ? $event : false;
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
	$settings = mt_get_settings();
	/**
	 * Filter number of events to show by default in the list of current events.
	 *
	 * @hook mt_get_events_count
	 *
	 * @param {int} $count Number of events to show.
	 *
	 * @return {int}
	 */
	$count = apply_filters( 'mt_get_events_count', 20 );
	$args  =
		array(
			'post_type'      => $settings['mt_post_types'],
			'posts_per_page' => $count,
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
	/**
	 * Customize arguments for events to show with the [tickets] shortcode.
	 *
	 * @hook mt_get_ticket_ids_query
	 *
	 * @param {array} $args An array of arguments passed to WP_Query.
	 *
	 * @return {array}
	 */
	$args  = apply_filters( 'mt_get_ticket_ids_query', $args );
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

/**
 * Move ticket from one event to another.
 *
 * @param int    $payment_id ID for the payment this ticket is from.
 * @param int    $event_id ID for the event a ticket is currently attached to.
 * @param int    $target_id ID for the event a ticket needs to be attached to.
 * @param string $ticket Ticket ID to be moved.
 *
 * @return bool
 */
function mt_move_ticket( $payment_id, $event_id, $target_id, $ticket ) {
	$registration = get_post_meta( $event_id, '_mt_registration_options', true );
	$ticket_data  = get_post_meta( $event_id, '_' . $ticket, true );
	$removed      = false;

	$added = mt_add_ticket( $target_id, $ticket, $ticket_data, $payment_id );
	if ( $added ) {
		$removed = mt_remove_ticket( $event_id, $ticket, $ticket_data, $payment_id );
	}

	$response = array(
		'registration' => $registration,
		'ticket_data'  => $ticket_data,
		'event_id'     => $event_id,
		'target'       => $target_id,
		'ticket'       => $ticket,
		'removed'      => $removed,
		'added'        => $added,
		'purchase'     => get_post_meta( $payment_id, '_purchased' ),
	);

	return $response;
}

/**
 * Add a ticket to an event.
 *
 * @param int    $event_id ID for an event.
 * @param string $ticket Ticket ID to be added.
 * @param array  $data Ticket data to add.
 * @param int    $payment_id Associated payment post.
 */
function mt_add_ticket( $event_id, $ticket, $data, $payment_id ) {
	// Exit early if the data passed isn't valid.
	if ( ! is_array( $data ) ) {
		return false;
	}
	$ticket_type  = $data['type'];
	$registration = get_post_meta( $event_id, '_mt_registration_options', true );
	// Exit early if this ticket type doesn't exist on the current event.
	if ( ! isset( $registration['prices'][ $ticket_type ] ) ) {
		return false;
	}
	$registration['prices'][ $ticket_type ]['sold'] = $registration['prices'][ $ticket_type ]['sold'] + 1;
	$registration['total']                          = ( 'inherit' !== $registration['total'] ) ? $registration['total'] + 1 : 'inherit';
	update_post_meta( $event_id, '_mt_registration_options', $registration );
	add_post_meta( $event_id, '_ticket', $ticket );
	update_post_meta( $event_id, '_' . $ticket, $data );

	mt_map_purchase_event_data( $event_id, $payment_id, $ticket_type, $data );
	mt_map_event_purchase_data( $event_id, $payment_id, $ticket_type, $data );

	return true;
}

/**
 * Add event purchase data to a purchase. Verifies whether this purchase is already attached first. Iterates over data on this payment ID and updates counts for a ticket type if exists or inserts if not.
 *
 * @param int    $event_id Event ID.
 * @param int    $payment_id Payment ID.
 * @param string $ticket_type Ticket type.
 * @param array  $data Payment data.
 */
function mt_map_purchase_event_data( $event_id, $payment_id, $ticket_type, $data ) {
	$purchase = get_post_meta( $payment_id, '_purchased' );
	$ids      = array();
	// See whether this event already exists in the purchase.
	foreach ( $purchase as $item ) {
		foreach ( $item as $k => $p ) {
			$ids[] = (int) $k;
			$n     = $p;
			if ( $event_id === $k ) {
				if ( isset( $n[ $ticket_type ] ) ) {
					$n[ $ticket_type ]['count'] = $n[ $ticket_type ]['count'] + 1;
				} else {
					$n[ $ticket_type ] = array(
						'count' => 1,
						'price' => $data['price'],
					);
				}
				$nitem = array( $k => $n );
				update_post_meta( $payment_id, '_purchased', $nitem, $item );
			}
		}
	}
	// If not, add a new item.
	if ( ! in_array( (int) $event_id, $ids, true ) ) {
		add_post_meta(
			$payment_id,
			'_purchased',
			array(
				$event_id => array(
					$ticket_type => array(
						'count' => 1,
						'price' => $data['price'],
					),
				),
			)
		);
	}
}

/**
 * Add purchase data to an event. Verifies whether this purchase is already attached first. Iterates over data on this event ID and updates counts for a ticket type if exists or inserts if not.
 *
 * @param int    $event_id Event ID.
 * @param int    $payment_id Payment ID.
 * @param string $ticket_type Ticket type.
 * @param array  $data Payment data.
 */
function mt_map_event_purchase_data( $event_id, $payment_id, $ticket_type, $data ) {
	// Check whether this purchase is already registered on the event.
	$event = get_post_meta( $event_id, '_purchase' );
	$ids   = array();
	if ( ! empty( $event ) ) {
		foreach ( $event as $item ) {
			foreach ( $item as $k => $p ) {
				$ids[] = (int) $k;
				$n     = $p;
				if ( $payment_id === $k ) {
					if ( isset( $n[ $ticket_type ] ) ) {
						$n[ $ticket_type ]['count'] = $n[ $ticket_type ]['count'] + 1;
					} else {
						$n[ $ticket_type ] = array(
							'count' => 1,
							'price' => $data['price'],
						);
					}
					$nitem = array( $k => $n );
					update_post_meta( $event_id, '_purchase', $nitem, $item );
				}
			}
		}
	} else {
		// If not, add a new item.
		if ( ! in_array( (int) $payment_id, $ids, true ) ) {
			add_post_meta(
				$event_id,
				'_purchase',
				array(
					$payment_id => array(
						$ticket_type => array(
							'count' => 1,
							'price' => $data['price'],
						),
					),
				)
			);
		}
	}
}

/**
 * Remove a ticket from an event.
 *
 * @param int    $event_id ID for an event.
 * @param string $ticket Ticket ID to be removed.
 * @param array  $data Ticket data to remove.
 * @param int    $payment_id Associated payment post.
 */
function mt_remove_ticket( $event_id, $ticket, $data, $payment_id ) {
	// Remove ticket from event.
	$registration                                   = get_post_meta( $event_id, '_mt_registration_options', true );
	$ticket_type                                    = $data['type'];
	$tickets_sold                                   = $registration['prices'][ $ticket_type ]['sold'];
	$new_sold                                       = $tickets_sold - 1;
	$registration['prices'][ $ticket_type ]['sold'] = $new_sold;
	$registration['total']                          = ( 'inherit' !== $registration['total'] ) ? $registration['total'] - 1 : 'inherit';

	update_post_meta( $event_id, '_mt_registration_options', $registration );
	$meta_deleted   = delete_post_meta( $event_id, '_ticket', $ticket );
	$ticket_deleted = delete_post_meta( $event_id, '_' . $ticket );
	// Update stats on the payment.
	$purchase = get_post_meta( $payment_id, '_purchased' );
	foreach ( $purchase as $item ) {
		foreach ( $item as $k => $p ) {
			if ( (int) $event_id === (int) $k ) {
				if ( ! isset( $p[ $ticket_type ] ) ) {
					continue;
				}
				$p[ $ticket_type ]['count'] = $p[ $ticket_type ]['count'] - 1;
				$nitem                      = array( $k => $p );
				update_post_meta( $payment_id, '_purchased', $nitem, $item );
			}
		}
	}
	// Update stats on the event.
	$event = get_post_meta( $event_id, '_purchase' );
	foreach ( $event as $item ) {
		foreach ( $item as $k => $p ) {
			if ( (int) $payment_id === (int) $k ) {
				if ( ! isset( $p[ $ticket_type ] ) ) {
					continue;
				}
				$p[ $ticket_type ]['count'] = $p[ $ticket_type ]['count'] - 1;
				$nitem                      = array( $k => $p );
				update_post_meta( $event_id, '_purchase', $nitem, $item );
			}
		}
	}

	return ( $meta_deleted && $ticket_deleted ) ? true : false;
}
