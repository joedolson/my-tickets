<?php
/*
*  Register custom fields for event registration forms
*
*/
require_once( 'includes/phpqrcode/qrlib.php' );

/**
 * Get logo for display on receipts and tickets.
 * @param array $args
 *
 * @return string
 */
function mt_get_logo( $args = array(), $post_ID = false ) {
	$options   = array_merge( mt_default_settings(), get_option( 'mt_settings' ) );
	$ticket = mt_get_ticket();
	if ( isset( $options['mt_ticket_image'] ) && $options['mt_ticket_image'] == 'event' && $ticket ) {
		// if event has post thumbnail, use that
		if ( has_post_thumbnail( $ticket->ID ) ) {
			return get_the_post_thumbnail( $ticket->ID );
		}
	}
	if ( $post_ID && has_post_thumbnail( $post_ID ) ) {
		return get_the_post_thumbnail( $post_ID );
	}
	$args = array_merge( array( 'alt' => 'My Tickets', 'class' => 'default', 'width' => '', 'height' => '' ), $args );
	$atts = '';
	foreach ( $args as $att => $value ) {
		if ( $value != '' ) {
			$atts .= ' ' . esc_attr( $att ) . '=' . '"' . esc_attr( $value ) . '"';
		}
	}
	$img = "<img src='" . plugins_url( '/images/logo.png', __FILE__ ) . "' $atts />";

	return $img;
}

function mt_logo( $args = array(), $post_ID = false ) {
	echo mt_get_logo( $args, $post_ID );
}

/* Template Functions for Receipts */
/**
 * Return formatted order data for receipt template.
 *
 * @return string
 */
function mt_get_cart_order() {
	$receipt = mt_get_receipt();
	if ( $receipt ) {
		$purchase = get_post_meta( $receipt->ID, '_purchased' );
		$data     = mt_format_purchase( $purchase, 'html' );

		return $data;
	}

	return '';
}

function mt_cart_order() {
	echo mt_get_cart_order();
}

/**
 * Get ticket information for a given purchase as data.
 *
 * @return mixed bool/array Ticket IDs.
 */
function mt_get_payment_tickets() {
    $receipt = mt_get_receipt();
    if ( $receipt ) {
        $purchase = get_post_meta(  $receipt->ID, '_purchased' );
        $id       = $receipt->ID;

        $ticket_array = array();
        foreach ( $purchase as $purch ) {
            foreach ( $purch as $event => $tickets ) {
                $purchases[ $event ] = $tickets;
                foreach ( $tickets as $type => $details ) {
                    // add ticket hash for each ticket
                    $count = $details['count'];
                    // only add tickets if count of tickets is more than 0
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
		$receipt_id = esc_attr( $_GET['receipt_id'] );

		return $receipt_id;
	}
}

function mt_receipt_id() {
	echo mt_get_receipt_id();
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

function mt_receipt_purchase_id() {
	echo mt_get_receipt_purchase_id();
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

function mt_cart_purchaser() {
	echo mt_get_cart_purchaser();
}

/**
 * Get formatted date/time of purchase.
 *
 * @return string
 */
function mt_get_cart_purchase_date() {
	$receipt = mt_get_receipt();
	if ( $receipt ) {
		$date = date_i18n( get_option( 'date_format' ) . ' @ ' . get_option( 'time_format' ), strtotime( $receipt->post_date ) );

		return $date;
	}

	return '';
}

function mt_cart_purchase_date() {
	echo mt_get_cart_purchase_date();
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
		if ( $paid == 'Completed' ) {
			$gateway       = get_post_meta( $receipt->ID, '_gateway', true );
			$gateways      = mt_setup_gateways();
			$gateway_label = isset( $gateways[ $gateway ] ) ? $gateways[ $gateway ]['label'] : $gateway;
			$transaction   = get_post_meta( $receipt->ID, '_transaction_id', true );
			$return        = __( "This receipt is paid in full.", 'my-tickets' );
			$return .= "
		<ul>
			<li>" . __( 'Payment through:', 'my-tickets' ) . " $gateway_label</li>
			<li>" . __( 'Transaction ID:', 'my-tickets' ) . " <code>$transaction</code></li>
		</ul>";

			return $return;
		} else if ( $paid == 'Refunded' ) {
			return __( 'This payment has been refunded.', 'my-tickets' );
		} else if ( $paid == 'Failed' ) {
			return __( 'Payment on this order failed.', 'my-tickets' );
		} else if ( $paid == 'Turned Back' ) {
		    return __( 'This purchase was cancelled and the tickets were returned to the seller.', 'my-tickets' );
        } else {
                return __( 'Payment on this purchase is not completed. The receipt will be updated with payment details when payment is completed.', 'my-tickets' );
        }
	}

	return '';
}

function mt_payment_details() {
	echo mt_get_payment_details();
}

/**
 * Get ticket ID (must be used in ticket template.)
 *
 * @return string
 */
function mt_get_ticket_id() {
	$ticket_id = esc_attr( $_GET['ticket_id'] );

	return $ticket_id;
}

function mt_ticket_id() {
	echo mt_get_ticket_id();
}

/**
 * Get ticket method (willcall, postal, eticket, printable)
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

function mt_ticket_method( $ticket_id = false ) {
	echo mt_get_ticket_method( $ticket_id );
}

/**
 * Get ticket's parent purchase ID
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

function mt_ticket_purchase_id( $ticket_id = false ) {
	echo mt_get_ticket_purchase_id( $ticket_id );
}

/**
 * Get ticket purchaser name
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

function mt_ticket_purchaser( $ticket_id = false ) {
	echo mt_get_ticket_purchaser( $ticket_id );
}


/**
 * Get custom field data; all by default, or only a specific field. Display in tickets.
 *
 * @param bool $custom_field
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

function mt_ticket_custom_fields( $custom_field = false, $ticket_id = false ) {
	echo mt_get_ticket_custom_fields( $custom_field, $ticket_id );
}

/**
 * Get date of event this ticket is for.
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

function mt_event_date( $ticket_id = false ) {
	echo mt_get_event_date( $ticket_id );
}

/**
 * Get title of event this ticket is for.
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

function mt_event_title( $ticket_id = false ) {
	echo mt_get_event_title( $ticket_id );
}

/**
 * Get time of event this ticket is for.
 *
 * @return string
 */
function mt_get_event_time( $ticket_id = false ) {
    if ( !$ticket_id ) {
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

function mt_event_time( $ticket_id = false ) {
	echo mt_get_event_time( $ticket_id );
}

/**
 * Get type of ticket. (Adult, child, section 1, section 2, etc.)
 *
 * @return mixed|string
 */
function mt_get_ticket_type( $ticket_id = false ) {
    if ( !$ticket_id ) {
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

function mt_ticket_type( $ticket_id = false ) {
	echo mt_get_ticket_type( $ticket_id );
}

/**
 * Get ticket price for ticket.
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
		if ( $paid != 'Completed' ) {
			$append = ': <em>' . __( 'Payment Due', 'my-tickets' ) . '</em>';
		}
		$type = apply_filters( 'mt_money_format', $data['price'] );

		return $type . $append;
	}

	return '';
}

function mt_ticket_price( $ticket_id = false ) {
	echo mt_get_ticket_price( $ticket_id );
}

// no getter for qrcodes; produces an image directly.
/**
 * Return image URL for printable/eticket QR codes.
 */
function mt_ticket_qrcode( $ticket_id = false ) {
	$text = ( $ticket_id ) ? $ticket_id : mt_get_ticket_id();
	echo esc_url( plugins_url( "my-tickets/includes/qrcode.php?mt=$text" ) );
}

/**
 * Get ticket venue location data.
 *
 * @uses filter mt_create_location_object
 *
 * @return string
 */
function mt_get_ticket_venue( $ticket_id = false ) {
    if ( !$ticket_id ) {
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
 * @param $location
 * @param $location_id
 *
 * @return mixed
 */
function mt_get_mc_location( $location, $location_id ) {
	if ( function_exists( 'mc_hcard' ) ) {
		global $wpdb;
		$location = $wpdb->get_row( $wpdb->prepare( 'SELECT * FROM ' .  my_calendar_locations_table() . ' WHERE location_id = %d', $location_id ) );
	}

	return $location;
}

// set up hCard formatted address.
/**
 * Produce HTML for My Tickets hCard.
 *
 * @param $location
 *
 * @return string
 */
function mt_hcard( $location ) {
	$url     = $location->location_url;
	$label   = stripslashes( $location->location_label );
	$street  = stripslashes( $location->location_street );
	$street2 = stripslashes( $location->location_street2 );
	$city    = stripslashes( $location->location_city );
	$state   = stripslashes( $location->location_state );
	$zip     = stripslashes( $location->location_postcode );
	$country = stripslashes( $location->location_country );
	$phone   = stripslashes( $location->location_phone );
	if ( ! $url && ! $label && ! $street && ! $street2 && ! $city && ! $state && ! $zip && ! $country && ! $phone ) {
		return '';
	}
	$link  = ( $url != '' ) ? "<a href='$url' class='location-link external'>$label</a>" : $label;
	$hcard = "<div class=\"address vcard\">";
	$hcard .= "<div class=\"adr\">";
	$hcard .= ( $label != '' ) ? "<strong class=\"org\">" . $link . "</strong><br />" : '';
	$hcard .= ( $street . $street2 . $city . $state . $zip . $country . $phone == '' ) ? '' : "<div class='sub-address'>";
	$hcard .= ( $street != "" ) ? "<div class=\"street-address\">" . $street . "</div>" : '';
	$hcard .= ( $street2 != "" ) ? "<div class=\"street-address\">" . $street2 . "</div>" : '';
	$hcard .= ( $city . $state . $zip != '' ) ? "<div>" : '';
	$hcard .= ( $city != "" ) ? "<span class=\"locality\">" . $city . "</span><span class='sep'>, </span>" : '';
	$hcard .= ( $state != "" ) ? "<span class=\"region\">" . $state . "</span> " : '';
	$hcard .= ( $zip != "" ) ? " <span class=\"postal-code\">" . $zip . "</span>" : '';
	$hcard .= ( $city . $state . $zip != '' ) ? "</div>" : '';
	$hcard .= ( $country != "" ) ? "<div class=\"country-name\">" . $country . "</div>" : '';
	$hcard .= ( $phone != "" ) ? "<div class=\"tel\">" . $phone . "</div>" : '';
	$hcard .= ( $street . $street2 . $city . $state . $zip . $country . $phone == '' ) ? '' : "</div>";
	$hcard .= "</div>";
	$hcard .= "</div>";

	return $hcard;
}

function mt_ticket_venue( $ticket_id = false ) {
	echo mt_get_ticket_venue( $ticket_id );
}

// verification
/**
 * Verify that a ticket is valid, paid for, and which event it's for.
 *
 * @return string
 */
function mt_get_verification( $ticket_id = false ) {
	$ticket_id = ( ! $ticket_id ) ? mt_get_ticket_id() : $ticket_id;
	$verified  = mt_verify_ticket( $ticket_id );
	$ticket    = mt_get_ticket( $ticket_id );
	if ( $ticket ) {
		$data         = get_post_meta( $ticket->ID, '_'.$ticket_id, true );
		$purchase_id  = $data['purchase_id'];
		$status       = get_post_meta( $purchase_id, '_is_paid', true );
		$due          = get_post_meta( $purchase_id, '_total_paid', true );
		$due          = apply_filters( 'mt_money_format', $due );
		$text         = ( $verified ) ? __( 'Ticket Verified', 'my-tickets' ) : __( 'Invalid Ticket ID', 'my-tickets' );
		$text        .= ( $status == 'Pending' ) ? ' - ' . sprintf( __( 'Payment pending: %s', 'my-tickets' ), $due ) : '';
		$status_class = sanitize_title( $status );
		$used         = get_post_meta( $purchase_id, '_tickets_used' );
		if ( !is_array( $used ) ) { $used = array(); }
		$is_used = false;
		if ( in_array( $ticket_id, $used ) ) {
			$is_used       = true;
			$status_class .= ' used';
			$text         .= ' (' . __( "Ticket has been used.", 'my-tickets' ) . ')';
		}

		if ( ( current_user_can( 'mt-verify-ticket' ) || current_user_can( 'manage_options' ) ) && ! $is_used ) {
			add_post_meta( $purchase_id, '_tickets_used', $ticket_id );
		}

		do_action( 'mt_ticket_verified', $verified, $is_used, $purchase_id, $ticket_id );


		return "<div class='$status_class'>" . $text . "</div>";
	}

	return '<div class="invalid">' . __( 'Not a valid ticket ID', 'my-tickets' ) . '</div>';
}

function mt_verification( $ticket_id = false ) {
	echo mt_get_verification( $ticket_id );
}

/**
 * Fetch custom fields set up using the custom fields API
 * This function only pulls single values; if you need arrays, you'll need to write your own custom handler.
 *
 * @param bool|false $field name of field as defined in custom code
 * @param string $callback name of function to call and process output.
 *
 * @return string
 *
 */
function mt_get_ticket_custom_field( $field = false, $callback = false, $ticket_id = false ) {
    if ( !$ticket_id ) {
        $ticket = mt_get_ticket();
    } else {
        $ticket = mt_get_ticket( $ticket_id );
    }
	if ( $field ) {
		$purchase    = get_post_meta( $ticket->ID, '_' . $ticket_id, true );
		$purchase_id = $purchase['purchase_id'];
		$meta   = get_post_meta( $purchase_id, $field, true );

		if ( $meta && isset( $meta[$field] ) ) {
			if ( $callback ) {
				return call_user_func( $callback, $meta );
			} else {
				return wp_kses_post( $meta[$field] );
			}
		}
	}

	return '';
}

function mt_ticket_custom_field( $field = false, $ticket_id = false ) {
	echo mt_get_ticket_custom_field( $field, false, $ticket_id );
}