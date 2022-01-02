<?php
/**
 * Verification template.
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
	<title><?php bloginfo( 'blogname' ); ?> &bull; <?php _e( 'Verify Ticket', 'my-tickets' ); ?> &bull; <?php mt_ticket_id(); ?></title>
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

		.mt-verification span {
			font-weight: 400;
		}

		@media print {
			a.print {
				display: none;
			}
		}
	</style>
</head>
<body>
<div class='panel verify <?php mt_ticket_method(); ?>'>

	<h1 class='event-title'><?php mt_event_title(); ?></h1>

	<p><?php mt_event_date(); ?> @ <span class='time'><?php mt_event_time(); ?></span></p>

	<div class='mt-verification'><?php mt_verification(); ?></div>

</div>

</body>
</html>
