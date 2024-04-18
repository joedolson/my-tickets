<?php
/**
 * Ticket settings.
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
 * Update ticketing settings.
 *
 * @param array $post POST data.
 *
 * @return bool|string
 */
function mt_update_ticketing_settings( $post ) {
	if ( isset( $post['mt-ticketing-settings'] ) ) {
		$nonce = $_POST['_wpnonce'];
		if ( ! wp_verify_nonce( $nonce, 'my-tickets' ) ) {
			return '';
		}
		$mt_handling              = ( isset( $post['mt_handling'] ) ) ? $post['mt_handling'] : 0;
		$mt_ticket_handling       = ( isset( $post['mt_ticket_handling'] ) ) ? $post['mt_ticket_handling'] : 0;
		$mt_shipping              = ( isset( $post['mt_shipping'] ) ) ? $post['mt_shipping'] : 0;
		$mt_ticketing             = ( isset( $post['mt_ticketing'] ) ) ? $post['mt_ticketing'] : array();
		$mt_ticket_type_default   = ( isset( $post['mt_ticket_type_default'] ) ) ? $post['mt_ticket_type_default'] : '';
		$mt_total_tickets         = ( isset( $post['mt_tickets_total'] ) ) ? $post['mt_tickets_total'] : 'inherit';
		$mt_shipping_time         = ( isset( $post['mt_shipping_time'] ) ) ? $post['mt_shipping_time'] : '3-5';
		$mt_hide_remaining        = ( isset( $post['mt_hide_remaining'] ) ) ? 'true' : 'false';
		$mt_show_closed           = ( isset( $post['mt_show_closed'] ) ) ? 'true' : 'false';
		$mt_singular              = ( isset( $post['mt_singular'] ) ) ? 'true' : 'false';
		$mt_inventory             = ( isset( $post['mt_inventory'] ) ) ? 'virtual' : 'actual';
		$mt_hide_remaining_limit  = ( isset( $post['mt_hide_remaining_limit'] ) ) ? intval( $post['mt_hide_remaining_limit'] ) : 0;
		$mt_collect_shipping      = ( isset( $post['mt_collect_shipping'] ) ) ? 'true' : 'false';
		$mt_hide_empty_short_cart = ( isset( $post['mt_hide_empty_short_cart'] ) ) ? 'true' : 'false';
		$close_value              = ( isset( $post['mt_tickets_close_value'] ) ) ? $post['mt_tickets_close_value'] : '';
		$close_type               = ( isset( $post['mt_tickets_close_type'] ) ) ? $post['mt_tickets_close_type'] : 'integer';
		$mt_display_remaining     = ( isset( $post['mt_display_remaining'] ) ) ? $post['mt_display_remaining'] : 'proportion';
		$mt_ticket_image          = ( isset( $post['mt_ticket_image'] ) ) ? $post['mt_ticket_image'] : 'ticket';
		$default_model            = ( isset( $post['default_model'] ) ) ? $post['default_model'] : 'continuous';

		$ticket_models = array( 'continuous', 'discrete', 'event' );
		foreach ( $ticket_models as $model ) {
			$model_defaults = ( isset( $post['defaults'][ $model ] ) ) ? $post['defaults'][ $model ] : array();
			$labels         = ( isset( $post['mt_label'][ $model ] ) ) ? $post['mt_label'][ $model ] : array();
			$prices         = ( isset( $post['mt_price'][ $model ] ) ) ? $post['mt_price'][ $model ] : array();
			$close          = ( isset( $post['mt_close'][ $model ] ) ) ? $post['mt_close'][ $model ] : array();
			$availability   = ( isset( $post['mt_tickets'][ $model ] ) ) ? $post['mt_tickets'][ $model ] : array();
			$pricing_array  = mt_setup_pricing( $labels, $prices, $availability, $close );

			$defaults[ $model ]            = $model_defaults;
			$defaults[ $model ]['pricing'] = $pricing_array;
			$defaults[ $model ]['tickets'] = ( is_array( $mt_total_tickets ) ) ? $mt_total_tickets[ $model ] : $mt_total_tickets;
		}

		/**
		 * Filter settings array before saving option.
		 *
		 * @hook mt_settings
		 *
		 * @param {array} $settings Array of settings with values set by user prior to save to database.
		 * @param {array} $_POST Post data array.
		 *
		 * @return {array}
		 */
		$settings = apply_filters(
			'mt_settings',
			array(
				'defaults'                 => $defaults,
				'default_model'            => $default_model,
				'mt_shipping'              => $mt_shipping,
				'mt_handling'              => $mt_handling,
				'mt_ticket_handling'       => $mt_ticket_handling,
				'mt_hide_empty_short_cart' => $mt_hide_empty_short_cart,
				'mt_ticketing'             => $mt_ticketing,
				'mt_ticket_type_default'   => $mt_ticket_type_default,
				'mt_shipping_time'         => $mt_shipping_time,
				'mt_tickets_close_value'   => $close_value,
				'mt_tickets_close_type'    => $close_type,
				'mt_display_remaining'     => $mt_display_remaining,
				'mt_show_closed'           => $mt_show_closed,
				'mt_singular'              => $mt_singular,
				'mt_inventory'             => $mt_inventory,
				'mt_ticket_image'          => $mt_ticket_image,
				'mt_hide_remaining'        => $mt_hide_remaining,
				'mt_hide_remaining_limit'  => $mt_hide_remaining_limit,
				'mt_collect_shipping'      => $mt_collect_shipping,
			),
			$_POST
		);
		$settings = array_merge( get_option( 'mt_settings', array() ), $settings );
		update_option( 'mt_settings', $settings );
		/**
		 * Filter updated settings messages appended to the 'My Tickets Ticketing Defaults saved' message.
		 *
		 * @hook mt_ticketing_update_settings
		 *
		 * @param {string} $messages Text string with updated settings messages. Default empty string.
		 * @param {array}  $post Array of settings passed to function.
		 *
		 * @return {string}
		 */
		$messages = apply_filters( 'mt_ticketing_update_settings', '', $post );

		return '<div class="updated"><p><strong>' . __( 'My Tickets Ticketing Defaults saved', 'my-tickets' ) . "</strong></p>$messages</div>";
	}

	return false;
}

/**
 * Form to update ticketing settings.
 */
function mt_ticketing_settings() {
	$response = mt_update_ticketing_settings( $_POST );
	$options  = mt_get_settings();
	?>
	<div class="wrap my-tickets" id="mt_settings">
		<div id="icon-options-general" class="icon32"><br/></div>
		<h1 class="hndle"><?php _e( 'Event Ticket Settings', 'my-tickets' ); ?></h1>
		<?php echo wp_kses_post( $response ); ?>
		<div class="postbox-container jcd-wide">
			<div class="metabox-holder">
				<form method="post" action="<?php echo admin_url( 'admin.php?page=mt-ticketing' ); ?>">
					<div><input type="hidden" name="_wpnonce" value="<?php echo wp_create_nonce( 'my-tickets' ); ?>"/>
					</div>
					<div class="ui-sortable meta-box-sortables">
						<div class="postbox">
							<h2 id="mt-ticketing-options" class="hndle"><?php _e( 'Global Ticketing Options', 'my-tickets' ); ?></h2>

							<div class="inside">
								<?php
								/**
								 *  Insert additional settings fields at top of global ticketing options.
								 *
								 * @hook mt_ticketing_settings_fields
								 *
								 * @param {string} $fields HTML output of settings fields. Default empty.
								 * @param {array} $options Array of option keys and values.
								 *
								 * @return {string}
								 */
								echo apply_filters( 'mt_ticketing_settings_fields', '', $options );
								?>
								<?php
								/**
								 * Filter ticketing options available.
								 *
								 * @hook mt_registration_tickets_options
								 *
								 * @param {array} $options Array of available ticket types.
								 *
								 * @return {array}
								 */
								$mt_ticketing = apply_filters(
									'mt_registration_tickets_options',
									array(
										'printable' => __( 'Printable', 'my-tickets' ),
										'eticket'   => __( 'E-tickets', 'my-tickets' ),
										'postal'    => __( 'Postal Mail', 'my-tickets' ),
										'willcall'  => __( 'Pick up at box office', 'my-tickets' ),
									)
								);
								$ticketing    = $options['mt_ticketing'];
								$form         = '<fieldset><legend>' . __( 'Available Ticket Types', 'my-tickets' ) . "</legend><ul class='ticket-type checkboxes'>";
								foreach ( $mt_ticketing as $type => $label ) {
									$checked = ( in_array( $type, array_keys( $ticketing ), true ) ) ? ' checked="checked"' : '';
									$form   .= "<li><input name='mt_ticketing[$type]' id='mt_tickets_$type' type='checkbox' value='" . stripslashes( ( $label ) ) . "' $checked /> <label for='mt_tickets_$type'>$label</label></li>";
								}
								$form                  .= '</ul></fieldset>';
								$form                  .= '
									<p>
										<label for="mt_ticket_type_default">' . __( 'Default ticket type', 'my-tickets' ) . '</label>
										<select name="mt_ticket_type_default" id="mt_ticket_type_default">';
								$mt_ticket_type_default = isset( $options['mt_ticket_type_default'] ) ? $options['mt_ticket_type_default'] : '';
								foreach ( $mt_ticketing as $type => $label ) {
									$selected = selected( $type, $mt_ticket_type_default, false );
									$form    .= "<option value='$type'$selected>$label</option>";
								}
								$form                    .= '</select></p>';
								$form                    .= '<fieldset><legend>' . __( 'Ticket Shipping', 'my-tickets' ) . '</legend>';
								$shipping                 = $options['mt_shipping'];
								$form                    .= "<p class='shipping'><label for='mt_shipping'>" . __( 'Shipping Cost for Postal Mail', 'my-tickets' ) . "</label> <input name='mt_shipping' id='mt_shipping' type='text' size='4' value='$shipping' /></p>";
								$shipping_time            = $options['mt_shipping_time'];
								$form                    .= "<p class='shipping'><label for='mt_shipping_time'>" . __( 'Approximate Shipping Time for Postal Mail (days)', 'my-tickets' ) . "</label> <input name='mt_shipping_time' id='mt_shipping_time' type='text' value='$shipping_time' /></p>";
								$mt_collect_shipping      = ( isset( $options['mt_collect_shipping'] ) ) ? $options['mt_collect_shipping'] : 'false';
								$form                    .= "<p class='handling ticket-collect-shipping'><input name='mt_collect_shipping' id='mt_collect_shipping' type='checkbox' value='true'" . checked( $mt_collect_shipping, 'true', false ) . " /> <label for='mt_collect_shipping'>" . __( 'Always collect shipping address', 'my-tickets' ) . '</label></p>';
								$form                    .= '</fieldset>';
								$form                    .= '<fieldset><legend>' . __( 'Administrative Fees', 'my-tickets' ) . '</legend>';
								$handling                 = ( isset( $options['mt_handling'] ) ) ? $options['mt_handling'] : '';
								$form                    .= "<p class='handling cart-handling'><label for='mt_handling'>" . __( 'Handling/Administrative Fee (per Cart)', 'my-tickets' ) . "</label> <input name='mt_handling' id='mt_handling' type='text' size='4' value='$handling' /></p>";
								$ticket_handling          = ( isset( $options['mt_ticket_handling'] ) ) ? $options['mt_ticket_handling'] : '';
								$form                    .= "<p class='handling ticket-handling'><label for='mt_ticket_handling'>" . __( 'Handling/Administrative Fee (per Ticket)', 'my-tickets' ) . "</label> <input name='mt_ticket_handling' id='mt_ticket_handling' type='text' size='4' value='$ticket_handling' /></p>";
								$form                    .= '</fieldset>';
								$form                    .= '<fieldset><legend>' . __( 'Display & sales limits', 'my-tickets' ) . '</legend>';
								$mt_tickets_close_value   = ( isset( $options['mt_tickets_close_value'] ) ) ? $options['mt_tickets_close_value'] : '';
								$form                    .= "<p class='handling ticket-close-value'><label for='mt_tickets_close_value'>" . __( 'Tickets reserved for sale at the door', 'my-tickets' ) . "</label> <input name='mt_tickets_close_value' id='mt_tickets_close_value' type='number' size='4' value='$mt_tickets_close_value' /></p>";
								$mt_tickets_close_type    = ( isset( $options['mt_tickets_close_type'] ) ) ? $options['mt_tickets_close_type'] : '';
								$form                    .= "<p class='close ticket-close-type'><label for='mt_tickets_close_type'>" . __( 'Reserve tickets based on', 'my-tickets' ) . "</label> <select name='mt_tickets_close_type' id='mt_tickets_close_type' />
											<option value='integer'" . selected( $mt_tickets_close_type, 'integer', false ) . '>' . __( 'Specific number of tickets', 'my-tickets' ) . "</option>
											<option value='percent'" . selected( $mt_tickets_close_type, 'percent', false ) . '>' . __( 'Percentage of available tickets', 'my-tickets' ) . '</option>
										</select></p>';
								$mt_show_closed           = ( isset( $options['mt_show_closed'] ) ) ? $options['mt_show_closed'] : 'false';
								$form                    .= "<p class='handling ticket-show-closed'><input name='mt_show_closed' id='mt_tickets_show_closed' type='checkbox' value='true'" . checked( $mt_show_closed, 'true', false ) . " /> <label for='mt_tickets_show_closed'>" . __( 'Show ticket types that are closed in Add to Cart form', 'my-tickets' ) . '</label></p>';
								$mt_singular              = ( isset( $options['mt_singular'] ) ) ? $options['mt_singular'] : 'false';
								$form                    .= "<p class='handling ticket-singular-only'><input name='mt_singular' id='mt_tickets_singular' type='checkbox' value='true'" . checked( $mt_singular, 'true', false ) . " /> <label for='mt_tickets_singular'>" . __( 'Only show Add to Cart form on singular pages', 'my-tickets' ) . '</label></p>';
								$mt_hide_remaining        = ( isset( $options['mt_hide_remaining'] ) ) ? $options['mt_hide_remaining'] : 'false';
								$form                    .= "<p class='handling ticket-hide-remaining'><input name='mt_hide_remaining' id='mt_tickets_hide_remaining' type='checkbox' value='true'" . checked( $mt_hide_remaining, 'true', false ) . " /> <label for='mt_tickets_hide_remaining'>" . __( 'Hide number of tickets remaining', 'my-tickets' ) . '</label></p>';
								$mt_hide_remaining_limit  = ( isset( $options['mt_hide_remaining_limit'] ) ) ? $options['mt_hide_remaining_limit'] : 0;
								$form                    .= "<p class='handling ticket-hide-remaining-limit'><label for='mt_tickets_hide_remaining_limit'>" . __( 'Show number of tickets remaining when available tickets falls below:', 'my-tickets' ) . "</label> <input name='mt_hide_remaining_limit' id='mt_tickets_hide_remaining_limit' type='number' value='" . $mt_hide_remaining_limit . "' /></p>";
								$mt_inventory             = $options['mt_inventory'];
								$form                    .= "<p class='handling ticket-inventory'><input name='mt_inventory' id='mt_inventory' type='checkbox' value='virtual'" . checked( $mt_inventory, 'virtual', false ) . " /> <label for='mt_inventory'>" . __( 'Decrease inventory when tickets are added to cart', 'my-tickets' ) . '</label></p>';
								$mt_display_remaining     = ( isset( $options['mt_display_remaining'] ) ) ? $options['mt_display_remaining'] : 'proportion';
								$form                    .= "<p class='handling ticket-display-remaining'><label for='mt_tickets_display_remaining'>" . __( 'Remaining tickets display type', 'my-tickets' ) . "</label> <select name='mt_display_remaining' id='mt_tickets_display_remaining' />
									<option value='proportion'" . selected( $mt_display_remaining, 'proportion', false ) . '>' . __( 'Available/total, e.g. 23/40', 'my-tickets' ) . "</option>
									<option value='number'" . selected( $mt_display_remaining, 'number', false ) . '>' . __( 'Available only, e.g. 23', 'my-tickets' ) . '</option>
								</select></p>';
								$form                    .= '</fieldset>';
								$form                    .= '<fieldset><legend>' . __( 'Miscellaneous', 'my-tickets' ) . '</legend>';
								$mt_ticket_image          = ( isset( $options['mt_ticket_image'] ) ) ? $options['mt_ticket_image'] : '';
								$form                    .= "<p class='image ticket-image-type'>
									<label for='mt_ticket_image'>" . __( 'Image shown on tickets', 'my-tickets' ) . "</label>
									<select name='mt_ticket_image' id='mt_ticket_image' />
										<option value='ticket'" . selected( $mt_ticket_image, 'ticket', false ) . '>' . __( 'Featured image on Ticket Page', 'my-tickets' ) . "</option>
										<option value='event'" . selected( $mt_ticket_image, 'event', false ) . '>' . __( 'Featured image for Event', 'my-tickets' ) . '</option>' .
										apply_filters( 'mt_custom_ticket_image_option', '' ) . '
									</select>
								</p>';
								$mt_hide_empty_short_cart = ( isset( $options['mt_hide_empty_short_cart'] ) ) ? $options['mt_hide_empty_short_cart'] : 'false';
								$form                    .= "<p class='handling ticket-hide-empty-short-cart'><input name='mt_hide_empty_short_cart' id='mt_hide_empty_short_cart' type='checkbox' value='true'" . checked( $mt_hide_empty_short_cart, 'true', false ) . " /> <label for='mt_hide_empty_short_cart'>" . __( 'Hide short cart widget when empty', 'my-tickets' ) . '</label></p>';
								$form                    .= '</fieldset>';
								echo wp_kses( $form, mt_kses_elements() );
								?>
							</div>
						</div>
					</div>

					<div class="ui-sortable meta-box-sortables">
						<div class="postbox">
							<h2 id="mt-ticketing-options" class="hndle"><?php _e( 'Default Ticket Settings', 'my-tickets' ); ?></h2>
							<div class="inside">
								<p>
									<?php _e( 'Changing these settings does not impact events that have already been created.', 'my-tickets' ); ?>
								</p>
								<fieldset>
									<legend><?php _e( 'Default ticket model', 'my-tickets' ); ?></legend>
									<ul class="checkboxes">
										<li><input type="radio" name="default_model" id="default_model_continuous" value="continuous" <?php checked( $options['default_model'], 'continuous' ); ?> /> <label for="default_model_continuous"><?php _e( 'Audience Types', 'my-tickets' ); ?></label></li>
										<li><input type="radio" name="default_model" id="default_model_discrete" value="discrete" <?php checked( $options['default_model'], 'discrete' ); ?>/> <label for="default_model_discrete"><?php _e( 'Seating Sections', 'my-tickets' ); ?></label></li>
										<li><input type="radio" name="default_model" id="default_model_event" value="event" <?php checked( $options['default_model'], 'event' ); ?>/> <label for="default_model_event"><?php _e( 'Event', 'my-tickets' ); ?></label></li>
									</ul>
								</fieldset>
							<?php
							$ticket_models = array(
								'discrete'   => __( 'Seating Sections', 'my-tickets' ),
								'continuous' => __( 'Audience Types', 'my-tickets' ),
								'event'      => __( 'Event', 'my-tickets' ),
							);
							$tabs          = '';
							foreach ( $ticket_models as $model => $label ) {
								$tabs .= "<li><a href='#$model'>" . $label . '</a></li>';
							}
							?>
							<div class='mt-tabs'>
								<ul class='tabs'>
									<?php echo $tabs; ?>
								</ul>
								<?php
								foreach ( $ticket_models as $model => $label ) {
									$displayed = $options['defaults'][ $model ];
									$multiple  = ( isset( $displayed['multiple'] ) && 'true' === $displayed['multiple'] ) ? true : false;
									$type      = $displayed['sales_type'];
									if ( ! $type || 'tickets' === $type ) {
										$is_tickets      = true;
										$is_registration = false;
									} else {
										$is_tickets      = false;
										$is_registration = true;
									}
									$method = $displayed['counting_method'];
									?>
									<div class='wptab mt_<?php echo $model; ?>' id='<?php echo $model; ?>'>
										<div class="mt-flex">
											<div class="ticket-sale-expiration">
												<p>
													<label for='reg_expires_<?php echo $model; ?>'><?php _e( 'Stop online sales <em>x</em> hours before event', 'my-tickets' ); ?></label>
													<input type='number' name='defaults[<?php echo $model; ?>][reg_expires]' id='reg_expires_<?php echo $model; ?>' value='<?php echo stripslashes( esc_attr( $displayed['reg_expires'] ) ); ?>'/>
												</p>
												<p>
													<label for='multiple_<?php echo $model; ?>'><?php _e( 'Allow multiple tickets/ticket type per purchaser', 'my-tickets' ); ?></label>
													<input type='checkbox' name='defaults[<?php echo $model; ?>][multiple]' id='multiple_<?php echo $model; ?>' value='true' <?php echo ( $multiple ) ? ' checked="checked"' : ''; ?> />
												</p>
											</div>
											<div class="ticket-sale-types">
												<fieldset>
													<legend><?php _e( 'Type of Sale', 'my-tickets' ); ?></legend>
													<p>
														<input type='radio' name='defaults[<?php echo $model; ?>][sales_type]' id='mt_sales_type_tickets_<?php echo $model; ?>' value='tickets'<?php checked( $is_tickets, true ); ?> />
														<label for='mt_sales_type_tickets_<?php echo $model; ?>'><?php _e( 'Ticket Sales', 'my-tickets' ); ?></label><br/>
														<input type='radio' name='defaults[<?php echo $model; ?>][sales_type]' id='mt_sales_type_registration_<?php echo $model; ?>' value='registration'<?php checked( $is_registration, true ); ?> />
														<label for='mt_sales_type_registration_<?php echo $model; ?>'><?php _e( 'Event Registration', 'my-tickets' ); ?></label>
													</p>
												</fieldset>
												<input type="hidden" name='defaults[<?php echo $model; ?>][counting_method]' value='<?php echo esc_attr( $method ); ?>' />
											</div>
										</div>
										<?php echo mt_prices_table( $displayed, $model ); ?>
									</div>
									<?php
								}
								?>
								</div>
							</div>
						</div>
					</div>
					<p class="mt-save-settings"><input type="submit" name="mt-ticketing-settings" class="button-primary" value="<?php _e( 'Save Ticket Defaults', 'my-tickets' ); ?>"/></p>
				</form>
			</div>
		</div>
		<?php mt_show_support_box(); ?>
	</div>
	<?php
	// creates settings page for My tickets.
}
