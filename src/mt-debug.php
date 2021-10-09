<?php
/**
 * Debugging Display.
 *
 * @category Core
 * @package  My Tickets
 * @author   Joe Dolson
 * @license  GPLv2 or later
 * @link     https://www.joedolson.com/my-tickets/
 */

add_action( 'admin_menu', 'mt_show_debug_box' );
/**
 * Set up post meta box.
 */
function mt_show_debug_box() {
	if ( MT_DEBUG && current_user_can( 'manage_options' ) ) {
		add_meta_box( 'mt-debug', 'My Tickets Debugging', 'mt_show_debug_data', 'mt-payments', 'advanced' );
	}
}


/**
 * Display debug log.
 */
function mt_show_debug_data() {
	global $post_ID;
	$records   = '';
	$debug_log = get_post_meta( $post_ID, '_debug_data' );
	if ( is_array( $debug_log ) ) {
		foreach ( $debug_log as $key => $entry ) {
			$datetime = get_option( 'date_format' ) . ' @ ' . get_option( 'time_format' );
			$records .= "<li><button type='button' class='toggle-debug button-secondary' aria-expanded='false'><strong>" . $entry['subject'] . ' / ' . date_i18n( $datetime, $entry['timestamp'] ) . "</strong></button><pre class='mt-debug-details'>" . print_r( $entry['data'], 1 ) . '</pre></li>';
		}
	}

	echo ( '' !== $records ) ? wp_kses_post( "<div class='mtt-debug-log'><h3>Debugging Log:</h3><ul>$records</ul></div>" ) : '';
}
