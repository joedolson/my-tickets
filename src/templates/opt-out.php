<?php
/**
 * Opt out template.
 *
 * @category Core
 * @package  My Tickets
 * @author   Joe Dolson
 * @license  GPLv2 or later
 * @link     https://www.joedolson.com/my-tickets/
 */

?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
	<title><?php bloginfo( 'blogname' ); ?> &bull; <?php _e( 'Opt Out', 'my-tickets' ); ?></title>
	<meta name='viewport' content='width=device-width, initial-scale=1.0'>
	<style>
		body {
			font-family: HelveticaNeue, Arial, Verdana, sans-serif;
			background: #e6efee;
		}

		.panel {
			padding: 1.5em;
			margin: 0 auto;
			max-width: 650px;
			border: 1px solid #999;
			box-shadow: 3px 3px 3px #999;
			background: #f6f6f6;
			color: #323232;
		}

		.panel * {
			margin-top: 0;
			margin-bottom: 1em;
			word-wrap: breakword;
		}
	</style>
</head>
<body>
<div class='panel opt_out'>
	<?php
	if ( isset( $_GET['oops'] ) ) {
		?>
		<h1 class="site-title"><?php bloginfo( 'blogname' ); ?></h1>
		<h2 class='event-title'><?php _e( "You've opted in!", 'my-tickets' ); ?></h2>
		<p><?php _e( 'You are now resubscribed to notifications about your ticket purchase!', 'my-tickets' ); ?></p>
		<?php
	} else {
		?>
		<h1 class="site-title"><?php bloginfo( 'blogname' ); ?></h1>
		<h2 class='event-title'><?php _e( "You've opted out!", 'my-tickets' ); ?></h2>
		<p><?php _e( "We're sorry you don't want to hear from us about your ticket purchase, and you won't hear from us again about this purchase, although we will still notify you about any future purchases. Thanks for your business!", 'my-tickets' ); ?></p>
		<p>
		<?php
		$nonce = wp_create_nonce( 'mt_resubscribe' );
		$url   = add_query_arg( 'opt_out', absint( $_GET['opt_out'] ), home_url() );
		$url   = add_query_arg( 'oops', $nonce, $url );
		// Translators: URL to resubscribe.
		printf( __( 'Whoops! I didn\'t mean to do that. <a href="%s">Resubscribe me</a>', 'my-tickets' ), $url );
		?>
		</p>
		<?php
	}
	?>
</div>

</body>
</html>
