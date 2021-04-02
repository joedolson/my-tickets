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
	$records                    = '';
	$debug_log['Purchase Data'] = get_post_meta( $post_ID, '_purchase_data', true );
	if ( is_array( $debug_log ) ) {
		foreach ( $debug_log as $key => $entry ) {
			$records .= "<li><button type='button' class='toggle-debug button-secondary' aria-expanded='false'><strong>$key</strong></button><pre class='wpt-debug-details'>" . esc_html( $entry ) . '</pre></li>';
		}
	}
	$script = "
<script>
(function ($) {
$(function() {
	$( 'button.toggle-debug' ).on( 'click', function() {
		var next = $( this ).next( 'pre' );
		if ( $( this ).next( 'pre' ).is( ':visible' ) ) {
			$( this ).next( 'pre' ).hide();
			$( this ).attr( 'aria-expanded', 'false' );
		} else {
			$( this ).next( 'pre' ).show();
			$( this ).attr( 'aria-expanded', 'true' );
		}
	});
})
})(jQuery);
</script>";

	echo ( '' !== $records ) ? "$script<div class='mtt-debug-log'><h3>Debugging Log:</h3><ul>$records</ul></div>" : '';
}
