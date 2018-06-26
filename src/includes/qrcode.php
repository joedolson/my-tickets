<?php

// get ticket ID from ticket template
$ticket = ( isset( $_GET['mt'] ) ) ? $_GET['mt'] : die( 'Invalid ticket ID' ); // ticket_ID
// sanitize ticket ID
$ticket = strtolower( preg_replace( "/[^a-z0-9\-]+/i", "", $ticket ) );

define('WP_USE_THEMES', false);
define('SHORTINIT', true );
if ( file_exists( '../../../../wp-load.php' ) ) {
	require_once( '../../../../wp-load.php' );
	require( ABSPATH . WPINC . '/formatting.php' );
	require( ABSPATH . WPINC . '/link-template.php' );
	require( ABSPATH . WPINC . '/kses.php' );
	$url = esc_url_raw( add_query_arg( array( 'ticket_id'=> $ticket, 'action' => 'mt-verify' ), home_url() ) );
} else {
	// if the above fails, we'll generate a URL, but it may be wrong.
	$url = MT_HOME_URL . "?ticket_id=$ticket&action=mt-verify";
}

require_once( 'phpqrcode/qrlib.php' );
// generate QRcode
QRcode::png( $url, false, QR_ECLEVEL_H, 12 );