<?php
/**
 * Add to Cart data.
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

add_filter( 'mc_after_event', 'mt_registration_form', 5, 4 );
add_filter( 'the_content', 'mt_registration_form_post', 20, 1 ); // after wpautop.

/**
 * Appends a registration form to post content for posts with defined event data.
 *
 * @uses function mt_registration_form();
 * @param string $content Post Content.
 *
 * @return string
 */
function mt_registration_form_post( $content ) {
	$options = array_merge( mt_default_settings(), get_option( 'mt_settings', array() ) );
	global $post;
	if ( in_array( get_post_type( $post ), $options['mt_post_types'], true ) ) {
		$event = $post->ID;
		if ( get_post_meta( $event, '_mc_event_data', true ) ) {
			$content = mt_registration_form( $content, $event );
		}
	}

	return $content;
}

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
 * Generates event registration form.
 *
 * @param string                $content Page content.
 * @param mixed/bool/int/object $event If boolean, exit.
 * @param string                $view Type of view for context.
 * @param string                $time Time view being displayed.
 * @param boolean               $override Don't display.
 *
 * @return string
 */
function mt_registration_form( $content, $event = false, $view = 'calendar', $time = 'month', $override = false ) {
	$options       = array_merge( mt_default_settings(), get_option( 'mt_settings', array() ) );
	$purchase_page = $options['mt_purchase_page'];
	$receipt_page  = $options['mt_purchase_page'];
	$tickets_page  = $options['mt_tickets_page'];
	if ( is_page( $purchase_page ) || is_page( $receipt_page ) || is_page( $tickets_page ) ) {
		return $content;
	}
	if ( ! $event ) {
		return $content;
	}

	$form        = '';
	$cart_data   = '';
	$sold_out    = '';
	$has_tickets = '';
	$output      = '';
	$event_id    = ( is_object( $event ) ) ? $event->event_post : $event;

	if ( 'mc-events' === get_post_type( $event_id ) ) {
		$sell = get_post_meta( $event_id, '_mt_sell_tickets', true );
		if ( 'false' === $sell ) {
			return $content;
		}
	}

	if ( 'true' === get_post_meta( $event_id, '_mt_hide_registration_form', true ) && false === $override ) {
		return $content;
	}
	$registration = get_post_meta( $event_id, '_mt_registration_options', true );
	// if no 'total' is set at all, this is not an event with tickets.
	if ( empty( $registration['prices'] ) ) {
		return $content;
	}
	// if total is set to inherit, but any ticket class has no defined number of tickets available, return. '0' is a valid number of tickets, '' is not.
	if ( ( isset( $registration['total'] ) && 'inherit' === $registration['total'] ) && 'general' !== $registration['counting_method'] && ! mt_has_tickets( $registration['prices'] ) ) {
		return $content;
	}
	// if total number of tickets is set but is an empty string or is not set; return.
	if ( ( isset( $registration['total'] ) && '' === trim( $registration['total'] ) ) || ! isset( $registration['total'] ) ) {
		return $content;
	}
	$expired   = mt_expired( $event_id, true );
	$no_postal = mt_no_postal( $event_id );
	if ( $no_postal && 1 === count( $options['mt_ticketing'] ) && in_array( 'postal', $options['mt_ticketing'], true ) && ! ( current_user_can( 'mt-order-expired' ) || current_user_can( 'manage_options' ) ) ) {
		$expired = true;
	}
	$handling_notice = '';
	if ( ! $expired ) {
		if ( is_array( $registration ) ) {
			$pricing = $registration['prices'];
			$nonce   = wp_nonce_field( 'mt-cart-nonce', '_wpnonce', true, false );
			if ( is_array( mt_in_cart( $event_id ) ) ) {
				$cart_data = mt_in_cart( $event_id );
			}
			if ( isset( $_GET['mc_id'] ) ) {
				$mc_id     = (int) $_GET['mc_id'];
				$permalink = add_query_arg( 'mc_id', $mc_id, get_the_permalink() );
			} else {
				$permalink = get_the_permalink();
			}
			if ( is_array( $pricing ) ) {
				$default_available = apply_filters( 'mt_default_available', 100, $registration );
				$available         = ( 'general' === $registration['counting_method'] ) ? $default_available : $registration['total'];
				// if multiple != true, use checkboxes.
				$input_type = ( isset( $registration['multiple'] ) && 'true' === $registration['multiple'] ) ? 'number' : 'checkbox';
				// Figure out handling for radio input type.
				$tickets_data      = mt_tickets_left( $pricing, $available );
				$default_available = apply_filters( 'mt_default_available', 100, $registration );
				$tickets_remaining = ( 'general' === $registration['counting_method'] ) ? $default_available : $tickets_data['remain'];
				$tickets_sold      = $tickets_data['sold'];
				$class             = 'mt-available';
				if ( $tickets_remaining && $tickets_remaining > apply_filters( 'mt_tickets_close_value', 0, $event_id, $tickets_data ) ) {
					$sold_out    = false;
					$total_order = 0;
					foreach ( $pricing as $type => $settings ) {
						$settings = apply_filters( 'mt_ticket_settings', $settings, $pricing, $event_id );
						if ( ! mt_can_order( $type ) ) {
							continue;
						}
						$extra_label = apply_filters( 'mt_extra_label', '', $event, $type );
						if ( mt_admin_only( $type ) ) {
							$extra_label = '<span class="mt-admin-only">(' . __( 'Administrators only', 'my-tickets' ) . ')</span>';
						}
						if ( $type ) {
							if ( ! isset( $settings['price'] ) ) {
								continue;
							}
							$price              = mt_calculate_discount( $settings['price'], $event_id );
							$price              = mt_handling_price( $price, $event, $type );
							$price              = apply_filters( 'mt_money_format', $price );
							$ticket_handling    = apply_filters( 'mt_ticket_handling_price', $options['mt_ticket_handling'], $event );
							$handling_notice    = mt_handling_notice();
							$ticket_price_label = apply_filters( 'mt_ticket_price_label', $price, $settings['price'], $ticket_handling );
							$value              = ( is_array( $cart_data ) && isset( $cart_data[ $type ] ) ) ? $cart_data[ $type ] : apply_filters( 'mt_cart_default_value', '0', $type );
							$value              = ( '' === $value ) ? 0 : (int) $value;
							$order_value        = $value;
							$attributes         = '';
							$close              = ( isset( $settings['close'] ) && ! empty( $settings['close'] ) ) ? $settings['close'] : '';
							if ( $close && $close < time() ) {
								// If this ticket type is no longer available, skip.
								continue;
							}
							if ( 'checkbox' === $input_type || 'radio' === $input_type ) {
								if ( 1 === $value ) {
									$attributes = " checked='checked'";
								}
								$value       = 1;
								$order_value = 0;
							}
							if ( 'inherit' === $available ) {
								$tickets   = absint( $settings['tickets'] );
								$sold      = absint( $settings['sold'] );
								$remaining = ( $tickets - $sold );
								$max_limit = apply_filters( 'mt_max_sale_per_event', false );
								if ( $max_limit ) {
									$max = ( $max_limit > $remaining ) ? $remaining : $max_limit;
								} else {
									$max = $remaining;
								}
								$disable = ( $remaining < 1 ) ? ' disabled="disabled"' : '';
								if ( '' === $attributes ) {
									$attributes = " min='0' max='$max'";
									if ( 0 === $remaining ) {
										$attributes .= ' readonly="readonly"';
										$class       = 'mt-sold-out';
									} else {
										$class = 'mt-available';
									}
								}
								$form .= "<div class='mt-ticket-field mt-ticket-$type $class'><label for='mt_tickets_$type" . '_' . "$event_id' id='mt_tickets_label_$type" . '_' . "$event_id'>" . esc_attr( $settings['label'] ) . $extra_label . '</label>';
								$form .= apply_filters(
									'mt_add_to_cart_input',
									"<input type='$input_type' name='mt_tickets[$type]' id='mt_tickets_$type" . '_' . "$event_id' class='tickets_field' value='$value' $attributes aria-labelledby='mt_tickets_label_$type" . '_' . $event_id . " mt_tickets_data_$type'$disable />",
									$input_type,
									$type,
									$value,
									$attributes,
									$disable,
									$max,
									$available
								);

								$hide_remaining = mt_hide_remaining( $tickets_remaining );
								// Translators: Ticket price label, number remaining.
								$form       .= "<span id='mt_tickets_data_$type' class='ticket-pricing$hide_remaining'>" . sprintf( apply_filters( 'mt_tickets_remaining_discrete_text', __( '(%1$s, %2$s remaining%3$s)', 'my-tickets' ), $ticket_price_label, $remaining, $tickets ), $ticket_price_label . '<span class="tickets-remaining">', "<span class='value remaining-tickets'>" . $remaining . "</span>/<span class='ticket-count'>" . $tickets . '</span>', '</span>' ) . '</span>';
								$form       .= "<span class='mt-error-notice' aria-live='assertive'></span></div>";
								$total_order = $total_order + $order_value;
							} else {
								$remaining = $tickets_remaining;
								if ( '' === $attributes ) {
									$attributes = " min='0' max='$remaining'";
									if ( 0 === $remaining ) {
										$attributes .= ' readonly="readonly"';
										$class       = 'mt-sold-out';
									}
								}
								$price_in_label = apply_filters( 'mt_price_in_label', false, $event_id );
								$price          = "<span id='mt_tickets_data_$type'>$ticket_price_label</span>";
								$label_price    = ( $price_in_label ) ? ' <span class="mt-label-price">' . strip_tags( $price ) . '</span>' : '';
								$post_price     = ( ! $price_in_label ) ? $price : '';
								$form          .= "<div class='mt-ticket-field mt-ticket-$type $class'><label for='mt_tickets_$type" . '_' . "$event_id' id='mt_tickets_label_$type" . '_' . "$event_id'>" . esc_attr( $settings['label'] ) . $extra_label . $label_price . '</label>';
								$form          .= apply_filters(
									'mt_add_to_cart_input',
									"<input type='$input_type' name='mt_tickets[$type]' $attributes id='mt_tickets_$type" . '_' . "$event_id' class='tickets_field' value='$value' aria-labelledby='mt_tickets_label_$type" . '_' . $event_id . " mt_tickets_data_$type' />",
									$input_type,
									$type,
									$value,
									$attributes,
									'',
									$remaining,
									$available
								);
								$form          .= $post_price . "<span class='mt-error-notice' aria-live='assertive'></span></div>";
								$total_order    = $total_order + $value;
							}
							$has_tickets = true;
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
			} else {
				$remaining_notice = '';
			}

			if ( true === $has_tickets ) {
				$closing_time = mt_sales_close( $event_id, $registration['reg_expires'] );
				$no_post      = ( $no_postal && in_array( 'postal', array_keys( $options['mt_ticketing'] ), true ) ) ? "<p class='mt-no-post'>" . apply_filters( 'mt_cannot_send_by_email_text', __( 'Tickets for this event cannot be sent by mail.', 'my-tickets' ) ) . '</p>' : '';
				$legend       = ( 'registration' === $registration['sales_type'] ) ? __( 'Register', 'my-tickets' ) : __( 'Buy Tickets', 'my-tickets' );
				$legend       = apply_filters( 'mt_button_legend_text', $legend, $registration );
				$disabled     = ( $total_order > $tickets_remaining ) ? " disabled='disabled'" : '';
				$output       = "
			<div class='mt-order'>
				<div class='mt-response' id='mt-response-$event_id' aria-live='assertive'></div>
				$no_post
				$closing_time
				$handling_notice
				<form action='" . esc_url( $permalink ) . "' method='POST' class='ticket-orders' id='order-tickets' tabindex='-1'>
					<div>
						$nonce
						<input type='hidden' name='mt_event_id' value='$event_id' />" . apply_filters( 'mt_add_to_cart_hidden_fields', '', $event_id ) . "
					</div>
					<fieldset>
					<legend>$legend</legend>
						$remaining_notice
						<p>$form</p>" . apply_filters( 'mt_add_to_cart_fields', '', $event_id ) . "<p>
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
		$available           = $registration['total'];
		$pricing             = $registration['prices'];
		$tickets_remaining   = mt_tickets_left( $pricing, $available );
		$tickets_remain_text = mt_tickets_remaining( $tickets_remaining, $event_id );
		$sales_closed        = ( 'registration' === $registration['sales_type'] ) ? __( 'Online registration for this event is closed', 'my-tickets' ) : __( 'Online ticket sales for this event are closed.', 'my-tickets' );
		$output              = "<div class='mt-order mt-closed'><p>" . apply_filters( 'mt_sales_closed', $sales_closed ) . "$tickets_remain_text</p></div>";
	}

	if ( true === $sold_out && $tickets_sold > 0 ) {
		$tickets_soldout = ( 'registration' === $registration['sales_type'] ) ? __( 'Registration for this event is full', 'my-tickets' ) : __( 'Tickets for this event are sold out.', 'my-tickets' );
		$output          = "<div class='mt-order mt-soldout'><p>" . apply_filters( 'mt_tickets_soldout', $tickets_soldout ) . '</p></div>';
		$output         .= apply_filters( 'mt_tickets_soldout_content', '', $event_id, $registration );
		$soldout         = get_post_meta( $event_id, '_mt_event_soldout', true );
		if ( 'true' !== $soldout ) {
			update_post_meta( $event_id, '_mt_event_soldout', 'true' );
			do_action( 'mt_event_sold_out', $event_id, $registration, 'soldout' );
		}
	}

	return $content . $output;
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
	$options       = array_merge( mt_default_settings(), get_option( 'mt_settings', array() ) );
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
	$available         = $registration['total'];
	$pricing           = $registration['prices'];
	$tickets_remaining = mt_tickets_left( $pricing, $available );
	// Translators: Number of tickets remaining.
	$remaining = ( 0 >= $tickets_remaining['remain'] ) ? $expired : sprintf( __( '%s tickets remaining', 'my-tickets' ), '<strong>' . $tickets_remaining['remain'] . '</strong>' );
	$sold_out  = ( 0 >= $tickets_remaining['remain'] ) ? __( 'Sold out', 'my-tickets' ) : $remaining;

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
	$options = array_merge( mt_default_settings(), get_option( 'mt_settings', array() ) );
	// If hide remaining is enabled, set as hidden.
	$hide_remaining = ( isset( $options['mt_hide_remaining'] ) && 'true' === $options['mt_hide_remaining'] ) ? ' hiding' : '';
	// Hide tickets if there are more than x tickets available if limit is set.
	$hide_limiting = ( isset( $options['mt_hide_remaining_limit'] ) && ( $tickets_remaining > absint( $options['mt_hide_remaining_limit'] ) ) ) ? ' hiding' : '';

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
	$tickets_remaining = $tickets_data['remain'];
	if ( $tickets_remaining && $tickets_remaining > apply_filters( 'mt_tickets_close_value', 0, $event_id, $tickets_data ) ) {
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
	$options            = array_merge( mt_default_settings(), get_option( 'mt_settings', array() ) );
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
	$options = array_merge( mt_default_settings(), get_option( 'mt_settings', array() ) );
	if ( isset( $options['mt_ticket_handling'] ) && is_numeric( $options['mt_ticket_handling'] ) ) {
		$price = $price + apply_filters( 'mt_ticket_handling_price', $options['mt_ticket_handling'], $event );
	}

	return $price;
}

/**
 * Produce price if a per-ticket handling charge is being applied.
 *
 * @return string handling notice
 */
function mt_handling_notice() {
	$options = array_merge( mt_default_settings(), get_option( 'mt_settings', array() ) );
	if ( isset( $options['mt_ticket_handling'] ) && is_numeric( $options['mt_ticket_handling'] ) && $options['mt_ticket_handling'] > 0 ) {
		// Translators: amount of ticket handling charge.
		$handling_notice = "<div class='mt-ticket-handling'>" . apply_filters( 'mt_ticket_handling_notice', sprintf( __( 'Tickets include a %s ticket handling charge.', 'my-tickets' ), apply_filters( 'mt_money_format', $options['mt_ticket_handling'] ) ) ) . '</div>';
	} else {
		$handling_notice = '';
	}

	return $handling_notice;
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
	$options       = array_merge( mt_default_settings(), get_option( 'mt_settings', array() ) );
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

/**
 * Determine how many tickets have been sold for a given pricing set.
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
	$options = array_merge( mt_default_settings(), get_option( 'mt_settings', array() ) );
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
 * Abstract function for saving user data (cookie or meta). Saves as cookie is not logged in, as user meta if is.
 *
 * @param array  $passed Data passed to save.
 * @param string $type Type of data to save.
 * @param bool   $override Whether to override this.
 *
 * @return bool
 */
function mt_save_data( $passed, $type = 'cart', $override = false ) {
	$type = sanitize_title( $type );
	if ( true === $override ) {
		$save = $passed;
	} else {
		switch ( $type ) {
			case 'cart':
				$save              = mt_get_cart();
				$options           = $passed['options'];
				$event_id          = $passed['event_id'];
				$save[ $event_id ] = $options;
				break;
			case 'payment':
				$save = $passed;
				break;
			default:
				$save = $passed;
		}
	}
	$current_user = wp_get_current_user();
	mt_refresh_cache();
	if ( is_user_logged_in() ) {
		update_user_meta( $current_user->ID, "_mt_user_$type", $save );

		return true;
	} else {
		$unique_id = ( isset( $_COOKIE['mt_unique_id'] ) ) ? sanitize_text_field( $_COOKIE['mt_unique_id'] ) : false;
		if ( get_transient( 'mt_' . $unique_id . '_' . $type ) ) {
			delete_transient( 'mt_' . $unique_id . '_' . $type );
		}
		set_transient( 'mt_' . $unique_id . '_' . $type, $save, mt_current_time() + WEEK_IN_SECONDS );

		return true;
	}
}

add_action( 'init', 'mt_set_user_unique_id' );
/**
 * Note: if sitecookiepath doesn't match the site's render location, this won't work.
 * It'll also create a secondary issue where AJAX actions read the sitecookiepath cookie.
 */
function mt_set_user_unique_id() {
	if ( ! defined( 'DOING_CRON' ) ) {
		$unique_id = ( isset( $_COOKIE['mt_unique_id'] ) ) ? sanitize_text_field( $_COOKIE['mt_unique_id'] ) : false;
		if ( ! $unique_id ) {
			$unique_id = mt_generate_unique_id();
			if ( version_compare( PHP_VERSION, '7.3.0', '>' ) ) {
				// Fix syntax.
				$options = array(
					'expires'  => time() + 60 * 60 * 24 * 7,
					'path'     => COOKIEPATH,
					'domain'   => COOKIE_DOMAIN,
					'secure'   => false,
					'httponly' => true,
					'samesite' => 'Lax',
				);
				setcookie( 'mt_unique_id', $unique_id, $options );
			} else {
				setcookie( 'mt_unique_id', $unique_id, time() + 60 * 60 * 24 * 7, COOKIEPATH, COOKIE_DOMAIN, false, true );
			}
		}
	}
}

/**
 * Generate a unique ID to track the current cart process.
 *
 * @return string
 */
function mt_generate_unique_id() {
	$length     = 16;
	$characters = '0123456789AaBbCcDdEeFfGgHhIiJjKkLlMmNnOoPpQqRrSsTtUuVvWwXxYyZz-_';
	$string     = '';
	for ( $p = 0; $p < $length; $p++ ) {
		$string .= $characters[ mt_rand( 0, strlen( $characters ) - 1 ) ];
	}

	return $string;
}

/**
 * Abstract function to retrieve data for current user/public user.
 *
 * @param string       $type Type of data.
 * @param bool|integer $user_ID User ID or false if not logged in.
 *
 * @return array|mixed
 */
function mt_get_data( $type, $user_ID = false ) {
	if ( $user_ID ) {
		$data = get_user_meta( $user_ID, "_mt_user_$type", true );
	} else {
		if ( is_user_logged_in() ) {
			$current_user = wp_get_current_user();
			$data         = get_user_meta( $current_user->ID, "_mt_user_$type", true );
		} else {
			$unique_id = ( isset( $_COOKIE['mt_unique_id'] ) ) ? sanitize_text_field( $_COOKIE['mt_unique_id'] ) : false;
			if ( $unique_id ) {
				$data = get_transient( 'mt_' . $unique_id . '_' . $type );
			} else {
				$data = '[]';
			}
			if ( $data ) {
				if ( '' !== $data && ! is_numeric( $data ) && ! is_array( $data ) ) {
					// Data is probably JSON and needs to be decoded.
					$data = json_decode( $data );
				}
			} else {
				$data = false;
			}
		}
	}

	return $data;
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
 * @param Integer $event_id event ID.
 * @param Integer $user_ID user ID.
 *
 * @return bool
 */
function mt_in_cart( $event_id, $user_ID = false ) {
	$cart = mt_get_cart( $user_ID );
	if ( isset( $cart[ $event_id ] ) ) {
		return $cart[ $event_id ];
	}

	return false;
}
