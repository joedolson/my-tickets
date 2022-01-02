<?php
/**
 * Cart handling.
 *
 * @category Core
 * @package  My Tickets
 * @author   Joe Dolson
 * @license  GPLv2 or later
 * @link     https://www.joedolson.com/my-tickets/
 */

add_action( 'init', 'mt_handle_cart' );
/**
 * Handle cart submission. Receive data, create payment, delete cart if applicable, register message.
 */
function mt_handle_cart() {
	$options = array_merge( mt_default_settings(), get_option( 'mt_settings', array() ) );
	if ( ! isset( $_POST['mt_submit'] ) ) {
		return;
	} else {
		$nonce = $_POST['_wpnonce'];
		if ( ! wp_verify_nonce( $nonce, 'mt_cart_nonce' ) ) {
			die;
		}
		// add filter here to handle required custom fields in cart TODO.
		$email_valid = ( isset( $_POST['mt_email'] ) ) ? is_email( $_POST['mt_email'] ) : false;
		if ( ! $email_valid || ! isset( $_POST['mt_fname'] ) || '' === $_POST['mt_fname'] || ! isset( $_POST['mt_lname'] ) || '' === $_POST['mt_lname'] || ! isset( $_POST['mt_email'] ) || '' === $_POST['mt_email'] || ! isset( $_POST['mt_email2'] ) || $_POST['mt_email'] !== $_POST['mt_email2'] ) {
			$url = add_query_arg( 'response_code', 'required-fields', get_permalink( $options['mt_purchase_page'] ) );
			wp_safe_redirect( $url );
			exit;
		}
		$post    = map_deep( $_POST, 'sanitize_text_field' );
		$payment = mt_create_payment( $post );
		if ( $payment ) {
			// Handle custom fields added to cart form.
			do_action( 'mt_handle_custom_cart_data', $payment, $post );
			if ( ! ( mt_get_data( 'payment' ) ) ) {
				mt_debug( print_r( $post, 1 ), 'Data passed from client at payment creation', $payment );
				mt_save_data( $payment, 'payment' );
			}
		} else {
			mt_register_message( 'payment', 'error' );
		}
	}
}

/**
 * Abstract function to delete data. Defaults to delete user's shopping cart.
 *
 * @param string $data Type of data to delete.
 */
function mt_delete_data( $data = 'cart' ) {
	$unique_id = ( isset( $_COOKIE['mt_unique_id'] ) ) ? sanitize_text_field( $_COOKIE['mt_unique_id'] ) : false;
	if ( is_user_logged_in() ) {
		$current_user = wp_get_current_user();
		delete_user_meta( $current_user->ID, "_mt_user_$data" );
	}
	if ( $unique_id ) {
		delete_transient( 'mt_' . $unique_id . '_' . $data );
	}
}

/**
 *  Verify payment status. If a payment is completed, do not re-use that payment record.
 *
 * @param integer $payment Payment ID.
 *
 * @return boolean
 */
function mt_is_payment_completed( $payment ) {
	$payment_status = get_post_meta( $payment, '_is_paid', true );
	if ( 'Completed' === $payment_status ) {
		return true;
	}
	return false;
}

/**
 * Generates new payment from POSTed data.
 *
 * @param array $post POST data.
 *
 * @return array|int|mixed|WP_Error
 */
function mt_create_payment( $post ) {
	$options = array_merge( mt_default_settings(), get_option( 'mt_settings', array() ) );
	// save payment post.
	$current_user = wp_get_current_user();
	$purchaser    = ( is_user_logged_in() ) ? $current_user->ID : 1;
	$payment      = mt_get_data( 'payment' );
	if ( ! is_string( get_post_status( $payment ) ) || 'trash' === get_post_status( $payment ) ) {
		$payment = false;
	}
	if ( $payment && ! mt_is_payment_completed( $payment ) ) {
		$purchase_id = mt_get_data( 'payment' );
		$status      = 'draft';
	} else {
		mt_delete_data( 'payment' );
		$status      = 'draft';
		$date        = mt_date( 'Y-m-d H:i:00', mt_current_time(), false );
		$post_title  = sanitize_text_field( $post['mt_fname'] . ' ' . $post['mt_lname'] );
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
	do_action( 'mt_after_insert_payment', $purchase_id, $post );
	update_post_meta( $purchase_id, '_first_name', sanitize_text_field( $post['mt_fname'] ) );
	update_post_meta( $purchase_id, '_last_name', sanitize_text_field( $post['mt_lname'] ) );
	if ( isset( $options['mt_ticket_handling'] ) && is_numeric( $options['mt_ticket_handling'] ) ) {
		update_post_meta( $purchase_id, '_ticket_handling', $options['mt_ticket_handling'] );
	}
	$email = $post['mt_email'];
	update_post_meta( $purchase_id, '_email', sanitize_email( $email ) );
	$phone = ( isset( $post['mt_phone'] ) ) ? $post['mt_phone'] : '';
	update_post_meta( $purchase_id, '_phone', sanitize_text_field( $phone ) );
	if ( is_user_logged_in() ) {
		update_user_meta( $purchaser, 'mt_phone', sanitize_text_field( $phone ) );
	}
	$vat = ( isset( $post['mt_vat'] ) ) ? $post['mt_vat'] : '';
	update_post_meta( $purchase_id, '_vat', sanitize_text_field( $vat ) );
	if ( is_user_logged_in() ) {
		update_user_meta( $purchaser, 'mt_vat', sanitize_text_field( $vat ) );
	}
	$purchased = ( isset( $post['mt_cart_order'] ) ) ? $post['mt_cart_order'] : false;
	$paid      = mt_calculate_cart_cost( $purchased, $purchase_id );
	if ( isset( $options['mt_handling'] ) && is_numeric( $options['mt_handling'] ) ) {
		$paid = $paid + $options['mt_handling'];
		update_post_meta( $purchase_id, '_mt_handling', $options['mt_handling'] );
	}
	if ( isset( $options['mt_shipping'] ) && 'postal' === $post['ticketing_method'] ) {
		$paid = $paid + $options['mt_shipping'];
		update_post_meta( $purchase_id, '_mt_shipping', $options['mt_shipping'] );
	}
	update_post_meta( $purchase_id, '_total_paid', $paid );
	$payment_status = ( 0 === (int) $paid ) ? 'Completed' : 'Pending';
	update_post_meta( $purchase_id, '_is_paid', $payment_status );
	if ( is_user_logged_in() && ! is_admin() && '' !== trim( $options['mt_members_discount'] ) ) {
		update_post_meta( $purchase_id, '_discount', $options['mt_members_discount'] );
	}
	update_post_meta( $purchase_id, '_gateway', sanitize_text_field( $post['mt_gateway'] ) );
	update_post_meta( $purchase_id, '_purchase_data', $purchased );
	// Debugging.
	mt_debug( print_r( $purchased, 1 ), 'Purchase Data saved at nav to payment screen', $purchase_id );
	mt_debug( print_r( $post, 1 ), 'Data passed from client at nav to payment screen', $purchase_id );
	update_post_meta( $purchase_id, '_ticketing_method', $post['ticketing_method'] );
	// for pushing data into custom fields.
	do_action( 'mt_save_payment_fields', $purchase_id, $post, $purchased );
	if ( $purchase_id ) {
		wp_update_post(
			array(
				'ID'        => $purchase_id,
				'post_name' => 'mt_payment_' . $purchase_id,
			)
		);
	}
	$receipt_id = md5(
		add_query_arg(
			array(
				'post_type' => 'mt-payments',
				'p'         => $purchase_id,
			),
			home_url()
		)
	);
	update_post_meta( $purchase_id, '_receipt', $receipt_id );
	if ( 'publish' === $status ) {
		wp_update_post(
			array(
				'ID'          => $purchase_id,
				'post_status' => $status,
			)
		);
	}

	return $purchase_id;
}

/**
 * Generates tickets for purchase.
 *
 * @param integer $purchase_id Payment ID.
 * @param bool    $purchased Is this already sold.
 * @param bool    $resending We're resending a notice right now.
 *
 * @return null
 */
function mt_create_tickets( $purchase_id, $purchased = false, $resending = false ) {
	$purchased = ( $purchased ) ? $purchased : get_post_meta( $purchase_id, '_purchase_data', true );
	if ( ! is_array( $purchased ) || mt_purchase_has_tickets( $purchase_id ) ) {
		return;
	}
	$ids = array();
	foreach ( $purchased as $event_id => $purchase ) {
		// It's possible for an event ID to appear in this list twice. If so, ignore the repetitions; they're duplicates.
		if ( in_array( $event_id, $ids, true ) ) {
			continue;
		}
		$registration = get_post_meta( $event_id, '_mt_registration_options', true );
		$created      = false;
		$ids[]        = $event_id;
		add_post_meta( $purchase_id, '_purchased', array( $event_id => $purchase ) );
		add_post_meta( $event_id, '_purchase', array( $purchase_id => $purchase ) );
		foreach ( $purchase as $type => $ticket ) {
			// add ticket hash for each ticket.
			$count                                   = $ticket['count'];
			$price                                   = $ticket['price'];
			$sold                                    = absint( $registration['prices'][ $type ]['sold'] );
			$new_sold                                = $sold + $count;
			$registration['prices'][ $type ]['sold'] = $new_sold;
			for ( $i = 0; $i < $count; $i ++ ) {
				$ticket_id = mt_generate_ticket_id( $purchase_id, $event_id, $type, $i, $price );
				if ( ! $resending && ! mt_ticket_exists( $purchase_id, $ticket_id ) ) {
					$created = true;
					add_post_meta( $event_id, '_ticket', $ticket_id );
					update_post_meta(
						$event_id,
						'_' . $ticket_id,
						array(
							'type'        => $type,
							'price'       => $price,
							'purchase_id' => $purchase_id,
						)
					);
				}
			}
			if ( ! $resending && $created ) {
				update_post_meta( $event_id, '_mt_registration_options', $registration );
			}
		}
	}
}

/**
 * Check whether this purchase has already had tickets created.
 *
 * @param int $purchase_id Payment ID.
 *
 * @return boolean
 */
function mt_purchase_has_tickets( $purchase_id ) {
	// This crudely checks whether the _purchased data point is created, but doesn't check the entire list of tickets.
	$tickets = get_post_meta( $purchase_id, '_purchased', true );
	if ( is_array( $tickets ) && ! empty( $tickets ) ) {
		return true;
	}

	return false;
}

/**
 * Check whether this ticket ID already exists.
 *
 * @param int    $purchase_id Payment ID.
 * @param string $ticket_id Ticket ID string.
 *
 * @return boolean
 */
function mt_ticket_exists( $purchase_id, $ticket_id ) {
	global $wpdb;
	$value = $wpdb->get_var( $wpdb->prepare( "SELECT meta_key FROM $wpdb->postmeta WHERE post_id = %d AND meta_value = %s", $purchase_id, $ticket_id ) );

	return ( $value ) ? true : false;
}

/**
 * Check whether this ticket ID exists in the event. (Handles tickets that have been removed from purchase pool.)
 *
 * @param int    $event_id Event ID.
 * @param string $ticket_id Ticket ID string.
 *
 * @return boolean
 */
function mt_ticket_exists_on_event( $event_id, $ticket_id ) {
	global $wpdb;
	$value = $wpdb->get_var( $wpdb->prepare( "SELECT meta_key FROM $wpdb->postmeta WHERE post_id = %d AND meta_value = %s", $event_id, $ticket_id ) );

	return ( $value ) ? true : false;
}

/**
 * Generates the ticket ID from purchase ID, ticket type, number of ticket purchased of that time, and price.
 *
 * @param integer $purchase_id Payment ID.
 * @param integer $event_id Event ID for this ticket.
 * @param string  $type Type of ticket purchased.
 * @param integer $i Count; how many of this type have been purchased in this payment.
 * @param float   $price Price for this ticket.
 *
 * @return string
 */
function mt_generate_ticket_id( $purchase_id, $event_id, $type, $i, $price ) {
	// hash data.
	$hash = md5( $purchase_id . $type . $i . $price . $event_id );
	// reduce to 13 chars.
	$hash = substr( $hash, 0, 12 );
	// seed with $type substring & ticket type ID.
	$hash = substr( $type, 0, 2 ) . $hash . zeroise( $i, 4 );

	$args = array(
		'purchase_id' => $purchase_id,
		'event_id'    => $event_id,
		'type'        => $type,
		'i'           => $i,
		'price'       => $price,
	);
	mt_generate_sequential_id( $hash, $args );

	return apply_filters( 'mt_generate_ticket_id', $hash, $args );
}

/**
 * Generate a sequential ID for each ticket.
 *
 * @param string $hash Hash ID for ticket.
 * @param array  $args Array of ticket ID generation information.
 *
 * @return int sequential ID
 */
function mt_generate_sequential_id( $hash, $args ) {
	$sequential_base = get_post_meta( $args['event_id'], '_sequential_base', true );
	$sequential_id   = ( $sequential_base ) ? $sequential_base + 1 : 1;
	if ( ! get_post_meta( $args['event_id'], '_' . $hash . '_seq_id', true ) ) {
		update_post_meta( $args['event_id'], '_' . $hash . '_seq_id', $sequential_id );
		update_post_meta( $args['event_id'], '_sequential_base', $sequential_id );
	}

	return $sequential_id;
}

/**
 * Calculates cost of cart. (Actual cost, after discounts.)
 *
 * @param array $purchased Tickets purchased.
 * @param int   $payment_id Payment ID. Use for calculating discounts.
 *
 * @return float
 */
function mt_calculate_cart_cost( $purchased, $payment_id ) {
	$total = 0;
	if ( $purchased ) {
		foreach ( $purchased as $event_id => $tickets ) {
			$prices = mt_get_prices( $event_id, $payment_id );
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
	$total = apply_filters( 'mt_apply_discounts', $total, $purchased, $payment_id );

	return round( $total, 2 );
}

/**
 * Compares price paid by customer to expected price of cart.
 *
 * @param float $price Total amount paid.
 * @param int   $purchase_id Payment ID to compare against.
 *
 * @return float Checked value
 */
function mt_check_payment_amount( $price, $purchase_id ) {
	$total_paid = get_post_meta( $purchase_id, '_total_paid', true );
	$donation   = get_post_meta( $purchase_id, '_donation', true );
	$total      = (float) ( (float) $total_paid + (float) $donation );

	return $total;
}

/**
 * Execute a refresh of the My Tickets primary URL caches if caching plug-in installed.
 */
function mt_refresh_cache() {
	$options    = array_merge( mt_default_settings(), get_option( 'mt_settings', array() ) );
	$receipts   = $options['mt_receipt_page'];
	$tickets    = $options['mt_tickets_page'];
	$purchase   = $options['mt_purchase_page'];
	$to_refresh = apply_filters( 'mt_cached_pages_to_refresh', array( $receipts, $tickets, $purchase ) );

	foreach ( $to_refresh as $post ) {
		if ( ! $post || ! get_post( $post ) ) {
			continue;
		}
		// W3 Total Cache.
		if ( function_exists( 'w3tc_pgcache_flush_post' ) ) {
			w3tc_pgcache_flush_post( $post );
		}

		// WP Super Cache.
		if ( function_exists( 'wp_cache_post_change' ) ) {
			wp_cache_post_change( $post );
		}

		// WP Rocket.
		if ( function_exists( 'rocket_clean_post' ) ) {
			rocket_clean_post( $post );
		}

		// WP Fastest Cache.
		if ( isset( $GLOBALS['wp_fastest_cache'] ) && method_exists( $GLOBALS['wp_fastest_cache'], 'singleDeleteCache' ) ) {
			$GLOBALS['wp_fastest_cache']->singleDeleteCache( false, $post );
		}

		// Comet Cache.
		if ( class_exists( 'comet_cache' ) ) {
			comet_cache::clearPost( $post );
		}

		// Cache Enabler.
		if ( class_exists( 'Cache_Enabler' ) ) {
			Cache_Enabler::clear_page_cache_by_post_id( $post );
		}

		// WP-Optimize.
		if ( class_exists( 'WPO_Page_Cache' ) ) {
			WPO_Page_Cache::delete_single_post_cache( $post );
		}
	}
}
