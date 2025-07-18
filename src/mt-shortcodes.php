<?php
/**
 * Shortcodes
 *
 * @category Display
 * @package  My Tickets
 * @author   Joe Dolson
 * @license  GPLv3
 * @link     https://www.joedolson.com/my-tickets/
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
} // Exit if accessed directly.

add_shortcode( 'quick-cart', 'my_tickets_short_cart' );
add_filter( 'universal_top_of_header', 'my_tickets_short_cart', 10, 1 );
add_filter( 'milky_way_top_of_header', 'my_tickets_short_cart', 10, 1 );

/**
 * Shortcode to add quick cart to site. Shows current number of tickets and total value plus link to checkout.
 *
 * @return string
 */
function my_tickets_short_cart() {
	$options = mt_get_settings();
	$cart    = mt_get_cart();
	$total   = mt_total_cart( $cart );
	$tickets = mt_count_cart( $cart );
	// Translators: Number of tickets.
	$ticket_text  = apply_filters( 'mt_quick_cart_ticket_text', sprintf( _n( '%s ticket', '%s tickets', $tickets, 'my-tickets' ), "<span class='mt_qc_tickets'>$tickets</span>" ) );
	$url          = mt_get_cart_url();
	$symbol_order = $options['symbol_order'];
	if ( 'symbol-first' === $symbol_order ) {
		$currency = "<span class='mt_currency'>" . mt_symbols( $options['mt_currency'] ) . "</span><span class='mt_qc_total'>" . number_format( $total, 2 ) . '</span>';
	} else {
		$currency = "<span class='mt_qc_total'>" . number_format( $total, 2 ) . "</span> <span class='mt_currency'>" . mt_symbols( $options['mt_currency'] ) . '</span>';
	}
	// Translators: Checkout URL.
	$checkout = apply_filters( 'mt_quick_cart_checkout', sprintf( __( '<a href="%s">Checkout</a>', 'my-tickets' ), esc_url( $url ) ) );
	if ( $tickets < 1 && 'true' === $options['mt_hide_empty_short_cart'] ) {
		return '';
	}
	return "
		<div class='my-tickets mt-quick-cart' aria-live='polite'>" . __( 'In your cart: ', 'my-tickets' ) . "$ticket_text
			<span class='divider'>|</span> 
			$currency
			<span class='divider'>|</span> 
			$checkout
		</div>";
}

/**
 * Shortcode to generate ticketing form. Required attribute: event="event_id"
 *
 * @param array  $atts Shortcode attributes.
 * @param string $content Contained content.
 *
 * @return string
 */
function mt_add_to_cart_form_shortcode( $atts, $content = '' ) {
	$atts = shortcode_atts(
		array(
			'event'    => false,
			'view'     => 'calendar',
			'time'     => 'month',
			'location' => 'false',
		),
		$atts
	);

	$post_id  = mt_get_current_event();
	$event_id = ( isset( $atts['event'] ) && ! empty( $atts['event'] ) ) ? absint( $atts['event'] ) : $post_id;
	$return   = '';
	$location = '';
	if ( $event_id ) {
		$return = mt_add_to_cart_form( $content, $event_id, $atts['view'], $atts['time'], true );
		if ( 'false' !== $atts['location'] ) {
			$location = mt_get_ticket_venue( false, $event_id );
		}
		if ( 'before' === $atts['location'] || 'true' === $atts['location'] ) {
			$return = $location . $return;
		}
		if ( 'after' === $atts['location'] ) {
			$return = $return . $location;
		}

		return $return;
	}

	return $content;
}
add_shortcode( 'ticket', 'mt_add_to_cart_form_shortcode' );


/**
 * Shortcode to display event venue.
 *
 * @param array  $atts Shortcode attributes.
 * @param string $content Contained content.
 *
 * @return string
 */
function mt_ticket_venue_shortcode( $atts, $content = '' ) {
	$atts = shortcode_atts(
		array(
			'event' => false,
		),
		$atts
	);

	$post_id  = mt_get_current_event();
	$event_id = isset( $atts['event'] ) ? absint( $atts['event'] ) : $post_id;
	if ( $event_id ) {
		$venue = mt_get_ticket_venue( false, $event_id );
	} else {
		$venue = $content;
	}

	return $venue;
}
add_shortcode( 'ticket_venue', 'mt_ticket_venue_shortcode' );

/**
 * Produce a list of featured tickets with a custom template and registration forms.
 *
 * @param array  $atts Shortcode attributes.
 * @param string $content Contained content.
 *
 * @return string
 */
function mt_featured_tickets( $atts, $content = '' ) {
	$grouped = false;
	$atts    = shortcode_atts(
		array(
			'events'   => false,
			'group'    => false,
			'taxonomy' => 'mt-event-group',
			'view'     => 'calendar',
			'time'     => 'month',
			'template' => '<h3>{post_title}: {event_begin format="l, F d"}</h3><p>{post_excerpt}</p>',
		),
		$atts
	);
	if ( $atts['events'] ) {
		$events = explode( ',', $atts['events'] );
	} elseif ( $atts['group'] ) {
		if ( is_string( $atts['group'] ) ) {
			// Gets a group of events by taxonomy term.
			$events = mt_get_events_by_term( $atts['group'], $atts['taxonomy'] );
		}
		if ( is_numeric( $atts['group'] ) ) {
			// Gets a group of events by My Calendar event group ID.
			$events = mt_get_events_by_group_id( $atts['group'] );
		}
		$grouped = true;
	} else {
		/**
		 * Set an array of default event IDs to show in the [tickets] shortcode. Only runs if the 'events' shortcode attribute is false.
		 *
		 * @hook mt_default_ticketed_events
		 *
		 * @param {array} $events Array of event IDs.
		 * @param {array} $atts Shortcode attributes.
		 * @param {string} $content Shortcode contents.
		 *
		 * @return {array}
		 */
		$events = apply_filters( 'mt_default_ticketed_events', array(), $atts, $content );
	}
	$content = '';
	if ( is_array( $events ) && ! empty( $events ) ) {
		$group = false;
		if ( $grouped ) {
			$count = count( $events );
			$last  = $events[ $count - 1 ];
			$first = $events[0];
			$group = array(
				'first' => $first,
				'last'  => $last,
			);
		}
		foreach ( $events as $event ) {
			$event_data = get_post_meta( $event, '_mc_event_data', true );
			$post       = get_post( $event, ARRAY_A );
			if ( is_array( $post ) && is_array( $event_data ) ) {
				/**
				 * Filter the data used to draw event templates in the [tickets] shortcode.
				 *
				 * @hook mt_ticket_template_array
				 *
				 * @param {array} $data Merged array of stored event data and post object as array.
				 *
				 * @return {array}
				 */
				$data       = apply_filters( 'mt_ticket_template_array', array_merge( $event_data, $post ) );
				$event_data = "<div class='mt-event-details'>" . mt_draw_template( $data, $atts['template'] ) . '</div>';
				$content   .= "<div class='mt-event-item'>" . $event_data . mt_add_to_cart_form( '', $event, $atts['view'], $atts['time'], true, $group ) . '</div>';
			}
		}
	}

	return "<div class='mt-event-list'>" . $content . '</div>';
}
add_shortcode( 'tickets', 'mt_featured_tickets' );

/**
 * Display the number of tickets remaining for an event.
 *
 * @param array  $atts Shortcode attributes.
 * @param string $content Contained content.
 *
 * @return string
 */
function mt_remaining_tickets( $atts, $content = '' ) {
	$atts     = shortcode_atts(
		array(
			'event'    => false,
			'template' => '<p>{remain} tickets left of {total}</p>',
		),
		$atts
	);
	$template = $atts['template'];

	if ( ! is_numeric( $atts['event'] ) ) {
		global $post;
		if ( is_object( $post ) ) {
			$event_id = $post->ID;
		} else {
			return $content;
		}
	} else {
		$event_id = (int) $atts['event'];
	}
	$registration = get_post_meta( $event_id, '_mt_registration_options', true );
	if ( is_array( $registration ) ) {
		$pricing      = $registration['prices'];
		$available    = $registration['total'];
		$tickets_data = mt_tickets_left( $pricing, $available );

		return '<div class="mt-remaining-tickets">' . mt_draw_template( $tickets_data, $template ) . '</div>';
	} else {
		return $content;
	}

	return $content;
}
add_shortcode( 'remaining', 'mt_remaining_tickets' );

/**
 * Add My Tickets data into the My Calendar templating array.
 * Currently: `register` => Add to cart form;
 *            `ticket_status` => Show if the event is sold out.
 *            `tickets_available` => Show total number of tickets available.
 *
 * @param array  $e Array of My Calendar template values.
 * @param object $event My Calendar event object.
 *
 * @return array
 */
function mt_add_shortcode( $e, $event ) {
	$e['register']          = mt_add_to_cart_form( '', $event->event_post );
	$e['ticket_status']     = mt_event_status( $event->event_post );
	$tickets_available      = mt_check_inventory( $event->event_post );
	$e['tickets_available'] = ( isset( $tickets_available['available'] ) ) ? '<span class="mc-tickets-available">' . $tickets_available['available'] . '</span>' : '';

	return $e;
}
// Add My Tickets tags into My Calendar templating for upcoming events lists, etc.
add_filter( 'mc_filter_shortcodes', 'mt_add_shortcode', 5, 2 );

/**
 * Shortcode to output a user's purchases and tickets.
 *
 * @param array  $atts Array of shortcode attributes.
 * @param string $content Contained content.
 *
 * @return string
 */
function mt_user_purchases( $atts, $content ) {
	$atts   = shortcode_atts(
		array(
			'user_id'    => false,
			'count'      => 10,
			'user_email' => '',
		),
		$atts
	);
	$output = mt_display_payments( $atts['user_id'], $atts['count'], $atts['user_email'] );

	return $output;
}
add_shortcode( 'my-payments', 'mt_user_purchases' );

/**
 * Fetch a user's payment history and output on the front-end.
 *
 * @param int    $user_id User ID.
 * @param int    $count Number of payments to display by default.
 * @param string $user_email User email. If provided, fetch payments by email supplied rather than user ID. Ignores User ID.
 *
 * @return string
 */
function mt_display_payments( $user_id = false, $count = 10, $user_email = '' ) {
	$output = '';
	if ( is_user_logged_in() ) {
		$user  = ( ! $user_id ) ? wp_get_current_user()->ID : $user_id;
		$count = ( ! $count ) ? 10 : absint( $count );
		if ( $user && ! $user_email ) {
			$payments = get_posts(
				array(
					'post_type'   => 'mt-payments',
					'post_status' => array( 'draft, publish' ),
					'author'      => $user,
					'numberposts' => $count,
				)
			);
		} elseif ( $user_email && is_email( $user_email ) ) {
			$payments = get_posts(
				array(
					'post_type'   => 'mt-payments',
					'post_status' => array( 'draft', 'publish' ),
					'numberposts' => $count,
					'meta_query'  => array(
						array(
							'key'     => 'email',
							'value'   => $user_email,
							'compare' => '=',
						),
					),
				)
			);
		} else {
			$payments = array();
		}
		if ( ! empty( $payments ) ) {
			$thead = '<table class="widefat mt-payments striped">
						<caption>' . __( 'Your Payments', 'my-tickets' ) . '</caption>
						<thead>
							<tr>
								<th scope="col">' . __( 'ID', 'my-tickets' ) . '</th>
								<th scope="col">' . __( 'Name', 'my-tickets' ) . '</th>
								<th scope="col">' . __( 'Date', 'my-tickets' ) . '</th>
								<th scope="col">' . __( 'Status', 'my-tickets' ) . '</th>
								<th scope="col">' . __( 'Details', 'my-tickets' ) . '</th>
							</tr>
						</thead>
						<tbody>';
			$tfoot = '</tbody>
				</table>';
			foreach ( $payments as $payment ) {
				$details  = '<div class="mt-payment-details">';
				$details .= mt_payment_data( $payment->ID, array( 'dispute', 'other', 'purchase', 'ticket' ) );
				$details .= '</div>';
				$classes  = implode( ' ', array( $payment->post_status, sanitize_title( get_post_meta( $payment->ID, '_is_paid', true ) ) ) );
				$output  .= '<tr class="' . $classes . '">
					<td>' . $payment->ID . '</td>
					<td>' . esc_html( get_the_title( $payment->ID ) ) . '</td>
					<td>' . get_the_date( 'Y-m-d H:i', $payment->ID ) . '</td>
					<td>' . mt_get_payment_status( $payment->ID ) . '</td>
					<td><button type="button" class="mt-show-payment-details" aria-expanded="false">' . __( 'Payment Details', 'my-tickets' ) . '</button>' . $details . '</td>
				</tr>';
			}
			$output = $thead . $output . $tfoot;
		}
	}

	return $output;
}
