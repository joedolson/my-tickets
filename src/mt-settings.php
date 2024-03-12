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

		$styles = mt_get_settings( 'style_vars' );
		if ( ! empty( $_POST['style_vars'] ) ) {
			if ( isset( $_POST['new_style_var'] ) ) {
				$key = sanitize_text_field( $_POST['new_style_var']['key'] );
				$val = sanitize_text_field( $_POST['new_style_var']['val'] );
				if ( $key && $val ) {
					if ( 0 !== strpos( $key, '--' ) ) {
						$key = '--' . $key;
					}
					$styles[ $key ] = $val;
				}
			}
			foreach ( $_POST['style_vars'] as $key => $value ) {
				if ( '' !== trim( $value ) ) {
					$styles[ $key ] = sanitize_text_field( $value );
				}
			}
			if ( isset( $_POST['delete_var'] ) ) {
				$delete = map_deep( $_POST['delete_var'], 'sanitize_text_field' );
				foreach ( $delete as $del ) {
					unset( $styles[ $del ] );
				}
			}
		}

		$messages = $_POST['mt_messages'];
		$settings = apply_filters(
			'mt_update_settings',
			array(
				'messages'      => $messages,
				'mt_post_types' => $mt_post_types,
				'mt_to'         => $mt_to,
				'mt_from'       => $mt_from,
				'mt_html_email' => $mt_html_email,
				'style_vars'    => $styles,
			),
			$_POST
		);
		$settings = array_merge( get_option( 'mt_settings', array() ), $settings );
		update_option( 'mt_settings', $settings );
		$messages = apply_filters( 'mt_update_settings', '', $post );

		return '<div class="updated"><p><strong>' . __( 'My Tickets Settings saved', 'my-tickets' ) . "</strong></p>$messages</div>";
	}
	$return = mt_import_settings();
	if ( $return ) {
		return '<div class="updated"><p>' . $return . '</p></div>';
	}

	return false;
}

/**
 * Generate URL to export settings.
 */
function mt_export_settings_url() {
	$nonce = wp_create_nonce( 'mt-export-settings' );
	$url   = add_query_arg( 'mt-export-settings', $nonce, admin_url( 'admin.php?my-tickets' ) );

	return $url;
}

/**
 * Get My Tickets options.
 *
 * @param string $setting A key in the settings array.
 *
 * @return array
 */
function mt_get_settings( $setting = '' ) {
	$options  = ( ! is_array( get_option( 'mt_settings' ) ) ) ? array() : get_option( 'mt_settings' );
	$defaults = mt_default_settings();
	// Update settings structure for ticketing models if needed.
	if ( isset( $options['defaults'] ) && isset( $options['defaults']['counting_method'] ) ) {
		$type                         = $options['defaults']['counting_method'];
		$options['defaults'][ $type ] = $options['defaults'];
		$models                       = array_merge( $defaults['defaults'], $options['defaults'] );
		$options['defaults']          = $models;
	}
	$options = array_merge( $defaults, $options );
	if ( ! empty( $setting ) && $options[ $setting ] ) {
		return $options[ $setting ];
	}

	return $options;
}

/**
 * Export settings
 */
function mt_export_settings() {
	if ( isset( $_GET['mt-export-settings'] ) ) {
		$nonce = wp_verify_nonce( $_GET['mt-export-settings'], 'mt-export-settings' );
		if ( $nonce ) {
			$date     = gmdate( 'Y-m-d', current_time( 'timestamp' ) ); // phpcs:ignore WordPress.DateTime.CurrentTimeTimestamp.Requested
			$settings = get_option( 'mt_settings' );
			header( 'Content-Type: application/json' );
			header( 'Content-Disposition: attachment; filename=my-tickets-' . sanitize_title( get_bloginfo( 'name' ) ) . '-' . $date . '.json' );
			header( 'Pragma: no-cache' );
			wp_send_json( $settings, 200 );
		}
	}
}
add_action( 'admin_init', 'mt_export_settings' );

/**
 * Import settings
 */
function mt_import_settings() {
	if ( isset( $_FILES['mt-import-settings'] ) ) {
		$options = ( ! is_array( get_option( 'mt_settings' ) ) ) ? array() : get_option( 'mt_settings' );
		$nonce   = wp_verify_nonce( $_POST['_wpnonce'], 'my-tickets-nonce' );
		if ( $nonce ) {
			$settings = file_get_contents( $_FILES['mt-import-settings']['tmp_name'] );
			$settings = json_decode( $settings, ARRAY_A );
			if ( null === $settings ) {
				$return = json_last_error();
			} else {
				$settings = map_deep( $settings, 'sanitize_textarea_field' );
				// Remove the My Tickets page IDs from imported settings. Set to local value if present.
				$pages = array( 'mt_purchase_page', 'mt_receipt_page', 'mt_tickets_page' );
				foreach ( $pages as $page ) {
					if ( isset( $settings[ $page ] ) ) {
						if ( $options[ $page ] ) {
							$settings[ $page ] = $options[ $page ];
						} else {
							unset( $settings[ $page ] );
						}
					}
				}
				update_option( 'mt_settings', $settings );
				$return = __( 'My Tickets settings have been replaced with the imported values.', 'my-tickets' );
			}
			return $return;
		}
	}
	return '';
}

/**
 * Generate settings form.
 */
function mt_settings() {
	$response = mt_update_settings( $_POST );
	$options  = mt_get_settings();

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
									<input type="text" name="mt_to" id="mt_to" class="widefat" value="<?php echo ( '' === $options['mt_to'] ) ? esc_attr( get_bloginfo( 'admin_email' ) ) : stripslashes( esc_attr( $options['mt_to'] ) ); ?>"/>
								</p>
								<p>
									<label for="mt_from"><?php _e( 'Send from:', 'my-tickets' ); ?></label><br/>
									<input type="text" name="mt_from" id="mt_from" class="widefat" value="<?php echo ( '' === $options['mt_from'] ) ? esc_attr( get_bloginfo( 'admin_email' ) ) : stripslashes( esc_attr( $options['mt_from'] ) ); ?>"/>
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
															<input type="text" name="mt_messages[<?php echo esc_html( $type ); ?>][admin][subject]" id="mt_messages_<?php echo esc_html( $type ); ?>_admin_subject" class="widefat" value="<?php echo stripslashes( esc_attr( $options['messages'][ $type ]['admin']['subject'] ) ); ?>"/>
														</li>
														<li>
															<label for="mt_messages_<?php echo esc_html( $type ); ?>_admin_body">
																<?php
																// Translators: message status: Completed, Failed, Refunded, or Offline & Pending.
																printf( __( '%s - Administrator Message', 'my-tickets' ), $status_type );
																?>
															</label><br/>
															<textarea class="widefat" name="mt_messages[<?php echo esc_html( $type ); ?>][admin][body]" id="mt_messages_<?php echo esc_html( $type ); ?>_admin_body" rows="12" cols="60"><?php echo stripslashes( esc_attr( $options['messages'][ $type ]['admin']['body'] ) ); ?></textarea>
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
															<input type="text" name="mt_messages[<?php echo esc_html( $type ); ?>][purchaser][subject]" id="mt_messages_<?php echo esc_html( $type ); ?>_purchaser_subject" class="widefat" value="<?php echo stripslashes( esc_attr( $options['messages'][ $type ]['purchaser']['subject'] ) ); ?>"/>
														</li>
														<li>
															<label for="mt_messages_<?php echo esc_html( $type ); ?>_purchaser_body">
																<?php
																// Translators: message status: Completed, Failed, Refunded, or Offline & Pending.
																printf( __( '%s - Purchaser Message', 'my-tickets' ), $status_type );
																?>
															</label><br/>
															<textarea class="widefat" name="mt_messages[<?php echo esc_html( $type ); ?>][purchaser][body]" id="mt_messages_<?php echo esc_html( $type ); ?>_purchaser_body" rows="12" cols="60"><?php echo stripslashes( esc_attr( $options['messages'][ $type ]['purchaser']['body'] ) ); ?></textarea>
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
									$custom_fields = mt_get_custom_fields( 'tags' );
									foreach ( $custom_fields as $name => $field ) {
										$tags[] = $name;
									}
									// Add custom cart fields to display of template tags.
									$cart_custom_fields = apply_filters( 'mt_cart_custom_fields', array(), array(), 'tags' );
									foreach ( $cart_custom_fields as $name => $field ) {
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
								<fieldset class="mt-css-variables">
									<legend><?php esc_html_e( 'CSS Variables', 'my-tickets' ); ?></legend>
									<?php
									$output = '';
									$styles = mt_get_settings( 'style_vars' );
									$styles = mt_style_variables( $styles );
									foreach ( $styles as $var => $style ) {
										$var_id = 'mt' . sanitize_key( $var );
										if ( ! in_array( $var, array_keys( mt_style_variables() ), true ) ) {
											// Translators: CSS variable name.
											$delete = " <input type='checkbox' id='delete_var_$var_id' name='delete_var[]' value='" . esc_attr( $var ) . "' /><label for='delete_var_$var_id'>" . sprintf( esc_html__( 'Delete %s', 'my-tickets' ), '<span class="screen-reader-text">' . $var . '</span>' ) . '</label>';
										} else {
											$delete = '';
										}
										$output .= "<li><label for='$var_id'>" . esc_html( $var ) . "</label><br /><input class='mt-color-input' type='text' id='$var_id' data-variable='$var' name='style_vars[$var]' value='" . esc_attr( $style ) . "' />$delete</li>";
									}
									if ( $output ) {
										echo wp_kses( "<ul class='checkboxes'>$output</ul>", mt_kses_elements() );
									}
									?>
									<div class="mt-new-variable">
										<p>
											<label for='new_style_var_key'><?php esc_html_e( 'New variable', 'my-tickets' ); ?></label><br />
											<input type='text' name='new_style_var[key]' id='new_style_var_key' />
										</p>
										<p>
											<label for='new_style_var_val'><?php esc_html_e( 'Value', 'my-tickets' ); ?></label><br />
											<input type='text' class="mt-color-input" name='new_style_var[val]' id='new_style_var_val' />
										</p>
									</div>
								</fieldset>	
								<p class="mt-save-settings"><input type="submit" name="mt-submit-settings" class="button-primary" value="<?php _e( 'Save Settings', 'my-tickets' ); ?>"/></p>
							</form>
						</div>
					</div>
				</div>
			</div>
			<div class="metabox-holder">
				<div class="ui-sortable meta-box-sortables">
					<div class="postbox">
						<h2 class="hndle"><?php _e( 'Import and Export Settings', 'my-tickets' ); ?></h2>
						<div class="inside">
							<p><a href="<?php echo mt_export_settings_url(); ?>"><?php _e( 'Export settings', 'my-tickets' ); ?></a></p>
							<form method="POST" enctype="multipart/form-data" action="<?php echo esc_url( admin_url( 'admin.php?page=my-tickets' ) ); ?>">
								<input type="hidden" name="_wpnonce" value="<?php echo wp_create_nonce( 'my-tickets-nonce' ); ?>" />
								<p class="mt-input-settings">
									<label for="mt-import-settings"><?php _e( 'Import Settings', 'my-tickets' ); ?></label>
									<input type="file" name="mt-import-settings" id="mt-import-settings" accept="application/json" /> 
									<input type="submit" class="button-secondary" value="<?php _e( 'Import Settings', 'my-tickets' ); ?>">	
								</p>
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
	$version = ( true === SCRIPT_DEBUG ) ? wp_rand( 10000, 100000 ) : mt_get_current_version();
	$options = mt_get_settings();
	if ( isset( $_GET['page'] ) && 'my-tickets' === $_GET['page'] || isset( $_GET['page'] ) && 'mt-ticketing' === $_GET['page'] ) {
		$firstitem = ( 'my-tickets' === $_GET['page'] ) ? 'completed' : 'discrete';
		wp_enqueue_script( 'mt.tabs', plugins_url( 'js/tabs.js', __FILE__ ), array( 'jquery' ), $version );
		wp_localize_script(
			'mt.tabs',
			'mtTabs',
			array(
				'firstItem' => $firstitem,
			)
		);
	}
	if ( isset( $_GET['page'] ) && 'mt-ticketing' === $_GET['page'] || ( 'post' === $current_screen->base && in_array( $current_screen->id, $options['mt_post_types'], true ) || 'toplevel_page_my-calendar' === $current_screen->base ) ) {
		wp_register_script( 'mt.duet', plugins_url( 'js/duet/duet.js', __FILE__ ), array(), $version );
		wp_enqueue_style( 'mt.duet', plugins_url( 'js/duet/themes/default.css', __FILE__ ), array(), $version );
		wp_enqueue_script( 'mt.add', plugins_url( 'js/pricing-table.js', __FILE__ ), array( 'jquery', 'mt.duet' ), $version );
		wp_localize_script(
			'mt.add',
			'mt',
			array(
				'delete'   => __( 'Delete', 'my-tickets' ),
				'undo'     => __( 'Undo Deletion', 'my-tickets' ),
				'action'   => 'mt_ajax_load_model',
				'security' => wp_create_nonce( 'mt-load-model' ),
			)
		);
		wp_localize_script(
			'mt.duet',
			'duetLocalization',
			array(
				'buttonLabel'         => __( 'Choose date', 'my-tickets' ),
				'placeholder'         => 'YYYY-MM-DD',
				'selectedDateMessage' => __( 'Selected date is', 'my-tickets' ),
				'prevMonthLabel'      => __( 'Previous month', 'my-tickets' ),
				'nextMonthLabel'      => __( 'Next month', 'my-tickets' ),
				'monthSelectLabel'    => __( 'Month', 'my-tickets' ),
				'yearSelectLabel'     => __( 'Year', 'my-tickets' ),
				'closeLabel'          => __( 'Close window', 'my-tickets' ),
				'keyboardInstruction' => __( 'You can use arrow keys to navigate dates', 'my-tickets' ),
				'calendarHeading'     => __( 'Choose a date', 'my-tickets' ),
				'dayNames'            => array(
					date_i18n( 'D', strtotime( 'Sunday' ) ),
					date_i18n( 'D', strtotime( 'Monday' ) ),
					date_i18n( 'D', strtotime( 'Tuesday' ) ),
					date_i18n( 'D', strtotime( 'Wednesday' ) ),
					date_i18n( 'D', strtotime( 'Thursday' ) ),
					date_i18n( 'D', strtotime( 'Friday' ) ),
					date_i18n( 'D', strtotime( 'Saturday' ) ),
				),
				'monthNames'          => array(
					date_i18n( 'F', strtotime( 'January 1' ) ),
					date_i18n( 'F', strtotime( 'February 1' ) ),
					date_i18n( 'F', strtotime( 'March 1' ) ),
					date_i18n( 'F', strtotime( 'April 1' ) ),
					date_i18n( 'F', strtotime( 'May 1' ) ),
					date_i18n( 'F', strtotime( 'June 1' ) ),
					date_i18n( 'F', strtotime( 'July 1' ) ),
					date_i18n( 'F', strtotime( 'August 1' ) ),
					date_i18n( 'F', strtotime( 'September 1' ) ),
					date_i18n( 'F', strtotime( 'October 1' ) ),
					date_i18n( 'F', strtotime( 'November 1' ) ),
					date_i18n( 'F', strtotime( 'December 1' ) ),
				),
				'monthNamesShort'     => array(
					date_i18n( 'M', strtotime( 'January 1' ) ),
					date_i18n( 'M', strtotime( 'February 1' ) ),
					date_i18n( 'M', strtotime( 'March 1' ) ),
					date_i18n( 'M', strtotime( 'April 1' ) ),
					date_i18n( 'M', strtotime( 'May 1' ) ),
					date_i18n( 'M', strtotime( 'June 1' ) ),
					date_i18n( 'M', strtotime( 'July 1' ) ),
					date_i18n( 'M', strtotime( 'August 1' ) ),
					date_i18n( 'M', strtotime( 'September 1' ) ),
					date_i18n( 'M', strtotime( 'October 1' ) ),
					date_i18n( 'M', strtotime( 'November 1' ) ),
					date_i18n( 'M', strtotime( 'December 1' ) ),
				),
				'locale'              => str_replace( '_', '-', get_locale() ),
			)
		);
	}
	if ( isset( $_GET['page'] ) && 'mt-payment' === $_GET['page'] ) {
		wp_enqueue_script( 'jquery-ui-autocomplete' );
		wp_enqueue_script( 'mt.tabs', plugins_url( 'js/tabs.js', __FILE__ ), array( 'jquery' ), $version );
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
			),
			$version
		);
		wp_localize_script(
			'mt.functions',
			'mtAjax',
			array(
				'action' => 'mt_post_lookup',
			)
		);
	} elseif ( isset( $_GET['page'] ) && 'mt-reports' === $_GET['page'] ) {
		wp_enqueue_script( 'mt.tabs', plugins_url( 'js/tabs.js', __FILE__ ), array( 'jquery' ), $version );
		wp_localize_script(
			'mt.tabs',
			'mtTabs',
			array(
				'firstItem' => 'mt_completed',
			)
		);
	}
	if ( 'mt-payments' === $current_screen->post_type ) {
		wp_enqueue_script( 'mt.payments', plugins_url( 'js/jquery.payments.js', __FILE__ ), array( 'jquery' ), $version );
		wp_localize_script(
			'mt.payments',
			'mt_data',
			array(
				'action'       => 'move_ticket',
				'deleteaction' => 'delete_ticket',
				'security'     => wp_create_nonce( 'mt-move-ticket' ),
			)
		);
		wp_enqueue_script(
			'mt.functions',
			plugins_url( 'js/jquery.functions.js', __FILE__ ),
			array(
				'jquery',
				'jquery-ui-autocomplete',
			),
			$version
		);
		wp_localize_script(
			'mt.functions',
			'mtAjax',
			array(
				'action' => 'mt_event_lookup',
			)
		);
	}
	if ( 'post' === $current_screen->base && in_array( $current_screen->id, $options['mt_post_types'], true ) || 'toplevel_page_my-calendar' === $current_screen->base ) {
		wp_enqueue_script( 'mt.show', plugins_url( 'js/jquery.showfields.js', __FILE__ ), array( 'jquery' ), $version, true );
		wp_localize_script(
			'mt.show',
			'mtShow',
			array(
				// translators: quantity of time in H:MM format.
				'expireAfter'  => __( '%s after the event starts.', 'my-tickets' ),
				// translators: quantity of time in H:MM format.
				'expireBefore' => __( '%s before the event starts.', 'my-tickets' ),
			)
		);
	}
}

/**
 * Generate date picker output.
 *
 * @param array $args Array of field arguments.
 *
 * @return string
 */
function mt_datepicker_html( $args ) {
	$sweek    = absint( get_option( 'start_of_week' ) );
	$firstday = ( 1 === $sweek || 0 === $sweek ) ? $sweek : 0;

	$id       = isset( $args['id'] ) ? esc_attr( $args['id'] ) : 'id_arg_missing';
	$name     = isset( $args['name'] ) ? esc_attr( $args['name'] ) : 'name_arg_missing';
	$value    = isset( $args['value'] ) ? esc_attr( $args['value'] ) : '';
	$required = isset( $args['required'] ) ? 'required' : '';
	$output   = "<duet-date-picker first-day-of-week='$firstday' identifier='$id' name='$name' value='$value' $required></duet-date-picker><input type='date' id='$id' name='$name' value='$value' $required class='duet-fallback' />";
	/**
	 * Filter the My Tickets datepicker output.
	 *
	 * @hook mt_datepicker_html
	 *
	 * @param {string} $output Default datepicker output.
	 * @param {array}  $args Datepicker setup arguments.
	 *
	 * @return {string}
	 */
	$output = apply_filters( 'mt_datepicker_html', $output, $args );

	return $output;
}

add_action( 'wp_ajax_delete_ticket', 'mt_ajax_delete_ticket' );
/**
 * Delete a single occurrence of an event from the event manager.
 */
function mt_ajax_delete_ticket() {
	$event_id   = (int) $_REQUEST['event_id'];
	$payment_id = (int) $_REQUEST['payment_id'];
	$ticket     = sanitize_text_field( $_REQUEST['ticket'] );

	if ( ! check_ajax_referer( 'mt-move-ticket', 'security', false ) ) {
		wp_send_json(
			array(
				'success'  => 0,
				'response' => __( 'Invalid Security Check', 'my-tickets' ),
			)
		);
	}

	if ( current_user_can( 'mt-view-reports' ) ) {
		$ticket_data = get_post_meta( $event_id, '_' . $ticket, true );
		$removed     = mt_remove_ticket( $event_id, $ticket, $ticket_data, $payment_id );
		if ( $removed ) {
			$tickets = get_post_meta( $payment_id, '_tickets', true );
			foreach ( $tickets as $key => $tick ) {
				if ( $tick === $ticket ) {
					unset( $tickets[ $key ] );
				}
			}
			update_post_meta( $payment_id, '_tickets', $tickets );
			wp_send_json(
				array(
					'success'  => 1,
					'response' => esc_html( __( 'Ticket permanently deleted.', 'my-tickets' ) ),
					'result'   => $removed,
				)
			);
		} else {
			wp_send_json(
				array(
					'success'  => 0,
					'response' => esc_html( __( 'Ticket could not be deleted.', 'my-tickets' ) ),
					'result'   => $removed,
				)
			);
		}
	} else {
		wp_send_json(
			array(
				'success'  => 0,
				'response' => esc_html__( 'You are not authorized to perform this action', 'my-tickets' ),
			)
		);
	}
}

add_action( 'wp_ajax_move_ticket', 'mt_ajax_move_ticket' );
/**
 * Delete a single occurrence of an event from the event manager.
 */
function mt_ajax_move_ticket() {
	$event_id   = (int) $_REQUEST['event_id'];
	$target     = (int) $_REQUEST['target'];
	$payment_id = (int) $_REQUEST['payment_id'];
	$ticket     = sanitize_text_field( $_REQUEST['ticket'] );

	if ( ! check_ajax_referer( 'mt-move-ticket', 'security', false ) ) {
		wp_send_json(
			array(
				'success'  => 0,
				'response' => __( 'Invalid Security Check', 'my-tickets' ),
			)
		);
	}

	if ( $event_id === $target ) {
		wp_send_json(
			array(
				'success'  => 0,
				'response' => __( 'The new event provided is the same as the current event.', 'my-tickets' ),
			)
		);
	}

	if ( ! $event_id || ! $target || ! $ticket ) {
		wp_send_json(
			array(
				'success'  => 0,
				'response' => __( 'Please provide an ID for a post configured to sell tickets.', 'my-tickets' ),
			)
		);
	}

	if ( current_user_can( 'mt-view-reports' ) ) {
		$result  = mt_move_ticket( $payment_id, $event_id, $target, $ticket );
		$new     = get_the_title( $target );
		$added   = $result['added'];
		$removed = $result['removed'];
		$success = ( $added && $removed ) ? 1 : 0;
		if ( $success ) {
			wp_send_json(
				array(
					'success'  => $success,
					// translators: Title of new event for ticket.
					'response' => esc_html( sprintf( __( 'Ticket moved to %s', 'my-tickets' ), $new ) ),
					'result'   => $result,
				)
			);
		} else {
			$message = ( ! $added ) ? __( 'Unable to add ticket to the new event.', 'my-tickets' ) : __( 'Ticket was not moved successfully.', 'my-tickets' );
			wp_send_json(
				array(
					'success'  => $success,
					'response' => esc_html( $message ),
					'result'   => $result,
				)
			);
		}
	} else {
		wp_send_json(
			array(
				'success'  => 0,
				'response' => esc_html__( 'You are not authorized to perform this action', 'my-tickets' ),
			)
		);
	}
}

add_action( 'admin_enqueue_scripts', 'mt_report_scripts' );
/**
 * Enqueue footer scripts in report view.
 */
function mt_report_scripts() {
	$version = ( true === SCRIPT_DEBUG ) ? wp_rand( 10000, 100000 ) : mt_get_current_version();
	if ( isset( $_GET['mt-event-report'] ) && isset( $_GET['mt_print'] ) ) {
		wp_enqueue_script( 'mt.printable', plugins_url( 'js/report.js', __FILE__ ), array( 'jquery' ), $version );
		wp_localize_script(
			'mt.printable',
			'mtprint',
			array(
				'mt_action_text' => __( 'Hide', 'my-tickets' ),
			)
		);
		wp_enqueue_style( 'mt.printable', plugins_url( 'css/report.css', __FILE__ ), array(), $version );
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
				's'         => sanitize_text_field( $_REQUEST['term'] ),
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


add_action( 'wp_ajax_mt_event_lookup', 'mt_event_lookup' );
/**
 * AJAX event lookup.
 */
function mt_event_lookup() {
	$options    = mt_get_settings();
	$post_types = $options['mt_post_types'];
	if ( isset( $_REQUEST['term'] ) ) {
		$posts       = get_posts(
			array(
				's'         => sanitize_text_field( $_REQUEST['term'] ),
				'post_type' => $post_types,
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
