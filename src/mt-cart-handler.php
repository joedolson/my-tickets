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
	$options = mt_get_settings();
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
	$options = mt_get_settings();
	// save payment post.
	$current_user = wp_get_current_user();
	if ( isset( $post['mt_purchaser_id'] ) ) {
		$purchaser = absint( $post['mt_purchaser_id'] );
	} else {
		$purchaser = ( is_user_logged_in() ) ? $current_user->ID : 0;
	}
	$date    = ( isset( $post['mt_purchase_date'] ) ) ? $post['mt_purchase_date'] : mt_date( 'Y-m-d H:i:00', mt_current_time(), false );
	$payment = mt_get_data( 'payment' );
	if ( ! is_string( get_post_status( $payment ) ) || 'trash' === get_post_status( $payment ) ) {
		$payment = false;
	}
	if ( $payment && ! mt_is_payment_completed( $payment ) ) {
		$purchase_id = mt_get_data( 'payment' );
		$status      = 'draft';
	} else {
		mt_delete_data( 'payment' );
		$status      = 'draft';
		$date        = $date;
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
	/**
	 * Action immediately after new payment post is created.
	 *
	 * @hook mt_after_insert_payment
	 *
	 * @param {int}   $purchase_id Payment post ID.
	 * @param {array} $post Array of data passed to function.
	 */
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
	if ( $purchaser ) {
		update_user_meta( $purchaser, 'mt_phone', sanitize_text_field( $phone ) );
	}
	$vat = ( isset( $post['mt_vat'] ) ) ? $post['mt_vat'] : '';
	update_post_meta( $purchase_id, '_vat', sanitize_text_field( $vat ) );
	if ( is_user_logged_in() ) {
		update_user_meta( $purchaser, 'mt_vat', sanitize_text_field( $vat ) );
	}
	$purchased = ( isset( $post['mt_cart_order'] ) ) ? $post['mt_cart_order'] : false;
	$paid      = mt_calculate_cart_cost( $purchased, $purchase_id );
	$gateway   = sanitize_text_field( $post['mt_gateway'] );
	if ( isset( $options['mt_handling'] ) && is_numeric( $options['mt_handling'] ) ) {
		$handling_total = mt_get_cart_handling( $options, $gateway );
		$paid           = $paid + $handling_total;
		update_post_meta( $purchase_id, '_mt_handling', $handling_total );
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
	update_post_meta( $purchase_id, '_gateway', $gateway );
	update_post_meta( $purchase_id, '_purchase_data', $purchased );
	// Debugging.
	mt_debug( print_r( $purchased, 1 ), 'Purchase Data saved at nav to payment screen', $purchase_id );
	mt_debug( print_r( $post, 1 ), 'Data passed from client at nav to payment screen', $purchase_id );
	update_post_meta( $purchase_id, '_ticketing_method', $post['ticketing_method'] );
	/**
	 * Action run when payment is saved.
	 *
	 * @hook mt_save_payment_fields
	 *
	 * @param {int}   $purchase_id Payment post ID.
	 * @param {array} $post Array of data passed to function.
	 * @param {array} $purchased Array of ticket purchase data from cart.
	 */
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
 * Get inventory change comparing submitted cart data and existing cart data.
 *
 * @param array $passed Data passed from cart form. If empty, removing existing cart.
 * @param array $saved Data currently saved in cart.
 *
 * @return array Array of changes to record.
 */
function mt_get_inventory_change( $passed = array(), $saved = array() ) {
	$remove = false;
	if ( empty( $saved ) ) {
		$saved = mt_get_cart();
	}
	if ( empty( $passed ) ) {
		// If no data passed, we need to remove the current cart.
		$passed = $saved;
		$remove = true;
	}
	$changes = array();
	foreach ( $passed as $event_id => $counts ) {
		foreach ( $counts as $type => $new_count ) {
			$old_count = absint( ( isset( $saved[ $event_id ] ) ) ? $saved[ $event_id ][ $type ] : 0 );
			$new_count = absint( $new_count );
			// If no change, don't include unless removing cart.
			if ( $new_count !== $old_count || $remove ) {
				$increment        = ( $remove ) ? ( $old_count * -1 ) : ( $old_count - $new_count ) * -1;
				$changes[ $type ] = array(
					'event_id' => $event_id,
					'count'    => $increment,
					'old'      => $old_count,
					'new'      => $new_count,
				);
			}
		}
	}

	return $changes;
}

/**
 * Update virtual inventory for an event.
 *
 * @param int    $event_id Event post ID.
 * @param string $type Type of ticket changing.
 * @param int    $count Number of tickets to add or substract.
 */
function mt_update_inventory( $event_id, $type, $count ) {
	$virtual_inventory = get_post_meta( $event_id, '_mt_virtual_inventory', true );
	if ( ! is_array( $virtual_inventory ) ) {
		$virtual_inventory = array();
	}
	if ( isset( $virtual_inventory[ $type ] ) ) {
		$current                    = $virtual_inventory[ $type ];
		$new                        = ( ( $current + $count ) < 0 ) ? 0 : $current + $count;
		$virtual_inventory[ $type ] = $new;
	} else {
		// Can't initialize store with negative values.
		if ( $count > 0 ) {
			$virtual_inventory[ $type ] = absint( $count );
		}
	}
	update_post_meta( $event_id, '_mt_virtual_inventory', $virtual_inventory );
}

/**
 * Determine how many total real tickets have been sold for a given pricing set. Ignores virtual inventory.
 *
 * @param array          $pricing Pricing array.
 * @param string|integer $available Available tickets.
 *
 * @return array|bool
 */
function mt_tickets_left( $pricing, $available ) {
	$total = 0;
	$sold  = 0;
	foreach ( $pricing as $options ) {
		if ( 'inherit' !== $available ) {
			$sold = $sold + intval( $options['sold'] );
		} else {
			$tickets = intval( $options['tickets'] ) - intval( $options['sold'] );
			$total   = $total + $tickets;
			$sold    = $sold + intval( $options['sold'] );
		}
	}
	if ( 'inherit' !== $available && is_numeric( trim( $available ) ) ) {
		$total = $available - $sold;
	}

	return array(
		'remain' => $total,
		'sold'   => $sold,
		'total'  => $sold + $total,
	);
}

/**
 * Check virtual inventory for an event and ticket group. Returns total tickets if ticket type not passed.
 *
 * @param int    $event_id Event post ID.
 * @param string $type Type of ticket being checked.
 *
 * @return array|false Number of tickets available in key 'available', sold in key 'sold'.
 */
function mt_check_inventory( $event_id, $type = '' ) {
	$options      = mt_get_settings();
	$registration = get_post_meta( $event_id, '_mt_registration_options', true );
	if ( ! is_array( $registration ) ) {
		return false;
	}
	$prices = $registration['prices'];
	if ( ( 'discrete' === $registration['counting_method'] || 'event' === $registration['counting_method'] ) ) {
		if ( '' !== $type ) {
			$available = absint( $prices[ $type ]['tickets'] );
			$sold      = absint( isset( $prices[ $type ]['sold'] ) ? $prices[ $type ]['sold'] : 0 );
		} else {
			$available = 0;
			$sold      = 0;
			foreach ( $prices as $pricetype ) {
				$available += (int) $pricetype['tickets'];
				$sold      += (int) $pricetype['sold'];
			}
		}
	} else {
		$available = absint( $registration['total'] );
		$sold      = 0;
		foreach ( $prices as $pricetype ) {
			$sold = $sold + intval( ( isset( $pricetype['sold'] ) ) ? $pricetype['sold'] : 0 );
		}
	}

	/**
	 * Filter whether a particular event uses virtual inventory. Return 'virtual' to use the virtual inventory, 'actual' to use completed purchases only.
	 *
	 * @hook mt_is_virtual_inventory
	 *
	 * @param {string} $mt_inventory 'actual' or 'virtual'.
	 * @param {int}    $event_id Event ID.
	 *
	 * @return {string}
	 */
	$is_virtual = apply_filters( 'mt_is_virtual_inventory', $options['mt_inventory'], $event_id );
	if ( 'virtual' === $is_virtual ) {
		// Virtual inventory holds tickets in carts but not yet sold.
		$virtual_inventory = get_post_meta( $event_id, '_mt_virtual_inventory', true );
		if ( '' !== $type ) {
			$current_virtual = isset( $virtual_inventory[ $type ] ) ? $virtual_inventory[ $type ] : 0;
		} else {
			$current_virtual = 0;
			if ( is_array( $virtual_inventory ) ) {
				foreach ( $virtual_inventory as $type => $quantity ) {
					$current_virtual += (int) $quantity;
				}
			}
		}

		$available = $available - $current_virtual;
		$sold      = $sold + $current_virtual;
	}

	return array(
		'available' => $available,
		'sold'      => $sold,
		'total'     => $available + $sold,
	);
}

/**
 * Generates tickets for purchase.
 *
 * @param integer    $purchase_id Payment ID.
 * @param bool|array $purchased Array when initially building tickets, false otherwise.
 * @param bool       $resending We're resending a notice right now.
 *
 * @return null
 */
function mt_create_tickets( $purchase_id, $purchased = false, $resending = false ) {
	// _purchase_data contains the original purchase info; it's not updated when something is moved.
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
			for ( $i = 0; $i < $count; $i++ ) {
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
	$options    = mt_get_settings();
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
