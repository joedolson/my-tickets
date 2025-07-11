<?php
/**
 * Create and send notifications.
 *
 * @category Core
 * @package  My Tickets
 * @author   Joe Dolson
 * @license  GPLv3
 * @link     https://www.joedolson.com/my-tickets/
 */

// if a post is trashed, return the tickets to pool.
// Trashing a payment is *not* a refund; no notifications are sent.
add_action( 'wp_trash_post', 'mt_return_tickets_action' );
/**
 * Return reserved tickets to pool if a payment is trashed.
 *
 * @param int $id Payment ID.
 */
function mt_return_tickets_action( $id ) {
	$type = get_post_type( $id );
	if ( 'mt-payments' === $type && ( false !== get_post_status( $id ) ) ) {
		mt_return_tickets( $id );
	}
}


add_action( 'save_post', 'mt_generate_notifications', 15 );
/**
 * Send payment notifications to admin and purchaser when a payment is transitioned to published.
 *
 * @param int $id Payment ID.
 */
function mt_generate_notifications( $id ) {
	$type   = get_post_type( $id );
	$status = 'quo';
	if ( 'mt-payments' === $type ) {
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE || wp_is_post_revision( $id ) ) {
			return;
		}
		$post = get_post( $id );
		if ( 'publish' !== $post->post_status ) {
			return;
		}
		$email_sent  = get_post_meta( $id, '_notified', true );
		$last_status = get_post_meta( $id, '_last_status', true );
		$paid        = get_post_meta( $id, '_is_paid', true );
		if ( ! $email_sent || isset( $_POST['_send_email'] ) || ( 'Pending' === $last_status && 'Completed' === $paid ) ) {
			$resend           = ( isset( $_POST['_send_email'] ) ) ? true : false;
			$details['email'] = get_post_meta( $id, '_email', true );
			$details['name']  = get_the_title( $id );
			$details['id']    = $id;
			// only send this if email is provided; otherwise send notice to admin.
			if ( ! ( is_email( $details['email'] ) ) ) {
				$details['email'] = get_option( 'admin_email' );
				$status           = 'invalid_email';
			}
			mt_send_notifications( $paid, $details, $status, $resend );
		}
	}

	return;
}

add_filter( 'mt_format_array', 'mt_format_array', 10, 5 );
/**
 * Format array data for use in email notifications.
 *
 * @param string $output text output.
 * @param string $type Type of display.
 * @param array  $data Data to display.
 * @param int    $payment_id Payment ID.
 * @param string $context Admin or email.
 *
 * @return string
 */
function mt_format_array( $output, $type, $data, $payment_id, $context = 'admin' ) {
	if ( is_array( $data ) ) {
		switch ( $type ) {
			case 'purchase':
				$output = mt_format_purchase( $data, false, $payment_id );
				break;
			case 'address':
				$output = mt_format_address( $data );
				break;
			case 'tickets':
				$output = mt_format_tickets( $data, 'text', $payment_id, $context );
				break;
			case 'ticket_ids':
				$output = mt_format_tickets( $data, 'ids', $payment_id, $context );
				break;
		}
	}

	return $output;
}

/**
 * Get the permalink to an event.
 *
 * @param int $event_id Post ID for the ticketed event.
 *
 * @return string URL to event.
 */
function mt_get_event_link( $event_id ) {
	$url = get_the_permalink( $event_id );

	/**
	 * Filter the link to a ticketed event.
	 *
	 * @hook mt_get_event_link
	 *
	 * @param {string} $url Event permalink.
	 * @param {int}    $event_id Event Post ID.
	 *
	 * @return {string}
	 */
	$url = apply_filters( 'mt_get_event_link', $url, $event_id );

	return $url;
}

/**
 * Format purchase data for use in email notifications. (Basically, simplified version of cart output.)
 *
 * @param array                 $purchase Purchase data.
 * @param bool                  $format Text or HTML.
 * @param mixed integer/boolean $payment_id Payment ID.
 *
 * @return string
 */
function mt_format_purchase( $purchase, $format = false, $payment_id = false ) {
	$output  = '';
	$options = mt_get_settings();
	// format purchase.
	$is_html = ( 'true' === $options['mt_html_email'] || 'html' === $format ) ? true : false;
	$sep     = ( $is_html ) ? '<br />' : "\n";
	if ( ! $purchase ) {
		$output = __( 'Your ticket information will be available once your payment is completed.', 'my-tickets' );
	} else {
		$total = 0;
		$ids   = array();
		foreach ( $purchase as $event ) {
			foreach ( $event as $event_id => $tickets ) {
				if ( in_array( $event_id, $ids, true ) ) {
					continue;
				}
				$ids[] = $event_id;
				if ( false === get_post_status( $event_id ) ) {
					// This event does not exist.
					return __( 'This event has been deleted.', 'my-tickets' );
				}
				$handling = get_post_meta( $payment_id, '_ticket_handling', true );
				if ( ! ( $handling && is_numeric( $handling ) ) ) {
					$handling = 0;
				}
				$handling     = (float) $handling;
				$title        = ( $is_html ) ? '<strong>' . get_the_title( $event_id ) . '</strong>' : get_the_title( $event_id );
				$title        = ( $is_html ) ? "<a href='" . mt_get_event_link( $event_id ) . "'>" . $title . '</a>' : $title;
				$event        = get_post_meta( $event_id, '_mc_event_data', true );
				$registration = get_post_meta( $event_id, '_mt_registration_options', true );
				$counting     = $registration['counting_method'];
				if ( ! is_array( $event ) ) {
					continue; // This event may no longer have event data on it, and needs to be skipped.
				}
				$date     = date_i18n( get_option( 'date_format' ), strtotime( $event['event_begin'] ) );
				$time     = date_i18n( get_option( 'time_format' ), strtotime( $event['event_time'] ) );
				$general  = ( isset( $event['general_admission'] ) && 'on' === $event['general_admission'] ) ? true : false;
				$validity = ( isset( $event['event_valid'] ) ) ? $event['event_valid'] : 0;
				if ( 'expire' === $validity && isset( $event['expire_date'] ) && ! empty( $event['expire_date'] ) ) {
					$valid_dt = $event['expire_date'];
				} else {
					$purchase_date = get_the_date( 'Y-m-d', $payment_id );
					$valid_dt      = ( 'infinite' !== $validity ) ? strtotime( $purchase_date . ' + ' . $validity ) : '';
				}
				if ( 'infinite' === $validity ) {
					$valid_til = __( 'Ticket does not expire', 'my-tickets' );
				} else {
					$valid_til = mt_date( get_option( 'date_format' ), $valid_dt );
					// Translators: Date ticket valid until.
					$valid_til = ( $general ) ? sprintf( __( 'Tickets valid until %s', 'my-tickets' ), $valid_til ) : $event['event_begin'] . ' ' . $event['event_time'];
				}
				$handling_notice = '';
				$tickets_list    = '';
				foreach ( $tickets as $type => $ticket ) {
					if ( $ticket['count'] > 0 ) {
						$price = (float) $ticket['price'];
						$orig  = mt_get_original_ticket_price( $event_id, $type );
						if ( 'event' === $counting ) {
							// At this stage, the event date is parsed by sanitize_title, and contains an extra hyphen.
							$type = substr_replace( $type, ' ', 10, 1 );
							$type = date_i18n( get_option( 'date_format' ) . ' @ ' . get_option( 'time_format' ), strtotime( $type ) );
						} else {
							$type = apply_filters( 'mt_ticket_type_label', ucfirst( str_replace( '-', ' ', $type ) ) );
						}
						$price       = $price - $handling;
						$discount    = ( $price !== $orig ) ? $price : mt_calculate_discount( $price, $event_id, $payment_id );
						$total       = ( $discount !== $price ) ? $total + $discount * $ticket['count'] : $total + $price * $ticket['count'];
						$display_app = '';
						// Match formats so comparison is valid.
						if ( sprintf( '%01.2f', $orig ) !== sprintf( '%01.2f', $price ) ) {
							// Translators: original ticket price, before discounts.
							$discount = strip_tags( apply_filters( 'mt_money_format', $orig ) );
							// Translators: discounted cost of ticket.
							$display_app = ' (' . sprintf( __( 'Discounted from %s', 'my-tickets' ), $discount ) . ')';
						}
						$display_price = strip_tags( apply_filters( 'mt_money_format', $price ) ) . $display_app;
						if ( $handling ) {
							// Translators: price of ticket handling charge.
							$handling_notice = ' ' . apply_filters( 'mt_handling_charge_of', sprintf( __( '(Per-ticket handling charge of %s)', 'my-tickets' ), apply_filters( 'mt_money_format', $handling ) ) );
						}
						if ( $is_html ) {
							// translators: Type of tickets, cost of tickets, price of tickets.
							$tickets_list .= sprintf( _n( '%1$s: %3$d ticket at %2$s', '%1$s: %3$d tickets at %2$s', $ticket['count'], 'my-tickets' ), '<strong>' . $type . '</strong>', $display_price, $ticket['count'] ) . $handling_notice . $sep;
						} else {
							// translators: type of tickets, cost of tickets, price of tickets.
							$tickets_list .= sprintf( _n( '%1$s: %3$d ticket at %2$s', '%1$s: %3$d tickets at %2$s', $ticket['count'], 'my-tickets' ), $type, $display_price, $ticket['count'] ) . $handling_notice . $sep;
						}
					}
				}
				if ( '' !== trim( $tickets_list ) ) {
					if ( $general ) {
						$output .= sprintf( apply_filters( 'mt_purchased_ga_tickets_format', '%1$s - %2$s', $is_html, $event ), $title, $valid_til ) . $sep;
					} else {
						if ( 'event' === $counting ) {
							$output .= sprintf( apply_filters( 'mt_purchased_tickets_format', '%1$s', $is_html, $event ), $title ) . $sep;
						} else {
							$output .= sprintf( apply_filters( 'mt_purchased_tickets_format', '%1$s - %2$s @ %3$s', $is_html, $event ), $title, $date, $time ) . $sep;
						}
					}
					$output .= apply_filters( 'mt_custom_tickets_fields', '', $event_id, $payment_id, $sep );
					$output .= $sep . $tickets_list;
				}
			}
		}
		$output .= $sep;
		$total   = apply_filters( 'mt_apply_total_discount', $total, $payment_id );
		if ( $is_html ) {
			$output = wpautop( $output . '<hr><strong>' . __( 'Ticket Total', 'my-tickets' ) . '</strong>: ' . strip_tags( apply_filters( 'mt_money_format', $total ) ) );
		} else {
			$output .= $sep . __( 'Ticket Total', 'my-tickets' ) . ': ' . strip_tags( apply_filters( 'mt_money_format', $total ) ) . $sep;
		}
	}

	return $output;
}

/**
 * Format shipping address data for use in email notifications.
 *
 * @param array $address Address data.
 *
 * @return string
 */
function mt_format_address( $address ) {
	// format address.
	$output = '';
	if ( $address ) {
		$options = mt_get_settings();
		$sep     = ( 'true' === $options['mt_html_email'] ) ? '<br />' : PHP_EOL;
		foreach ( $address as $value ) {
			$separator = ( '' === trim( $value ) ) ? '' : $sep;
			$output   .= $value . $separator;
		}

		return ( 'true' === $options['mt_html_email'] ) ? wpautop( $output ) : PHP_EOL . $output . PHP_EOL;
	}

	return $output;
}

/**
 * Format ticket data for use in email notifications.
 *
 * @param array  $tickets Tickets to format.
 * @param string $type Type of display.
 * @param int    $payment_id Payment ID.
 * @param string $context Admin or email.
 *
 * @return string
 */
function mt_format_tickets( $tickets, $type = 'text', $payment_id = false, $context = 'admin' ) {
	if ( ! $payment_id ) {
		return '';
	}
	$options  = mt_get_settings();
	$output   = '';
	$show     = '';
	$move     = '';
	$is_html  = ( 'true' === $options['mt_html_email'] || 'html' === $type ) ? true : false;
	$sep      = ( $is_html ) ? '<br />' . "\r\n" : "\n";
	$total    = count( $tickets );
	$i        = 1;
	$test_use = false;
	if ( is_admin() && ( current_user_can( 'mt-verify-ticket' ) || current_user_can( 'manage_options' ) ) ) {
		if ( isset( $_GET['ticket-action'] ) && isset( $_GET['ticket'] ) ) {
			$ticket_id = sanitize_text_field( $_GET['ticket'] );
			if ( 'checkin' === $_GET['ticket-action'] ) {
				add_post_meta( $payment_id, '_tickets_used', $ticket_id );
			} else {
				delete_post_meta( $payment_id, '_tickets_used', $ticket_id );
			}
		}
		$used     = get_post_meta( $payment_id, '_tickets_used' );
		$test_use = true;
	}
	$options    = mt_get_settings();
	$ticket_url = get_permalink( $options['mt_tickets_page'] );
	foreach ( $tickets as $ticket ) {
		if ( $test_use ) {
			$ticket_id = str_replace( array( $ticket_url . '&ticket_id=', $ticket_url . '?ticket_id=' ), '', $ticket );
			if ( is_array( $used ) ) {
				$is_used = in_array( $ticket_id, $used, true );
				$action  = add_query_arg(
					array(
						'action' => 'edit',
						'post'   => $payment_id,
						'ticket' => $ticket_id,
					),
					admin_url( 'post.php' )
				);
				$checkin = add_query_arg( 'ticket-action', 'checkin', $action );
				$undo    = add_query_arg( 'ticket-action', 'undo', $action );
				if ( 'admin' === $context ) {
					$show = ( $is_used ) ? " <span class='dashicons dashicons-yes' aria-hidden='true'></span><a href='" . esc_url( $undo ) . "'>" . __( 'Checked in', 'my-tickets' ) . '</a> ' : " <span class='dashicons dashicons-edit' aria-hidden='true'></span><a href='" . esc_url( $checkin ) . "'>" . __( 'Check-in', 'my-tickets' ) . '</a>';
					$show = '<div>' . $show . '</div>';
				}
			}
			if ( 'admin' === $context ) {
				$event_id     = mt_get_ticket( $ticket_id )->ID;
				$prices       = mt_get_prices( $event_id );
				$type_options = '';
				$ticket_data  = get_post_meta( $event_id, '_' . $ticket_id, true );
				$ticket_type  = isset( $ticket_data['type'] ) ? $ticket_data['type'] : '';
				foreach ( $prices as $key => $type ) {
					if ( $ticket_type === $key ) {
						continue;
					}
					$type_options .= '<option value="' . $key . '">' . $type['label'] . '</option>';
				}
				// Translators: 1) type of ticket, 2) event ticket sold for, 3) Event time.
				$status    = sprintf( __( 'Move %1$s ticket (%2$s, %3$s) to a different event', 'my-tickets' ), mt_get_ticket_type( $ticket_id ), get_the_title( $event_id ), mt_get_event_time( $ticket_id ) );
				$move      = "<button type='button' class='edit-ticket button-secondary' aria-expanded='false' aria-controls='mt-edit-tickets-$i'>" . __( 'Edit', 'my-tickets' ) . '</button>';
				$move_form = '<div class="mt-move-tickets-wrapper">
						<div class="mt-move-tickets" id="mt-edit-tickets-' . $i . '">
							<div class="mt-ticket-moved-response" aria-live="polite">' . $status . '</div>
							<div class="mt-move-tickets-inner">
								<label for="mt-move-tickets-choose-' . $i . '">Move to Event <i><span aria-live="assertive"></span></i></label> 
								<input type="text" placeholder="Search term" id="mt-move-tickets-choose-' . $i . '" class="suggest widefat mt-move-tickets-target" name="mt-event-target" value="" />
							</div>
							<div class="mt-move-tickets-inner">
								<label for="mt-switch-ticket-type">' . __( 'Change ticket group', 'my-tickets' ) . '</label>
								<select id="mt-switch-ticket-type" class="widefat mt-switch-ticket-type">
									<option value="none">' . __( 'No change', 'my-tickets' ) . '</option>
									' . $type_options . '
								</select>
							</div>
							<button type="button" data-payment="' . $payment_id . '" data-event="' . $event_id . '" data-ticket="' . $ticket_id . '" class="mt-move-tickets-button button-secondary">' . __( 'Update Ticket', 'my-tickets' ) . '</button>
							<button type="button" data-payment="' . $payment_id . '" data-event="' . $event_id . '" data-ticket="' . $ticket_id . '" class="mt-delete-ticket-button button-secondary"><span class="dashicons dashicons-no" aria-hidden="true"></span> ' . __( 'Delete Ticket', 'my-tickets' ) . '</button>
						</div>
					</div>';
			}
		}
		if ( 'ids' === $type ) {
			$ticket_output = "$i/$total: $ticket" . $show . $move . $sep;
		} else {
			$ticket        = ( $is_html ) ? "<a href='$ticket'>" . __( 'View Ticket', 'my-tickets' ) . " ($i/$total)</a>" : $ticket;
			$ticket_output = "$i/$total: " . $ticket . $show . $move . $sep;
			$ticket_output = ( 'admin' === $context ) ? '<li><div class="controls">' . $ticket . $show . $move . '</div>' . $move_form . '</li>' : $ticket_output;
		}

		$output .= apply_filters( 'mt_custom_ticket_output', $ticket_output, $payment_id, $sep );
		++$i;
	}
	$output = ( 'admin' === $context ) ? '<ul class="admin-tickets">' . $output . '</ul>' : $output;

	return $output;
}

add_filter( 'mt_format_receipt', 'mt_format_receipt' );
/**
 * Generate link to receipt for email notifications.
 *
 * @param string $receipt Receipt URL.
 *
 * @return string
 */
function mt_format_receipt( $receipt ) {
	$options = mt_get_settings();
	if ( 'true' === $options['mt_html_email'] ) {
		$receipt = "<a href='$receipt'>" . __( 'View your receipt for this purchase', 'my-tickets' ) . '</a>';
	}

	return $receipt;
}

/**
 * Send notifications to purchaser and admin.
 *
 * @param string $status Payment status.
 * @param array  $details Payment information.
 * @param bool   $error Error thrown.
 * @param bool   $resending Resending this notification.
 */
function mt_send_notifications( $status = 'Completed', $details = array(), $error = false, $resending = false ) {
	$options  = mt_get_settings();
	$blogname = get_option( 'blogname' );
	$subject  = '';
	$body     = '';
	$subject2 = '';
	$body2    = '';
	$send     = true;
	$id       = $details['id']; // Purchase id.
	$gateway  = get_post_meta( $id, '_gateway', true );
	$notes    = ( ! empty( $options['mt_gateways'][ $gateway ]['notes'] ) ) ? $options['mt_gateways'][ $gateway ]['notes'] : '';
	$phone    = get_post_meta( $id, '_phone', true );
	$vat      = get_post_meta( $id, '_vat', true );
	// Restructure post meta array to match cart array.
	if ( ( 'Completed' === $status || ( 'Pending' === $status && 'offline' === $gateway ) ) && ! $resending ) {
		mt_create_tickets( $id );
	}
	$purchased     = get_post_meta( $id, '_purchased' );
	$purchase_data = get_post_meta( $id, '_purchase_data', true );
	$ticket_array  = mt_setup_tickets( $purchased, $id );
	$handling      = mt_get_cart_handling( $options, $gateway );
	$shipping      = ( isset( $options['mt_shipping'] ) ) ? floatval( $options['mt_shipping'] ) : 0;

	$total = mt_calculate_cart_cost( $purchase_data, $id ) + $handling + $shipping;
	$hash  = md5(
		add_query_arg(
			array(
				'post_type' => 'mt-payments',
				'p'         => $id,
			),
			home_url()
		)
	);

	$receipt        = add_query_arg( 'receipt_id', $hash, get_permalink( $options['mt_receipt_page'] ) );
	$transaction_id = get_post_meta( $id, '_transaction_id', true );

	if ( 'Completed' === $status ) {
		$amount_due = '0.00';
	} else {
		$amount_due = $total;
	}
	$amount_due       = strip_tags( apply_filters( 'mt_money_format', $amount_due ) );
	$total            = strip_tags( apply_filters( 'mt_money_format', $total ) );
	$transaction_data = get_post_meta( $id, '_transaction_data', true );
	$address          = ( isset( $transaction_data['shipping'] ) ) ? $transaction_data['shipping'] : false;
	$ticketing_method = get_post_meta( $id, '_ticketing_method', true );
	$email            = $details['email'];

	if ( 'eticket' === $ticketing_method || 'printable' === $ticketing_method ) {
		$tickets    = apply_filters( 'mt_format_array', '', 'tickets', $ticket_array, $id, 'email' );
		$ticket_ids = apply_filters( 'mt_format_array', '', 'ticket_ids', array_keys( $ticket_array ), $id, 'email' );
		update_post_meta( $id, '_is_delivered', 'true' );
	} else {
		$tickets    = ( 'willcall' === $ticketing_method ) ? __( 'Your tickets will be available at the box office.', 'my-tickets' ) : __( 'Your tickets will be mailed to you at the address provided.', 'my-tickets' );
		$tickets    = ( 'true' === $options['mt_html_email'] ) ? '<p>' . $tickets . '</p>' : $tickets;
		$ticket_ids = '';
	}
	$bulk_tickets = ( 'printable' === $ticketing_method ) ? add_query_arg(
		array(
			'receipt_id' => $hash,
			'multiple'   => true,
		),
		get_permalink( $options['mt_tickets_page'] )
	) : '';
	// Get translatable name of ticketing method.
	$method_options  = mt_default_fields()['ticketing_method']['choices'];
	$friendly_method = ( isset( $method_options[ $ticketing_method ] ) ) ? $method_options[ $ticketing_method ] : $ticketing_method;

	$purchases = apply_filters( 'mt_format_array', '', 'purchase', $purchased, $id, 'email' );
	$data      = array(
		'receipt'        => apply_filters( 'mt_format_receipt', $receipt ),
		'tickets'        => $tickets,
		'ticket_ids'     => $ticket_ids,
		'name'           => $details['name'],
		'blogname'       => $blogname,
		'total'          => $total,
		'key'            => $hash,
		'purchase'       => $purchases,
		'address'        => apply_filters( 'mt_format_array', '', 'address', $address, $id, 'email' ),
		'transaction'    => apply_filters( 'mt_format_array', '', 'transaction', $transaction_data, $id, 'email' ),
		'transaction_id' => $transaction_id,
		'amount_due'     => $amount_due,
		'handling'       => apply_filters( 'mt_money_format', $handling ),
		'shipping'       => apply_filters( 'mt_money_format', $shipping ),
		'method'         => $friendly_method,
		'phone'          => $phone,
		'vat'            => $vat,
		'purchase_ID'    => $id,
		'purchase_edit'  => get_edit_post_link( $id, 'email' ),
		'gateway_notes'  => $notes,
		'buyer_email'    => $email,
		'event_notes'    => apply_filters( 'mt_format_notes', '', $purchased, $id ),
		'bulk_tickets'   => $bulk_tickets,
	);

	$custom_fields = mt_get_custom_fields( 'notify' );
	foreach ( $custom_fields as $name => $field ) {
		$info  = get_post_meta( $id, $name, true );
		$event = isset( $info['event_id'] ) ? $info['event_id'] : false;
		if ( ! $event ) {
			continue;
		}
		$value         = $info[ $name ];
		$data[ $name ] = call_user_func( $field['display_callback'], $value, $event, $field );
	}

	$data = apply_filters( 'mt_notifications_data', $data, $details );

	$headers[] = "From: $blogname Events <" . $options['mt_from'] . '>';
	$headers[] = "Reply-to: $options[mt_from]";

	if ( 'Completed' === $status || ( 'Pending' === $status && 'offline' === $gateway ) ) {
		$append = '';
		if ( 'invalid_email' === $error ) {
			$append = __( 'Purchaser did not provide valid email', 'my-tickets' );
		}
		if ( ! empty( $options['messages']['interim']['purchaser']['subject'] ) && ! empty( $options['messages']['interim']['purchaser']['body'] ) && 'Pending' === $status && 'offline' === $gateway ) {
			$purchaser_subject = $options['messages']['interim']['purchaser']['subject'];
			$purchaser_body    = $options['messages']['interim']['purchaser']['body'];
			$admin_subject     = $options['messages']['interim']['admin']['subject'];
			$admin_body        = $options['messages']['interim']['admin']['body'];
		} else {
			$purchaser_subject = $options['messages']['completed']['purchaser']['subject'];
			$purchaser_body    = $options['messages']['completed']['purchaser']['body'];
			$admin_subject     = $options['messages']['completed']['admin']['subject'];
			$admin_body        = $options['messages']['completed']['admin']['body'];
		}

		$subject  = mt_draw_template( $data, $purchaser_subject );
		$subject2 = mt_draw_template( $data, $admin_subject );

		$body  = mt_draw_template( $data, $append . $purchaser_body );
		$body2 = mt_draw_template( $data, $admin_body );
	}

	if ( 'Refunded' === $status ) {

		$subject  = mt_draw_template( $data, $options['messages']['refunded']['purchaser']['subject'] );
		$subject2 = mt_draw_template( $data, $options['messages']['refunded']['admin']['subject'] );

		$body  = mt_draw_template( $data, $options['messages']['refunded']['purchaser']['body'] );
		$body2 = mt_draw_template( $data, $options['messages']['refunded']['admin']['body'] );

		// put tickets purchased on this registration back on event.
		mt_return_tickets( $id );
	}

	if ( 'Turned Back' === $status ) {
		// No notifications, just cancelled.
		mt_return_tickets( $id );
	}

	if ( 'Failed' === $status ) {

		$subject  = mt_draw_template( $data, $options['messages']['failed']['purchaser']['subject'] );
		$subject2 = mt_draw_template( $data, $options['messages']['failed']['admin']['subject'] );

		$body  = mt_draw_template( $data, $options['messages']['failed']['purchaser']['body'] );
		$body2 = mt_draw_template( $data, $options['messages']['failed']['admin']['body'] );
	}

	if ( 'Pending' === $status || ( false !== strpos( $status, 'Other' ) ) ) {
		if ( 'Pending' === $status && 'offline' === $gateway ) {
			// For offline payments, we do send notifications.
			$send = true;
		} else {
			// No messages sent while status is pending or for 'Other' statuses.
			$send = false;
		}
	}

	if ( $send ) {
		if ( 'true' === $options['mt_html_email'] ) {
			add_filter( 'wp_mail_content_type', 'mt_html_type' );
			$body = mt_html_email_header( $data ) . $body . mt_html_email_footer( $data );
		}

		// message to purchaser.
		$body = apply_filters( 'mt_modify_email_body', $body, 'purchaser' );
		// Log this message.
		add_post_meta(
			$id,
			'_mt_send_email',
			array(
				'body'    => $body,
				'subject' => $subject,
				'date'    => mt_current_time(),
			)
		);
		$sent = wp_mail( $email, $subject, $body, $headers );
		if ( ! $sent ) {
			// If mail sends, try without custom headers.
			wp_mail( $email, $subject, $body );
		}
		// message to admin.
		$body2      = apply_filters( 'mt_modify_email_body', $body2, 'admin' );
		$admin_sent = wp_mail( $options['mt_to'], $subject2, $body2, $headers );
		if ( ! $admin_sent ) {
			// If mail send fails, try without custom headers.
			wp_mail( $options['mt_to'], $subject2, $body2 );
		}
		if ( 'true' === $options['mt_html_email'] ) {
			remove_filter( 'wp_mail_content_type', 'mt_html_type' );
		}
		update_post_meta( $id, '_notified', 'true' );
	}
}

/**
 * Write HTML email header for email body.
 *
 * @param array $data Email data array.
 *
 * @return string
 */
function mt_html_email_header( $data ) {
	$margin = is_rtl() ? 'rightmargin' : 'leftmargin';
	$header = '<!DOCTYPE html>
<html ' . get_language_attributes() . '>
	<head>
		<meta http-equiv="Content-Type" content="text/html; charset=' . get_bloginfo( 'charset' ) . '" />
		<meta content="width=device-width, initial-scale=1.0" name="viewport">
		<title>' . get_bloginfo( 'name', 'display' ) . '</title>
	</head>
	<body ' . $margin . '="0" marginwidth="0" topmargin="0" marginheight="0" offset="0" style="padding: 0">
		<table width="100%" id="outer_wrapper">
			<tr>
				<td><!-- Deliberately empty to support consistent sizing and layout across multiple email clients. --></td>
				<td width="600" id="wrapper">
					<div id="wrapper" style="' . mt_html_email_wrapper_styles() . '">
						<div id="header"><h1 style="' . mt_html_email_h1_styles() . '">' . $data['blogname'] . '</h1></div>
						<div id="body_content" style="' . mt_html_email_body_styles() . '">';
	/**
	 * Filter the header used for HTML email messages.
	 *
	 * @hook mt_html_email_header
	 *
	 * @param {string} $header HTML header content.
	 * @param {array}  $data Email data array.
	 *
	 * @return {string}
	 */
	return apply_filters( 'mt_html_email_header', $header, $data );
}

/**
 * Write HTML email footer for email body.
 *
 * @param array $data Email data array.
 *
 * @return string
 */
function mt_html_email_footer( $data ) {
	$footer = '
						</div>
					</div>
				</td>
				<td></td>
			</tr>
		</table>
	</body>
</html>';
	/**
	 * Filter the footer used for HTML email messages.
	 *
	 * @hook mt_html_email_footer
	 *
	 * @param {string} $header HTML footer content.
	 * @param {array}  $data Email data array.
	 *
	 * @return {string}
	 */
	return apply_filters( 'mt_html_email_footer', $footer, $data );
}

/**
 * Write Inline HTML email styles for email main wrapper.
 */
function mt_html_email_wrapper_styles() {
	$styles = 'background-color: #fff;margin: 0;padding: 40px 0;-webkit-text-size-adjust: none !important;width: 100%;max-width: 600px;';
	/**
	 * Filter the wrapper styles used for HTML email messages.
	 *
	 * @hook mt_html_email_wrapper_styles
	 *
	 * @param {string} $styles HTML styles.
	 *
	 * @return {string}
	 */
	return apply_filters( 'mt_html_email_wrapper_styles', $styles );
}

/**
 * Write Inline HTML email styles for email h1.
 */
function mt_html_email_h1_styles() {
	$styles = 'color:#000;font-size:32px;font-weight:700;text-align:center;';
	/**
	 * Filter the wrapper styles used for HTML email messages.
	 *
	 * @hook mt_html_email_h1_styles
	 *
	 * @param {string} $styles HTML styles.
	 *
	 * @return {string}
	 */
	return apply_filters( 'mt_html_email_h1_styles', $styles );
}

/**
 * Write Inline HTML email styles for email body.
 */
function mt_html_email_body_styles() {
	$styles = 'padding:1rem; background: #f3f3f3; color: #111;';
	/**
	 * Filter the wrapper styles used for HTML email messages.
	 *
	 * @hook mt_html_email_h1_styles
	 *
	 * @param {string} $styles HTML styles.
	 *
	 * @return {string}
	 */
	return apply_filters( 'mt_html_email_body_styles', $styles );
}

add_filter( 'mt_format_notes', 'mt_create_event_notes', 10, 3 );
/**
 * Add event specific notes.
 *
 * @param string $event_notes Notes to send with notifications.
 * @param array  $purchased Purchased tickets data.
 * @param int    $payment_id Payment ID.
 *
 * @return string
 */
function mt_create_event_notes( $event_notes, $purchased, $payment_id ) {
	$options = mt_get_settings();
	if ( is_array( $purchased ) ) {
		foreach ( $purchased as $event ) {
			foreach ( $event as $event_id => $tickets ) {
				if ( 'true' === $options['mt_html_email'] ) {
					$notes = wpautop( get_post_meta( $event_id, '_mt_event_notes', true ) );
				} else {
					$notes = get_post_meta( $event_id, '_mt_event_notes', true ) . PHP_EOL . PHP_EOL;
				}
				$event_notes .= apply_filters( 'mt_event_notes', $notes, $payment_id, $event_id );
			}
		}
	}

	return $event_notes;
}

/**
 * Draw template from event data. If My Calendar is installed, use the My Calendar template engine.
 *
 * @param array  $data Data to use in a template.
 * @param string $template Format structure for template.
 *
 * @return string
 */
function mt_draw_template( $data, $template ) {
	if ( function_exists( 'mc_draw_template' ) ) {
		return mc_draw_template( $data, $template );
	} else {
		$template = stripcslashes( $template );
		foreach ( $data as $key => $value ) {
			if ( is_object( $value ) && ! empty( $value ) ) {
				// null values return false.
			} else {
				if ( false !== strpos( $template, '{' . $key ) ) {
					if ( false !== strpos( $template, '{' . $key . ' ' ) ) { // only do preg_match if appropriate.
						preg_match_all( '/{' . $key . '\b(?>\s+(?:before="([^"]*)"|after="([^"]*)"|format="([^"]*)")|[^\s]+|\s+){0,2}}/', $template, $matches, PREG_PATTERN_ORDER );
						if ( $matches ) {
							$number = count( $matches[0] );
							for ( $i = 0; $i < $number; $i++ ) {
								$orig   = $value;
								$before = $matches[1][ $i ];
								$after  = $matches[2][ $i ];
								$format = $matches[3][ $i ];
								if ( '' !== $format ) {
									$value = date_i18n( stripslashes( $format ), strtotime( stripslashes( $value ) ) );
								}
								$value    = ( '' === $value ) ? '' : $before . $value . $after;
								$search   = $matches[0][ $i ];
								$template = str_replace( $search, $value, $template );
								$value    = $orig;
							}
						}
					} else { // don't do preg match (never required for RSS).
						$template = stripcslashes( str_replace( '{' . $key . '}', $value, $template ) );
					}
				} // end {$key check.
			}
		}

		return stripslashes( trim( $template ) );
	}
}

/**
 * Return tickets to available ticket pool if Payment is refunded or trashed.
 *
 * @param int $payment_id Payment ID.
 */
function mt_return_tickets( $payment_id ) {
	$purchases = get_post_meta( $payment_id, '_purchased' );
	if ( is_array( $purchases ) ) {
		foreach ( $purchases as $key => $value ) {
			foreach ( $value as $event_id => $purchase ) {
				if ( false !== get_post_status( $event_id ) ) {
					$registration = get_post_meta( $event_id, '_mt_registration_options', true );
					foreach ( $purchase as $type => $ticket ) {
						if ( ! isset( $registration['prices'][ $type ] ) ) {
							// If this ticket type was removed, it could trigger a fatal error.
							continue;
						}
						// add ticket hash for each ticket.
						$count                                   = $ticket['count'];
						$price                                   = $ticket['price'];
						$sold                                    = $registration['prices'][ $type ]['sold'];
						$new_sold                                = $sold - $count;
						$registration['prices'][ $type ]['sold'] = $new_sold;
						update_post_meta( $event_id, '_mt_registration_options', $registration );
						for ( $i = 0; $i < $count; $i++ ) {
							// delete tickets from system.
							$ticket_id = mt_generate_ticket_id( $payment_id, $event_id, $type, $i, $price );
							delete_post_meta( $event_id, '_ticket', $ticket_id );
							delete_post_meta( $event_id, '_' . $ticket_id );
						}
					}
				}
			}
		}
		update_post_meta( $payment_id, '_returned', 'true' );
	}
}

/**
 * Return a ticket to purchase pool if refunded or deleted.
 *
 * @param string $ticket_id - ID for a single ticket.
 * @param int    $event_id - ID for event ticket was sold for.
 * @param int    $payment_id Payment ID.
 * @param string $type - type of ticket sold.
 */
function mt_return_ticket( $ticket_id, $event_id, $payment_id, $type ) {
	delete_post_meta( $event_id, '_ticket', $ticket_id );
	delete_post_meta( $event_id, '_' . $ticket_id );
	$registration                            = get_post_meta( '_mt_registration_options', true );
	$sold                                    = $registration['prices'][ $type ]['sold'];
	$new_sold                                = $sold + 1;
	$registration['prices'][ $type ]['sold'] = $new_sold;
	update_post_meta( $event_id, '_mt_registration_options', $registration );
}

add_action( 'mt_ticket_type_close_sales', 'mt_notify_admin', 10, 3 );
add_action( 'mt_ticket_sales_closed', 'mt_notify_admin', 10, 3 );
add_action( 'mt_event_sold_out', 'mt_notify_admin', 10, 3 );
/**
 * Send notification to admin when ticket sales are closed.
 *
 * @param int          $event Event ID.
 * @param array|string $ticket_info Event registration data or ticket type.
 * @param string       $context 'closed' or 'soldout'.
 */
function mt_notify_admin( $event, $ticket_info, $context ) {
	$event     = (int) $event;
	$event_url = get_the_permalink( $event );
	$options   = mt_get_settings();
	$email     = $options['mt_to'];
	$blogname  = get_option( 'blogname' );
	$headers[] = "From: $blogname Events <" . $options['mt_from'] . '>';
	$headers[] = "Reply-to: $options[mt_from]";
	apply_filters( 'mt_filter_email_headers', $headers, $event );
	$title     = get_the_title( $event );
	$inventory = mt_check_inventory( $event, '', false );
	$download  = admin_url( "admin.php?page=mt-reports&amp;event_id=$event&amp;format=csv&amp;mt-event-report=purchases" );
	$tickets   = admin_url( "admin.php?page=mt-reports&amp;event_id=$event&amp;format=csv&amp;mt-event-report=tickets" );
	if ( 'type' === $context ) {
		$download = add_query_arg( 'mt_select_ticket_type', $ticket_info, $download );
		$tickets  = add_query_arg( 'mt_select_ticket_type', $ticket_info, $tickets );
	}
	if ( 'closed' === $context || 'type' === $context ) {
		if ( 'type' === $context ) {
			$title = $title . ' (' . $ticket_info . ')';
		}
		/**
		 * Filter the "ticket sales closed" email subject sent to administrators.
		 *
		 * @hook mt_closure_subject
		 *
		 * @param {string} $subject Email subject line.
		 * @param {int}    $event Event Post ID.
		 *
		 * @return {string}
		 */
		$subject = apply_filters(
			'mt_closure_subject',
			sprintf(
				// Translators: Name of event being closed.
				__( 'Ticket sales for %s are now closed', 'my-tickets' ),
				$title
			),
			$event
		);
		$subject = mb_encode_mimeheader( $subject );
		if ( $inventory && 0 === (int) $inventory['sold'] ) {
			// Translators: Name of event closed.
			$body = apply_filters( 'mt_closure_body', sprintf( __( 'Online ticket sales for %1$s are now closed with no online sales.', 'my-tickets' ), $title ), $event, 'no-sales' );
		} else {
			/**
			 * Filter the "ticket sales closed" email body sent to administrators.
			 *
			 * @hook mt_closure_body
			 *
			 * @param {string} $body Email body text.
			 * @param {int}    $event Event Post ID.
			 * @param {string} $type Message type. 'has-sales' or 'no-sales'.
			 *
			 * @return {string}
			 */
			$body = apply_filters(
				'mt_closure_body',
				sprintf(
					// Translators: Name of event closed; link to download list of purchase; link to download list of tickets.
					'<p>' . __( 'Online ticket sales for %1$s are now closed. <a href="%2$s">Download the purchases list</a> <a href="%3$s">Download the tickets list</a>', 'my-tickets' ) . '</p><p>' . $event_url . '</p>',
					$title,
					$download,
					$tickets
				),
				$event,
				'has-sales'
			);
		}
	}
	if ( 'soldout' === $context ) {
		/**
		 * Filter the "ticket sales sold out" email subject sent to administrators.
		 *
		 * @hook mt_soldout_subject
		 *
		 * @param {string} $subject Email subject line.
		 * @param {int}    $event Event Post ID.
		 *
		 * @return {string}
		 */
		$subject = apply_filters(
			'mt_soldout_subject',
			sprintf(
				// Translators: Name of event soldout.
				__( '%s has sold out. Ticket sales are now closed.', 'my-tickets' ),
				strip_tags( $title )
			),
			$event
		);
		$subject = mb_encode_mimeheader( $subject );
		/**
		 * Filter the "ticket sales sold out" email body sent to administrators.
		 *
		 * @hook mt_soldout_body
		 *
		 * @param {string} $body Email body text.
		 * @param {int}    $event Event Post ID.
		 *
		 * @return {string}
		 */
		$body = apply_filters(
			'mt_soldout_body',
			sprintf(
				// Translators: Name of event closed; link to download list of purchase; link to download list of tickets.
				'<p>' . __( '%1$s has sold out, and ticket sales are now closed. <a href="%2$s">Download the purchases list</a> <a href="%3$s">Download the tickets list</a>', 'my-tickets' ) . '</p><p>' . $event_url . '</p>',
				strip_tags( $title ),
				$download,
				$tickets
			),
			$event
		);
	}
	/**
	 * Filter the recipient of admin closure and sold out email notifications.
	 *
	 * @hook mt_closure_recipient
	 *
	 * @param {string} $email Email address of recipient.
	 * @param {id}     $event Event Post ID.
	 *
	 * @return {string}
	 */
	$to = apply_filters( 'mt_closure_recipient', $email, $event );
	add_filter( 'wp_mail_content_type', 'mt_html_type' );
	$body = apply_filters( 'mt_modify_email_body', $body, 'admin' );
	wp_mail( $to, $subject, $body, $headers );
	remove_filter( 'wp_mail_content_type', 'mt_html_type' );
}

/**
 * Return string for HTML email types
 */
function mt_html_type() {

	return 'text/html';
}

// Use Codemirror for email fields when enabled.
add_action(
	'admin_enqueue_scripts',
	function () {
		if ( ! function_exists( 'wp_enqueue_code_editor' ) ) {
			return;
		}
		if ( 'toplevel_page_my-tickets' === get_current_screen()->id || 'my-tickets_page_mt-reports' === get_current_screen()->id ) {

			// Enqueue code editor and settings for manipulating HTML.
			$settings = wp_enqueue_code_editor(
				array(
					'type'       => 'text/html',
					'codemirror' => array(
						'autoRefresh' => true,
					),
				)
			);

			// Bail if user disabled CodeMirror or using default styles.
			$options = mt_get_settings();
			if ( false === $settings || 'true' !== $options['mt_html_email'] ) {
				return;
			}
			if ( 'toplevel_page_my-tickets' === get_current_screen()->id ) {
				wp_add_inline_script(
					'code-editor',
					sprintf(
						'jQuery( function() { wp.codeEditor.initialize( "mt_messages_completed_admin_body", %s ); } );',
						wp_json_encode( $settings )
					)
				);
				wp_add_inline_script(
					'code-editor',
					sprintf(
						'jQuery( function() { wp.codeEditor.initialize( "mt_messages_completed_purchaser_body", %s ); } );',
						wp_json_encode( $settings )
					)
				);
				wp_add_inline_script(
					'code-editor',
					sprintf(
						'jQuery( function() { wp.codeEditor.initialize( "mt_messages_failed_admin_body", %s ); } );',
						wp_json_encode( $settings )
					)
				);
				wp_add_inline_script(
					'code-editor',
					sprintf(
						'jQuery( function() { wp.codeEditor.initialize( "mt_messages_failed_purchaser_body", %s ); } );',
						wp_json_encode( $settings )
					)
				);
				wp_add_inline_script(
					'code-editor',
					sprintf(
						'jQuery( function() { wp.codeEditor.initialize( "mt_messages_refunded_admin_body", %s ); } );',
						wp_json_encode( $settings )
					)
				);
				wp_add_inline_script(
					'code-editor',
					sprintf(
						'jQuery( function() { wp.codeEditor.initialize( "mt_messages_refunded_purchaser_body", %s ); } );',
						wp_json_encode( $settings )
					)
				);
				wp_add_inline_script(
					'code-editor',
					sprintf(
						'jQuery( function() { wp.codeEditor.initialize( "mt_messages_interim_admin_body", %s ); } );',
						wp_json_encode( $settings )
					)
				);
				wp_add_inline_script(
					'code-editor',
					sprintf(
						'jQuery( function() { wp.codeEditor.initialize( "mt_messages_interim_purchaser_body", %s ); } );',
						wp_json_encode( $settings )
					)
				);
			} else {
				wp_add_inline_script(
					'code-editor',
					sprintf(
						'jQuery( function() { wp.codeEditor.initialize( "mt_body", %s ); } );',
						wp_json_encode( $settings )
					)
				);
			}
		}
	}
);
