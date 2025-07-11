<?php
/**
 * Process submissions from add to cart forms.
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

// begin add boxes.
add_action( 'admin_menu', 'mt_add_ticket_box' );
/**
 * Add purchase data meta box to enabled post types.
 */
function mt_add_ticket_box() {
	$options = mt_get_settings();
	foreach ( $options['mt_post_types'] as $name ) {
		if ( 'mc-events' !== $name ) {
			add_meta_box( 'mt_custom_div', __( 'My Tickets Purchase Data', 'my-tickets' ), 'mt_add_ticket_form', $name, 'normal', 'high' );
		}
	}
}

/**
 * Add ticket form to enabled post types meta boxes.
 */
function mt_add_ticket_form() {
	global $post_id;
	$format   = sprintf(
		'<input type="hidden" name="%1$s" id="%1$s" value="%2$s" />',
		'mt-tickets-nonce',
		wp_create_nonce( 'mt-tickets-nonce' )
	);
	$data     = get_post_meta( $post_id, '_mc_event_data', true );
	$location = get_post_meta( $post_id, '_mc_event_location', true );

	$options       = mt_get_settings();
	$purchase_page = $options['mt_purchase_page'];
	$receipt_page  = $options['mt_receipt_page'];
	$tickets_page  = $options['mt_tickets_page'];
	$current       = ( isset( $_GET['post'] ) ) ? intval( $_GET['post'] ) : false;
	if ( ( $current === $purchase_page || $current === $receipt_page || $current === $tickets_page ) && empty( $data ) ) {
		echo wp_kses_post( '<p>' . __( 'This is a core My Tickets page, used for processing transactions. You cannot use this page as an event.', 'my-tickets' ) . '</p>' );
		return;
	}
	$validity = array(
		'3 years'  => __( '3 years', 'my-tickets' ),
		'2 years'  => __( '2 years', 'my-tickets' ),
		'1 year'   => __( '1 year', 'my-tickets' ),
		'6 months' => __( '6 months', 'my-tickets' ),
		'3 months' => __( '3 months', 'my-tickets' ),
		'1 month'  => __( '1 month', 'my-tickets' ),
		'4 weeks'  => __( '4 weeks', 'my-tickets' ),
		'3 weeks'  => __( '3 weeks', 'my-tickets' ),
		'2 weeks'  => __( '2 weeks', 'my-tickets' ),
		'1 week'   => __( '1 week', 'my-tickets' ),
		'infinite' => __( 'No expiration', 'my-tickets' ),
	);
	/**
	 * Filter the available choices for how long general admissions tickets are valid.
	 *
	 * @hook mt_validity_options
	 *
	 * @param {array} $validity Associative array of validity options.
	 *
	 * @return {array}
	 */
	$validity = apply_filters( 'mt_validity_options', $validity );
	// add fields for event time and event date.
	if ( isset( $data['event_begin'] ) ) {
		$event_begin = $data['event_begin'];
		$event_time  = $data['event_time'];
		$sell        = ' checked="checked"';
		$general     = ( isset( $data['general_admission'] ) && 'on' === $data['general_admission'] ) ? ' checked="checked"' : '';
		$dated       = ( isset( $data['general_admission'] ) && 'on' === $data['general_admission'] ) ? '' : ' checked="checked"';
		$valid       = ( isset( $data['event_valid'] ) && $general ) ? $data['event_valid'] : '';
		$expire      = ( isset( $data['expire_date'] ) && is_numeric( $data['expire_date'] ) && $general ) ? gmdate( 'Y-m-d', $data['expire_date'] ) : '';
	} else {
		$event_begin = '';
		$event_time  = '';
		$sell        = '';
		$general     = '';
		$dated       = '';
		$valid       = '';
		$expire      = '';
	}
	$option_string = '<option value="">' . __( 'Select a value', 'my-tickets' ) . '</option>
	<option value="expire"' . selected( 'expire', $valid, false ) . '>' . __( 'Custom expiration date', 'my-tickets' ) . '</option>';
	foreach ( $validity as $key => $option ) {
		$option_string .= '<option value="' . esc_attr( $key ) . '"' . selected( $key, $valid, false ) . '>' . esc_html( $option ) . '</option>';
	}
	$clear = '<p><input type="checkbox" class="mt-delete-data" name="mt-delete-data" id="mt-delete-data" /> <label for="mt-delete-data">' . __( 'Delete ticket sales data on this post', 'my-tickets' ) . '</label></p>';
	// Show ticket selector checkbox on post types.
	global $current_screen;
	if ( $current_screen && 'post' === $current_screen->base ) {
		$format .= "<p class='mt-trigger-container'>
			<input type='checkbox' class='mt-trigger' name='mt-trigger' id='mt-trigger'$sell /> <label for='mt-trigger'>" . __( 'Sell tickets on this post.', 'my-tickets' ) . '</label>
			</p>';
	}
	if ( function_exists( 'mc_location_select' ) ) {
		$selector = "
		<label for='mt-event-location'>" . __( 'Select a location', 'my-tickets' ) . "</label>
		<select name='mt-event-location' id='mt-event-location'>
			<option value=''> -- </option>
			" . mc_location_select( $location ) . '
		</select>';
	} else {
		// Translators: URL for My Calendar installation.
		$selector = sprintf( __( 'Install <a href="%s">My Calendar</a> to manage and choose locations for your events', 'my-tickets' ), admin_url( 'plugin-install.php?tab=search&s=my-calendar' ) );
	}
	$form =
		"<div class='mt-ticket-form'>
			<ul class='checkboxes type-selector'>
				<li><input type='radio' name='mt_general' value='dated' id='mt-general-dated'$dated /> <label for='mt-general-dated'>" . __( 'Date-based', 'my-tickets' ) . "</label></li>
				<li><input type='radio' name='mt_general' value='general' id='mt-general-general'$general /> <label for='mt-general-general'>" . __( 'General Admission', 'my-tickets' ) . "</label></li>
			</ul>
			<div class='mt-ticket-data'>
				<div class='mt-ticket-validity'>
					<p>
						<label for='mt_valid'>" . __( 'Ticket validity', 'my-tickets' ) . "</label> <select name='mt_valid' id='mt_valid'>$option_string</select>
					</p>
					<p class='expire_date'>
						<label for='expire_date'>" . __( 'Ticket Expiration Date', 'my-tickets' ) . "</label> <input type='date' name='expire_date' id='expire_date' value='$expire' />
					</p>
				</div>
				<div class='mt-ticket-dates'>
					<p>
						<label for='event_begin'>" . __( 'Event Date', 'my-tickets' ) . "</label> <input type='date' name='event_begin' id='event_begin' value='$event_begin' /> <label for='event_time'>" . __( 'Event Time', 'my-tickets' ) . "</label> <input type='time' name='event_time' id='event_time' value='$event_time' />
					</p>
				</div>
			</div>
			<div class='mt-ticket-location'>
				<p>
					$selector
				</p>
			</div>" . apply_filters( 'mc_event_registration', '', $post_id, $data, 'admin' ) . $clear . '</div>';
	echo wp_kses( '<div class="mt_post_fields my-tickets">' . $format . $form . '</div>', mt_kses_elements() );
}

add_action( 'save_post', 'mt_ticket_meta', 10 );
/**
 * Save ticket meta data when enabled post is saved.
 *
 * @param int $post_id Post ID.
 */
function mt_ticket_meta( $post_id ) {
	if ( isset( $_POST['mt-tickets-nonce'] ) && isset( $_POST['mt-trigger'] ) ) {
		$nonce = sanitize_text_field( $_POST['mt-tickets-nonce'] );
		if ( ! wp_verify_nonce( $nonce, 'mt-tickets-nonce' ) ) {
			wp_die( 'My Tickets: Invalid meta nonce' );
		}
		$post = map_deep( $_POST, 'sanitize_textarea_field' );
		if ( 'event' === $post['mt_counting_method'] ) {
			// If using event types, then set the overall event date to the last event in the series.
			$dates  = $post['mt_label'];
			$times  = $post['mt_label_time'];
			$stamps = array();
			foreach ( $dates as $key => $date ) {
				$date     = strtotime( $date . ' ' . $times[ $key ] );
				$stamps[] = $date;
			}
			rsort( $stamps );
			$event_begin = mt_date( 'Y-m-d', $stamps[0], false );
			$event_time  = mt_date( 'H:i:s', $stamps[0], false );
		} else {
			$event_begin = mt_date( 'Y-m-d', strtotime( $post['event_begin'] ), false );
			if ( '' !== $post['event_time'] ) {
				$event_time = mt_date( 'H:i:s', strtotime( $post['event_time'] ), false );
			} else {
				$event_time = '23:59:59';
			}
		}
		$general = ( isset( $post['mt_general'] ) && 'general' === $post['mt_general'] ) ? 'on' : '';
		$valid   = ( isset( $post['mt_valid'] ) ) ? sanitize_text_field( $post['mt_valid'] ) : '';
		$expire  = ( isset( $post['expire_date'] ) ) ? strtotime( $post['expire_date'] ) : '';
		$data    = array(
			'event_begin'       => $event_begin,
			'event_time'        => $event_time,
			'event_post'        => $post_id,
			'general_admission' => $general,
			'event_valid'       => $valid,
			'expire_date'       => $expire,
		);
		if ( isset( $post['mt-event-location'] ) && is_numeric( $post['mt-event-location'] ) ) {
			update_post_meta( $post_id, '_mc_event_location', $post['mt-event-location'] );
		}
		update_post_meta( $post_id, '_mc_event_data', $data );
		update_post_meta( $post_id, '_mc_event_date', strtotime( $event_begin ) );
		mt_save_registration_data( $post_id, $post );
	} elseif ( isset( $_POST['mt-tickets-nonce'] ) && ! isset( $_POST['mt-trigger'] ) ) {
		delete_post_meta( $post_id, '_mc_event_data' );
		delete_post_meta( $post_id, '_mc_event_date' );
		delete_post_meta( $post_id, '_mc_event_location' );
		delete_post_meta( $post_id, '_mt_registration_options' );
	}

	return;
}

/**
 * Gets array of ticket types and prices for an event
 *
 * @param int $event_id Event ID.
 * @param int $payment_id Payment ID.
 *
 * @uses mt_calculate_discount()
 *
 * @return boolean|array
 */
function mt_get_prices( $event_id, $payment_id = false ) {
	$registration = get_post_meta( $event_id, '_mt_registration_options', true );
	if ( isset( $registration['prices'] ) ) {
		$prices = $registration['prices'];
		if ( is_array( $prices ) ) { // cycle only if pricing is being modified.
			foreach ( $prices as $label => $options ) {
				if ( 'sold' !== $label ) {
					$price      = isset( $prices[ $label ]['price'] ) ? $prices[ $label ]['price'] : false;
					$orig_price = $price;
					if ( ! $price ) {
						continue;
					}
					$price                     = mt_calculate_discount( $price, $event_id, $payment_id );
					$prices[ $label ]['price'] = $price;
					if ( $price !== $orig_price ) {
						$prices[ $label ]['orig_price'] = $orig_price;
					}
				}
			}
		}

		return $prices;
	}

	return false;
}

/**
 * Calculates actual cost of an event ticket if member discount in effect
 *
 * @param float    $price Event Ticket Price before discounts.
 * @param int      $event_id Event ID.
 * @param int|bool $payment_id Payment ID.
 *
 * @return float
 */
function mt_calculate_discount( $price, $event_id, $payment_id = false ) {
	$options = mt_get_settings();
	if ( is_user_logged_in() ) { // members discount.
		if ( is_admin() && ! ( defined( 'DOING_AJAX' ) && DOING_AJAX ) ) {
			$discount = 0;
		} else {
			$discount = (int) $options['mt_members_discount'];
		}
	} else {
		$discount = 0;
	}
	$discount   = apply_filters( 'mt_members_discount', $discount, $event_id, $payment_id );
	$discounted = ( 0 !== $discount ) ? $price - ( $price * ( $discount / 100 ) ) : $price;
	$discounted = apply_filters( 'mt_apply_event_discounts', $discounted, $event_id, $payment_id );
	if ( mt_zerodecimal_currency() ) {
		$discounted = round( $discounted, 0 );
	} else {
		$discounted = sprintf( '%01.2f', $discounted );
	}

	return $discounted;
}

/**
 * Add registration fields for My Calendar events & My Tickets posts.
 *
 * @param string $form Form html.
 * @param bool   $has_data Does this form contain data.
 * @param object $data object Data contained.
 * @param string $context Admin or public context.
 * @param string $model Ticket model type to draw.
 *
 * @return string
 */
function mt_registration_fields( $form, $has_data, $data, $context = 'admin', $model = '' ) {
	if ( 'public' === $context ) {
		return $form;
	}
	$event_id     = 0;
	$notes        = '';
	$hide         = false;
	$checked      = '';
	$registration = array();
	$options      = mt_get_settings();
	if ( true === $has_data && property_exists( $data, 'event_post' ) ) {
		$event_id     = (int) $data->event_post;
		$notes        = get_post_meta( $event_id, '_mt_event_notes', true );
		$hide         = get_post_meta( $event_id, '_mt_hide_registration_form', true );
		$registration = get_post_meta( $event_id, '_mt_registration_options', true );
		$checked      = ( 'true' === get_post_meta( $event_id, '_mt_sell_tickets', true ) ) ? ' checked="checked"' : '';
	}
	if ( is_int( $has_data ) && $has_data ) {
		$event_id     = $has_data;
		$notes        = get_post_meta( $event_id, '_mt_event_notes', true );
		$hide         = get_post_meta( $event_id, '_mt_hide_registration_form', true );
		$registration = get_post_meta( $event_id, '_mt_registration_options', true );
		$checked      = ( 'true' === get_post_meta( $event_id, '_mt_sell_tickets', true ) ) ? ' checked="checked"' : '';
	}
	$is_hidden      = ( 'true' === $hide ) ? ' checked="checked"' : '';
	$model_selector = '';
	if ( empty( $registration ) ) {
		$model = ( '' !== $model ) ? $model : $options['default_model'];
		if ( $data ) {
			$data = '<div class="hidden mt-ticket-data-json">' . esc_html( wp_json_encode( $data ) ) . '</div>';
		} else {
			$data = '';
		}
		$selector = '';
		$models   = array( 'continuous', 'discrete', 'event' );
		foreach ( $models as $option ) {
			$selected  = ( $model === $option ) ? ' aria-selected="true"' : ' aria-selected="false"';
			$selector .= "<button type='button' role='tab' class='button button-secondary' data-model='$option' data-event='$event_id' $selected>" . ucfirst( $option ) . '</button>';
		}
		$model_selector = '
		<div class="mt-load-model" role="tablist" aria-label="' . __( 'Choose Ticket Model', 'my-tickets' ) . '">
			' . $data . '<strong aria-hidden="true">' . __( 'Choose Ticket Model', 'my-tickets' ) . '</strong>' . $selector . '
		</div>';
	}
	$shortcode = ( $registration ) ? "<label for='shortcode'>" . __( 'Add to Cart Form Shortcode', 'my-tickets' ) . "</label><br /><textarea id='shortcode' readonly='readonly' class='large-text readonly'>[ticket event='$event_id']</textarea>" : '';

	// Appear on My Calendar events to toggle ticket sales.
	$format  = ( isset( $_GET['page'] ) && 'my-calendar' === $_GET['page'] ) ? "<p><input type='checkbox' class='mt-trigger' name='mt-trigger' id='mt-trigger'$checked /> <label for='mt-trigger'>" . __( 'Sell tickets on this event.', 'my-tickets' ) . '</label></p>' : '';
	$reports = ( $event_id && ! empty( get_post_meta( $event_id, '_ticket' ) ) ) ? "<p class='get-report'><span class='dashicons dashicons-chart-bar' aria-hidden='true'></span> <a href='" . admin_url( "admin.php?page=mt-reports&amp;event_id=$event_id" ) . "'>" . __( 'View Tickets Purchased for this event', 'my-tickets' ) . '</a></p>' : '';
	$form    = $reports . $format . '<div class="mt-ticket-form">' . $shortcode . $model_selector . '<div class="mt-ticket-wrapper-form" role="tabpanel" tabindex="0">' . mt_get_registration_fields( $form, $has_data, $data, $context, $model ) . '</div>';

	$form .= "<p>
		<label for='mt_event_notes'>" . __( 'Event-specific notes for email notifications', 'my-tickets' ) . "</label><br />
		<textarea id='mt_event_notes' name='mt_event_notes' cols='60' rows='4' class='widefat' aria-describedby='template_tag'>" . stripslashes( esc_textarea( $notes ) ) . "</textarea><br />
		<span id='template_tag'>" . __( 'Template tag for email notifications:', 'my-tickets' ) . ' <code>{event_notes}</code></span>
	</p>';
	$form .= "<p><input type='checkbox' name='mt_hide_registration_form' id='mt_hide' $is_hidden /> <label for='mt_hide'>" . __( 'Don\'t display form on event', 'my-tickets' ) . '</label></p></div>';

	return $form;
}

/**
 * Add registration fields for My Calendar events & posts.
 *
 * @param string $form Form html.
 * @param bool   $has_data Does this form contain data.
 * @param object $data object Data contained.
 * @param string $context Admin or public context.
 * @param string $model Whether to fetch a specific ticket model format.
 *
 * @return string
 */
function mt_get_registration_fields( $form, $has_data, $data, $context = 'admin', $model = '' ) {
	$original_form = $form;
	$options       = mt_get_settings();
	$description   = false;
	$event_id      = false;
	if ( true === $has_data && property_exists( $data, 'event_post' ) ) {
		$event_id     = (int) $data->event_post;
		$registration = get_post_meta( $event_id, '_mt_registration_options', true );
		$description  = stripslashes( esc_attr( $data->event_registration ) );
	}
	if ( is_int( $has_data ) && $has_data ) {
		$event_id     = $has_data;
		$description  = false;
		$registration = get_post_meta( $event_id, '_mt_registration_options', true );
	}
	if ( empty( $registration ) ) {
		$default_model = ( '' !== $model ) ? $model : $options['default_model'];
		$registration  = $options['defaults'][ $default_model ];
	}
	$registration['event_id'] = $event_id;

	$expiration  = ( isset( $registration['reg_expires'] ) ) ? $registration['reg_expires'] : $options['defaults']['reg_expires'];
	$multiple    = ( isset( $registration['multiple'] ) ) ? $registration['multiple'] : $options['defaults']['multiple'];
	$is_multiple = ( 'true' === $multiple ) ? 'checked="checked"' : '';
	$type        = ( isset( $registration['sales_type'] ) ) ? $registration['sales_type'] : $options['defaults']['sales_type'];
	if ( ! $type || 'tickets' === $type ) {
		$is_tickets      = ' checked="checked"';
		$is_registration = '';
	} else {
		$is_tickets      = '';
		$is_registration = ' checked="checked"';
	}
	$method = ( isset( $registration['counting_method'] ) ) ? $registration['counting_method'] : $default_model;
	$form   = mt_prices_table( $registration, $method );
	$form  .= "
	<p>
		<label for='reg_expires'>" . __( 'Ticket sales end:', 'my-tickets' ) . "</label> <input type='number' name='reg_expires' id='reg_expires' value='$expiration' step='.25' aria-describedby='reg_expiration' size='7' /> <span id='reg_expiration' aria-live='polite'></span>
	</p>
	<p>
		<input type='checkbox' name='mt_multiple' id='mt_multiple' value='true' $is_multiple /> <label for='mt_multiple'>" . __( 'Allow multiple tickets/ticket type per purchaser', 'my-tickets' ) . '</label>
	</p>';
	$form  .= '
		<div class="ticket-sale-types"><fieldset><legend>' . __( 'Type of Sale', 'my-tickets' ) . "</legend>
		<p>
			<input type='radio' name='mt_sales_type' id='mt_sales_type_tickets' value='tickets' $is_tickets /> <label for='mt_sales_type_tickets'>" . __( 'Ticket Sales', 'my-tickets' ) . "</label><br />
			<input type='radio' name='mt_sales_type' id='mt_sales_type_registration' value='registration' $is_registration /> <label for='mt_sales_type_registration'>" . __( 'Event Registration', 'my-tickets' ) . '</label>
		</p>
		</fieldset>
		<fieldset><legend>' . __( 'Ticket Counting Method', 'my-tickets' ) . "</legend>
			<p>
				<input type='radio' name='mt_counting_method' id='mt_counting_method_discrete' value='discrete' " . checked( $method, 'discrete', false ) . " /> <label for='mt_counting_method_discrete'>" . __( 'Seating Section - (Section A, Section B, etc.)', 'my-tickets' ) . "</label><br />
				<input type='radio' name='mt_counting_method' id='mt_counting_method_continuous' value='continuous' " . checked( $method, 'continuous', false ) . " /> <label for='mt_counting_method_continuous'>" . __( 'Audience Type - (Adult, Child, Senior)', 'my-tickets' ) . "</label><br />
				<input type='radio' name='mt_counting_method' id='mt_counting_method_event' value='event' " . checked( $method, 'event', false ) . " /> <label for='mt_counting_method_event'>" . __( 'Events (Jan 1st, 2pm)', 'my-tickets' ) . '</label>
			</p>
		</fieldset></div>';
	if ( false !== $description ) {
		$form .= "<p><label for='event_registration'>" . __( 'Registration Information', 'my-tickets' ) . "</label> <textarea name='event_registration' id='event_registration' cols='40' rows='4'/>$description</textarea></p>";
	}
	/**
	 * Show custom fields/content in event creation inside My Calendar. Inserted at the end of the form.
	 *
	 * @hook mt_custom_data_fields
	 *
	 * @param {string} $form HTML Output. Default empty.
	 * @param {array}  $registration Saved event data.
	 * @param {object} $data Event data.
	 *
	 * @return {string}
	 */
	$form .= apply_filters( 'mt_custom_data_fields', '', $registration, $data );

	/**
	 * Filter generated form for event creation inside My Calendar.
	 *
	 * @hook mc_event_registration_form
	 *
	 * @param {string} $form Form HTML.
	 * @param {bool}   $has_data Whether this form has data.
	 * @param {object} $data Event object.
	 * @param {string} $context Public or admin view.
	 * @param {string} $original_form Form output before filter modifications.
	 *
	 * @return {string}
	 */
	return apply_filters( 'mc_event_registration_form', $form, $has_data, $data, $context, $original_form );
}

/**
 * Generates pricing table from registration array; uses defaults if no values passed.
 *
 * @global WP_Screen $current_screen;
 * @param array  $registration array of ticketing and registration data for this event.
 * @param string $counting Ticket model type to display when showing defaults.
 *
 * @return string
 */
function mt_prices_table( $registration = array(), $counting = '' ) {
	if ( empty( $registration ) ) {
		$registration = mt_get_settings( 'defaults' )['continuous'];
	}
	global $current_screen;
	$base           = ( $current_screen ) ? $current_screen->base : false;
	$is_post_screen = ( 'post' === $base || wp_doing_ajax() || 'toplevel_page_my-calendar' === $base ) ? true : false;
	// Compound array in settings, single array in posts.
	$pattern   = ( $is_post_screen ) ? '[]' : "[$counting][]";
	$altpatt   = ( $is_post_screen ) ? '' : "[$counting]";
	$counting  = ( $counting ) ? $counting : $registration['counting_method'];
	$available = '';
	$tickets   = ( isset( $registration['tickets'] ) ) ? $registration['tickets'] : false;
	$label     = ( 'event' === $counting ) ? __( 'Event Date & Time', 'my-tickets' ) : __( 'Label', 'my-tickets' );
	$type      = ( 'event' === $counting ) ? 'hidden' : 'text';
	$return    = "<table class='widefat mt-pricing mt-$counting'>
					<caption>" . __( 'Ticket Prices and Availability', 'my-tickets' ) . "</caption>
					<thead>
						<tr>
							<th scope='col'>" . __( 'Move', 'my-tickets' ) . "</th>
							<th scope='col'>" . $label . "</th>
							<th scope='col'>" . __( 'Price', 'my-tickets' ) . "</th>
							<th scope='col'>" . __( 'Available', 'my-tickets' ) . "</th>
							<th scope='col'>" . __( 'Sold', 'my-tickets' ) . "</th>
							<th scope='col'>" . __( 'Close Sales', 'my-tickets' ) . '</th>
						</tr>
					</thead>
					<tbody>';
	if ( 'discrete' === $counting || 'event' === $counting ) {
		$available_empty = "<input type='text' name='mt_tickets$pattern' id='mt_tickets' value='' size='8' />";
		$total           = '<input type="hidden" name="mt_tickets_total' . $altpatt . '" value="inherit" />';
	} else {
		$disabled        = ( 'general' === $counting ) ? ' disabled="disabled"' : '';
		$notice          = ( 'general' === $counting ) ? ' <em id="ticket-counting-status_' . $counting . '">' . __( 'Ticket counting is disabled for general admission events.', 'my-tickets' ) . '</em>' : '';
		$value           = ( isset( $registration['total'] ) && 'inherit' !== $registration['total'] ) ? $registration['total'] : $tickets;
		$available_empty = "<input type='hidden' name='mt_tickets$pattern' id='mt_tickets_" . $counting . "' value='inherit' />";
		$total           = "<p class='mt-available-tickets'><label for='mt_tickets_total_" . $counting . "'>" . __( 'Total Tickets Available', 'my-tickets' ) . ':</label> <input ' . $disabled . ' type="text" name="mt_tickets_total' . $altpatt . '" id="mt_tickets_total_' . $counting . '" aria-describedby="ticket-counting-status" value="' . esc_attr( $value ) . '" />' . $notice . '</p>';
	}
	$labels_index = array();
	$pricing      = ( isset( $registration['prices'] ) ) ? $registration['prices'] : $registration['pricing']; // array of prices; label => cost/available/sold.
	if ( is_array( $pricing ) ) {
		foreach ( $pricing as $label => $options ) {
			if ( ! isset( $options['label'] ) ) {
				continue;
			}
			if ( 'discrete' === $counting || 'event' === $counting ) {
				$available = "<input type='text' name='mt_tickets$pattern' id='mt_tickets_$counting" . '_' . "$label' value='" . esc_attr( $options['tickets'] ) . "' size='8' />";
			} else {
				$available = "<input type='hidden' name='mt_tickets$pattern' id='mt_tickets_$counting" . '_' . "$label' value='inherit' />";
			}
			if ( $label ) {
				$date        = ( isset( $options['label'] ) && '' !== $options['label'] ) ? $options['label'] : '';
				$args        = array(
					'id'    => "mt_label_$label",
					'name'  => 'mt_label' . $pattern,
					'value' => ( $date ) ? gmdate( 'Y-m-d', strtotime( $date ) ) : '',
				);
				$time        = ( $date ) ? gmdate( 'H:i:00', strtotime( $date ) ) : '';
				$label_field = ( 'event' === $counting ) ? '<div class="mt-date-time-picker">' . mt_datepicker_html( $args ) . '<label for="mt_label_picker_time_' . $label . '" class="screen-reader-text">' . __( 'Time', 'my-tickets' ) . '</label><input type="time" name="mt_label_time' . $pattern . '" id="mt_label_picker_time_' . $label . '" value="' . $time . '"></div>' : '';
				$label_class = ( 'event' === $counting ) ? 'duet-fallback' : '';

				$class       = ( 0 !== (int) $options['sold'] || 'complimentary' === sanitize_title( $options['label'] ) ) ? 'undeletable' : 'deletable';
				$sold        = ( isset( $_GET['mode'] ) && 'copy' === $_GET['mode'] ) ? 0 : $options['sold'];
				$close       = ( isset( $_GET['mode'] ) && 'copy' === $_GET['mode'] ) ? '' : ( isset( $options['close'] ) ? $options['close'] : '' );
				$close_field = ( 'event' === $counting ) ? '--' : '<input type="date" name="mt_close' . $pattern . '" value="' . ( ( $close ) ? gmdate( 'Y-m-d', $close ) : '' ) . '" />';
				$comps       = ( 'complimentary' === sanitize_title( $options['label'] ) ) ? '<br />' . __( 'Note: complimentary tickets can only be added by logged-in administrators.', 'my-tickets' ) : '';
				$return     .= "
				<tr class='$class'>
					<td class='mt-controls'>
						<button type='button' class='button up'><span class='dashicons dashicons-arrow-up-alt'></span><span class='screen-reader-text'>" . __( 'Move Up', 'my-tickets' ) . "</span></button> 
						<button type='button' class='button down'><span class='dashicons dashicons-arrow-down-alt'></span><span class='screen-reader-text'>" . __( 'Move Down', 'my-tickets' ) . "</span></button>
					</td>
					<td>$label_field<input type='$type' class='$label_class' name='mt_label$pattern' id='mt_label_$counting" . '_' . "$label' value='" . esc_attr( stripslashes( strip_tags( $options['label'] ) ) ) . "' />$comps</td>
					<td><input type='number' name='mt_price$pattern' step='0.01' id='mt_price_$counting" . '_' . "$label' value='" . esc_attr( $options['price'] ) . "' size='8' /></td>
					<td>$available</td>
					<td><input type='hidden' name='mt_sold$pattern' value='" . $sold . "' />" . $sold . '</td>
					<td>' . $close_field . '</td>
				</tr>';

				$labels_index[ $label ] = $options['label'];
			}
		}
		$event_id = ( isset( $registration['event_id'] ) ) ? $registration['event_id'] : false;
		mt_index_labels( $labels_index, $event_id );

		$has_comps = false;
		$keys      = array_keys( $pricing );
		if ( in_array( 'complementary', $keys, true ) || in_array( 'complimentary', $keys, true ) ) {
			$has_comps = true;
		}
		if ( ! $has_comps && 'event' !== $counting ) {
			$return .= "
				<tr class='undeletable'>
					<td class='mt-controls'>
						<button type='button' class='button up'><span class='dashicons dashicons-arrow-up-alt'></span><span class='screen-reader-text'>" . __( 'Move Up', 'my-tickets' ) . "</span></button> 
						<button type='button' class='button down'><span class='dashicons dashicons-arrow-down-alt'></span><span class='screen-reader-text'>" . __( 'Move Down', 'my-tickets' ) . "</span></button>
					</td>
					<td><input type='text' readonly name='mt_label$pattern' id='mt_label_$counting" . '_' . "complimentary' value='Complimentary' /><br />" . __( 'Note: complimentary tickets can only be added by logged-in administrators.', 'my-tickets' ) . "</td>
					<td><input type='text' readonly name='mt_price$pattern' id='mt_price_$counting" . '_' . "complimentary' value='0' size='8' /></td>
					<td>$available</td>
					<td></td>
					<td></td>
				</tr>";
		}
	}
	$args            = array(
		'id'    => 'mt_label_new',
		'name'  => 'mt_label' . $pattern,
		'value' => '',
	);
	$new_label_field = ( 'event' === $counting ) ? '<div class="mt-date-time-picker">' . mt_datepicker_html( $args ) . '<label for="mt_label_picker_time_new" class="screen-reader-text">' . __( 'Time', 'my-tickets' ) . '</label><input type="time" name="mt_label_time' . $pattern . '" id="mt_label_picker_time_new"></div>' : '';
	$new_close_field = ( 'event' === $counting ) ? '--' : "<input type='date' name='mt_close$pattern' value='' />";

	$return   .= "
		<tr class='clonedPrice $counting' id='price" . $counting . "1'>
			<td></td>
			<td>$new_label_field<input type='text' class='$label_class' name='mt_label$pattern' id='mt_$counting" . '_' . "label' /></td>
			<td><input type='text' name='mt_price$pattern' id='mt_$counting" . '_' . "price' step='0.01' size='8' /></td>
			<td>$available_empty</td>
			<td></td>
			<td>" . $new_close_field . '</td>
		</tr>';
	$return   .= '</tbody></table>';
	$add_field = __( 'Add a price group', 'my-tickets' );
	$del_field = __( 'Remove last price group', 'my-tickets' );
	$return   .= '
			<p>
				<input type="button" value="' . $add_field . '" class="add-price button ' . $counting . '" data-context="' . $counting . '" />
				<input type="button" value="' . $del_field . '" class="del-price button ' . $counting . '" data-context="' . $counting . '" />
			</p>';

	return $total . $return;
}


/**
 * Create index of labels/stored names
 *
 * @param array $labels array of labels/names.
 * @param int   $event_id Optional event ID.
 */
function mt_index_labels( $labels, $event_id = false ) {
	$index = get_option( 'mt_labels' );
	if ( $event_id ) {
		$index = get_post_meta( $event_id, '_mt_labels', true );
	}
	$index = is_array( $index ) ? $index : array();
	$keys  = array_keys( $index );
	foreach ( $labels as $name => $label ) {
		if ( ! in_array( $name, $keys, true ) ) {
			$index[ $name ] = $label;
		}
	}
	if ( $event_id ) {
		update_post_meta( $event_id, '_mt_labels', $index );
	} else {
		update_option( 'mt_labels', $index );
	}
}

/**
 * Fetch label from stored key
 *
 * @param string $key Key for stored label.
 * @param int    $event_id ID of the event this label is attached to.
 *
 * @return string either key or found label
 */
function mt_get_label( $key, $event_id ) {
	$index = ( $event_id ) ? get_post_meta( $event_id, '_mt_labels', true ) : false;
	if ( ! $index ) {
		$index = get_option( 'mt_labels' );
	}
	if ( isset( $index[ $key ] ) ) {
		$key = $index[ $key ];
	}

	return $key;
}

/**
 * Transform event label times into close dates. Used when ticket groups are dates.
 *
 * @param array $labels Labels array.
 * @param array $times Times array.
 *
 * @return array
 */
function mt_close_times( $labels, $times ) {
	$close = array();
	foreach ( $labels as $key => $label ) {
		$date    = gmdate( 'Y-m-d H:i', strtotime( $label . ' ' . $times[ $key ] ) );
		$close[] = $date;
	}

	return $close;
}

/**
 * Save registration/ticketing info as post meta.
 *
 * @param int    $post_id Post ID.
 * @param array  $post $_POST data.
 * @param object $data My Calendar event object.
 * @param int    $event_id Event ID.
 */
function mt_save_registration_data( $post_id, $post, $data = array(), $event_id = false ) {
	$sell = ( isset( $post['mt-trigger'] ) ) ? 'true' : 'false';
	if ( isset( $post['mt_label'] ) && 'true' === $sell ) {
		$reg_data        = get_post_meta( $post_id, '_mt_registration_options', true );
		$event_begin     = ( isset( $post['event_begin'] ) ) ? $post['event_begin'] : '';
		$event_begin     = ( is_array( $event_begin ) ) ? $event_begin[0] : $event_begin;
		$labels          = ( isset( $post['mt_label'] ) ) ? $post['mt_label'] : array();
		$times           = ( isset( $post['mt_label_time'] ) ) ? $post['mt_label_time'] : array();
		$prices          = ( isset( $post['mt_price'] ) ) ? $post['mt_price'] : array();
		$sold            = ( isset( $post['mt_sold'] ) ) ? $post['mt_sold'] : array();
		$close           = ( isset( $post['mt_close'] ) ) ? $post['mt_close'] : mt_close_times( $labels, $times );
		$hide            = ( isset( $post['mt_hide_registration_form'] ) ) ? 'true' : 'false';
		$availability    = ( isset( $post['mt_tickets'] ) ) ? $post['mt_tickets'] : 'inherit';
		$total_tickets   = ( isset( $post['mt_tickets_total'] ) ) ? $post['mt_tickets_total'] : 'inherit';
		$pricing_array   = mt_setup_pricing( $labels, $prices, $availability, $close, $sold, $times );
		$reg_expires     = ( isset( $post['reg_expires'] ) ) ? (int) $post['reg_expires'] : 0;
		$multiple        = ( isset( $post['mt_multiple'] ) ) ? 'true' : 'false';
		$mt_sales_type   = ( isset( $post['mt_sales_type'] ) ) ? $post['mt_sales_type'] : 'tickets';
		$counting_method = ( isset( $post['mt_counting_method'] ) ) ? $post['mt_counting_method'] : 'discrete';
		$counting_method = ( isset( $post['mt_general'] ) && 'general' === $post['mt_general'] ) ? 'general' : $counting_method;
		$notes           = ( isset( $post['mt_event_notes'] ) ) ? $post['mt_event_notes'] : '';
		$clear           = ( isset( $post['mt-delete-data'] ) ) ? true : false;
		if ( $clear ) {
			$pricing_array = mt_setup_pricing( $labels, $prices, $availability, $close, array(), $times );
			$tickets       = get_post_meta( $post_id, '_ticket' );
			foreach ( $tickets as $ticket_id ) {
				// Delete individual ticket IDs.
				delete_post_meta( $post_id, '_' . $ticket_id );
				// Delete sequential ids.
				delete_post_meta( $post_id, '_' . $ticket_id . '_seq_id' );
			}
			// Delete record of tickets.
			delete_post_meta( $post_id, '_ticket' );
			// Delete base enumerator for sequential ticket IDs.
			delete_post_meta( $post_id, '_sequential_base' );
			// Delete purchase records used for reporting.
			delete_post_meta( $post_id, '_purchase' );
			// Delete sold out flag.
			delete_post_meta( $post_id, '_mt_event_soldout' );
			// Delete event expiration notice.
			delete_post_meta( $post_id, '_mt_event_expired' );
			// retain payments (as they might apply to multiple events) but remove tickets from them.
		}
		$registration_options = array(
			'reg_expires'     => $reg_expires,
			'sales_type'      => $mt_sales_type,
			'counting_method' => $counting_method,
			'prices'          => $pricing_array,
			'total'           => $total_tickets,
			'multiple'        => $multiple,
		);
		$updated_expire       = ( isset( $reg_data['reg_expires'] ) && $reg_data['reg_expires'] !== $reg_expires ) ? true : false;
		if ( mt_date_comp( mt_date( 'Y-m-d H:i:s', mt_current_time() ), $event_begin ) || $updated_expire ) {
			// if the date changes, and is now in the future, re-open ticketing.
			// also if the amount of time before closure changes.
			delete_post_meta( $post_id, '_mt_event_expired' );
		}
		/**
		 * Filter event registration options for an event before saving.
		 *
		 * @hook mt_registration_options
		 *
		 * @param {array}  $registration_options Saved options for this event.
		 * @param {array}  $post POST data passed to function.
		 * @param {object} $data Event object.
		 *
		 * @return {array}
		 */
		$registration_options = apply_filters( 'mt_registration_options', $registration_options, $post, $data );
		update_post_meta( $post_id, '_mt_registration_options', $registration_options );
		update_post_meta( $post_id, '_mt_hide_registration_form', $hide );
		update_post_meta( $post_id, '_mt_sell_tickets', $sell );
		update_post_meta( $post_id, '_mt_event_notes', $notes );
	} elseif ( 'false' === $sell ) {
		delete_post_meta( $post_id, '_mt_sell_tickets' );
	}
}

/**
 * Generates pricing array from POST data
 *
 * @param array $labels Price labels.
 * @param array $prices Prices.
 * @param array $availability Availability for tickets.
 * @param array $close Dates when specific ticket types could go off sale.
 * @param array $sold array - empty when event is created.
 * @param array $times Array of event times when counting as individual events.
 *
 * @return array ticket data
 */
function mt_setup_pricing( $labels, $prices, $availability, $close, $sold = array(), $times = array() ) {
	$return = array();
	if ( is_array( $labels ) ) {
		$i = 0;
		foreach ( $labels as $key => $label ) {
			if ( $label ) {
				$label          = ( isset( $times[ $key ] ) ) ? $label . ' ' . $times[ $key ] : $label;
				$internal_label = sanitize_title( $label );
				$price          = ( is_numeric( $prices[ $i ] ) ) ? $prices[ $i ] : (int) $prices[ $i ];
				if ( isset( $availability[ $i ] ) && '' !== $availability[ $i ] ) {
					$tickets = ( is_numeric( $availability[ $i ] ) ) ? $availability[ $i ] : (int) $availability[ $i ];
				} else {
					$tickets = '';
				}
				$sold_tickets              = ( isset( $sold[ $i ] ) ) ? (int) $sold[ $i ] : '';
				$closing                   = ( isset( $close[ $i ] ) ) ? strtotime( $close[ $i ] ) : '';
				$return[ $internal_label ] = array(
					'label'   => esc_html( $label ),
					'price'   => $price,
					'tickets' => $tickets,
					'sold'    => $sold_tickets,
					'close'   => $closing,
				);
			}
			++$i;
		}
	}

	return $return;
}
