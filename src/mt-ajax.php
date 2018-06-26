<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
} // Exit if accessed directly

add_action( 'wp_ajax_mt_ajax_cart', 'mt_ajax_cart' );
add_action( 'wp_ajax_nopriv_mt_ajax_cart', 'mt_ajax_cart' );

/**
 * Submits a cart update request from AJAX when cart is modified from Cart page
 *
 * @uses filter mt_update_cart_field_handler
 */
function mt_ajax_cart() {
	// verify nonce
	if ( ! check_ajax_referer( 'mt-ajax-cart-nonce', 'security', false ) ) {
		echo 0;
		die;
	}
	if ( $_REQUEST['action'] == 'mt_ajax_cart' ) {
		$post = $_REQUEST['data'];
		$post = array( $post['mt_event_id'] => array( $post['mt_event_type'] => array( 'count' => $post['mt_event_tickets'] ) ) );
		// generate and submit cart data
		$saved = mt_update_cart( $post );
		$saved = apply_filters( 'mt_update_cart_field_handler', $saved, $post );
		echo ( $saved ) ? json_encode( array(
				'success'  => 1,
				'response' => __( 'Cart updated', 'my-tickets' )
			) ) : json_encode( array( 'success' => 0, 'response' => __( 'Cart not updated', 'my-tickets' ) ) );
		die;
	}
}

add_action( 'wp_ajax_mt_ajax_handler', 'mt_ajax_handler' );
add_action( 'wp_ajax_nopriv_mt_ajax_handler', 'mt_ajax_handler' );

/**
 * Submit a cart update request from AJAX when add_to_cart button used from event
 * Submits an address update event on save_address action from purchase process
 *
 * @uses filter mt_add_to_cart_ajax_field_handler
 * @uses mt_ajax_updated_success
 * @uses mt_ajax_updated_unchanged
 *
 * @uses mt_save_address_success
 * @uses mt_save_address_failure
 *
 */
function mt_ajax_handler() {
	$options = array_merge( mt_default_settings(), get_option( 'mt_settings' ) );
	// verify nonce
	if ( ! check_ajax_referer( 'mt-cart-nonce', 'security', false ) ) {
		wp_send_json( array( 'response' => __( "Invalid security response.", 'my-tickets' ), 'saved' => false ) );
	}
	if ( $_REQUEST['function'] == 'add_to_cart' ) {
		$post = $_REQUEST['data'];
		// reformat request data to multidimensional array
		$data   = explode( '&', $post );
		$submit = array();
		$cart   = mt_get_cart();
		foreach ( $data as $d ) {
			$item = explode( '=', $d );
			if ( strpos( $item[0], '%5' ) !== false ) {
				$key   = str_replace( '%5D', '', $item[0] );
				$array = explode( '%5B', $key );
				if ( $cart ) {
					$submit[ $array[0] ][ $array[1] ]['count'] = urldecode( intval( $item[1] ) );
				} else {
					$submit[ $array[0] ][ $array[1] ] = urldecode( $item[1] );
				}
			} else {
				$submit[ $item[0] ] = urldecode( $item[1] );
			}
		}
		mt_debug( print_r( $submit, 1 ), 'mt_ajax_handler' );

		// generate and submit cart data
		$save  = array(
			$submit['mt_event_id'] => $submit['mt_tickets'],
			'mt_event_id'          => $submit['mt_event_id'],
			'mt_tickets'           => $submit['mt_tickets']
		);

		mt_debug( print_r( $save, 1 ), 'mt_ajax_handler' );

		$saved = mt_update_cart( $save );
		$saved = apply_filters( 'mt_add_to_cart_ajax_field_handler', $saved, $submit );
		$url   = get_permalink( $options['mt_purchase_page'] );
		if ( $saved['success'] == 1 ) {
			$response = apply_filters( 'mt_ajax_updated_success', sprintf( __( "Your cart is updated. <a href='%s'>Go to cart</a>", 'my-tickets' ), $url ) );
		} else {
			$response = apply_filters( 'mt_ajax_updated_unchanged', sprintf( __( "Cart not changed. <a href='%s'>Go to cart</a>", 'my-tickets' ), $url ) );
		}
		$return = array(
			'response' => $response,
			'success'  => $saved['success'],
			'count'    => mt_count_cart( $saved['cart'] ),
			'total'    => mt_total_cart( $saved['cart'] ),
			'event_id' => $submit['mt_event_id']
		);
		wp_send_json( $return );
	}
	if ( $_REQUEST['function'] == 'save_address' ) {
		$post = $_REQUEST['data'];
		$current_user = wp_get_current_user();
		$saved = update_user_meta( $current_user->ID, '_mt_shipping_address', $post );
		if ( $saved ) {
			$response['response'] = apply_filters( 'mt_save_address_success', __( 'Address updated.', 'my-tickets' ) );
		} else {
			$response['response'] = apply_filters( 'mt_save_address_failure', __( 'Address not updated.', 'my-tickets' ) );
		}
		wp_send_json( $response );
	}
}
