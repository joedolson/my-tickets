<?php
/**
 * Ticket display template tags.
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

require_once( 'vendor/autoload.php' );
use chillerlan\QRCode\QRCode;
use chillerlan\QRCode\QROptions;

/**
 * Get logo for display on receipts and tickets.
 *
 * @param array $args Custom arguments.
 * @param int   $post_ID Post ID.
 *
 * @return string
 */
function mt_get_logo( $args = array(), $post_ID = false ) {
	$options = array_merge( mt_default_settings(), get_option( 'mt_settings', array() ) );
	$ticket  = mt_get_ticket();
	if ( isset( $options['mt_ticket_image'] ) && 'event' === $options['mt_ticket_image'] && $ticket ) {
		// if event has post thumbnail, use that.
		if ( has_post_thumbnail( $ticket->ID ) ) {
			return get_the_post_thumbnail( $ticket->ID );
		}
	}
	if ( $post_ID && has_post_thumbnail( $post_ID ) ) {
		return get_the_post_thumbnail( $post_ID, 'my-tickets-logo' );
	}
	$args = array_merge(
		array(
			'alt'    => 'My Tickets',
			'class'  => 'default',
			'width'  => '',
			'height' => '',
		),
		$args
	);
	$atts = '';
	foreach ( $args as $att => $value ) {
		if ( '' !== trim( $value ) ) {
			$atts .= ' ' . esc_attr( $att ) . '=' . '"' . esc_attr( $value ) . '"';
		}
	}
	$img = "<img src='" . plugins_url( '/images/logo.png', __FILE__ ) . "' $atts />";

	return $img;
}

/**
 * Get logo for display on receipts and tickets.
 *
 * @param array $args Custom arguments.
 * @param int   $post_ID Post ID.
 *
 * @return void
 */
function mt_logo( $args = array(), $post_ID = false ) {
	echo wp_kses_post( mt_get_logo( $args, $post_ID ) );
}

// Template Functions for Receipts.
/**
 * Return formatted order data for receipt template.
 *
 * @return string
 */
function mt_get_cart_order() {
	$receipt = mt_get_receipt();
	if ( $receipt ) {
		$purchase = get_post_meta( $receipt->ID, '_purchased' );
		$data     = mt_format_purchase( $purchase, 'html', $receipt->ID );

		return $data;
	}

	return '';
}

/**
 * Return formatted order data for receipt template.
 *
 * @return void
 */
function mt_cart_order() {
	echo wp_kses_post( mt_get_cart_order() );
}

/**
 * Get ticket information for a given purchase as data.
 *
 * @return mixed bool/array Ticket IDs.
 */
function mt_get_payment_tickets() {
	$receipt = mt_get_receipt();
	if ( $receipt ) {
		$purchase = get_post_meta( $receipt->ID, '_purchased' );
		$id       = $receipt->ID;

		$ticket_array = array();
		foreach ( $purchase as $purch ) {
			foreach ( $purch as $event => $tickets ) {
				$purchases[ $event ] = $tickets;
				foreach ( $tickets as $type => $details ) {
					// add ticket hash for each ticket.
					$count = $details['count'];
					// only add tickets if count of tickets is more than 0.
					if ( $count >= 1 ) {
						$price = $details['price'];
						for ( $i = 0; $i < $count; $i ++ ) {
							$ticket_id      = mt_generate_ticket_id( $id, $event, $type, $i, $price );
							$ticket_array[] = $ticket_id;
						}
					}
				}
			}
		}

		return $ticket_array;
	}

	return false;
}

/**
 * Return receipt ID.
 *
 * @return string
 */
function mt_get_receipt_id() {
	if ( isset( $_GET['receipt_id'] ) ) {
		$receipt_id = sanitize_text_field( $_GET['receipt_id'] );

		return $receipt_id;
	}
}

/**
 * Return receipt ID.
 *
 * @return void
 */
function mt_receipt_id() {
	echo esc_html( mt_get_receipt_id() );
}

/**
 * Get receipt's purchase ID
 *
 * @return integer
 */
function mt_get_receipt_purchase_id() {
	$purchase    = mt_get_receipt();
	$purchase_id = $purchase->ID;

	return $purchase_id;
}

/**
 * Get receipt's purchase ID
 *
 * @return void
 */
function mt_receipt_purchase_id() {
	echo esc_html( mt_get_receipt_purchase_id() );
}

/**
 * Get provided purchaser name from payment.
 *
 * @return string
 */
function mt_get_cart_purchaser() {
	$receipt = mt_get_receipt();
	if ( $receipt ) {
		$purchaser = get_the_title( $receipt->ID );

		return $purchaser;
	}

	return '';
}

/**
 * Get provided purchaser name from payment.
 *
 * @return void
 */
function mt_cart_purchaser() {
	echo esc_html( mt_get_cart_purchaser() );
}

/**
 * Get formatted date/time of purchase.
 *
 * @return string
 */
function mt_get_cart_purchase_date() {
	$receipt = mt_get_receipt();
	if ( $receipt ) {
		$format = apply_filters( 'mt_cart_purchase_date', '<span class="mt-cart-date">' . get_option( 'date_format' ) . '</span><span class="mt-date-separator"> @ </span><span class="mt-cart-time">' . get_option( 'time_format' ) . '</span>' );
		$date   = date_i18n( $format, strtotime( $receipt->post_date ) );

		return $date;
	}

	return '';
}

/**
 * Get formatted date/time of purchase.
 *
 * @return void
 */
function mt_cart_purchase_date() {
	echo wp_kses_post( mt_get_cart_purchase_date() );
}

/**
 * Get payment gateway data from payment.
 *
 * @return string
 */
function mt_get_payment_details() {
	$receipt = mt_get_receipt();
	if ( $receipt ) {
		$paid = get_post_meta( $receipt->ID, '_is_paid', true );
		if ( 'Completed' === $paid ) {
			$gateway       = get_post_meta( $receipt->ID, '_gateway', true );
			$gateways      = mt_setup_gateways();
			$gateway_label = isset( $gateways[ $gateway ] ) ? $gateways[ $gateway ]['label'] : $gateway;
			$transaction   = get_post_meta( $receipt->ID, '_transaction_id', true );
			$total         = get_post_meta( $receipt->ID, '_total_paid', true );
			$hand_total    = get_post_meta( $receipt->ID, '_mt_handling', true );
			$handling      = ( $hand_total ) ? '<li>' . __( 'Handling:', 'my-tickets' ) . ' ' . apply_filters( 'mt_money_format', $hand_total ) . '</li>' : '';
			$ship_total    = get_post_meta( $receipt->ID, '_mt_shipping', true );
			$shipping      = ( $ship_total ) ? '<li>' . __( 'Shipping:', 'my-tickets' ) . ' ' . apply_filters( 'mt_money_format', $ship_total ) . '</li>' : '';
			$vat           = get_post_meta( $receipt->ID, '_vat', true );
			// Translators: VAT ID.
			$vat     = ( $vat ) ? '<li>' . sprintf( __( 'VAT Number: %s', 'my-tickets' ), '<code>' . $vat . '</code>' ) . '</li>' : '';
			$return  = __( 'This receipt is paid in full.', 'my-tickets' );
			$return .= '
		<ul>
			<li>' . __( 'Payment through:', 'my-tickets' ) . " $gateway_label</li>
			<li>" . __( 'Transaction ID:', 'my-tickets' ) . " <code>$transaction</code></li>
			$handling
			$shipping
			<li>" . __( 'Amount Paid:', 'my-tickets' ) . ' ' . apply_filters( 'mt_money_format', $total ) . "</li>
			$vat
		</ul>";

			return $return;
		} elseif ( 'Refunded' === $paid ) {
			return __( 'This payment has been refunded.', 'my-tickets' );
		} elseif ( 'Failed' === $paid ) {
			return __( 'Payment on this order failed.', 'my-tickets' );
		} elseif ( 'Turned Back' === $paid ) {
			return __( 'This purchase was cancelled and the tickets were returned to the seller.', 'my-tickets' );
		} else {
			$due = get_post_meta( $receipt->ID, '_total_paid', true );
			$due = apply_filters( 'mt_money_format', $due );
			// Translators: Amount due on this payment.
			return __( 'Payment on this purchase is not completed. The receipt will be updated with payment details when payment is completed.', 'my-tickets' ) . ' ' . sprintf( __( 'Amount due: %s', 'my-tickets' ), '<strong>' . $due . '</strong>' );
		}
	}

	return '';
}

/**
 * Get payment gateway data from payment.
 *
 * @return void
 */
function mt_payment_details() {
	echo wp_kses_post( mt_get_payment_details() );
}

/**
 * Get ticket ID (must be used in ticket template.)
 *
 * @return string
 */
function mt_get_ticket_id() {
	$ticket_id = sanitize_text_field( $_GET['ticket_id'] );

	return $ticket_id;
}

/**
 * Get ticket ID (must be used in ticket template.)
 *
 * @return void
 */
function mt_ticket_id() {
	echo esc_html( mt_get_ticket_id() );
}

/**
 * Get sequential ticket ID for display purposes.
 *
 * @param string $ticket_id Unique ticket ID.
 *
 * @return string sequential ID
 */
function mt_get_sequential_id( $ticket_id = false ) {
	$ticket_id  = ( $ticket_id ) ? $ticket_id : mt_get_ticket_id();
	$ticket     = mt_get_ticket( $ticket_id );
	$sequential = get_post_meta( $ticket->ID, '_' . $ticket_id . '_seq_id', true );
	if ( ! $sequential ) {
		$sequential = mt_generate_sequential_id( $ticket_id, array( 'event_id' => $ticket->ID ) );
	}

	return zeroise( $sequential, 6 );
}

/**
 * Echo sequential ticket ID (must be used in ticket template.)
 *
 * @return void
 */
function mt_sequential_id() {
	echo esc_html( mt_get_sequential_id() );
}

/**
 * Get ticket method (willcall, postal, eticket, printable)
 *
 * @param bool|int $ticket_id Ticket ID.
 *
 * @return mixed|string
 */
function mt_get_ticket_method( $ticket_id = false ) {
	if ( ! $ticket_id ) {
		$ticket_id = mt_get_ticket_id();
	}
	$purchase    = get_post_meta( mt_get_ticket( $ticket_id )->ID, '_' . $ticket_id, true );
	$purchase_id = $purchase['purchase_id'];
	$ticket_type = get_post_meta( $purchase_id, '_ticketing_method', true );
	$ticket_type = ( $ticket_type ) ? $ticket_type : 'willcall';

	return $ticket_type;
}

/**
 * Get ticket method (willcall, postal, eticket, printable)
 *
 * @param bool|int $ticket_id Ticket ID.
 *
 * @return void
 */
function mt_ticket_method( $ticket_id = false ) {
	echo esc_html( mt_get_ticket_method( $ticket_id ) );
}

/**
 * Get ticket's parent purchase ID
 *
 * @param bool|int $ticket_id Ticket ID.
 *
 * @return integer
 */
function mt_get_ticket_purchase_id( $ticket_id = false ) {
	if ( ! $ticket_id ) {
		$ticket_id = mt_get_ticket_id();
	}
	$purchase    = get_post_meta( mt_get_ticket( $ticket_id )->ID, '_' . $ticket_id, true );
	$purchase_id = $purchase['purchase_id'];

	return $purchase_id;
}

/**
 * Get ticket's parent purchase ID
 *
 * @param bool|int $ticket_id Ticket ID.
 *
 * @return void
 */
function mt_ticket_purchase_id( $ticket_id = false ) {
	echo esc_html( mt_get_ticket_purchase_id( $ticket_id ) );
}

/**
 * Get ticket purchaser name
 *
 * @param bool|int $ticket_id Ticket ID.
 *
 * @return mixed|string
 */
function mt_get_ticket_purchaser( $ticket_id = false ) {
	if ( ! $ticket_id ) {
		$ticket_id = mt_get_ticket_id();
	}
	$purchase    = get_post_meta( mt_get_ticket( $ticket_id )->ID, '_' . $ticket_id, true );
	$purchase_id = $purchase['purchase_id'];
	$purchaser   = get_post_field( 'post_title', $purchase_id );

	return $purchaser;
}

/**
 * Get ticket purchaser name
 *
 * @param bool|int $ticket_id Ticket ID.
 *
 * @return void
 */
function mt_ticket_purchaser( $ticket_id = false ) {
	echo esc_html( mt_get_ticket_purchaser( $ticket_id ) );
}

/**
 * Get custom field data; all by default, or only a specific field. Display in tickets.
 *
 * @param bool|string $custom_field Custom Field Name.
 * @param bool|string $ticket_id Ticket ID.
 *
 * @return string
 */
function mt_get_ticket_custom_fields( $custom_field = false, $ticket_id = false ) {
	if ( ! $ticket_id ) {
		$ticket_id = mt_get_ticket_id();
	}
	$purchase    = get_post_meta( mt_get_ticket( $ticket_id )->ID, '_' . $ticket_id, true );
	$purchase_id = $purchase['purchase_id'];

	return mt_show_custom_data( $purchase_id, $custom_field );
}

/**
 * Get custom field data; all by default, or only a specific field. Display in tickets.
 *
 * @param bool|string $custom_field Custom Field Name.
 * @param bool|string $ticket_id Ticket ID.
 *
 * @return void
 */
function mt_ticket_custom_fields( $custom_field = false, $ticket_id = false ) {
	echo wp_kses_post( mt_get_ticket_custom_fields( $custom_field, $ticket_id ) );
}

/**
 * Get date of event this ticket is for.
 *
 * @param bool|string $ticket_id Ticket ID.
 *
 * @return string
 */
function mt_get_event_date( $ticket_id = false ) {
	if ( ! $ticket_id ) {
		$ticket = mt_get_ticket();
	} else {
		$ticket = mt_get_ticket( $ticket_id );
	}
	if ( $ticket ) {
		$event = get_post_meta( $ticket->ID, '_mc_event_data', true );
		$date  = isset( $event['event_begin'] ) ? $event['event_begin'] : '';
		$date  = date_i18n( get_option( 'date_format' ), strtotime( $date ) );

		return $date;
	}

	return '';
}

/**
 * Get date of event this ticket is for.
 *
 * @param bool|string $ticket_id Ticket ID.
 *
 * @return void
 */
function mt_event_date( $ticket_id = false ) {
	echo wp_kses_post( mt_get_event_date( $ticket_id ) );
}

/**
 *  Get event notes.
 *
 * @param bool|string $ticket_id Ticket ID.
 *
 * @return string
 */
function mt_get_ticket_event_notes( $ticket_id = false ) {
	if ( ! $ticket_id ) {
		$ticket = mt_get_ticket();
	} else {
		$ticket = mt_get_ticket( $ticket_id );
	}
	$notes = wpautop( get_post_meta( $ticket->ID, '_mt_event_notes', true ) );

	return $notes;
}

/**
 * Echo event notes.
 *
 * @param bool|string $ticket_id Ticket ID.
 */
function mt_ticket_event_notes( $ticket_id ) {
	echo wp_kses_post( mt_get_ticket_event_notes( $ticket_id ) );
}

/**
 * Get title of event this ticket is for.
 *
 * @param bool|string $ticket_id Ticket ID.
 *
 * @return string
 */
function mt_get_event_title( $ticket_id = false ) {
	if ( ! $ticket_id ) {
		$ticket = mt_get_ticket();
	} else {
		$ticket = mt_get_ticket( $ticket_id );
	}
	if ( $ticket ) {
		$title = apply_filters( 'the_title', apply_filters( 'mt_the_title', $ticket->post_title, $ticket ), $ticket_id );

		return $title;
	}

	return '';
}

/**
 * Get title of event this ticket is for.
 *
 * @param bool|string $ticket_id Ticket ID.
 *
 * @return void
 */
function mt_event_title( $ticket_id = false ) {
	echo wp_kses_post( mt_get_event_title( $ticket_id ) );
}

/**
 * Get time of event this ticket is for.
 *
 * @param bool|string $ticket_id Ticket ID.
 *
 * @return string
 */
function mt_get_event_time( $ticket_id = false ) {
	if ( ! $ticket_id ) {
		$ticket = mt_get_ticket();
	} else {
		$ticket = mt_get_ticket( $ticket_id );
	}
	if ( $ticket ) {
		$event = get_post_meta( $ticket->ID, '_mc_event_data', true );
		$time  = isset( $event['event_time'] ) ? $event['event_time'] : '';
		$time  = date_i18n( get_option( 'time_format' ), strtotime( $time ) );

		return $time;
	}

	return '';
}

/**
 * Get time of event this ticket is for.
 *
 * @param bool|string $ticket_id Ticket ID.
 *
 * @return void
 */
function mt_event_time( $ticket_id = false ) {
	echo esc_html( mt_get_event_time( $ticket_id ) );
}

/**
 * Get type of ticket. (Adult, child, section 1, section 2, etc.)
 *
 * @param bool|string $ticket_id Ticket ID.
 *
 * @return mixed|string
 */
function mt_get_ticket_type( $ticket_id = false ) {
	if ( ! $ticket_id ) {
		$ticket    = mt_get_ticket();
		$ticket_id = mt_get_ticket_id();
	} else {
		$ticket = mt_get_ticket( $ticket_id );
	}
	if ( $ticket ) {
		$type  = get_post_meta( $ticket->ID, '_' . $ticket_id, true );
		$type  = $type['type'];
		$label = mt_get_label( $type );

		return apply_filters( 'mt_ticket_type', $label, $type );
	}

	return '';
}

/**
 * Get type of ticket. (Adult, child, section 1, section 2, etc.)
 *
 * @param bool|string $ticket_id Ticket ID.
 *
 * @return void
 */
function mt_ticket_type( $ticket_id = false ) {
	echo esc_html( mt_get_ticket_type( $ticket_id ) );
}

/**
 * Get ticket price for ticket.
 *
 * @param bool|string $ticket_id Ticket ID.
 *
 * @return string
 */
function mt_get_ticket_price( $ticket_id = false ) {
	if ( ! $ticket_id ) {
		$ticket    = mt_get_ticket();
		$ticket_id = mt_get_ticket_id();
	} else {
		$ticket = mt_get_ticket( $ticket_id );
	}
	$append = ': <em>' . __( 'Paid', 'my-tickets' ) . '</em>';
	if ( $ticket ) {
		$data    = get_post_meta( $ticket->ID, '_' . $ticket_id, true );
		$receipt = $data['purchase_id'];
		$paid    = get_post_meta( $receipt, '_is_paid', true );
		if ( 'Completed' !== $paid ) {
			$append = ': <em>' . __( 'Payment Due', 'my-tickets' ) . '</em>';
		}
		$type = apply_filters( 'mt_money_format', $data['price'] );

		return $type . $append;
	}

	return '';
}

/**
 * Get ticket price for ticket.
 *
 * @param bool|string $ticket_id Ticket ID.
 *
 * @return void
 */
function mt_ticket_price( $ticket_id = false ) {
	echo wp_kses_post( mt_get_ticket_price( $ticket_id ) );
}

/**
 * Return image URL for printable/eticket QR codes.
 *
 * @param bool|string $ticket_id Ticket ID.
 */
function mt_get_ticket_qrcode( $ticket_id = false ) {
	$options   = array_merge( mt_default_settings(), get_option( 'mt_settings', array() ) );
	$id        = ( '' !== $options['mt_purchase_page'] && is_numeric( $options['mt_purchase_page'] ) ) ? absint( $options['mt_purchase_page'] ) : false;
	$ticket_id = ( $ticket_id ) ? $ticket_id : mt_get_ticket_id();
	$url       = esc_url_raw(
		add_query_arg(
			array(
				'ticket_id' => $ticket_id,
				'action'    => 'mt-verify',
			),
			get_permalink( $id )
		)
	);
	$qrcode    = array(
		'version'    => 9,
		'outputType' => QRCODE::OUTPUT_IMAGE_PNG,
		'eccLevel'   => QRCODE::ECC_M,
	);
	$qrcode    = apply_filters( 'mt_qrcode_options', $qrcode, $ticket_id );
	$options   = new QROptions( $qrcode );
	$code      = new QRCode( $options );
	return $code->render( esc_url_raw( $url ) );
}

/**
 * Return image URL for printable/eticket QR codes.
 *
 * @param bool|string $ticket_id Ticket ID.
 */
function mt_ticket_qrcode( $ticket_id = false ) {
	echo esc_attr( mt_get_ticket_qrcode( $ticket_id ) );
}

/**
 * Get ticket venue location data.
 *
 * @param bool|string $ticket_id Ticket ID.
 * @uses filter mt_create_location_object
 *
 * @return string
 */
function mt_get_ticket_venue( $ticket_id = false ) {
	if ( ! $ticket_id ) {
		$ticket = mt_get_ticket();
	} else {
		$ticket = mt_get_ticket( $ticket_id );
	}
	if ( $ticket ) {
		$location_id = get_post_meta( $ticket->ID, '_mc_event_location', true );
		$html        = false;
		if ( $location_id ) {
			$location = apply_filters( 'mt_create_location_object', false, $location_id );
			if ( ! $location ) {
				return '';
			} else {
				$html = mt_hcard( $location, true );
			}
		}
		$html = apply_filters( 'mt_hcard', $html, $location_id, $ticket );
		if ( $html ) {
			return $html;
		}
	}

	return '';
}

add_filter( 'mt_create_location_object', 'mt_get_mc_location', 10, 2 );
/**
 * If My Calendar installed, return My Calendar location object.
 *
 * @param Object $location Location object.
 * @param int    $location_id Location ID.
 *
 * @return mixed
 */
function mt_get_mc_location( $location, $location_id ) {
	if ( function_exists( 'mc_hcard' ) ) {
		global $wpdb;
		$location = $wpdb->get_row( $wpdb->prepare( 'SELECT * FROM ' . my_calendar_locations_table() . ' WHERE location_id = %d', $location_id ) ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
	}

	return $location;
}

// set up hCard formatted address.
/**
 * Produce HTML for My Tickets hCard.
 *
 * @param object $location Location object.
 *
 * @return string
 */
function mt_hcard( $location ) {
	$url     = trim( $location->location_url );
	$label   = trim( stripslashes( $location->location_label ) );
	$street  = trim( stripslashes( $location->location_street ) );
	$street2 = trim( stripslashes( $location->location_street2 ) );
	$city    = trim( stripslashes( $location->location_city ) );
	$state   = trim( stripslashes( $location->location_state ) );
	$zip     = trim( stripslashes( $location->location_postcode ) );
	$country = trim( stripslashes( $location->location_country ) );
	$phone   = trim( stripslashes( $location->location_phone ) );
	if ( ! $url && ! $label && ! $street && ! $street2 && ! $city && ! $state && ! $zip && ! $country && ! $phone ) {
		return '';
	}
	$link   = ( '' !== $url ) ? "<a href='$url' class='location-link external'>$label</a>" : $label;
	$hcard  = '<div class="address vcard">';
	$hcard .= '<div class="adr">';
	$hcard .= ( '' !== $label ) ? '<strong class="org">' . $link . '</strong><br />' : '';
	$hcard .= ( '' === $street . $street2 . $city . $state . $zip . $country . $phone ) ? '' : "<div class='sub-address'>";
	$hcard .= ( '' !== $street ) ? '<div class="street-address">' . $street . '</div>' : '';
	$hcard .= ( '' !== $street2 ) ? '<div class="street-address">' . $street2 . '</div>' : '';
	$hcard .= ( '' !== $city . $state . $zip ) ? '<div>' : '';
	$hcard .= ( '' !== $city ) ? '<span class="locality">' . $city . "</span><span class='sep'>, </span>" : '';
	$hcard .= ( '' !== $state ) ? '<span class="region">' . $state . '</span> ' : '';
	$hcard .= ( '' !== $zip ) ? ' <span class="postal-code">' . $zip . '</span>' : '';
	$hcard .= ( '' !== $city . $state . $zip ) ? '</div>' : '';
	$hcard .= ( '' !== $country ) ? '<div class="country-name">' . $country . '</div>' : '';
	$hcard .= ( '' !== $phone ) ? '<div class="tel">' . $phone . '</div>' : '';
	$hcard .= ( '' === $street . $street2 . $city . $state . $zip . $country . $phone ) ? '' : '</div>';
	$hcard .= '</div>';
	$hcard .= '</div>';

	return $hcard;
}

/**
 * Get ticket venue location data.
 *
 * @param bool|string $ticket_id Ticket ID.
 *
 * @return void
 */
function mt_ticket_venue( $ticket_id = false ) {
	echo wp_kses_post( mt_get_ticket_venue( $ticket_id ) );
}

/**
 * Verify that a ticket is valid, paid for, and which event it's for.
 *
 * @param bool|string $ticket_id Ticket ID.
 *
 * @return string
 */
function mt_get_verification( $ticket_id = false ) {
	$ticket_id = ( ! $ticket_id ) ? mt_get_ticket_id() : $ticket_id;
	$verified  = mt_verify_ticket( $ticket_id );
	$ticket    = mt_get_ticket( $ticket_id );
	if ( $ticket ) {
		$data        = get_post_meta( $ticket->ID, '_' . $ticket_id, true );
		$purchase_id = $data['purchase_id'];
		$status      = get_post_meta( $purchase_id, '_is_paid', true );
		$due         = get_post_meta( $purchase_id, '_total_paid', true );
		$due         = apply_filters( 'mt_money_format', $due );
		$text        = ( $verified ) ? __( 'Ticket Verified', 'my-tickets' ) : __( 'Invalid Ticket ID', 'my-tickets' );
		// Translators: Amount due on account.
		$text        .= ( 'Pending' === $status ) ? ' - ' . sprintf( __( 'Payment pending: %s', 'my-tickets' ), $due ) : '';
		$status_class = sanitize_title( $status );
		$used         = get_post_meta( $purchase_id, '_tickets_used' );
		if ( ! is_array( $used ) ) {
			$used = array();
		}
		$is_used = false;
		if ( in_array( $ticket_id, $used, true ) ) {
			$is_used       = true;
			$status_class .= ' used';
			$text         .= ' (' . __( 'Ticket has been used.', 'my-tickets' ) . ')';
		}

		$text .= wpautop( mt_get_ticket_validity( $ticket ) );
		if ( ( current_user_can( 'mt-verify-ticket' ) || current_user_can( 'manage_options' ) ) && ! $is_used ) {
			$text .= wpautop( __( 'Ticket usage recorded', 'my-tickets' ) );
			add_post_meta( $purchase_id, '_tickets_used', $ticket_id );
		}

		do_action( 'mt_ticket_verified', $verified, $is_used, $purchase_id, $ticket_id );

		return "<div class='$status_class'>" . $text . '</div>';
	}

	return '<div class="invalid">' . __( 'Not a valid ticket ID', 'my-tickets' ) . '</div>';
}

/**
 * Get the validity for a general admission ticket.
 *
 * @param int|object $ticket Ticket object or ID.
 * @param string     $format Full validity statement or expiration only.
 *
 * @return string
 */
function mt_get_ticket_validity( $ticket = false, $format = 'full' ) {
	if ( ! $ticket ) {
		$ticket = mt_get_ticket();
	}
	if ( ! is_int( $ticket ) ) {
		$ticket = $ticket->ID;
	}
	$ticket_id  = mt_get_ticket_id();
	$text       = '';
	$expires    = '';
	$event_data = get_post_meta( $ticket, '_mc_event_data', true );
	if ( $event_data ) {
		$general  = ( isset( $event_data['general_admission'] ) && ! empty( $event_data['general_admission'] ) ) ? true : false;
		$validity = ( isset( $event_data['event_valid'] ) && $general ) ? trim( $event_data['event_valid'] ) : false;
		if ( $validity ) {
			$data         = get_post_meta( $ticket, '_' . $ticket_id, true );
			$sale_id      = $data['purchase_id'];
			$format       = ( '' === get_option( 'date_format' ) ) ? 'Y-m-d' : get_option( 'date_format' );
			$format       = apply_filters( 'mt_validity_date_format', $format, $event_data );
			$date_of_sale = get_the_date( $format, $sale_id );
			$status       = mt_date( $format, strtotime( $date_of_sale . ' + ' . $validity ) );
			// Translators: Purchase date.
			$text .= wpautop( sprintf( apply_filters( 'mt_ticket_validity_sale_date', __( '<strong>Purchased:</strong> %s', 'my-tickets' ), $event_data ), '<span class="mt-date-of-sale">' . $date_of_sale . '</span>' ) );
			// Translators: Expiration date.
			$expires = wpautop( sprintf( apply_filters( 'mt_ticket_validity_expiration_date', __( '<strong>Expires:</strong> %s', 'my-tickets' ), $event_data ), '<span class="mt-date-of-validity">' . $status . '</span>' ) );
			$text   .= $expires;
			if ( strtotime( $date_of_sale . ' + ' . $validity ) < time() ) {
				$text .= '<p class="mt-expired">' . __( 'Ticket has expired', 'my-tickets' ) . '</p>';
			}
		}
	}

	return ( 'full' === $format ) ? $text : $expires;
}

/**
 * Echo ticket validity.
 *
 * @param int|object $ticket Ticket object or ID.
 * @param string     $format Full validity statement or expiration only.
 */
function mt_ticket_validity( $ticket = false, $format = 'full' ) {
	echo mt_get_ticket_validity( $ticket, $format );
}

/**
 * Verify that a ticket is valid, paid for, and which event it's for.
 *
 * @param bool|string $ticket_id Ticket ID.
 *
 * @return void
 */
function mt_verification( $ticket_id = false ) {
	echo wp_kses_post( mt_get_verification( $ticket_id ) );
}

/**
 * Fetch custom fields set up using the custom fields API
 * This function only pulls single values; if you need arrays, you'll need to write your own custom handler.
 *
 * @param bool|false  $field name of field as defined in custom code.
 * @param string      $callback name of function to call and process output.
 * @param bool|string $ticket_id Ticket ID.
 *
 * @return string
 */
function mt_get_ticket_custom_field( $field = false, $callback = false, $ticket_id = false ) {
	if ( ! $ticket_id ) {
		$ticket = mt_get_ticket();
	} else {
		$ticket = mt_get_ticket( $ticket_id );
	}
	if ( $field ) {
		$purchase    = get_post_meta( $ticket->ID, '_' . $ticket_id, true );
		$purchase_id = $purchase['purchase_id'];
		$meta        = get_post_meta( $purchase_id, $field, true );
		if ( $meta && isset( $meta[ $field ] ) ) {
			if ( $callback ) {
				return call_user_func( $callback, $meta );
			} else {
				return $meta[ $field ];
			}
		}
	}

	return '';
}

/**
 * Fetch custom fields set up using the custom fields API
 * This function only pulls single values; if you need arrays, you'll need to write your own custom handler.
 *
 * @param bool|false  $field name of field as defined in custom code.
 * @param bool|string $ticket_id Ticket ID.
 *
 * @return void
 */
function mt_ticket_custom_field( $field = false, $ticket_id = false ) {
	echo wp_kses_post( mt_get_ticket_custom_field( $field, false, $ticket_id ) );
}
