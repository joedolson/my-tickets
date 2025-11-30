<?php
/**
 * Generate and display receipts.
 *
 * @category Core
 * @package  My Tickets
 * @author   Joe Dolson
 * @license  GPLv3
 * @link     https://www.joedolson.com/my-tickets/
 */

add_filter( 'template_redirect', 'mt_receipt', 10, 1 );
/**
 * If a valid receipt, load receipt template. Else, redirect to purchase page.
 */
function mt_receipt() {
	$options = mt_get_settings();
	$id      = ( '' !== $options['mt_receipt_page'] && is_numeric( $options['mt_receipt_page'] ) ) ? absint( $options['mt_receipt_page'] ) : false;
	if ( $id && ( is_single( $id ) || is_page( $id ) ) ) {
		if ( isset( $_GET['receipt_id'] ) ) {
			$receipt     = mt_get_receipt();
			$receipt_id  = md5( sanitize_text_field( $_GET['receipt_id'] ) . mt_get_payment_log_id( $receipt->ID ) );
			$time        = get_post_modified_time( 'U', true, $receipt->ID );
			$date        = ( $receipt ) ? $time : false;
			$is_verified = false;
			if ( isset( $_POST['mt-verify-email'] ) ) {
				$nonce       = isset( $_POST['_wpnonce'] ) ? $_POST['_wpnonce'] : false;
				$verify      = wp_verify_nonce( $nonce, 'mt-verify-email' );
				$email       = sanitize_text_field( $_POST['mt-verify-email'] );
				$is_verified = ( $verify && get_post_meta( $receipt->ID, '_email', true ) === $email ) ? true : false;
			}
			$cookie_receipt = ( isset( $_COOKIE['mt_purchase_receipt'] ) ) ? $_COOKIE['mt_purchase_receipt'] : false;
			// Allow conditions: within 10 minutes of purchase & browser has a matching cookie; current user can view reports; user has verified email.
			if ( ( time() <= $date + 600 && $cookie_receipt === $receipt_id ) || current_user_can( 'mt-view-reports' ) || $is_verified ) {
				$template = locate_template( 'receipt.php' );
				if ( $template ) {
					load_template( $template );
				} else {
					load_template( __DIR__ . '/templates/receipt.php' );
				}
			} else {
				load_template( __DIR__ . '/mt-verify.php' );
			}
			exit;
		} else {
			wp_safe_redirect( mt_get_cart_url() );
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
