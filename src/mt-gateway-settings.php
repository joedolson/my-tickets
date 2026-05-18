<?php
/**
 * Payment related settings
 *
 * @category Settings
 * @package  My Tickets
 * @author   Joe Dolson
 * @license  GPLv3
 * @link     https://www.joedolson.com/my-tickets/
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
} // Exit if accessed directly.

/**
 * Update settings for payments.
 *
 * @param array $post POST data.
 *
 * @return string
 */
function mt_update_gateway_settings( $post ) {
	if ( isset( $post['mt-payment-settings'] ) ) {
		$nonce = sanitize_text_field( $post['_wpnonce'] );
		if ( ! wp_verify_nonce( $nonce, 'my-tickets' ) ) {
			return '';
		}
		$mt_ssl             = ( isset( $post['mt_ssl'] ) ) ? 'true' : 'false';
		$mt_use_sandbox     = ( isset( $post['mt_use_sandbox'] ) ) ? 'true' : 'false';
		$mt_default_gateway = ( isset( $post['mt_default_gateway'] ) ) ? $post['mt_default_gateway'] : 'offline';
		$mt_gateway         = ( isset( $post['mt_gateway'] ) ) ? $post['mt_gateway'] : array( 'offline' );
		// if a gateway is set as default that isn't enabled, enable it.
		if ( ! ( in_array( $mt_default_gateway, $mt_gateway, true ) ) ) {
			$mt_gateway[] = $mt_default_gateway;
		}
		$mt_gateways = ( isset( $post['mt_gateways'] ) ) ? $post['mt_gateways'] : array();

		$settings = array(
			'mt_use_sandbox'      => $mt_use_sandbox,
			'mt_ssl'              => $mt_ssl,
			'mt_gateway'          => $mt_gateway,
			'mt_default_gateway'  => $mt_default_gateway,
			'mt_gateways'         => $mt_gateways,
		);
		/**
		 * Filter My Tickets gateway settings before saving.
		 *
		 * @hook mt_gateway_settings
		 *
		 * @param array $settings Settings after changes provided by admin but before saving.
		 * @param array $post     $_POST data.
		 *
		 * @return array
		 */
		$settings = apply_filters( 'mt_gateway_settings', $settings, $post );
		$settings = array_merge( get_option( 'mt_settings', array() ), $settings );
		update_option( 'mt_settings', $settings );
		/**
		 * Filter message appended to gateway settings updated notification.
		 *
		 * @hook mt_gateway_update_settings
		 *
		 * @param string $messages HTML output of messages.
		 * @param array  $post POST data.
		 *
		 * @return string
		 */
		$messages = apply_filters( 'mt_gateway_update_settings', '', $post );

		return '<div class="updated"><p><strong>' . __( 'My Tickets Payment Gateway Settings saved', 'my-tickets' ) . "</strong></p>$messages</div>";
	}

	return '';
}

/**
 * Payment settings form.
 */
function mt_gateway_settings() {
	$notes = isset( $_POST['mt_gateways']['offline']['notes'] ) ? wp_kses_post( $_POST['mt_gateways']['offline']['notes'] ) : '';
	$post  = map_deep( $_POST, 'sanitize_textarea_field' );
	// The notes field supports HTML, so uses a separate sanitization.
	$post['mt_gateways']['offline']['notes'] = $notes;

	$response     = mt_update_gateway_settings( $post );
	$options      = mt_get_settings();
	$alert        = '';
	$testing_mode = ( 'true' === $options['mt_use_sandbox'] ) ? true : false;
	if ( $testing_mode ) {
		$alert = "<div class='notice updated'><p>" . __( 'Currently in testing mode. Use sandbox accounts when testing payment gateways.', 'my-tickets' ) . '</p></div>';
	}
	?>
	<div class="wrap my-tickets" id="mt_gateway_settings">
		<h1><?php _e( 'Payment Gateway Settings', 'my-tickets' ); ?></h1>
		<?php echo wp_kses_post( $response ); ?>
		<?php echo wp_kses_post( $alert ); ?>
		<div class="postbox-container jcd-wide">
			<div class="metabox-holder">
				<form method="post" action="<?php echo esc_url( admin_url( 'admin.php?page=mt-gateway' ) ); ?>">
					<div><input type="hidden" name="_wpnonce" value="<?php echo esc_attr( wp_create_nonce( 'my-tickets' ) ); ?>"/>
					</div>
					<div class="ui-sortable meta-box-sortables">
						<div class="postbox">
							<h2 class="hndle"><?php _e( 'Payment Gateways', 'my-tickets' ); ?></h2>

							<div class="inside">
								<?php
								$default_selector = '';
								$pg_tabs          = '';
								$payment_gateways = '';
								$mt_gateways      = mt_setup_gateways();
								foreach ( $mt_gateways as $gateway => $fields ) {
									$pg_settings       = '';
									$gateway_enabled   = ( in_array( $gateway, $options['mt_gateway'], true ) ) ? ' checked="checked"' : '';
									$default_selector .= "
									<li>
										<input type='checkbox' id='mt_gateway_$gateway' name='mt_gateway[]' value='$gateway'" . $gateway_enabled . " /> <label for='mt_gateway_$gateway'>$fields[label]</label>
									</li>";
									$handling_fees     = array(
										'type'  => 'checkbox',
										'label' => __( 'Ignore cart-wide ticket handling fees with this gateway.', 'my-tickets' ),
										'value' => 'true',
									);

									$fields['fields']['mt_handling'] = $handling_fees;
									$settings                        = isset( $fields['fields'] ) ? $fields['fields'] : false;
									if ( $settings ) {
										foreach ( $settings as $key => $label ) {
											if ( is_array( $label ) ) {
												$input_type    = $label['type'];
												$text_label    = $label['label'];
												$default_value = isset( $label['value'] ) ? $label['value'] : '';
												$description   = isset( $label['description'] ) ? $label['description'] : '';
												$describedby   = '';
												$describing    = '';
												if ( $description ) {
													$describedby = ' aria-describedby="' . $key . '_description"';
													$describing  = '<span id="' . $key . '_description" class="aria-description"><span class="dashicons dashicons-info" aria-hidden="true"></span> ' . $description . '</span>';
												}
												$value   = ( ! empty( $options['mt_gateways'][ $gateway ][ $key ] ) ) ? $options['mt_gateways'][ $gateway ][ $key ] : $default_value;
												$checked = ( 'checkbox' === $input_type && ( isset( $options['mt_gateways'][ $gateway ][ $key ] ) && $options['mt_gateways'][ $gateway ][ $key ] === $label['value'] ) ) ? 'checked="checked"' : '';
												if ( 'checkbox' === $input_type ) {
													// Checkboxes with empty values will stay unchecked on save.
													$pg_settings .= "<li class='$input_type'><div><input type='$input_type' name='mt_gateways[$gateway][$key]' id='mt_$gateway-$key' size='60' value='" . stripslashes( esc_attr( $value ) ) . "' $checked $describedby /> <label for='mt_$gateway-$key'>$text_label</label></div>$describing</li>";
												} elseif ( 'textarea' === $input_type ) {
													$pg_settings .= "<li class='$input_type'><div><label for='mt_$gateway-$key'>$text_label</label> <textarea cols='60' rows='4' class='widefat' name='mt_gateways[$gateway][$key]' id='mt_$gateway-$key' size='60' $describedby >" . stripslashes( esc_textarea( $value ) ) . "</textarea></div>$describing</li>";
												} else {
													$pg_settings .= "<li class='$input_type'><div><label for='mt_$gateway-$key'>$text_label</label> <input class='widefat' type='$input_type' name='mt_gateways[$gateway][$key]' id='mt_$gateway-$key' size='60' value='" . stripslashes( esc_attr( $value ) ) . "' $describedby /></div>$describing</li>";
												}
											} else {
												$value        = ( ! empty( $options['mt_gateways'][ $gateway ][ $key ] ) ) ? $options['mt_gateways'][ $gateway ][ $key ] : '';
												$input_type   = ( str_contains( $key, '_secret' ) || str_contains( $key, 'key' ) && '' !== trim( $value ) ) ? 'password' : 'text';
												$pg_settings .= "<li class='textfield'><div><label for='mt_$gateway-$key'>$label</label> <input type='$input_type' name='mt_gateways[$gateway][$key]' id='mt_$gateway-$key' size='60' value='" . stripslashes( esc_attr( $value ) ) . "' /></div></li>";
											}
										}
									}
									$notes = ( isset( $fields['note'] ) ) ? '<p>' . wp_kses(
										$fields['note'],
										array(
											'strong' => array(),
											'code'   => array(),
											'em'     => array(),
											'a'      => array( 'href' ),
										)
									) . '</p>' : '';
									// Translators: Gateway settings.
									$pg_tabs          .= "<li><button id='tab_mt_" . $gateway . "' role='tab' type='button' aria-selected='false' aria-controls='$gateway'>" . sprintf( __( '%s settings', 'my-tickets' ), $fields['label'] ) . '</button></li>';
									$checked           = ( mt_is_checked( 'mt_default_gateway', $gateway, $options ) ) ? ' checked="checked"' : '';
									$payment_gateways .= "
									<div class='wptab mt_$gateway' id='$gateway' role='tabpanel' aria-labelledby='tab_mt_" . $gateway . "'>
									<fieldset>
										<legend>$fields[label]</legend>
										<p><input type='radio' name='mt_default_gateway' id='mt_default_gateway_$gateway' value='$gateway'" . $checked . " /> <label for='mt_default_gateway_$gateway'>" . __( 'Default gateway', 'my-tickets' ) . "</label></p>
											$pg_settings
											$notes
									</fieldset>
									</div>";
								}
								echo wp_kses(
									'<fieldset><legend>' . __( 'Enabled Payment Gateways', 'my-tickets' ) . "</legend><ul class='checkboxes'>$default_selector</ul></fieldset>
								<div class='mt-tabs mt-payments'>
									<ul class='tabs' role='tablist'>
										$pg_tabs
									</ul>
									$payment_gateways
								</div>",
									mt_kses_elements()
								);
								?>
								<p>
									<input type="checkbox" id="mt_use_sandbox" name="mt_use_sandbox" <?php checked( true, mt_is_checked( 'mt_use_sandbox', 'true', $options ) ); ?> />
									<label for="mt_use_sandbox"><?php _e( 'Testing mode (no payments will be processed)', 'my-tickets' ); ?></label>
								</p>
								<p>
									<input type="checkbox" id="mt_ssl" name="mt_ssl" value="true" aria-describedby="mt_ssl_note" <?php checked( true, mt_is_checked( 'mt_ssl', 'true', $options ) ); ?> />
									<label for="mt_ssl"><?php _e( 'Use SSL for Payment pages.', 'my-tickets' ); ?></label><br/>
									<span id="mt_ssl_note"><?php _e( 'You must have an SSL certificate to use this option', 'my-tickets' ); ?></span>
								</p>
							</div>
						</div>
					</div>
					<p class="mt-save-settings"><input type="submit" name="mt-payment-settings" class="button-primary" value="<?php _e( 'Save Payment Settings', 'my-tickets' ); ?>"/></p>
				</form>
			</div>
		</div>
		<?php mt_show_support_box(); ?>
	</div>
	<?php
	// creates settings page for My tickets.
}
