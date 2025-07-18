<?php
/**
 * Fields API.
 *
 * @category API
 * @package  My Tickets
 * @author   Joe Dolson
 * @license  GPLv3
 * @link     https://www.joedolson.com/my-tickets/
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
} // Exit if accessed directly

add_action( 'init', 'mt_add_actions' );
/**
 * Parent function to insert custom filters.
 */
function mt_add_actions() {
	// adds field to Add to Cart form.
	add_filter( 'mt_add_to_cart_fields', 'mt_custom_field', 10, 2 );
	// Save field data to cookie/user meta for use in cart.
	add_action( 'mt_add_to_cart_ajax_field_handler', 'mt_handle_custom_field', 10, 2 );
	// Display field data in shopping cart.
	add_filter( 'mt_show_in_cart_fields', 'mt_show_custom_field', 10, 6 );
	// Insert submitted data into Payment post meta.
	add_action( 'mt_save_payment_fields', 'mt_insert_custom_field', 10, 3 );
	// Display field data in tickets list.
	add_filter( 'mt_custom_tickets_fields', 'mt_custom_tickets_fields', 10, 4 );
}

/**
 * Get My Tickets custom fields.
 *
 * @param string $context Where is this field being used. Input, cart, ticket, receipt.
 *
 * @return array
 */
function mt_get_custom_fields( $context ) {
	$fields = array();
	/**
	 * Hook to add custom fields.
	 *
	 * @hook mt_custom_fields
	 *
	 * @param {array}  $fields Array of custom fields. Default empty.
	 * @param {string} $context Where is this function being called. Input, cart, ticket, receipt, etc.
	 *
	 * @return {array}
	 */
	$fields = apply_filters( 'mt_custom_fields', $fields, $context );

	return $fields;
}

/**
 * Add custom fields to tickets.
 *
 * @param string $output Field output.
 * @param int    $event_id Event ID.
 * @param int    $payment_id Payment ID.
 * @param string $sep Field separator.
 *
 * @return string
 */
function mt_custom_tickets_fields( $output, $event_id, $payment_id, $sep ) {
	$custom_fields = mt_get_custom_fields( 'tickets' );
	$return        = '';
	$last_value    = '';
	foreach ( $custom_fields as $name => $field ) {
		$data   = get_post_meta( $payment_id, $name );
		$return = apply_filters( 'mt_custom_ticket_display_field', '', $data, $name, $payment_id );
		if ( ! empty( $data ) && '' === $return ) {

			foreach ( $data as $d ) {
				if ( ! isset( $field['display_callback'] ) ) {
					$display_value = stripslashes( $d[ $name ] );
				} else {
					$display_value = call_user_func( $field['display_callback'], $d[ $name ], 'payment', $field );
				}

				if ( '' !== $display_value && (string) $d['event_id'] === (string) $event_id ) {
					$added_value = $field['title'] . ' - ' . $display_value;
					if ( $added_value === $last_value ) {
						continue;
					} else {
						$add_value  = apply_filters( 'mt_custom_ticket_format_output', $added_value . $sep, $payment_id, $name );
						$output    .= $add_value;
						$last_value = $added_value;
					}
				}
			}
		}
	}
	$return = ( $return ) ? '<div class="mt-custom-fields">' . $return . '</div>' : '';

	return $output . $return;
}

/**
 * Check whether defined custom ticket fields should be shown on a given event.
 *
 * @param array $field Field info.
 * @param int   $event_id Event ID.
 *
 * @return bool
 */
function mt_apply_custom_field( $field, $event_id ) {
	$return = false;
	if ( ! isset( $field['context'] ) || 'global' === $field['context'] ) {
		$return = true;
	}
	if ( is_numeric( $field['context'] ) && (int) $field['context'] === (int) $event_id ) {
		$return = true;
	}
	if ( is_array( $field['context'] ) && in_array( $event_id, $field['context'], true ) ) {
		$return = true;
	}

	/**
	 * Filter the boolean value that indicates whether a custom field should be shown on an event.
	 *
	 * @hook mt_apply_custom_field_rules
	 *
	 * @param {bool}  $return True to return this field.
	 * @param {array} $field Array of custom field characteristics.
	 * @param {int}   $event_id Event being displayed.
	 *
	 * @return {bool}
	 */
	return apply_filters( 'mt_apply_custom_field_rules', $return, $field, $event_id );
}

/**
 * Process array of custom field definitions to return output HTML.
 *
 * @param array $fields Field data.
 * @param int   $event_id Event ID.
 *
 * @return string
 */
function mt_custom_field( $fields, $event_id ) {
	$custom_fields = mt_get_custom_fields( 'input' );
	$output        = '';
	foreach ( $custom_fields as $name => $field ) {
		$continue = mt_apply_custom_field( $field, $event_id );
		/**
		 * Modify a custom field's characteristics prior to rendering.
		 *
		 * @hook mt_field_parameters
		 *
		 * @param {array} $field Array of field information.
		 * @param {int}   $event_id The event being rendered.
		 *
		 * @return {array}
		 */
		$field = apply_filters( 'mt_field_parameters', $field, $event_id );
		if ( $continue && is_array( $field ) ) {
			$user_value = esc_attr( stripslashes( mt_get_data( $name . '_' . $event_id ) ) );
			$required   = isset( $field['required'] ) ? ' required' : '';
			$req_label  = isset( $field['required'] ) ? ' <span class="required">' . __( 'Required', 'my-tickets' ) . '</span>' : '';
			switch ( $field['input_type'] ) {
				case 'text':
				case 'number':
				case 'email':
				case 'url':
				case 'date':
				case 'tel':
					$output = "<input type='" . $field['input_type'] . "' name='$name' id='$name' value='$user_value'$required />";
					break;
				case 'textarea':
					$output = "<textarea rows='6' cols='60' name='$name' id='$name'$required>$user_value</textarea>";
					break;
				case 'select':
					if ( isset( $field['input_values'] ) ) {
						$output = "<select name='$name' id='$name'$required>";
						foreach ( $field['input_values'] as $value ) {
							$value = esc_attr( stripslashes( $value ) );
							if ( $value === $user_value ) {
								$selected = " selected='selected'";
							} else {
								$selected = '';
							}
							$output .= "<option value='" . esc_attr( stripslashes( $value ) ) . "'$selected>" . $value . "</option>\n";
						}
						$output .= '</select>';
					}
					break;
				case 'checkbox':
				case 'radio':
					if ( isset( $field['input_values'] ) ) {
						$value = $field['input_values'];
						if ( '' !== $user_value ) {
							$checked = ' checked="checked"';
						} else {
							$checked = '';
						}
						$output = "<input type='" . $field['input_type'] . "' name='$name' id='$name' value='" . esc_attr( stripslashes( $value ) ) . "'$checked $required />";
					}
					break;
				default:
					$output = "<input type='text' name='$name' id='$name' value='$user_value' $required />";
			}
			$class   = 'mt-' . sanitize_html_class( $field['title'] );
			$fields .= "<div class='mt-ticket-field $class'><label for='$name'>" . $field['title'] . $req_label . '</label> ' . $output . '</div>';
		}
	}

	return $fields;
}

/**
 * Call user functions to sanitize and save custom data with current user's shopping cart.
 *
 * @param mixed $saved data to save.
 * @param array $submit Submitted data.
 *
 * @return mixed
 */
function mt_handle_custom_field( $saved, $submit ) {
	$custom_fields = mt_get_custom_fields( 'sanitize' );
	foreach ( $custom_fields as $name => $field ) {
		if ( isset( $submit[ $name ] ) ) {
			if ( ! isset( $field['sanitize_callback'] ) || ( isset( $field['sanitize_callback'] ) && ! function_exists( $field['sanitize_callback'] ) ) ) {
				// if no sanitization is provided, we'll prep it for SQL and strip tags.
				if ( is_array( $submit[ $name ] ) ) {
					$sanitized = map_deep( $submit[ $name ], 'sanitize_text_field' );
				} else {
					$sanitized = sanitize_text_field( $submit[ $name ] );
				}
			} else {
				$sanitized = call_user_func( $field['sanitize_callback'], urldecode( $submit[ $name ] ) );
			}
			$event_id = $submit['mt_event_id'];
			mt_save_data( $sanitized, $name . '_' . $event_id );
		}
	}

	return $saved;
}

/**
 * Display saved data in shopping cart and add hidden input field to pass into payment creation.
 *
 * @param string   $content Shopping cart html.
 * @param int      $event_id Event ID.
 * @param int|bool $payment Payment ID if available.
 * @param string   $type Ticket type for this line item.
 * @param int      $count Number of tickets of this type in cart. May change dynamically.
 * @param string   $format Whether we're in the cart or confirmation view.
 *
 * @return string
 */
function mt_show_custom_field( $content, $event_id, $payment, $type, $count, $format ) {
	$custom_fields = mt_get_custom_fields( 'display' );
	$return        = '';
	foreach ( $custom_fields as $name => $field ) {
		$data = mt_get_data( $name . '_' . $event_id );
		if ( ! isset( $field['display_callback'] ) || ( isset( $field['display_callback'] ) && ! function_exists( $field['display_callback'] ) ) ) {
			$display_value = sanitize_text_field( $data );
		} else {
			$display_value = call_user_func( $field['display_callback'], $data, 'cart', $field );
		}
		$return .= $display_value . "<input type='hidden' name='{$name}[$event_id]' value='" . esc_attr( $data ) . "' />";
	}
	$return = ( $return ) ? '<div class="mt-custom-fields">' . $return . '</div>' : '';

	return $content . $return;
}

/**
 * Insert post meta data into payment field.
 *
 * @param integer $payment_id Payment ID.
 * @param array   $post POST data.
 * @param array   $purchased Purchase info.
 */
function mt_insert_custom_field( $payment_id, $post, $purchased ) {
	$custom_fields = mt_get_custom_fields( 'save' );
	foreach ( $custom_fields as $name => $field ) {
		if ( isset( $post[ $name ] ) ) {
			foreach ( $post[ $name ] as $event_id => $data ) {
				if ( '' !== $data ) {
					if ( ! isset( $field['sanitize_callback'] ) || ( isset( $field['sanitize_callback'] ) && ! function_exists( $field['sanitize_callback'] ) ) ) {
						// if no sanitization is provided, we'll prep it for SQL and strip tags.
						$sanitized = sanitize_text_field( $data );
					} else {
						$sanitized = call_user_func( $field['sanitize_callback'], $data );
					}
					$data = array(
						'event_id' => $event_id,
						$name      => $sanitized,
					);
					add_post_meta( $payment_id, $name, $data );
					/**
					 * Execute a custom action when saving custom field data.
					 *
					 * @hook mt_save_custom_field
					 *
					 * @param {int}    $payment_id Payment ID for this purchase.
					 * @param {int}    $event_id Event tickets purchased for.
					 * @param {string} $name Custom field key being saved.
					 * @param {array}  $sanitized Data submitted by user post-sanitization.
					 * @param {array}  $purchased Full purchase data.
					 */
					do_action( 'mt_custom_field_saved', $payment_id, $event_id, $name, $data, $purchased );
				}
			}
		}
	}
}

/**
 * Display custom field in payment post manager.
 *
 * @param string $content Form HTML.
 * @param int    $payment_id Payment ID.
 *
 * @return string
 */
function mt_show_payment_field( $content, $payment_id ) {
	$custom_fields = mt_get_custom_fields( 'admin' );
	$output        = '';
	foreach ( $custom_fields as $name => $field ) {
		$data = get_post_meta( $payment_id, $name );
		/**
		 * Customize the output of custom fields in the admin Payment record.
		 *
		 * @hook mt_custom_display_field
		 *
		 * @param {string} $output_html. Default empty string.
		 * @param {mixed}  $data Saved data from post meta.
		 * @param {string} $name Field name array key.
		 *
		 * @return {string}
		 */
		$return = apply_filters( 'mt_custom_display_field', '', $data, $name );
		if ( '' === $return ) {
			foreach ( $data as $d ) {
				if ( ! isset( $field['display_callback'] ) ) {
					$display_value = stripslashes( $d[ $name ] );
				} else {
					$display_value = call_user_func( $field['display_callback'], $d[ $name ], 'payment', $field );
				}
				if ( '' !== $display_value ) {
					$event_title = get_the_title( $d['event_id'] );
					$output     .= apply_filters( 'mt_custom_data_format_output', "<p><strong>$event_title</strong>:<br />$field[title] - $display_value</p>", $payment_id, $name );
				}
			}
		} else {
			$output .= $return;
		}
	}

	return $content . $output;
}

/**
 * Display custom field on tickets/receipts
 *
 * @param int            $payment_id Payment ID.
 * @param string|boolean $custom_field Custom field data to display.
 *
 * @return string
 */
function mt_show_custom_data( $payment_id, $custom_field = false ) {
	$custom_fields = mt_get_custom_fields( 'receipt' );
	$output        = '';
	foreach ( $custom_fields as $name => $field ) {
		if ( false === $custom_field || $custom_field === $name ) {
			$data = get_post_meta( $payment_id, $name );
			/**
			 * Customize the display of a custom field.
			 *
			 * @hook mt_custom_display_field
			 *
			 * @param {string} $return Return value of the field.
			 * @param {mixed}  $data Value stored in post meta.
			 * @param {string} $name Field name.
			 *
			 * @return {string}
			 */
			$return = apply_filters( 'mt_custom_display_field', '', $data, $name );
			if ( '' === $return ) {
				foreach ( $data as $d ) {
					if ( ! isset( $field['display_callback'] ) ) {
						$display_value = stripslashes( $d[ $name ] );
					} else {
						$display_value = call_user_func( $field['display_callback'], $d[ $name ], 'payment', $field );
					}
					if ( '' !== $display_value ) {
						$event_title = get_the_title( $d['event_id'] );
						$output     .= apply_filters( 'mt_custom_data_format_output', "<p><strong>$event_title</strong>:<br />$field[title] - $display_value</p>", $payment_id, $custom_field );
					}
				}
			} else {
				$output .= $return;
			}
		}
	}

	return $output;
}
