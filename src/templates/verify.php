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
			padding: 0;
			margin: 0;
			background: #fff;
		}

		h1 {
			margin: 0;
		}

		.panel main {
			padding: 1em;
			margin: 0 auto;
		}

		.panel img {
			max-width: 100%;
			height: auto;
			display: block;
			margin: 0 auto;
		}

		.panel * {
			word-wrap: breakword;
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
			font-size: 1.5em;
		}

		.ticket-data {
			font-size: 1.2em;
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
	<header><?php mt_logo( array( 'alt' => mt_get_event_title() ) ); ?></header>
	<main>
		<h1 class='event-title'><?php mt_event_title(); ?></h1>
		<div class="ticket-data">
			<?php
			if ( ! mt_get_ticket_validity() ) {
				?>
				<p><?php mt_event_date(); ?> @ <span class='time'><?php mt_event_time(); ?></span></p>
				<?php
			}
			?>
			<p class='purchaser'>
				<strong><?php _e( 'Purchaser:', 'my-tickets' ); ?></strong> <?php mt_ticket_purchaser(); ?>
			</p>
			<p class='purchase-date'>
				<strong><?php _e( 'Purchased on:', 'my-tickets' ); ?></strong>
				<?php
					$purchase_id = mt_get_ticket_purchase_id();
					$date        = get_post_field( 'post_date', $purchase_id );
					$date        = date_i18n( get_option( 'date_format' ), strtotime( $date ) );
					echo esc_html( $date );
				?>
			</p>
		</div>
		<div class='mt-verification'><?php mt_verification(); ?></div>

	</main>
</div>

</body>
</html>
