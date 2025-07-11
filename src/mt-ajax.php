<?php
/**
 * AJAX handlers for add to cart & cart processing.
 *
 * @category Core
 * @package  My Tickets
 * @author   Joe Dolson
 * @license  GPLv3
 * @link     https://www.joedolson.com/my-tickets/
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
} // Exit if accessed directly.

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
		$success = wp_json_encode(
			array(
				'success'  => 1,
				'response' => __( 'Cart updated', 'my-tickets' ),
			)
		);
		$failure = wp_json_encode(
			array(
				'success'  => 0,
				'response' => __( 'Cart not updated', 'my-tickets' ),
			)
		);
		echo ( $saved ) ? $success : $failure;
		die;
	}
}
add_action( 'wp_ajax_mt_ajax_cart', 'mt_ajax_cart' );
add_action( 'wp_ajax_nopriv_mt_ajax_cart', 'mt_ajax_cart' );

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
		$save = array();
		if ( isset( $submit['mt_tickets'] ) ) {
			$modified      = false;
			$modified_type = '';
			foreach ( $submit['mt_tickets'] as $type => $count ) {
				$count = is_array( $count ) ? $count[0] : (int) $count;
				/**
				 * Documented in `mt-add-to-cart.php`.
				 */
				$registration      = get_post_meta( $submit['mt_event_id'], '_mt_registration_options', true );
				$default_available = apply_filters( 'mt_default_available', 100, $registration );
				if ( 'general' === $registration['counting_method'] ) {
					$available_count = $default_available;
				} else {
					$available       = mt_check_inventory( $submit['mt_event_id'], $type );
					$available_count = ( $available ) ? $available['available'] : 0;
				}
				$append = '';
				if ( $count > $available_count ) {
					// Set to max available if requested greater than available.
					$submit['mt_tickets'][ $type ] = $available_count;
					$modified                      = true;
					$modified_type                 = ( 0 === $available_count ) ? 'soldout' : 'changed';
				}
			}
			$save = array(
				$submit['mt_event_id'] => $submit['mt_tickets'],
				'mt_event_id'          => $submit['mt_event_id'],
				'mt_tickets'           => $submit['mt_tickets'],
			);
		}

		$saved = mt_update_cart( $save );
		/**
		 * Filter submitted data after cart update.
		 *
		 * @hook mt_add_to_cart_ajax_field_handler
		 *
		 * @param {array} $saved Array containing a `success` and `cart` element.
		 * @param {array} $submit Array of data submitted from form.
		 *
		 * @return {array}
		 */
		$saved = apply_filters( 'mt_add_to_cart_ajax_field_handler', $saved, $submit );
		$url   = mt_get_cart_url();
		if ( $modified ) {
			$append = ( 'changed' === $modified_type ) ? __( 'Some tickets are no longer available, and were removed from your order. Please review your purchase carefully!', 'my-tickets' ) : __( 'There are no longer tickets available for this event.', 'my-tickets' );
			$append = ' ' . $append;
		}
		if ( 1 === (int) $saved['success'] ) {
			// Translators: Cart URL.
			$message  = ( 'soldout' === $modified_type ) ? __( 'Your cart is updated.', 'my-tickets' ) : sprintf( __( "Your cart is updated. <a href='%s'>Checkout</a>", 'my-tickets' ), $url );
			$response = apply_filters( 'mt_ajax_updated_success', $message . $append );
		} else {
			// Translators: Cart URL.
			$response = apply_filters( 'mt_ajax_updated_unchanged', sprintf( __( "Cart not changed. <a href='%s'>Checkout</a>", 'my-tickets' ), $url ) ) . $append;
		}
		$return = array(
			'response' => $response,
			'success'  => $saved['success'],
			'count'    => mt_count_cart( $saved['cart'] ),
			'total'    => mt_total_cart( $saved['cart'] ),
			'event_id' => $submit['mt_event_id'],
			'data'     => $count,
		);
		wp_send_json( $return );
	}
	if ( 'save_address' === $_REQUEST['function'] ) {
		$post         = array_map( 'sanitize_text_field', $_REQUEST['data'] );
		$current_user = wp_get_current_user();
		$saved        = update_user_meta( $current_user->ID, '_mt_shipping_address', $post );
		$response     = array();
		if ( $saved ) {
			$response['response'] = apply_filters( 'mt_save_address_success', __( 'Address updated.', 'my-tickets' ) );
		} else {
			$response['response'] = apply_filters( 'mt_save_address_failure', __( 'Address not updated.', 'my-tickets' ) );
		}
		wp_send_json( $response );
	}
	if ( 'extend_cart' === $_REQUEST['function'] ) {
		$extend   = mt_extend_expiration();
		$response = array(
			'response' => __( 'Extension failed.', 'my-tickets' ),
		);
		if ( $extend ) {
			$response = array(
				'response' => __( 'Cart expiration extended', 'my-tickets' ),
			);
		}
		wp_send_json( $response );
	}
}
add_action( 'wp_ajax_mt_ajax_handler', 'mt_ajax_handler' );
add_action( 'wp_ajax_nopriv_mt_ajax_handler', 'mt_ajax_handler' );

/**
 * AJAX load a ticket model set.
 */
function mt_ajax_load_model() {
	// verify nonce.
	if ( ! check_ajax_referer( 'mt-load-model', 'security', false ) ) {
		wp_send_json(
			array(
				'response' => __( 'Invalid security response.', 'my-tickets' ),
				'form'     => '',
			)
		);
	}
	$model    = ( in_array( $_REQUEST['model'], array( 'continuous', 'discrete', 'event' ), true ) ) ? sanitize_key( $_REQUEST['model'] ) : false;
	$event_id = absint( $_REQUEST['event_id'] );
	$data     = isset( $_REQUEST['event'] ) ? map_deep( json_decode( $_REQUEST['event'] ), 'sanitize_text_field' ) : array();
	$form     = mt_get_registration_fields( '', $event_id, $data, 'admin', $model );
	wp_send_json(
		array(
			'form' => $form,
		)
	);
}
add_action( 'wp_ajax_mt_ajax_load_model', 'mt_ajax_load_model' );
add_action( 'wp_ajax_nopriv_mt_ajax_load_model', 'mt_ajax_load_model' );
