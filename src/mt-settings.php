<?php
/**
 * General settings.
 *
 * @category Settings
 * @package  My Tickets
 * @author   Joe Dolson
 * @license  GPLv2 or later
 * @link     https://www.joedolson.com/my-tickets/
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
} // Exit if accessed directly

/**
 * My Tickets general settings.
 *
 * @param array $post POST data.
 *
 * @return bool|string
 */
function mt_update_settings( $post ) {
	if ( isset( $post['mt-submit-settings'] ) ) {
		$nonce = isset( $_POST['_wpnonce'] ) ? $_POST['_wpnonce'] : false;
		if ( ! wp_verify_nonce( $nonce, 'my-tickets' ) ) {
			return false;
		}
		$mt_to         = sanitize_text_field( $post['mt_to'] ); // send to.
		$mt_from       = is_email( $post['mt_from'] ); // send from.
		$mt_html_email = ( isset( $post['mt_html_email'] ) ) ? 'true' : 'false'; // send as HTML.
		$mt_post_types = ( isset( $post['mt_post_types'] ) ) ? $post['mt_post_types'] : array();
		array_push( $mt_post_types, 'mc-events' );

		$messages = $_POST['mt_messages'];
		$settings = apply_filters(
			'mt_update_settings',
			array(
				'messages'      => $messages,
				'mt_post_types' => $mt_post_types,
				'mt_to'         => $mt_to,
				'mt_from'       => $mt_from,
				'mt_html_email' => $mt_html_email,
			),
			$_POST
		);
		$settings = array_merge( get_option( 'mt_settings', array() ), $settings );
		update_option( 'mt_settings', $settings );
		$messages = apply_filters( 'mt_update_settings', '', $post );

		return '<div class="updated"><p><strong>' . __( 'My Tickets Settings saved', 'my-tickets' ) . "</strong></p>$messages</div>";
	}

	return false;
}

/**
 * Generate settings form.
 */
function mt_settings() {
	$response = mt_update_settings( $_POST );
	$options  = ( ! is_array( get_option( 'mt_settings' ) ) ) ? array() : get_option( 'mt_settings' );
	$defaults = mt_default_settings();
	$options  = array_merge( $defaults, $options );

	$post_types    = get_post_types( array( 'public' => true ), 'objects' );
	$mt_post_types = $options['mt_post_types'];
	if ( ! is_array( $mt_post_types ) ) {
		$mt_post_types = array();
	}
	$mt_post_type_options = '';

	foreach ( $post_types as $type ) {
		if ( 'mc-events' === $type->name ) {
			continue;
		}
		if ( in_array( $type->name, $mt_post_types, true ) ) {
			$selected = ' checked="checked"';
		} else {
			$selected = '';
		}
		$mt_post_type_options .= "<li><input type='checkbox' name='mt_post_types[]' id='mt_$type->name' value='$type->name' $selected> <label for='mt_$type->name'>" . esc_html( $type->labels->name ) . '</label></li>';
	}
	?>
	<div class="wrap my-tickets" id="mt_settings">
		<h1><?php _e( 'Event Registrations', 'my-tickets' ); ?></h1>
		<?php echo wp_kses_post( $response ); ?>
		<div class="postbox-container jcd-wide">
			<div class="metabox-holder">

				<div class="ui-sortable meta-box-sortables">
					<div class="postbox">
						<h2 class="hndle"><?php _e( 'My Tickets Event Registration Settings', 'my-tickets' ); ?></h2>

						<div class="inside">
							<form method="post" action="<?php echo esc_url( admin_url( 'admin.php?page=my-tickets' ) ); ?>">
								<div><input type="hidden" name="_wpnonce" value="<?php echo wp_create_nonce( 'my-tickets' ); ?>" /></div>
								<fieldset>
									<legend><?php _e( 'Enable ticketing for these post types:', 'my-tickets' ); ?></legend>
									<ul class="checkboxes">
										<?php echo wp_kses( $mt_post_type_options, mt_kses_elements() ); ?>
									</ul>
								</fieldset>
								<h4><?php _e( 'Ticket Purchase Messages', 'my-tickets' ); ?></h4>
								<?php
									echo apply_filters( 'mt_settings_fields', '', $options );
								?>
								<p>
									<input type="checkbox" id="mt_html_email" name="mt_html_email" <?php checked( true, mt_is_checked( 'mt_html_email', 'true', $options ) ); ?> />
									<label for="mt_html_email"><?php _e( 'Send email as HTML.', 'my-tickets' ); ?></label>
								</p>
								<p>
									<label for="mt_to"><?php _e( 'Send to:', 'my-tickets' ); ?></label><br/>
									<input type="text" name="mt_to" id="mt_to" size="60" value="<?php echo ( '' === $options['mt_to'] ) ? esc_attr( get_bloginfo( 'admin_email' ) ) : stripslashes( esc_attr( $options['mt_to'] ) ); ?>"/>
								</p>
								<p>
									<label for="mt_from"><?php _e( 'Send from:', 'my-tickets' ); ?></label><br/>
									<input type="text" name="mt_from" id="mt_from" size="60" value="<?php echo ( '' === $options['mt_from'] ) ? esc_attr( get_bloginfo( 'admin_email' ) ) : stripslashes( esc_attr( $options['mt_from'] ) ); ?>"/>
								</p>
								<?php
								$tabs         = '';
								$status_types = array(
									'completed' => __( 'Completed', 'my-tickets' ),
									'failed'    => __( 'Failed', 'my-tickets' ),
									'refunded'  => __( 'Refunded', 'my-tickets' ),
									'interim'   => __( 'Offline & Pending', 'my-tickets' ),
								);
								foreach ( $status_types as $type => $status_type ) {
									$tabs .= "<li><a href='#$type'>$status_type</a></li>";
								}
								?>
								<div class='mt-notifications'>
									<div class='mt-tabs'>
										<ul class='tabs'>
											<?php echo wp_kses_post( $tabs ); ?>
										</ul>
										<?php
										foreach ( $status_types as $type => $status_type ) {
											?>
											<div class='wptab mt_<?php echo esc_html( $type ); ?>' id='<?php echo esc_html( $type ); ?>' aria-live='assertive'>
												<fieldset>
													<legend><?php _e( 'Sent to administrators', 'my-tickets' ); ?></legend>
													<ul>
														<li>
															<label for="mt_messages_<?php echo esc_html( $type ); ?>_admin_subject">
																<?php
																// Translators: message status: Completed, Failed, Refunded, or Offline & Pending.
																printf( __( '%s - Administrator Subject', 'my-tickets' ), $status_type );
																?>
															</label><br/>
															<input type="text" name="mt_messages[<?php echo esc_html( $type ); ?>][admin][subject]" id="mt_messages_<?php echo esc_html( $type ); ?>_admin_subject" size="60" value="<?php echo stripslashes( esc_attr( $options['messages'][ $type ]['admin']['subject'] ) ); ?>"/>
														</li>
														<li>
															<label for="mt_messages_<?php echo esc_html( $type ); ?>_admin_body">
																<?php
																// Translators: message status: Completed, Failed, Refunded, or Offline & Pending.
																printf( __( '%s - Administrator Message', 'my-tickets' ), $status_type );
																?>
															</label><br/>
															<textarea name="mt_messages[<?php echo esc_html( $type ); ?>][admin][body]" id="mt_messages_<?php echo esc_html( $type ); ?>_admin_body" rows="12" cols="60"><?php echo stripslashes( esc_attr( $options['messages'][ $type ]['admin']['body'] ) ); ?></textarea>
														</li>
													</ul>
												</fieldset>
												<fieldset>
													<legend><?php _e( 'Sent to purchaser', 'my-tickets' ); ?></legend>
													<ul>
														<li>
															<label for="mt_messages_<?php echo esc_html( $type ); ?>_purchaser_subject">
																<?php
																// Translators: message status: Completed, Failed, Refunded, or Offline & Pending.
																printf( __( '%s - Purchaser Subject', 'my-tickets' ), $status_type );
																?>
															</label><br/>
															<input type="text" name="mt_messages[<?php echo esc_html( $type ); ?>][purchaser][subject]" id="mt_messages_<?php echo esc_html( $type ); ?>_purchaser_subject" size="60" value="<?php echo stripslashes( esc_attr( $options['messages'][ $type ]['purchaser']['subject'] ) ); ?>"/>
														</li>
														<li>
															<label for="mt_messages_<?php echo esc_html( $type ); ?>_purchaser_body">
																<?php
																// Translators: message status: Completed, Failed, Refunded, or Offline & Pending.
																printf( __( '%s - Purchaser Message', 'my-tickets' ), $status_type );
																?>
															</label><br/>
															<textarea name="mt_messages[<?php echo esc_html( $type ); ?>][purchaser][body]" id="mt_messages_<?php echo esc_html( $type ); ?>_purchaser_body" rows="12" cols="60"><?php echo stripslashes( esc_attr( $options['messages'][ $type ]['purchaser']['body'] ) ); ?></textarea>
														</li>
													</ul>
												</fieldset>
											</div>
											<?php
										}
										?>
									</div>
									<?php
									$tags = array(
										'receipt',
										'tickets',
										'ticket_ids',
										'name',
										'blogname',
										'total',
										'key',
										'purchase',
										'address',
										'gateway_notes',
										'transaction',
										'transaction_id',
										'amount_due',
										'method',
										'handling',
										'shipping',
										'phone',
										'vat',
										'purchase_ID',
										'purchase_edit',
										'buyer_email',
										'event_notes',
										'bulk_tickets',
									);
									// Add custom fields to display of template tags.
									$custom_fields = apply_filters( 'mt_custom_fields', array(), 'tags' );
									foreach ( $custom_fields as $name => $field ) {
										$tags[] = $name;
									}
									// Add custom tags that are not also custom fields.
									$tags      = apply_filters( 'mt_display_tags', $tags );
									$tags      = array_map( 'mt_array_code', $tags );
									$available = implode( ', ', $tags );
									?>
									<p><em>
									<?php
									// Translators: list of template tags.
									printf( __( 'Available template tags: %s', 'my-tickets' ), $available );
									?>
									</em></p>
								</div>

								<p><input type="submit" name="mt-submit-settings" class="button-primary" value="<?php _e( 'Save Settings', 'my-tickets' ); ?>"/></p>
							</form>
						</div>
					</div>
				</div>
			</div>
			<div class="metabox-holder">
				<div class="ui-sortable meta-box-sortables">
					<div class="postbox">
						<h2 class="hndle"><?php _e( 'Premium Add-on License Keys', 'my-tickets' ); ?></h2>
						<?php
						if ( isset( $_POST['mt_license_keys'] ) && wp_verify_nonce( $_POST['_wpnonce_tickets'], 'my-tickets-licensing' ) ) {
							echo wp_kses_post( "<div class='updated'><ul>" . apply_filters( 'mt_save_license', '', $_POST ) . '</ul></div>' );
						}
						?>
						<div class="inside">
							<?php
							$fields = apply_filters( 'mt_license_fields', '' );
							if ( '' !== $fields ) {
								?>
							<form method="post" action="<?php echo esc_url( admin_url( 'admin.php?page=my-tickets' ) ); ?>">
								<div>
									<input type="hidden" name="mt_license_keys" value="saved" />
									<input type="hidden" name="_wpnonce_tickets" value="<?php echo wp_create_nonce( 'my-tickets-licensing' ); ?>"/>
								</div>
							<ul>
								<?php echo wp_kses( $fields, mt_kses_elements() ); ?>
							</ul>
								<p><input type="submit" name="mt-submit-settings" class="button-primary" value="<?php _e( 'Save License Keys', 'my-tickets' ); ?>"/></p>
							</form>
								<?php
							} else {
								// Translators: URL to purchase add-ons.
								echo wp_kses_post( '<p>' . sprintf( __( 'If you install any <a href="%s">My Tickets Premium Add-ons</a>, the license fields will appear here.', 'my-tickets' ), 'https://www.joedolson.com/my-tickets/add-ons/' ) . '</p>' );
							}
							?>
						</div>
					</div>
				</div>
			</div>
		</div>
		<?php mt_show_support_box(); ?>
	</div>
	<?php
	// creates settings page for My tickets.
}

/**
 * Display a set of array values for template tags.
 *
 * @param string $k Array value.
 *
 * @return string
 */
function mt_array_code( $k ) {
	return '<code>{' . $k . '}</code>';
}

add_action( 'admin_enqueue_scripts', 'mt_wp_enqueue_scripts' );
/**
 * Enqueue admin scripts and styles.
 */
function mt_wp_enqueue_scripts() {
	global $current_screen;
	$options = array_merge( mt_default_settings(), get_option( 'mt_settings', array() ) );
	if ( isset( $_GET['page'] ) && 'my-tickets' === $_GET['page'] ) {
		wp_enqueue_script( 'mt.tabs', plugins_url( 'js/tabs.js', __FILE__ ), array( 'jquery' ) );
		wp_localize_script(
			'mt.tabs',
			'mtTabs',
			array(
				'firstItem' => 'completed',
			)
		);
	}
	if ( isset( $_GET['page'] ) && 'mt-ticketing' === $_GET['page'] ) {
		wp_enqueue_script( 'mt.add', plugins_url( 'js/jquery.addfields.js', __FILE__ ), array( 'jquery' ) );
		wp_localize_script(
			'mt.add',
			'mt',
			array(
				'delete' => __( 'Delete', 'my-tickets' ),
				'undo'   => __( 'Undo Deletion', 'my-tickets' ),
			)
		);
	}
	if ( isset( $_GET['page'] ) && 'mt-payment' === $_GET['page'] ) {
		wp_enqueue_script( 'jquery-ui-autocomplete' );
		wp_enqueue_script( 'mt.tabs', plugins_url( 'js/tabs.js', __FILE__ ), array( 'jquery' ) );
		wp_localize_script(
			'mt.tabs',
			'mtTabs',
			array(
				'firstItem' => $options['mt_default_gateway'],
			)
		);
		wp_enqueue_script(
			'mt.functions',
			plugins_url( 'js/jquery.functions.js', __FILE__ ),
			array(
				'jquery',
				'jquery-ui-autocomplete',
			)
		);
		wp_localize_script(
			'mt.functions',
			'mtAjax',
			array(
				'action' => 'mt_post_lookup',
			)
		);
	} elseif ( isset( $_GET['page'] ) && 'mt-reports' === $_GET['page'] ) {
		wp_enqueue_script( 'mt.tabs', plugins_url( 'js/tabs.js', __FILE__ ), array( 'jquery' ) );
		wp_localize_script(
			'mt.tabs',
			'mtTabs',
			array(
				'firstItem' => 'mt_completed',
			)
		);
	}
	if ( 'mt-payment' === $current_screen->post_type ) {
		wp_enqueue_script( 'mt.payments', plugins_url( 'js/jquery.payments.js', __FILE__ ), array( 'jquery' ) );
	}
	if ( 'post' === $current_screen->base && in_array( $current_screen->id, $options['mt_post_types'], true ) || 'toplevel_page_my-calendar' === $current_screen->base ) {
		wp_enqueue_script( 'mt.add', plugins_url( 'js/jquery.addfields.js', __FILE__ ), array( 'jquery' ) );
		wp_localize_script(
			'mt.add',
			'mt',
			array(
				'delete' => __( 'Delete', 'my-tickets' ),
				'undo'   => __( 'Undo Deletion', 'my-tickets' ),
			)
		);
		wp_enqueue_script( 'mt.show', plugins_url( 'js/jquery.showfields.js', __FILE__ ), array( 'jquery' ), '1.0.0', true );
	}
}

add_action( 'admin_enqueue_scripts', 'mt_report_scripts' );
/**
 * Enqueue footer scripts in report view.
 */
function mt_report_scripts() {
	if ( isset( $_GET['mt-event-report'] ) && isset( $_GET['mt_print'] ) ) {
		wp_enqueue_script( 'mt.printable', plugins_url( 'js/report.js', __FILE__ ), array( 'jquery' ) );
		wp_localize_script(
			'mt.printable',
			'mtprint',
			array(
				'mt_action_text' => __( 'Hide', 'my-tickets' ),
			)
		);
		wp_enqueue_style( 'mt.printable', plugins_url( 'css/report.css', __FILE__ ) );
	}
}

add_action( 'wp_ajax_mt_post_lookup', 'mt_post_lookup' );
/**
 * AJAX post lookup.
 */
function mt_post_lookup() {
	if ( isset( $_REQUEST['term'] ) ) {
		$posts       = get_posts(
			array(
				's'         => $_REQUEST['term'],
				'post_type' => array( 'post', 'page' ),
			)
		);
		$suggestions = array();
		global $post;
		foreach ( $posts as $post ) {
			setup_postdata( $post );
			$suggestion          = array();
			$suggestion['value'] = esc_html( $post->post_title );
			$suggestion['id']    = $post->ID;
			$suggestions[]       = $suggestion;
		}

		echo esc_html( $_GET['callback'] ) . '(' . json_encode( $suggestions ) . ')';
		exit;
	}
}
