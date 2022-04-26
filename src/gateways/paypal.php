<?php
/**
 * PayPal payment gateway.
 *
 * @category Payments
 * @package  My Tickets
 * @author   Joe Dolson
 * @license  GPLv2 or later
 * @link     https://www.joedolson.com/my-tickets/
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
} // Exit if accessed directly

add_action( 'mt_receive_ipn', 'mt_paypal_ipn' );
/**
 * Process notification from PayPal following payment.
 *
 * @return null
 */
function mt_paypal_ipn() {
	if ( isset( $_REQUEST['mt_paypal_ipn'] ) && 'true' === $_REQUEST['mt_paypal_ipn'] ) {
		if ( isset( $_POST['payment_status'] ) ) {
			$options  = array_merge( mt_default_settings(), get_option( 'mt_settings', array() ) );
			$receiver = ( isset( $options['mt_gateways']['paypal']['email'] ) ) ? strtolower( $options['mt_gateways']['paypal']['email'] ) : false;
			$url      = ( 'true' === $options['mt_use_sandbox'] ) ? 'https://ipnpb.sandbox.paypal.com/cgi-bin/webscr' : 'https://ipnpb.paypal.com/cgi-bin/webscr';

			$req = 'cmd=_notify-validate';
			foreach ( $_POST as $key => $value ) {
				$req .= "&$key=" . urlencode( $value ); // These values are sent back to PayPal for verification. Must match original values sent.
			}

			$args   = wp_parse_args( $req, array() );
			$params = array(
				'body'        => $args,
				'timeout'     => 30,
				'user-agent'  => 'WordPress/My Tickets',
				'httpversion' => '1.1',
			);

			// transaction variables to store.
			$payment_status = sanitize_text_field( $_POST['payment_status'] );
			if ( isset( $_POST['num_cart_items'] ) ) {
				// My Tickets support for cart formatted requests. My Tickets only supports processing of a single order, however.
				$item_number = absint( $_POST['item_number1'] );
			} else {
				$item_number = absint( $_POST['item_number'] );
			}
			$price            = sanitize_text_field( $_POST['mc_gross'] );
			$payment_currency = sanitize_text_field( $_POST['mc_currency'] );
			$receiver_email   = sanitize_email( $_POST['receiver_email'] );
			$payer_email      = sanitize_email( $_POST['payer_email'] );
			$payer_first_name = sanitize_text_field( $_POST['first_name'] );
			$payer_last_name  = sanitize_text_field( $_POST['last_name'] );
			$mc_fee           = sanitize_text_field( $_POST['mc_fee'] );
			$txn_id           = sanitize_text_field( $_POST['txn_id'] );
			$parent           = isset( $_POST['parent_txn_id'] ) ? sanitize_text_field( $_POST['parent_txn_id'] ) : '';
			$ipn              = wp_remote_post( $url, $params );

			if ( is_wp_error( $ipn ) ) {
				// send an email notification about this error.
				wp_mail( $options['mt_to'], __( 'My Tickets could not contact PayPal', 'my-tickets' ), print_r( $ipn, 1 ) );
				status_header( 503 );
				die;
			}
			$response      = $ipn['body'];
			$response_code = $ipn['response']['code'];

			// map paypal IPN format of address to MT format
			// All gateways must map shipping addresses to this format.
			$address = array(
				'street'  => isset( $_POST['address_street'] ) ? sanitize_text_field( $_POST['address_street'] ) : '',
				'street2' => isset( $_POST['address2'] ) ? sanitize_text_field( $_POST['address2'] ) : '',
				'city'    => isset( $_POST['address_city'] ) ? sanitize_text_field( $_POST['address_city'] ) : '',
				'state'   => isset( $_POST['address_state'] ) ? sanitize_text_field( $_POST['address_state'] ) : '',
				'country' => isset( $_POST['address_country_code'] ) ? sanitize_text_field( $_POST['address_country_code'] ) : '',
				'code'    => isset( $_POST['address_zip'] ) ? sanitize_text_field( $_POST['address_zip'] ) : '',
			);

			$data = array(
				'transaction_id' => $txn_id,
				'price'          => $price,
				'currency'       => $payment_currency,
				'email'          => $payer_email,
				'first_name'     => $payer_first_name,
				'last_name'      => $payer_last_name,
				'fee'            => $mc_fee,
				'parent'         => $parent,
				'status'         => $payment_status,
				'purchase_id'    => $item_number,
				'shipping'       => $address,
			);
			// Die conditions for PayPal.
			// If receiver email or currency are wrong, this is probably a fraudulent transaction.
			// If no receiver email provided, that check will be skipped.
			if ( 'Refunded' === $payment_status ) {
				$value_match = true; // It won't match, and probably doesn't need to.
			} else {
				$value_match = mt_check_payment_amount( $price, $item_number );
			}
			$error_msg      = array();
			$messages       = '';
			$receiver       = strtolower( $receiver );
			$receiver_email = strtolower( $receiver_email );
			if ( ( $receiver && ( $receiver_email !== $receiver ) ) || $payment_currency !== $options['mt_currency'] || false === $value_match ) {
				$price       = number_format( (float) $price, 2 );
				$value_match = number_format( (float) $value_match, 2 );
				// Translators: Item Number of payment triggering error.
				if ( (string) $price !== (string) $value_match ) {
					// Translators: price paid, price expected.
					$error_msg[] = sprintf( __( 'Price paid did not match the price expected: %1$s paid vs %2$s expected.', 'my-tickets' ), $price, $value_match );
				}
				if ( $receiver_email !== $receiver ) {
					// Translators: email provided by PayPal, email in My Tickets settings.
					$error_msg[] = sprintf( __( 'Receiver Email and PayPal Email did not match: %1$s vs %2$s. Please check that the email in your My Tickets settings matches the primary email in your PayPal account.', 'my-tickets' ), $receiver_email, $receiver );
				}
				if ( $payment_currency !== $options['mt_currency'] ) {
					// Translators: currency received, currency expected.
					$error_msg[] = sprintf( __( 'Currency received did not match the currency expected: %1$s vs %2$s.', 'my-tickets' ), $payment_currency, $options['mt_currency'] );
				}
				foreach ( $error_msg as $msg ) {
					$messages .= "\n\n" . $msg;
				}
				// Translators: purchase ID.
				wp_mail( $options['mt_to'], __( 'Payment Conditions Error', 'my-tickets' ), sprintf( __( 'There were errors processing payment on purchase ID %s:', 'my-tickets' ), $item_number ) . $messages . "\n" . print_r( $data, 1 ) );
				status_header( 200 ); // Why 200? Because that's the only way to stop PayPal.
				die;
			}
			mt_handle_payment( $response, $response_code, $data, $_POST );
			// Everything's all right.
			status_header( 200 );
		} else {
			if ( isset( $_POST['txn_type'] ) ) {
				// this is a transaction other than a purchase.
				if ( 'dispute' === $_POST['case_type'] ) {
					$posts = get_posts(
						array(
							'post_type'  => 'mt-payments',
							'meta_key'   => '_transaction_id',
							'meta_value' => sanitize_text_field( $_POST['txn_id'] ),
						)
					);
					if ( ! empty( $posts ) ) {
						$post = $posts[0];
						update_post_meta( $post->ID, '_dispute_reason', sanitize_text_field( $_POST['reason_code'] ) );
						update_post_meta( $post->ID, '_dispute_message', sanitize_text_field( $_POST['buyer_additional_information'] ) );
					}
				}
				status_header( 200 );
			}
			status_header( 503 );
			die;
		}
	}

	return;
}

add_action( 'http_api_curl', 'mt_paypal_http_api_curl' );
/**
 * Set cURL to use SSL version supporting TLS 1.2
 *
 * @param object $handle cURL object.
 */
function mt_paypal_http_api_curl( $handle ) {
	curl_setopt( $handle, CURLOPT_SSLVERSION, 6 );
}

add_filter( 'mt_shipping_fields', 'mt_paypal_shipping_fields', 10, 2 );
/**
 * Rename shipping fields as needed for PayPal.
 *
 * @param string $form Original form fields.
 * @param string $gateway Selected gateway.
 *
 * @return string
 */
function mt_paypal_shipping_fields( $form, $gateway ) {
	if ( 'paypal' === $gateway ) {
		$search  = array(
			'mt_shipping_street',
			'mt_shipping_street2',
			'mt_shipping_city',
			'mt_shipping_state',
			'mt_shipping_country',
			'mt_shipping_code',
		);
		$replace = array( 'address1', 'address2', 'city', 'state', 'country', 'zip' );

		return str_replace( $search, $replace, $form );
	}

	return $form;
}

add_filter( 'mt_format_transaction', 'mt_paypal_transaction', 10, 2 );
/**
 * Optional filter to modify return from PayPal.
 *
 * @param array  $transaction Transaction data.
 * @param string $gateway Selected gateway.
 *
 * @return array
 */
function mt_paypal_transaction( $transaction, $gateway ) {
	if ( 'paypal' === $gateway ) {
		// alter return value if desired.
	}

	return $transaction;
}

add_filter( 'mt_setup_gateways', 'mt_setup_paypal', 10, 1 );
/**
 * Setup PayPal settings fields.
 *
 * @param array $gateways Existing gateways array.
 *
 * @return array
 */
function mt_setup_paypal( $gateways ) {
	$gateways['paypal'] = array(
		'label'  => __( 'PayPal', 'my-tickets' ),
		'fields' => array(
			'email'       => __( 'PayPal email (primary)', 'my-tickets' ),
			'merchant_id' => __( 'PayPal Merchant ID', 'my-tickets' ),
			'notes'       => __( 'PayPal Notes for Email Templates', 'my-tickets' ),
			'selector'    => __( 'Payment Gateway selector label', 'my-tickets' ),
		),
		// Translators: URL recommended for My Tickets IPN.
		'note'   => sprintf( __( 'You need IPN (Instant Payment Notification) enabled in your PayPal account to handle payments. Your IPN address for My Tickets is currently %s.', 'my-tickets' ), '<code>' . add_query_arg( 'mt_paypal_ipn', 'true', home_url( '/' ) ) . '</code>' ),
	);

	return $gateways;
}

add_filter( 'mt_gateway', 'mt_gateway_paypal', 10, 3 );
/**
 * Setup PayPal payment fields..
 *
 * @param string $form Payment form.
 * @param string $gateway Selected gateway.
 * @param array  $args Setup arguments.
 *
 * @return array
 */
function mt_gateway_paypal( $form, $gateway, $args ) {
	if ( 'paypal' === $gateway ) {
		$options        = array_merge( mt_default_settings(), get_option( 'mt_settings', array() ) );
		$payment_id     = $args['payment'];
		$handling       = ( isset( $options['mt_handling'] ) && is_numeric( $options['mt_handling'] ) ) ? $options['mt_handling'] : 0;
		$total          = $args['total'] + $handling;
		$shipping       = ( 'postal' === $args['method'] || ( isset( $options['mt_collect_shipping'] ) && 'true' === $options['mt_collect_shipping'] ) ) ? 2 : 1;
		$shipping_price = ( 'postal' === $args['method'] ) ? number_format( $options['mt_shipping'], 2 ) : 0;
		$use_sandbox    = $options['mt_use_sandbox'];
		$currency       = $options['mt_currency'];
		$merchant       = $options['mt_gateways']['paypal']['merchant_id'];
		$purchaser      = get_the_title( $payment_id );
		// Translators: Site's name, purchaser name.
		$item_name  = apply_filters( 'mt_paypal_item_name', sprintf( __( '%1$s Order from %2$s', 'my-tickets' ), get_option( 'blogname' ), $purchaser ), $payment_id );
		$return_url = add_query_arg(
			array(
				'response_code' => 'thanks',
				'gateway'       => 'paypal',
				'payment_id'    => $payment_id,
			),
			get_permalink( $options['mt_purchase_page'] )
		);
		$form       = "
		<form action='" . ( 'true' !== $use_sandbox ? 'https://www.paypal.com/cgi-bin/webscr' : 'https://www.sandbox.paypal.com/cgi-bin/webscr' ) . "' method='POST'>
		<input type='hidden' name='cmd' value='_xclick' />
		<input type='hidden' name='business' value='" . esc_attr( $merchant ) . "' />
		<input type='hidden' name='item_name' value='" . esc_attr( $item_name ) . "' />
		<input type='hidden' name='item_number' value='" . esc_attr( $payment_id ) . "' />
		<input type='hidden' name='amount' value='" . esc_attr( $total ) . "' />
		<input type='hidden' name='no_shipping' value='" . esc_attr( $shipping ) . "' />
		<input type='hidden' name='shipping' value='" . esc_attr( $shipping_price ) . "' />
		<input type='hidden' name='no_note' value='1' />
		<input type='hidden' name='currency_code' value='" . esc_attr( $currency ) . "' />";
		$form      .= "
		<input type='hidden' name='notify_url' value='" . mt_replace_http( add_query_arg( 'mt_paypal_ipn', 'true', esc_url( get_permalink( $options['mt_purchase_page'] ) ) ) ) . "' />
		<input type='hidden' name='return' value='" . mt_replace_http( esc_url( $return_url ) ) . "' />
		<input type='hidden' name='cancel_return' value='" . mt_replace_http( add_query_arg( 'response_code', 'cancel', esc_url( get_permalink( $options['mt_purchase_page'] ) ) ) ) . "' />";
		$form      .= mt_render_field( 'address', 'paypal' );
		$form      .= "<input type='submit' name='submit' class='button' value='" . esc_attr( apply_filters( 'mt_gateway_button_text', __( 'Make Payment through PayPal', 'my-tickets' ), $gateway ) ) . "' />";
		$form      .= apply_filters( 'mt_paypal_form', '', $gateway, $args );
		$form      .= '</form>';
	}

	return $form;
}

/**
 * Currencies supported by PayPal.
 *
 * @return array
 */
function mt_paypal_supported() {
	return array( 'AUD', 'BRL', 'CAD', 'CZK', 'DKK', 'EUR', 'HKD', 'HUF', 'ILS', 'JPY', 'MYR', 'MXN', 'NOK', 'NZD', 'PHP', 'PLN', 'GBP', 'RUB', 'SGD', 'SEK', 'CHF', 'TWD', 'THB', 'TRY', 'USD' );
}

add_filter( 'mt_currencies', 'mt_paypal_currencies', 10, 1 );
/**
 * If this gateway is active, limit currencies to supported currencies.
 *
 * @param array $currencies All currencies.
 *
 * @return array supported currencies.
 */
function mt_paypal_currencies( $currencies ) {
	$options     = ( ! is_array( get_option( 'mt_settings' ) ) ) ? array() : get_option( 'mt_settings' );
	$defaults    = mt_default_settings();
	$options     = array_merge( $defaults, $options );
	$mt_gateways = $options['mt_gateway'];

	if ( is_array( $mt_gateways ) && in_array( 'authorizenet', $mt_gateways, true ) ) {
		$paypal = mt_paypal_supported();
		$return = array();
		foreach ( $paypal as $currency ) {
			$keys = array_keys( $currencies );
			if ( in_array( $currency, $keys, true ) ) {
				$return[ $currency ] = $currencies[ $currency ];
			}
		}

		return $return;
	}

	return $currencies;
}
