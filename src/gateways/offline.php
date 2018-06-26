<?php

add_filter( 'mt_shipping_fields', 'mt_offline_shipping_fields', 10, 2 );
function mt_offline_shipping_fields( $form, $gateway ) {
	if ( $gateway == 'offline' ) {
		$search  = array(
			'mt_shipping_street',
			'mt_shipping_city',
			'mt_shipping_state',
			'mt_shipping_country',
			'mt_shipping_code'
		);
		$replace = array( 'address', 'city', 'state', 'address_country', 'zip' );

		return str_replace( $search, $replace, $form );
	}

	return $form;
}

add_filter( 'mt_format_transaction', 'mt_format_offline_transaction', 10, 2 );
function mt_format_offline_transaction( $transaction, $gateway ) {
	if ( $gateway == 'offline' ) {
		// alter return value if desired.
	}

	return $transaction;
}

add_filter( 'mt_setup_gateways', 'mt_setup_offline', 10, 1 );
function mt_setup_offline( $gateways ) {
	$gateways['offline'] = array(
		'label'  => __( 'Offline', 'my-tickets' ),
		'fields' => array(
			'notes'       => __( 'Offline Payment Notes', 'my-tickets' )
		)
	);

	return $gateways;
}

add_filter( 'mt_gateway', 'mt_gateway_offline', 10, 3 );
function mt_gateway_offline( $form, $gateway, $args ) {
	if ( $gateway == 'offline' ) {
		$options        = array_merge( mt_default_settings(), get_option( 'mt_settings' ) );
		$payment_id     = $args['payment'];
		$handling       = ( isset( $options['mt_handling'] ) ) ? $options['mt_handling'] : 0;
		$total          = $args['total'] + $handling;
		$shipping_price = ( $args['method'] == 'postal' ) ? number_format( $options['mt_shipping'], 2 ) : 0;
		$currency       = $options['mt_currency'];
		$form           = "
		<form action='" . get_permalink( $options['mt_purchase_page'] ) . "' method='POST'>
		<input type='hidden' name='mt_purchase' value='" . sprintf( __( '%s Order', 'my-tickets' ), get_option( 'blogname' ) ) . "' />
		<input type='hidden' name='mt_item' value='" . esc_attr( $payment_id ) . "' />
		<input type='hidden' name='mt_amount' value='" . esc_attr( $total ) . "' />
		<input type='hidden' name='mt_shipping' value='" . esc_attr( $shipping_price ) . "' />
		<input type='hidden' name='mt_offline_payment' value='true' />
		<input type='hidden' name='mt_currency' value='" . esc_attr( $currency ) . "' />";
		$form .= mt_render_field( 'address', 'offline' );
		$form .= "<input type='submit' name='submit' class='button' value='" . esc_attr( apply_filters( 'mt_gateway_button_text', __( 'Complete Reservation', 'my-tickets' ), $gateway ) ) . "' />";
		$form .= apply_filters( 'mt_offline_form', '', $gateway, $args );
		$form .= "</form>";
	}

	return $form;
}

add_action( 'wp_loaded', 'mt_offline_processor' );
function mt_offline_processor() {
	if ( isset( $_POST['mt_offline_payment'] ) && $_POST['mt_offline_payment'] == 'true' ) {
		$options  = array_merge( mt_default_settings(), get_option( 'mt_settings' ) );
		$response = 'VERIFIED';
		$response_code = 200;

		// transaction variables to store
		$item_number      = $_POST['mt_item'];
		$price            = 0;
		$payment_currency = $_POST['mt_currency'];
		$txn_id           = 'offline';

		// All gateways must map shipping addresses to this format.
		$address = array(
			'street'  => isset( $_POST['address'] ) ? $_POST['address'] : '',
			'street2' => isset( $_POST['address2'] ) ? $_POST['address2'] : '',
			'city'    => isset( $_POST['city'] ) ? $_POST['city'] : '',
			'state'   => isset( $_POST['state'] ) ? $_POST['state'] : '',
			'country' => isset( $_POST['address_country'] ) ? $_POST['address_country'] : '',
			'code'    => isset( $_POST['zip'] ) ? $_POST['zip'] : ''
		);

		// if the total price on this transaction is zero, mark as completed.
		$payment_status = ( 0 == $_POST['mt_amount'] || '0.00' == $_POST['mt_amount'] ) ? 'Completed' : 'Pending';

		$data = array(
			'transaction_id' => $txn_id,
			'price'          => $price,
			'currency'       => $payment_currency,
			'status'         => $payment_status,
			'purchase_id'    => $item_number,
			'shipping'       => $address
		);

		mt_handle_payment( $response, $response_code, $data, $_POST );
		// Everything's all right.
		wp_safe_redirect( esc_url_raw( add_query_arg( array( 'response_code'=> 'thanks', 'payment_id' => $item_number ), get_permalink( $options['mt_purchase_page'] ) ) ) );
	}

	return;
}
