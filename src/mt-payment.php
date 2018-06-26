<?php
/* Functions related to handling payments */

/*
 * Any gateway can call this function to handle inserting payment data into DB
 * 
 * @param $response string -- 'VERIFIED' to continue
 * @param $response_code HTTP CODE - must be 200 to continue.
 * @param $data payment details array
 * @param $post data posted from gateway
 */
function mt_handle_payment( $response, $response_code, $data, $post ) {
	// $response_code must equal 200 to handle response.
	$options        = array_merge( mt_default_settings(), get_option( 'mt_settings' ) );
	$data           = apply_filters( 'mt_filter_payment_data', $data, $post );
	$payment_status = $data['status'];
	$txn_id         = $data['transaction_id'];
	$purchase_id    = $data['purchase_id'];
	$blogname       = get_option( 'blogname' );
	if ( $response_code == '200' ) {
		// Response must equal "Verified" to handle response
		if ( $response == "VERIFIED" ) {
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

			update_post_meta( $purchase_id, '_transaction_id', $txn_id );
			update_post_meta( $purchase_id, '_transaction_data', $data );
			update_post_meta( $purchase_id, '_is_paid', $status );
			wp_update_post( array( 'ID' => $purchase_id, 'post_status' => 'publish' ) ); // trigger notifications.
		} else {
			// If we're here, there was an invalid payment response detected.
			// log for manual investigation
			$mail_From    = "From: $blogname Events <" . $options['mt_from'] . ">";
			$mail_Subject = sprintf( __( "INVALID Response from My Tickets Payment: %s", 'my-tickets' ), $response );
			$mail_Body    = __( "Something went wrong. Hopefully this information will help:", 'my-tickets' ) . "\n\n";
			$mail_Body .= print_r( $post, 1 );
			wp_mail( $options['mt_to'], $mail_Subject, $mail_Body, $mail_From );
		}
	} else {
		// If we're here, WP HTTP couldn't contact the payment gateway.
		$mail_From    = "From: $blogname Events <" . $options['mt_from'] . ">";
		$mail_Subject = sprintf( __( "WP HTTP Failed to contact the payment gateway: %s", 'my-tickets' ), $response_code );
		$mail_Body    = __( "Something went wrong. Hopefully this information will help:", 'my-tickets' ) . "\n\n";
		$mail_Body .= print_r( $data, 1 );
		$mail_Body .= print_r( $post, 1 );
		$mail_Body .= print_r( $response, 1 );
		wp_mail( $options['mt_to'], $mail_Subject, $mail_Body, $mail_From );
		mt_log( $response, $response_code, $data, $post );
	}
}

function mt_log( $response, $response_code, $data, $post ) {
	// log errors
	// if there is no purchase ID, then there's nowhere to log this data.
	$purchase_id = ( isset( $data['purchase_id'] ) ) ? $data['purchase_id'] : false;
	if ( $purchase_id ) {
		// could have more than one error.
		add_post_meta( $purchase_id, '_error_log', array( $response, $response_code, $data, $post ) );
	}
}

function mt_delete_log( $purchase_id ) {
	delete_post_meta( $purchase_id, '_error_log' );
}