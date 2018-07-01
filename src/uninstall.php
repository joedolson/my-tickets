<?php
/**
 * Uninstall My Tickets.
 *
 * @category Core
 * @package  My Tickets
 * @author   Joe Dolson
 * @license  GPLv2 or later
 * @link     https://www.joedolson.com/my-tickets/
 */

if ( ! defined( 'ABSPATH' ) && ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit();
} else {
	$options       = get_option( 'mt_settings' );
	$purchase_page = ( is_numeric( $options['mt_purchase_page'] ) ) ? $options['mt_purchase_page'] : false;
	$receipt_page  = ( is_numeric( $options['mt_receipt_page'] ) ) ? $options['mt_receipt_page'] : false;
	$tickets_page  = ( is_numeric( $options['mt_tickets_page'] ) ) ? $options['mt_tickets_page'] : false;

	if ( $purchase_page ) {
		wp_delete_post( $purchase_page, true );
	}

	if ( $receipt_page ) {
		wp_delete_post( $receipt_page, true );
	}

	if ( $tickets_page ) {
		wp_delete_post( $tickets_page, true );
	}


	delete_option( 'mt_settings' );
	delete_option( 'mt_license_key' );
}
