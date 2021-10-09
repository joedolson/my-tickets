<?php
// phpcs:ignoreFile
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
		}

		.panel {
			padding: 1em;
			margin: 0 auto;
			border: 1px solid #999;
		}

		.panel img {
			display: block;
			margin: 0 auto;
		}

		.panel * {
			word-wrap: breakword;
		}

		.panel .post-footer {
			background: #eee;
			padding: 1em;
			margin: 0 -1em;
			font-size: .8em;
		}

		a.print {
			display: block;
			width: 100%;
			text-align: center;
		}

		.mt-verification div {
			padding: .5em;
		}

		.pending {
			background: #f5e6ab;
			border-left: 8px solid #755100;
		}

		.completed {
			background: #edfaef;
			border-left: 8px solid #005c12;
			font-weight: 700;
		}

		.completed.used {
			background: #facfd2;
			border-left: 8px solid #8a2424;
			font-weight: 700;
		}

		.mt-verification {
			font-size: 1.6em;
		}

		@media print {
			a.print {
				display: none;
			}
		}
	</style>
</head>
<body>
<div class='panel opt_out'>

	<h1 class='event-title'><?php _e( "You've opted out!", 'my-tickets' ); ?></h1>

	<p><?php _e( "We're sorry you don't want to hear from us about your purchase, and you won't hear from us again about this purchase, although we will still notify you about any future purchases. Thanks for your business!", 'my-tickets' ); ?></p>

</div>

</body>
</html>
