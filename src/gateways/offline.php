<?php
/**
 * Offline payment gateway
 *
 * @category Payment
 * @package  My Tickets
 * @author   Joe Dolson
 * @license  GPLv2 or later
 * @link     https://www.joedolson.com/my-tickets/
 */

add_filter( 'mt_shipping_fields', 'mt_offline_shipping_fields', 10, 2 );
/**
 * Rename shipping fields as needed for Offline gateway.
 *
 * @param string $form Original form fields.
 * @param string $gateway Selected gateway.
 *
 * @return string
 */
function mt_offline_shipping_fields( $form, $gateway ) {
	if ( 'offline' === $gateway ) {
		$search  = array(
			'mt_shipping_street',
			'mt_shipping_city',
			'mt_shipping_state',
			'mt_shipping_country',
			'mt_shipping_code',
		);
		$replace = array( 'address', 'city', 'state', 'address_country', 'zip' );

		return str_replace( $search, $replace, $form );
	}

	return $form;
}

add_filter( 'mt_format_transaction', 'mt_format_offline_transaction', 10, 2 );
/**
 * Optional filter to modify return from PayPal.
 *
 * @param array  $transaction Transaction data.
 * @param string $gateway Selected gateway.
 *
 * @return array
 */
function mt_format_offline_transaction( $transaction, $gateway ) {
	if ( 'offline' === $gateway ) {
		// alter return value if desired.
	}

	return $transaction;
}

add_filter( 'mt_setup_gateways', 'mt_setup_offline', 10, 1 );
/**
 * Setup Offline settings fields.
 *
 * @param array $gateways Existing gateways array.
 *
 * @return array
 */
function mt_setup_offline( $gateways ) {
	$gateways['offline'] = array(
		'label'  => __( 'Offline', 'my-tickets' ),
		'fields' => array(
			'notes'    => __( 'Offline Payment Notes', 'my-tickets' ),
			'selector' => __( 'Gateway selector label', 'my-tickets' ),
		),
	);

	return $gateways;
}

add_filter( 'mt_gateway', 'mt_gateway_offline', 10, 3 );
/**
 * Setup Offline payment fields..
 *
 * @param string $form Payment form.
 * @param string $gateway Selected gateway.
 * @param array  $args Setup arguments.
 *
 * @return array
 */
function mt_gateway_offline( $form, $gateway, $args ) {
	if ( 'offline' === $gateway ) {
		$options        = array_merge( mt_default_settings(), get_option( 'mt_settings', array() ) );
		$payment_id     = $args['payment'];
		$handling       = absint( ( isset( $options['mt_handling'] ) ) ? $options['mt_handling'] : 0 );
		$total          = $args['total'] + $handling;
		$shipping_price = ( 'postal' === $args['method'] ) ? number_format( $options['mt_shipping'], 2 ) : 0;
		$currency       = $options['mt_currency'];
		// Translators: Site name.
		$purchase = sprintf( __( '%s Order', 'my-tickets' ), get_option( 'blogname' ) );
		$form     = "
		<form action='" . get_permalink( $options['mt_purchase_page'] ) . "' method='POST'>
		<input type='hidden' name='mt_purchase' value='" . esc_attr( $purchase ) . "' />
		<input type='hidden' name='mt_item' value='" . esc_attr( $payment_id ) . "' />
		<input type='hidden' name='mt_amount' value='" . esc_attr( $total ) . "' />
		<input type='hidden' name='mt_shipping' value='" . esc_attr( $shipping_price ) . "' />
		<input type='hidden' name='mt_currency' value='" . esc_attr( $currency ) . "' />
		<input type='hidden' name='mt_gateway_offline' value='true' />";
		$form    .= mt_render_field( 'address', 'offline' );
		$form    .= "<input type='submit' name='submit' class='button' value='" . esc_attr( apply_filters( 'mt_gateway_button_text', __( 'Complete Reservation', 'my-tickets' ), $gateway ) ) . "' />";
		$form    .= apply_filters( 'mt_offline_form', '', $gateway, $args );
		$form    .= '</form>';
	}

	return $form;
}

add_action( 'wp_loaded', 'mt_offline_processor' );
/**
 *  Process posted data from Offline payment.
 */
function mt_offline_processor() {
	if ( isset( $_POST['mt_gateway_offline'] ) && 'true' === $_POST['mt_gateway_offline'] ) {
		$options       = array_merge( mt_default_settings(), get_option( 'mt_settings', array() ) );
		$response      = 'VERIFIED';
		$response_code = 200;

		// transaction variables to store.
		$item_number = ( isset( $_POST['mt_item'] ) ) ? sanitize_text_field( $_POST['mt_item'] ) : mt_get_data( 'offline-payment' );
		mt_delete_data( 'offline-payment' );
		$price            = 0;
		$payment_currency = sanitize_text_field( $_POST['mt_currency'] );
		$txn_id           = 'offline';

		// All gateways must map shipping addresses to this format.
		$address = array(
			'street'  => isset( $_POST['address'] ) ? sanitize_text_field( $_POST['address'] ) : '',
			'street2' => isset( $_POST['address2'] ) ? sanitize_text_field( $_POST['address2'] ) : '',
			'city'    => isset( $_POST['city'] ) ? sanitize_text_field( $_POST['city'] ) : '',
			'state'   => isset( $_POST['state'] ) ? sanitize_text_field( $_POST['state'] ) : '',
			'country' => isset( $_POST['address_country'] ) ? sanitize_text_field( $_POST['address_country'] ) : '',
			'code'    => isset( $_POST['zip'] ) ? sanitize_text_field( $_POST['zip'] ) : '',
		);

		// if the total price on this transaction is zero, mark as completed.
		$payment_status = ( '0' === $_POST['mt_amount'] || '0.00' === $_POST['mt_amount'] ) ? 'Completed' : 'Pending';

		$data = array(
			'transaction_id' => $txn_id,
			'price'          => $price,
			'currency'       => $payment_currency,
			'status'         => $payment_status,
			'purchase_id'    => $item_number,
			'shipping'       => $address,
		);

		mt_handle_payment( $response, $response_code, $data, $_POST );
		$redirect = esc_url_raw(
			add_query_arg(
				array(
					'response_code' => 'thanks',
					'payment_id'    => $item_number,
				),
				get_permalink( $options['mt_purchase_page'] )
			)
		);
		// Everything's all right.
		wp_safe_redirect( $redirect );
		exit;
	}

	return;
}
