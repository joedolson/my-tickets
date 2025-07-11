<?php
/**
 * Handle payment gateways and transact payment data into DB.
 *
 * @category Payments
 * @package  My Tickets
 * @author   Joe Dolson
 * @license  GPLv3
 * @link     https://www.joedolson.com/my-tickets/
 */

/**
 * Any gateway can call this function to handle inserting payment data into DB
 *
 * @param string $response string -- 'VERIFIED' to continue.
 * @param string $response_code HTTP CODE - must be 200 to continue.
 * @param array  $data payment details array.
 * @param array  $post data posted from gateway. Only used in error handling and hooks.
 */
function mt_handle_payment( $response, $response_code, $data, $post ) {
	$options = mt_get_settings();
	/**
	 * Filter payment data before saving results into database and sending notifications.
	 *
	 * @hook mt_filter_payment_data
	 *
	 * @param {array} $data Payment Details.
	 * @param {array} $post Data sent to My Tickets from payment gateway.
	 *
	 * @return {array}
	 */
	$data           = apply_filters( 'mt_filter_payment_data', $data, $post );
	$payment_status = $data['status'];
	$txn_id         = $data['transaction_id'];
	$payment_id     = $data['purchase_id'];
	$blogname       = get_option( 'blogname' );
	if ( 200 === absint( $response_code ) ) {
		// Response must equal "verified" (not case sensitive) to handle response.
		if ( 'verified' === strtolower( $response ) ) {
			switch ( $payment_status ) {
				case 'Completed':
					$status = 'Completed';
					break;
				case 'Processed':
				case 'Created':
				case 'Pending':
					$status = 'Pending';
					break;
				case 'Denied':
				case 'Expired':
				case 'Voided':
				case 'Reversed':
				case 'Canceled_Reversal':
				case 'Failed':
					$status = 'Failed';
					break;
				case 'Refunded':
					$status = 'Refunded';
					break;
				default:
					$status = "Other: $payment_status";
			}

			update_post_meta( $payment_id, '_transaction_id', $txn_id );
			update_post_meta( $payment_id, '_transaction_data', $data );
			update_post_meta( $payment_id, '_is_paid', $status );
			/**
			 * Take action when a payment is completed successfully.
			 *
			 * @hook mt_successful_payment
			 *
			 * @param {int}    $payment_id Payment ID.
			 * @param {string} $response Response confirmation from gateway.
			 * @param {array}  $data Data sent from gateway.
			 * @param {array}  $post Posted data from gateway.
			 */
			do_action( 'mt_successful_payment', $payment_id, $response, $data, $post );
			wp_update_post(
				array(
					'ID'          => $payment_id,
					'post_status' => 'publish',
				)
			); // trigger notifications.
		} else {
			// If we're here, there was an invalid payment response detected.
			// log for manual investigation.
			$mail_from = "From: $blogname Events <" . $options['mt_from'] . '>';
			// Translators: Response from My Tickets payment gateway.
			$mail_subject = sprintf( __( 'INVALID Response from My Tickets Payment: %s', 'my-tickets' ), $response );
			$mail_body    = __( 'Something went wrong. Hopefully this information will help:', 'my-tickets' ) . "\n\n";
			$mail_body   .= print_r( map_deep( $post, 'sanitize_text_field' ), 1 );
			wp_mail( $options['mt_to'], $mail_subject, $mail_body, $mail_from );
		}
		mt_log( $response, $response_code, $data, $post );
	} else {
		// If we're here, WP HTTP couldn't contact the payment gateway.
		$mail_from = "From: $blogname Events <" . $options['mt_from'] . '>';
		// Translators: Response code provided by payment gateway on failed connection.
		$mail_subject = sprintf( __( 'WP HTTP Failed to contact the payment gateway: %s', 'my-tickets' ), $response_code );
		$mail_body    = __( 'Something went wrong. Hopefully this information will help:', 'my-tickets' ) . "\n\n";
		$mail_body   .= print_r( $data, 1 );
		$mail_body   .= print_r( map_deep( $post, 'sanitize_text_field' ), 1 );
		$mail_body   .= print_r( $response, 1 );
		wp_mail( $options['mt_to'], $mail_subject, $mail_body, $mail_from );
		mt_log( $response, $response_code, $data, $post );
	}
}

/**
 * Log a payment error.
 *
 * @param array  $response Response.
 * @param string $response_code Response code.
 * @param array  $data Response data.
 * @param array  $post POST data.
 */
function mt_log( $response, $response_code, $data, $post ) {
	// log errors.
	// if there is no purchase ID, then there's nowhere to log this data.
	$payment_id = ( isset( $data['purchase_id'] ) ) ? $data['purchase_id'] : false;
	if ( $payment_id ) {
		// could have more than one error.
		add_post_meta( $payment_id, '_error_log', array( $response, $response_code, $data, $post ) );
	}
}

/**
 * Delete error logs for a given payment.
 *
 * @param int $payment_id Payment ID.
 */
function mt_delete_log( $payment_id ) {
	delete_post_meta( $payment_id, '_error_log' );
}
