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
		$mt_handling             = ( isset( $post['mt_handling'] ) ) ? $post['mt_handling'] : 0;
		$mt_ticket_handling      = ( isset( $post['mt_ticket_handling'] ) ) ? $post['mt_ticket_handling'] : 0;
		$mt_shipping             = ( isset( $post['mt_shipping'] ) ) ? $post['mt_shipping'] : 0;
		$mt_ticketing            = ( isset( $post['mt_ticketing'] ) ) ? $post['mt_ticketing'] : array();
		$mt_ticket_type_default  = ( isset( $post['mt_ticket_type_default'] ) ) ? $post['mt_ticket_type_default'] : '';
		$mt_total_tickets        = ( isset( $post['mt_tickets_total'] ) ) ? $post['mt_tickets_total'] : 'inherit';
		$mt_shipping_time        = ( isset( $post['mt_shipping_time'] ) ) ? $post['mt_shipping_time'] : '3-5';
		$mt_hide_remaining       = ( isset( $post['mt_hide_remaining'] ) ) ? 'true' : 'false';
		$mt_hide_remaining_limit = ( isset( $post['mt_hide_remaining_limit'] ) ) ? intval( $post['mt_hide_remaining_limit'] ) : 0;
		$mt_collect_shipping     = ( isset( $post['mt_collect_shipping'] ) ) ? 'true' : 'false';
		$defaults                = ( isset( $post['defaults'] ) ) ? $post['defaults'] : array();
		$labels                  = ( isset( $post['mt_label'] ) ) ? $post['mt_label'] : array();
		$prices                  = ( isset( $post['mt_price'] ) ) ? $post['mt_price'] : array();
		$close                   = ( isset( $post['mt_close'] ) ) ? $post['mt_close'] : array();
		$availability            = ( isset( $post['mt_tickets'] ) ) ? $post['mt_tickets'] : array();
		$close_value             = ( isset( $post['mt_tickets_close_value'] ) ) ? $post['mt_tickets_close_value'] : '';
		$close_type              = ( isset( $post['mt_tickets_close_type'] ) ) ? $post['mt_tickets_close_type'] : 'integer';
		$mt_ticket_image         = ( isset( $post['mt_ticket_image'] ) ) ? $post['mt_ticket_image'] : 'ticket';
		$pricing_array           = mt_setup_pricing( $labels, $prices, $availability, $close );
		$defaults['pricing']     = $pricing_array;
		$defaults['tickets']     = $mt_total_tickets;
		$defaults['multiple']    = ( isset( $post['defaults']['multiple'] ) ) ? $post['defaults']['multiple'] : '';

		$settings = apply_filters(
			'mt_settings',
			array(
				'defaults'                => $defaults,
				'mt_shipping'             => $mt_shipping,
				'mt_handling'             => $mt_handling,
				'mt_ticket_handling'      => $mt_ticket_handling,
				'mt_ticketing'            => $mt_ticketing,
				'mt_ticket_type_default'  => $mt_ticket_type_default,
				'mt_shipping_time'        => $mt_shipping_time,
				'mt_tickets_close_value'  => $close_value,
				'mt_tickets_close_type'   => $close_type,
				'mt_ticket_image'         => $mt_ticket_image,
				'mt_hide_remaining'       => $mt_hide_remaining,
				'mt_hide_remaining_limit' => $mt_hide_remaining_limit,
				'mt_collect_shipping'     => $mt_collect_shipping,
			),
			$_POST
		);
		$settings = array_merge( get_option( 'mt_settings', array() ), $settings );
		update_option( 'mt_settings', $settings );
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
	$options  = ( ! is_array( get_option( 'mt_settings' ) ) ) ? array() : get_option( 'mt_settings' );
	$defaults = mt_default_settings();
	$options  = array_merge( $defaults, $options );
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
									echo apply_filters( 'mt_ticketing_settings_fields', '', $options );
								?>
								<?php
								// array of ticket options. Need to also be registered as ticket action.
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
								$form         = '<fieldset><legend>' . __( 'Available Ticket Types', 'my-calendar' ) . "</legend><ul class='ticket-type checkboxes'>";
								foreach ( $mt_ticketing as $type => $label ) {
									$checked = ( in_array( $type, array_keys( $ticketing ), true ) ) ? ' checked="checked"' : '';
									$form   .= "<li><label for='mt_tickets_$type'>$label</label> <input name='mt_ticketing[$type]' id='mt_tickets_$type' type='checkbox' value='" . stripslashes( ( $label ) ) . "' $checked /></li>";
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
								$form .= '</select></p>';
								// only show shipping field if postal mail ticket is selected.
								$shipping                = $options['mt_shipping'];
								$form                   .= "<p class='shipping'><label for='mt_shipping'>" . __( 'Shipping Cost for Postal Mail', 'my-tickets' ) . "</label> <input name='mt_shipping' id='mt_shipping' type='text' size='4' value='$shipping' /></p>";
								$shipping_time           = $options['mt_shipping_time'];
								$form                   .= "<p class='shipping'><label for='mt_shipping_time'>" . __( 'Approximate Shipping Time for Postal Mail (days)', 'my-tickets' ) . "</label> <input name='mt_shipping_time' id='mt_shipping_time' type='text' value='$shipping_time' /></p>";
								$mt_collect_shipping     = ( isset( $options['mt_collect_shipping'] ) ) ? $options['mt_collect_shipping'] : 'false';
								$form                   .= "<p class='handling ticket-collect-shipping'><label for='mt_collect_shipping'>" . __( 'Always collect shipping address', 'my-tickets' ) . "</label> <input name='mt_collect_shipping' id='mt_collect_shipping' type='checkbox' value='true'" . checked( $mt_collect_shipping, 'true', false ) . ' /></p>';
								$handling                = ( isset( $options['mt_handling'] ) ) ? $options['mt_handling'] : '';
								$form                   .= "<p class='handling cart-handling'><label for='mt_handling'>" . __( 'Handling/Administrative Fee (per Cart)', 'my-tickets' ) . "</label> <input name='mt_handling' id='mt_handling' type='text' size='4' value='$handling' /></p>";
								$ticket_handling         = ( isset( $options['mt_ticket_handling'] ) ) ? $options['mt_ticket_handling'] : '';
								$form                   .= "<p class='handling ticket-handling'><label for='mt_ticket_handling'>" . __( 'Handling/Administrative Fee (per Ticket)', 'my-tickets' ) . "</label> <input name='mt_ticket_handling' id='mt_ticket_handling' type='text' size='4' value='$ticket_handling' /></p>";
								$mt_tickets_close_value  = ( isset( $options['mt_tickets_close_value'] ) ) ? $options['mt_tickets_close_value'] : '';
								$form                   .= "<p class='handling ticket-close-value'><label for='mt_tickets_close_value'>" . __( 'Tickets reserved for sale at the door', 'my-tickets' ) . "</label> <input name='mt_tickets_close_value' id='mt_tickets_close_value' type='number' size='4' value='$mt_tickets_close_value' /></p>";
								$mt_hide_remaining       = ( isset( $options['mt_hide_remaining'] ) ) ? $options['mt_hide_remaining'] : 'false';
								$form                   .= "<p class='handling ticket-hide-remaining'><label for='mt_tickets_hide_remaining'>" . __( 'Hide number of tickets remaining', 'my-tickets' ) . "</label> <input name='mt_hide_remaining' id='mt_tickets_hide_remaining' type='checkbox' value='true'" . checked( $mt_hide_remaining, 'true', false ) . ' /></p>';
								$mt_hide_remaining_limit = ( isset( $options['mt_hide_remaining_limit'] ) ) ? $options['mt_hide_remaining_limit'] : 0;
								$form                   .= "<p class='handling ticket-hide-remaining-limit'><label for='mt_tickets_hide_remaining_limit'>" . __( 'Show number of tickets remaining when available tickets falls below:', 'my-tickets' ) . "</label> <input name='mt_hide_remaining_limit' id='mt_tickets_hide_remaining_limit' type='number' value='" . $mt_hide_remaining_limit . "' /></p>";
								$mt_tickets_close_type   = ( isset( $options['mt_tickets_close_type'] ) ) ? $options['mt_tickets_close_type'] : '';
								$form                   .= "<p class='close ticket-close-type'><label for='mt_tickets_close_type'>" . __( 'Reserve tickets based on', 'my-tickets' ) . "</label><select name='mt_tickets_close_type' id='mt_tickets_close_type' />
											<option value='integer'" . selected( $mt_tickets_close_type, 'integer', false ) . '>' . __( 'Specific number of tickets', 'my-tickets' ) . "</option>
											<option value='percent'" . selected( $mt_tickets_close_type, 'percent', false ) . '>' . __( 'Percentage of available tickets', 'my-tickets' ) . '</option>
										</select></p>';
								$mt_ticket_image         = ( isset( $options['mt_ticket_image'] ) ) ? $options['mt_ticket_image'] : '';
								$form                   .= "<p class='image ticket-image-type'>
									<label for='mt_ticket_image'>" . __( 'Image shown on tickets', 'my-tickets' ) . "</label>
									<select name='mt_ticket_image' id='mt_ticket_image' />
										<option value='ticket'" . selected( $mt_ticket_image, 'ticket', false ) . '>' . __( 'Featured image on Ticket Page', 'my-tickets' ) . "</option>
										<option value='event'" . selected( $mt_ticket_image, 'event', false ) . '>' . __( 'Featured image for Event', 'my-tickets' ) . '</option>' .
										apply_filters( 'mt_custom_ticket_image_option', '' ) . '
									</select>
								</p>';
								echo wp_kses( $form, mt_kses_elements() );
								$multiple = ( isset( $options['defaults']['multiple'] ) && 'true' === $options['defaults']['multiple'] ) ? true : false;
								?>
							</div>
						</div>
					</div>

					<div class="ui-sortable meta-box-sortables">
						<div class="postbox">
							<h2 id="mt-ticketing-options" class="hndle"><?php _e( 'Default Ticket Settings', 'my-tickets' ); ?></h2>

							<div class="inside">
									<p>
										<em><?php _e( 'Changing these settings does not impact events that have already been created.', 'my-tickets' ); ?></em>
									</p>
									<p>
										<label for='reg_expires'><?php _e( 'Stop online sales <em>x</em> hours before event', 'my-tickets' ); ?></label>
										<input type='number' name='defaults[reg_expires]' id='reg_expires' value='<?php stripslashes( esc_attr( $options['defaults']['reg_expires'] ) ); ?>'/>
									</p>

									<p>
										<label for='multiple'><?php _e( 'Allow multiple tickets/ticket type per purchaser', 'my-tickets' ); ?></label>
										<input type='checkbox' name='defaults[multiple]' id='multiple' value='true' <?php echo ( $multiple ) ? ' checked="checked"' : ''; ?> />
									</p>
									<?php
									$type = $options['defaults']['sales_type'];
									if ( ! $type || 'tickets' === $type ) {
										$is_tickets      = true;
										$is_registration = false;
									} else {
										$is_tickets      = false;
										$is_registration = true;
									}
									$method = $options['defaults']['counting_method'];
									if ( 'discrete' === $method ) {
										$is_discrete   = true;
										$is_continuous = false;
									} else {
										$is_discrete   = false;
										$is_continuous = true;
									}
									echo mt_prices_table();
									?>
								<div class="ticket-sale-types">
									<fieldset>
										<legend><?php _e( 'Type of Sale', 'my-tickets' ); ?></legend>
										<p>
											<input type='radio' name='defaults[sales_type]' id='mt_sales_type_tickets' value='tickets'<?php checked( $is_tickets, true ); ?> />
											<label for='mt_sales_type_tickets'><?php _e( 'Ticket Sales', 'my-tickets' ); ?></label><br/>
											<input type='radio' name='defaults[sales_type]' id='mt_sales_type_registration' value='registration'<?php checked( $is_registration, true ); ?> />
											<label for='mt_sales_type_registration'><?php _e( 'Event Registration', 'my-tickets' ); ?></label>
										</p>
									</fieldset>
									<fieldset>
										<legend><?php _e( 'Ticket Counting Method', 'my-tickets' ); ?></legend>
										<p>
											<input type='radio' name='defaults[counting_method]' id='mt_counting_method_discrete' value='discrete' <?php checked( $is_discrete, true ); ?> />
											<label for='mt_counting_method_discrete'><?php _e( 'Discrete - (Section A, Section B, etc.)', 'my-tickets' ); ?></label><br/>
											<input type='radio' name='defaults[counting_method]' id='mt_counting_method_continuous' value='continuous'<?php checked( $is_continuous, true ); ?> />
											<label for='mt_counting_method_continuous'><?php _e( 'Continuous - (Adult, Child, Senior)', 'my-tickets' ); ?></label>
										</p>
									</fieldset>
								</div>
							</div>
						</div>
					</div>
					<p><input type="submit" name="mt-ticketing-settings" class="button-primary" value="<?php _e( 'Save Ticket Defaults', 'my-tickets' ); ?>"/></p>
				</form>
			</div>
		</div>
		<?php mt_show_support_box(); ?>
	</div>
	<?php
	// creates settings page for My tickets.
}
