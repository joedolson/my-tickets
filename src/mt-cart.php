<?php
/**
 * Shopping Cart.
 *
 * @category Core
 * @package  My Tickets
 * @author   Joe Dolson
 * @license  GPLv2 or later
 * @link     https://www.joedolson.com/my-tickets/
 */

add_filter( 'the_content', 'my_tickets_cart', 20, 2 );
/**
 * Display My Tickets shopping cart on purchase page.
 *
 * @param string $content Post Content.
 *
 * @return string
 */
function my_tickets_cart( $content ) {
	$options = array_merge( mt_default_settings(), get_option( 'mt_settings', array() ) );
	$id      = ( '' !== $options['mt_purchase_page'] ) ? $options['mt_purchase_page'] : false;
	if ( $id && ( is_single( $id ) || is_page( $id ) ) ) {
		// by default, any page content is appended after the cart. This can be changed.
		$content_before = apply_filters( 'mt_content_before_cart', '' );
		$content_after  = apply_filters( 'mt_content_after_cart', $content );
		$cart           = mt_generate_cart();
		$content        = $content_before . $cart . $content_after;
	}

	return $content;
}

add_action( 'init', 'mt_handle_response_message' );
/**
 * Delete cart data if payment response message is 'thank you'.
 *
 * @return void
 */
function mt_handle_response_message() {
	// if we've got a thank you message, we don't need this cart any more.
	if ( isset( $_GET['response_code'] ) && 'thanks' === $_GET['response_code'] ) {
		mt_delete_data( 'cart' );
		mt_delete_data( 'payment' );
	}
}

add_filter( 'mt_content_before_cart', 'mt_response_messages' );
/**
 * Display response messages from cart on purchase page.
 *
 * @return string
 */
function mt_response_messages() {
	$message       = '';
	$response_code = '';
	if ( isset( $_GET['response_code'] ) ) {
		$response_code = sanitize_text_field( $_GET['response_code'] );
		if ( 'cancel' === $response_code ) {
			$message = __( "We're sorry you were unable to complete your purchase! Please contact us if you had any issues in the purchase process.", 'my-tickets' );
		}
		if ( 'thanks' === $response_code ) {
			$message = __( 'Thanks for your purchase!', 'my-tickets' );
			if ( isset( $_GET['payment_id'] ) ) {
				$payment_id = (int) $_GET['payment_id'];
				$gateway    = get_post_meta( $payment_id, '_gateway', true );
				if ( 'offline' === $gateway ) {
					wp_publish_post( $payment_id );
					$message = __( 'Thanks for your order!', 'my-tickets' );
				}
			}
		}
		if ( 'required-fields' === $response_code ) {
			$message = __( 'First name, last name, and email are required fields. Please fill in these fields and submit again!', 'my-tickets' );
		}
		if ( ! $message ) {
			$message = ( isset( $_GET['reason'] ) ) ? sanitize_text_field( $_GET['reason'] ) : '';
			if ( ! $message ) {
				$message = ( isset( $_GET['response_reason_text'] ) ) ? sanitize_text_field( $_GET['response_reason_text'] ) : '';
			}
			$message = sanitize_text_field( $message );
		}
		return apply_filters( 'mt_response_messages', $message, $response_code );
	}

	return $message;
}

add_filter( 'mt_response_messages', 'mt_wrap_response_messages', 20, 2 );
/**
 * Generate filterable HTML to wrap response messages.
 *
 * @param string $message Text of response message.
 * @param string $code Error code received.
 *
 * @return string
 */
function mt_wrap_response_messages( $message, $code ) {
	return "<p class='mt-message error-" . esc_attr( $code ) . "'>$message</p>";
}

/**
 * Check whether cart includes any items that disallow shipping tickets.
 *
 * @param array $cart Cart data.
 *
 * @return bool
 */
function mt_cart_no_postal( $cart ) {
	foreach ( $cart as $event => $data ) {
		$prices = mt_get_prices( $event );
		if ( is_array( $data ) ) {
			foreach ( $data as $type => $count ) {
				if ( $count > 0 ) {
					if ( isset( $prices[ $type ] ) ) {
						if ( mt_no_postal( $event ) && ! mt_expired( $event ) ) {
							return true;
						}
					}
				}
			}
		}
	}

	return false;
}

/**
 * Incorporated basic required fields into cart data. Name, Email, ticket type selector.
 *
 * @param array  $cart Cart data.
 * @param string $custom_fields HTML for custom fields added by plugins.
 *
 * @return mixed|string
 */
function mt_required_fields( $cart, $custom_fields ) {
	$options   = array_merge( mt_default_settings(), get_option( 'mt_settings', array() ) );
	$output    = mt_render_field( 'name' );
	$output   .= mt_render_field( 'email' );
	$output   .= ( isset( $options['mt_phone'] ) && 'on' === $options['mt_phone'] ) ? mt_render_field( 'phone' ) : '';
	$output   .= ( isset( $options['mt_vat'] ) && 'on' === $options['mt_vat'] ) ? mt_render_field( 'vat' ) : '';
	$output   .= apply_filters( 'mt_filter_custom_field_output', $custom_fields, $cart );
	$opt_types = $options['mt_ticketing'];
	if ( isset( $opt_types['postal'] ) ) {
		$no_postal = mt_cart_no_postal( $cart );
		if ( $no_postal ) {
			unset( $opt_types['postal'] );
		}
	}
	$types = array_keys( $opt_types );
	if ( 1 === count( $types ) ) {
		foreach ( $types as $type ) {
			$output .= mt_render_type( $type );
		}
	} else {
		$output .= mt_render_types( $types );
	}

	return $output;
}

/**
 * Test whether user is logged in and whether user registration is allowed. Return invitation to log-in or register.
 * Displays only if public registration is enabled.
 *
 * @return string
 */
function mt_invite_login_or_register() {
	if ( ! is_user_logged_in() && '1' === get_option( 'users_can_register' ) ) {
		$login = apply_filters( 'mt_login_html', "<a href='" . wp_login_url() . "'>" . __( 'Log in', 'my-tickets' ) . '</a>' );
		if ( '1' === get_option( 'users_can_register' ) ) {
			$register = apply_filters( 'mt_register_html', "<a href='" . wp_registration_url() . "'>" . __( 'Create an account', 'my-tickets' ) . '</a>' );
		} else {
			$register = '';
		}
		if ( '' !== $register ) {
			// Translators: Login link, register link.
			$text = wpautop( sprintf( __( '%1$s or %2$s', 'my-tickets' ), $login, $register ) );
		} else {
			// Translators: Login link.
			$text = wpautop( sprintf( __( '%1$s now!', 'my-tickets' ), $login ) );
		}

		return apply_filters( 'mt_invite_login_or_register', "<div class='mt-invite-login-or-register'>$text</div>" );
	}

	return '';
}

/**
 * If multiple types are available, allow to choose whether or not to use an e-ticket. Notify of multiple methods that are available.
 *
 * @param array $types Types of tickets enabled.
 *
 * @return string
 */
function mt_render_types( $types ) {
	$options   = array_merge( mt_default_settings(), get_option( 'mt_settings', array() ) );
	$ticketing = apply_filters( 'mt_ticketing_availability', $options['mt_ticketing'], $types );
	$default   = isset( $options['mt_ticket_type_default'] ) ? $options['mt_ticket_type_default'] : '';
	$output    = '<p class="mt-ticket-type"><label for="ticketing_method">' . __( 'Ticket Type', 'my-tickets' ) . '</label> <select name="ticketing_method" id="ticketing_method">';
	foreach ( $ticketing as $key => $method ) {
		if ( in_array( $key, $types, true ) ) {
			$selected = selected( $key, $default, false );
			$output  .= "<option value='$key'$selected>$method</option>";
		}
	}
	$output .= '</select></p>';

	return $output;
}

/**
 * Display notice informing purchaser of the format that their ticket will be delivered in.
 *
 * @param string $type Ticket type.
 *
 * @return string
 */
function mt_render_type( $type ) {
	$options = array_merge( mt_default_settings(), get_option( 'mt_settings', array() ) );
	switch ( $type ) {
		case 'eticket':
			$return = __( 'Your ticket will be delivered as an e-ticket. You will receive a link in your email.', 'my-tickets' );
			break;
		case 'printable':
			$return = __( 'Your ticket will be provided for printing after purchase. You will receive a link to the ticket in your email. Please print your ticket and bring it with you to the event.', 'my-tickets' );
			break;
		case 'postal':
			// Translators: estimated number of days for ticket shipping.
			$return = sprintf( __( 'Your ticket will be sent to you by mail. You should receive your ticket within %s days, after payment is completed.', 'my-tickets' ), $options['mt_shipping_time'] );
			break;
		case 'willcall':
			$return = __( 'Your ticket will be under your name at the box office. Please arrive early to allow time to pick up your ticket.', 'my-tickets' );
			break;
		default:
			$return = '';
	}

	return "<input type='hidden' name='ticketing_method' value='$type' />" . apply_filters( 'mt_render_ticket_type_message', "<p class='ticket-type-message'>" . $return . '</p>', $type );
}

/**
 * Display input field on cart screen. (Pre Confirmation)
 *
 * @param string $field Name of field to display.
 * @param bool   $argument Custom arguments.
 *
 * @return mixed|void
 */
function mt_render_field( $field, $argument = false ) {
	$current_user = wp_get_current_user();
	$output       = '';
	$defaults     = array(
		'street'  => '',
		'street2' => '',
		'city'    => '',
		'state'   => '',
		'country' => '',
		'code'    => '',
	);
	$payment_id   = false;
	if ( isset( $_GET['payment'] ) || mt_get_data( 'payment' ) ) {
		$payment_id = ( isset( $_GET['payment'] ) ) ? (int) $_GET['payment'] : mt_get_data( 'payment' );
	}
	switch ( $field ) {
		case 'address':
			// only show shipping fields if postal ticketing in use.
			if ( ( isset( $_POST['ticketing_method'] ) && 'postal' === $_POST['ticketing_method'] ) || mt_always_collect_shipping() ) {
				$user_address = ( is_user_logged_in() ) ? get_user_meta( $current_user->ID, '_mt_shipping_address', true ) : $defaults;
				if ( get_user_meta( $current_user->ID, '_mt_shipping_address', true ) ) {
					$save_address_label = __( 'Update Address', 'my-tickets' );
				} else {
					$save_address_label = __( 'Save Address', 'my-tickets' );
				}
				$save_address = ( is_user_logged_in() ) ? '<p><a href="#" class="mt_save_shipping">' . $save_address_label . "<span class='mt-processing'><img src='" . admin_url( 'images/spinner-2x.gif' ) . "' alt='" . __( 'Working', 'my-tickets' ) . "' /></span></a></p>" : '';
				$address      = ( isset( $_POST['mt_shipping']['address'] ) ) ? $_POST['mt_shipping']['address'] : (array) $user_address;
				$address      = array_merge( $defaults, $address );
				$required     = ' ' . __( '(required)', 'my-tickets' );
				$output       = '
				<fieldset class="mt-shipping-address">
					<legend>' . __( 'Shipping Address', 'my-tickets' ) . '</legend>
					<p>
						<label for="mt_address_street">' . __( 'Street', 'my-tickets' ) . $required . '</label>
						<input type="text" name="mt_shipping_street" id="mt_address_street" class="mt_street" value="' . esc_attr( stripslashes( $address['street'] ) ) . '" autocomplete="address-line1" required />
					</p>
					<p>
						<label for="mt_address_street2">' . __( 'Street (2)', 'my-tickets' ) . '</label>
						<input type="text" name="mt_shipping_street2" id="mt_address_street2" class="mt_street2" value="' . esc_attr( stripslashes( $address['street2'] ) ) . '" autocomplete="address-line2" />
					</p>
					<p>
						<label for="mt_address_city">' . __( 'City', 'my-tickets' ) . $required . '</label>
						<input type="text" name="mt_shipping_city" id="mt_address_city" class="mt_city" value="' . esc_attr( stripslashes( $address['city'] ) ) . '" autocomplete="address-level2" required />
					</p>
					<p>
						<label for="mt_address_state">' . __( 'State/Province', 'my-tickets' ) . '</label>
						<input type="text" name="mt_shipping_state" id="mt_address_state" class="mt_state" value="' . esc_attr( stripslashes( $address['state'] ) ) . '" autocomplete="address-level1" />
					</p>
					<p>
						<label for="mt_address_code">' . __( 'Postal Code', 'my-tickets' ) . $required . '</label>
						<input type="text" name="mt_shipping_code" size="10" id="mt_address_code" class="mt_code" value="' . esc_attr( stripslashes( $address['code'] ) ) . '" autocomplete="postal-code" required />
					</p>
					<p>
						<label for="mt_address_country">' . __( 'Country', 'my-tickets' ) . $required . '</label>
						<select name="mt_shipping_country" id="mt_address_country" class="mt_country" autocomplete="country">
						<option value="">Select Country</option>
						' . mt_shipping_country( $address['country'] ) . '
						</select>
					</p>' . $save_address . '
					<div class="mt-response" aria-live="assertive"></div>
				</fieldset>';
			}
			$output = apply_filters( 'mt_shipping_fields', $output, $argument );
			break;
		case 'name':
			$user_fname = ( is_user_logged_in() ) ? $current_user->user_firstname : '';
			$user_lname = ( is_user_logged_in() ) ? $current_user->user_lastname : '';
			$fname      = ( isset( $_POST['mt_fname'] ) ) ? sanitize_text_field( $_POST['mt_fname'] ) : $user_fname;
			$lname      = ( isset( $_POST['mt_lname'] ) ) ? sanitize_text_field( $_POST['mt_lname'] ) : $user_lname;
			if ( $payment_id ) {
				$fname = get_post_meta( $payment_id, '_first_name', true );
				$lname = get_post_meta( $payment_id, '_last_name', true );
			}
			$output = '<div class="mt-names"><p><label for="mt_fname">' . __( 'First Name (required)', 'my-tickets' ) . '</label> <input type="text" name="mt_fname" id="mt_fname" value="' . esc_attr( stripslashes( $fname ) ) . '" autocomplete="given-name" required aria-required="true" /></p><p><label for="mt_lname">' . __( 'Last Name (required)', 'my-tickets' ) . '</label> <input type="text" name="mt_lname" id="mt_lname" value="' . esc_attr( stripslashes( $lname ) ) . '" autocomplete="family-name" required aria-required="true" /></p></div>';
			break;
		case 'email':
			$user_email = ( is_user_logged_in() ) ? $current_user->user_email : '';
			$email      = ( isset( $_POST['mt_email'] ) ) ? sanitize_text_field( $_POST['mt_email'] ) : $user_email;
			if ( $payment_id ) {
				$email = get_post_meta( $payment_id, '_email', true );
			}
			$output  = '<div class="mt-emails"><p><label for="mt_email">' . __( 'E-mail (required)', 'my-tickets' ) . '</label> <input type="email" name="mt_email" id="mt_email" value="' . esc_attr( stripslashes( $email ) ) . '" autocomplete="email" required aria-required="true"  /></p>';
			$output .= '<p><label for="mt_email2">' . __( 'E-mail (confirm)', 'my-tickets' ) . '</label> <input type="email" name="mt_email2" id="mt_email2" value="' . esc_attr( stripslashes( $email ) ) . '" required aria-required="true"  /><span class="mt_email_check" aria-live="polite"><span class="ok"><i class="dashicons dashicons-yes" aria-hidden="true"></i>' . __( 'Email address matches', 'my-tickets' ) . '</span><span class="notemail"><i class="dashicons dashicons-no" aria-hidden="true"></i>' . __( 'Not a valid email address', 'my-tickets' ) . '</span><span class="mismatch"><i class="dashicons dashicons-no" aria-hidden="true"></i>' . __( 'Email address does not match', 'my-tickets' ) . '</span></span></span></p></div>';
			break;
		case 'phone':
			$user_phone = ( is_user_logged_in() ) ? get_user_meta( $current_user->ID, 'mt_phone', true ) : '';
			$phone      = ( isset( $_POST['mt_phone'] ) ) ? sanitize_text_field( $_POST['mt_phone'] ) : $user_phone;
			if ( $payment_id ) {
				$phone = get_post_meta( $payment_id, '_phone', true );
			}
			$output = '<p><label for="mt_phone">' . __( 'Phone (required)', 'my-tickets' ) . '</label> <input type="text" name="mt_phone" id="mt_phone" value="' . esc_attr( stripslashes( $phone ) ) . '" autocomplete="tel" required aria-required="true"  /></p>';
			break;
		case 'vat':
			$user_vat = ( is_user_logged_in() ) ? get_user_meta( $current_user->ID, 'mt_vat', true ) : '';
			$vat      = ( isset( $_POST['mt_vat'] ) ) ? sanitize_text_field( $_POST['mt_vat'] ) : $user_vat;
			if ( $payment_id ) {
				$vat = get_post_meta( $payment_id, '_vat', true );
			}
			$output = '<p><label for="mt_vat">' . __( 'VAT Number', 'my-tickets' ) . '</label> <input type="text" name="mt_vat" id="mt_vat" value="' . esc_attr( stripslashes( $vat ) ) . '" required aria-required="true"  /></p>';
			break;
	}

	return apply_filters( 'mt_render_field', $output, $field );
}


/**
 *  Generate option list of country codes.
 *
 * @param string $country selected country or country code.
 *
 * @return string
 */
function mt_shipping_country( $country = '' ) {
	$countries = array(
		'AF' => __( 'Afghanistan', 'my-tickets' ),
		'AX' => __( 'Aland Islands', 'my-tickets' ),
		'AL' => __( 'Albania', 'my-tickets' ),
		'DZ' => __( 'Algeria', 'my-tickets' ),
		'AS' => __( 'American Samoa', 'my-tickets' ),
		'AD' => __( 'Andorra', 'my-tickets' ),
		'AO' => __( 'Angola', 'my-tickets' ),
		'AI' => __( 'Anguilla', 'my-tickets' ),
		'AQ' => __( 'Antarctica', 'my-tickets' ),
		'AG' => __( 'Antigua And Barbuda', 'my-tickets' ),
		'AR' => __( 'Argentina', 'my-tickets' ),
		'AM' => __( 'Armenia', 'my-tickets' ),
		'AW' => __( 'Aruba', 'my-tickets' ),
		'AU' => __( 'Australia', 'my-tickets' ),
		'AT' => __( 'Austria', 'my-tickets' ),
		'AZ' => __( 'Azerbaijan', 'my-tickets' ),
		'BS' => __( 'Bahamas', 'my-tickets' ),
		'BH' => __( 'Bahrain', 'my-tickets' ),
		'BD' => __( 'Bangladesh', 'my-tickets' ),
		'BB' => __( 'Barbados', 'my-tickets' ),
		'BY' => __( 'Belarus', 'my-tickets' ),
		'BE' => __( 'Belgium', 'my-tickets' ),
		'BZ' => __( 'Belize', 'my-tickets' ),
		'BJ' => __( 'Benin', 'my-tickets' ),
		'BM' => __( 'Bermuda', 'my-tickets' ),
		'BT' => __( 'Bhutan', 'my-tickets' ),
		'BO' => __( 'Bolivia', 'my-tickets' ),
		'BA' => __( 'Bosnia And Herzegovina', 'my-tickets' ),
		'BW' => __( 'Botswana', 'my-tickets' ),
		'BV' => __( 'Bouvet Island', 'my-tickets' ),
		'BR' => __( 'Brazil', 'my-tickets' ),
		'IO' => __( 'British Indian Ocean Territory', 'my-tickets' ),
		'BN' => __( 'Brunei Darussalam', 'my-tickets' ),
		'BG' => __( 'Bulgaria', 'my-tickets' ),
		'BF' => __( 'Burkina Faso', 'my-tickets' ),
		'BI' => __( 'Burundi', 'my-tickets' ),
		'KH' => __( 'Cambodia', 'my-tickets' ),
		'CM' => __( 'Cameroon', 'my-tickets' ),
		'CA' => __( 'Canada', 'my-tickets' ),
		'CV' => __( 'Cape Verde', 'my-tickets' ),
		'KY' => __( 'Cayman Islands', 'my-tickets' ),
		'CF' => __( 'Central African Republic', 'my-tickets' ),
		'TD' => __( 'Chad', 'my-tickets' ),
		'CL' => __( 'Chile', 'my-tickets' ),
		'CN' => __( 'China', 'my-tickets' ),
		'CX' => __( 'Christmas Island', 'my-tickets' ),
		'CC' => __( 'Cocos (Keeling) Islands', 'my-tickets' ),
		'CO' => __( 'Colombia', 'my-tickets' ),
		'KM' => __( 'Comoros', 'my-tickets' ),
		'CG' => __( 'Congo', 'my-tickets' ),
		'CD' => __( 'Congo, Democratic Republic', 'my-tickets' ),
		'CK' => __( 'Cook Islands', 'my-tickets' ),
		'CR' => __( 'Costa Rica', 'my-tickets' ),
		'CI' => __( 'Cote D\'Ivoire', 'my-tickets' ),
		'HR' => __( 'Croatia', 'my-tickets' ),
		'CU' => __( 'Cuba', 'my-tickets' ),
		'CY' => __( 'Cyprus', 'my-tickets' ),
		'CZ' => __( 'Czech Republic', 'my-tickets' ),
		'DK' => __( 'Denmark', 'my-tickets' ),
		'DJ' => __( 'Djibouti', 'my-tickets' ),
		'DM' => __( 'Dominica', 'my-tickets' ),
		'DO' => __( 'Dominican Republic', 'my-tickets' ),
		'EC' => __( 'Ecuador', 'my-tickets' ),
		'EG' => __( 'Egypt', 'my-tickets' ),
		'SV' => __( 'El Salvador', 'my-tickets' ),
		'GQ' => __( 'Equatorial Guinea', 'my-tickets' ),
		'ER' => __( 'Eritrea', 'my-tickets' ),
		'EE' => __( 'Estonia', 'my-tickets' ),
		'ET' => __( 'Ethiopia', 'my-tickets' ),
		'FK' => __( 'Falkland Islands (Malvinas)', 'my-tickets' ),
		'FO' => __( 'Faroe Islands', 'my-tickets' ),
		'FJ' => __( 'Fiji', 'my-tickets' ),
		'FI' => __( 'Finland', 'my-tickets' ),
		'FR' => __( 'France', 'my-tickets' ),
		'GF' => __( 'French Guiana', 'my-tickets' ),
		'PF' => __( 'French Polynesia', 'my-tickets' ),
		'TF' => __( 'French Southern Territories', 'my-tickets' ),
		'GA' => __( 'Gabon', 'my-tickets' ),
		'GM' => __( 'Gambia', 'my-tickets' ),
		'GE' => __( 'Georgia', 'my-tickets' ),
		'DE' => __( 'Germany', 'my-tickets' ),
		'GH' => __( 'Ghana', 'my-tickets' ),
		'GI' => __( 'Gibraltar', 'my-tickets' ),
		'GR' => __( 'Greece', 'my-tickets' ),
		'GL' => __( 'Greenland', 'my-tickets' ),
		'GD' => __( 'Grenada', 'my-tickets' ),
		'GP' => __( 'Guadeloupe', 'my-tickets' ),
		'GU' => __( 'Guam', 'my-tickets' ),
		'GT' => __( 'Guatemala', 'my-tickets' ),
		'GG' => __( 'Guernsey', 'my-tickets' ),
		'GN' => __( 'Guinea', 'my-tickets' ),
		'GW' => __( 'Guinea-Bissau', 'my-tickets' ),
		'GY' => __( 'Guyana', 'my-tickets' ),
		'HT' => __( 'Haiti', 'my-tickets' ),
		'HM' => __( 'Heard Island & Mcdonald Islands', 'my-tickets' ),
		'VA' => __( 'Holy See (Vatican City State)', 'my-tickets' ),
		'HN' => __( 'Honduras', 'my-tickets' ),
		'HK' => __( 'Hong Kong', 'my-tickets' ),
		'HU' => __( 'Hungary', 'my-tickets' ),
		'IS' => __( 'Iceland', 'my-tickets' ),
		'IN' => __( 'India', 'my-tickets' ),
		'ID' => __( 'Indonesia', 'my-tickets' ),
		'IR' => __( 'Iran, Islamic Republic Of', 'my-tickets' ),
		'IQ' => __( 'Iraq', 'my-tickets' ),
		'IE' => __( 'Ireland', 'my-tickets' ),
		'IM' => __( 'Isle Of Man', 'my-tickets' ),
		'IL' => __( 'Israel', 'my-tickets' ),
		'IT' => __( 'Italy', 'my-tickets' ),
		'JM' => __( 'Jamaica', 'my-tickets' ),
		'JP' => __( 'Japan', 'my-tickets' ),
		'JE' => __( 'Jersey', 'my-tickets' ),
		'JO' => __( 'Jordan', 'my-tickets' ),
		'KZ' => __( 'Kazakhstan', 'my-tickets' ),
		'KE' => __( 'Kenya', 'my-tickets' ),
		'KI' => __( 'Kiribati', 'my-tickets' ),
		'KR' => __( 'Korea', 'my-tickets' ),
		'KW' => __( 'Kuwait', 'my-tickets' ),
		'KG' => __( 'Kyrgyzstan', 'my-tickets' ),
		'LA' => __( 'Lao People\'s Democratic Republic', 'my-tickets' ),
		'LV' => __( 'Latvia', 'my-tickets' ),
		'LB' => __( 'Lebanon', 'my-tickets' ),
		'LS' => __( 'Lesotho', 'my-tickets' ),
		'LR' => __( 'Liberia', 'my-tickets' ),
		'LY' => __( 'Libyan Arab Jamahiriya', 'my-tickets' ),
		'LI' => __( 'Liechtenstein', 'my-tickets' ),
		'LT' => __( 'Lithuania', 'my-tickets' ),
		'LU' => __( 'Luxembourg', 'my-tickets' ),
		'MO' => __( 'Macao', 'my-tickets' ),
		'MK' => __( 'Macedonia', 'my-tickets' ),
		'MG' => __( 'Madagascar', 'my-tickets' ),
		'MW' => __( 'Malawi', 'my-tickets' ),
		'MY' => __( 'Malaysia', 'my-tickets' ),
		'MV' => __( 'Maldives', 'my-tickets' ),
		'ML' => __( 'Mali', 'my-tickets' ),
		'MT' => __( 'Malta', 'my-tickets' ),
		'MH' => __( 'Marshall Islands', 'my-tickets' ),
		'MQ' => __( 'Martinique', 'my-tickets' ),
		'MR' => __( 'Mauritania', 'my-tickets' ),
		'MU' => __( 'Mauritius', 'my-tickets' ),
		'YT' => __( 'Mayotte', 'my-tickets' ),
		'MX' => __( 'Mexico', 'my-tickets' ),
		'FM' => __( 'Micronesia, Federated States Of', 'my-tickets' ),
		'MD' => __( 'Moldova', 'my-tickets' ),
		'MC' => __( 'Monaco', 'my-tickets' ),
		'MN' => __( 'Mongolia', 'my-tickets' ),
		'ME' => __( 'Montenegro', 'my-tickets' ),
		'MS' => __( 'Montserrat', 'my-tickets' ),
		'MA' => __( 'Morocco', 'my-tickets' ),
		'MZ' => __( 'Mozambique', 'my-tickets' ),
		'MM' => __( 'Myanmar', 'my-tickets' ),
		'NA' => __( 'Namibia', 'my-tickets' ),
		'NR' => __( 'Nauru', 'my-tickets' ),
		'NP' => __( 'Nepal', 'my-tickets' ),
		'NL' => __( 'Netherlands', 'my-tickets' ),
		'AN' => __( 'Netherlands Antilles', 'my-tickets' ),
		'NC' => __( 'New Caledonia', 'my-tickets' ),
		'NZ' => __( 'New Zealand', 'my-tickets' ),
		'NI' => __( 'Nicaragua', 'my-tickets' ),
		'NE' => __( 'Niger', 'my-tickets' ),
		'NG' => __( 'Nigeria', 'my-tickets' ),
		'NU' => __( 'Niue', 'my-tickets' ),
		'NF' => __( 'Norfolk Island', 'my-tickets' ),
		'MP' => __( 'Northern Mariana Islands', 'my-tickets' ),
		'NO' => __( 'Norway', 'my-tickets' ),
		'OM' => __( 'Oman', 'my-tickets' ),
		'PK' => __( 'Pakistan', 'my-tickets' ),
		'PW' => __( 'Palau', 'my-tickets' ),
		'PS' => __( 'Palestinian Territory, Occupied', 'my-tickets' ),
		'PA' => __( 'Panama', 'my-tickets' ),
		'PG' => __( 'Papua New Guinea', 'my-tickets' ),
		'PY' => __( 'Paraguay', 'my-tickets' ),
		'PE' => __( 'Peru', 'my-tickets' ),
		'PH' => __( 'Philippines', 'my-tickets' ),
		'PN' => __( 'Pitcairn', 'my-tickets' ),
		'PL' => __( 'Poland', 'my-tickets' ),
		'PT' => __( 'Portugal', 'my-tickets' ),
		'PR' => __( 'Puerto Rico', 'my-tickets' ),
		'QA' => __( 'Qatar', 'my-tickets' ),
		'RE' => __( 'Reunion', 'my-tickets' ),
		'RO' => __( 'Romania', 'my-tickets' ),
		'RU' => __( 'Russian Federation', 'my-tickets' ),
		'RW' => __( 'Rwanda', 'my-tickets' ),
		'BL' => __( 'Saint Barthelemy', 'my-tickets' ),
		'SH' => __( 'Saint Helena', 'my-tickets' ),
		'KN' => __( 'Saint Kitts And Nevis', 'my-tickets' ),
		'LC' => __( 'Saint Lucia', 'my-tickets' ),
		'MF' => __( 'Saint Martin', 'my-tickets' ),
		'PM' => __( 'Saint Pierre And Miquelon', 'my-tickets' ),
		'VC' => __( 'Saint Vincent And Grenadines', 'my-tickets' ),
		'WS' => __( 'Samoa', 'my-tickets' ),
		'SM' => __( 'San Marino', 'my-tickets' ),
		'ST' => __( 'Sao Tome And Principe', 'my-tickets' ),
		'SA' => __( 'Saudi Arabia', 'my-tickets' ),
		'SN' => __( 'Senegal', 'my-tickets' ),
		'RS' => __( 'Serbia', 'my-tickets' ),
		'SC' => __( 'Seychelles', 'my-tickets' ),
		'SL' => __( 'Sierra Leone', 'my-tickets' ),
		'SG' => __( 'Singapore', 'my-tickets' ),
		'SK' => __( 'Slovakia', 'my-tickets' ),
		'SI' => __( 'Slovenia', 'my-tickets' ),
		'SB' => __( 'Solomon Islands', 'my-tickets' ),
		'SO' => __( 'Somalia', 'my-tickets' ),
		'ZA' => __( 'South Africa', 'my-tickets' ),
		'GS' => __( 'South Georgia And Sandwich Isl.', 'my-tickets' ),
		'ES' => __( 'Spain', 'my-tickets' ),
		'LK' => __( 'Sri Lanka', 'my-tickets' ),
		'SD' => __( 'Sudan', 'my-tickets' ),
		'SR' => __( 'Suriname', 'my-tickets' ),
		'SJ' => __( 'Svalbard And Jan Mayen', 'my-tickets' ),
		'SZ' => __( 'Swaziland', 'my-tickets' ),
		'SE' => __( 'Sweden', 'my-tickets' ),
		'CH' => __( 'Switzerland', 'my-tickets' ),
		'SY' => __( 'Syrian Arab Republic', 'my-tickets' ),
		'TW' => __( 'Taiwan', 'my-tickets' ),
		'TJ' => __( 'Tajikistan', 'my-tickets' ),
		'TZ' => __( 'Tanzania', 'my-tickets' ),
		'TH' => __( 'Thailand', 'my-tickets' ),
		'TL' => __( 'Timor-Leste', 'my-tickets' ),
		'TG' => __( 'Togo', 'my-tickets' ),
		'TK' => __( 'Tokelau', 'my-tickets' ),
		'TO' => __( 'Tonga', 'my-tickets' ),
		'TT' => __( 'Trinidad And Tobago', 'my-tickets' ),
		'TN' => __( 'Tunisia', 'my-tickets' ),
		'TR' => __( 'Turkey', 'my-tickets' ),
		'TM' => __( 'Turkmenistan', 'my-tickets' ),
		'TC' => __( 'Turks And Caicos Islands', 'my-tickets' ),
		'TV' => __( 'Tuvalu', 'my-tickets' ),
		'UG' => __( 'Uganda', 'my-tickets' ),
		'UA' => __( 'Ukraine', 'my-tickets' ),
		'AE' => __( 'United Arab Emirates', 'my-tickets' ),
		'GB' => __( 'United Kingdom', 'my-tickets' ),
		'US' => __( 'United States', 'my-tickets' ),
		'UM' => __( 'United States Outlying Islands', 'my-tickets' ),
		'UY' => __( 'Uruguay', 'my-tickets' ),
		'UZ' => __( 'Uzbekistan', 'my-tickets' ),
		'VU' => __( 'Vanuatu', 'my-tickets' ),
		'VE' => __( 'Venezuela', 'my-tickets' ),
		'VN' => __( 'Viet Nam', 'my-tickets' ),
		'VG' => __( 'Virgin Islands, British', 'my-tickets' ),
		'VI' => __( 'Virgin Islands, U.S.', 'my-tickets' ),
		'WF' => __( 'Wallis And Futuna', 'my-tickets' ),
		'EH' => __( 'Western Sahara', 'my-tickets' ),
		'YE' => __( 'Yemen', 'my-tickets' ),
		'ZM' => __( 'Zambia', 'my-tickets' ),
		'ZW' => __( 'Zimbabwe', 'my-tickets' ),
	);

	$options = '';
	foreach ( $countries as $key => $value ) {
		$selected = ( $country === $key || $country === $value ) ? ' selected="selected"' : '';
		$options .= '<option value="' . esc_attr( $key ) . '"' . $selected . '>' . esc_html( $value ) . '</option>';
	}

	return $options;
}
/**
 * Check whether the options to collect addresses are turned on.
 *
 * @return bool
 */
function mt_always_collect_shipping() {
	$options  = array_merge( mt_default_settings(), get_option( 'mt_settings', array() ) );
	$shipping = ( isset( $options['mt_collect_shipping'] ) ) ? $options['mt_collect_shipping'] : false;
	$shipping = ( 'true' === $shipping ) ? true : false;

	return $shipping;
}

/**
 * Generate selector for choosing payment gateway.
 *
 * @return string
 */
function mt_gateways() {
	$selector = '';
	$options  = array_merge( mt_default_settings(), get_option( 'mt_settings', array() ) );
	$enabled  = $options['mt_gateway'];
	$url      = get_permalink( $options['mt_purchase_page'] );
	if ( 1 === count( $enabled ) ) {
		return '';
	} else {
		$labels = mt_setup_gateways();
		foreach ( $enabled as $gate ) {
			$current_gate = ( isset( $_GET['mt_gateway'] ) && in_array( $_GET['mt_gateway'], $enabled, true ) ) ? $_GET['mt_gateway'] : $options['mt_default_gateway'];
			if ( isset( $labels[ $gate ] ) ) {
				$checked = ( $gate === $current_gate ) ? ' class="active"' : '';
				$current = ( $gate === $current_gate ) ? ' aria-current="true"' : '';
				$label   = $labels[ $gate ]['label'];

				if ( isset( $options['mt_gateways'][ $gate ]['selector'] ) ) {
					$label = ( '' === $options['mt_gateways'][ $gate ]['selector'] ) ? $label : $options['mt_gateways'][ $gate ]['selector'];
				};
				$selector .= "<li$checked><a href='$url?mt_gateway=$gate' data-assign='$gate'$current>$label</a></li>";
			}
		}

		return "<div class='gateway-selector'><ul><li>" . __( 'Payment Gateway', 'my-tickets' ) . ": $selector</ul></div>";
	}
}

/**
 * Generate breadcrumb path for cart purchase process
 *
 * @param string|boolean $gateway Has a value if we're on payment process; false if not set.
 *
 * @return string
 */
function mt_generate_path( $gateway ) {
	$options = array_merge( mt_default_settings(), get_option( 'mt_settings', array() ) );
	$path    = '<span class="active"><a href="' . apply_filters( 'mt_home_breadcrumb_url', home_url() ) . '">' . __( 'Home', 'my-tickets' ) . '</a></span>';
	if ( false === $gateway ) {
		$path .= '<span class="inactive"><strong>' . __( 'Cart', 'my-tickets' ) . '</strong></span>';
	} else {
		$path .= '<span class="active"><a href="' . get_permalink( $options['mt_purchase_page'] ) . '">' . __( 'Cart', 'my-tickets' ) . '</a></span>';
	}
	if ( false === $gateway ) {
		$path .= '<span class="inactive">' . __( 'Payment', 'my-tickets' ) . '</span>';
	} else {
		$path .= '<span class="inactive"><strong>' . __( 'Payment', 'my-tickets' ) . '</strong></span>';
	}
	return "<div class='mt_purchase_path'>" . $path . '</div>';
}

/**
 * Generate cart. Show all events with tickets in cart unless event is already past the time when it can be ordered.
 * TODO: Display notice if item has been removed from cart.
 *
 * @param bool $user_ID User ID.
 *
 * @return mixed|string|void
 */
function mt_generate_cart( $user_ID = false ) {
	// if submitted successfully & payment required, toggle to payment form.
	$options     = array_merge( mt_default_settings(), get_option( 'mt_settings', array() ) );
	$gateway     = isset( $_POST['mt_gateway'] ) ? $_POST['mt_gateway'] : false;
	$type        = isset( $_POST['ticketing_method'] ) ? $_POST['ticketing_method'] : false;
	$breadcrumbs = mt_generate_path( $gateway );
	// TODO: If gateway is offline, mt_generate_gateway is never run. Use mt_generate_gateway to create button in both cases.
	// Need to handle the case where multiple gateways are available, however; can't display the gateway until after gateway is selected.
	if ( $gateway ) {
		$response = mt_update_cart( $_POST['mt_cart_order'] );
		$cart     = $response['cart'];
		$output   = mt_generate_gateway( $cart );
	} else {
		$cart           = mt_get_cart( $user_ID );
		$total          = apply_filters( 'mt_generate_cart_total', mt_total_cart( $cart ), $cart );
		$count          = mt_count_cart( $cart );
		$handling_total = ( isset( $options['mt_handling'] ) && '' !== $options['mt_handling'] ) ? $options['mt_handling'] : 0;
		$handling       = apply_filters( 'mt_money_format', $handling_total );
		$nonce          = wp_nonce_field( 'mt_cart_nonce', '_wpnonce', true, false );
		$enabled        = $options['mt_gateway'];
		$current_gate   = ( isset( $_GET['mt_gateway'] ) && in_array( $_GET['mt_gateway'], $enabled, true ) ) ? $_GET['mt_gateway'] : $options['mt_default_gateway'];
		$current_gate   = ( ! $total ) ? 'offline' : $current_gate; // Must be offline gateway if price is free.
		$gateway        = "<input type='hidden' name='mt_gateway' value='" . esc_attr( $current_gate ) . "' />";
		$cart_page      = get_permalink( $options['mt_purchase_page'] );
		if ( is_array( $cart ) && ! empty( $cart ) && $count > 0 ) {
			$output  = '
		<div class="mt_cart">
			<div class="mt-response" aria-live="assertive"></div>
			<form action="' . esc_url( $cart_page ) . '" method="POST">' . "
			<input class='screen-reader-text' type='submit' name='mt_submit' value='" . apply_filters( 'mt_submit_button_text', __( 'Review cart and make payment', 'my-tickets' ), $current_gate ) . "' />" . '
				' . $nonce . '
				' . $gateway;
			$output .= mt_generate_cart_table( $cart );
			if ( 0 !== $handling_total ) {
				// Translators: amount of handling fee.
				$output .= "<div class='mt_cart_handling'>" . apply_filters( 'mt_cart_handling_text', sprintf( __( 'A handling fee of %s will be applied to this purchase.', 'my-tickets' ), $handling ), $current_gate ) . '</div>';
			}
			if ( mt_handling_notice() ) {
				$output .= "<div class='mt_ticket_handling'>" . mt_handling_notice() . '</div>';
			}
			$custom_fields = apply_filters( 'mt_cart_custom_fields', array(), $cart, $gateway );
			$custom_output = '';
			foreach ( $custom_fields as $key => $field ) {
				$custom_output .= $field;
			}
			$button  = "<p class='mt_submit'><input type='submit' name='mt_submit' value='" . apply_filters( 'mt_submit_button_text', __( 'Review cart and make payment', 'my-tickets' ), $current_gate ) . "' /></p>";
			$output .= "<div class='mt_cart_total' aria-live='assertive'>" . apply_filters( 'mt_cart_total_content', '', $current_gate, $cart ) . apply_filters( 'mt_cart_ticket_total_text', __( 'Ticket Total:', 'my-tickets' ), $current_gate ) . " <span class='mt_total_number'>" . apply_filters( 'mt_money_format', $total ) . "</span></div>\n" . mt_invite_login_or_register() . "\n" . mt_required_fields( $cart, $custom_output ) . "\n" . "$button\n<input type='hidden' name='my-tickets' value='true' />" . apply_filters( 'mt_cart_hidden_fields', '' ) . '</form>' . mt_gateways() . mt_copy_cart() . '</div>';
		} else {
			do_action( 'mt_cart_is_empty' );
			// clear POST data to prevent re-submission of data.
			$_POST = array();
			if ( isset( $_GET['payment_id'] ) ) {
				$post_id = absint( $_GET['payment_id'] );
				$date    = get_post_modified_time( 'U', false, $post_id );
				if ( $date < ( mt_current_time() - 300 ) ) {
					// This transaction data is only available publically for 5 minutes after post is updated.
					return '';
				} else {
					$receipt  = get_post_meta( $post_id, '_receipt', true );
					$options  = array_merge( mt_default_settings(), get_option( 'mt_settings', array() ) );
					$link     = add_query_arg( 'receipt_id', $receipt, get_permalink( $options['mt_receipt_page'] ) );
					$purchase = get_post_meta( $post_id, '_purchased' );
					$append   = apply_filters( 'mt_confirmed_transaction', '', $receipt, $purchase, $post_id );
					$output   = "<div class='transaction-purchase panel'><div class='inner'><h4>" . __( 'Receipt ID:', 'my-tickets' ) . " <code><a href='$link'>$receipt</a></code></h4>" . mt_format_purchase( $purchase, 'html', $post_id ) . $append . '</div></div>';
					do_action( 'mt_purchase_completed', $post_id, $link, $purchase );
				}
			} else {
				$output = apply_filters( 'mt_cart_is_empty_text', "<p class='cart-empty'>" . __( 'Your cart is currently empty.', 'my-tickets' ) . '</p>' );
			}
		}
	}

	return $breadcrumbs . $output;
}

/**
 * Copy public cart into admin.
 *
 * @return string
 */
function mt_copy_cart() {
	if ( current_user_can( 'mt-copy-cart' ) || current_user_can( 'manage_options' ) ) {
		$unique_id = ( isset( $_COOKIE['mt_unique_id'] ) ) ? sanitize_text_field( $_COOKIE['mt_unique_id'] ) : false;
		if ( $unique_id ) {
			return "<p><a href='" . esc_url( admin_url( "post-new.php?post_type=mt-payments&amp;cart=$unique_id" ) ) . "'>" . __( 'Create new admin payment with this cart', 'my-tickets' ) . '</a></p>';
		}
	}

	return '';
}

add_filter( 'mt_link_title', 'mt_core_link_title', 10, 2 );
/**
 * Filter event titles to display as linked in cart when a link is available. Occurrence IDs are not available, so details link can't be provided.
 *
 * @param string $event_title Title of event.
 * @param object $event Event post object.
 *
 * @return string linked title if a link is available (any event post or event with a link)
 */
function mt_core_link_title( $event_title, $event ) {
	$event_title = apply_filters( 'mt_the_title', $event_title, $event );
	$event_id    = get_post_meta( $event->ID, '_mc_event_id', true );
	if ( $event_id && function_exists( 'mc_get_details_link' ) ) {
		$event = mc_get_event_core( $event_id );
		$link  = mc_event_link( $event );
	} else {
		$link = get_permalink( $event->ID );
	}
	if ( $link ) {
		return "<a href='$link'>$event_title</a>";
	} else {
		return $event_title;
	}
}

/**
 * Generate tabular data for cart. Include custom fields if defined.
 *
 * @param array  $cart Cart data.
 * @param string $format Format to display.
 *
 * @return string
 */
function mt_generate_cart_table( $cart, $format = 'cart' ) {
	$options = array_merge( mt_default_settings(), get_option( 'mt_settings', array() ) );
	if ( ! is_admin() ) {
		$caption = ( 'confirmation' === $format ) ? __( 'Review and Purchase', 'my-tickets' ) : __( 'Shopping Cart', 'my-tickets' );
		$class   = ' mt_cart';
	} else {
		$caption = __( 'Ticket Order', 'my-tickets' );
		$class   = '';
	}

	$output = '
	<table class="widefat' . $class . '"><caption>' . $caption . '</caption>
			<thead>
				<tr>
					<th scope="col">' . __( 'Event', 'my-tickets' ) . '</th><th scope="col">' . __( 'Order', 'my-tickets' ) . '</th>';
	if ( 'cart' === $format ) {
		$output .= '<th scope="col" class="mt-update-column">' . __( 'Update', 'my-tickets' ) . '</th>';
	}
	$output .= '</tr></thead><tbody>';
	$total   = 0;
	if ( is_array( $cart ) && ! empty( $cart ) ) {
		foreach ( $cart as $event_id => $order ) {
			// If this post doesn't exist, don't include in cart, e.g. event was deleted after being added to cart.
			// Also omit trashed status.
			if ( false === get_post_status( $event_id ) || 'trash' === get_post_status( $event_id ) ) {
				continue;
			}
			$expired = mt_expired( $event_id );
			if ( ! $expired ) {
				// There is no payment ID yet, but $_POST data and $_COOKIE data should be available for pricing.
				$prices = mt_get_prices( $event_id );
				$event  = get_post( $event_id );
				if ( ! is_object( $event ) ) {
					// this is coming from a deleted event.
					continue;
				}
				$title = apply_filters( 'mt_link_title', $event->post_title, $event );
				$image = ( has_post_thumbnail( $event_id ) ) ? get_the_post_thumbnail( $event_id, array( 80, 80 ) ) : '';
				$data  = get_post_meta( $event_id, '_mc_event_data', true );
				if ( ! is_array( $data ) || empty( $data ) ) {
					continue;
				}
				$registration = get_post_meta( $event_id, '_mt_registration_options', true );
				$general      = ( isset( $data['general_admission'] ) && 'on' === $data['general_admission'] ) ? true : false;
				$validity     = ( isset( $data['event_valid'] ) ) ? $data['event_valid'] : 0;
				$valid_til    = mt_date( get_option( 'date_format' ), strtotime( '+ ' . $validity ) );
				// Translators: Date ticket valid until.
				$date      = ( $general ) ? sprintf( __( 'Tickets valid until %s', 'my-tickets' ), $valid_til ) : $data['event_begin'] . ' ' . $data['event_time'];
				$dt_format = apply_filters( 'mt_cart_datetime', get_option( 'date_format' ) . ' @ ' . get_option( 'time_format' ) );
				$datetime  = "<span class='mt-datetime'>" . ( ( $general ) ? $date : date_i18n( $dt_format, strtotime( $date ) ) ) . '</span>';
				if ( is_array( $order ) && ! empty( $order ) ) {
					foreach ( $order as $type => $count ) {
						if ( ! mt_can_order( $type ) ) {
							continue;
						}
						if ( $count > 0 ) {
							if ( isset( $prices[ $type ] ) ) {
								$price      = mt_handling_price( $prices[ $type ]['price'], $event_id, $type );
								$orig_price = ( isset( $prices[ $type ]['orig_price'] ) ) ? mt_handling_price( $prices[ $type ]['orig_price'], $event_id, $type ) : $price;
								$label      = $prices[ $type ]['label'];
								if ( 'discrete' === $registration['counting_method'] ) {
									$available = absint( $prices[ $type ]['tickets'] );
									$sold      = absint( isset( $prices[ $type ]['sold'] ) ? $prices[ $type ]['sold'] : 0 );
								} else {
									$available = absint( $registration['total'] );
									$sold      = 0;
									foreach ( $registration['prices'] as $pricetype ) {
										$sold = $sold + intval( ( isset( $pricetype['sold'] ) ) ? $pricetype['sold'] : 0 );
									}
								}
								$default_available = apply_filters( 'mt_default_available', 100, $registration );
								$remaining         = ( 'general' === $registration['counting_method'] ) ? $default_available : $available - $sold;
								$max_limit         = apply_filters( 'mt_max_sale_per_event', false );
								if ( $max_limit ) {
									$max = ( $max_limit > $remaining ) ? $remaining : $max_limit;
								} else {
									$max = $remaining;
								}
								if ( $count > $max ) {
									$count = $max;
								}
								if ( 'cart' === $format || is_admin() ) {
									$hidden = "
											<input type='hidden' class='mt_count' name='mt_cart_order[$event_id][$type][count]' value='$count' />
											<input type='hidden' name='mt_cart_order[$event_id][$type][price]' value='$price' />";
									if ( isset( $prices[ $type ]['orig_price'] ) ) {
										$hidden .= "<input type='hidden' name='mt_cart_order[$event_id][$type][orig_price]' value='$orig_price' />";
									}
								} else {
									$hidden = '';
								}
								$total  = $total + ( $price * $count );
								$custom = apply_filters( 'mt_show_in_cart_fields', '', $event_id );
								// Translators: (number of tickets) at (price per ticket).
								$cart_message = sprintf( __( '%1$s at %2$s', 'my-tickets' ), "<span class='count' data-limit='$max'>$count</span>", apply_filters( 'mt_money_format', $price ) );
								$output      .= "
											<tr id='mt_cart_order_$event_id" . '_' . "$type' class='mt_row_$event_id'>
												<th scope='row'>$image$title: <em>$label</em><br />$datetime$hidden$custom</th>
												<td aria-live='assertive'>" . $cart_message . '</td>';
								if ( 'cart' === $format && apply_filters( 'mt_include_update_column', true ) ) {
									if ( 'true' === $registration['multiple'] ) {
										$output .= "<td class='mt-update-column'><div class='mt-update-buttons'><button data-id='$event_id' data-type='$type' rel='#mt_cart_order_$event_id" . '_' . "$type' class='more'>+<span class='screen-reader-text'> " . __( 'Add a ticket', 'my-tickets' ) . "</span></button> <button data-id='$event_id' data-type='$type' rel='#mt_cart_order_$event_id" . '_' . "$type' class='less'>-<span class='screen-reader-text'> " . __( 'Remove a ticket', 'my-tickets' ) . "</span></button> <button data-id='$event_id' data-type='$type' rel='#mt_cart_order_$event_id" . '_' . "$type' class='remove'>x<span class='screen-reader-text'> " . __( 'Remove from cart', 'my-tickets' ) . '</span></button></div></td>';
									} else {
										$output .= "<td class='mt-update-column'><div class='mt-update-buttons'><button data-id='$event_id' data-type='$type' rel='#mt_cart_order_$event_id" . '_' . "$type' class='remove'>x<span class='screen-reader-text'> " . __( 'Remove from cart', 'my-tickets' ) . '</span></button>' . apply_filters( 'mt_no_multiple_registration', '' ) . '</div></td>';
									}
								}
								$output .= '</tr>';
							}
						}
					}
				}
			}
		}
	}
	$output .= '</tbody></table>';

	return $output;
}

/**
 * Get total $ value of saved cart.
 *
 * @param array $cart Cart data.
 * @param int   $payment_id Payment ID.
 * @param bool  $apply_discounts 'False' to get original total without discounts.
 *
 * @return float
 */
function mt_total_cart( $cart, $payment_id = false, $apply_discounts = true ) {
	$total = 0;
	if ( is_array( $cart ) ) {
		foreach ( $cart as $event => $order ) {
			$expired = mt_expired( $event );
			if ( ! $expired ) {
				$prices = mt_get_prices( $event, $payment_id );
				if ( is_array( $order ) ) {
					foreach ( $order as $type => $count ) {
						if ( $count > 0 ) {
							$count = intval( $count );
							$price = ( isset( $prices[ $type ] ) ) ? $prices[ $type ]['price'] : '0';
							if ( $price ) {
								$price = mt_handling_price( $price, $event );
							}
							$total = $total + ( $price * $count );
						}
					}
				}
			}
		}
	}

	return ( $apply_discounts ) ? apply_filters( 'mt_apply_total_discount', $total, $payment_id ) : $total;
}

/**
 * Get number of tickets in current cart.
 *
 * @param array $cart Cart data.
 *
 * @return int
 */
function mt_count_cart( $cart ) {
	$total = 0;
	if ( is_array( $cart ) ) {
		foreach ( $cart as $event => $order ) {
			$expired = mt_expired( $event );
			if ( ! $expired ) {
				if ( is_array( $order ) ) {
					foreach ( $order as $type => $count ) {
						$total = $total + intval( $count );
					}
				}
			}
		}
	}

	return $total;
}

/**
 * Generate payment gateway code from selected gateway.
 *
 * @param array $cart cart data.
 *
 * uses: filter mt_gateway (pull gateway form).
 * uses: filter mt_form_wrapper (html wrapper around gateway).
 *
 * @return string
 */
function mt_generate_gateway( $cart ) {
	$options    = array_merge( mt_default_settings(), get_option( 'mt_settings', array() ) );
	$return_url = get_permalink( $options['mt_purchase_page'] );
	// Translators: cart url.
	$link         = apply_filters( 'mt_return_link', "<p class='return-to-cart'>" . sprintf( __( '<a href="%s">Return to cart</a>', 'my-tickets' ), $return_url ) . '</p>' );
	$confirmation = mt_generate_cart_table( $cart, 'confirmation' );
	$payment      = mt_get_data( 'payment' );
	$total        = mt_total_cart( $cart, $payment );
	$count        = mt_count_cart( $cart );
	if ( $count > 0 ) {
		$ticket_method  = ( isset( $_POST['ticketing_method'] ) ) ? sanitize_text_field( $_POST['ticketing_method'] ) : 'willcall';
		$shipping_total = ( 'postal' === $ticket_method && is_numeric( $options['mt_shipping'] ) ) ? $options['mt_shipping'] : 0;
		$handling_total = ( isset( $options['mt_handling'] ) && is_numeric( $options['mt_handling'] ) ) ? $options['mt_handling'] : 0;
		$shipping       = ( $shipping_total ) ? "<div class='mt_cart_shipping mt_cart_label'>" . __( 'Shipping:', 'my-tickets' ) . " <span class='mt_shipping_number mt_cart_value'>" . apply_filters( 'mt_money_format', $shipping_total ) . '</span></div>' : '';
		$handling       = ( $handling_total ) ? "<div class='mt_cart_handling mt_cart_label'>" . __( 'Handling:', 'my-tickets' ) . " <span class='mt_handling_number mt_cart_value'>" . apply_filters( 'mt_money_format', $handling_total ) . '</span></div>' : '';
		$tick_handling  = mt_handling_notice();
		$mt_gateway     = ( isset( $_POST['mt_gateway'] ) ) ? sanitize_text_field( $_POST['mt_gateway'] ) : 'offline';
		$other_charges  = apply_filters( 'mt_custom_charges', 0, $cart, $mt_gateway );
		$other_notices  = apply_filters( 'mt_custom_notices', '', $cart, $mt_gateway );
		// If everything in cart is free, don't pass through payment gateway.
		if ( 0 === (int) ( $total + $shipping_total + $handling_total + $other_charges ) && 'offline' !== $mt_gateway ) {
			$mt_gateway = 'offline';
		}

		$report_total = "<div class='mt_cart_total'>" . apply_filters( 'mt_cart_total_text', __( 'Total:', 'my-tickets' ), $mt_gateway ) . " <span class='mt_total_number'>" . apply_filters( 'mt_money_format', $total + $shipping_total + $handling_total + $other_charges ) . '</span></div>';
		$args         = apply_filters(
			'mt_payment_form_args',
			array(
				'cart'    => $cart,
				'total'   => $total,
				'payment' => $payment,
				'method'  => $ticket_method,
			)
		);

		$form  = apply_filters( 'mt_gateway', '', $mt_gateway, $args );
		$form  = apply_filters( 'mt_form_wrapper', $form );
		$form .= "<span id='mt_unsubmitted'></span>";

		return $link . $confirmation . "<div class='mt-after-cart'>" . $tick_handling . $shipping . $handling . $other_notices . $report_total . '</div>' . $form;
	} else {
		do_action( 'mt_cart_is_empty' );

		return apply_filters( 'mt_cart_is_empty_text', "<p class='cart-empty'>" . __( 'Your cart is currently empty.', 'my-tickets' ) . '</p>' );
	}
}

add_filter( 'mt_form_wrapper', 'mt_wrap_payment_button' );
/**
 * Generate HTML to wrap gateway form.
 *
 * @param string $form Form HTML.
 *
 * @return string
 */
function mt_wrap_payment_button( $form ) {
	return "<div class='mt-payment-form'>" . $form . '</div>';
}

/**
 * If SSL is enabled, replace HTTP in URL.
 *
 * @param string $url site URL.
 *
 * @return mixed
 */
function mt_replace_http( $url ) {
	$options = array_merge( mt_default_settings(), get_option( 'mt_settings', array() ) );
	if ( 'true' === $options['mt_ssl'] ) {
		$url = preg_replace( '|^http://|', 'https://', $url );
	}

	return $url;
}

/**
 * Test whether an event is no longer available for purchase. If user has capability to order expired events, allow.
 *
 * @param int     $event An event ID.
 * @param boolean $react Should a reaction happen.
 *
 * @return bool
 */
function mt_expired( $event, $react = false ) {
	if ( current_user_can( 'mt-order-expired' ) || current_user_can( 'manage_options' ) ) {
		return false;
	}
	$options = get_post_meta( $event, '_mt_registration_options', true );
	if ( 'general' === $options['counting_method'] ) {
		// General admissions sales do not expire.
		return false;
	}
	$expired = get_post_meta( $event, '_mt_event_expired', true );
	if ( 'true' === $expired ) {
		return true;
	} else {
		$data = get_post_meta( $event, '_mc_event_data', true );
		if ( is_array( $data ) && is_array( $options ) && ! empty( $options ) ) {
			if ( ! isset( $data['event_begin'] ) || ( isset( $data['general_admission'] ) && 'on' === $data['general_admission'] ) ) {
				return false;
			}
			$expires    = ( isset( $options['reg_expires'] ) ) ? $options['reg_expires'] : 0;
			$expiration = $expires * 60 * 60;
			$begin      = strtotime( $data['event_begin'] . ' ' . $data['event_time'] ) - $expiration;
			if ( mt_date_comp( mt_date( 'Y-m-d H:i:s', $begin ), mt_date( 'Y-m-d H:i:s', mt_current_time() ) ) && $react ) {
				update_post_meta( $event, '_mt_event_expired', 'true' );
				do_action( 'mt_ticket_sales_closed', $event, $options, 'closed' );

				return true;
			}
		}

		return false;
	}

	return false;
}

/**
 * Get saved cart data for user.
 *
 * @param bool|int    $user_ID User ID.
 * @param bool|string $cart_id Cart identifier.
 *
 * @return array|mixed
 */
function mt_get_cart( $user_ID = false, $cart_id = false ) {
	$cart      = array();
	$unique_id = ( isset( $_COOKIE['mt_unique_id'] ) ) ? sanitize_text_field( $_COOKIE['mt_unique_id'] ) : false;
	if ( $user_ID ) {
		$cart = get_user_meta( $user_ID, '_mt_user_cart', true );
	} elseif ( ! $user_ID && $cart_id ) {
		$cart = get_transient( 'mt_' . $cart_id . '_cart' );
	} else {
		if ( is_user_logged_in() ) {
			$current_user = wp_get_current_user();
			$cart         = get_user_meta( $current_user->ID, '_mt_user_cart', true );
		} else {
			if ( $unique_id ) {
				$cart = get_transient( 'mt_' . $unique_id . '_cart' );
			}
		}
	}
	if ( is_user_logged_in() && ! $cart ) {
		if ( $unique_id ) {
			$cart = get_transient( 'mt_' . $unique_id . '_cart' );
		}
	}
	return $cart;
}

add_action( 'wp_head', 'mt_cart_meta', 1 );
/**
 * Adds the user's cart ID into <head> meta data for admin retrieval for customer assistance.
 *
 * Cart Data does not expose any user-specific information; contains only event ID and tickets selected.
 */
function mt_cart_meta() {
	$unique_id = ( isset( $_COOKIE['mt_unique_id'] ) ) ? sanitize_text_field( $_COOKIE['mt_unique_id'] ) : false;
	if ( $unique_id ) {
		echo "<meta name='cart_id' value='" . esc_attr( $unique_id ) . "' />\n";
	}
}
