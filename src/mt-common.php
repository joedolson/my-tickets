<?php
/**
 * Licensing.
 *
 * @category Core
 * @package  My Tickets
 * @author   Joe Dolson
 * @license  GPLv2
 * @link     https://www.joedolson.com/my-tickets/
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
} // Exit if accessed directly.

/**
 * Check submitted license key.
 *
 * @param string|boolean $key License key.
 * @param string         $product Product being checked.
 * @param string         $store URL of store.
 *
 * @return bool
 */
function mt_check_license( $key = false, $product = '', $store = '' ) {
	// listen for our activate button to be clicked.
	if ( isset( $_POST['mt_license_keys'] ) ) {
		// retrieve the license from the database.
		$license = trim( sanitize_text_field( $key ) );
		// data to send in our API request.
		$api_params = array(
			'edd_action' => 'activate_license',
			'license'    => $license,
			'url'        => home_url(),
		);
		// All products were changed to use item_id on 3/7/2024. Need to support old references, though.
		if ( is_numeric( $product ) ) {
			$api_params['item_id'] = absint( $product );
		} else {
			$api_params['item_name'] = urlencode( $product );
		}

		// Call the custom API.
		$response = wp_remote_post(
			$store,
			array(
				'timeout' => 15,
				'body'    => $api_params,
			)
		);
		// make sure the response came back okay.
		if ( is_wp_error( $response ) ) {
			return false;
		}
		// decode the license data.
		$license_data = json_decode( wp_remote_retrieve_body( $response ) );

		// $license_data->license will be either "active" or "inactive".
		return $license_data->license;
	}

	return false;
}

/**
 * Verify the key.
 *
 * @param string $option Option to check for key storage.
 * @param string $id Product ID.
 * @param string $store URL of store.
 * @param string $name Name of product.
 *
 * @return string
 */
function mt_verify_key( $option, $id, $store, $name = '' ) {
	$message = '';

	$key      = sanitize_text_field( $_POST[ $option ] );
	$prev_key = get_option( $option, $key );
	update_option( $option, $key );

	$name = ( $name ) ? $name : __( 'My Tickets Add-on', 'my-tickets' );

	if ( '' !== $key ) {
		$confirmation = mt_check_license( $key, $id, $store );
	} else {
		$confirmation = ( '' !== $prev_key ) ? 'deleted' : '';
	}

	$previously = get_option( $option . '_valid' );
	update_option( $option . '_valid', $confirmation );
	if ( 'inactive' === $confirmation ) {
		// Translators: plugin name.
		$message = sprintf( __( 'That %s key is not active.', 'my-tickets' ), $name );
	} elseif ( 'valid' === $confirmation ) {
		if ( 'valid' === $previously ) {
			// translators: plugin name.
			$message = sprintf( __( '%s key has already been activated for this site. Enjoy!', 'my-tickets' ), $name );
		} else {
			// Translators: plugin name.
			$message = sprintf( __( '%s key validated. Enjoy!', 'my-tickets' ), $name );
		}
	} elseif ( 'deleted' === $confirmation ) {
		// Translators: plugin name.
		$message = sprintf( __( 'You have deleted your %s license key.', 'my-tickets' ), $name );
	} elseif ( 'invalid' === $confirmation ) {
		// translators: plugin name.
		$message = sprintf( __( 'The provided license key for %s is not a valid key.', 'my-tickets' ), $name );
	} else {
		// Translators: 1) plugin name, 2) license confirmation code.
		$message = ( '' !== $confirmation ) ? sprintf( __( 'Validating %1$s returned an unexpected message, %2$s, from the license server. Try again in a bit.', 'my-tickets' ), $name, '<code>' . esc_html( $confirmation ) . '</code>' ) : '';
	}
	$message = ( '' !== $message ) ? " $message " : $message; // just add a space.

	return $message;
}
