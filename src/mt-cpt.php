<?php
/**
 * Custom Post Type elements - display and handling.
 *
 * @category Core
 * @package  My Tickets
 * @author   Joe Dolson
 * @license  GPLv2 or later
 * @link     https://www.joedolson.com/my-tickets/
 */

add_action( 'add_meta_boxes', 'mt_add_meta_boxes' );
/**
 * Load  meta boxes for tickets.
 *
 * @return void
 */
function mt_add_meta_boxes() {
	add_meta_box( 'mt_purchase_options', __( 'Purchase Options', 'my-tickets' ), 'mt_add_inner_box', 'mt-payments', 'normal', 'high' );
	add_meta_box( 'mt_purchase_info', __( 'Purchase Information', 'my-tickets' ), 'mt_add_uneditable', 'mt-payments', 'side', 'high' );
	if ( isset( $_GET['post'] ) && isset( $_GET['action'] ) ) {
		global $post_id;
		add_meta_box( 'mt_send_email', __( 'Contact Purchaser', 'my-tickets' ), 'mt_email_purchaser', 'mt-payments', 'normal', 'default' );
		if ( get_post_meta( $post_id, '_error_log', true ) !== '' && current_user_can( 'manage_options' ) ) {
			add_meta_box( 'mt_error_log', __( 'Payment Log', 'my-tickets' ), 'mt_error_data', 'mt-payments', 'normal', 'default' );
		}
	}
}

/**
 * Display error data from log.
 */
function mt_error_data() {
	global $post_id;
	$logs   = get_post_meta( $post_id, '_error_log' );
	$output = '';
	foreach ( $logs as $log ) {
		$data    = '<pre>' . print_r( array_map( 'sanitize_text_field', $log[2] ), 1 ) . '</pre>';
		$submit  = '<pre>' . print_r( array_map( 'sanitize_text_field', $log[3] ), 1 ) . '</pre>';
		$row     = sprintf( '<td scope="row">%1$s</td><td>%2$s</td><td>%3$s</td><td>%4$s</td>', $log[0], $log[1], $data, $submit );
		$output .= $row;
	}
	$table = sprintf( '<table class="widefat"><thead><tr><th scope="col">' . __( 'Status', 'my-tickets' ) . '</th><th scope="col">' . __( 'HTTP', 'my-tickets' ) . '</th><th scope="col">' . __( 'Data', 'my-tickets' ) . '</th><th scope="col">' . __( 'Order', 'my-tickets' ) . '</th></tr></thead><tbody>%s</tbody></table>', $output );
	echo wp_kses_post( $table );
}

/**
 * Send custom email to ticket purchaser from payment record.
 */
function mt_email_purchaser() {
	global $post_id;
	$messages = false;
	$nonce    = '<input type="hidden" name="mt-email-nonce" value="' . wp_create_nonce( 'mt-email-nonce' ) . '" />';
	$form     = "<p><label for='mt_send_subject'>" . __( 'Subject', 'my-tickets' ) . "</label><br /><input type='text' size='60' name='mt_send_subject' id='mt_send_subject' /></p>
	<p><label for='mt_send_email'>" . __( 'Message', 'my-tickets' ) . "</label><br /><textarea cols='60' rows='6' name='mt_send_email' id='mt_send_email'></textarea></p>
	<input type='submit' class='button-primary' id='mt_email_form' value='" . __( 'Email Purchaser', 'my-tickets' ) . "' />";
	$email    = get_post_meta( $post_id, '_mt_send_email' );
	$message  = '<h3>' . __( 'Prior Messages', 'my-tickets' ) . '</h3>';
	foreach ( $email as $mail ) {
		if ( is_array( $mail ) ) {
			$body     = $mail['body'];
			$subject  = $mail['subject'];
			$date     = date_i18n( get_option( 'date_format' ) . ' @ ' . get_option( 'time_format' ), $mail['date'] );
			$message .= "<li><strong>$subject <code>($date)</code></strong><br /><blockquote>" . stripslashes( wp_kses_post( $body ) ) . '</blockquote></li>';
			$messages = true;
		}
	}
	$prior = ( $messages ) ? '<ul class="mt-message-log">' . $message . '</ul>' : '';
	echo wp_kses( '<div class="mt_post_fields panels">' . $nonce . $form . $prior . '</div>', mt_kses_elements() );
}

add_action( 'save_post', 'mt_delete_error_log', 10 );
/**
 * Delete error logs.
 *
 * @param int $id Payment ID.
 */
function mt_delete_error_log( $id ) {
	if ( isset( $_POST['mt_delete_log'] ) ) {
		mt_delete_log( $id );
	}
}

add_action( 'save_post', 'mt_cpt_email_purchaser', 10 );
/**
 * Send email notification when post is saved.
 *
 * @param int $id Purchaser ID.
 */
function mt_cpt_email_purchaser( $id ) {
	if ( isset( $_POST['mt-email-nonce'] ) ) {
		$options  = array_merge( mt_default_settings(), get_option( 'mt_settings', array() ) );
		$blogname = get_option( 'blogname' );
		$nonce    = $_POST['mt-email-nonce'];
		if ( ! wp_verify_nonce( $nonce, 'mt-email-nonce' ) ) {
			wp_die( 'Invalid nonce' );
		}
		if ( isset( $_POST['_inline_edit'] ) ) {
			return;
		}

		if ( isset( $_POST['mt_send_email'] ) && '' !== $_POST['mt_send_email'] ) {
			$body        = ( 'true' === $options['mt_html_email'] ) ? wp_kses_post( $_POST['mt_send_email'] ) : sanitize_textarea_field( $_POST['mt_send_email'] );
			$subject     = ( 'true' === $options['mt_html_email'] ) ? wp_kses_post( $_POST['mt_send_subject'] ) : sanitize_text_field( $_POST['mt_send_subject'] );
			$email       = get_post_meta( $id, '_email', true );
			$opt_out_url = add_query_arg( 'opt_out', $id, home_url() );
			// Translators: Link to stop email notices.
			$opt_out   = PHP_EOL . PHP_EOL . '<p><small>' . sprintf( __( "Don't want to receive email from us? Follow this link: %s", 'my-tickets' ), $opt_out_url ) . '</small></p>';
			$opt_out   = apply_filters( 'mt_opt_out_text', $opt_out, $opt_out_url, $id, 'single' );
			$headers[] = "From: $blogname Events <" . $options['mt_from'] . '>';
			$headers[] = "Reply-to: $options[mt_from]";
			if ( 'true' === $options['mt_html_email'] ) {
				add_filter( 'wp_mail_content_type', 'mt_html_type' );
				$body = wpautop( $body . $opt_out );
			} else {
				$body = strip_tags( $body . $opt_out );
			}
			$body = apply_filters( 'mt_modify_email_body', $body );

			// message to purchaser.
			$sent = wp_mail( $email, $subject, $body, $headers );
			if ( ! $sent ) {
				// If mail sends, try without custom headers.
				wp_mail( $email, $subject, $body );
			}

			if ( 'true' === $options['mt_html_email'] ) {
				remove_filter( 'wp_mail_content_type', 'mt_html_type' );
			}
			add_post_meta(
				$id,
				'_mt_send_email',
				array(
					'body'    => $body,
					'subject' => $subject,
					'date'    => mt_current_time(),
				)
			);
		}
	}
}

/**
 * Set up custom fields for payment page.
 *
 * @return mixed|void
 */
function mt_default_fields() {
	$mt_fields =
		array(
			'is_paid'           => array(
				'label'   => __( 'Payment Status', 'my-tickets' ),
				'input'   => 'select',
				'default' => 'Pending',
				'choices' => array( '--', 'Completed', 'Pending', 'Failed', 'Refunded', 'Turned Back', 'Reserved', 'Waiting List', 'Other' ),
			),
			'ticketing_method'  => array(
				'label'   => __( 'Ticketing Method', 'my-tickets' ),
				'input'   => 'select',
				'default' => 'willcall',
				'choices' => apply_filters(
					'mt_registration_tickets_options',
					array(
						'printable' => __( 'Printable', 'my-tickets' ),
						'eticket'   => __( 'E-tickets', 'my-tickets' ),
						'postal'    => __( 'Postal Mail', 'my-tickets' ),
						'willcall'  => __( 'Pick up at box office', 'my-tickets' ),
					)
				),
			),
			'is_delivered'      => array(
				'label'   => __( 'Ticket Delivered', 'my-tickets' ),
				'input'   => 'checkbox',
				'default' => '',
				'notes'   => __( 'E-tickets and printable tickets are delivered via email.', 'my-tickets' ),
			),
			'mt_return_tickets' => array(
				'label'   => __( 'Return tickets to purchase pool', 'my-tickets' ),
				'input'   => 'checkbox',
				'default' => 'checked',
			),
			'total_paid'        => array(
				'label'   => __( 'Tickets Total', 'my-tickets' ),
				'input'   => 'text',
				'default' => '',
			),
			'email'             => array(
				'label'   => __( 'Purchaser Email', 'my-tickets' ),
				'input'   => 'text',
				'default' => '',
			),
			'phone'             => array(
				'label'   => __( 'Purchaser Phone', 'my-tickets' ),
				'input'   => 'text',
				'default' => '',
			),
			'vat'               => array(
				'label'   => __( 'Purchaser VAT Number', 'my-tickets' ),
				'input'   => 'text',
				'default' => '',
			),
			'notes'             => array(
				'label' => __( 'Payment Notes', 'my-tickets' ),
				'input' => 'textarea',
				'notes' => 'Internal-use only',
			),
			'send_email'        => array(
				'label'   => __( 'Re-send Email Notification', 'my-tickets' ),
				'input'   => 'checkbox',
				'context' => 'edit',
			),
			'gateway'           => array(
				'label'   => __( 'Payment Method', 'my-tickets' ),
				'input'   => 'select',
				'choices' => array( 'Credit Card', 'Check', 'Cash', 'Other' ),
				'context' => 'new',
			),
			'transaction_id'    => array(
				'label'   => __( 'Transaction ID', 'my-tickets' ),
				'input'   => 'text',
				'context' => 'new',
			),
		);

	return apply_filters( 'mt_add_custom_fields', $mt_fields );
}

/**
 * Display inner box of metabox.
 */
function mt_add_inner_box() {
	global $post_id;
	$fields = mt_default_fields();
	$format = sprintf(
		'<input type="hidden" name="%1$s" id="%1$s" value="%2$s" />',
		'mt-meta-nonce',
		wp_create_nonce( 'mt-meta-nonce' )
	);
	foreach ( $fields as $key => $value ) {
		$label    = $value['label'];
		$input    = $value['input'];
		$choices  = ( isset( $value['choices'] ) ) ? $value['choices'] : false;
		$multiple = ( isset( $value['multiple'] ) ) ? true : false;
		$notes    = ( isset( $value['notes'] ) ) ? $value['notes'] : '';
		$format  .= mt_create_field( $key, $label, $input, $post_id, $choices, $multiple, $notes, $value );
	}
	if ( ! isset( $_GET['post'] ) ) {
		// for new payments only; imports user's cart.
		$cart_id           = false;
		$cart_transient_id = false;
		if ( isset( $_GET['cart'] ) && is_numeric( $_GET['cart'] ) ) {
			$cart_id = (int) $_GET['cart'];
		}
		if ( isset( $_GET['cart_id'] ) ) {
			$cart_transient_id = sanitize_text_field( $_GET['cart_id'] );
		}
		$cart = mt_get_cart( $cart_id, $cart_transient_id );
		// Translators: link to public web site.
		$order = ( $cart ) ? mt_generate_cart_table( $cart, 'confirmation' ) : '<p>' . sprintf( __( 'Visit the <a href="%s">public web site</a> to set up a cart order', 'my-tickets' ), home_url() ) . '</p>';
		$total = '<strong>' . __( 'Total', 'my-tickets' ) . '</strong>: ' . apply_filters( 'mt_money_format', mt_total_cart( $cart, $post_id ) );
	} else {
		$order = '';
		$total = '';
	}
	echo wp_kses( '<div class="mt_post_fields">' . $format . $order . $total . '</div>', mt_kses_elements() );
}

/**
 * Create interface for viewing payment fields that can't be edited.
 */
function mt_add_uneditable() {
	global $post_id;
	if ( isset( $_GET['post'] ) && isset( $_GET['action'] ) ) {
		$data = mt_payment_data( $post_id );
		echo wp_kses_post( $data );
	}
}

/**
 * Get payment purchase data for payment sidebar.
 *
 * @param int   $post_id Post ID.
 * @param array $sections Sections to include in output.
 *
 * @return string
 */
function mt_payment_data( $post_id, $sections = array() ) {
	$dispute        = get_post_meta( $post_id, '_dispute_reason', true );
	$dispute_reason = get_post_meta( $post_id, '_dispute_message', true );

	if ( $dispute ) {
		$dispute_data  = "<div class='mt-dispute'><h3>" . __( 'Ticket Dispute: ', 'my-tickets' ) . '</h3><ul>';
		$dispute_data .= "<li>$dispute</li>";
		$dispute_data .= "<li>$dispute_reason</li>";
		$dispute_data .= '</ul></div>';
	} else {
		$dispute_data = '';
	}
	$receipt       = get_post_meta( $post_id, '_receipt', true );
	$options       = array_merge( mt_default_settings(), get_option( 'mt_settings', array() ) );
	$link          = add_query_arg( 'receipt_id', $receipt, get_permalink( $options['mt_receipt_page'] ) );
	$bulk_tickets  = add_query_arg(
		array(
			'receipt_id' => $receipt,
			'multiple'   => true,
		),
		get_permalink( $options['mt_tickets_page'] )
	);
	$purchase      = get_post_meta( $post_id, '_purchased' );
	$discount      = get_post_meta( $post_id, '_discount', true );
	$discount_text = '';
	if ( get_post_meta( $post_id, '_mtdi_discount', true ) ) {
		// Translators: Quantity of member discount.
		$discount_text = ( '' !== trim( $discount ) ) ? sprintf( __( ' @ %d&#37; member discount', 'my-tickets' ), $discount ) : '';
	}

	$status = get_post_meta( $post_id, '_is_paid', true );
	$total  = mt_money_format( get_post_meta( $post_id, '_total_paid', true ) );
	// Translators: Amount still owed on this transaction.
	$owed             = ( 'Pending' === $status ) ? "<div class='mt-owed'>" . sprintf( __( 'Owed: %s', 'my-tickets' ), $total ) . '</div>' : '';
	$tickets          = mt_setup_tickets( $purchase, $post_id );
	$ticket_data      = "<div class='ticket-data panel'><div class='inner'><h3>" . __( 'Tickets', 'my-tickets' ) . '</h3>' . mt_format_tickets( $tickets, 'html', $post_id ) . '<br /><a href="' . $bulk_tickets . '">View All Tickets</a></div></div>';
	$purchase_data    = "<div class='transaction-purchase panel'><div class='inner'><h3>" . __( 'Receipt ID:', 'my-tickets' ) . " <code><a href='$link'>$receipt</a></code></h3>" . mt_format_purchase( $purchase, 'html', $post_id ) . '</div></div>';
	$gateway          = get_post_meta( $post_id, '_gateway', true );
	$transaction_data = "<div class='transaction-data $gateway panel'><div class='inner'><h3>" . __( 'Gateway:', 'my-tickets' ) . " <code>$gateway</code>$discount_text</h3>" . apply_filters( 'mt_format_transaction', get_post_meta( $post_id, '_transaction_data', true ), get_post_meta( $post_id, '_gateway', true ) ) . '</div></div>';
	$other_data       = apply_filters( 'mt_show_in_payment_fields', '', $post_id );
	if ( '' !== $other_data ) {
		$other_data = "<div class='custom-data panel'><div class='inner'><h3>" . __( 'Custom Field Data', 'my-tickets' ) . '</h3>' . $other_data . '</div></div>';
	}
	$top    = apply_filters( 'mt_payment_purchase_information_top', '', $post_id );
	$bottom = apply_filters( 'mt_payment_purchase_information_bottom', '', $post_id );

	if ( ! in_array( 'dispute', $sections, true ) && ! empty( $sections ) ) {
		$dispute_data = '';
	}
	if ( ! in_array( 'transaction', $sections, true ) && ! empty( $sections ) ) {
		$transaction_data = '';
	}
	if ( ! in_array( 'purchase', $sections, true ) && ! empty( $sections ) ) {
		$purchase_data = '';
	}
	if ( ! in_array( 'ticket', $sections, true ) && ! empty( $sections ) ) {
		$ticket_data = '';
	}
	if ( ! in_array( 'other', $sections, true ) && ! empty( $sections ) ) {
		$other_data = '';
	}

	return '<div class="mt_post_fields panels">' . $top . $owed . $dispute_data . $transaction_data . $purchase_data . $ticket_data . $other_data . $bottom . '</div>';
}

/**
 * Get a list of event IDs for any given purchase.
 *
 * @param int $purchase_id Payment ID.
 *
 * @return array
 */
function mt_list_events( $purchase_id ) {
	$purchase = get_post_meta( $purchase_id, '_purchased' );
	$events   = array();
	if ( is_array( $purchase ) ) {
		foreach ( $purchase as $purch ) {
			foreach ( $purch as $event => $tickets ) {
				$events[] = $event;
			}
		}
	}

	return $events;
}

/**
 * Generate tickets for a given purchase.
 *
 * @param array $purchase Purchase data.
 * @param int   $id Payment ID.
 *
 * @return array
 */
function mt_setup_tickets( $purchase, $id ) {
	$options      = array_merge( mt_default_settings(), get_option( 'mt_settings', array() ) );
	$ticket_array = array();
	foreach ( $purchase as $purch ) {
		foreach ( $purch as $event => $tickets ) {
			$purchases[ $event ] = $tickets;
			$ticket_meta         = get_post_meta( $event, '_ticket' );
			foreach ( $tickets as $type => $details ) {
				// add ticket hash for each ticket.
				$count = $details['count'];
				// only add tickets if count of tickets is more than 0.
				if ( $count >= 1 ) {
					$price = $details['price'];
					for ( $i = 0; $i < $count; $i ++ ) {
						$ticket_id = mt_generate_ticket_id( $id, $event, $type, $i, $price );
						// check for existing ticket data.
						$meta = get_post_meta( $id, $ticket_id, true );
						// if ticket data doesn't exist, create it.
						if ( ! $meta ) {
							if ( ! in_array( $ticket_id, $ticket_meta, true ) ) {
								add_post_meta( $event, '_ticket', $ticket_id );
							}
							update_post_meta(
								$id,
								$ticket_id,
								array(
									'type'        => $type,
									'price'       => $price,
									'purchase_id' => $id,
								)
							);
						}

						$ticket_array[ $ticket_id ] = add_query_arg( 'ticket_id', $ticket_id, get_permalink( $options['mt_tickets_page'] ) );
					}
				}
			}
		}
	}

	return $ticket_array;
}

add_filter( 'mt_format_transaction', 'mt_offline_transaction', 5, 2 );
/**
 * Format transaction data shown in Payment history.
 *
 * @param array  $transaction Transaction details.
 * @param string $gateway Selected gateway.
 *
 * @return string
 */
function mt_offline_transaction( $transaction, $gateway ) {
	// this is the default format.
	$output   = '';
	$shipping = '';
	if ( is_array( $transaction ) ) {
		foreach ( $transaction as $key => $value ) {
			if ( 'shipping' === $key ) {
				foreach ( $value as $label => $field ) {
					if ( 'status' === $label && isset( $_GET['post_id'] ) ) {
						$post_id = (int) $_GET['post_id'];
						$field   = get_post_meta( $post_id, '_is_paid', true );
					}
					$shipping .= '<li><strong>' . ucfirst( $label ) . "</strong> $field</li>";
				}
			} else {
				$output .= '<li><strong>' . ucfirst( str_replace( '_', ' ', $key ) ) . "</strong>: $value</li>";
			}
		}
	}
	if ( ! $output ) {
		return __( 'Transaction not yet completed.', 'my-tickets' );
	} else {
		if ( $shipping ) {
			$shipping = '<h3>' . __( 'Shipping Address', 'my-tickets' ) . '</h3><ul>' . $shipping . '</ul>';
		}
		if ( $output ) {
			$output = '<ul>' . $output . '</ul>';
		}

		return $output . $shipping;
	}
}

/**
 * Define meta box fields that can be changed by Admin in a payment record.
 *
 * @param string      $key Name of field.
 * @param string      $label Label for field.
 * @param string      $type Type of field.
 * @param integer     $post_id Post ID.
 * @param bool|array  $choices Array of choices for multiple choice fields.
 * @param bool|string $multiple Indicates whether this is part of a set of fields.
 * @param string      $notes Field notes.
 * @param array       $field Array governing field context.
 *
 * @return bool|string
 */
function mt_create_field( $key, $label, $type, $post_id, $choices = false, $multiple = false, $notes = '', $field = array() ) {
	$options = array_merge( mt_default_settings(), get_option( 'mt_settings', array() ) );
	if ( isset( $field['context'] ) && 'edit' === $field['context'] && ! isset( $_GET['post'] ) ) {
		return '';
	}
	if ( isset( $field['context'] ) && 'new' === $field['context'] && isset( $_GET['post'] ) ) {
		return '';
	}
	$value = false;
	if ( 'true' === $multiple ) {
		$custom = (array) get_post_meta( $post_id, '_' . $key );
	} else {
		$custom = esc_attr( get_post_meta( $post_id, '_' . $key, true ) );
	}
	if ( 'notes' !== $key && 'Refunded' === get_post_meta( $post_id, '_is_paid', true ) ) {
		$disabled = 'disabled';
	} else {
		$disabled = '';
	}
	switch ( $type ) {
		case 'text':
			if ( $multiple ) {
				foreach ( $custom as $val ) {
					if ( is_array( $val ) ) {
						foreach ( $val as $event => $tickets ) {
							$event_title = get_the_title( $event );
							$value      .= '<p><strong>' . $label . ': ' . $event_title . '</strong><br />';
							foreach ( $tickets as $klabel => $data ) {
								$value .= "<em>$klabel</em>: $data[count] @ $data[price]<br />";
							}
							$value .= '</p>';
						}
					}
				}
			} else {
				if ( 'total_paid' === $key && '' !== $custom ) {
					$custom = number_format( $custom, 2 );
					$label .= ' (' . $options['mt_currency'] . ')';
				}
				$value = "<label for='_$key'>$label</label><br /><input class='widefat' type='text' name='_$key' id='_$key' value='$custom' $disabled />";
			}
			break;
		case 'textarea':
			$value = '<label for="_' . $key . '">' . $label . ' <em>(' . $notes . ')</em></label><br />' . '<textarea class="widefat" cols="60" rows="4" name="_' . $key . '" id="_' . $key . '">' . $custom . '</textarea>';
			break;
		case 'checkbox':
			// the mt_return_tickets should only be visible if a payment is failed.
			if ( ( 'mt_return_tickets' === $key && 'Failed' === get_post_meta( $post_id, '_is_paid', true ) || 'Refunded' === get_post_meta( $post_id, '_is_paid', true ) || 'Turned Back' === get_post_meta( $post_id, '_is_paid', true ) ) || 'mt_return_tickets' !== $key ) {
				if ( 'mt_return_tickets' === $key && 'true' === get_post_meta( $post_id, '_returned', true ) ) {
					$notes = __( 'Tickets from this purchase have been returned to the purchase pool', 'my-tickets' );
				}
				$checked = checked( $custom, 'true', false );
				$value   = '<input type="checkbox" name="_' . $key . '" id="_' . $key . '" aria-labelledby="_' . $key . ' _' . $key . '_notes" value="true" ' . $checked . ' /> <label for="_' . $key . '">' . $label . '</label><br /><span id="_' . $key . '_notes">' . $notes . '</span>';
			}
			break;
		case 'select':
			$value = '<label for="_' . $key . '">' . $label . '</label> ' . '<select name="_' . $key . '" id="_' . $key . '">' . mt_create_options( $choices, $custom ) . '</select>';
			break;
		case 'none':
			$value = "<p><strong>$label</strong>: <span>" . esc_html( $custom ) . '</span></p>';
			break;
	}

	return "<div class='mt-field $type'>" . $value . '</div>';
}

/**
 * Create options for a custom select control.
 *
 * @param array      $choices Options for select control.
 * @param string|int $selected Selected choice.
 *
 * @return string
 */
function mt_create_options( $choices, $selected ) {
	$return = '';
	if ( is_array( $choices ) ) {
		foreach ( $choices as $key => $value ) {
			if ( ! is_numeric( $key ) ) {
				$k       = esc_attr( $key );
				$chosen  = ( $k === $selected ) ? ' selected="selected"' : '';
				$return .= "<option value='$key'$chosen>$value</option>";
			} else {
				$v       = esc_attr( $value );
				$chosen  = ( $v === $selected ) ? ' selected="selected"' : '';
				$return .= "<option value='$value'$chosen>$value</option>";
			}
		}
	}

	return $return;
}

add_action( 'save_post', 'mt_post_meta', 10 );
/**
 * Save updates to payment meta data
 *
 * @param int $id Post ID.
 */
function mt_post_meta( $id ) {
	$fields = mt_default_fields();
	if ( isset( $_POST['mt-meta-nonce'] ) ) {
		$nonce = $_POST['mt-meta-nonce'];
		if ( ! wp_verify_nonce( $nonce, 'mt-meta-nonce' ) ) {
			wp_die( 'Invalid nonce' );
		}
		if ( isset( $_POST['_inline_edit'] ) ) {
			return;
		}
		// create new ticket purchase.
		if ( isset( $_POST['mt_cart_order'] ) ) {
			$purchased = map_deep( $_POST['mt_cart_order'], 'sanitize_text_field' );
			mt_create_tickets( $id, $purchased );
			// handle custom fields in custom orders.
			do_action( 'mt_save_payment_fields', $id, $_POST, $purchased );
			$receipt_id = md5(
				add_query_arg(
					array(
						'post_type' => 'mt-payments',
						'p'         => $id,
					),
					home_url()
				)
			);
			update_post_meta( $id, '_receipt', $receipt_id );
		}

		if ( is_array( $fields ) ) {
			foreach ( $fields as $key => $value ) {
				if ( 'checkbox' === $value['input'] && ! isset( $_POST[ '_' . $key ] ) ) {
					delete_post_meta( $id, '_' . $key );
				}
				if ( isset( $_POST[ '_' . $key ] ) ) {
					$value = sanitize_text_field( $_POST[ '_' . $key ] );
					if ( 'is_paid' === $key ) {
						// Track last status.
						update_post_meta( $id, '_last_status', get_post_meta( $id, '_is_paid', true ) );
					}
					update_post_meta( $id, '_' . $key, $value );
					// If related event has been deleted, ignore this.
					if ( 'mt_return_tickets' === $key && 'true' === $value && ( false !== get_post_status( $id ) ) ) {
						mt_return_tickets( $id );
					}
				}
			}
		}
	}
}

add_action( 'init', 'mt_posttypes' );
/**
 * Define Payments post type.
 */
function mt_posttypes() {
	$labels = array(
		'name'               => 'Payments',
		'singular_name'      => 'Payment',
		'menu_name'          => 'Payments',
		'add_new'            => __( 'Add New', 'my-tickets' ),
		'add_new_item'       => __( 'Create New Payment', 'my-tickets' ),
		'edit_item'          => __( 'Modify Payment', 'my-tickets' ),
		'new_item'           => __( 'New Payment', 'my-tickets' ),
		'view_item'          => __( 'View Payment', 'my-tickets' ),
		'search_items'       => __( 'Search payments', 'my-tickets' ),
		'not_found'          => __( 'No payments found', 'my-tickets' ),
		'not_found_in_trash' => __( 'No payments found in Trash', 'my-tickets' ),
		'parent_item_colon'  => '',
	);
	$args   = array(
		'labels'              => $labels,
		'public'              => false,
		'publicly_queryable'  => false,
		'exclude_from_search' => true,
		'show_ui'             => true,
		'show_in_menu'        => true,
		'menu_icon'           => 'dashicons-tickets',
		'query_var'           => true,
		'hierarchical'        => false,
		'supports'            => array( 'title' ),
	);
	register_post_type( 'mt-payments', $args );
}

add_filter( 'post_updated_messages', 'mt_posttypes_messages' );
/**
 * Define textdomain messages for Payments post type.
 *
 * @param array $messages Post type descriptors.
 *
 * @return mixed
 */
function mt_posttypes_messages( $messages ) {
	global $post;
	$messages['mt-payments'] = array(
		0  => '', // Unused. Messages start at index 1.
		1  => __( 'Payment updated.', 'my-tickets' ),
		2  => __( 'Custom field updated.', 'my-tickets' ),
		3  => __( 'Custom field deleted.', 'my-tickets' ),
		4  => __( 'Payment updated.', 'my-tickets' ),
		// translators: %s: date and time of the revision.
		5  => isset( $_GET['revision'] ) ? sprintf( __( 'Payment restored to revision from %s', 'my-tickets' ), wp_post_revision_title( (int) $_GET['revision'], false ) ) : false,
		6  => __( 'Payment published.', 'my-tickets' ),
		7  => __( 'Payment saved.', 'my-tickets' ),
		8  => __( 'Payment submitted.', 'my-tickets' ),
		// Translators: %s: date scheduled to publish.
		9  => sprintf( __( 'Payment scheduled for: <strong>%s</strong>.', 'my-tickets' ), date_i18n( __( 'M j, Y @ G:i' ), strtotime( $post->post_date ) ) ),
		10 => __( 'Payment draft updated.', 'my-tickets' ),
	);

	return $messages;
}

/**
 * Get value of a custom field by fieldname and post ID.
 *
 * @param string $field Name of field.
 * @param int    $id Post ID.
 *
 * @return mixed
 */
function mt_get_custom_field( $field, $id = false ) {
	global $post;
	$id           = ( $id ) ? absint( $id ) : $post->ID;
	$custom_field = get_post_meta( $id, $field, true );

	return $custom_field;
}

// Actions/Filters for various tables and the css output.
add_action( 'admin_init', 'mt_add' );
/**
 * Add custom columns to payments post type page.
 */
function mt_add() {
	$options = array_merge( mt_default_settings(), get_option( 'mt_settings', array() ) );
	add_action( 'admin_head', 'mt_css' );
	add_filter( 'manage_mt-payments_posts_columns', 'mt_column' );
	add_action( 'manage_mt-payments_posts_custom_column', 'mt_custom_column', 10, 2 );
	foreach ( $options['mt_post_types'] as $name ) {
		add_filter( 'manage_' . $name . '_posts_columns', 'mt_is_event' );
		add_action( 'manage_' . $name . '_posts_custom_column', 'mt_is_event_column', 10, 2 );
	}
}

/**
 * Add column to show whether a post has event characteristics to post manager.
 *
 * @param array $cols All columns.
 *
 * @return mixed
 */
function mt_is_event( $cols ) {
	$cols['mt_is_event'] = __( 'Tickets', 'my-ticket' );

	return $cols;
}

/**
 * Add status/total and receipt ID fields to Payments post type.
 *
 * @param array $cols All columns.
 *
 * @return mixed
 */
function mt_column( $cols ) {
	$cols['mt_status']      = __( 'Status', 'my-tickets' );
	$cols['mt_paid']        = __( 'Cart Total', 'my-tickets' );
	$cols['mt_receipt']     = __( 'Receipt ID', 'my-tickets' );
	$cols['mt_payer_email'] = __( 'Email', 'my-tickets' );

	return $cols;
}

/**
 * If post object has event characteristics, show tickets sold/remaining.
 *
 * @param string $column_name Name of column.
 * @param int    $id Object ID.
 */
function mt_is_event_column( $column_name, $id ) {
	switch ( $column_name ) {
		case 'mt_is_event':
			$event_data = get_post_meta( $id, '_mc_event_data', true );
			if ( $event_data ) {
				$registration = get_post_meta( $id, '_mt_registration_options', true );
				if ( is_array( $registration ) ) {
					$available = $registration['total'];
					$pricing   = $registration['prices'];
					$tickets   = mt_tickets_left( $pricing, $available );
					$remain    = ( 'general' === $registration['counting_method'] ) ? __( 'No limit', 'my-tickets' ) : $tickets['remain'];
					$sold      = $tickets['sold'];
					// Translators: Tickets remaining, total sold.
					$status = "<span class='mt is-event'>" . sprintf( __( '%1$s (%2$s sold)', 'my-tickets' ), $remain, $sold ) . '</span>';
				} else {
					$status = "<span class='mt not-event'>" . __( 'Not ticketed', 'my-tickets' ) . '</span>';
				}
			} else {
				$status = "<span class='mt not-event'>" . __( 'Not ticketed', 'my-tickets' ) . '</span>';
			}
			echo wp_kses_post( $status );
			break;
	}
}

add_filter( 'mc_event_classes', 'mt_is_mc_ticketed', 10, 4 );
/**
 * Add class to My Calendar events if have tickets
 *
 * @param array  $classes Array of my calendar classes.
 * @param object $event My Calendar event object.
 * @param int    $uid Unique ID.
 * @param string $type Display type.
 *
 * @return array
 */
function mt_is_mc_ticketed( $classes, $event, $uid, $type ) {
	if ( ! is_object( $event ) ) {
		return $classes;
	}
	$event_id = $event->event_post;
	if ( mt_is_ticketed_event( $event_id ) ) {
		$classes[] = 'ticketed-event';
	}

	return $classes;
}

/**
 * Check a given post ID to see event status
 *
 * @param int $id Post ID.
 *
 * @return boolean
 */
function mt_is_ticketed_event( $id ) {
	$event_data = get_post_meta( $id, '_mc_event_data', true );
	if ( $event_data ) {
		$registration = get_post_meta( $id, '_mt_registration_options', true );
		if ( is_array( $registration ) ) {
			$status = true;
		} else {
			$status = false;
		}
	} else {
		$status = false;
	}

	return $status;
}

/**
 * In Payment post type, get status paid and receipt data.
 *
 * @param string $column_name Name of the current column.
 * @param int    $id Post ID.
 */
function mt_custom_column( $column_name, $id ) {
	switch ( $column_name ) {
		case 'mt_status':
			$pd       = get_post_meta( $id, '_is_paid', true );
			$pd_class = esc_attr( strtolower( $pd ) );
			$pd_class = ( false !== strpos( $pd_class, 'other' ) ) ? 'other' : $pd_class;
			$status   = "<span class='mt $pd_class'>$pd</span>";
			echo wp_kses_post( $status );
			break;
		case 'mt_paid':
			$pd   = get_post_meta( $id, '_total_paid', true );
			$pd   = apply_filters( 'mt_money_format', $pd );
			$paid = "<span>$pd</span>";
			echo wp_kses_post( $paid );
			break;
		case 'mt_receipt':
			$pd      = get_post_meta( $id, '_receipt', true );
			$receipt = "<code>$pd</code>";
			echo wp_kses_post( $receipt );
			break;
		case 'mt_payer_email':
			$em   = get_post_meta( $id, '_email', true );
			$show = '<code>' . sanitize_email( $em ) . '</code>';
			echo wp_kses_post( $show );
			break;
	}
}

/**
 * Value of current column.
 *
 * @param mixed  $value Value to display.
 * @param string $column_name Column key.
 * @param int    $id Post ID.
 *
 * @return mixed
 */
function mt_return_value( $value, $column_name, $id ) {
	if ( 'mt_status' === $column_name || 'mt_paid' === $column_name || 'mt_receipt' === $column_name || 'mt_email' === $column_name ) {
		$value = $id;
	}

	return $value;
}

/**
 * Custom width CSS for columns.
 */
function mt_css() {
	global $current_screen;
	if ( 'mt-payments' === $current_screen->id || 'edit-mt-payments' === $current_screen->id ) {
		wp_enqueue_style( 'mt.posts', plugins_url( 'css/mt-post.css', __FILE__ ) );
	}
}

add_filter( 'pre_get_posts', 'filter_mt_payments' );
/**
 * Run filters to view sets of payments.
 *
 * @param object $query WP Query object.
 */
function filter_mt_payments( $query ) {
	global $pagenow;
	if ( ! is_admin() ) {
		return;
	}

	$qv = &$query->query_vars;
	if ( 'edit.php' === $pagenow && ! empty( $qv['post_type'] ) && 'mt-payments' === $qv['post_type'] ) {
		if ( empty( $_GET['mt_filter'] ) || 'all' === $_GET['mt_filter'] ) {
			return;
		}
		if ( isset( $_GET['mt_filter'] ) ) {
			$value = esc_html( $_GET['mt_filter'] );
			$query->set(
				'meta_query',
				array(
					array(
						'key'     => '_is_paid',
						'value'   => $value,
						'compare' => '=',
					),
				)
			);
		}
	}
}

add_action( 'restrict_manage_posts', 'filter_mt_dropdown' );
/**
 * Show dropdown to filter payments.
 */
function filter_mt_dropdown() {
	global $typenow;
	if ( 'mt-payments' === $typenow ) {
		$mt_filter = isset( $_GET['mt_filter'] ) ? $_GET['mt_filter'] : '';
		?>
		<label for="mt_filter" class="screen-reader-text"><?php _e( 'Filter Payments', 'my-tickets' ); ?></label>
		<select class="postform" id="mt_filter" name="mt_filter">
			<option value="all"><?php _e( 'All Payments', 'my-tickets' ); ?></option>
			<option value="Completed"<?php selected( 'Completed', $mt_filter ); ?>><?php _e( 'Completed', 'my-tickets' ); ?></option>
			<option value="Pending"<?php selected( 'Pending', $mt_filter ); ?>><?php _e( 'Pending', 'my-tickets' ); ?></option>
			<option value="Refunded"<?php selected( 'Refunded', $mt_filter ); ?>><?php _e( 'Refunded', 'my-tickets' ); ?></option>
			<option value="Failed"<?php selected( 'Failed', $mt_filter ); ?>><?php _e( 'Failed', 'my-tickets' ); ?></option>
		</select>
		<?php
	}
}

add_filter( 'bulk_actions-edit-mt-payments', 'mt_bulk_actions' );
/**
 * Add bulk action to mark payments completed.
 *
 * @param array $bulk_actions Existing bulk actions.
 */
function mt_bulk_actions( $bulk_actions ) {
	$bulk_actions['complete'] = __( 'Mark as Completed', 'my-tickets' );

	return $bulk_actions;
}

add_filter( 'handle_bulk_actions-edit-mt-payments', 'mt_bulk_action_handler', 10, 3 );
/**
 * Implement bulk actions.
 *
 * @param string $redirect_to Redirect to new URL.
 * @param string $doaction Selected bulk action.
 * @param array  $post_ids Array of IDs selected.
 *
 * @return string $redirect_to
 */
function mt_bulk_action_handler( $redirect_to, $doaction, $post_ids ) {
	if ( 'complete' !== $doaction ) {
		return $redirect_to;
	}
	$completed = 0;
	foreach ( $post_ids as $post_id ) {
		update_post_meta( $post_id, '_is_paid', 'Completed' );
		// Set previous status to 'Pending' to ensure notifications are sent.
		update_post_meta( $post_id, '_last_status', 'Pending' );
		wp_update_post(
			array(
				'ID'          => $post_id,
				'post_status' => 'publish',
			)
		);
		$completed ++;
	}
	// build the redirect url.
	$redirect_to = add_query_arg(
		array(
			'completed' => $completed,
			'ids'       => join( ',', $post_ids ),
		),
		$redirect_to
	);

	return $redirect_to;
}

add_action( 'admin_notices', 'mt_bulk_admin_notices' );
/**
 * Admin notice covering bulk edits.
 */
function mt_bulk_admin_notices() {
	global $post_type, $pagenow;
	if ( 'edit.php' === $pagenow && 'mt-payments' === $post_type && isset( $_REQUEST['completed'] ) && (int) $_REQUEST['completed'] ) {
		// Translators: Number of payments edited.
		$message = sprintf( _n( '%s payment completed & ticket notification sent.', '%s payments completed and ticket notifications sent.', $_REQUEST['completed'], 'my-tickets' ), number_format_i18n( $_REQUEST['completed'] ) );
		echo wp_kses_post( "<div class='updated'><p>$message</p></div>" );
	}
}

add_filter( 'wp_list_pages_excludes', 'mt_exclude_pages', 10, 2 );
/**
 * Exclude receipt and ticket pages from page lists.
 *
 * @param array $array Array of pages.
 *
 * @return array
 */
function mt_exclude_pages( $array ) {
	if ( ! is_admin() ) {
		$options  = array_merge( mt_default_settings(), get_option( 'mt_settings', array() ) );
		$tickets  = $options['mt_tickets_page'];
		$receipts = $options['mt_receipt_page'];
		if ( $tickets && $receipts ) {
			$array[] = $tickets;
			$array[] = $receipts;
		}
	}

	return $array;
}

add_filter( 'display_post_states', 'mt_post_states', 10, 2 );
/**
 * Change 'draft' label to 'Active cart'
 *
 * @param array  $post_states Default post states array.
 * @param object $post Current post.
 *
 * @return array
 */
function mt_post_states( $post_states, $post ) {
	$post = get_post( $post );
	if ( 'mt-payments' === get_post_type( $post ) && 'draft' === $post->post_status ) {
		$post_states['draft'] = __( 'Active cart', 'my-tickets' );
	}
	return $post_states;
}
