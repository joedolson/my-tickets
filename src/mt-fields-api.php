<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
} // Exit if accessed directly

/*
 * Add custom fields easily by creating an array attached to the filter mt_custom_fields. For more control, define your own functions attached to these actions/filters.
 *
 * Example minimal usage:
 
add_filter( 'mt_custom_fields', 'create_custom_fields', 10, 2 );
function create_custom_fields( $array ) {
	// Other fields: sanitize callback; input type; input values; display_callback
	$array['test_event_data'] = array( 
		'title'             => "Test Event Data",
		'sanitize_callback' => 'my_sanitize_callback',
		'display_callback'  => 'my_display_callback',
		'input_type'        => 'text', // most HTML5 or standard inputs supported
		'input_values'      => '', // single value required for checkbox or radio; array of values for select
		'context'           => 'global' // or event ID or array of event IDs; use filter mt_apply_custom_field_rules to apply custom rules
	);
	return $array;
}

function my_display_callback( $data, $context ) {
	return urldecode( $data );
}

function my_sanitize_callback( $data ) {
	return esc_html( $data );
} 
*/

add_action( 'init', 'mt_add_actions' );
function mt_add_actions() {
	// adds field to Add to Cart form
	add_filter( 'mt_add_to_cart_fields', 'mt_custom_field', 10, 2 );
	// Save field data to cookie/user meta for use in cart
	add_action( 'mt_add_to_cart_ajax_field_handler', 'mt_handle_custom_field', 10, 2 );
	// Display field data in shopping cart
	add_filter( 'mt_show_in_cart_fields', 'mt_show_custom_field', 10, 2 );
	// Insert submitted data into Payment post meta
	add_action( 'mt_save_payment_fields', 'mt_insert_custom_field', 10, 3 );
	// Display field data in tickets list.
	add_filter( 'mt_custom_tickets_fields', 'mt_custom_tickets_fields', 10, 4 );
}


function mt_custom_tickets_fields( $output, $event_id, $payment_id, $sep ) {
	$custom_fields = apply_filters( 'mt_custom_fields', array(), 'tickets' );
	$return        = '';
	$last_value    = '';
	foreach ( $custom_fields as $name => $field ) {
		$data   = get_post_meta( $payment_id, $name );
		$return = apply_filters( 'mt_custom_ticket_display_field', '', $data, $name, $payment_id );
		if ( ! empty( $data ) && '' == $return ) {

			foreach ( $data as $d ) {
				if ( ! isset( $field['display_callback'] ) ) {
					$display_value = stripslashes( $d[ $name ] );
				} else {
					$display_value = call_user_func( $field['display_callback'], $d[ $name ], 'payment' );
				}

				if ( $display_value != '' && $d[ 'event_id' ] == $event_id ) {
					$added_value = $field['title'] . ' - ' . $display_value;
					if ( $added_value == $last_value ) {
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

	return $output . $return;
}

/**
 * Check whether defined custom ticket fields should be shown on a given event.
 *
 * @param $field array
 * @param $event_id integer
 *
 * @return bool|mixed|void
 */
function mt_apply_custom_field( $field, $event_id ) {
    $return = false;
	if ( ! isset( $field['context'] ) || $field['context'] == 'global' ) {
		$return = true;
	}
	if ( is_numeric( $field['context'] ) && (int) $field['context'] == $event_id ) {
		$return = true;
	}
	if ( is_array( $field['context'] ) && in_array( $event_id, $field['context'] ) ) {
		$return = true;
	}

	// add your custom rules.
	return apply_filters( 'mt_apply_custom_field_rules', $return, $field, $event_id );
}

/**
 * Process array of custom field definitions to return output HTML.
 *
 * @param $fields array
 * @param $event_id integer
 *
 * @return string
 */
function mt_custom_field( $fields, $event_id ) {
	$custom_fields = apply_filters( 'mt_custom_fields', array(), 'input' );
	$output        = '';
	foreach ( $custom_fields as $name => $field ) {
		$continue = mt_apply_custom_field( $field, $event_id );
		if ( $continue ) {
			$user_value = esc_attr( stripslashes( mt_get_data( $name . '_' . $event_id ) ) );
			$required   = isset( $field['required'] ) ? ' required' : '';
			$req_label  = isset( $field['required'] ) ? ' <span class="required">' . __( 'Required', 'my-tickets' ) . '</span>' : '';
			switch ( $field['input_type'] ) {
				case "text":
				case "number":
				case "email":
				case "url":
				case "date":
				case "tel":
					$output = "<input type='" . $field['input_type'] . "' name='$name' id='$name' value='$user_value'$required />";
					break;
				case "textarea":
					$output = "<textarea rows='6' cols='60' name='$name' id='$name'$required>$user_value</textarea>";
					break;
				case "select":
					if ( isset( $field['input_values'] ) ) {
						$output = "<select name='$name' id='$name'$required>";
						foreach ( $field['input_values'] as $value ) {
							$value = esc_attr( stripslashes( $value ) );
							if ( $value == $user_value ) {
								$selected = " selected='selected'";
							} else {
								$selected = '';
							}
							$output .= "<option value='" . esc_attr( stripslashes( $value ) ) . "'$selected>" . $value . "</option>\n";
						}
						$output .= "</select>";
					}
					break;
				case "checkbox":
				case "radio":
					if ( isset( $field['input_values'] ) ) {
						$value = $field['input_values'];
						if ( $user_value != '' ) {
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
			$fields .= "<p><label for='$name'>" . $field['title'] . $req_label . "</label> " . $output . "</p>";
		}
	}

	return $fields;
}

/**
 * Call user functions to sanitize and save custom data with current user's shopping cart.
 *
 * @param $submit array
 */
function mt_handle_custom_field( $saved, $submit ) {
	$custom_fields = apply_filters( 'mt_custom_fields', array(), 'sanitize' );
	foreach ( $custom_fields as $name => $field ) {
		if ( isset( $submit[ $name ] ) ) {
			if ( ! isset( $field['sanitize_callback'] ) || ( isset( $field['sanitize_callback'] ) && !function_exists( $field['sanitize_callback'] ) ) ) {
				// if no sanitization is provided, we'll prep it for SQL and strip tags.
				$sanitized = esc_html( strip_tags( urldecode( $submit[ $name ] ) ) );
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
 * @param $content string
 * @param $event_id integer
 *
 * @return string
 */
function mt_show_custom_field( $content, $event_id ) {
	$custom_fields = apply_filters( 'mt_custom_fields', array(), 'display' );
	foreach ( $custom_fields as $name => $field ) {
		$data = mt_get_data( $name . '_' . $event_id );
		if ( ! isset( $field['display_callback'] ) || ( isset( $field['display_callback'] ) && !function_exists( $field['display_callback'] ) ) ) {
			$display_value = stripslashes( $data );
		} else {
			$display_value = call_user_func( $field['display_callback'], $data, 'cart' );
		}
		$content .= $display_value . "<input type='hidden' name='{$name}[$event_id]' value='" . esc_attr( $data ) . "' />";
	}

	return $content;
}

/**
 * Insert post meta data into payment field.
 *
 * @param integer $payment_id
 * @param array   $post
 * @param array   $purchased
 */
function mt_insert_custom_field( $payment_id, $post, $purchased ) {
	$custom_fields = apply_filters( 'mt_custom_fields', array(), 'save' );
	foreach ( $custom_fields as $name => $field ) {
		if ( isset( $post[ $name ] ) ) {
			foreach ( $post[ $name ] as $key => $data ) {
				if ( $data != '' ) {
					$data = array( 'event_id' => $key, $name => $data );
					add_post_meta( $payment_id, $name, $data );
				}
			}
		}
	}
}

/**
 * Display custom field in payment post manager.
 *
 * @param $content string
 * @param $payment_id integer
 *
 * @return string
 */
function mt_show_payment_field( $content, $payment_id ) {
	$custom_fields = apply_filters( 'mt_custom_fields', array(), 'admin' );
	$output        = '';
	foreach ( $custom_fields as $name => $field ) {
		$data   = get_post_meta( $payment_id, $name );
		$return = apply_filters( 'mt_custom_display_field', '', $data, $name );
		if ( $return === '' ) {
			foreach ( $data as $d ) {
				if ( ! isset( $field['display_callback'] ) ) {
					$display_value = stripslashes( $d[ $name ] );
				} else {
					$display_value = call_user_func( $field['display_callback'], $d[ $name ], 'payment' );
				}
				if ( $display_value != '' ) {
					$event_title = get_the_title( $d['event_id'] );
					$output .= apply_filters( 'mt_custom_data_format_output', "<p><strong>$event_title</strong>:<br />$field[title] - $display_value</p>", $payment_id, $name );
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
 * @param $payment_id integer
 * @param $custom_field string|boolean
 *
 * @return string
 */
function mt_show_custom_data( $payment_id, $custom_field = false ) {
	$custom_fields = apply_filters( 'mt_custom_fields', array(), 'receipt' );
	$output        = '';
	foreach ( $custom_fields as $name => $field ) {
		if ( $custom_field == false || $custom_field == $name ) {
			$data   = get_post_meta( $payment_id, $name );
			$return = apply_filters( 'mt_custom_display_field', '', $data, $name );
			if ( $return === '' ) {
				foreach ( $data as $d ) {
					if ( ! isset( $field['display_callback'] ) ) {
						$display_value = stripslashes( $d[ $name ] );
					} else {
						$display_value = call_user_func( $field['display_callback'], $d[ $name ], 'payment' );
					}
					if ( $display_value != '' ) {
						$event_title = get_the_title( $d['event_id'] );
						$output .= apply_filters( 'mt_custom_data_format_output', "<p><strong>$event_title</strong>:<br />$field[title] - $display_value</p>", $payment_id, $custom_field );
					}
				}
			} else {
				$output .= $return;
			}
		}
	}

	return $output;
}