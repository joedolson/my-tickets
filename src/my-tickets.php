<?php
/**
 * My Tickets, Accessible ticket sales for WordPress
 *
 * @package     My Tickets - Accessible Event Ticketing
 * @author      Joe Dolson
 * @copyright   2014-2025 Joe Dolson
 * @license     GPL-2.0+
 *
 * @wordpress-plugin
 * Plugin Name: My Tickets
 * Plugin URI:  https://www.joedolson.com/my-tickets/
 * Description: Sell Tickets and take registrations for your events. Integrate with My Calendar.
 * Author:      Joe Dolson
 * Author URI:  https://www.joedolson.com
 * Text Domain: my-tickets
 * License:     GPL-2.0+
 * License URI: http://www.gnu.org/license/gpl-2.0.txt
 * Domain Path: lang
 * Version:     2.0.20
 */

/*
	Copyright 2014-2025  Joe Dolson (email : joe@joedolson.com)

	This program is free software; you can redistribute it and/or modify
	it under the terms of the GNU General Public License as published by
	the Free Software Foundation; either version 2 of the License, or
	(at your option) any later version.

	This program is distributed in the hope that it will be useful,
	but WITHOUT ANY WARRANTY; without even the implied warranty of
	MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
	GNU General Public License for more details.

	You should have received a copy of the GNU General Public License
	along with this program; if not, write to the Free Software
	Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/

/**
 * Return current version of My Tickets.
 *
 * @return string Current My Tickets version.
 */
function mt_get_current_version() {
	$mt_version = '2.0.20';

	return $mt_version;
}

add_action( 'admin_notices', 'mt_status_notice', 10 );
/**
 * Display notice to admin users about My Tickets status.
 */
function mt_status_notice() {
	// Only shown when in the Playground preview.
	if ( 'true' === get_option( 'mt_show_playground_intro', '' ) ) {
		echo '<div class="notice notice-info">';
		echo '<h3>' . __( 'Thanks for trying out My Tickets!', 'my-tickets' ) . '</h3>';
		echo '<p>' . __( "Let me give you a few quick things to try out while you're here:", 'my-tickets' ) . '</p>';
		echo '<ol>';
		// translators: Post edit link.
		echo '<li>' . sprintf( __( 'Visit <a href="%s">the playground example event</a> to test setting up ticketing.', 'my-tickets' ), admin_url( 'edit.php?post_type=page' ) ) . '</li>';
		echo '<li>' . __( 'Explore the Payment and Ticket settings to try different options.', 'my-tickets' ) . '</li>';
		echo '<li>' . __( "Payment gateways aren't available in the playground, but you can test Offline payments.", 'my-tickets' ) . '</li>';
		echo '</ol>';
		// translators: link to plugin documentation.
		echo '<p>' . sprintf( __( 'To learn more, check out the <a href="%s">plugin documentation</a>.', 'my-tickets' ), 'https://docs.joedolson.com/my-tickets/' ) . '</p>';
		echo '</div>';
	}
	if ( current_user_can( 'activate_plugins' ) ) {
		$options  = get_option( 'mt_settings' );
		$purchase = ( isset( $options['mt_purchase_page'] ) ) ? $options['mt_purchase_page'] : false;
		$receipt  = ( isset( $options['mt_receipt_page'] ) ) ? $options['mt_receipt_page'] : false;
		$tickets  = ( isset( $options['mt_tickets_page'] ) ) ? $options['mt_tickets_page'] : false;
		$settings = admin_url( 'admin.php?page=mt-payment#mt-required' );
		if ( ! $purchase || 'publish' !== get_post_status( $purchase ) ) {
			if ( ! $purchase ) {
				// Translators: URL to settings page.
				echo "<div class='error notice'><p>" . sprintf( __( 'The required My Tickets cart page is not assigned. <a href="%s" class="button-secondary">Check settings</a>', 'my-tickets' ), $settings ) . '</p></div>';
			} else {
				$edit = get_edit_post_link( $purchase );
				if ( $edit ) {
					// Translators: URL to edit post page.
					echo "<div class='error notice'><p>" . sprintf( __( 'The required My Tickets cart page is not publicly available. <a href="%s" class="button-secondary">Edit the cart page</a>', 'my-tickets' ), $edit ) . '</p></div>';
				} else {
					$admin_url = str_replace( 'mt-required', 'mt_purchase_page', $settings );
					// Translators: URL to My Tickets settings.
					echo "<div class='error notice'><p>" . sprintf( __( 'The assigned My Tickets cart page does not exist. <a href="%s">', 'my-tickets' ), $admin_url ) . '</p></div>';
				}
			}
		}
		if ( ! $receipt || 'publish' !== get_post_status( $receipt ) ) {
			if ( ! $receipt ) {
				// Translators: URL to settings page.
				echo "<div class='error notice'><p>" . sprintf( __( 'The required My Tickets receipts page is not assigned. <a href="%s" class="button-secondary">Check settings</a>', 'my-tickets' ), $settings ) . '</p></div>';
			} else {
				$edit = get_edit_post_link( $receipt );
				if ( $edit ) {
					// Translators: URL to edit post page.
					echo "<div class='error notice'><p>" . sprintf( __( 'The required My Tickets receipts page is not publicly available. <a href="%s" class="button-secondary">Edit the receipts page</a>', 'my-tickets' ), $edit ) . '</p></div>';
				} else {
					$admin_url = str_replace( 'mt-required', 'mt_receipt_page', $settings );
					// Translators: URL to My Tickets settings.
					echo "<div class='error notice'><p>" . sprintf( __( 'The assigned My Tickets receipt display page does not exist. <a href="%s">Check settings</a>', 'my-tickets' ), $admin_url ) . '</p></div>';
				}
			}
		}
		if ( ! $tickets || 'publish' !== get_post_status( $tickets ) ) {
			if ( ! $tickets ) {
				// Translators: URL to settings page.
				echo "<div class='error notice'><p>" . sprintf( __( 'The required My Tickets tickets page is not assigned. <a href="%s" class="button-secondary">Check settings</a>', 'my-tickets' ), $settings ) . '</p></div>';
			} else {
				$edit = get_edit_post_link( $tickets );
				if ( $edit ) {
					// Translators: URL to edit post page.
					echo "<div class='error notice'><p>" . sprintf( __( 'The required My Tickets tickets page is not publicly available. <a href="%s" class="button-secondary">Edit the tickets page</a>', 'my-tickets' ), $edit ) . '</p></div>';
				} else {
					$admin_url = str_replace( 'mt-required', 'mt_tickets_page', $settings );
					// Translators: URL to My Tickets settings.
					echo "<div class='error notice'><p>" . sprintf( __( 'The assigned My Tickets ticket display page does not exist. <a href="%s">Check settings</a>', 'my-tickets' ), $admin_url ) . '</p></div>';
				}
			}
		}
	}
}

/**
 * Define default gateways. Just place file in gateways directory to add to set.
 *
 * @return array
 */
function mt_import_gateways() {
	$results   = array();
	$directory = plugin_dir_path( __FILE__ ) . 'gateways';
	$handler   = opendir( $directory );
	// keep going until all files in directory have been read.
	while ( $file = readdir( $handler ) ) { // phpcs:ignore Generic.CodeAnalysis.AssignmentInCondition.FoundInWhileCondition
		// if $file isn't this directory or its parent add it to the results array.
		if ( '.' !== $file && '..' !== $file ) {
			$results[] = $file;
		}
	}
	closedir( $handler );
	sort( $results, SORT_STRING );

	return $results;
}

add_action( 'init', 'mt_build_gateways' );
/**
 * Load gateways.
 *
 * @uses filter mt_import_gateways
 */
function mt_build_gateways() {
	/**
	 * Internal use only: register an additional payment gateway for My Tickets.
	 *
	 * @hook mt_import_gateways
	 *
	 * @param {array} Associative array of strings identifying the gateway's loading file without the .php extension.
	 *
	 * @return {array}
	 */
	$gateways = apply_filters( 'mt_import_gateways', mt_import_gateways() );
	foreach ( $gateways as $gateway ) {
		if ( false !== strpos( $gateway, '.php' ) ) {
			require_once plugin_dir_path( __FILE__ ) . 'gateways/' . $gateway;
		}
	}
}

require plugin_dir_path( __FILE__ ) . 'includes/data-utilities.php';
require plugin_dir_path( __FILE__ ) . 'includes/date-utilities.php';
require plugin_dir_path( __FILE__ ) . 'includes/event-utilities.php';
require plugin_dir_path( __FILE__ ) . 'mt-common.php';
require plugin_dir_path( __FILE__ ) . 'mt-cpt.php';
require plugin_dir_path( __FILE__ ) . 'mt-fields-api.php';
require plugin_dir_path( __FILE__ ) . 'mt-payment.php';
require plugin_dir_path( __FILE__ ) . 'mt-reports.php';
require plugin_dir_path( __FILE__ ) . 'mt-notifications.php';
require plugin_dir_path( __FILE__ ) . 'mt-help.php';
require plugin_dir_path( __FILE__ ) . 'mt-processing.php';
require plugin_dir_path( __FILE__ ) . 'mt-cart.php';
require plugin_dir_path( __FILE__ ) . 'mt-cart-handler.php';
require plugin_dir_path( __FILE__ ) . 'mt-ajax.php';
require plugin_dir_path( __FILE__ ) . 'mt-tickets.php';
require plugin_dir_path( __FILE__ ) . 'mt-receipt.php';
require plugin_dir_path( __FILE__ ) . 'mt-shortcodes.php';
require plugin_dir_path( __FILE__ ) . 'class-mt-short-cart-widget.php';
require plugin_dir_path( __FILE__ ) . 'mt-add-to-cart.php';
require plugin_dir_path( __FILE__ ) . 'mt-templating.php';
require plugin_dir_path( __FILE__ ) . 'mt-settings.php';
require plugin_dir_path( __FILE__ ) . 'mt-payment-settings.php';
require plugin_dir_path( __FILE__ ) . 'mt-ticketing-settings.php';
require plugin_dir_path( __FILE__ ) . 'mt-debug.php';

// Not used by core plug-in; only if premium add-ons are installed.
if ( ! class_exists( 'EDD_SL_Plugin_Updater' ) ) {
	require_once plugin_dir_path( __FILE__ ) . 'updates/EDD_SL_Plugin_Updater.php';
}

/*
 * Quick and easy email-based debugging
 *
 * @param $data email message body
 * @param $subject email message subject extension
*/
define( 'MT_DEBUG', false );
/**
 * Send debugging data as needed.
 *
 * @param string   $data Information used for debugging.
 * @param string   $subject Message subject line.
 * @param int|bool $post_id Post ID if available.
 * @param bool     $override Send message even if not enabled.
 */
function mt_debug( $data, $subject = '', $post_id = false, $override = false ) {
	if ( true === MT_DEBUG || true === $override ) {
		if ( $post_id ) {
			add_post_meta(
				$post_id,
				'_debug_data',
				array(
					'subject'   => $subject,
					'data'      => $data,
					'timestamp' => mt_current_time(),
				)
			);
		} else {
			wp_mail( get_option( 'admin_email' ), "Debugging: $subject", $data );
		}
	}
}

add_action( 'plugins_loaded', 'mt_update_check' );
/**
 * Check whether plugin needs to be updated.
 */
function mt_update_check() {
	$mt_version = mt_get_current_version();
	if ( version_compare( $mt_version, '0.9.9', '<' ) ) {
		// insert update cycles here, when needed.
	}

	update_option( 'mt_version', $mt_version );
}

register_activation_hook( __FILE__, 'mt_activation' );
/**
 * On plug-in activation, merge default settings and options, create purchase pages if necessary.
 */
function mt_activation() {
	$options = mt_get_settings();
	if ( ! isset( $options['mt_purchase_page'] ) || ! is_numeric( $options['mt_purchase_page'] ) ) {
		$purchase                    = mt_setup_page( 'purchase' );
		$receipt                     = mt_setup_page( 'receipt' );
		$tickets                     = mt_setup_page( 'tickets' );
		$options['mt_purchase_page'] = $purchase;
		$options['mt_receipt_page']  = $receipt;
		$options['mt_tickets_page']  = $tickets;
		update_option( 'mt_settings', $options );
	}
	flush_rewrite_rules();

	if ( ! wp_next_scheduled( 'my_tickets_hourly_cron' ) ) {
		wp_schedule_event( current_time( 'timestamp' ), 'hourly', 'my_tickets_hourly_cron' ); // phpcs:ignore WordPress.DateTime.CurrentTimeTimestamp.Requested
	}
}

add_action( 'my_tickets_hourly_cron', 'my_tickets_hourly_cron_actions' );
/**
 * Daily cron job to remove expired anonymous shopping cart data.
 */
function my_tickets_hourly_cron_actions() {
	$carts = mt_find_carts();
	my_tickets_check_cart_expirations( $carts );
}

register_deactivation_hook( __FILE__, 'mt_plugin_deactivated' );
/**
 * On plugin deactivation.
 */
function mt_plugin_deactivated() {
	flush_rewrite_rules();
}

/**
 * Label My Tickets pages in the admin.
 *
 * @param array  $states States for post.
 * @param object $post The post object.
 *
 * @return array
 */
function mt_admin_state( $states, $post ) {
	if ( is_admin() ) {
		$options = mt_get_settings();
		if ( absint( $options['mt_purchase_page'] ) === $post->ID ) {
			$states[] = __( 'Shopping Cart Page', 'my-tickets' );
		}
		if ( absint( $options['mt_receipt_page'] ) === $post->ID ) {
			$states[] = __( 'Receipt Page', 'my-tickets' );
		}
		if ( absint( $options['mt_tickets_page'] ) === $post->ID ) {
			$states[] = __( 'Ticket Page', 'my-tickets' );
		}
	}

	return $states;
}
add_filter( 'display_post_states', 'mt_admin_state', 10, 2 );

add_action( 'admin_menu', 'my_tickets_menu' );
/**
 * Add submenus.
 */
function my_tickets_menu() {
	$icon_path = plugins_url( '/images', __FILE__ );
	/**
	 * Set capability required to manage My Tickets settings. Default `manage_options`.
	 *
	 * @hook mt_registration_permissions
	 *
	 * @param {string} $permission WordPress capability string.
	 *
	 * @return {string} WordPress capability string.
	 */
	$permission = apply_filters( 'mt_registration_permissions', 'manage_options' );
	if ( function_exists( 'add_menu_page' ) ) {
		add_menu_page( __( 'My Tickets', 'my-tickets' ), __( 'My Tickets', 'my-tickets' ), $permission, 'my-tickets', 'mt_settings', $icon_path . '/tickets.png' );
	}
	add_action( 'admin_head', 'mt_reg_styles' );
	add_submenu_page( 'my-tickets', __( 'My Tickets', 'my-tickets' ), __( 'Settings', 'my-tickets' ), $permission, 'my-tickets', 'mt_settings' );
	add_submenu_page( 'my-tickets', __( 'My Tickets', 'my-tickets' ), __( 'Payment Settings', 'my-tickets' ), $permission, 'mt-payment', 'mt_payment_settings' );
	$ticketing = add_submenu_page( 'my-tickets', __( 'My Tickets', 'my-tickets' ), __( 'Ticket Settings', 'my-tickets' ), $permission, 'mt-ticketing', 'mt_ticketing_settings' );
	add_submenu_page( 'my-tickets', __( 'My Tickets > Reports', 'my-tickets' ), __( 'Reports', 'my-tickets' ), 'mt-view-reports', 'mt-reports', 'mt_reports_page' );
	add_submenu_page( 'my-tickets', __( 'My Tickets > Payments', 'my-tickets' ), __( 'Payments', 'my-tickets' ), 'mt-view-reports', 'mt-payments', 'mt_payments_page' );
	add_submenu_page( 'my-tickets', __( 'My Tickets > Help', 'my-tickets' ), __( 'Ticketing Help', 'my-tickets' ), $permission, 'mt-help', 'mt_help' );

	add_action( 'load-' . $ticketing, 'mt_ticketing_help_tab' );
}

add_action( 'admin_init', 'mt_redirect_to_payments' );
/**
 * Redirect users to payments section if they click on the Payments link under My Tickets menu.
 */
function mt_redirect_to_payments() {
	if ( isset( $_GET['page'] ) && 'mt-payments' === $_GET['page'] ) {
		wp_safe_redirect( admin_url( 'edit.php?post_type=mt-payments' ) );
		exit;
	}
}

/**
 * Doesn't do anything.
 *
 * @return void
 */
function mt_payments_page() {
	return;
}

/**
 * Generate Help tab information in screen.
 */
function mt_ticketing_help_tab() {
	$screen = get_current_screen();
	$screen->add_help_tab(
		array(
			'id'      => 'mt_ticketing_help_tab_1',
			'title'   => __( 'Ticket Options', 'my-tickets' ),
			'content' => '<p><strong>' . __( 'General Ticketing Options', 'my-tickets' ) . '</strong><br />' . __( 'These options are global to all tickets. They include shipping rates, administrative fees, types of tickets available to your customers, and how you reserve tickets for sales at your ticket office.', 'my-tickets' ) . '</p>',
		)
	);
	$screen->add_help_tab(
		array(
			'id'      => 'mt_ticketing_help_tab_2',
			'title'   => __( 'Ticket Defaults', 'my-tickets' ),
			'content' => '<p><strong>' . __( 'Ticket Defaults', 'my-tickets' ) . '</strong><br />' . __( 'Ticket defaults are settings that are specific to events. These values are what will be set up by default when you create a new event, but can be changed within the event. Only events that have a value entered for the number of tickets available for purchase will show up for sale on your site.', 'my-tickets' ) . '</p>',
		)
	);
	$resources  = '<p>' . __( 'More Help', 'my-tickets' ) . '</p>';
	$resources .= '<ul>
					<li><a href="http://docs.joedolson.com/my-tickets/">' . __( 'Documentation', 'my-tickets' ) . '</a></li>
					<li><a href="http://docs.joedolson.com/my-tickets/2014/11/25/ticket-settings/">' . __( 'Ticket Settings', 'my-tickets' ) . '</a></li>
				</ul>';
	$screen->set_help_sidebar( $resources );
}
/**
 * Enqueue admin styles.
 */
function mt_reg_styles() {
	$version = mt_get_current_version();
	if ( SCRIPT_DEBUG ) {
		$version = $version . '-' . wp_rand( 10000, 99999 );
	}
	wp_enqueue_style( 'mt-styles', plugins_url( '/css/my-tickets.css', __FILE__ ), array(), $version );
}

add_action( 'admin_enqueue_scripts', 'mt_admin_enqueue_public_scripts' );
/**
 * If on My Calendar admin design page, enqueue public scripts.
 */
function mt_admin_enqueue_public_scripts() {
	global $current_screen;
	$id = $current_screen->id;
	if ( 'my-calendar_page_my-calendar-design' === $id ) {
		mt_public_enqueue_scripts();
	}
}

add_action( 'wp_enqueue_scripts', 'mt_public_enqueue_scripts' );
/**
 * Enqueue public-facing scripts and styles. Localize scripts.
 */
function mt_public_enqueue_scripts() {
	if ( file_exists( get_stylesheet_directory() . '/css/mt-cart.css' ) ) {
		$ticket_styles = get_stylesheet_directory_uri() . '/css/mt-cart.css';
	} else {
		$ticket_styles = plugins_url( '/css/mt-cart.css', __FILE__ );
	}
	$options  = mt_get_settings();
	$symbol   = mt_symbols( $options['mt_currency'] );
	$cart_url = esc_url( mt_get_cart_url() );
	$redirect = isset( $options['mt_redirect'] ) ? $options['mt_redirect'] : '0';
	/**
	 * Force add to cart redirect. Return 1 to override plugin settings and redirect to the shopping cart after tickets are added to the cart.
	 *
	 * @hook mt_redirect
	 *
	 * @param {string} $redirect Boolean value as string: 1 or 0.
	 *
	 * @return {string} Boolean value as string: 1 or 0.
	 */
	$redirect = apply_filters( 'mt_redirect', $redirect );
	$version  = mt_get_current_version();
	if ( SCRIPT_DEBUG ) {
		$version = $version . '-' . wp_rand( 10000, 99999 );
	}
	if ( SCRIPT_DEBUG ) {
		$public_url = plugins_url( 'js/jquery.public.js', __FILE__ );
	} else {
		$public_url = plugins_url( 'js/jquery.public.min.js', __FILE__ );
	}
	wp_enqueue_script( 'mt.public', $public_url, array( 'jquery', 'wp-a11y' ), $version, true );
	wp_enqueue_style( 'mt-styles', $ticket_styles, array( 'dashicons' ), $version );
	wp_add_inline_style( 'mt-styles', mt_generate_css() );

	wp_localize_script(
		'mt.public',
		'mt_ajax',
		array(
			'action'               => 'mt_ajax_handler',
			'url'                  => admin_url( 'admin-ajax.php' ),
			'security'             => wp_create_nonce( 'mt-cart-nonce' ),
			'currency'             => $symbol,
			'cart_url'             => $cart_url,
			'redirect'             => $redirect,
			'requiredFieldsText'   => __( 'Please complete all required fields!', 'my-tickets' ),
			'cartExpired'          => __( 'Your shopping cart has expired.', 'my-tickets' ),
			'cartExpiringSoon'     => __( 'Your cart will expire in 2 minutes. Press Ctrl plus Space to extend 5 minutes.', 'my-tickets' ),
			'cartExpiringVerySoon' => __( 'Your cart will expire in 1 minute. Press Ctrl plus Space to extend 5 minutes.', 'my-tickets' ),
		)
	);
	$enabled  = $options['mt_gateway'];
	$handling = array();
	foreach ( $enabled as $gate ) {
		$handling[ $gate ] = mt_get_cart_handling( $options, $gate );
	}
	wp_localize_script(
		'mt.public',
		'mt_ajax_cart',
		array(
			'action'     => 'mt_ajax_cart',
			'url'        => admin_url( 'admin-ajax.php' ),
			'security'   => wp_create_nonce( 'mt-ajax-cart-nonce' ),
			'max_limit'  => __( "You've reached the maximum number of tickets available for this purchase.", 'my-tickets' ),
			'processing' => __( 'Cart update processing', 'my-tickets' ),
			'thousands'  => $options['mt_thousands_sep'],
			'handling'   => $handling,
		)
	);
}


/**
 * Set up gateway settings fields. Define gateway name and fields.
 *
 * @return array
 */
function mt_setup_gateways() {
	$gateways = array(
		'offline' => array(
			'label'  => __( 'Offline', 'my-tickets' ),
			'fields' => array( 'forms' => __( 'Payment Forms Accepted', 'my-tickets' ) ),
		),
	);
	/**
	 * Filter gateway settings fields.
	 *
	 * @hook mt_setup_gateways
	 *
	 * @param {array} $gateways Array holding custom fields relevant to each payment gateway.
	 */
	return apply_filters( 'mt_setup_gateways', $gateways );
}

/**
 * Define default settings on installation.
 *
 * @return array
 */
function mt_default_settings() {
	$gateways = array(
		'paypal'       => array(
			'email'       => '',
			'merchant_id' => '',
		),
		'authorizenet' => array(
			'api'  => '',
			'key'  => '',
			'hash' => '',
		),
		'offline'      => array(
			'forms' => '',
		),
	);

	$messages   = array(
		'completed' => array(
			'purchaser' => array(
				'subject' => 'Thanks for your purchase from {blogname}',
				'body'    => '
<p>
Thanks for your ticket purchase from {blogname}, {name}!
</p>
<p>
{receipt}
</p>
<p>
{tickets}
</p>

{purchase}

{address}

<p>Amount due: {amount_due}</p>

<p>
We\'ll see you soon!<br />
{blogname}
</p>',
			),
			'admin'     => array(
				'subject' => 'New ticket purchase on {blogname}',
				'body'    => '
<p>
{name} has purchased tickets on {blogname}:
</p>
<p>
{receipt}
</p>
<p>
{tickets}
</p>

{purchase}

{address}

<p>Amount due: {amount_due}</p>
				',
			),
		),
		'failed'    => array(
			'purchaser' => array(
				'subject' => 'Payment Failed on ticket purchase from {blogname}',
				'body'    => 'Payment failed on purchase: {receipt}',
			),
			'admin'     => array(
				'subject' => 'Payment Failed on ticket purchase from {blogname}',
				'body'    => 'Payment failed on purchase: {receipt}',
			),
		),
		'refunded'  => array(
			'purchaser' => array(
				'subject' => 'Your purchase from {blogname} has been refunded.',
				'body'    => 'Payment refunded on purchase: {receipt}',
			),
			'admin'     => array(
				'subject' => 'Purchase from {name} has been refunded.',
				'body'    => 'Payment refunded on purchase: {receipt}',
			),
		),
		'interim'   => array(
			'purchaser' => array(
				'subject' => 'Your purchase from {blogname} has been received and is pending payment.',
				'body'    => 'Payment receipt: {receipt}',
			),
			'admin'     => array(
				'subject' => 'Purchase from {name} has been received and is pending payment.',
				'body'    => 'Payment receipt: {receipt}',
			),
		),
	);
	$continuous = array(
		'reg_expires'     => '3',
		'multiple'        => 'true',
		'sales_type'      => 'tickets',
		'counting_method' => 'continuous',
		'pricing'         => array(
			'adult'          => array(
				'label'   => 'Adult',
				'price'   => '20',
				'tickets' => '',
				'sold'    => '',
				'close'   => '',
			),
			'senior-student' => array(
				'label'   => 'Senior/Student',
				'price'   => '15',
				'tickets' => '',
				'sold'    => '',
				'close'   => '',
			),
			'child'          => array(
				'label'   => 'Child',
				'price'   => '10',
				'tickets' => '',
				'sold'    => '',
				'close'   => '',
			),
		),
		'tickets'         => '120',
	);

	$discrete = array(
		'reg_expires'     => '3',
		'multiple'        => 'true',
		'sales_type'      => 'tickets',
		'counting_method' => 'discrete',
		'pricing'         => array(
			'section-a' => array(
				'label'   => 'Section A',
				'price'   => '20',
				'tickets' => '40',
				'sold'    => '',
				'close'   => '',
			),
			'section-b' => array(
				'label'   => 'Section B',
				'price'   => '10',
				'tickets' => '60',
				'sold'    => '',
				'close'   => '',
			),
		),
		'tickets'         => 'inherit',
	);

	$showtime = array(
		'reg_expires'     => '3',
		'multiple'        => 'true',
		'sales_type'      => 'tickets',
		'counting_method' => 'event',
		'pricing'         => array(
			'first-showing'  => array(
				'label'   => date_i18n( 'Y-m-d H:i', strtotime( '8:00pm + 1 day' ) ),
				'price'   => '20',
				'tickets' => '40',
				'sold'    => '',
				'close'   => '',
			),
			'second-showing' => array(
				'label'   => date_i18n( 'Y-m-d H:i', strtotime( '2:00pm + 2 days' ) ),
				'price'   => '15',
				'tickets' => '40',
				'sold'    => '',
				'close'   => '',
			),
		),
		'tickets'         => 'inherit',
	);

	$defaults = array(
		// Messages following registration/ticket order & payment.
		'defaults'                 => array(
			'continuous' => $continuous,
			'discrete'   => $discrete,
			'event'      => $showtime,
		),
		'default_model'            => 'continuous',
		'messages'                 => $messages,
		'mt_post_types'            => array( 'mc-events', 'page' ),
		'mt_license_key'           => '',
		'mt_html_email'            => 'true',
		'mt_to'                    => get_bloginfo( 'admin_email' ),
		'mt_from'                  => get_bloginfo( 'admin_email' ),
		'mt_use_sandbox'           => 'false',
		'mt_currency'              => 'USD',
		'mt_dec_point'             => '.',
		'mt_thousands_sep'         => ',',
		'mt_phone'                 => 'off',
		'mt_vat'                   => 'off',
		'mt_redirect'              => '0',
		'mt_members_discount'      => '',
		'mt_ssl'                   => 'false',
		'mt_gateway'               => array( 'offline' ),
		'mt_default_gateway'       => 'offline',
		'mt_purchase_page'         => '',
		'mt_receipt_page'          => '',
		'mt_tickets_page'          => '',
		'mt_report_order'          => 'event',
		'mt_report_direction'      => 'asc',
		'mt_default_report'        => 'purchases',
		'mt_default_format'        => 'csv',
		'mt_ticketing'             => array( 'printable' => 'on' ),
		'mt_shipping'              => 0,
		'mt_handling'              => 0,
		'mt_shipping_time'         => '3-5 days',
		'mt_gateways'              => $gateways,
		'mt_ticket_handling'       => '',
		'mt_tickets_close_value'   => '',
		'mt_tickets_close_type'    => 'integer',
		'mt_ticket_image'          => 'ticket',
		'symbol_order'             => 'symbol-first',
		'mt_hide_empty_short_cart' => 'false',
		'mt_expiration'            => '',
		'mt_display_remaining'     => 'proportion',
		'mt_show_closed'           => 'false',
		'style_vars'               => mt_style_variables(),
		'mt_inventory'             => 'actual',
		'mt_singular'              => 'true',
	);

	return $defaults;
}

add_filter( 'template_include', 'mt_verify', 10, 1 );
/**
 * Verify ticket for e-ticketing or printables. (Use any QR code reader on phone or tablet.)
 *
 * @param string $template Template name.
 *
 * @return string
 */
function mt_verify( $template ) {
	if ( isset( $_GET['ticket_id'] ) && isset( $_GET['action'] ) && 'mt-verify' === $_GET['action'] ) {
		$template = locate_template( 'verify.php' );
		if ( $template ) {
			return $template;
		} else {
			return __DIR__ . '/templates/verify.php';
		}
	}

	return $template;
}

add_filter( 'template_include', 'mt_bulk_verify', 10, 1 );
/**
 * Verify all tickets on a purchase for e-ticketing or printables. (Use any QR code reader on phone or tablet.)
 *
 * @param string $template Template name.
 *
 * @return string
 */
function mt_bulk_verify( $template ) {
	if ( isset( $_GET['receipt_id'] ) && isset( $_GET['action'] ) && 'mt-verify' === $_GET['action'] ) {
		$template = locate_template( 'bulk-verify.php' );
		if ( $template ) {
			return $template;
		} else {
			return __DIR__ . '/templates/bulk-verify.php';
		}
	}

	return $template;
}

add_action( 'init', 'mt_admin_delete' );
/**
 * Give admins easy ability to delete cart from adminbar.
 */
function mt_admin_delete() {
	if ( is_user_logged_in() && isset( $_GET['mt_delete'] ) && 'true' === $_GET['mt_delete'] ) {
		mt_delete_data( 'cart' );
		mt_delete_data( 'payment' );
		$redirect = wp_get_referer();
		wp_safe_redirect( $redirect );
		exit;
	}
}

add_action( 'admin_bar_menu', 'mt_admin_bar', 200 );
/**
 * Add delete to admin bar.
 */
function mt_admin_bar() {
	if ( current_user_can( 'mt-copy-cart' ) ) {
		global $wp_admin_bar;
		$url  = add_query_arg( 'mt_delete', 'true', home_url() );
		$args = array(
			'id'    => 'mt_delete',
			'title' => __( 'Empty Cart', 'my-tickets' ),
			'href'  => $url,
		);
		$wp_admin_bar->add_node( $args );
		$purchase_page = mt_get_cart_url();
		if ( $purchase_page ) {
			$args = array(
				'id'     => 'mt-view-cart',
				'title'  => __( 'View Cart', 'my-tickets' ),
				'href'   => esc_url( $purchase_page ),
				'parent' => 'mt_delete',
			);
			$wp_admin_bar->add_node( $args );
		}
	}
}

/**
 * Setup purchase pages on activation if pages of that name don't already exist.
 *
 * @param string $slug slug of page to create.
 *
 * @return int|WP_Error
 */
function mt_setup_page( $slug ) {
	$current_user = wp_get_current_user();
	if ( ! is_page( $slug ) ) {
		$page      = array(
			'post_title'  => ucfirst( $slug ),
			'post_status' => 'publish',
			'post_type'   => 'page',
			'post_author' => $current_user->ID,
			'ping_status' => 'closed',
		);
		$post_ID   = wp_insert_post( $page );
		$post_slug = wp_unique_post_slug( $slug, $post_ID, 'publish', 'page', 0 );
		wp_update_post(
			array(
				'ID'        => $post_ID,
				'post_name' => $post_slug,
			)
		);
	} else {
		$post    = get_page_by_path( $slug );
		$post_ID = $post->ID;
	}

	return $post_ID;
}

add_action( 'init', 'mt_register_actions', 20 );
/**
 * Register and deregister key actions in My Calendar.
 */
function mt_register_actions() {
	$is_group_editing = false;
	if ( isset( $_GET['group_id'] ) ) {
		$is_group_editing = true;
	}
	if ( function_exists( 'my_calendar' ) ) {
		remove_filter( 'mc_event_registration', 'mc_standard_event_registration', 10, 4 );
		if ( ! $is_group_editing ) {
			add_action( 'mc_update_event_post', 'mt_save_registration_data', 10, 4 );
		}
	}
	if ( ! $is_group_editing ) {
		add_filter( 'mc_event_registration', 'mt_registration_fields', 10, 4 );
	}
	add_filter( 'template_include', 'mt_receive_ipn' );
}

/**
 * Pass no-store cache rules for My Tickets cart page.
 *
 * @param array $headers array of header strings.
 * @param WP    $wp Current WP environment.
 *
 * @return array
 */
function mt_headers( $headers, $wp ) {
	$options       = mt_get_settings();
	$purchase_page = $options['mt_purchase_page'];
	if ( is_page( $purchase_page ) ) {
		$headers['Cache-Control'] = 'no-cache, no-store, must-revalidate, max-age=0';
	}

	return $headers;
}
add_filter( 'wp_headers', 'mt_headers', 100, 2 );

/**
 * Define custom action processed at the time of template include.
 *
 * @param string $template Template name.
 */
function mt_receive_ipn( $template ) {
	do_action( 'mt_receive_ipn' );

	return $template;
}

add_action( 'wp_footer', 'mt_test_mode' );
/**
 * Display message if in testing mode.
 */
function mt_test_mode() {
	$options = mt_get_settings();
	if ( 'true' === $options['mt_use_sandbox'] ) {
		echo "<div class='mt_sandbox_enabled'>" . __( 'My Tickets is currently in testing mode. No financial transactions will be processed.', 'my-tickets' ) . '</div>';
	}
}

/**
 * Utility function duplicates my_calendar_date_xcomp; true if first date before second date
 *
 * @param string $early Date/time.
 * @param string $late Second date/time.
 *
 * @return bool
 */
function mt_date_comp( $early, $late ) {
	$firstdate = strtotime( $early );
	$lastdate  = strtotime( $late );
	if ( $firstdate < $lastdate ) {
		return true;
	} else {
		return false;
	}
}

/**
 * Show support information.
 *
 * @param bool|array $add Addiitional panels as needed.
 */
function mt_show_support_box( $add = false ) {
	?>
	<div class="postbox-container jcd-narrow">
	<div class="metabox-holder">

		<div class="ui-sortable meta-box-sortables">
			<div class="postbox">
				<h2 class="sales hndle"><?php _e( 'Buy Premium Add-ons', 'my-tickets' ); ?></h2>
				<div id="support" class="inside resources">
					<p><strong>
						<?php
						// Translators: Sales URL.
						printf( __( 'Do more with My Tickets - <a href="%s">Buy Premium Add-ons</a>!', 'my-tickets' ), 'https://www.joedolson.com/my-tickets/add-ons/' );
						?>
					</strong></p>
					<ul>
						<li><a href="https://www.joedolson.com/awesome/my-tickets-discounts/">My Tickets: Discounts</a></li>
						<li><a href="https://www.joedolson.com/awesome/my-tickets-donations/">My Tickets: Donations</a></li>
						<li><a href="https://www.joedolson.com/awesome/my-tickets-waiting-list/">My Tickets: Waiting List</a></li>
						<li><a href="https://www.joedolson.com/awesome/my-tickets-stripe/">My Tickets: Stripe</a></li>
						<li><a href="https://www.joedolson.com/awesome/my-tickets-paypal-pro/">My Tickets: PayPal Pro</a></li>
						<li><a href="https://www.joedolson.com/awesome/tickets-authorize-net/">My Tickets: Authorize.net</a></li>
					</ul>
				</div>
			</div>
		</div>

		<div class="ui-sortable meta-box-sortables">
			<div class="postbox">
				<h2 class="hndle"><?php _e( 'Get My Tickets Help', 'my-tickets' ); ?></h2>

				<div class="inside">
					<ul>
						<li>
							<div class="dashicons dashicons-editor-help" aria-hidden="true"></div>
							<strong><a href="<?php echo admin_url( 'admin.php?page=mt-help' ); ?>#get-started"><?php _e( 'Getting Started', 'my-tickets' ); ?></strong></a></li>
						<li>
							<div class="dashicons dashicons-editor-help" aria-hidden="true"></div>
							<a href="<?php echo admin_url( 'admin.php?page=mt-help' ); ?>#get-support"><?php _e( 'Get Support', 'my-tickets' ); ?></a>
						</li>
						<li>
							<div class="dashicons dashicons-editor-help" aria-hidden="true"></div>
							<a href="<?php echo admin_url( 'admin.php?page=mt-help' ); ?>#faq"><?php _e( 'My Tickets FAQ', 'my-tickets' ); ?></a>
						</li>
						<li>
							<div class="dashicons dashicons-book-alt" aria-hidden="true"></div>
							<a href="http://docs.joedolson.com/my-tickets/"><?php _e( 'Documentation', 'my-tickets' ); ?></a>
						</li>
						<li>
							<div class="dashicons dashicons-yes" aria-hidden="true"></div>
							<a href="http://profiles.wordpress.org/joedolson/"><?php _e( 'Check out my other plug-ins', 'my-tickets' ); ?></a>
						</li>
						<li>
							<div class="dashicons dashicons-star-filled" aria-hidden="true"></div>
							<a href="http://wordpress.org/plugins/my-tickets/"><?php _e( 'Rate this plug-in', 'my-tickets' ); ?></a>
						</li>
					</ul>
				</div>
			</div>
		</div>

		<div class="ui-sortable meta-box-sortables">
			<div class="postbox">
				<h2 class="hndle"><?php _e( 'Find me on Social Media', 'my-tickets' ); ?></h2>
				<div id="support" class="inside resources">
					<ul class="mt-flex mt-social">
						<li><a href="https://toot.io/@joedolson">
							<svg aria-hidden="true" width="24" height="24" viewBox="0 0 61 65" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M60.7539 14.3904C59.8143 7.40642 53.7273 1.90257 46.5117 0.836066C45.2943 0.655854 40.6819 0 29.9973 0H29.9175C19.2299 0 16.937 0.655854 15.7196 0.836066C8.70488 1.87302 2.29885 6.81852 0.744617 13.8852C-0.00294988 17.3654 -0.0827298 21.2237 0.0561464 24.7629C0.254119 29.8384 0.292531 34.905 0.753482 39.9598C1.07215 43.3175 1.62806 46.6484 2.41704 49.9276C3.89445 55.9839 9.87499 61.0239 15.7344 63.0801C22.0077 65.2244 28.7542 65.5804 35.2184 64.1082C35.9295 63.9428 36.6318 63.7508 37.3252 63.5321C38.8971 63.0329 40.738 62.4745 42.0913 61.4937C42.1099 61.4799 42.1251 61.4621 42.1358 61.4417C42.1466 61.4212 42.1526 61.3986 42.1534 61.3755V56.4773C42.153 56.4557 42.1479 56.4345 42.1383 56.4151C42.1287 56.3958 42.1149 56.3788 42.0979 56.3655C42.0809 56.3522 42.0611 56.3429 42.04 56.3382C42.019 56.3335 41.9971 56.3336 41.9761 56.3384C37.8345 57.3276 33.5905 57.8234 29.3324 57.8156C22.0045 57.8156 20.0336 54.3384 19.4693 52.8908C19.0156 51.6397 18.7275 50.3346 18.6124 49.0088C18.6112 48.9866 18.6153 48.9643 18.6243 48.9439C18.6333 48.9236 18.647 48.9056 18.6643 48.8915C18.6816 48.8774 18.7019 48.8675 18.7237 48.8628C18.7455 48.858 18.7681 48.8585 18.7897 48.8641C22.8622 49.8465 27.037 50.3423 31.2265 50.3412C32.234 50.3412 33.2387 50.3412 34.2463 50.3146C38.4598 50.1964 42.9009 49.9808 47.0465 49.1713C47.1499 49.1506 47.2534 49.1329 47.342 49.1063C53.881 47.8507 60.1038 43.9097 60.7362 33.9301C60.7598 33.5372 60.8189 29.8148 60.8189 29.4071C60.8218 28.0215 61.2651 19.5781 60.7539 14.3904Z" fill="url(#paint0_linear_89_8)"/><path d="M50.3943 22.237V39.5876H43.5185V22.7481C43.5185 19.2029 42.0411 17.3949 39.036 17.3949C35.7325 17.3949 34.0778 19.5338 34.0778 23.7585V32.9759H27.2434V23.7585C27.2434 19.5338 25.5857 17.3949 22.2822 17.3949C19.2949 17.3949 17.8027 19.2029 17.8027 22.7481V39.5876H10.9298V22.237C10.9298 18.6918 11.835 15.8754 13.6453 13.7877C15.5128 11.7049 17.9623 10.6355 21.0028 10.6355C24.522 10.6355 27.1813 11.9885 28.9542 14.6917L30.665 17.5633L32.3788 14.6917C34.1517 11.9885 36.811 10.6355 40.3243 10.6355C43.3619 10.6355 45.8114 11.7049 47.6847 13.7877C49.4931 15.8734 50.3963 18.6899 50.3943 22.237Z" fill="white"/><defs><linearGradient id="paint0_linear_89_8" x1="30.5" y1="0" x2="30.5" y2="65" gradientUnits="userSpaceOnUse"><stop stop-color="#6364FF"/><stop offset="1" stop-color="#563ACC"/></linearGradient></defs></svg>
							<span class="screen-reader-text">Mastodon</span></a>
						</li>
						<li><a href="https://bsky.app/profile/joedolson.bsky.social">
							<svg width="24" height="24" viewBox="0 0 568 501" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M123.121 33.6637C188.241 82.5526 258.281 181.681 284 234.873C309.719 181.681 379.759 82.5526 444.879 33.6637C491.866 -1.61183 568 -28.9064 568 57.9464C568 75.2916 558.055 203.659 552.222 224.501C531.947 296.954 458.067 315.434 392.347 304.249C507.222 323.8 536.444 388.56 473.333 453.32C353.473 576.312 301.061 422.461 287.631 383.039C285.169 375.812 284.017 372.431 284 375.306C283.983 372.431 282.831 375.812 280.369 383.039C266.939 422.461 214.527 576.312 94.6667 453.32C31.5556 388.56 60.7778 323.8 175.653 304.249C109.933 315.434 36.0535 296.954 15.7778 224.501C9.94525 203.659 0 75.2916 0 57.9464C0 -28.9064 76.1345 -1.61183 123.121 33.6637Z" fill="#1185fe"/></svg>
							<span class="screen-reader-text">Bluesky</span></a>
						</li>
						<li><a href="https://linkedin.com/in/joedolson">
							<svg aria-hidden="true" height="24" viewBox="0 0 72 72" width="24" xmlns="http://www.w3.org/2000/svg"><g fill="none" fill-rule="evenodd"><path d="M8,72 L64,72 C68.418278,72 72,68.418278 72,64 L72,8 C72,3.581722 68.418278,-8.11624501e-16 64,0 L8,0 C3.581722,8.11624501e-16 -5.41083001e-16,3.581722 0,8 L0,64 C5.41083001e-16,68.418278 3.581722,72 8,72 Z" fill="#007EBB"/><path d="M62,62 L51.315625,62 L51.315625,43.8021149 C51.315625,38.8127542 49.4197917,36.0245323 45.4707031,36.0245323 C41.1746094,36.0245323 38.9300781,38.9261103 38.9300781,43.8021149 L38.9300781,62 L28.6333333,62 L28.6333333,27.3333333 L38.9300781,27.3333333 L38.9300781,32.0029283 C38.9300781,32.0029283 42.0260417,26.2742151 49.3825521,26.2742151 C56.7356771,26.2742151 62,30.7644705 62,40.051212 L62,62 Z M16.349349,22.7940133 C12.8420573,22.7940133 10,19.9296567 10,16.3970067 C10,12.8643566 12.8420573,10 16.349349,10 C19.8566406,10 22.6970052,12.8643566 22.6970052,16.3970067 C22.6970052,19.9296567 19.8566406,22.7940133 16.349349,22.7940133 Z M11.0325521,62 L21.769401,62 L21.769401,27.3333333 L11.0325521,27.3333333 L11.0325521,62 Z" fill="#FFF"/></g></svg>
							<span class="screen-reader-text">LinkedIn</span></a>
						</li>
						<li><a href="https://github.com/joedolson">
							<svg aria-hidden="true" width="24" height="24" viewBox="0 0 1024 1024" fill="none" xmlns="http://www.w3.org/2000/svg"><path fill-rule="evenodd" clip-rule="evenodd" d="M8 0C3.58 0 0 3.58 0 8C0 11.54 2.29 14.53 5.47 15.59C5.87 15.66 6.02 15.42 6.02 15.21C6.02 15.02 6.01 14.39 6.01 13.72C4 14.09 3.48 13.23 3.32 12.78C3.23 12.55 2.84 11.84 2.5 11.65C2.22 11.5 1.82 11.13 2.49 11.12C3.12 11.11 3.57 11.7 3.72 11.94C4.44 13.15 5.59 12.81 6.05 12.6C6.12 12.08 6.33 11.73 6.56 11.53C4.78 11.33 2.92 10.64 2.92 7.58C2.92 6.71 3.23 5.99 3.74 5.43C3.66 5.23 3.38 4.41 3.82 3.31C3.82 3.31 4.49 3.1 6.02 4.13C6.66 3.95 7.34 3.86 8.02 3.86C8.7 3.86 9.38 3.95 10.02 4.13C11.55 3.09 12.22 3.31 12.22 3.31C12.66 4.41 12.38 5.23 12.3 5.43C12.81 5.99 13.12 6.7 13.12 7.58C13.12 10.65 11.25 11.33 9.47 11.53C9.76 11.78 10.01 12.26 10.01 13.01C10.01 14.08 10 14.94 10 15.21C10 15.42 10.15 15.67 10.55 15.59C13.71 14.53 16 11.53 16 8C16 3.58 12.42 0 8 0Z" transform="scale(64)" fill="#1B1F23"/></svg>
							<span class="screen-reader-text">GitHub</span></a>
						</li>
					</ul>
				</div>
			</div>
		</div>
	</div>
	</div>
	<?php
}

add_filter( 'mt_money_format', 'mt_money_format', 10, 1 );
/**
 * Format money for use in cart or other context.
 *
 * @param float $price Price.
 *
 * @return string
 */
function mt_money_format( $price ) {
	if ( is_numeric( $price ) ) {
		$options       = mt_get_settings();
		$symbol        = mt_symbols( $options['mt_currency'] );
		$dec_point     = $options['mt_dec_point'];
		$thousands_sep = $options['mt_thousands_sep'];
		$order         = $options['symbol_order'];
		$price         = '<span class="price">' . number_format( $price, 2, $dec_point, $thousands_sep ) . '</span>';
		$space         = ( 'symbol-first' === $order ) ? '' : ' ';
		/**
		 * Filter the character used to separate the currency symbol from the value.
		 *
		 * @hook mt_money_format_spacer
		 *
		 * @param {string} $space Spacing character. Default empty string or single space.
		 * @param {string} $price Formatted price without currency symbols.
		 *
		 * @return {string}
		 */
		$space = apply_filters( 'mt_money_format_spacer', $space, $price );

		return ( 'symbol-first' === $order ) ? $symbol . $space . $price : $price . $space . $symbol;
	} else {
		return '';
	}
}

/**
 * See whether field should be checked.
 *
 * @param string $field Field name.
 * @param mixed  $value Saved value.
 * @param array  $options Field options.
 *
 * @return bool
 */
function mt_is_checked( $field, $value, $options ) {
	if ( isset( $options[ $field ] ) && $options[ $field ] === $value ) {
		$checked = true;
	} else {
		$checked = false;
	}

	return $checked;
}

add_action( 'show_user_profile', 'mt_user_profile' );
add_action( 'edit_user_profile', 'mt_user_profile' );
/**
 * Display an account holder's shopping cart in their user profile.
 *
 * @return void
 */
function mt_user_profile() {
	if ( isset( $_GET['user_id'] ) ) {
		$edit_user = ( is_numeric( $_GET['user_id'] ) ) ? $_GET['user_id'] : false;
	} else {
		$current_user = wp_get_current_user();
		$edit_user    = $current_user->ID;
	}
	if ( current_user_can( 'manage_options' ) ) {
		echo '<div class="mt-user-settings"><fieldset><legend style="font-size:1rem;font-weight:600">' . esc_html( __( 'My Tickets Permissions', 'my-tickets' ) ) . '</legend>';
		$caps    = array(
			'mt-verify-ticket' => __( 'Can verify tickets', 'my-tickets' ),
			'mt-order-expired' => __( 'Can place orders after expiration dates.', 'my-tickets' ),
			'mt-view-reports'  => __( 'Can view reports', 'my-tickets' ),
			'mt-copy-cart'     => __( 'Can import user shopping carts', 'my-tickets' ),
			'mt-order-comps'   => __( 'Can order complimentary tickets', 'my-tickets' ),
		);
		$options = '';
		foreach ( $caps as $cap => $label ) {
			$checked  = ( user_can( $edit_user, $cap ) ) ? ' checked="checked"' : '';
			$options .= "<li><input type='checkbox' name='mt_capabilities[]' value='$cap' id='mt_$cap' $checked /> <label for='mt_$cap'>$label</label></li>";
		}
		$options = "<ul>$options</ul></fieldset></div>";
		echo wp_kses( $options, mt_kses_elements() );
	}
	if ( current_user_can( 'mt-copy-cart' ) || current_user_can( 'edit_user' ) ) {
		echo '<h3>' . esc_html__( 'My Tickets Shopping Cart', 'my-tickets' ) . '</h3>';
		$cart         = mt_get_cart( $edit_user );
		$confirmation = mt_generate_cart_table( $cart, false, 'confirmation' );
		echo wp_kses( $confirmation . "<p><a href='" . admin_url( "post-new.php?post_type=mt-payments&amp;cart=$edit_user" ) . "'>" . __( 'Create new payment with this cart', 'my-tickets' ) . '</a></p>', mt_kses_elements() );
	}
}

add_action( 'profile_update', 'mt_save_profile' );
/**
 * Update user capabilities to apply selected My Tickets capabilities
 */
function mt_save_profile() {
	$current_user = wp_get_current_user();
	$user_ID      = $current_user->ID;
	if ( isset( $_POST['user_id'] ) ) {
		$edit_id = (int) $_POST['user_id'];
	} else {
		$edit_id = $user_ID;
	}
	$user = get_user_by( 'id', $edit_id );
	if ( isset( $_POST['mt_capabilities'] ) && current_user_can( 'manage_options' ) ) {
		$caps = array(
			'mt-verify-ticket',
			'mt-order-expired',
			'mt-view-reports',
			'mt-copy-cart',
			'mt-order-comps',
		);
		foreach ( $_POST['mt_capabilities'] as $add_cap ) {
			$user->add_cap( $add_cap );
		}
		$merged = array_diff( $caps, $_POST['mt_capabilities'] );
		foreach ( $merged as $remove_cap ) {
			$user->remove_cap( $remove_cap );
		}
	}
}

add_action( 'admin_init', 'mt_check_permissions' );
/**
 * Check what permissions current user has and apply as needed.
 */
function mt_check_permissions() {
	if ( current_user_can( 'manage_options' ) && ! current_user_can( 'mt-verify-ticket' ) ) {
		// if the current user can manage options, they might as well be able to do MT tasks.
		global $current_user;

		$user_roles = $current_user->roles;
		$user_role  = array_shift( $user_roles );

		$role = get_role( $user_role );
		$role->add_cap( 'mt-verify-ticket' );
		$role->add_cap( 'mt-order-comps' );
		$role->add_cap( 'mt-order-expired' );
		$role->add_cap( 'mt-view-reports' );
		$role->add_cap( 'mt-copy-cart' );
	}
}

/**
 * Custom KSES allowed elements for sanitizing input fields and forms.
 *
 * @return array
 */
function mt_kses_elements() {
	$elements = array(
		'h2'               => array(),
		'h3'               => array(),
		'h4'               => array(),
		'option'           => array(
			'value'    => array(),
			'selected' => array(),
		),
		'select'           => array(
			'id'               => array(),
			'aria-describedby' => array(),
			'aria-labelledby'  => array(),
			'name'             => array(),
			'disabled'         => array(),
			'min'              => array(),
			'max'              => array(),
			'required'         => array(),
			'readonly'         => array(),
			'autocomplete'     => array(),
			'class'            => array(),
		),
		'duet-date-picker' => array(
			'identifier'        => array(),
			'first-day-of-week' => array(),
			'name'              => array(),
			'value'             => array(),
			'required'          => array(),
		),
		'label'            => array(
			'for'   => array(),
			'class' => array(),
		),
		'input'            => array(
			'id'               => array(),
			'class'            => array(),
			'aria-describedby' => array(),
			'aria-labelledby'  => array(),
			'value'            => array(),
			'type'             => array(),
			'name'             => array(),
			'size'             => array(),
			'checked'          => array(),
			'disabled'         => array(),
			'min'              => array(),
			'max'              => array(),
			'required'         => array(),
			'readonly'         => array(),
			'autocomplete'     => array(),
			'step'             => array(),
			'placeholder'      => array(),
		),
		'textarea'         => array(
			'id'               => array(),
			'class'            => array(),
			'cols'             => array(),
			'rows'             => array(),
			'aria-describedby' => array(),
			'aria-labelledby'  => array(),
			'disabled'         => array(),
			'required'         => array(),
			'readonly'         => array(),
			'name'             => array(),
		),
		'form'             => array(
			'id'     => array(),
			'name'   => array(),
			'action' => array(),
			'method' => array(),
			'class'  => array(),
		),
		'button'           => array(
			'name'             => array(),
			'disabled'         => array(),
			'type'             => array(),
			'class'            => array(),
			'aria-expanded'    => array(),
			'aria-describedby' => array(),
			'role'             => array(),
			'aria-selected'    => array(),
			'aria-controls'    => array(),
			'data-event'       => array(),
			'data-ticket'      => array(),
			'data-payment'     => array(),
			'id'               => array(),
			'aria-pressed'     => array(),
			'data-model'       => array(),
			'data-event'       => array(),
		),
		'ul'               => array(
			'class' => array(),
		),
		'fieldset'         => array(),
		'legend'           => array(),
		'li'               => array(
			'class' => array(),
		),
		'span'             => array(
			'id'          => array(),
			'class'       => array(),
			'itemprop'    => array(),
			'itemscope'   => array(),
			'itemtype'    => array(),
			'aria-live'   => array(),
			'aria-hidden' => array(),
		),
		'strong'           => array(
			'id'    => array(),
			'class' => array(),
		),
		'code'             => array(
			'id'    => array(),
			'class' => array(),
		),
		'p'                => array(
			'class' => array(),
		),
		'em'               => array(
			'id' => array(),
		),
		'div'              => array(
			'class'           => array(),
			'aria-live'       => array(),
			'id'              => array(),
			'role'            => array(),
			'data-default'    => array(),
			'aria-labelledby' => array(),
			'aria-label'      => array(),
		),
		'img'              => array(
			'class'    => true,
			'src'      => true,
			'alt'      => true,
			'width'    => true,
			'height'   => true,
			'id'       => true,
			'longdesc' => true,
			'tabindex' => true,
		),
		'br'               => array(),
		'table'            => array(
			'class' => array(),
			'id'    => array(),
		),
		'caption'          => array(),
		'thead'            => array(),
		'tfoot'            => array(),
		'tbody'            => array(),
		'tr'               => array(
			'class' => array(),
			'id'    => array(),
		),
		'th'               => array(
			'scope' => array(),
			'class' => array(),
			'id'    => array(),
		),
		'td'               => array(
			'class'     => array(),
			'id'        => array(),
			'aria-live' => array(),
		),
		'a'                => array(
			'aria-labelledby'  => true,
			'aria-describedby' => true,
			'href'             => true,
			'class'            => true,
		),
		'pre'              => array(),
	);

	return $elements;
}

/**
 * Ensure that expected style variables are always present.
 *
 * @param array $styles Array of style variables saved in settings.
 *
 * @return array
 */
function mt_style_variables( $styles = array() ) {
	$core_styles = array(
		'--mt-order-background' => '#f6f7f7',
		'--mt-order-shadow'     => '#dcdcde',
		'--mt-error-color'      => '#b32d2e',
		'--mt-error-border'     => '#b32d2e',
		'--mt-text-color'       => '#2c3338',
		'--mt-success-color'    => '#007017',
		'--mt-success-border'   => '#007017',
		'--mt-message-bg'       => '#f0f6fc',
		'--mt-message-color'    => '#2c3338',
		'--mt-field-background' => '#f6f7f7',
		'--mt-field-color'      => '#2c3338',
		'--mt-field-border'     => '#50575e',
	);
	foreach ( $core_styles as $key => $value ) {
		if ( ! isset( $styles[ $key ] ) ) {
			$styles[ $key ] = $value;
		}
	}

	return $styles;
}

/**
 * Generate ticketing CSS output.
 */
function mt_generate_css() {
	$styles     = (array) mt_get_settings( 'style_vars' );
	$styles     = mt_style_variables( $styles );
	$style_vars = '';
	foreach ( $styles as $key => $var ) {
		if ( $var ) {
			$style_vars .= sanitize_key( $key ) . ': ' . $var . '; ';
		}
	}
	if ( '' !== $style_vars ) {
		$style_vars = '.my-tickets {' . $style_vars . '}';
	}

	$css = "
/* Styles by My Tickets - Joe Dolson https://www.joedolson.com/ */
$style_vars";

	return $css;
}
