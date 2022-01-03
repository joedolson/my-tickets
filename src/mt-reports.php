<?php
/**
 * Generate and display reports.
 *
 * @category Core
 * @package  My Tickets
 * @author   Joe Dolson
 * @license  GPLv2 or later
 * @link     https://www.joedolson.com/my-tickets/
 */

/* Payments Page; display payment history */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
} // Exit if accessed directly.

/**
 * Display reports screen.
 */
function mt_reports_page() {
	?>
	<div class='wrap my-tickets'>
	<h1><?php _e( 'My Tickets Reporting', 'my-tickets' ); ?></h1>
	<div class="postbox-container jcd-wide">
		<div class="metabox-holder">
			<div class="ui-sortable meta-box-sortables">
				<div class="postbox">
					<h2 class="hndle"><?php _e( 'Reports on Ticket Sales and Registrations', 'my-tickets' ); ?></h2>

					<div class="inside">
						<?php
						echo wp_kses( '<p><button class="show-button">' . __( 'Show Hidden Columns', 'my-tickets' ) . '</button></p>', mt_kses_elements() );
						if ( isset( $_POST['event_id'] ) && is_numeric( $_POST['event_id'] ) ) {
							if ( ! ( '' === strip_tags( $_POST['mt_subject'] ) || '' === strip_tags( $_POST['mt_body'] ) ) ) {
								mt_mass_email();
							}
						}
						if ( ! isset( $_GET['event_id'] ) ) {
							mt_generate_report_by_time();
						} else {
							if ( isset( $_GET['mt-event-report'] ) && 'tickets' === $_GET['mt-event-report'] ) {
								mt_generate_tickets_by_event();
							} else {
								mt_generate_report_by_event();
							}
							$event_id         = (int) $_GET['event_id'];
							$report_type      = ( isset( $_GET['mt-event-report'] ) && 'tickets' === $_GET['mt-event-report'] ) ? 'tickets' : 'purchases';
							$print_report_url = admin_url( 'admin.php?page=mt-reports&event_id=' . $event_id . '&mt-event-report=' . $report_type . '&format=view&mt_print=true' );
							$back_url         = admin_url( apply_filters( 'mt_printable_report_back', 'admin.php?page=mt-reports' ) );
							echo wp_kses_post( '<p><a class="button print-button" href="' . esc_url( $print_report_url ) . '">' . __( 'Print this report', 'my-tickets' ) . '</a><a class="mt-back button" href="' . esc_url( $back_url ) . '">' . __( 'Return to My Tickets Reports', 'my-tickets' ) . '</a></p>' );
						}
						?>
						<div class="mt-report-selector">
							<?php mt_choose_report_by_date(); ?>
							<?php mt_choose_report_by_event(); ?>
						</div>
						<div class='mt-email-purchasers'>
							<?php mt_email_purchasers(); ?>
						</div>
					</div>
				</div>
			</div>
		</div>
	</div>
	<?php mt_show_support_box(); ?>
	</div>
	<?php
}

/**
 * Generate a report of tickets on a single event.
 *
 * @param bool|int $event_id Event ID.
 * @param bool     $return Return or echo.
 *
 * @return void|string
 */
function mt_generate_tickets_by_event( $event_id = false, $return = false ) {
	if ( current_user_can( 'mt-view-reports' ) || current_user_can( 'manage_options' ) ) {
		$event_id = ( isset( $_GET['event_id'] ) ) ? (int) $_GET['event_id'] : $event_id;
		if ( $event_id ) {
			$title         = get_the_title( $event_id );
			$output        = '';
			$data          = mt_get_tickets( $event_id );
			$report        = $data['html'];
			$total_tickets = count( $report );
			// Translators: name of event.
			$table_top    = "<table class='widefat'><caption>" . sprintf( __( 'Tickets Purchased for &ldquo;%s&rdquo;', 'my-tickets' ), $title ) . "</caption>
						<thead>
							<tr>
								<th scope='col' class='mt-id' id='mt-id'>" . __( 'Ticket ID', 'my-tickets' ) . "</th>
								<th scope='col' class='mt-seqid' id='mt-seqid'>" . __( 'Sequential ID', 'my-tickets' ) . "</th>
								<th scope='col' class='mt-type' id='mt-type'>" . __( 'Ticket Type', 'my-tickets' ) . "</th>
								<th scope='col' class='mt-purchaser' id='mt-purchaser'>" . __( 'Purchaser', 'my-tickets' ) . "</th>
								<th scope='col' class='mt-post' id='mt-post'>" . __( 'Purchase ID', 'my-tickets' ) . "</th>
								<th scope='col' class='mt-price' id='mt-price'>" . __( 'Price', 'my-tickets' ) . "</th>
								<th scope='col' class='mt-status' id='mt-status'>" . __( 'Status', 'my-tickets' ) . "</th>
								<th scope='col' class='mt-used' id='mt-used'>" . __( 'Used', 'my-tickets' ) . '</th>
							</tr>
						</thead>
						<tbody>';
			$table_bottom = '</tbody></table>';

			foreach ( $report as $row ) {
				$table_top .= $row;
			}
			$table   = $table_top . $table_bottom;
			$output .= $table;
			if ( $return ) {
				// Translators: Number of tickets sold.
				return "<p class='totals'>" . sprintf( __( '%1$s tickets sold.', 'my-tickets' ), "<strong>$total_tickets</strong>" ) . '</p>' . $output;
			} else {
				// Translators: Number of tickets sold.
				echo wp_kses_post( "<p class='totals'>" . sprintf( __( '%1$s tickets sold.', 'my-tickets' ), "<strong>$total_tickets</strong>" ) . '</p>' . $output );
			}
		}
	} else {
		if ( $return ) {
			return false;
		} else {
			echo wp_kses_post( "<div class='updated error'><p>" . __( 'You do not have sufficient permissions to view ticketing reports.', 'my-tickets' ) . '</p></div>' );
		}
	}
}


/**
 * Generate a report of payments on a single event.
 *
 * @param bool|int $event_id Event ID.
 * @param bool     $return Return or echo.
 *
 * @return void|string
 */
function mt_generate_report_by_event( $event_id = false, $return = false ) {
	if ( current_user_can( 'mt-view-reports' ) || current_user_can( 'manage_options' ) ) {
		$event_id = ( isset( $_GET['event_id'] ) ) ? (int) $_GET['event_id'] : $event_id;
		if ( $event_id ) {
			$title        = get_the_title( $event_id );
			$tabs         = '';
			$out          = '';
			$options      = ( isset( $_GET['options'] ) ) ? map_deep( $_GET['options'], 'sanitize_text_field' ) : array(
				'type'           => 'html',
				'output'         => 'payments',
				'include_failed' => true,
			);
			$status_types = array(
				'completed'    => __( 'Completed (%Completed)', 'my-tickets' ),
				// Translators: percent signs, *not* placeholders.
				'failed'       => __( 'Failed (%Failed)', 'my-tickets' ),
				'refunded'     => __( 'Refunded (%Refunded)', 'my-tickets' ),
				'pending'      => __( 'Pending (%Pending)', 'my-tickets' ),
				'reserved'     => __( 'Reserved (%Reserved)', 'my-tickets' ),
				'turned-back'  => __( 'Turned Back (%Turned Back)', 'my-tickets' ),
				'waiting-list' => __( 'Waiting List (%Waiting List)', 'my-tickets' ),
			);
			foreach ( $status_types as $type => $status_type ) {
				$tabs .= "<li><a href='#mt_$type'>$status_type</a></li>";
			}
			$output = "
				<div class='mt-tabs'>
					<ul class='tabs'>
						$tabs
					</ul>";

			$data           = mt_purchases( $event_id, $options );
			$report         = $data['report']['html'];
			$total_tickets  = $data['tickets'];
			$total_sales    = count( $data['report']['html']['Completed'] ) + count( $data['report']['html']['Pending'] );
			$total_income   = $data['income'];
			$custom_fields  = apply_filters( 'mt_custom_fields', array(), 'reports' );
			$custom_headers = '';
			foreach ( $custom_fields as $name => $field ) {
				$custom_headers .= "<th scope='col' class='mt_" . sanitize_title( $name ) . "'>" . $field['title'] . "</th>\n";
			}
			$table_top    = "<table class='widefat'><caption>%caption%</caption>
						<thead>
							<tr>
								<th scope='col' class='mt-purchaser'>" . __( 'Purchaser', 'my-tickets' ) . "</th>
								<th scope='col' class='mt-type'>" . __( 'Type', 'my-tickets' ) . "</th>
								<th scope='col' class='mt-tickets'>" . __( 'Tickets', 'my-tickets' ) . "</th>
								<th scope='col' class='mt-owed'>" . __( 'Owed', 'my-tickets' ) . "</th>
								<th scope='col' class='mt-paid'>" . __( 'Paid', 'my-tickets' ) . "</th>
								<th scope='col' id='mt_method' class='mt_method'>" . __( 'Ticket Method', 'my-tickets' ) . "</th>
								<th scope='col' class='mt-date'>" . __( 'Date', 'my-tickets' ) . "</th>
								<th scope='col' class='mt-id'>" . __( 'ID', 'my-tickets' ) . "</th>
								$custom_headers
								<th scope='col' class='mt-notes'>" . __( 'Notes', 'my-tickets' ) . '</th>
							</tr>
						</thead>
						<tbody>';
			$table_bottom = '</tbody></table>';

			foreach ( $report as $status => $rows ) {
				${$status} = '';
				$count     = count( $rows );
				$output    = str_replace( "%$status", $count, $output );
				if ( 0 === $count ) {
					continue;
				}
				foreach ( $rows as $type => $row ) {
					${$status} .= $row;
				}
				$caption       = "$title: <em>$status</em>";
				$use_table_top = str_replace( '%caption%', $caption, $table_top );
				$out          .= "<div class='wptab wp_" . sanitize_title( $status ) . "' id='mt_" . sanitize_title( $status ) . "'>" . $use_table_top . ${$status} . $table_bottom . '</div>';
			}

			$output .= $out . '</div>';
			// Translators: Number of tickets sold, total number of sales completed, number of purchases transacted.
			$total_line  = "<p class='totals'>" . sprintf( __( '%1$s tickets sold in %3$s purchases. Total completed sales: %2$s', 'my-tickets' ), "<strong>$total_tickets</strong>", '<strong>' . apply_filters( 'mt_money_format', $total_income ) . '</strong>', "<strong>$total_sales</strong>" ) . '</p>';
			$custom_line = apply_filters( 'mt_custom_total_line_event', '', $event_id );
			if ( $return ) {
				return  $total_line . $custom_line . $output;
			} else {
				echo wp_kses_post( $total_line . $custom_line . $output );
			}
		}
	} else {
		if ( $return ) {
			return false;
		} else {
			echo wp_kses_post( "<div class='updated error'><p>" . __( 'You do not have sufficient permissions to view sales reports.', 'my-tickets' ) . '</p></div>' );
		}
	}
}

/**
 * Produce selector to choose report by event.
 *
 * @return void
 */
function mt_choose_report_by_event() {
	$selector = mt_select_events();
	$selected = ( isset( $_GET['format'] ) && 'csv' === $_GET['format'] ) ? " selected='selected'" : '';
	$report   = ( isset( $_GET['mt-event-report'] ) ) ? sanitize_text_field( $_GET['mt-event-report'] ) : '';
	$form     = "
			<div class='report-by-event'>
				<h3>" . __( 'Report by Event', 'my-tickets' ) . "</h3>
				<form method='GET' action='" . esc_url( admin_url( 'admin.php?page=mt-reports' ) ) . "'>
					<div>
						<input type='hidden' name='page' value='mt-reports' />
					</div>
					<p>
					<label for='mt_select_event'>" . __( 'Select Event', 'my-tickets' ) . "</label>
					<select name='event_id' id='mt_select_event'>
						$selector
					</select>
					</p>
					<p>
					<label for='mt_select_report_type'>" . __( 'Select Report Type', 'my-tickets' ) . "</label>
					<select name='mt-event-report' id='mt_select_report_type'>
						<option value='tickets'" . selected( $report, 'tickets', false ) . '>' . __( 'List of Tickets', 'my-tickets' ) . "</option>
						<option value='purchases'" . selected( $report, 'purchases', false ) . '>' . __( 'List of Purchases', 'my-tickets' ) . "</option>
					</select>
					</p>
					<p>
					<label for='mt_select_format_event'>" . __( 'Report Format', 'my-tickets' ) . "</label>
					<select name='format' id='mt_select_format_event'>
						<option value='view'>" . __( 'View Report', 'my-tickets' ) . "</option>
						<option value='csv'$selected>" . __( 'Download CSV', 'my-tickets' ) . "</option>
					</select>
					</p>
					<p><input type='submit' name='mt-display-report' class='button-primary' value='" . __( 'Get Report by Event', 'my-tickets' ) . "' /></p>
				</form>
			</div>";
	echo wp_kses( $form, mt_kses_elements() );
}

/**
 * Display selector to choose report by date.
 *
 * @return void
 */
function mt_choose_report_by_date() {
	$selected = ( isset( $_GET['format'] ) && 'csv' === $_GET['format'] ) ? " selected='selected'" : '';
	$start    = ( isset( $_GET['mt_start'] ) ) ? sanitize_text_field( $_GET['mt_start'] ) : mt_date( 'Y-m-d', strtotime( '-1 month' ) );
	$end      = ( isset( $_GET['mt_end'] ) ) ? sanitize_text_field( $_GET['mt_end'] ) : mt_date( 'Y-m-d' );
	$form     = "
			<div class='report-by-date'>
				<h3>" . __( 'Sales Report by Date', 'my-tickets' ) . "</h3>
				<form method='GET' action='" . admin_url( 'admin.php?page=mt-reports' ) . "'>
					<div>
						<input type='hidden' name='page' value='mt-reports' />
					</div>
					<p>
						<label for='mt_start'>" . __( 'Report Start Date', 'my-tickets' ) . "</label>
						<input type='date' name='mt_start' id='mt_start' value='$start' />
					</p>
					<p>
						<label for='mt_end'>" . __( 'Report End Date', 'my-tickets' ) . "</label>
						<input type='date' name='mt_end' id='mt_end' value='$end' />
					</p>
					<p>
						<label for='mt_select_format_date'>" . __( 'Report Format', 'my-tickets' ) . "</label>
						<select name='format' id='mt_select_format_date'>
							<option value='view'>" . __( 'View Report', 'my-tickets' ) . "</option>
							<option value='csv'$selected>" . __( 'Download CSV', 'my-tickets' ) . "</option>
						</select>
					</p>
					<p><input type='submit' name='mt-display-report' class='button-primary' value='" . __( 'Get Report by Date', 'my-tickets' ) . "' /></p>
				</form>
			</div>";
	echo wp_kses( $form, mt_kses_elements() );
}

/**
 * Produce form to choose event for mass emailing purchasers.
 */
function mt_email_purchasers() {
	$selector = mt_select_events();
	$event_id = ( isset( $_GET['event_id'] ) ) ? (int) $_GET['event_id'] : false;
	$body     = ( isset( $_POST['mt_body'] ) ) ? sanitize_textarea_field( $_POST['mt_body'] ) : '';
	$subject  = ( isset( $_POST['mt_subject'] ) ) ? sanitize_text_field( $_POST['mt_subject'] ) : '';
	$email    = get_post_meta( $event_id, '_mass_email' );
	if ( ! empty( $email ) ) {
		if ( isset( $_GET['message'] ) ) {
			$strip = intval( $_GET['message'] );
			for ( $i = 0; $i < $strip; $i++ ) {
				$removed = ( is_array( $email ) ) ? array_pop( $email ) : array();
			}
		}
		if ( ! empty( $email ) ) {
			$last_email = end( $email );
			$body       = $last_email['body'];
			$subject    = $last_email['subject'];
		}
	}
	$form = '
		<h3>' . __( 'Email Purchasers of Tickets by Event', 'my-tickets' ) . "</h3>
		<form method='POST' action='" . admin_url( 'admin.php?page=mt-reports' ) . "'>
			<p>
			<label for='mt_select_event_for_email'>" . __( 'Select Event', 'my-tickets' ) . "</label>
			<select name='event_id' id='mt_select_event_for_email'>
				$selector
			</select>
			</p>
			<p>
			<label for='mt_subject'>" . __( 'Email Subject', 'my-tickets' ) . "</label><br />
			<input type='text' name='mt_subject' id='mt_subject' value='" . esc_attr( $subject ) . "' size='40' />
			</p>
			<p>
			<label for='mt_body' id='body_label'>" . __( 'Email Body', 'my-tickets' ) . "</label><br />
			<textarea name='mt_body' id='mt_body' cols='60' rows='12' aria-labelledby='body_label body_description'>" . esc_textarea( stripslashes( $body ) ) . "</textarea><br />
			<span id='body_description'>" . __( 'Use <code>{name}</code> to insert the recipient\'s name', 'my-tickets' ) . "</span>
			</p>
			<p><input type='checkbox' name='mt-test-email' value='test' id='mt_test_email'> <label for='mt_test_email'>" . __( 'Send test email', 'my-tickets' ) . "</label></p>
			<p><input type='submit' name='mt-email-purchasers' class='button-primary' value='" . __( 'Send Email', 'my-tickets' ) . "' /></p>
		</form>";

	echo wp_kses( $form, mt_kses_elements() );
}

/**
 * Select events with event sales data. (If no sales, not returned.)
 *
 * @return string
 */
function mt_select_events() {
	// fetch posts with meta data for event sales.
	$settings = array_merge( mt_default_settings(), get_option( 'mt_settings', array() ) );
	// add time query to this query after timestamp field has been in place for a few months.
	// only show limit of 100 events.
	$args    =
		array(
			'post_type'      => $settings['mt_post_types'],
			'posts_per_page' => apply_filters( 'mt_select_events_count', 100 ),
			'post_status'    => array( 'publish', 'draft', 'private' ),
			'meta_query'     => array(
				'relation' => 'AND',
				'queries'  => array(
					'key'     => '_ticket',
					'compare' => 'EXISTS',
				),
			),
		);
	$args    = apply_filters( 'mt_select_events_args', $args );
	$query   = new WP_Query( $args );
	$posts   = $query->posts;
	$options = '<option value="false"> --- </option>';
	if ( isset( $_GET['event_id'] ) ) {
		$event_id = absint( $_GET['event_id'] );
		$post     = get_post( $event_id );
		if ( $post ) {
			$posts[] = $post;
		}
	}
	foreach ( $posts as $post ) {
		$tickets    = get_post_meta( $post->ID, '_ticket' );
		$count      = count( $tickets );
		$show_event = false;
		$selected   = ( isset( $_GET['event_id'] ) && absint( $_GET['event_id'] ) === $post->ID ) ? ' selected="selected"' : '';
		$event_data = get_post_meta( $post->ID, '_mc_event_data', true );
		if ( is_array( $event_data ) ) {
			$event_date   = strtotime( $event_data['event_begin'] );
			$display_date = date_i18n( get_option( 'date_format' ), $event_date );
			// if this event happened more than 2 years ago, don't show in list *unless* it's the currently selected report.
			$report_age_limit = apply_filters( 'mt_reports_age_limit', mt_current_time() - ( YEAR_IN_SECONDS * 2 ) );
			if ( isset( $event_data['general_admission'] ) && 'on' === $event_data['general_admission'] ) {
				$show_event = true;
			}
			if ( $event_date > $report_age_limit || ' selected="selected"' === $selected || $show_event ) {
				$title    = apply_filters( 'mt_the_title', $post->post_title, $post );
				$options .= "<option value='$post->ID'$selected>$title ($count); $display_date</option>\n";
			}
		}
	}

	return $options;
}

/**
 * Return array of formatted purchase data for use in reports by event ID.
 *
 * @param int   $event_id Event ID.
 * @param array $options Report options.
 *
 * @return array
 */
function mt_purchases( $event_id, $options = array( 'include_failed' => false ) ) {
	if ( false === $event_id ) {
		exit;
	}
	$query         = get_post_meta( $event_id, '_purchase' );
	$report        = array(
		'html' => array(
			'Completed'    => array(),
			'Pending'      => array(),
			'Refunded'     => array(),
			'Failed'       => array(),
			'Reserved'     => array(),
			'Turned Back'  => array(),
			'Waiting List' => array(),
		),
		'csv'  => array(
			'Completed'    => array(),
			'Pending'      => array(),
			'Refunded'     => array(),
			'Failed'       => array(),
			'Reserved'     => array(),
			'Turned Back'  => array(),
			'Waiting List' => array(),
		),
	);
	$total_tickets = 0;
	$total_income  = 0;
	$alternate     = 'even';
	foreach ( $query as $payment ) {
		foreach ( $payment as $purchase_id => $details ) {
			if ( 'publish' !== get_post_status( $purchase_id ) ) {
				continue;
			}
			$status      = get_post_meta( $purchase_id, '_is_paid', true );
			$ticket_type = get_post_meta( $purchase_id, '_ticketing_method', true );
			$notes       = esc_html( get_post_meta( $purchase_id, '_notes', true ) );
			if ( false === $options['include_failed'] && ( 'Failed' === $status || 'Refunded' === $status || 'Turned Back' === $status ) ) {
				continue;
			}
			$types        = '';
			$ticket_count = 0;
			$subtotal     = 0;
			// get total # tickets on purchase.
			// get total # tickets on purchase.
			// get count of tickets for *this* event on purchase.
			// get total paid.
			// get total price to get owed (on purchase).
			foreach ( $details as $type => $tickets ) {
				$count = isset( $details[ $type ]['count'] ) ? $details[ $type ]['count'] : 0;
				// THIS results in only getting the details for one type listed; need all types for this to be valid.
				if ( $count > 0 ) {
					$purchaser  = get_the_title( $purchase_id );
					$first_name = get_post_meta( $purchase_id, '_first_name', true );
					$last_name  = get_post_meta( $purchase_id, '_last_name', true );
					$email      = get_post_meta( $purchase_id, '_email', true );
					if ( ! $first_name || ! $last_name ) {
						$name       = explode( ' ', $purchaser );
						$first_name = $name[0];
						$last_name  = end( $name );
					}
					$date        = get_the_time( 'Y-m-d', $purchase_id );
					$time        = get_the_time( get_option( 'time_format' ), $purchase_id );
					$transaction = get_post_meta( $purchase_id, '_transaction_data', true );
					$address     = ( isset( $transaction['shipping'] ) ) ? $transaction['shipping'] : false;
					$phone       = get_post_meta( $purchase_id, '_phone', true );
					$fee         = ( isset( $transaction['fee'] ) ) ? $transaction['fee'] : false;
					$street      = ( isset( $address['street'] ) ) ? $address['street'] : '';
					$street2     = ( isset( $address['street2'] ) ) ? $address['street2'] : '';
					$city        = ( isset( $address['city'] ) ) ? $address['city'] : '';
					$state       = ( isset( $address['state'] ) ) ? $address['state'] : '';
					$code        = ( isset( $address['code'] ) ) ? $address['code'] : '';
					$country     = ( isset( $address['country'] ) ) ? $address['country'] : '';
					$datetime    = "$date $time";
					$price       = $details[ $type ]['price'];
					$label       = mt_get_label( $type );
					$types      .= ( '' !== $types ) ? PHP_EOL . $label . ': ' . $count : $label . ':' . $count;
					$this_total  = $count * $price;
					$subtotal    = $subtotal + ( $this_total );
					// "sold" tickets are only in reports as those completed.
					if ( 'Completed' === $status ) {
						$total_income  = $total_income + $this_total;
						$total_tickets = $total_tickets + $count;
						$paid          = $subtotal;
					} else {
						$paid = 0;
					}
					// if $ticket_count == 0, don't show in options.
					$ticket_count  = $ticket_count + $count;
					$class         = esc_attr( strtolower( $ticket_type ) );
					$custom_fields = apply_filters( 'mt_custom_fields', array(), 'reports' );
					$custom_cells  = '';
					$custom_csv    = '';
					foreach ( $custom_fields as $name => $field ) {
						$value   = get_post_meta( $purchase_id, $name );
						$cstring = '';
						foreach ( $value as $v ) {
							if ( is_array( $v ) ) {
								if ( absint( $v['event_id'] ) === absint( $_GET['event_id'] ) ) {
									$keys = array_keys( $v );
									foreach ( $keys as $val ) {
										if ( 'event_id' !== $val ) {
											$cstring .= ( '' !== $cstring ) ? '; ' : '';
											$cstring .= esc_html( $v[ $val ] );
										}
									}
								}
							} elseif ( ! is_object( $v ) ) {
								$cstring .= $v;
							}
						}
						$value         = apply_filters( 'mt_format_report_field', $cstring, get_post_meta( $purchase_id, $name, true ), $purchase_id, $name );
						$custom_cells .= "<td class='mt_" . sanitize_title( $name ) . "'>$value</td>\n";
						$custom_csv   .= ",\"$value\"";
					}
				}
			}

			if ( $ticket_count > 0 ) {
				$owed      = $subtotal - $paid;
				$alternate = ( 'alternate' === $alternate ) ? 'even' : 'alternate';
				$row       = "<tr class='$alternate'>
								<th scope='row' class='mt-purchaser'>$last_name, $first_name</th>
								<td class='mt-type'>" . wpautop( $types ) . "</td>
								<td class='mt-tickets'>$ticket_count</td>
								<td class='mt-price'>" . apply_filters( 'mt_money_format', $owed ) . "</td>
								<td class='mt-paid'>" . apply_filters( 'mt_money_format', $paid ) . "</td>
								<td class='mt_ticket_type'><span class='mt $class'>$ticket_type</span></td>
								<td class='mt-date'>$datetime</td>
								<td class='mt-id'><a href='" . esc_url( get_edit_post_link( $purchase_id ) ) . "'>$purchase_id</a></td>
								$custom_cells
								<td class='mt-notes'>$notes</td>                                    
						  </tr>";
				// add split field to csv headers.
				$types                       = str_replace( PHP_EOL, ', ', $types );
				$csv                         = "\"$last_name\",\"$first_name\",\"$email\",\"$types\",\"$ticket_count\",\"$owed\",\"$paid\",\"$fee\",\"$ticket_type\",\"$date\",\"$time\",\"$purchase_id\",\"$phone\",\"$street\",\"$street2\",\"$city\",\"$state\",\"$code\",\"$country\"$custom_csv" . PHP_EOL;
				$report['html'][ $status ][] = $row;
				$report['csv'][ $status ][]  = $csv;
			}
		}
	}

	return array(
		'report'  => $report,
		'income'  => $total_income,
		'tickets' => $total_tickets,
	);
}

/**
 * Function to produce a list of tickets for a given event.
 *
 * @param int $event_id Event ID.
 *
 * @return array
 */
function mt_get_tickets( $event_id ) {
	$query     = get_post_meta( $event_id, '_ticket' );
	$report    = array(
		'html' => array(),
		'csv'  => array(),
	);
	$options   = get_option( 'mt_settings', array() );
	$alternate = 'even';
	foreach ( $query as $ticket_id ) {
		$ticket       = get_post_meta( $event_id, '_' . $ticket_id, true );
		$ticket_url   = add_query_arg( 'ticket_id', $ticket_id, get_permalink( $options['mt_tickets_page'] ) );
		$purchase_id  = $ticket['purchase_id'];
		$type         = $ticket['type'];
		$label        = mt_get_label( $type );
		$price        = $ticket['price'];
		$purchaser    = get_the_title( $purchase_id );
		$status       = get_post_meta( $purchase_id, '_is_paid', true );
		$used_tickets = get_post_meta( $purchase_id, '_tickets_used' );
		$first_name   = get_post_meta( $purchase_id, '_first_name', true );
		$last_name    = get_post_meta( $purchase_id, '_last_name', true );
		$seq_id       = mt_get_sequential_id( $ticket_id );
		$used         = ( in_array( $ticket_id, $used_tickets, true ) ) ? '<span class="dashicons dashicons-tickets-alt" aria-hidden="true"></span> ' . __( 'Used', 'my-tickets' ) : '--';
		if ( ! $first_name || ! $last_name ) {
			$name       = explode( ' ', $purchaser );
			$first_name = $name[0];
			$last_name  = end( $name );
		}
		$alternate = ( 'alternate' === $alternate ) ? 'even' : 'alternate';
		$row       = "
		<tr class='$alternate'>
			<th scope='row' class='mt-id' id='mt-id'><a href='$ticket_url'>$ticket_id</a></th>
			<td class='mt-seqid' id='mt-seqid'>$seq_id</th>
			<td class='mt-type' id='mt-type'>$label</td>
			<td class='mt-purchaser' id='mt-purchaser'>$purchaser</td>
			<td class='mt-post' id='mt-post'><a href='" . get_edit_post_link( $purchase_id ) . "'>$purchase_id</a></td>
			<td class='mt-price' id='mt-price'>" . apply_filters( 'mt_money_format', $price ) . "</td>
			<td class='mt-status' id='mt-status'>$status</td>
			<td class='mt-used' id='mt-used'>$used</td>
		</tr>";
		// add split field to csv headers.
		$csv              = "\"$ticket_id\",\"$seq_id\",\"$last_name\",\"$first_name\",\"$type\",\"$purchase_id\",\"$price\",\"$status\",\"$used\"" . PHP_EOL;
		$report['html'][] = $row;
		$report['csv'][]  = $csv;
	}

	return $report;
}

add_action( 'admin_init', 'mt_download_csv_event' );
/**
 * Download report of event data as CSV
 */
function mt_download_csv_event() {
	if (
		isset( $_GET['format'] ) && 'csv' === $_GET['format'] &&
		isset( $_GET['page'] ) && 'mt-reports' === $_GET['page'] &&
		isset( $_GET['event_id'] ) &&
		isset( $_GET['mt-event-report'] ) && 'purchases' === $_GET['mt-event-report']
	) {
		$event_id        = intval( $_GET['event_id'] );
		$title           = get_the_title( $event_id );
		$purchases       = mt_purchases( $event_id );
		$report          = $purchases['report']['csv'];
		$custom_headings = '';
		$custom_fields   = apply_filters( 'mt_custom_fields', array(), 'reports' );
		foreach ( $custom_fields as $name => $field ) {
			$custom_headings .= ",\"$name\"";
		}
		$csv = __( 'Last Name', 'my-tickets' ) . ',' . __( 'First Name', 'my-tickets' ) . ',' . __( 'Email', 'my-tickets' ) . ',' . __( 'Ticket Type', 'my-tickets' ) . ',' . __( 'Purchased', 'my-tickets' ) . ',' . __( 'Price', 'my-tickets' ) . ',' . __( 'Paid', 'my-tickets' ) . ',' . __( 'Fees', 'my-tickets' ) . ',' . __( 'Ticket Method', 'my-tickets' ) . ',' . __( 'Date', 'my-tickets' ) . ',' . __( 'Time', 'my-tickets' ) . ',' . __( 'Purchase ID', 'my-tickets' ) . ',' . __( 'Phone', 'my-tickets' ) . ',' . __( 'Street', 'my-tickets' ) . ',' . __( 'Street (2)', 'my-tickets' ) . ',' . __( 'City', 'my-tickets' ) . ',' . __( 'State', 'my-tickets' ) . ',' . __( 'Postal Code', 'my-tickets' ) . ',' . __( 'Country', 'my-tickets' ) . $custom_headings . PHP_EOL;
		foreach ( $report as $status => $rows ) {
			foreach ( $rows as $type => $row ) {
				$csv .= $row;
			}
		}
		$title = sanitize_title( $title ) . '-' . mt_date( 'Y-m-d' );
		header( 'Content-Type: application/csv' );
		header( "Content-Disposition: attachment; filename=$title.csv" );
		header( 'Pragma: no-cache' );
		echo wp_kses_post( $csv );
		exit;
	}
}

add_action( 'admin_init', 'mt_download_csv_tickets' );
/**
 * Download report of ticket data for an event as CSV
 */
function mt_download_csv_tickets() {
	if (
		isset( $_GET['format'] ) && 'csv' === $_GET['format'] &&
		isset( $_GET['page'] ) && 'mt-reports' === $_GET['page'] &&
		isset( $_GET['event_id'] ) &&
		isset( $_GET['mt-event-report'] ) && 'tickets' === $_GET['mt-event-report']
	) {
		$event_id = intval( $_GET['event_id'] );
		$title    = get_the_title( $event_id ) . ' tickets';
		$tickets  = mt_get_tickets( $event_id );
		$report   = $tickets['csv'];
		$csv      = __( 'Ticket ID', 'my-tickets' ) . ',' . __( 'Sequential ID', 'my-tickets' ) . ',' . __( 'Last Name', 'my-tickets' ) . ',' . __( 'First Name', 'my-tickets' ) . ',' . __( 'Ticket Type', 'my-tickets' ) . ',' . __( 'Purchase ID', 'my-tickets' ) . ',' . __( 'Price', 'my-tickets' ) . ',' . __( 'Used', 'my-tickets' ) . PHP_EOL;
		foreach ( $report as $row ) {
			$csv .= "$row";
		}
		$title = sanitize_title( $title ) . '-' . mt_date( 'Y-m-d' );
		header( 'Content-Type: application/csv' );
		header( "Content-Disposition: attachment; filename=$title.csv" );
		header( 'Pragma: no-cache' );
		echo wp_kses_post( $csv );
		exit;
	}
}


add_action( 'admin_init', 'mt_download_csv_time' );
/**
 * Download report by sales period as CSV.
 */
function mt_download_csv_time() {
	$output = '';
	if ( isset( $_GET['format'] ) && 'csv' === $_GET['format'] && isset( $_GET['page'] ) && 'mt-reports' === $_GET['page'] && isset( $_GET['mt_start'] ) ) {
		$report = mt_get_report_data_by_time();
		$csv    = $report['csv'];
		$start  = $report['start'];
		$end    = $report['end'];
		foreach ( $csv as $row ) {
			$output .= "$row";
		}
		$title = sanitize_title( $start . '_' . $end ) . '-' . mt_date( 'Y-m-d' );
		header( 'Content-Type: application/csv' );
		header( "Content-Disposition: attachment; filename=$title.csv" );
		header( 'Pragma: no-cache' );
		echo wp_kses_post( $output );
		exit;
	}
}

/**
 * Get report data for reports by time period.
 *
 * @param string $start Start date.
 * @param string $end End date.
 *
 * @return array
 */
function mt_get_report_by_time( $start, $end ) {
	$posts_per_page = -1;
	if ( mt_date( 'Y-m-d', strtotime( apply_filters( 'mt_default_report_start_date', '-1 week' ) ) ) === $start && mt_date( 'Y-m-d' ) === $end ) {
		$posts_per_page = 50;
	}

	$args  = array(
		'post_type'      => 'mt-payments',
		'post_status'    => array( 'publish' ),
		'date_query'     => array(
			'after'     => $start,
			'before'    => $end,
			'inclusive' => true,
		),
		'posts_per_page' => $posts_per_page,
	);
	$query = new WP_Query( $args );
	$posts = $query->posts;

	return $posts;
}

/**
 * Return data from report by time.
 *
 * @return mixed
 */
function mt_get_report_data_by_time() {
	$start          = ( isset( $_GET['mt_start'] ) ) ? sanitize_text_field( $_GET['mt_start'] ) : mt_date( 'Y-m-d', strtotime( apply_filters( 'mt_default_report_start_date', '-1 week' ) ) );
	$end            = ( isset( $_GET['mt_end'] ) ) ? sanitize_text_field( $_GET['mt_end'] ) : mt_date( 'Y-m-d' );
	$posts          = mt_get_report_by_time( $start, $end );
	$total          = 0;
	$alternate      = 'even';
	$html           = array();
	$csv            = array();
	$custom_fields  = apply_filters( 'mt_custom_fields', array(), 'reports' );
	$custom_headers = '';
	foreach ( $custom_fields as $name => $field ) {
		$custom_headers .= ',"' . $field['title'] . '"';
	}
	$csv[] = '"Last Name","First Name","Email","Ticket Type","Purchase Value","Status","Events","Date"' . $custom_headers . PHP_EOL;
	foreach ( $posts as $post ) {
		$purchaser  = get_the_title( $post->ID );
		$first_name = get_post_meta( $post->ID, '_first_name', true );
		$last_name  = get_post_meta( $post->ID, '_last_name', true );
		if ( ! $first_name && ! $last_name ) {
			$name       = explode( ' ', $purchaser );
			$first_name = $name[0];
			$last_name  = end( $name );
		}
		$value        = floatval( get_post_meta( $post->ID, '_total_paid', true ) );
		$format_value = apply_filters( 'mt_money_format', $value );
		$total        = $total + $value;
		$status       = get_post_meta( $post->ID, '_is_paid', true );
		$email        = get_post_meta( $post->ID, '_email', true );
		$type         = get_post_meta( $post->ID, '_ticketing_method', true );
		$purchased    = get_post_meta( $post->ID, '_purchased' );
		$date         = get_the_time( 'Y-m-d', $post->ID );
		$time         = get_the_time( get_option( 'time_format' ), $post->ID );
		$titles       = array();
		foreach ( $purchased as $purchase ) {
			foreach ( $purchase as $event => $purch ) {
				// If, after iterating over an event's tickets, there are none, don't include.
				$subtotal = 0;
				foreach ( $purch as $type => $values ) {
					$count    = (int) $values['count'];
					$subtotal = $subtotal + $count;
				}
				if ( 0 === $subtotal ) {
					continue;
				}
				$post_type = get_post_type( $event );
				if ( 'mc-events' === $post_type ) {
					$mc_event = get_post_meta( $event, '_mc_event_id', true );
					$url      = admin_url( 'admin.php?page=my-calendar&amp;mode=edit&amp;event_id=' . $mc_event );
				} else {
					$url = admin_url( "post.php?post=$event&amp;action=edit" );
				}
				$titles[]     = "<a href='$url'>" . get_the_title( $event ) . '</a>';
				$raw_titles[] = get_the_title( $event );
			}
		}
		$events        = implode( ', ', $titles );
		$raw_events    = implode( ', ', array_map( 'strip_tags', $titles ) );
		$alternate     = ( 'alternate' === $alternate ) ? 'even' : 'alternate';
		$custom_fields = apply_filters( 'mt_custom_fields', array(), 'reports' );
		$custom_cells  = '';
		$custom_csv    = '';
		foreach ( $custom_fields as $name => $field ) {
			$c_value = get_post_meta( $post->ID, $name );
			$cstring = '';
			foreach ( $c_value as $v ) {
				if ( is_array( $v ) ) {
					if ( absint( $v['event_id'] ) === absint( $post->ID ) ) {
						$keys = array_keys( $v );
						foreach ( $keys as $val ) {
							if ( 'event_id' !== $val ) {
								$cstring .= ( '' !== $cstring ) ? '; ' : '';
								$cstring .= esc_html( $v[ $val ] );
							}
						}
					}
				} elseif ( ! is_object( $v ) ) {
					$cstring .= $v;
				}
			}
			$c_value       = apply_filters( 'mt_format_report_field', $cstring, get_post_meta( $post->ID, $name, true ), $post->ID, $name );
			$custom_cells .= "<td class='mt_" . sanitize_title( $name ) . "'>$c_value</td>\n";
			$custom_csv   .= ",\"$c_value\"";
		}
		$html[] = "
			<tr class='$alternate'>
				<td class='mt-purchaser'><a href='" . get_edit_post_link( $post->ID ) . "'>$purchaser</a></td>
				<td class='mt-value'>$format_value</td>
				<td class='mt-type'>$type</td>
				<td class='mt-status'>$status</td>
				<td class='mt-events'>$events</td>
				<td class='mt-date'>$date $time</td>
				$custom_cells
			</tr>\n";
		$csv[]  = '"' . $last_name . '","' . $first_name . '","' . $email . '","' . $type . '","' . $value . '","' . $status . '","' . $raw_events . '","' . $date . ' ' . $time . '"' . $custom_csv . PHP_EOL;
	}
	$report['html']  = $html;
	$report['csv']   = $csv;
	$report['total'] = $total;
	$report['start'] = $start;
	$report['end']   = $end;

	return $report;
}

/**
 * Print report by time to screen.
 */
function mt_generate_report_by_time() {
	$report = mt_get_report_data_by_time();
	$output = '';
	if ( is_array( $report ) && ! empty( $report ) ) {
		$purchases      = $report['html'];
		$total          = $report['total'];
		$start          = $report['start'];
		$end            = $report['end'];
		$custom_fields  = apply_filters( 'mt_custom_fields', array(), 'reports' );
		$custom_headers = '';
		foreach ( $custom_fields as $name => $field ) {
			$custom_headers .= "<th scope='col' class='mt_" . sanitize_title( $name ) . "'>" . $field['title'] . "</th>\n";
		}
		// Translators: Starting date, ending date.
		$output .= '<h3>' . sprintf( __( 'Sales from %1$s to %2$s', 'my-tickets' ), $start, $end ) . '</h3>';
		$output .= "<table class='widefat'>
			<thead>
				<tr>
					<th scope='col' class='mt-purchaser'>" . __( 'Purchaser', 'my-tickets' ) . "</th>
					<th scope='col' class='mt-value'>" . __( 'Purchase Value', 'my-tickets' ) . "</th>
					<th scope='col' class='mt-type'>" . __( 'Type', 'my-tickets' ) . "</th>
					<th scope='col' class='mt-status'>" . __( 'Status', 'my-tickets' ) . "</th>
					<th scope='col' class='mt-events'>" . __( 'Events', 'my-tickets' ) . "</th>
					<th scope='col' class='mt-date'>" . __( 'Date', 'my-tickets' ) . '</th>' .
					$custom_headers .
				'</tr>
			</thead>
			<tbody>';
		if ( is_array( $purchases ) && ! empty( $purchases ) ) {
			foreach ( $purchases as $row ) {
				$output .= $row;
			}
		}
		$output .= '</tbody>
		</table>';
		// Translators: Time period.
		$output     .= sprintf( '<p>' . __( 'Total sales in period: %s', 'my-tickets' ) . '</p>', '<strong>' . apply_filters( 'mt_money_format', $total ) . '</strong>' );
		$custom_line = apply_filters( 'mt_custom_total_line_time', '', $start, $end );
		$output     .= $custom_line;
	} else {
		$output = '<p>' . __( 'No sales in period.', 'my-tickets' ) . '</p>';
	}
	echo wp_kses_post( $output );
}

/**
 * Return a list of purchasers names/emails for use in mass emailing
 *
 * @param int $event_id Event ID.
 *
 * @return array
 */
function mt_get_purchasers( $event_id ) {
	$purchases = get_post_meta( $event_id, '_purchase' );
	$contacts  = array();
	if ( is_array( $purchases ) ) {
		foreach ( $purchases as $payment ) {
			foreach ( $payment as $purchase_id => $details ) {
				if ( 'publish' !== get_post_status( $purchase_id ) ) {
					continue;
				}
				$status = get_post_meta( $purchase_id, '_is_paid', true );
				// only send email to Completed payments.
				if ( 'Completed' !== $status ) {
					continue;
				}
				$purchaser  = get_the_title( $purchase_id );
				$email      = get_post_meta( $purchase_id, '_email', true );
				$opt_out    = get_post_meta( $purchase_id, '_opt_out', true );
				$contacts[] = array(
					'purchase_id' => $purchase_id,
					'opt_out'     => $opt_out,
					'name'        => $purchaser,
					'email'       => $email,
				);
			}
		}
	}

	return $contacts;
}

/**
 * Send mass email to purchasers of event.
 *
 * @param bool|int $event_id Event ID.
 */
function mt_mass_email( $event_id = false ) {
	if ( ! $event_id ) {
		$event_id = ( isset( $_POST['event_id'] ) ) ? (int) $_POST['event_id'] : false;
	}
	if ( $event_id ) {
		$body      = ( ! empty( $_POST['mt_body'] ) ) ? wp_kses_post( $_POST['mt_body'] ) : false;
		$subject   = ( ! empty( $_POST['mt_subject'] ) ) ? wp_kses_post( $_POST['mt_subject'] ) : false;
		$orig_subj = stripslashes( $subject );
		$orig_body = stripslashes( $body );
		$message   = '';
		// save email message to event post.
		add_post_meta(
			$event_id,
			'_mass_email',
			array(
				'subject' => $subject,
				'body'    => $body,
			)
		);
		if ( ! $body || ! $subject ) {
			echo wp_kses_post( "<div class='updated error'><p>" . __( 'You must include a message subject and body to send mass email.', 'my-tickets' ) . '</p></div>' );
			return;
		}
		$event       = get_the_title( $event_id );
		$options     = array_merge( mt_default_settings(), get_option( 'mt_settings', array() ) );
		$purchasers  = mt_get_purchasers( $event_id );
		$count       = count( $purchasers );
		$emails_sent = 0;
		$opt_outs    = 0;
		$blogname    = get_option( 'blogname' );
		$headers[]   = "From: $blogname Events <" . $options['mt_from'] . '>';
		$headers[]   = "Reply-to: $options[mt_from]";
		foreach ( $purchasers as $purchaser ) {
			if ( 'true' !== $purchaser['opt_out'] ) {
				$purchase_id = $purchaser['purchase_id'];
				$opt_out_url = add_query_arg( 'opt_out', $purchase_id, home_url() );
				// Translators: Opt out URL.
				$opt_out = PHP_EOL . PHP_EOL . '<p><small>' . sprintf( __( "Don't want to receive email from us? Follow this link: %s", 'my-tickets' ), $opt_out_url ) . '</small></p>';
				$opt_out = apply_filters( 'mt_opt_out_text', $opt_out, $opt_out_url, $event_id, 'bulk' );
				$to      = $purchaser['email'];
				$name    = $purchaser['name'];
				$subject = str_replace( '{name}', $name, $subject );
				$body    = str_replace( '{name}', $name, $body );
				if ( 'true' === $options['mt_html_email'] ) {
					add_filter( 'wp_mail_content_type', 'mt_html_type' );
					$body = wpautop( $body . $opt_out );
				} else {
					$body = strip_tags( $body . $opt_out );
				}
				$body = apply_filters( 'mt_modify_email_body', $body, 'purchaser' );
				// Log this message.
				add_post_meta(
					$purchase_id,
					'_mt_send_email',
					array(
						'body'    => $body,
						'subject' => $subject,
						'date'    => mt_current_time(),
					)
				);
				// For test emails, skip sending & reset values.
				if ( isset( $_POST['mt-test-email'] ) ) {
					$emails_sent ++;
					$subject = $orig_subj;
					$body    = $orig_body;
					$message = __( 'Test Email Sent', 'my-tickets' );
				} else {
					$sent = wp_mail( $to, $subject, $body, $headers );
					if ( ! $sent ) {
						// If mail sends, try without custom headers.
						wp_mail( $to, $subject, $body );
					}
					$emails_sent ++;
					$subject = $orig_subj;
					$body    = $orig_body;
				}
				if ( 'true' === $options['mt_html_email'] ) {
					remove_filter( 'wp_mail_content_type', 'mt_html_type' );
				}
			} else {
				$opt_outs ++;
			}
		}
		// send copy of message to admin.
		if ( 'true' === $options['mt_html_email'] ) {
			add_filter( 'wp_mail_content_type', 'mt_html_type' );
		}
		$sent = wp_mail( $options['mt_to'], $orig_subj, wpautop( $orig_body ), $headers );
		if ( ! $sent ) {
			// If mail sends, try without custom headers.
			wp_mail( $options['mt_to'], $orig_subj, wpautop( $orig_body ) );
		}
		if ( 'true' === $options['mt_html_email'] ) {
			remove_filter( 'wp_mail_content_type', 'mt_html_type' );
		}
		// Translators: Number of purchasers notified, total number of purchasers, number of purchasers opted out, name of event.
		$message = ( '' !== $message ) ? $message : sprintf( __( '%1$d/%2$d purchasers of tickets for "%4$s" have been emailed. %3$d/%2$d purchasers have opted out.', 'my-tickets' ), $emails_sent, $count, $opt_outs, $event );
		echo wp_kses_post( "<div class='updated'><p>" . $message . '</p></div>' );
	}
}

add_action( 'template_include', 'mt_opt_out' );
/**
 * Receive opt-out data so purchasers can opt out of receiving email.
 *
 * @param string $template Opt out template name.
 *
 * @return string
 */
function mt_opt_out( $template ) {
	if ( isset( $_GET['opt_out'] ) && is_numeric( $_GET['opt_out'] ) ) {
		$post_id = (int) $_GET['opt_out'];
		update_post_meta( $post_id, '_opt_out', 'true' );
		$template = locate_template( 'opt_out.php' );
		if ( $template ) {
			return $template;
		} else {
			$template = locate_template( 'opt-out.php' );
			if ( locate_template( 'opt-out.php' ) ) {
				return $template;
			} else {
				return dirname( __FILE__ ) . '/templates/opt-out.php';
			}
		}
	}

	return $template;
}
