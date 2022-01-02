<?php
/**
 * Tickets template.
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
	<title><?php bloginfo( 'blogname' ); ?> &bull; <?php _e( 'Tickets', 'my-tickets' ); ?> &bull; <?php mt_ticket_id(); ?></title>
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
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
		h1 {
			font-size: 1em;
			font-weight: 400;
			padding: 0;
			margin: 0;
			line-height: 1.2;
		}

		.ticket img {
			display: inline-block;
			float: left;
		}

		.ticket.eticket .post-thumbnail {
			margin: 2em auto;
			text-align: center;
			float: none;
		}

		.eticket .post-thumbnail img {
			float: none;
		}

		.ticket {
			padding: 1em;
			width: 800px;
			height: 200px;
		}

		.bulk-tickets .ticket {
			margin-bottom: 2em;
		}

		.eticket.ticket {
			max-width: 480px;
			padding: 0;
			width: 100%;
			height: auto;
			border: none;
		}

		.eticket .ticket-data {
			padding: 0 2em 4em;
			position: relative;
		}

		.ticket .inside {
			position: relative;
			width: 100%;
			height: 100%;
		}

		.ticket .post-thumbnail {
			float: left;
			margin-right: 1em;
			width: 25%;
		}

		.ticket .post-thumbnail img {
			width: 100%;
			height: auto;
		}

		.ticket .post-content {
			position: absolute;
			bottom: 0;
			left: 25%;
			margin-left: 1em;
			font-size: .8em;
			color: #555;
			font-style: italic;
		}

		.eticket.ticket .post-content {
			margin-left: 0;
			font-size: .9em;
			position: static;
		}

		.ticket .ticket_id {
			position: absolute;
			bottom: -10px;
			right: 0;
			font-size: .7em;
			text-transform: uppercase;
		}

		.eticket .ticket_id {
			position: static;
			font-size: .8em;
			clear: both;
		}

		.ticket .event-date {
			color: #444;
			font-size: 1.1em;
		}

		.ticket .time {
			color: #000;
		}

		.ticket .event-title {
			font-size: 1.2em;
			font-weight: 700;
		}

		.ticket .ticket-type {
			margin-top: 2em;
			font-size: 1.3em;
			font-weight: 700;
		}

		.ticket .ticket-price {
			font-size: 1.6em;
		}

		.ticket .map {
			display: none;
		}

		.printable .ticket-qrcode, .willcall .ticket-qrcode {
			position: absolute;
			right: 0;
			bottom: 0;
		}

		.printable .ticket-qrcode img, .willcall .ticket-qrcode img {
			max-width: 96px;
		}

		.eticket .ticket-qrcode img {
			float: none;
			width: 100%;
			height: auto;
		}

		.ticket-venue {
			font-size: .9em;
			position: absolute;
			right: 0;
			top: 0;
			text-align: right;
		}

		.eticket .ticket-venue, .eticket .ticket-id {
			position: static;
			text-align: left;
			font-size: 1em;
		}

		@media only screen and (max-width: 800px) {
			.printable {
				padding: 1em;
				width: 90%;
				min-width: 320px;
				height: 500px;
			}

			.eticket.ticket {
				width: 100%;
				height: auto;
				padding: 0;
				border: none;
			}

			.eticket.ticket .inside {
				padding: 1em;
				height: auto;
			}

			.ticket .event-title {
				clear: left;
				padding-top: 1em;
			}

			.ticket-venue {
				position: static;
				text-align: left;
				margin-top: 2em;
			}

			.ticket .post-content {
				position: static;
			}

		}

		@media print {
			.bulk-tickets .ticket {
				page-break-inside: avoid;
			}
		}
	</style>
</head>
<body>
<div class='panel ticket <?php mt_ticket_method(); ?>'>
	<div class='inside'>
		<?php
		// load data from the Tickets Page.
		if ( have_posts() ) {
			while ( have_posts() ) {
				the_post();
				if ( 'eticket' !== mt_get_ticket_method() ) {
					?>
				<div class='post-thumbnail'>
					<?php mt_logo( array(), get_the_ID() ); ?>
				</div>
					<?php
				} else {
					?>
				<div class='ticket-qrcode'>
					<img src="<?php mt_ticket_qrcode(); ?>" alt="<?php __( 'QR Code Verification Link', 'my-tickets' ); ?>"/>
				</div>
					<?php
				}
				?>
				<div class="ticket-data">
					<h1 class='event-title'>
						<?php mt_event_title(); ?>
					</h1>

					<div class='event-date'>
						<?php
						if ( mt_get_ticket_validity() ) {
							echo mt_ticket_validity();
						} else {
							?>
							<?php mt_event_date(); ?> @ <span class='time'><?php mt_event_time(); ?></span>
							<?php
						}
						?>
					</div>
					<div class='ticket-type'>
						<?php mt_ticket_type(); ?>
					</div>
					<div class='ticket-price'>
						<?php mt_ticket_price(); ?>
					</div>
					<div class='ticket-venue'>
						<?php mt_ticket_venue(); ?>
					</div>
					<?php
					if ( mt_get_ticket_method() !== 'eticket' ) {
						?>
						<div class='ticket-qrcode'>
							<img src="<?php mt_ticket_qrcode(); ?>" alt="QR Code Verification Link"/>
						</div>
						<?php
					}
					?>
					<div class='post-content'>
					<?php
					$content = get_the_content();
					if ( '' === trim( strip_tags( $content ) ) ) {
						$content = ( current_user_can( 'edit_pages' ) ) ? __( 'Add your custom text into the post content.', 'my-tickets' ) : '';
					}
					echo $content;
					?>
					<?php edit_post_link(); ?>
					</div>
					<?php
					if ( 'eticket' === mt_get_ticket_method() ) {
						?>
						<div class='post-thumbnail'>
							<?php
							if ( has_post_thumbnail() ) {
								the_post_thumbnail( 'my-tickets-logo' );
							} else {
								mt_logo();
							}
							?>
						</div>
						<?php
					}
					?>
					<div class='ticket_id'>
						<?php mt_ticket_id(); ?>
					</div>
					<?php echo apply_filters( 'mt_custom_ticket', '', mt_get_ticket_id(), mt_get_ticket_method() ); ?>
				</div>
				<?php
			}
		}
		?>

	</div>
</div>
<?php
if ( 'printable' === mt_get_ticket_method() ) {
	?>
	<a href="javascript:window.print()" class="print">Print</a>
	<?php
}
?>
</body>
</html>
