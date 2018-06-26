<?php

add_action( 'init', 'mt_handle_cart' );
/**
 * Handle cart submission. Receive data, create payment, delete cart if applicable, register message.
 */
function mt_handle_cart() {
	$options = array_merge( mt_default_settings(), get_option( 'mt_settings' ) );
	if ( ! isset( $_POST['mt_submit'] ) ) {
		return;
	} else {
		$nonce = $_POST['_wpnonce'];
		if ( ! wp_verify_nonce( $nonce, 'mt_cart_nonce' ) ) {
			die;
		}
		// add filter here to handle required custom fields in cart TODO

		if ( !isset( $_POST['mt_fname'] ) || $_POST['mt_fname'] == '' || !isset( $_POST['mt_lname'] ) || $_POST['mt_lname'] == '' || !isset( $_POST['mt_email'] ) || $_POST['mt_email'] == '' || !isset( $_POST['mt_email2'] ) || $_POST['mt_email'] != $_POST['mt_email2'] ) {
			$url = add_query_arg( 'response_code', 'required-fields', get_permalink( $options['mt_purchase_page'] ) );
			wp_safe_redirect( $url );
			die;
		}
		$payment = mt_create_payment( $_POST );
		if ( $payment ) {
			// Handle custom fields added to cart form
			do_action( 'mt_handle_custom_cart_data', $payment, $_POST );
			if ( isset( $_POST['mt_gateway'] ) && $_POST['mt_gateway'] == 'offline' && ( !isset( $_POST['ticketing_method'] ) || $_POST['ticketing_method'] != 'postal' ) && !mt_always_collect_shipping() ) {
				// if this is offline payment with no shipping info collected, we're done.
				mt_register_message( 'payment_due', 'success', $payment );
				mt_delete_data( 'cart' );
				mt_delete_data( 'payment' );
			} else {
				if ( !( mt_get_data( 'payment' ) ) ) {
					mt_save_data( $payment, 'payment' );
				}
			}
		} else {
			mt_register_message( 'payment', 'error' );
		}
	}
}

/**
 * Abstract function to delete data. Defaults to delete user's shopping cart.
 *
 * @param string $data
 */
function mt_delete_data( $data = 'cart' ) {
	$unique_id = ( isset( $_COOKIE['mt_unique_id'] ) ) ? $_COOKIE['mt_unique_id'] : false;
	if ( is_user_logged_in() ) {
		$current_user = wp_get_current_user();
		delete_user_meta( $current_user->ID, "_mt_user_$data" );
	} else {
		if ( $unique_id ) {
			delete_transient( 'mt_' . $unique_id . '_' . $data );
		}
	}
}

/**
 *  Verify payment status. If a payment is completed, do not re-use that payment record.
 *
 * @param integer $payment
 * @return boolean
 */
function mt_is_payment_completed( $payment ) {
	$payment_status = get_post_meta( $payment, '_is_paid', true );
	if ( $payment_status == 'Completed' ) {
		return true;
	}
	return false;
}

/**
 * Generates new payment from POSTed data.
 *
 * @param array $post
*/
function mt_create_payment( $post ) {
	$options = array_merge( mt_default_settings(), get_option( 'mt_settings' ) );
	// save payment post
	$current_user = wp_get_current_user();
	$purchaser    = ( is_user_logged_in() ) ? $current_user->ID : 1;
	$payment      = mt_get_data( 'payment' );
	if ( $payment && !mt_is_payment_completed( $payment ) ) {
		$purchase_id = mt_get_data( 'payment' );
		$status      = 'draft';
	} else {
		mt_delete_data( 'payment' );
		$status      = 'draft';
		$date        = date( 'Y-m-d H:i:00', current_time( 'timestamp' ) );
		$post_title  = $post['mt_fname'] . ' ' . $post['mt_lname'];
		$my_post     = array(
			'post_title'   => $post_title,
			'post_content' => json_encode( $post ),
			'post_status'  => 'draft',
			'post_author'  => $purchaser,
			'post_date'    => $date,
			'post_type'    => 'mt-payments',
		);
		$purchase_id = wp_insert_post( $my_post );
	}
	update_post_meta( $purchase_id, '_first_name', $post['mt_fname'] );
	update_post_meta( $purchase_id, '_last_name', $post['mt_lname'] );
	if ( isset( $options[ 'mt_ticket_handling' ] ) && is_numeric( $options[ 'mt_ticket_handling' ] ) ) {
		update_post_meta( $purchase_id, '_ticket_handling', $options['mt_ticket_handling'] );
	}
	$email = $post['mt_email'];
	update_post_meta( $purchase_id, '_email', $email );
	$phone = ( isset( $post['mt_phone']) ) ? $post['mt_phone'] : '';
	update_post_meta( $purchase_id, '_phone', $phone );
	if ( is_user_logged_in() ) {
		update_user_meta( $purchaser, 'mt_phone', $phone );
	}

	$purchased = ( isset( $post['mt_cart_order'] ) ) ? $post['mt_cart_order'] : false;
	$paid      = mt_calculate_cart_cost( $purchased );
	if ( isset( $options['mt_handling'] ) ) {
		$paid = $paid + $options['mt_handling'];
	}
	if ( isset( $options['mt_shipping'] ) && $post['ticketing_method'] == 'postal' ) {
	    $paid = $paid + $options['mt_shipping'];
    }
	update_post_meta( $purchase_id, '_total_paid', $paid );
	$payment_status = ( $paid == 0 ) ? 'Completed' : 'Pending';
	update_post_meta( $purchase_id, '_is_paid', $payment_status );
	if ( is_user_logged_in() && ! is_admin() && $options['mt_members_discount'] != '' ) {
		update_post_meta( $purchase_id, '_discount', $options['mt_members_discount'] );
	}
	update_post_meta( $purchase_id, '_gateway', $post['mt_gateway'] );
	update_post_meta( $purchase_id, '_purchase_data', $purchased );
	update_post_meta( $purchase_id, '_ticketing_method', $post['ticketing_method'] );
	if ( $post['ticketing_method'] == 'printable' || $post['ticketing_method'] == 'eticket' ) {
		update_post_meta( $purchase_id, '_is_delivered', 'true' );
	}
	// for pushing data into custom fields
	do_action( 'mt_save_payment_fields', $purchase_id, $post, $purchased );
	if ( $purchase_id ) {
		wp_update_post( array( 'ID' => $purchase_id, 'post_name' => 'mt_payment_' . $purchase_id ) );
	}
	$receipt_id = md5( add_query_arg( array( 'post_type' => 'mt-payments', 'p' => $purchase_id ), home_url() ) );
	update_post_meta( $purchase_id, '_receipt', $receipt_id );
	if ( $status == 'publish' ) {
		wp_update_post( array( 'ID' => $purchase_id, 'post_status' => $status ) );
	}

	return $purchase_id;
}

/**
 * Generates tickets for purchase.
 *
 * @param $purchase_id
 * @param bool $purchased
 */
function mt_create_tickets( $purchase_id, $purchased = false, $resending = false ) {
	$purchased = ( $purchased ) ? $purchased : get_post_meta( $purchase_id, '_purchase_data', true );
	if ( !is_array( $purchased ) ) {
		return;
	}
	foreach ( $purchased as $event_id => $purchase ) {
		/*
		 * This block of code caused problems when multiple events sold. I believe it is a redundant check, and can be eliminated.
			$_purchased = get_post_meta( $purchase_id, '_purchased', true );
			$_purchase  = get_post_meta( $event_id, '_purchase', true );
			$_receipt   = get_post_meta( $event_id, '_receipt', true );
			if ( $_purchased && $_purchase && $_receipt ) {
				// these tickets have already been generated
				return;
			}
		*/
		$registration = get_post_meta( $event_id, '_mt_registration_options', true );
		add_post_meta( $purchase_id, '_purchased', array( $event_id => $purchase ) );
		add_post_meta( $event_id, '_purchase', array( $purchase_id => $purchase ) );
		add_post_meta( $event_id, '_receipt', $purchase_id );
		foreach ( $purchase as $type => $ticket ) {
			// add ticket hash for each ticket.
			$count                                   = $ticket['count'];
			$price                                   = $ticket['price'];
			$sold                                    = $registration['prices'][ $type ]['sold'];
			$new_sold                                = $sold + $count;
			$registration['prices'][ $type ]['sold'] = $new_sold;
			if ( !$resending ) {
				update_post_meta( $event_id, '_mt_registration_options', $registration );
			}
			for ( $i = 0; $i < $count; $i ++ ) {
				$ticket_id = mt_generate_ticket_id( $purchase_id, $event_id, $type, $i, $price );
				if ( !$resending ) {
					add_post_meta( $event_id, '_ticket', $ticket_id );
					update_post_meta( $event_id, '_' . $ticket_id, array(
						'type'        => $type,
						'price'       => $price,
						'purchase_id' => $purchase_id
					) );
				}
			}
		}
	}
}

/**
 * Generates the ticket ID from purchase ID, ticket type, number of ticket purchased of that time, and price.
 *
 * @param $purchase_id
 * @param $type
 * @param $i
 * @param $price
 *
 * @return string
 */
function mt_generate_ticket_id( $purchase_id, $event_id, $type, $i, $price ) {
	// hash data
	$hash = md5( $purchase_id . $type . $i . $price . $event_id );
	// reduce to 13 chars
	$hash = substr( $hash, 0,12 );
	// seed with $type substring & ticket type ID
	$hash = substr( $type, 0, 2 ) . $hash . zeroise( $i, 4 );

	$args = array(
	    'purchase_id' => $purchase_id,
        'event_id'    => $event_id,
        'type'        => $type,
        'i'           => $i,
        'price'       => $price
    );

	return apply_filters( 'mt_generate_ticket_id', $hash, $args );
}

/**
 * Calculates cost of cart. (Actual cost, after discounts.)
 *
 * @param $purchased
 *
 * @return float
 */
function mt_calculate_cart_cost( $purchased ) {
	$total = 0;
	if ( $purchased ) {
		foreach ( $purchased as $event_id => $tickets ) {
			$prices = mt_get_prices( $event_id );
			if ( $prices ) {
				foreach ( $tickets as $type => $ticket ) {
					if ( (int) $ticket['count'] > 0 ) {
						$price = ( isset( $prices[ $type ] ) ) ? $prices[ $type ]['price'] : '';
						if ( $price ) {
							$price = mt_handling_price( $price, $event_id );
						}
						$total = $total + ( $price * $ticket['count'] );
					}
				}
			}
		}
	}
	$total = apply_filters( 'mt_apply_discounts', $total, $purchased );

	return round( $total, 2 );
}

/**
 * Compares price paid by customer to expected price of cart.
 *
 * @param $price
 * @param $payment
 *
 * @return boolean False if not a match
 */
function mt_check_payment_amount( $price, $purchase_id ) {
	$total_paid = abs( get_post_meta( $purchase_id, '_total_paid', true ) );
	$donation   = abs( get_post_meta( $purchase_id, '_donation', true ) );
	if ( $price == $total_paid + $donation ) {
		return true;
	}

	return false;
}