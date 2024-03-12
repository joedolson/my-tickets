<?php
/**
 * Add to Cart form.
 *
 * @category Core
 * @package  My Tickets
 * @author   Joe Dolson
 * @license  GPLv2 or later
 * @link     https://www.joedolson.com/my-tickets/
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
} // Exit if accessed directly.

/**
 * Appends a registration form to post content for posts with defined event data.
 *
 * @uses function mt_add_to_cart_form();
 * @param string $content Post Content.
 *
 * @return string
 */
function mt_add_to_cart_form_post( $content ) {
	$options = mt_get_settings();
	global $post;
	$only_singular = $options['mt_singular'];
	if ( $only_singular && ! is_singular( $options['mt_post_types'] ) ) {
		return $content;
	}
	if ( in_array( get_post_type( $post ), $options['mt_post_types'], true ) ) {
		$event = $post->ID;
		if ( get_post_meta( $event, '_mc_event_data', true ) ) {
			$content = mt_add_to_cart_form( $content, $event );
		}
	}

	return $content;
}
add_filter( 'the_content', 'mt_add_to_cart_form_post', 20, 1 ); // after wpautop.

/**
 * Test whether a price set for an event has any tickets available for purchase.
 *
 * @param array $pricing Events pricing array.
 *
 * @return bool
 */
function mt_has_tickets( $pricing ) {
	$return = false;
	if ( is_array( $pricing ) ) {
		foreach ( $pricing as $options ) {
			$tickets = (int) $options['tickets'];
			if ( $tickets > 0 ) {
				$return = true;
			}
		}

		return $return;
	}

	return false;
}

/**
 * Check add to cart form exit conditions. Returns the event registration array if all conditions pass.
 *
 * @param int  $event_id Event post ID.
 * @param bool $override Is the add to cart form output being overridden.
 *
 * @return bool|array
 */
function mt_check_early_returns( $event_id, $override ) {
	$options       = mt_get_settings();
	$purchase_page = $options['mt_purchase_page'];
	$receipt_page  = $options['mt_receipt_page'];
	$tickets_page  = $options['mt_tickets_page'];
	if ( is_page( $purchase_page ) || is_page( $receipt_page ) || is_page( $tickets_page ) || ! $event_id ) {
		return false;
	}
	$only_singular = $options['mt_singular'];
	if ( $only_singular && ! is_singular( $options['mt_post_types'] ) ) {
		return false;
	}
	if ( 'mc-events' === get_post_type( $event_id ) ) {
		$sell = get_post_meta( $event_id, '_mt_sell_tickets', true );
		if ( 'false' === $sell ) {
			return false;
		}
	}
	if ( 'true' === get_post_meta( $event_id, '_mt_hide_registration_form', true ) && false === $override ) {
		return false;
	}
	$registration = get_post_meta( $event_id, '_mt_registration_options', true );
	// if no 'total' is set at all, this is not an event with tickets.
	if ( empty( $registration['prices'] ) ) {
		return false;
	}
	// if total is set to inherit, but any ticket class has no defined number of tickets available, return. '0' is a valid number of tickets, '' is not.
	if ( ( isset( $registration['total'] ) && 'inherit' === $registration['total'] ) && 'general' !== $registration['counting_method'] && ! mt_has_tickets( $registration['prices'] ) ) {
		return false;
	}

	// if total number of tickets is set but is an empty string or is not set; return.
	if ( ( isset( $registration['total'] ) && '' === trim( $registration['total'] ) ) || ! isset( $registration['total'] ) ) {
		return false;
	}

	return $registration;
}

/**
 * Generates event registration form.
 *
 * @param string          $content Page content.
 * @param bool|int|object $event If boolean, exit.
 * @param string          $view Type of view for context.
 * @param string          $time Time view being displayed.
 * @param bool            $override Don't display.
 * @param bool|array      $group If grouped display, array with first and last IDs.
 *
 * @return string
 */
function mt_add_to_cart_form( $content, $event = false, $view = 'calendar', $time = 'month', $override = false, $group = false ) {
	$options  = mt_get_settings();
	$event_id = ( is_object( $event ) ) ? $event->event_post : $event;
	$continue = mt_check_early_returns( $event_id, $override );
	if ( ! $continue ) {
		return $content;
	} else {
		$registration = $continue;
	}

	$form        = '';
	$sold_out    = '';
	$has_tickets = '';
	$output      = '';
	$expired     = mt_expired( $event_id, true );
	$no_postal   = mt_no_postal( $event_id );
	if ( $no_postal && 1 === count( $options['mt_ticketing'] ) && in_array( 'postal', $options['mt_ticketing'], true ) && ! ( current_user_can( 'mt-order-expired' ) || current_user_can( 'manage_options' ) ) ) {
		$expired = true;
	}
	$handling_notice = '';
	if ( ! $expired ) {
		if ( is_array( $registration ) ) {
			$pricing = $registration['prices'];
			$nonce   = wp_nonce_field( 'mt-cart-nonce', '_wpnonce', true, false );
			if ( isset( $_GET['mc_id'] ) ) {
				$mc_id     = (int) $_GET['mc_id'];
				$permalink = add_query_arg( 'mc_id', $mc_id, get_the_permalink() );
			} else {
				$permalink = get_the_permalink();
			}
			if ( is_array( $pricing ) ) {
				/**
				 * Filter default number of tickets considered available under General Admission. General Admission uses a floating value of available tickets, so there's no real limit; this value does limit the number of tickets that can be purchased at once, however.
				 *
				 * @hook mt_default_available
				 *
				 * @param {int} $default_available Number of tickets available.
				 * @param {array} $registration Ticketing rules for this event.
				 *
				 * @return {int}
				 */
				$default_available = apply_filters( 'mt_default_available', 100, $registration );
				$available         = ( 'general' === $registration['counting_method'] ) ? $default_available : $registration['total'];
				$tickets_data      = mt_check_inventory( $event_id );
				$tickets_remaining = ( 'general' === $registration['counting_method'] ) ? $default_available : mt_check_inventory( $event_id )['available'];
				$tickets_sold      = $tickets_data['sold'];
				/**
				 * Filter when online ticket sales should close based on availability. Default 0; sales close when sold out.
				 *
				 * @hook mt_tickets_close_value
				 *
				 * @param {int}   $tickets_close_value Number of tickets remaining that triggers sold out condition.
				 * @param {int}   $event_id Event ID.
				 * @param {array} $tickets_data Data about sold and available tickets.
				 *
				 * @return {int}
				 */
				$close_value = apply_filters( 'mt_tickets_close_value', 0, $event_id, $tickets_data );
				if ( $tickets_remaining && $tickets_remaining > $close_value ) {
					$sold_out    = false;
					$total_order = 0;
					foreach ( $pricing as $type => $ticket_type ) {
						$row = mt_ticket_row( $event_id, $registration, $ticket_type, $type, $available, $tickets_remaining );
						if ( $row ) {
							$form           .= $row['form'];
							$handling_notice = $row['handling'];
							$total_order    += (int) $row['value'];
							$has_tickets     = $row['has_tickets'];
						}
					}
				} else {
					if ( 0 >= $tickets_remaining ) {
						$sold_out = true;
					} else {
						$output = '<p>' . mt_tickets_remaining( $tickets_data, $event_id ) . '</p>';
					}
				}
			}
			$remaining_notice = mt_remaining_tickets_notice( $event_id, $available, $tickets_remaining );
			// Translators: link to shopping cart/checkout.
			$in_cart = ( mt_in_cart( $event_id ) ) ? '<p class="my-tickets-in-cart">' . sprintf( __( 'Tickets for this event are in your cart. <a href="%s">Checkout</a>', 'my-tickets' ), get_permalink( $options['mt_purchase_page'] ) ) . '</p>' : '';

			if ( true === $has_tickets ) {
				$closing_time = mt_sales_close( $event_id, $registration['reg_expires'] );
				$no_post      = ( $no_postal && in_array( 'postal', array_keys( $options['mt_ticketing'] ), true ) ) ? "<p class='mt-no-post'>" . apply_filters( 'mt_cannot_send_by_email_text', __( 'Tickets for this event cannot be sent by mail.', 'my-tickets' ) ) . '</p>' : '';
				$legend       = ( 'registration' === $registration['sales_type'] ) ? __( 'Register', 'my-tickets' ) : __( 'Buy Tickets', 'my-tickets' );
				$legend       = apply_filters( 'mt_button_legend_text', $legend, $registration );
				$disabled     = ( $total_order > $tickets_remaining ) ? " disabled='disabled'" : '';
				/**
				 * Filter hidden fields added to My Tickets add to cart form.
				 *
				 * @hook mt_add_to_cart_hidden_fields
				 *
				 * @param {string} $hidden HTML output. Default empty.
				 * @param {int}    $event_id Event ID.
				 *
				 * @return {string}
				 */
				$hidden = apply_filters( 'mt_add_to_cart_hidden_fields', '', $event_id );
				/**
				 * Filter visible fields added to My Tickets add to cart form. Inserted between standard fields and submit button.
				 *
				 * @hook mt_add_to_cart_fields
				 *
				 * @param {string} $fields HTML output. Default empty.
				 * @param {int}    $event_id Event ID.
				 *
				 * @return {string}
				 */
				$fields = apply_filters( 'mt_add_to_cart_fields', '', $event_id );
				$output = "
			<div class='mt-order my-tickets'>
				<div class='mt-response' id='mt-response-$event_id' aria-live='assertive'></div>
				$no_post
				$closing_time
				$handling_notice
				<form action='" . esc_url( $permalink ) . "' method='POST' class='ticket-orders' id='order-tickets' tabindex='-1'>
					<div>
						$nonce
						<input type='hidden' name='mt_event_id' value='$event_id' />" . $hidden . "
					</div>
					<fieldset>
					<legend>$legend</legend>
						$in_cart
						$remaining_notice
						$form
						$fields" . "<p>
						<button type='submit' name='mt_add_to_cart'" . $disabled . '>' . apply_filters( 'mt_add_to_cart_text', __( 'Add to Cart', 'my-tickets' ) ) . "<span class='mt-processing'><img src='" . admin_url( 'images/spinner-2x.gif' ) . "' alt='" . __( 'Working', 'my-tickets' ) . "' /></span></button>
						<input type='hidden' name='my-tickets' value='true' />
						</p>
					</fieldset>
				</form>
			</div>";
			}
		}
	} else {
		$registration        = get_post_meta( $event_id, '_mt_registration_options', true );
		$tickets_remaining   = mt_check_inventory( $event_id );
		$tickets_remain_text = mt_tickets_remaining( $tickets_remaining, $event_id );
		$sales_closed        = ( 'registration' === $registration['sales_type'] ) ? __( 'Online registration for this event is closed', 'my-tickets' ) : __( 'Online ticket sales for this event are closed.', 'my-tickets' );
		$output              = "<div class='mt-order mt-closed'><p>" . apply_filters( 'mt_sales_closed', $sales_closed ) . "$tickets_remain_text</p></div>";
	}

	if ( true === $sold_out && $tickets_sold > 0 ) {
		$tickets_soldout = ( 'registration' === $registration['sales_type'] ) ? __( 'Registration for this event is full', 'my-tickets' ) : __( 'Tickets for this event are sold out.', 'my-tickets' );
		$output          = "<div class='mt-order mt-soldout'><p>" . apply_filters( 'mt_tickets_soldout', $tickets_soldout ) . '</p></div>';
		/**
		 * Append additional content to the tickets sold out notification.
		 *
		 * @hook mt_tickets_soldout_content
		 *
		 * @param {string} $output HTML output; default empty string.
		 * @param {int}    $event_id
		 * @param {array}  $registration Registration data for event.
		 *
		 * @return {string}
		 */
		$output .= apply_filters( 'mt_tickets_soldout_content', '', $event_id, $registration );
		$soldout = get_post_meta( $event_id, '_mt_event_soldout', true );
		if ( 'true' !== $soldout ) {
			update_post_meta( $event_id, '_mt_event_soldout', 'true' );
			do_action( 'mt_event_sold_out', $event_id, $registration, 'soldout' );
		}
	}

	return $content . $output;
}
add_filter( 'mc_after_event', 'mt_add_to_cart_form', 5, 4 );

/**
 * Format a date for display.
 *
 * @param string $date Date string.
 *
 * @return string
 */
function mt_format_date( $date ) {
	/**
	 * Filter the date format used in add to cart and cart.
	 *
	 * @hook mt_cart_date_format
	 *
	 * @param {string} $date_format Default from WordPress settings.
	 *
	 * @return {string}
	 */
	$date_format = apply_filters( 'mt_cart_date_format', get_option( 'date_format' ) );
	/**
	 * Filter the time format used in add to cart and cart.
	 *
	 * @hook mt_cart_time_format
	 *
	 * @param {string} $time_format Default from WordPress settings.
	 *
	 * @return {string}
	 */
	$time_format = apply_filters( 'mt_cart_time_format', get_option( 'time_format' ) );
	$date_string = mt_date( $date_format, strtotime( $date ), false );
	$time_string = mt_date( $time_format, strtotime( $date ), false );

	return '<strong>' . $date_string . '</strong><span>, </span>' . $time_string;
}

/**
 * Generate a single row in the add to cart form.
 *
 * @param int        $event_id Event post ID.
 * @param array      $registration Registration settings for this event.
 * @param array      $ticket_type Settings for this row.
 * @param string     $type Type of ticket.
 * @param int|string $available Number of tickets available or 'inherit'.
 * @param int        $tickets_remaining Number of tickets left for purchase.
 *
 * @return array|bool 'false' if ticket is not purchaseable.
 */
function mt_ticket_row( $event_id, $registration, $ticket_type, $type, $available, $tickets_remaining ) {
	$options   = mt_get_settings();
	$pricing   = $registration['prices'];
	$cart_data = mt_in_cart( $event_id );
	if ( ! is_array( $cart_data ) ) {
		$cart_data = '';
	}
	// if multiple != true, use checkboxes.
	$input_type = ( isset( $registration['multiple'] ) && 'true' === $registration['multiple'] ) ? 'number' : 'checkbox';
	$class      = 'mt-available';
	$form       = '';
	/**
	 * Filter value data about a specific type of ticket.
	 *
	 * @hook mt_ticket_settings
	 *
	 * @param {array}  $ticket_type Price and availability for a ticket type.
	 * @param {array}  $pricing Full pricing array being iterated.
	 * @param {int}    $event_id Event ID.
	 * @param {strnig} $type Current ticket type.
	 */
	$ticket_type = apply_filters( 'mt_ticket_settings', $ticket_type, $pricing, $event_id, $type );
	if ( ! mt_can_order( $type ) ) {
		return false;
	}
	$extra_label = '';
	if ( mt_admin_only( $type ) ) {
		$extra_label = ' <span class="mt-admin-only">(' . __( 'Administrators only', 'my-tickets' ) . ')</span>';
	}
	/**
	 * Add additional labeling appended to ticket type information.
	 *
	 * @hook mt_extra_label
	 *
	 * @param {string}     $extra_label Default empty string; 'Administrators only' for complimentary tickets.
	 * @param {int|object} $event Event post ID.
	 * @param {string}     $type Ticket type.
	 *
	 * @return {string}
	 */
	$extra_label = apply_filters( 'mt_extra_label', $extra_label, $event_id, $type );
	if ( $type ) {
		if ( ! isset( $ticket_type['price'] ) ) {
			return false;
		}
		$price = mt_calculate_discount( $ticket_type['price'], $event_id );
		$price = mt_handling_price( $price, $event_id, $type );
		$price = apply_filters( 'mt_money_format', $price );
		/**
		 * Filter ticket handling price.
		 *
		 * @hook mt_ticket_handling_price
		 *
		 * @param {string}     $ticket_handling Handling price from settings.
		 * @param {int}object} $event Event post ID.
		 * @param {string}     $type Ticket type.
		 */
		$ticket_handling    = apply_filters( 'mt_ticket_handling_price', $options['mt_ticket_handling'], $event_id, $type );
		$handling_notice    = mt_handling_notice();
		$ticket_price_label = '<span class="mt-ticket-price">' . apply_filters( 'mt_ticket_price_label', $price, $ticket_type['price'], $ticket_handling ) . '</span>';
		$value              = ( is_array( $cart_data ) && isset( $cart_data[ $type ] ) ) ? $cart_data[ $type ] : apply_filters( 'mt_cart_default_value', '0', $type );
		$value              = ( '' === $value ) ? 0 : (int) $value;
		$order_value        = $value;
		$attributes         = '';
		$close              = ( isset( $ticket_type['close'] ) && ! empty( $ticket_type['close'] ) ) ? $ticket_type['close'] : '';
		$type_sales_closed  = false;
		$closure            = '';
		$stop               = (int) $registration['reg_expires'];
		if ( $close && ( $close + $stop ) < time() ) {
			$show_closed = 'hide';
			if ( 'true' === $options['mt_show_closed'] ) {
				$show_closed = 'show';
			}
			/**
			 * Filter whether a ticket type that has closed will be shown for an event.
			 *
			 * @hook mt_ticket_type_sales_closed
			 *
			 * @param {string} $show_closed 'hide' or 'show'.
			 * @param {int}    $event_id ID for currently displayed post.
			 *
			 * @return {string}
			 */
			$ticket_type_sales_closed_behavior = apply_filters( 'mt_ticket_type_sales_closed', $show_closed, $event_id );
			if ( 'hide' === $ticket_type_sales_closed_behavior ) {
				// If this ticket type is no longer available, skip.
				return false;
			} else {
				$type_sales_closed = true;
				// translators: Date ticket sales closed.
				$closure = sprintf( __( 'Sales closed %s', 'my-tickets' ), date_i18n( get_option( 'date_format' ), $close ) );
			}
		}
		if ( 'checkbox' === $input_type || 'radio' === $input_type ) {
			if ( 1 === $value ) {
				$attributes = " checked='checked'";
			}
			$value       = 1;
			$order_value = 0;
		}
		$button_up   = ( 'number' === $input_type ) ? '<button type="button" class="mt-increment"><span class="dashicons dashicons-plus" aria-hidden="true"></span><span class="screen-reader-text">' . __( 'Add one', 'my-tickets' ) . '</span></button>' : '';
		$button_down = ( 'number' === $input_type ) ? '<button type="button" class="mt-decrement"><span class="dashicons dashicons-minus" aria-hidden="true"></span><span class="screen-reader-text">' . __( 'Remove one', 'my-tickets' ) . '</span></button>' : '';

		if ( 'inherit' === $available ) {
			$inventory = mt_check_inventory( $event_id, $type );
			$tickets   = absint( $ticket_type['tickets'] );
			$label     = ( 'event' === $registration['counting_method'] ) ? mt_format_date( $ticket_type['label'] ) : $ticket_type['label'];
			$remaining = $inventory['available'];
			/**
			 * Filter maximum sale per event. Limits number of tickets that can be purchased at a time.
			 *
			 * @hook mt_max_sale_per_event
			 *
			 * @param {bool} $max_limit Default false.
			 *
			 * @return {bool|int} Number of tickets that can be purchased at once or false.
			 */
			$max_limit = apply_filters( 'mt_max_sale_per_event', false );
			if ( $max_limit ) {
				$max = ( $max_limit > $remaining ) ? $remaining : $max_limit;
			} else {
				$max = $remaining;
			}
			$disable = ( $remaining < 1 || $type_sales_closed ) ? ' disabled="disabled"' : '';
			if ( '' === $attributes ) {
				$attributes = " min='0' max='$max' inputmode='numeric' pattern='[0-9]*'";
				if ( 0 === $remaining || $type_sales_closed ) {
					$attributes .= ' readonly="readonly"';
					$class       = ( $type_sales_closed ) ? 'mt-sales-closed' : 'mt-sold-out';
				} else {
					$class = 'mt-available';
				}
			}
			$form .= "<div class='mt-ticket-field mt-ticket-$type $class'><label for='mt_tickets_$type" . '_' . "$event_id' id='mt_tickets_label_$type" . '_' . "$event_id'>" . $label . $extra_label . '</label>';
			$form .= apply_filters(
				'mt_add_to_cart_input',
				"<div class='mt-ticket-input'><input type='$input_type' name='mt_tickets[$type]' id='mt_tickets_$type" . '_' . "$event_id' class='tickets_field' value='$value' $attributes aria-labelledby='mt_tickets_label_$type" . '_' . $event_id . " mt_tickets_data_$type'$disable />$button_up$button_down</div>",
				$input_type,
				$type,
				$value,
				$attributes,
				$disable,
				$max,
				$available
			);

			$hide_remaining = mt_hide_remaining( $tickets_remaining );
			// Translators: 1 Ticket price label, 2 number remaining as fraction e.g. 2/40, 3 closing span tag..
			$remaining_text = sprintf( apply_filters( 'mt_tickets_remaining_discrete_text', __( '%1$s %2$s remaining%3$s', 'my-tickets' ), $ticket_price_label, $remaining, $tickets ), $ticket_price_label . '<span class="tickets-remaining">', "<span class='value remaining-tickets'>" . $remaining . "</span>/<span class='ticket-count'>" . $tickets . '</span>', '</span>' );
			// Translators: 1 ticket price label, 2 number remaining as integer, 3 closing span tag.
			$available_text = sprintf( apply_filters( 'mt_tickets_available_discrete_text', __( '%1$s %2$s available%3$s', 'my-tickets' ), $ticket_price_label, $remaining, $tickets ), $ticket_price_label . '<span class="tickets-remaining tickets-available">', "<span class='value available-tickets'>" . $remaining . '</span>', '</span>' );
			if ( 'proportion' === $options['mt_display_remaining'] ) {
				$display_text = $remaining_text;
			} else {
				$display_text = $available_text;
			}

			$form .= "<div id='mt_tickets_data_$type' class='ticket-pricing$hide_remaining'>" . $display_text . '<span class="mt-closure-date">' . $closure . '</span></div>';
			$form .= "<span class='mt-error-notice' aria-live='assertive'></span></div>";
		} else {
			$remaining = $tickets_remaining;
			if ( '' === $attributes ) {
				$attributes = " min='0' max='$remaining'";
				if ( 0 === $remaining ) {
					$attributes .= ' readonly="readonly"';
					$class       = 'mt-sold-out';
				}
			}
			/**
			 * Filter whether the price should be shown in the label or as an aria-described field after the input.
			 *
			 * @hook mt_price_in_label
			 *
			 * @param {false} $price_in_label Default false.
			 * @param {int}   $event_id Event ID.
			 *
			 * @return {bool}
			 */
			$price_in_label = apply_filters( 'mt_price_in_label', false, $event_id );
			$price_class    = ( $price_in_label ) ? 'mt-price-in-label' : '';
			$price          = "<span id='mt_tickets_data_$type'>$ticket_price_label</span>";
			$label_price    = ( $price_in_label ) ? ' <span class="mt-label-price">' . strip_tags( $price ) . '</span>' : '';
			$post_price     = ( ! $price_in_label ) ? $price : '';
			$form          .= "<div class='mt-ticket-field mt-ticket-$type $class $price_class'><label for='mt_tickets_$type" . '_' . "$event_id' id='mt_tickets_label_$type" . '_' . "$event_id'>" . esc_attr( $ticket_type['label'] ) . $extra_label . $label_price . '</label>';
			$form          .= apply_filters(
				'mt_add_to_cart_input',
				"<div class='mt-ticket-input'><input type='$input_type' name='mt_tickets[$type]' $attributes id='mt_tickets_$type" . '_' . "$event_id' class='tickets_field' value='$value' aria-labelledby='mt_tickets_label_$type" . '_' . $event_id . " mt_tickets_data_$type' />$button_up$button_down</div>",
				$input_type,
				$type,
				$value,
				$attributes,
				'',
				$remaining,
				$available
			);
			$form          .= $post_price . "<span class='mt-error-notice' aria-live='assertive'></span></div>";
		}
		$has_tickets = true;
	}

	return array(
		'form'        => $form,
		'value'       => $order_value,
		'has_tickets' => $has_tickets,
		'handling'    => $handling_notice,
	);
}

/**
 * Return the remaining tickets notice for an event.
 *
 * @param int        $event_id Event post ID.
 * @param string|int $available Number of tickets available or type of availability if inherited.
 * @param int        $tickets_remaining Number of tickets remaining.
 *
 * @return string
 */
function mt_remaining_tickets_notice( $event_id, $available, $tickets_remaining ) {
	$remaining_notice = '';
	if ( 'inherit' !== $available ) {
		// If this event is general admission, then never show number of tickets remaining or status.
		$data = get_post_meta( $event_id, '_mc_event_data', true );
		if ( isset( $data['general_admission'] ) && 'on' === $data['general_admission'] ) {
			$hide_remaining = ' hiding';
		} else {
			$hide_remaining = mt_hide_remaining( $tickets_remaining );
		}
		// Translators: tickets remaining.
		$remaining_notice = '<p class="tickets-remaining' . $hide_remaining . '">' . sprintf( apply_filters( 'mt_tickets_remaining_continuous_text', __( '%s tickets remaining.', 'my-tickets' ) ), "<span class='value'>" . $tickets_remaining . '</span>' ) . '</p>';
	}

	return $remaining_notice;
}

/**
 * Get current status of an event.
 *
 * @param int $event_id Event ID.
 *
 * @return string
 */
function mt_event_status( $event_id = false ) {
	// Exit conditions.
	$options       = mt_get_settings();
	$purchase_page = $options['mt_purchase_page'];
	$receipt_page  = $options['mt_purchase_page'];
	$tickets_page  = $options['mt_tickets_page'];
	if ( is_page( $purchase_page ) || is_page( $receipt_page ) || is_page( $tickets_page ) ) {
		return '';
	}
	if ( ! $event_id ) {
		return '';
	}
	if ( 'mc-events' === get_post_type( $event_id ) ) {
		$sell = get_post_meta( $event_id, '_mt_sell_tickets', true );
		if ( 'false' === $sell ) {
			return '';
		}
	}

	if ( 'true' === get_post_meta( $event_id, '_mt_hide_registration_form', true ) ) {
		return '';
	}
	$registration = get_post_meta( $event_id, '_mt_registration_options', true );
	// if no 'total' is set at all, this is not an event with tickets.
	if ( empty( $registration['prices'] ) ) {
		return '';
	}

	// if total is set to inherit, but any ticket class has no defined number of tickets available, return. '0' is a valid number of tickets, '' is not.
	if ( ( isset( $registration['total'] ) && 'inherit' === $registration['total'] ) && ! mt_has_tickets( $registration['prices'] ) ) {
		return '';
	}

	// if total number of tickets is set but is an empty string or is not set; return.
	if ( ( isset( $registration['total'] ) && '' === trim( $registration['total'] ) ) || ! isset( $registration['total'] ) ) {
		return '';
	}

	// If this event is general admission, then never show number of tickets remaining or status.
	$data = get_post_meta( $event_id, '_mc_event_data', true );
	if ( isset( $data['general_admission'] ) && 'on' === $data['general_admission'] ) {
		return '';
	}

	$expired           = ( mt_expired( $event_id ) ) ? __( 'Sales closed', 'my-tickets' ) : '';
	$registration      = get_post_meta( $event_id, '_mt_registration_options', true );
	$tickets_remaining = mt_check_inventory( $event_id );
	// Translators: Number of tickets remaining.
	$remaining = ( 0 >= $tickets_remaining['available'] ) ? $expired : sprintf( __( '%s tickets remaining', 'my-tickets' ), '<strong>' . $tickets_remaining['available'] . '</strong>' );
	$sold_out  = ( 0 >= $tickets_remaining['available'] ) ? __( 'Sold out', 'my-tickets' ) : $remaining;

	return $sold_out;
}

/**
 * Figure whether tickets should be hidden.
 *
 * @param int $tickets_remaining Number of tickets remaining.
 *
 * @return string
 */
function mt_hide_remaining( $tickets_remaining ) {
	$options = mt_get_settings();
	// If hide remaining is enabled, set as hidden.
	$hide_remaining  = ( isset( $options['mt_hide_remaining'] ) && 'true' === $options['mt_hide_remaining'] ) ? ' hiding' : '';
	$remaining_limit = isset( $options['mt_hide_remaining_limit'] ) ? absint( $options['mt_hide_remaining_limit'] ) : 0;
	// Hide tickets if there are more than x tickets available if limit is set.
	$hide_limiting = ( isset( $options['mt_hide_remaining_limit'] ) && 0 !== $remaining_limit && ( $tickets_remaining > $remaining_limit ) ) ? ' hiding' : '';

	return ( isset( $options['mt_hide_remaining_limit'] ) ) ? $hide_limiting : $hide_remaining;
}
/**
 * Test whether the current ticket type is admin-only
 *
 * @param string $type Type of ticket being used.
 *
 * @return boolean
 */
function mt_admin_only( $type ) {
	if ( ( 'complementary' === $type || 'complimentary' === $type ) ) {
		return true;
	}

	/**
	 * Set a ticket type as only available to administrators. Return true to hide ticket types from the public.
	 *
	 * @hook mt_admin_only_ticket
	 *
	 * @param {false} $admin_only False to indicate a ticket type is available to the public.
	 * @param {string} $type Ticket type key.
	 *
	 * @return {bool}
	 */
	return apply_filters( 'mt_admin_only_ticket', false, $type );
}

/**
 * Test whether the current user can order the current ticket type.
 *
 * @param string $type Type of ticket being handled.
 *
 * @return bool
 */
function mt_can_order( $type ) {
	if ( ! mt_admin_only( $type ) ) {
		return true;
	}
	$comps = ( current_user_can( 'mt-order-comps' ) || current_user_can( 'manage_options' ) ) ? true : false;
	if ( mt_admin_only( $type ) && $comps ) {
		return true;
	}

	return false;
}

/**
 * Produce notice about tickets remaining after sales are closed.
 *
 * @param array   $tickets_data array of ticket sales data.
 * @param integer $event_id Event ID.
 *
 * @return string Notice
 */
function mt_tickets_remaining( $tickets_data, $event_id ) {
	$tickets_remaining = $tickets_data['available'];
	/**
	 * Filter when online ticket sales should close based on availability. Default 0; sales close when sold out.
	 *
	 * @hook mt_tickets_close_value
	 *
	 * @param {int}   $tickets_close_value Number of tickets remaining that triggers sold out condition.
	 * @param {int}   $event_id Event ID.
	 * @param {array} $tickets_data Data about sold and available tickets.
	 *
	 * @return {int}
	 */
	$close_value = apply_filters( 'mt_tickets_close_value', 0, $event_id, $tickets_data );
	if ( $tickets_remaining && $tickets_remaining > $close_value ) {
		$tickets_remain_text = '';
	} else {
		if ( $tickets_remaining > 0 ) {
			// Translators: number of tickets available.
			$tickets_remain_text = ' ' . sprintf( apply_filters( 'mt_tickets_still_remaining_text', _n( 'Online sales are closed, but there is still %d ticket available at the box office!', 'Online sales are closed, but there are still %d tickets available at the box office!', $tickets_remaining, 'my-tickets' ) ), $tickets_remaining );
		} else {
			$tickets_remain_text = '';
		}
	}

	return $tickets_remain_text;
}

add_filter( 'mt_tickets_close_value', 'mt_close_ticket_sales', 10, 3 );
/**
 * Customize when events will close for ticket sales, to reserve some tickets for door sales.
 *
 * @param integer $limit Point where ticket sales will close. Default: 0.
 * @param integer $event_id Event ID, in case somebody wanted some further customization.
 * @param array   $remaining remaining, sold, and total tickets available.
 *
 * @return integer new value where ticket sales are closed for an event.
 */
function mt_close_ticket_sales( $limit, $event_id, $remaining ) {
	$options            = mt_get_settings();
	$tickets_close_at   = ( isset( $options['mt_tickets_close_value'] ) && is_numeric( $options['mt_tickets_close_value'] ) ) ? $options['mt_tickets_close_value'] : 0;
	$tickets_close_type = ( isset( $options['mt_tickets_close_type'] ) ) ? $options['mt_tickets_close_type'] : 'integer';
	switch ( $tickets_close_type ) {
		case 'integer':
			$limit = $tickets_close_at;
			break;
		case 'percent':
			$limit = round( ( $tickets_close_at / 100 ) * $remaining['total'] );
			break;
	}

	return apply_filters( 'mt_custom_event_limit', $limit, $event_id, $remaining );
}

/**
 * Produce price if a per-ticket handling charge is being applied.
 *
 * @param float   $price Original price without handling.
 * @param integer $event Event ID.
 * @param string  $type Public or admin ticket type.
 *
 * @return float new price
 */
function mt_handling_price( $price, $event, $type = 'standard' ) {
	// correction for an early mipselling.
	if ( mt_admin_only( $type ) ) {
		return $price; // no handling on complimentary tickets.
	}
	$options = mt_get_settings();
	if ( isset( $options['mt_ticket_handling'] ) && is_numeric( $options['mt_ticket_handling'] ) ) {
		$price = $price + apply_filters( 'mt_ticket_handling_price', $options['mt_ticket_handling'], $event );
	}

	return $price;
}

/**
 * Produce notice if a per-ticket handling charge is being applied.
 *
 * @return string handling notice
 */
function mt_handling_notice() {
	$options = mt_get_settings();
	if ( isset( $options['mt_ticket_handling'] ) && is_numeric( $options['mt_ticket_handling'] ) && $options['mt_ticket_handling'] > 0 ) {
		// Translators: amount of ticket handling charge.
		$handling_notice = "<div class='mt-ticket-handling'>" . apply_filters( 'mt_ticket_handling_notice', sprintf( __( 'Tickets include a %s ticket handling charge.', 'my-tickets' ), apply_filters( 'mt_money_format', $options['mt_ticket_handling'] ) ) ) . '</div>';
	} else {
		$handling_notice = '';
	}

	return $handling_notice;
}

/**
 * Get the handling costs for a cart purchase.
 *
 * @param array  $options Array of My Tickets options.
 * @param string $gateway Currently selected gateway.
 *
 * @return float|int
 */
function mt_get_cart_handling( $options, $gateway ) {
	$handling = ( isset( $options['mt_handling'] ) ) ? $options['mt_handling'] : 0;
	$original = $handling;
	$ignored  = ( isset( $options['mt_gateways'][ $gateway ]['mt_handling'] ) && 'true' === $options['mt_gateways'][ $gateway ]['mt_handling'] ) ? true : false;
	if ( $ignored ) {
		$handling = 0;
	}
	/**
	 * Filter the handling ticket based on gateway selected.
	 *
	 * @hook mt_handling_total
	 *
	 * @param {float|int} $handling Cart handling fee after gateway settings applied.
	 * @param {float|int} $original Cart handling fee from settings.
	 * @param {string}    $gateway Selected gateway.
	 *
	 * @return {float|int}
	 */
	$handling = apply_filters( 'mt_handling_total', $handling, $original, $gateway );
	$handling = ( is_numeric( $handling ) ) ? floatval( $handling ) : 0;

	return $handling;
}

/**
 * Get closing date/time for event
 *
 * @param integer $event_id Event ID.
 * @param string  $expires Expiration time.
 *
 * @return string
 */
function mt_sales_close( $event_id, $expires ) {
	$event = get_post_meta( $event_id, '_mc_event_data', true );
	if ( $event && is_array( $event ) ) {
		if ( isset( $event['general_admission'] ) && 'on' === $event['general_admission'] ) {
			// Don't display closing information on General Admission events.
			return '';
		}
		if ( isset( $event['event_begin'] ) && isset( $event['event_time'] ) ) {
			$expiration = $expires * 60 * 60;
			$begin      = strtotime( $event['event_begin'] . ' ' . $event['event_time'] ) - $expiration;
			if ( mt_date( 'Y-m-d', $begin ) === mt_date( 'Y-m-d', mt_current_time() ) ) {
				// Translators: time that ticket sales close today.
				return '<p>' . sprintf( apply_filters( 'mt_ticket_sales_close_text', __( 'Ticket sales close at %s today', 'my-tickets' ), $event ), '<strong>' . date_i18n( get_option( 'time_format' ), $begin ) . '</strong>' ) . '</p>';
			}
		}
	}

	return '';
}

/**
 * Test whether event can currently allow tickets to be shipped, given provided time frame for shipping in relation to event date/time.
 *
 * @param integer $event_id Event ID.
 *
 * @return bool
 */
function mt_no_postal( $event_id ) {
	$options       = mt_get_settings();
	$shipping_time = $options['mt_shipping_time'];
	$event         = get_post_meta( $event_id, '_mc_event_data', true );
	$no_postal     = false;
	if ( $event && is_array( $event ) ) {
		$date = ( isset( $event['event_begin'] ) ) ? $event['event_begin'] : false;
		$time = ( isset( $event['event_time'] ) ) ? $event['event_time'] : false;
		if ( is_numeric( $date ) && is_numeric( $time ) ) {
			$event_date = strtotime( absint( $date . ' ' . $time ) );
			$no_postal  = ( $event_date <= ( mt_current_time() + ( 60 * 60 * 24 * $shipping_time ) ) ) ? true : false;

			return $no_postal;
		}
	}

	return $no_postal;
}

add_action( 'init', 'mt_add_to_cart' );
/**
 * Add event tickets to cart. (Non AJAX).
 *
 * @uses function mt_register_message.
 */
function mt_add_to_cart() {
	if ( ! isset( $_REQUEST['mt_add_to_cart'] ) ) {
		return;
	} else {
		if ( isset( $_POST['mt_add_to_cart'] ) ) {
			$nonce = $_POST['_wpnonce'];
			if ( ! wp_verify_nonce( $nonce, 'mt-cart-nonce' ) ) {
				return;
			}
		}
		if ( isset( $_GET['mt_add_to_cart'] ) ) {
			$data     = mt_get_data( 'cart' );
			$event_id = intval( $_GET['event_id'] );
			// todo: figure out how to set this up for mt_admin_only ticket types.
			$type    = ( isset( $_GET['ticket_type'] ) && ( 'complementary' !== $_GET['ticket_type'] && 'complimentary' !== $_GET['ticket_type'] ) ) ? sanitize_key( $_GET['ticket_type'] ) : false;
			$count   = isset( $_GET['count'] ) ? intval( $_GET['count'] ) : 1;
			$options = ( $type ) ? array( $type => $count ) : false;
			// Record into virtual inventory.
			$cur_count = ( isset( $data[ $event_id ] ) ) ? $data[ $event_id ][ $type ] : 0;
			$new_count = $count;
			if ( $new_count > $cur_count ) {
				$increment = ( $cur_count - $new_count );
			} else {
				$increment = ( $cur_count - $new_count ) * -1;
			}
			mt_update_inventory( $event_id, $type, $increment );
			if ( $data ) {
				$event_options = isset( $data[ $event_id ] ) && is_array( $data[ $event_id ] ) ? $data[ $event_id ] : array();
				$options       = array_merge( $event_options, $options );
			}
		} else {
			$event_id = ( isset( $_POST['mt_event_id'] ) ) ? intval( $_POST['mt_event_id'] ) : false;
			$options  = ( isset( $_POST['mt_tickets'] ) ) ? array_map( 'absint', $_POST['mt_tickets'] ) : false;
		}
		$saved = ( false !== $options ) ? mt_save_data(
			array(
				'event_id' => $event_id,
				'options'  => $options,
			)
		) : false;
	}
	if ( $saved ) {
		mt_register_message( 'add_to_cart', 'success' );
	} else {
		mt_register_message( 'add_to_cart', 'error' );
	}
}

/**
 * Register a message to be displayed following a cart or button action.
 *
 * @param string                $context Current message context.
 * @param string                $type Message type.
 * @param mixed boolean|integer $payment_id Payment ID.
 *
 * @return void
 */
function mt_register_message( $context, $type, $payment_id = false ) {
	global $mt_message;
	$mt_message = mt_get_message( $context, $type, $payment_id );
}

/**
 * Fetch a message to be displayed following a cart or button action.
 *
 * @param string  $context Current message context.
 * @param string  $type Message type.
 * @param integer $payment_id Payment ID.
 *
 * @return string
 */
function mt_get_message( $context, $type, $payment_id ) {
	$context = esc_attr( $context );
	$options = mt_get_settings();
	$type    = esc_attr( $type );
	if ( 'add_to_cart' === $context ) {
		$cart_url = get_permalink( $options['mt_purchase_page'] );
		switch ( $type ) {
			case 'success':
				// Translators: cart URL.
				$return = sprintf( __( 'Event successfully added to <a href="%s">your cart</a>.', 'my-tickets' ), $cart_url );
				break;
			case 'error':
				$return = __( 'That event could not be added to your cart.', 'my-tickets' );
				break;
			case 'error-expired':
				$return = __( 'Online ticket sales are no longer available for this event.', 'my-tickets' );
				break;
			default:
				$return = '';
		}
	} elseif ( 'update_cart' === $context ) {
		switch ( $type ) {
			case 'success':
				$return = __( 'Cart Updated.', 'my-tickets' );
				break;
			case 'error':
				$return = __( 'Could not update your cart.', 'my-tickets' );
				break;
			case 'error-expired':
				$return = __( 'The event is no longer available for online ticket sales.', 'my-tickets' );
				break;
			default:
				$return = '';
		}
	} elseif ( 'payment_due' === $context ) {
		switch ( $type ) {
			case 'success':
				if ( $payment_id ) {
					$gateway = get_post_meta( $payment_id, '_gateway', true );
					if ( 'offline' === $gateway ) {
						wp_publish_post( $payment_id );
					}
				}
				$return = __( 'Your ticket order has been submitted! Any payment due will be collected when you arrive at the event.', 'my-tickets' );
				break;
			default:
				$return = '';
		}
	} elseif ( 'cart_submitted' === $context ) {
		switch ( $type ) {
			case 'success':
				$return = __( 'Cart Submitted.', 'my-tickets' );
				break;
			case 'error':
				$return = __( 'Could not submit your cart.', 'my-tickets' );
				break;
			default:
				$return = '';
		}
	}

	return apply_filters( 'mt_get_message_text', "<div class='$context $type mt-message'><p>$return</p></div>", $context, $type );
}

add_filter( 'the_content', 'mt_display_message' );
/**
 * Get registered message and display at top of content.
 *
 * @param string $content Post content.
 *
 * @return string
 */
function mt_display_message( $content ) {
	if ( isset( $_POST['my-tickets'] ) && is_main_query() ) {
		global $mt_message;

		return $mt_message . $content;
	}

	return $content;
}

/**
 * Update cart data. Special case application of mt_save_data.
 *
 * @param array $post Posted data.
 *
 * @return array
 */
function mt_update_cart( $post = array() ) {
	$cart = mt_get_cart();
	if ( ! $cart ) {
		$event_id = ( isset( $post['mt_event_id'] ) ) ? $post['mt_event_id'] : false;
		$options  = ( isset( $post['mt_tickets'] ) ) ? $post['mt_tickets'] : false;
		$cart     = array(
			'event_id' => $event_id,
			'options'  => $options,
		);
		$updated  = mt_save_data( $cart );
	} else {
		foreach ( $post as $id => $item ) {
			if ( is_numeric( $id ) ) {
				$cart_item = isset( $cart[ $id ] ) ? $cart[ $id ] : array();
				$post_item = array();
				foreach ( $item as $type => $count ) {
					$post_item[ $type ] = absint( $count['count'] );
				}
				$post_item = array_merge( $cart_item, $post_item );
				if ( ! isset( $cart[ $id ] ) || ( $cart[ $id ] !== $post_item ) ) {
					$cart[ $id ] = $post_item;
				}
			}
		}
		$has_contents = false;
		// If any ticket type has a count, keep event in cart.
		foreach ( $cart as $id => $type ) {
			if ( is_array( $type ) ) {
				foreach ( $type as $counted ) {
					if ( 0 < (int) $counted ) {
						$has_contents = true;
					}
				}
			}
			if ( ! $has_contents ) {
				unset( $cart[ $id ] );
			}
		}

		$updated = mt_save_data( $cart, 'cart', true );
	}

	return array(
		'success' => $updated,
		'cart'    => $cart,
	);
}

/**
 * Checks whether a given event is currently represented in user's cart.
 *
 * @param int $event_id event ID.
 * @param int $user_ID user ID.
 *
 * @return bool|int
 */
function mt_in_cart( $event_id, $user_ID = false ) {
	$cart = mt_get_cart( $user_ID );
	if ( isset( $cart[ $event_id ] ) ) {
		return $cart[ $event_id ];
	}

	return false;
}
