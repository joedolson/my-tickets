<?php
/**
 * Render QR Codes on tickets
 *
 * @category Core
 * @package  My Tickets
 * @author   Joe Dolson
 * @license  GPLv2 or later
 * @link     https://www.joedolson.com/my-tickets/
 */

// get ticket ID from ticket template.
$ticket = ( isset( $_GET['mt'] ) ) ? $_GET['mt'] : die( 'Invalid ticket ID' );
// sanitize ticket ID.
$ticket = strtolower( preg_replace( '/[^a-z0-9\-]+/i', '', $ticket ) );

define( 'WP_USE_THEMES', false );
define( 'SHORTINIT', true );
$path = preg_replace( '/wp-content(?!.*wp-content).*/', '', __DIR__ );
if ( file_exists( $path . 'wp-load.php' ) ) {
	require_once( $path . 'wp-load.php' );
	if ( ! function_exists( 'wptexturize' ) ) {
		require( ABSPATH . WPINC . '/formatting.php' );
	}
	if ( ! function_exists( 'the_permalink' ) ) {
		require( ABSPATH . WPINC . '/link-template.php' );
	}
	if ( ! function_exists( 'wp_kses' ) ) {
		require( ABSPATH . WPINC . '/kses.php' );
	}
	$url = esc_url_raw(
		add_query_arg(
			array(
				'ticket_id' => $ticket,
				'action'    => 'mt-verify',
			),
			home_url()
		)
	);
} else {
	// if the above fails, we'll generate a URL, but it may be wrong.
	$url = MT_HOME_URL . "?ticket_id=$ticket&action=mt-verify";
}

require_once( 'phpqrcode/qrlib.php' );
// generate QRcode.
QRcode::png( $url, false, QR_ECLEVEL_H, 12 );
