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
function mt_update_payment_settings( $post ) {
	if ( isset( $post['mt-payment-settings'] ) ) {
		$nonce = sanitize_text_field( $post['_wpnonce'] );
		if ( ! wp_verify_nonce( $nonce, 'my-tickets' ) ) {
			return '';
		}
		$mt_members_discount = (int) preg_replace( '/\D/', '', $post['mt_members_discount'] );
		$mt_currency         = $post['mt_currency'];
		$mt_dec_point        = ( isset( $post['mt_dec_point'] ) ) ? $post['mt_dec_point'] : '.';
		$mt_thousands_sep    = ( isset( $post['mt_thousands_sep'] ) ) ? $post['mt_thousands_sep'] : ',';
		$symbol_order        = ( isset( $post['symbol_order'] ) ) ? $post['symbol_order'] : 'symbol-first';
		$mt_phone            = ( isset( $post['mt_phone'] ) ) ? 'on' : 'off';
		$mt_vat              = ( isset( $post['mt_vat'] ) ) ? 'on' : 'off';
		$mt_redirect         = ( isset( $post['mt_redirect'] ) ) ? '1' : '0';
		$mt_expiration       = ( isset( $post['mt_expiration'] ) ) ? absint( $post['mt_expiration'] ) : '';

		$mt_purchase_page = (int) $post['mt_purchase_page'];
		$mt_receipt_page  = (int) $post['mt_receipt_page'];
		$mt_tickets_page  = (int) $post['mt_tickets_page'];

		$settings = array(
			'mt_members_discount' => $mt_members_discount,
			'mt_currency'         => $mt_currency,
			'mt_dec_point'        => $mt_dec_point,
			'mt_thousands_sep'    => $mt_thousands_sep,
			'symbol_order'        => $symbol_order,
			'mt_phone'            => $mt_phone,
			'mt_vat'              => $mt_vat,
			'mt_purchase_page'    => $mt_purchase_page,
			'mt_receipt_page'     => $mt_receipt_page,
			'mt_tickets_page'     => $mt_tickets_page,
			'mt_redirect'         => $mt_redirect,
			'mt_expiration'       => $mt_expiration,
		);
		/**
		 * Filter My Tickets payment settings before saving.
		 *
		 * @hook mt_payment_settings
		 *
		 * @param array $settings Settings after changes provided by admin but before saving.
		 * @param array $post     $_POST data.
		 *
		 * @return array
		 */
		$settings = apply_filters( 'mt_payment_settings', $settings, $post );
		$settings = array_merge( get_option( 'mt_settings', array() ), $settings );
		update_option( 'mt_settings', $settings );
		/**
		 * Filter message appended to settings updated notification.
		 *
		 * @hook mt_payment_update_settings
		 *
		 * @param string $messages HTML output of messages.
		 * @param array  $post POST data.
		 *
		 * @return string
		 */
		$messages = apply_filters( 'mt_payment_update_settings', '', $post );

		return '<div class="updated"><p><strong>' . __( 'My Tickets Payment Settings saved', 'my-tickets' ) . "</strong></p>$messages</div>";
	}

	return '';
}

/**
 * Payment settings form.
 */
function mt_payment_settings() {
	$notes = isset( $_POST['mt_gateways']['offline']['notes'] ) ? wp_kses_post( $_POST['mt_gateways']['offline']['notes'] ) : '';
	$post  = map_deep( $_POST, 'sanitize_textarea_field' );
	// The notes field supports HTML, so uses a separate sanitization.
	$post['mt_gateways']['offline']['notes'] = $notes;

	$response     = mt_update_payment_settings( $post );
	$options      = mt_get_settings();
	$alert        = '';
	$testing_mode = ( 'true' === $options['mt_use_sandbox'] ) ? true : false;
	if ( $testing_mode ) {
		$alert = "<div class='notice updated'><p>" . __( 'Currently in testing mode. Use sandbox accounts when testing payment gateways.', 'my-tickets' ) . '</p></div>';
	}
	?>
	<div class="wrap my-tickets" id="mt_settings">
		<h1><?php _e( 'Payment & Cart Settings', 'my-tickets' ); ?></h1>
		<?php echo wp_kses_post( $response ); ?>
		<?php echo wp_kses_post( $alert ); ?>
		<div class="postbox-container jcd-wide">
			<div class="metabox-holder">
				<form method="post" action="<?php echo esc_url( admin_url( 'admin.php?page=mt-payment' ) ); ?>">
					<div><input type="hidden" name="_wpnonce" value="<?php echo esc_attr( wp_create_nonce( 'my-tickets' ) ); ?>"/>
					</div>
					<div class="ui-sortable meta-box-sortables">
						<div class="postbox">
							<h2 class="hndle"><?php _e( 'Shopping Cart Settings', 'my-tickets' ); ?></h2>

							<div class="inside">
								<div class="mt-flex">
									<p class="mt-money-format"><strong><?php _e( 'Current format', 'my-tickets' ); ?></strong><br /><?php echo sanitize_text_field( mt_money_format( '25097.87' ) ); ?></p>
									<p>
										<label for="mt_currency"><?php _e( 'Currency:', 'my-tickets' ); ?></label><br />
										<?php
										$mt_currency_codes = mt_currency();
										$select            = "<select name='mt_currency' id='mt_currency'>";
										foreach ( $mt_currency_codes as $code => $currency ) {
											$selected = ( $options['mt_currency'] === $code ) ? " selected='selected'" : '';
											$select  .= "<option value='$code'$selected>" . $currency['description'] . '</option>';
										}
										$select .= '</select>';
										echo wp_kses( $select, mt_kses_elements() );
										?>
									</p>
									<p>
										<label for="mt_dec_point"><?php _e( 'Decimal point', 'my-tickets' ); ?></label><br />
										<input type="text" name="mt_dec_point" id="mt_dec_point" size="3" value="<?php echo stripslashes( esc_attr( $options['mt_dec_point'] ) ); ?>"/>
									</p>
									<p>
										<label for="mt_thousands_sep"><?php _e( 'Separator', 'my-tickets' ); ?></label><br />
										<input type="text" name="mt_thousands_sep" id="mt_thousands_sep" size="3" value="<?php echo stripslashes( esc_attr( $options['mt_thousands_sep'] ) ); ?>"/>
									</p>
									<p>
										<label for="mt_symbol_first"><?php _e( 'Symbol Order', 'my-tickets' ); ?></label>
										<select name="symbol_order" id="mt_symbol_first">
											<option value="symbol-first" <?php selected( $options['symbol_order'], 'symbol-first' ); ?>><?php _e( 'Symbol first, number last', 'my-tickets' ); ?></option>
											<option value="symbol-last" <?php selected( $options['symbol_order'], 'symbol-last' ); ?>><?php _e( 'Number first, symbol last', 'my-tickets' ); ?></option>
										</select>
									</p>
								</div>
								<p>
									<label for="mt_members_discount"><?php _e( 'Member discount (%)', 'my-tickets' ); ?></label>
									<input type="number" name="mt_members_discount" id="mt_members_discount" size="3" min='0' max='100' value="<?php echo stripslashes( esc_attr( $options['mt_members_discount'] ) ); ?>"/>
								</p>
								<p>
									<input type="checkbox" name="mt_phone" id="mt_phone" value="on" <?php echo checked( $options['mt_phone'], 'on' ); ?> />
									<label for="mt_phone"><?php _e( 'Require phone number on purchases', 'my-tickets' ); ?></label>
								</p>
								<p>
									<input type="checkbox" name="mt_vat" id="mt_vat" value="on" <?php echo checked( $options['mt_vat'], 'on' ); ?> />
									<label for="mt_vat"><?php _e( 'Collect VAT Number', 'my-tickets' ); ?></label>
								</p>
								<p>
									<input type="checkbox" name="mt_redirect" id="mt_redirect" value="on" <?php echo checked( $options['mt_redirect'], '1' ); ?> />
									<label for="mt_redirect"><?php _e( 'Redirect to cart when tickets added', 'my-tickets' ); ?></label>
								</p>
								<p>
									<label for="mt_expiration"><?php _e( 'Cart Expiration Window', 'my-tickets' ); ?></label>
									<select name="mt_expiration" id="mt_expiration" aria-describedby="mt_expiration_info">
										<option value=""><?php _e( 'Default (1 week)', 'my-tickets' ); ?></option>
										<option value="600"<?php selected( 600, $options['mt_expiration'] ); ?>><?php _e( '10 minutes', 'my-tickets' ); ?></option>
										<option value="3600"<?php selected( 3600, $options['mt_expiration'] ); ?>><?php _e( '1 hour', 'my-tickets' ); ?></option>
										<option value="<?php echo ( 3 * HOUR_IN_SECONDS ); ?>"<?php selected( ( 3 * HOUR_IN_SECONDS ), $options['mt_expiration'] ); ?>><?php _e( '3 hours', 'my-tickets' ); ?></option>
										<option value="<?php echo ( 12 * HOUR_IN_SECONDS ); ?>"<?php selected( ( 12 * HOUR_IN_SECONDS ), $options['mt_expiration'] ); ?>><?php _e( '12 hours', 'my-tickets' ); ?></option>
										<option value="<?php echo ( DAY_IN_SECONDS ); ?>"<?php selected( ( DAY_IN_SECONDS ), $options['mt_expiration'] ); ?>><?php _e( '1 day', 'my-tickets' ); ?></option>
										<option value="<?php echo ( 3 * DAY_IN_SECONDS ); ?>"<?php selected( ( 3 * DAY_IN_SECONDS ), $options['mt_expiration'] ); ?>><?php _e( '3 days', 'my-tickets' ); ?></option>
										<option value="<?php echo ( 14 * DAY_IN_SECONDS ); ?>"<?php selected( ( 14 * DAY_IN_SECONDS ), $options['mt_expiration'] ); ?>><?php _e( '2 weeks', 'my-tickets' ); ?></option>
										<option value="<?php echo ( 30 * DAY_IN_SECONDS ); ?>"<?php selected( ( 30 * DAY_IN_SECONDS ), $options['mt_expiration'] ); ?>><?php _e( '1 month', 'my-tickets' ); ?></option>
									</select><br />
									<span class="aria-description" id="mt_expiration_info"><?php _e( 'How long tickets will remain in a shopping cart.', 'my-tickets' ); ?></span>
								</p>
								<?php
								/**
								 * Add payment settings fields.
								 *
								 * @hook mt_payment_settings_fields
								 *
								 * @param string $fields HTML output of fields.
								 * @param array  $options Saved settings data.
								 *
								 * @return string
								 */
								echo apply_filters( 'mt_payment_settings_fields', '', $options );
								?>
							</div>
						</div>
					</div>
					<div class="ui-sortable meta-box-sortables">
						<div class="postbox">
							<h2 class="hndle"><?php _e( 'Required Pages', 'my-tickets' ); ?></h2>

							<div class="inside">
								<p>
									<?php esc_html_e( 'These WordPress pages are used to render customer tickets, receipts, and the shopping cart.', 'my-tickets' ); ?>
								</p>
								<fieldset id="mt-required">
									<legend><?php _e( 'My Tickets Payment and Ticket Handling Pages', 'my-tickets' ); ?></legend>
									<?php
									// Translators: Current purchase page, 2: post status.
									$current_purchase_page = ( is_numeric( $options['mt_purchase_page'] ) ) ? sprintf( __( 'Currently: %1$s (%2$s)', 'my-tickets' ), "<a href='" . get_the_permalink( $options['mt_purchase_page'] ) . "'>" . get_the_title( $options['mt_purchase_page'] ) . '</a>', get_post_status( $options['mt_purchase_page'] ) ) : __( 'Not defined', 'my-tickets' );
									// Translators: Current receipts page, 2: post status.
									$current_receipt_page = ( is_numeric( $options['mt_receipt_page'] ) ) ? sprintf( __( 'Currently: %1$s (%2$s)', 'my-tickets' ), "<a href='" . get_the_permalink( $options['mt_receipt_page'] ) . "'>" . get_the_title( $options['mt_receipt_page'] ) . '</a>', get_post_status( $options['mt_receipt_page'] ) ) : __( 'Not defined', 'my-tickets' );
									// Translators: Current ticket display page.
									$current_tickets_page = ( is_numeric( $options['mt_tickets_page'] ) ) ? sprintf( __( 'Currently: %1$s (%2$s)', 'my-tickets' ), "<a href='" . get_the_permalink( $options['mt_tickets_page'] ) . "'>" . get_the_title( $options['mt_tickets_page'] ) . '</a>', get_post_status( $options['mt_tickets_page'] ) ) : __( 'Not defined', 'my-tickets' );
									?>
									<p>
										<input type="search" aria-describedby="mt_purchase_page_new" size='9' class='suggest' id="mt_purchase_page" name="mt_purchase_page" value="<?php echo stripslashes( esc_attr( $options['mt_purchase_page'] ) ); ?>" required aria-required="true" />
										<label for="mt_purchase_page">
											<?php _e( 'Shopping cart', 'my-tickets' ); ?><br />
											<em class='current'><?php echo wp_kses_post( $current_purchase_page ); ?></em>
										</label>
										<span class='new' aria-live="assertive" id="mt_purchase_page_new"></span>
									</p>
									<p>
										<input type="search" aria-describedby="mt_receipt_page_new"  size='9' class='suggest' id="mt_receipt_page" name="mt_receipt_page" value="<?php echo stripslashes( esc_attr( $options['mt_receipt_page'] ) ); ?>" required aria-required="true"/>
										<label for="mt_receipt_page">
											<?php _e( 'Receipt page', 'my-tickets' ); ?><br />
											<em class='current'><?php echo wp_kses_post( $current_receipt_page ); ?></em>
										</label>
										<span class='new' aria-live="assertive" id="mt_receipt_page"></span>
									</p>
									<p>
										<input type="search" aria-describedby="mt_tickets_page_new" size='9' class='suggest' id="mt_tickets_page" name="mt_tickets_page" value="<?php echo stripslashes( esc_attr( $options['mt_tickets_page'] ) ); ?>" required aria-required="true"/>
										<label for="mt_tickets_page">
											<?php _e( 'Tickets page', 'my-tickets' ); ?><br />
											<em class='current'><?php echo wp_kses_post( $current_tickets_page ); ?></em>
										</label>
										<span class='new' aria-live="assertive" id="mt_tickets_page_new"></span>
									</p>
								</fieldset>
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

/**
 * Return current currency symbol.
 *
 * @param string $currency Selected currency.
 *
 * @return string
 */
function mt_symbols( $currency ) {
	$currencies = mt_currency();
	$symbol     = $currencies[ $currency ]['symbol'];
	$symbol     = ( ! $symbol ) ? $currency : $symbol;

	return $symbol;
}

/**
 * All currencies.
 *
 * @return array
 */
function mt_currency() {
	/**
	 * Filter array of available currencies. Currencies available vary depending on payment gateway used.
	 *
	 * @hook mt_currencies
	 *
	 * @param array $currencies Array of currencies available.
	 *
	 * @return array
	 */
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
			'CZK' => array(
				'symbol'      => 'Kč',
				'description' => __( 'Czech Koruna (Kč)', 'my-tickets' ),
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
	$options    = mt_get_settings();
	$currency   = $options['mt_currency'];
	$currencies = mt_currency();
	$data       = $currencies[ $currency ];

	if ( isset( $data['zerodecimal'] ) && true === $data['zerodecimal'] ) {
		return true;
	}

	return false;
}
