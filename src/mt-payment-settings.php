<?php
/**
 * Payment related settings
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
 * Update settings for payments.
 *
 * @param array $post POST data.
 *
 * @return string
 */
function mt_update_payment_settings( $post ) {
	if ( isset( $post['mt-payment-settings'] ) ) {
		$nonce = $_POST['_wpnonce'];
		if ( ! wp_verify_nonce( $nonce, 'my-tickets' ) ) {
			return '';
		}
		$mt_use_sandbox      = ( isset( $post['mt_use_sandbox'] ) ) ? 'true' : 'false';
		$mt_ssl              = ( isset( $post['mt_ssl'] ) ) ? 'true' : 'false';
		$mt_members_discount = (int) preg_replace( '/\D/', '', $post['mt_members_discount'] );
		$mt_currency         = $post['mt_currency'];
		$mt_dec_point        = ( isset( $post['mt_dec_point'] ) ) ? $post['mt_dec_point'] : '.';
		$mt_thousands_sep    = ( isset( $post['mt_thousands_sep'] ) ) ? $post['mt_thousands_sep'] : ',';
		$mt_phone            = ( isset( $post['mt_phone'] ) ) ? 'on' : 'off';

		$mt_default_gateway = ( isset( $post['mt_default_gateway'] ) ) ? $post['mt_default_gateway'] : 'offline';
		$mt_gateway         = ( isset( $post['mt_gateway'] ) ) ? $post['mt_gateway'] : array( 'offline' );
		// if a gateway is set as default that isn't enabled, enable it.
		if ( ! ( in_array( $mt_default_gateway, $mt_gateway, true ) ) ) {
			$mt_gateway[] = $mt_default_gateway;
		}
		$mt_gateways = ( isset( $post['mt_gateways'] ) ) ? $post['mt_gateways'] : array();

		$mt_purchase_page = (int) $post['mt_purchase_page'];
		$mt_receipt_page  = (int) $post['mt_receipt_page'];
		$mt_tickets_page  = (int) $post['mt_tickets_page'];

		$settings = apply_filters(
			'mt_settings',
			array(
				'mt_use_sandbox'      => $mt_use_sandbox,
				'mt_members_discount' => $mt_members_discount,
				'mt_currency'         => $mt_currency,
				'mt_dec_point'        => $mt_dec_point,
				'mt_thousands_sep'    => $mt_thousands_sep,
				'mt_phone'            => $mt_phone,
				'mt_gateway'          => $mt_gateway,
				'mt_default_gateway'  => $mt_default_gateway,
				'mt_gateways'         => $mt_gateways,
				'mt_ssl'              => $mt_ssl,
				'mt_purchase_page'    => $mt_purchase_page,
				'mt_receipt_page'     => $mt_receipt_page,
				'mt_tickets_page'     => $mt_tickets_page,
			),
			$_POST
		);
		$settings = array_merge( get_option( 'mt_settings' ), $settings );
		update_option( 'mt_settings', $settings );
		$messages = apply_filters( 'mt_payment_update_settings', '', $post );

		return '<div class="updated"><p><strong>' . __( 'My Tickets Payment Settings saved', 'my-tickets' ) . "</strong></p>$messages</div>";
	}

	return '';
}

/**
 * Payment settings form.
 */
function mt_payment_settings() {
	$response     = mt_update_payment_settings( $_POST );
	$options      = ( ! is_array( get_option( 'mt_settings' ) ) ) ? array() : get_option( 'mt_settings' );
	$defaults     = mt_default_settings();
	$options      = array_merge( $defaults, $options );
	$alert        = '';
	$testing_mode = ( 'true' === $options['mt_use_sandbox'] ) ? true : false;
	if ( $testing_mode ) {
		$alert = "<div class='notice updated'><p>" . __( 'Currently in testing mode. Use sandbox accounts when testing payment gateways.', 'my-tickets' ) . '</p></div>';
	}
	?>
	<div class="wrap my-tickets" id="mt_settings">
		<div id="icon-options-general" class="icon32"><br/></div>
		<h1><?php _e( 'Event Registrations', 'my-tickets' ); ?></h1>
		<?php echo $response; ?>
		<?php echo $alert; ?>
		<div class="postbox-container jcd-wide">
			<div class="metabox-holder">
				<form method="post" action="<?php echo admin_url( 'admin.php?page=mt-payment' ); ?>">
					<div><input type="hidden" name="_wpnonce" value="<?php echo wp_create_nonce( 'my-tickets' ); ?>"/>
					</div>
					<div class="ui-sortable meta-box-sortables">
						<div class="postbox">
							<h2 class="hndle"><?php _e( 'Registration Payment Settings', 'my-tickets' ); ?></h2>

							<div class="inside">
								<ul>
									<li><label for="mt_currency"><?php _e( 'Currency:', 'my-tickets' ); ?></label>
										<?php
										$mt_currency_codes = mt_currency();
										echo "<select name='mt_currency' id='mt_currency'>";
										foreach ( $mt_currency_codes as $code => $currency ) {
											$selected = ( $options['mt_currency'] === $code ) ? " selected='selected'" : '';
											echo "<option value='$code'$selected>" . $currency['description'] . '</option>';
										}
										echo '</select>';
										?>
									</li>
									<li>
										<label for="mt_dec_point"><?php _e( 'Decimal Point', 'my-tickets' ); ?></label>
										<input type="text" name="mt_dec_point" id="mt_dec_point" size="3" value="<?php echo esc_attr( $options['mt_dec_point'] ); ?>"/>
									</li>
									<li>
										<label for="mt_thousands_sep"><?php _e( 'Thousands separator', 'my-tickets' ); ?></label>
										<input type="text" name="mt_thousands_sep" id="mt_thousands_sep" size="3" value="<?php echo esc_attr( $options['mt_thousands_sep'] ); ?>"/>
									</li>
									<li>
										<label for="mt_members_discount"><?php _e( 'Member discount (%)', 'my-tickets' ); ?></label>
										<input type="number" name="mt_members_discount" id="mt_members_discount" size="3" min='0' max='100' value="<?php echo esc_attr( $options['mt_members_discount'] ); ?>"/>
									</li>
									<li>
										<label for="mt_phone"><?php _e( 'Require phone number on purchases', 'my-tickets' ); ?></label>
										<input type="checkbox" name="mt_phone" id="mt_phone" value="on" <?php echo checked( $options['mt_phone'], 'on' ); ?> />
									</li>
									<?php
										echo apply_filters( 'mt_payment_settings_fields', '', $options );
									?>
								</ul>
							</div>
						</div>
					</div>
					<div class="ui-sortable meta-box-sortables">
						<div class="postbox">
							<h2 class="hndle"><?php _e( 'Payment Gateways', 'my-tickets' ); ?></h2>

							<div class="inside">
								<ul>
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
										$settings          = isset( $fields['fields'] ) ? $fields['fields'] : false;
										if ( $settings ) {
											foreach ( $settings as $key => $label ) {
												if ( is_array( $label ) ) {
													$input_type = $label['type'];
													$text_label = $label['label'];
													$value      = ( ! empty( $options['mt_gateways'][ $gateway ][ $key ] ) ) ? $options['mt_gateways'][ $gateway ][ $key ] : $label['value'];
													$checked    = ( 'checkbox' === $input_type && ( isset( $options['mt_gateways'][ $gateway ][ $key ] ) && $options['mt_gateways'][ $gateway ][ $key ] === $label['value'] ) ) ? 'checked="checked"' : '';
													if ( 'checkbox' === $input_type ) {
														$pg_settings .= "<li class='$input_type'><input type='$input_type' name='mt_gateways[$gateway][$key]' id='mt_$gateway-$key' size='60' value='" . esc_attr( $value ) . "' $checked /> <label for='mt_$gateway-$key'>$text_label</label></li>";
													} else {
														$pg_settings .= "<li class='$input_type'><label for='mt_$gateway-$key'>$text_label</label><br /> <input type='$input_type' name='mt_gateways[$gateway][$key]' id='mt_$gateway-$key' size='60' value='" . esc_attr( $value ) . "' $checked /></li>";
													}
												} else {
													$value        = ( ! empty( $options['mt_gateways'][ $gateway ][ $key ] ) ) ? $options['mt_gateways'][ $gateway ][ $key ] : '';
													$pg_settings .= "<li class='textfield'><label for='mt_$gateway-$key'>$label</label><br /> <input type='text' name='mt_gateways[$gateway][$key]' id='mt_$gateway-$key' size='60' value='" . esc_attr( $value ) . "' /></li>";
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
										$pg_tabs          .= "<li><a href='#$gateway'>" . sprintf( __( '%s settings', 'my-tickets' ), $fields['label'] ) . '</a></li>';
										$payment_gateways .= "
										<div class='wptab mt_$gateway' id='$gateway' aria-live='assertive'>
										<fieldset>
											<legend>$fields[label]</legend>
											<p><input type='radio' name='mt_default_gateway' id='mt_default_gateway_$gateway' value='$gateway'" . mt_is_checked( 'mt_default_gateway', $gateway, $options, true ) . " /> <label for='mt_default_gateway_$gateway'>" . __( 'Default gateway', 'my-tickets' ) . "</label></p>
												$pg_settings
												$notes
										</fieldset>
										</div>";
									}
									echo '<li><fieldset><legend>' . __( 'Enabled Payment Gateways', 'my-tickets' ) . "</legend> $default_selector</fieldset>
									<div class='mt-tabs'>
										<ul class='tabs'>
											$pg_tabs
										</ul>
										$payment_gateways
									</div></li>";
									?>
								</ul>
								<ul>
									<li>
										<input type="checkbox" id="mt_use_sandbox" name="mt_use_sandbox" <?php mt_is_checked( 'mt_use_sandbox', 'true', $options ); ?> />
										<label for="mt_use_sandbox"><?php _e( 'Testing mode (no payments will be processed)', 'my-tickets' ); ?></label>
									</li>
									<li>
										<input type="checkbox" id="mt_ssl" name="mt_ssl" value="true" aria-describedby="mt_ssl_note" <?php mt_is_checked( 'mt_ssl', 'true', $options ); ?> />
										<label for="mt_ssl"><?php _e( 'Use SSL for Payment pages.', 'my-tickets' ); ?></label><br/>
										<span id="mt_ssl_note"><?php _e( 'You must have an SSL certificate to use this option', 'my-tickets' ); ?></span>
									</li>
								</ul>
								<fieldset>
									<legend><?php _e( 'My Tickets Payment and Ticket Handling Pages', 'my-tickets' ); ?></legend>
									<?php
									// Translators: Current purchase page.
									$current_purchase_page = ( is_numeric( $options['mt_purchase_page'] ) ) ? sprintf( __( 'Currently: %s', 'my-tickets' ), "<a href='" . get_the_permalink( $options['mt_purchase_page'] ) . "'>" . get_the_title( $options['mt_purchase_page'] ) . '</a>' ) : __( 'Not defined', 'my-tickets' );
									// Translators: Current receipts page.
									$current_receipt_page = ( is_numeric( $options['mt_receipt_page'] ) ) ? sprintf( __( 'Currently: %s', 'my-tickets' ), "<a href='" . get_the_permalink( $options['mt_receipt_page'] ) . "'>" . get_the_title( $options['mt_receipt_page'] ) . '</a>' ) : __( 'Not defined', 'my-tickets' );
									// Translators: Current ticket display page.
									$current_tickets_page = ( is_numeric( $options['mt_tickets_page'] ) ) ? sprintf( __( 'Currently: %s', 'my-tickets' ), "<a href='" . get_the_permalink( $options['mt_tickets_page'] ) . "'>" . get_the_title( $options['mt_tickets_page'] ) . '</a>' ) : __( 'Not defined', 'my-tickets' );
									?>
									<ul>
										<li>
											<input type="text" size='6' class='suggest' id="mt_purchase_page" name="mt_purchase_page" value="<?php echo esc_attr( $options['mt_purchase_page'] ); ?>" required aria-required="true" />
											<label for="mt_purchase_page"><?php _e( 'Shopping cart', 'my-tickets' ); ?>
												<span class='new' aria-live="assertive"></span> <em class='current'><?php echo $current_purchase_page; ?></em></label><br/>
										</li>
										<li>
											<input type="text" size='6' class='suggest' id="mt_receipt_page" name="mt_receipt_page" value="<?php echo esc_attr( $options['mt_receipt_page'] ); ?>" required aria-required="true"/>
											<label for="mt_receipt_page"><?php _e( 'Receipt page', 'my-tickets' ); ?>
												<span class='new' aria-live="assertive"></span> <em class='current'><?php echo $current_receipt_page; ?></em></label><br/>
										</li>
										<li>
											<input type="text" size='6' class='suggest' id="mt_tickets_page" name="mt_tickets_page" value="<?php echo esc_attr( $options['mt_tickets_page'] ); ?>" required aria-required="true"/>
											<label for="mt_tickets_page"><?php _e( 'Tickets page', 'my-tickets' ); ?>
												<span class='new' aria-live="assertive"></span> <em class='current'><?php echo $current_tickets_page; ?></em></label><br/>
										</li>
									</ul>
								</fieldset>
							</div>
						</div>
					</div>
					<p><input type="submit" name="mt-payment-settings" class="button-primary" value="<?php _e( 'Save Payment Settings', 'my-tickets' ); ?>"/></p>
				</form>
			</div>
		</div>
		<?php mt_show_support_box(); ?>
	</div>
	<?php
	// creates settings page for My tickets.
}


/**
 * Return current currency symbol.
 *
 * @param array $currency Currencies.
 *
 * @return string
 */
function mt_symbols( $currency ) {
	$currencies = mt_currency();

	return $currencies[ $currency ]['symbol'];
}

/**
 * All currencies.
 *
 * @return array
 */
function mt_currency() {
	$currencies = apply_filters(
		'mt_currencies',
		array(
			'USD' => array(
				'symbol'      => '$',
				'description' => __( 'U.S. Dollars ($)', 'my-tickets' ),
			),
			'EUR' => array(
				'symbol'      => '€',
				'description' => __( 'Euros (€)', 'my-tickets' ),
			),
			'AUD' => array(
				'symbol'      => 'A $',
				'description' => __( 'Australian Dollars (A $)', 'my-tickets' ),
			),
			'CAD' => array(
				'symbol'      => 'C $',
				'description' => __( 'Canadian Dollars (C $)', 'my-tickets' ),
			),
			'GBP' => array(
				'symbol'      => '£',
				'description' => __( 'Pounds Sterling (£)', 'my-tickets' ),
			),
			'INR' => array(
				'symbol'      => '₹',
				'description' => __( 'Indian Rupees (₹)', 'my-tickets' ),
			),
			'JPY' => array(
				'symbol'      => '¥',
				'description' => __( 'Yen (¥)', 'my-tickets' ),
				'zerodecimal' => true,
			),
			'NZD' => array(
				'symbol'      => '$',
				'description' => __( 'New Zealand Dollar ($)', 'my-tickets' ),
			),
			'CHF' => array(
				'symbol'      => 'CHF ',
				'description' => __( 'Swiss Franc', 'my-tickets' ),
			),
			'HKD' => array(
				'symbol'      => '$',
				'description' => __( 'Hong Kong Dollar ($)', 'my-tickets' ),
			),
			'SGD' => array(
				'symbol'      => '$',
				'description' => __( 'Singapore Dollar ($)', 'my-tickets' ),
			),
			'SEK' => array(
				'symbol'      => 'kr ',
				'description' => __( 'Swedish Krona', 'my-tickets' ),
			),
			'DKK' => array(
				'symbol'      => 'kr ',
				'description' => __( 'Danish Krone', 'my-tickets' ),
			),
			'PLN' => array(
				'symbol'      => 'zł',
				'description' => __( 'Polish Zloty', 'my-tickets' ),
			), // this is triggedec9decring an error. Why.
			'NOK' => array(
				'symbol'      => 'kr ',
				'description' => __( 'Norwegian Krone', 'my-tickets' ),
			),
			'HUF' => array(
				'symbol'      => 'Ft ',
				'description' => __( 'Hungarian Forint', 'my-tickets' ),
				'zerodecimal' => true,
			),
			'ILS' => array(
				'symbol'      => '₪',
				'description' => __( 'Israeli Shekel', 'my-tickets' ),
			),
			'MXN' => array(
				'symbol'      => '$',
				'description' => __( 'Mexican Peso', 'my-tickets' ),
			),
			'BRL' => array(
				'symbol'      => 'R$',
				'description' => __( 'Brazilian Real', 'my-tickets' ),
			),
			'MYR' => array(
				'symbol'      => 'RM',
				'description' => __( 'Malaysian Ringgits', 'my-tickets' ),
			),
			'PHP' => array(
				'symbol'      => '₱',
				'description' => __( 'Philippine Pesos', 'my-tickets' ),
			),
			'TWD' => array(
				'symbol'      => 'NT$',
				'description' => __( 'Taiwan New Dollars', 'my-tickets' ),
				'zerodecimal' => true,
			),
			'THB' => array(
				'symbol'      => '฿',
				'description' => __( 'Thai Baht', 'my-tickets' ),
			),
			'TRY' => array(
				'symbol'      => 'TRY ',
				'description' => __( 'Turkish Lira', 'my-tickets' ),
			),
			'ZAR' => array(
				'symbol'      => 'R',
				'description' => __( 'South African Rand', 'my-tickets' ),
			),
		)
	);

	ksort( $currencies );

	return $currencies;
}

/**
 * Is the current currency a zerodecimal type.
 *
 * @return bool
 */
function mt_zerodecimal_currency() {
	$options  = ( ! is_array( get_option( 'mt_settings' ) ) ) ? array() : get_option( 'mt_settings' );
	$defaults = mt_default_settings();
	$options  = array_merge( $defaults, $options );

	$currency   = $options['mt_currency'];
	$currencies = mt_currency();
	$data       = $currencies[ $currency ];

	if ( isset( $data['zerodecimal'] ) && true === $data['zerodecimal'] ) {
		return true;
	}

	return false;
}
