<?php
/**
 * My Tickets, Accessible ticket sales for WordPress
 *
 * @package     My Tickets
 * @author      Joe Dolson
 * @copyright   2014-2022 Joe Dolson
 * @license     GPL-2.0+
 *
 * @wordpress-plugin
 * Plugin Name: My Tickets
 * Plugin URI:  http://www.joedolson.com/my-tickets/
 * Description: Sell Tickets and take registrations for your events. Integrates with My Calendar.
 * Author:      Joseph C Dolson
 * Author URI:  http://www.joedolson.com
 * Text Domain: my-tickets
 * License:     GPL-2.0+
 * License URI: http://www.gnu.org/license/gpl-2.0.txt
 * Domain Path: lang
 * Version:     1.9.6
 */

/*
	Copyright 2014-2022  Joe Dolson (email : joe@joedolson.com)

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

load_plugin_textdomain( 'my-tickets', false, dirname( plugin_basename( __FILE__ ) ) . '/lang' );

/**
 * Return current version of My Tickets.
 *
 * @return string Current My Tickets version.
 */
function mt_get_current_version() {
	$mt_version = '1.9.6';

	return $mt_version;
}

add_action( 'admin_notices', 'mt_status_notice', 10 );
/**
 * Display promotion notice to admin users who have not donated or purchased WP Tweets PRO.
 */
function mt_status_notice() {
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
				// Translators: URL to settings page.
				echo "<div class='error notice'><p>" . sprintf( __( 'The required My Tickets cart page is not publicly available. <a href="%s" class="button-secondary">Check settings</a>', 'my-tickets' ), $settings ) . '</p></div>';
			}
		}
		if ( ! $receipt || 'publish' !== get_post_status( $receipt ) ) {
			if ( ! $receipt ) {
				// Translators: URL to settings page.
				echo "<div class='error notice'><p>" . sprintf( __( 'The required My Tickets receipts page is not assigned. <a href="%s" class="button-secondary">Check settings</a>', 'my-tickets' ), $settings ) . '</p></div>';
			} else {
				// Translators: URL to settings page.
				echo "<div class='error notice'><p>" . sprintf( __( 'The required My Tickets receipts page is not publicly available. <a href="%s" class="button-secondary">Check settings</a>', 'my-tickets' ), $settings ) . '</p></div>';
			}
		}
		if ( ! $tickets || 'publish' !== get_post_status( $tickets ) ) {
			if ( ! $tickets ) {
				// Translators: URL to settings page.
				echo "<div class='error notice'><p>" . sprintf( __( 'The required My Tickets tickets page is not assigned. <a href="%s" class="button-secondary">Check settings</a>', 'my-tickets' ), $settings ) . '</p></div>';
			} else {
				// Translators: URL to settings page.
				echo "<div class='error notice'><p>" . sprintf( __( 'The required My Tickets tickets page is not publicly available. <a href="%s" class="button-secondary">Check settings</a>', 'my-tickets' ), $settings ) . '</p></div>';
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
	while ( $file = readdir( $handler ) ) { // phpcs:ignore WordPress.CodeAnalysis.AssignmentInCondition.FoundInWhileCondition
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
	$gateways = apply_filters( 'mt_import_gateways', mt_import_gateways() );
	foreach ( $gateways as $gateway ) {
		if ( false !== strpos( $gateway, '.php' ) ) {
			require_once( plugin_dir_path( __FILE__ ) . 'gateways/' . $gateway );
		}
	}
}

include( plugin_dir_path( __FILE__ ) . 'mt-common.php' );
include( plugin_dir_path( __FILE__ ) . 'mt-cpt.php' );
include( plugin_dir_path( __FILE__ ) . 'mt-fields-api.php' );
include( plugin_dir_path( __FILE__ ) . 'mt-payment.php' );
include( plugin_dir_path( __FILE__ ) . 'mt-reports.php' );
include( plugin_dir_path( __FILE__ ) . 'mt-notifications.php' );
include( plugin_dir_path( __FILE__ ) . 'mt-help.php' );
include( plugin_dir_path( __FILE__ ) . 'mt-processing.php' );
include( plugin_dir_path( __FILE__ ) . 'mt-cart.php' );
include( plugin_dir_path( __FILE__ ) . 'mt-cart-handler.php' );
include( plugin_dir_path( __FILE__ ) . 'mt-ajax.php' );
include( plugin_dir_path( __FILE__ ) . 'mt-tickets.php' );
include( plugin_dir_path( __FILE__ ) . 'mt-receipt.php' );
include( plugin_dir_path( __FILE__ ) . 'mt-shortcodes.php' );
include( plugin_dir_path( __FILE__ ) . 'class-mt-short-cart-widget.php' );
include( plugin_dir_path( __FILE__ ) . 'mt-button.php' );
include( plugin_dir_path( __FILE__ ) . 'mt-templating.php' );
include( plugin_dir_path( __FILE__ ) . 'mt-settings.php' );
include( plugin_dir_path( __FILE__ ) . 'mt-payment-settings.php' );
include( plugin_dir_path( __FILE__ ) . 'mt-ticketing-settings.php' );
include( plugin_dir_path( __FILE__ ) . 'mt-debug.php' );

// Not used by core plug-in; only if premium add-ons are installed.
if ( ! class_exists( 'EDD_SL_Plugin_Updater' ) ) {
	require_once( plugin_dir_path( __FILE__ ) . 'updates/EDD_SL_Plugin_Updater.php' );
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
	$options = array_merge( mt_default_settings(), get_option( 'mt_settings', array() ) );
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
		$options = array_merge( mt_default_settings(), get_option( 'mt_settings', array() ) );
		if ( absint( $options['mt_purchase_page'] ) === $post->ID ) {
			$states[] = __( 'Shopping Cart Page', 'my-calendar' );
		}
		if ( absint( $options['mt_receipt_page'] ) === $post->ID ) {
			$states[] = __( 'Receipt Page', 'my-calendar' );
		}
		if ( absint( $options['mt_purchase_page'] ) === $post->ID ) {
			$states[] = __( 'Ticket Page', 'my-calendar' );
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
	$icon_path  = plugins_url( '/my-tickets/images' );
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
			'content' => '<p><strong>' . __( 'General Ticketing Options' ) . '</strong><br />' . __( 'These options are global to all tickets. They include shipping rates, administrative fees, types of tickets available to your customers, and how you reserve tickets for sales at your ticket office.', 'my-tickets' ) . '</p>',
		)
	);
	$screen->add_help_tab(
		array(
			'id'      => 'mt_ticketing_help_tab_2',
			'title'   => __( 'Ticket Defaults', 'my-tickets' ),
			'content' => '<p><strong>' . __( 'Ticket Defaults' ) . '</strong><br />' . __( 'Ticket defaults are settings that are specific to events. These values are what will be set up by default when you create a new event, but can be changed within the event. Only events that have a value entered for the number of tickets available for purchase will show up for sale on your site.', 'my-tickets' ) . '</p>',
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
	wp_enqueue_style( 'mt-styles', plugins_url( '/css/my-tickets.css', __FILE__ ) );
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

	$options  = array_merge( mt_default_settings(), get_option( 'mt_settings', array() ) );
	$symbol   = mt_symbols( $options['mt_currency'] );
	$cart_url = esc_url( get_permalink( $options['mt_purchase_page'] ) );
	$redirect = isset( $options['mt_redirect'] ) ? $options['mt_redirect'] : '0';
	$redirect = apply_filters( 'mt_redirect', $redirect );

	wp_enqueue_script( 'mt.payment', plugins_url( 'js/jquery.payment.js', __FILE__ ), array( 'jquery' ) );
	wp_enqueue_script( 'mt.public', plugins_url( 'js/jquery.public.js', __FILE__ ), array( 'jquery' ) );
	wp_enqueue_style( 'mt-styles', $ticket_styles );
	wp_localize_script(
		'mt.public',
		'mt_ajax',
		array(
			'action'   => 'mt_ajax_handler',
			'url'      => admin_url( 'admin-ajax.php' ),
			'security' => wp_create_nonce( 'mt-cart-nonce' ),
			'currency' => $symbol,
			'cart_url' => $cart_url,
			'redirect' => $redirect,
		)
	);
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

	$messages  = array(
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
	$ticketing = array(
		'free'            => '',
		'sales_type'      => 'tickets',
		'counting_method' => 'continuous',
		'reg_expires'     => '3',
		'multiple'        => 'true',
		'pricing'         => array(
			'adult'          => array(
				'label'   => 'Adult',
				'price'   => '',
				'tickets' => '',
				'sold'    => '',
			),
			'senior-student' => array(
				'label'   => 'Senior/Student',
				'price'   => '',
				'tickets' => '',
				'sold'    => '',
			),
			'child'          => array(
				'label'   => 'Child',
				'price'   => '',
				'tickets' => '',
				'sold'    => '',
			),
		),
	);

	$defaults = array(
		// Messages following registration/ticket order & payment.
		'defaults'               => $ticketing,
		'messages'               => $messages,
		'mt_post_types'          => array( 'mc-events' ),
		'mt_license_key'         => '',
		'mt_html_email'          => 'true',
		'mt_to'                  => get_bloginfo( 'admin_email' ),
		'mt_from'                => get_bloginfo( 'admin_email' ),
		'mt_use_sandbox'         => 'false',
		'mt_currency'            => 'USD',
		'mt_dec_point'           => '.',
		'mt_thousands_sep'       => ',',
		'mt_phone'               => 'off',
		'mt_vat'                 => 'off',
		'mt_redirect'            => '0',
		'mt_members_discount'    => '',
		'mt_ssl'                 => 'false',
		'mt_gateway'             => array( 'offline' ),
		'mt_default_gateway'     => 'offline',
		'mt_purchase_page'       => '',
		'mt_receipt_page'        => '',
		'mt_tickets_page'        => '',
		'mt_ticketing'           => array( 'printable' => 'on' ),
		'mt_shipping'            => 0,
		'mt_handling'            => 0,
		'mt_shipping_time'       => '3-5 days',
		'mt_gateways'            => $gateways,
		'mt_ticket_handling'     => '',
		'mt_tickets_close_value' => '',
		'mt_tickets_close_type'  => 'integer',
		'mt_ticket_image'        => 'ticket',
		'symbol_order'           => 'symbol-first',
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
			return dirname( __FILE__ ) . '/templates/verify.php';
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
	global $wp_admin_bar;
	$url  = add_query_arg( 'mt_delete', 'true', home_url() );
	$args = array(
		'id'    => 'mt_delete',
		'title' => __( 'Empty Cart', 'my-tickets' ),
		'href'  => $url,
	);
	$wp_admin_bar->add_node( $args );
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
	apply_filters( 'debug', 'my tickets add actions/filters' );
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
	$options = array_merge( mt_default_settings(), get_option( 'mt_settings', array() ) );
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
						printf( __( 'Want to do more with My Tickets? <a href="%s">Premium add-ons are available!</a>!', 'my-tickets' ), 'https://www.joedolson.com/my-tickets/add-ons/' );
						?>
					</strong></p>
					<ul>
						<li><a href="https://www.joedolson.com/awesome/my-tickets-discounts/">My Tickets: Discounts</a></li>
						<li><a href="https://www.joedolson.com/awesome/my-tickets-donations/">My Tickets: Donations</a></li>
						<li><a href="https://www.joedolson.com/awesome/my-tickets-waiting-list/">My Tickets: Waiting List</a></li>
						<li><a href="https://www.joedolson.com/awesome/my-tickets-stripe/">My Tickets: Stripe</a></li>
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
					</ul>
				</div>
			</div>
		</div>

		<div class="ui-sortable meta-box-sortables">
			<div class="postbox">
				<h2 class="hndle"><?php _e( 'Support this Plug-in', 'my-tickets' ); ?></h2>
				<div id="support" class="inside resources">
					<?php mt_logo( array( 'class' => 'mt-logo' ) ); ?>
					<ul>
						<li>
							<p>
								<a href="https://twitter.com/intent/follow?screen_name=joedolson" class="twitter-follow-button" data-size="small" data-related="joedolson">Follow @joedolson</a>
								<script>!function(d,s,id){var js,fjs=d.getElementsByTagName(s)[0];if (!d.getElementById(id)){js=d.createElement(s);js.id=id;js.src="https://platform.twitter.com/widgets.js";fjs.parentNode.insertBefore(js,fjs);}}(document,"script","twitter-wjs");</script>
							</p>
						</li>
						<li><p><?php _e( '<a href="http://www.joedolson.com/donate/">Make a donation today!</a> Every donation counts - donate $2, $10, or $100 and help me keep this plug-in running!', 'my-tickets' ); ?></p>
							<form action="https://www.paypal.com/cgi-bin/webscr" method="post">
								<div>
									<input type="hidden" name="cmd" value="_s-xclick" />
									<input type="hidden" name="hosted_button_id" value="B75RYAX9BMX6S" />
									<input type="image" src="https://www.paypalobjects.com/en_US/i/btn/btn_donate_LG.gif" border="0" name="submit" alt=	"Donate" />
									<img alt="" border="0" src="https://www.paypalobjects.com/en_US/i/scr/pixel.gif" width="1" height="1" />
								</div>
							</form>

						</li>
						<li><a href="http://profiles.wordpress.org/users/joedolson/"><?php _e( 'Check out my other plug-ins', 'my-tickets' ); ?></a></li>
						<li><a href="http://wordpress.org/extend/plugins/my-tickets/"><?php _e( 'Rate this plug-in', 'my-tickets' ); ?></a></li>
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
		$options       = array_merge( mt_default_settings(), get_option( 'mt_settings', array() ) );
		$symbol        = mt_symbols( $options['mt_currency'] );
		$dec_point     = $options['mt_dec_point'];
		$thousands_sep = $options['mt_thousands_sep'];
		$order         = $options['symbol_order'];
		$price         = '<span class="price">' . number_format( $price, 2, $dec_point, $thousands_sep ) . '</span>';
		$space         = ( 'symbol-first' === $order ) ? '' : ' ';
		$space         = apply_filters( 'mt_money_format_spacer', $space, $price );

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
 * @param bool   $return Return or echo.
 *
 * @return bool
 */
function mt_is_checked( $field, $value, $options, $return = false ) {
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
		echo wp_kses_post( '<h3>' . __( 'Grant My Tickets Permissions', 'my-tickets' ) . '</h3>' );
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
		$options = "<ul>$options</ul>";
		echo wp_kses( $options, mt_kses_elements() );
	}
	if ( current_user_can( 'mt-copy-cart' ) || current_user_can( 'edit_user' ) ) {
		echo wp_kses_post( '<h3>' . __( 'My Tickets Shopping Cart', 'my-tickets' ) . '</h3>' );
		$cart         = mt_get_cart( $edit_user );
		$confirmation = mt_generate_cart_table( $cart, 'confirmation' );
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
	if ( isset( $_POST['mt_capabilities'] ) ) {
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
 * Wrapper for date()
 *
 * @param string $format Format to use.
 * @param int    $timestamp Timestamp.
 * @param bool   $offset False to not add offset; if already provided with offset.
 *
 * @return string Formatted date.
 */
function mt_date( $format, $timestamp = false, $offset = true ) {
	if ( ! $timestamp ) {
		$timestamp = time();
	}
	if ( $offset ) {
		$offset = intval( get_option( 'gmt_offset', 0 ) ) * 60 * 60;
	} else {
		$offset = 0;
	}
	$timestamp = $timestamp + $offset;

	return gmdate( $format, $timestamp );
}

/**
 *  Get current time in the format of timestamp.
 *
 * @return int timestamp-like data.
 */
function mt_current_time() {
	$timestamp = time();
	$offset    = 60 * 60 * intval( get_option( 'gmt_offset', 0 ) );
	$timestamp = $timestamp + $offset;

	return $timestamp;
}

/**
 * Custom KSES allowed elements for sanitizing input fields and forms.
 *
 * @return array
 */
function mt_kses_elements() {
	$elements = array(
		'h2'       => array(),
		'h3'       => array(),
		'h4'       => array(),
		'label'    => array(
			'for' => array(),
		),
		'option'   => array(
			'value'    => array(),
			'selected' => array(),
		),
		'select'   => array(
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
		),
		'input'    => array(
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
		),
		'textarea' => array(
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
		'form'     => array(
			'id'     => array(),
			'name'   => array(),
			'action' => array(),
			'method' => array(),
			'class'  => array(),
		),
		'button'   => array(
			'name'             => array(),
			'disabled'         => array(),
			'type'             => array(),
			'class'            => array(),
			'aria-expanded'    => array(),
			'aria-describedby' => array(),
			'role'             => array(),
			'aria-selected'    => array(),
			'aria-controls'    => array(),
		),
		'ul'       => array(
			'class' => array(),
		),
		'fieldset' => array(),
		'legend'   => array(),
		'li'       => array(
			'class' => array(),
		),
		'span'     => array(
			'id'          => array(),
			'class'       => array(),
			'itemprop'    => array(),
			'itemscope'   => array(),
			'itemtype'    => array(),
			'aria-live'   => array(),
			'aria-hidden' => array(),
		),
		'strong'   => array(
			'id'    => array(),
			'class' => array(),
		),
		'code'     => array(
			'id'    => array(),
			'class' => array(),
		),
		'p'        => array(
			'class' => array(),
		),
		'em'       => array(
			'id' => array(),
		),
		'div'      => array(
			'class'           => array(),
			'aria-live'       => array(),
			'id'              => array(),
			'role'            => array(),
			'data-default'    => array(),
			'aria-labelledby' => array(),
		),
		'img'      => array(
			'class'    => true,
			'src'      => true,
			'alt'      => true,
			'width'    => true,
			'height'   => true,
			'id'       => true,
			'longdesc' => true,
			'tabindex' => true,
		),
		'br'       => array(),
		'table'    => array(
			'class' => array(),
			'id'    => array(),
		),
		'caption'  => array(),
		'thead'    => array(),
		'tfoot'    => array(),
		'tbody'    => array(),
		'tr'       => array(
			'class' => array(),
			'id'    => array(),
		),
		'th'       => array(
			'scope' => array(),
			'class' => array(),
			'id'    => array(),
		),
		'td'       => array(
			'class'     => array(),
			'id'        => array(),
			'aria-live' => array(),
		),
		'a'        => array(
			'aria-labelledby'  => true,
			'aria-describedby' => true,
			'href'             => true,
			'class'            => true,
		),
	);

	return $elements;
}
