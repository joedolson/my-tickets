<?php
/**
 * Shortcodes
 *
 * @category Display
 * @package  My Tickets
 * @author   Joe Dolson
 * @license  GPLv2 or later
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
	$options = array_merge( mt_default_settings(), get_option( 'mt_settings', array() ) );
	$cart    = mt_get_cart();
	$total   = mt_total_cart( $cart );
	$tickets = mt_count_cart( $cart );
	// Translators: Number of tickets.
	$ticket_text  = apply_filters( 'mt_quick_cart_ticket_text', sprintf( __( '%s tickets', 'my-tickets' ), "<span class='mt_qc_tickets'>$tickets</span>" ) );
	$url          = get_permalink( $options['mt_purchase_page'] );
	$symbol_order = $options['symbol_order'];
	if ( 'symbol-first' === $symbol_order ) {
		$currency = "<span class='mt_currency'>" . mt_symbols( $options['mt_currency'] ) . "</span><span class='mt_qc_total'>" . number_format( $total, 2 ) . '</span>';
	} else {
		$currency = "<span class='mt_qc_total'>" . number_format( $total, 2 ) . "</span> <span class='mt_currency'>" . mt_symbols( $options['mt_currency'] ) . '</span>';
	}
	// Translators: Checkout URL.
	$checkout = apply_filters( 'mt_quick_cart_checkout', sprintf( __( '<a href="%s">Checkout</a>', 'my-tickets' ), $url ) );
	return "
		<div class='mt-quick-cart' aria-live='polite'>" . __( 'In your cart: ', 'my-tickets' ) . "$ticket_text
			<span class='divider'>|</span> 
			$currency
			<span class='divider'>|</span> 
			$checkout
		</div>";
}

add_shortcode( 'ticket', 'mt_registration_form_shortcode' );
/**
 * Shortcode to generate ticketing form. Required attribute: event="event_id"
 *
 * @param array  $atts Shortcode attributes.
 * @param string $content Contained content.
 *
 * @return string
 */
function mt_registration_form_shortcode( $atts, $content = '' ) {
	$atts = shortcode_atts(
		array(
			'event' => false,
			'view'  => 'calendar',
			'time'  => 'month',
		),
		$atts
	);
	if ( $atts['event'] ) {
		return mt_registration_form( $content, $atts['event'], $atts['view'], $atts['time'], true );
	}

	return '';
}
add_shortcode( 'tickets', 'mt_featured_tickets' );

/**
 * Produce a list of featured tickets with a custom template and registration forms.
 *
 * @param array  $atts Shortcode attributes.
 * @param string $content Contained content.
 *
 * @return string
 */
function mt_featured_tickets( $atts, $content = '' ) {
	$atts = shortcode_atts(
		array(
			'events'   => false,
			'view'     => 'calendar',
			'time'     => 'month',
			'template' => '<h3>{post_title}: {event_begin format="l, F d"}</h3><p>{post_excerpt}</p>',
		),
		$atts
	);
	if ( $atts['events'] ) {
		$events = explode( ',', $atts['events'] );
	} else {
		$events = apply_filters( 'mt_default_ticketed_events', array(), $atts, $content );
	}
	$content = '';
	if ( is_array( $events ) ) {
		foreach ( $events as $event ) {
			$event_data = get_post_meta( $event, '_mc_event_data', true );
			$post       = get_post( $event, ARRAY_A );
			if ( is_array( $post ) && is_array( $event_data ) ) {
				$data       = apply_filters( 'mt_ticket_template_array', array_merge( $event_data, $post ) );
				$event_data = "<div class='mt-event-details'>" . mt_draw_template( $data, $atts['template'] ) . '</div>';
				$content   .= "<div class='mt-event-item'>" . $event_data . mt_registration_form( '', $event, $atts['view'], $atts['time'], true ) . '</div>';
			}
		}
	}

	return "<div class='mt-event-list'>" . $content . '</div>';
}
add_shortcode( 'remaining', 'mt_remaining_tickets' );

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

// Add {register} form to My Calendar templating for upcoming events lists, etc.
add_filter( 'mc_filter_shortcodes', 'mt_add_shortcode', 5, 2 );
/**
 * Insert {register} quicktag into the My Calendar templating array.
 *
 * @param array  $e Array of My Calendar template values.
 * @param object $event My Calendar event object.
 *
 * @return array
 */
function mt_add_shortcode( $e, $event ) {
	$e['register']      = mt_registration_form( '', $event->event_post );
	$e['ticket_status'] = mt_event_status( $event->event_post );

	return $e;
}

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
					'post_type'    => 'mt-payments',
					'post_author'  => $user,
					'number_posts' => $count,
				)
			);
		} elseif ( $user_email && is_email( $user_email ) ) {
			$payments = get_posts(
				array(
					'post_type'    => 'mt-payments',
					'number_posts' => $count,
					'meta_query'   => array(
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
								<th scope="col">' . __( 'Payment ID', 'my-tickets' ) . '</th>
								<th scope="col">' . __( 'Payment Name', 'my-tickets' ) . '</th>
								<th scope="col">' . __( 'Payment Date', 'my-tickets' ) . '</th>
								<th scope="col">' . __( 'Show Details', 'my-tickets' ) . '</th>
							</tr>
						</thead>
						<tbody>';
			$tfoot = '</tbody>
				</table>';
			foreach ( $payments as $payment ) {
				$details  = '<div class="mt-payment-details">';
				$details .= mt_payment_data( $payment->ID, array( 'dispute', 'other', 'purchase', 'ticket' ) );
				$details .= '</div>';
				$output  .= '<tr>
					<td>' . $payment->ID . '</td>
					<td>' . get_the_title( $payment->ID ) . '</td>
					<td>' . get_the_date( 'Y-m-d H:i', $payment->ID ) . '</td>
					<td><button type="button" class="mt-show-payment-details" aria-expanded="false">' . __( 'Payment Details', 'my-tickets' ) . '</button>' . $details . '</td>
				</tr>';
			}
			$output = $thead . $output . $tfoot;
		}
	}

	return $output;
}
