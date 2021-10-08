<?php
/**
 * Licensing.
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
			'item_name'  => urlencode( $product ), // the name of our product in EDD.
			'url'        => home_url(),
		);

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
 * @param string $name Name of product.
 * @param string $store URL of store.
 *
 * @return string
 */
function mt_verify_key( $option, $name, $store ) {
	$message = '';

	$key = sanitize_text_field( $_POST[ $option ] );
	update_option( $option, $key );

	if ( '' !== $key ) {
		$confirmation = mt_check_license( $key, $name, $store );
	} else {
		$confirmation = 'deleted';
	}

	$previously = get_option( $option . '_valid' );
	update_option( $option . '_valid', $confirmation );
	if ( 'inactive' === $confirmation ) {
		// Translators: plugin name.
		$message = sprintf( __( 'That %s key is not active.', 'my-tickets' ), $name );
	} elseif ( 'active' === $confirmation || 'valid' === $confirmation ) {
		if ( 'true' === $previously || 'active' === $previously || 'valid' === $previously ) {
			$message = '';
		} else {
			// Translators: plugin name.
			$message = sprintf( __( '%s key validated. Enjoy!', 'my-tickets' ), $name );
		}
	} elseif ( 'deleted' === $confirmation ) {
		// Translators: plugin name.
		$message = sprintf( __( 'You have deleted your %s license key.', 'my-tickets' ), $name );
	} else {
		// Translators: plugin name.
		$message = sprintf( __( '%s received an unexpected message from the license server. Try again in a bit.', 'my-tickets' ), $name );
	}
	$message = ( '' !== $message ) ? " $message " : $message; // just add a space.

	return $message;
}
