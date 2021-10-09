<?php
/**
 * Generate and display receipts.
 *
 * @category Core
 * @package  My Tickets
 * @author   Joe Dolson
 * @license  GPLv2 or later
 * @link     https://www.joedolson.com/my-tickets/
 */

add_filter( 'template_redirect', 'mt_receipt', 10, 1 );
/**
 * If a valid receipt, load receipt template. Else, redirect to purchase page.
 */
function mt_receipt() {
	$options = array_merge( mt_default_settings(), get_option( 'mt_settings', array() ) );
	$id      = ( '' !== $options['mt_receipt_page'] && is_numeric( $options['mt_receipt_page'] ) ) ? absint( $options['mt_receipt_page'] ) : false;
	if ( $id && ( is_single( $id ) || is_page( $id ) ) ) {
		if ( isset( $_GET['receipt_id'] ) ) {
			$template = locate_template( 'receipt.php' );
			if ( $template ) {
				load_template( $template );
			} else {
				load_template( dirname( __FILE__ ) . '/templates/receipt.php' );
			}
			exit;
		} else {
			wp_safe_redirect( get_permalink( $options['mt_purchase_page'] ) );
			exit;
		}
	}
}

/**
 * Load receipt post data for use in receipt template.
 *
 * @return bool|object
 */
function mt_get_receipt() {
	$receipt_id = isset( $_GET['receipt_id'] ) ? sanitize_text_field( $_GET['receipt_id'] ) : false;
	$receipt    = false;
	if ( $receipt_id ) {
		$posts   = get_posts(
			array(
				'post_type'   => 'mt-payments',
				'meta_key'    => '_receipt',
				'meta_value'  => $receipt_id,
				'post_status' => 'publish,draft',
			)
		);
		$receipt = ( isset( $posts[0] ) ) ? $posts[0] : false;
	}

	return $receipt;
}
