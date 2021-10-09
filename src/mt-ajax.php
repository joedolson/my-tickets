<?php
/**
 * AJAX handlers for add to cart & cart processing.
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

add_action( 'wp_ajax_mt_ajax_cart', 'mt_ajax_cart' );
add_action( 'wp_ajax_nopriv_mt_ajax_cart', 'mt_ajax_cart' );

/**
 * Submits a cart update request from AJAX when cart is modified from Cart page.
 *
 * @uses filter mt_update_cart_field_handler
 */
function mt_ajax_cart() {
	// verify nonce.
	if ( ! check_ajax_referer( 'mt-ajax-cart-nonce', 'security', false ) ) {
		echo 0;
		die;
	}
	if ( 'mt_ajax_cart' === $_REQUEST['action'] ) {
		$post = map_deep( $_REQUEST['data'], 'sanitize_text_field' );
		$post = array(
			$post['mt_event_id'] => array(
				$post['mt_event_type'] => array(
					'count' => $post['mt_event_tickets'],
				),
			),
		);
		// generate and submit cart data.
		$saved   = mt_update_cart( $post );
		$saved   = apply_filters( 'mt_update_cart_field_handler', $saved, $post );
		$success = json_encode(
			array(
				'success'  => 1,
				'response' => __( 'Cart updated', 'my-tickets' ),
			)
		);
		$failure = json_encode(
			array(
				'success'  => 0,
				'response' => __( 'Cart not updated', 'my-tickets' ),
			)
		);
		echo ( $saved ) ? $success : $failure;
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
 */
function mt_ajax_handler() {
	$options = array_merge( mt_default_settings(), get_option( 'mt_settings', array() ) );
	// verify nonce.
	if ( ! check_ajax_referer( 'mt-cart-nonce', 'security', false ) ) {
		wp_send_json(
			array(
				'response' => __( 'Invalid security response.', 'my-tickets' ),
				'saved'    => false,
			)
		);
	}
	if ( 'add_to_cart' === $_REQUEST['function'] ) {
		parse_str( $_REQUEST['data'], $data );
		$data = map_deep( $data, 'sanitize_text_field' );
		// reformat request data to multidimensional array.
		$cart = mt_get_cart();
		foreach ( $data as $k => $d ) {
			if ( 'mt_tickets' === $k ) {
				foreach ( $d as $n => $value ) {
					if ( $cart ) {
						$data[ $k ][ $n ] = array(
							'count' => $value,
						);
					} else {
						$data[ $k ][ $n ] = $value;
					}
				}
			}
		}
		$submit = $data;

		// generate and submit cart data.
		$save = array(
			$submit['mt_event_id'] => $submit['mt_tickets'],
			'mt_event_id'          => $submit['mt_event_id'],
			'mt_tickets'           => $submit['mt_tickets'],
		);

		$saved = mt_update_cart( $save );
		$saved = apply_filters( 'mt_add_to_cart_ajax_field_handler', $saved, $submit );
		$url   = get_permalink( $options['mt_purchase_page'] );
		if ( 1 === (int) $saved['success'] ) {
			// Translators: Cart URL.
			$response = apply_filters( 'mt_ajax_updated_success', sprintf( __( "Your cart is updated. <a href='%s'>Go to cart</a>", 'my-tickets' ), $url ) );
		} else {
			// Translators: Cart URL.
			$response = apply_filters( 'mt_ajax_updated_unchanged', sprintf( __( "Cart not changed. <a href='%s'>Go to cart</a>", 'my-tickets' ), $url ) );
		}
		$return = array(
			'response' => $response,
			'success'  => $saved['success'],
			'count'    => mt_count_cart( $saved['cart'] ),
			'total'    => mt_total_cart( $saved['cart'] ),
			'event_id' => $submit['mt_event_id'],
		);
		wp_send_json( $return );
	}
	if ( 'save_address' === $_REQUEST['function'] ) {
		$post         = array_map( 'sanitize_text_field', $_REQUEST['data'] );
		$current_user = wp_get_current_user();
		$saved        = update_user_meta( $current_user->ID, '_mt_shipping_address', $post );
		if ( $saved ) {
			$response['response'] = apply_filters( 'mt_save_address_success', __( 'Address updated.', 'my-tickets' ) );
		} else {
			$response['response'] = apply_filters( 'mt_save_address_failure', __( 'Address not updated.', 'my-tickets' ) );
		}
		wp_send_json( $response );
	}
}
