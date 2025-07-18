<?php
/**
 * Tickets template (multiple).
 *
 * @category Core
 * @package  My Tickets
 * @author   Joe Dolson
 * @license  GPLv3
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
			margin: 0 auto;
			border: 1px dashed #777;
		}

		.panel * {
			word-wrap: breakword;
			line-height: 1.5;
		}

		.panel .post-footer {
			background: #eee;
			padding: 1rem;
			margin: 0 -1rem;
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

		.ticket.eticket .post-thumbnail {
			margin: 2em auto;
			text-align: center;
		}

		.bulk-checkin,
		.ticket {
			padding: 1rem;
			width: 800px;
		}

		.bulk-checkin {
			margin: 0 auto;
			height: auto;
			display: flex;
			align-items: center;
			gap: 20px;
			background: rgba( 0,0,0,.05 );
			margin-bottom: 1rem;
			font-size: 1.5rem;
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
			display: grid;
			align-items: center;
			grid-template-columns: 25% 1fr 25%;
			gap: 1rem;
		}

		.ticket .post-thumbnail img {
			width: 100%;
			height: auto;
		}

		.ticket .post-content {
			margin-top: 2rem;
			font-size: .8em;
			color: #555;
			font-style: italic;
		}

		.eticket.ticket .post-content {
			margin-left: 0;
			font-size: .9em;
		}

		.ticket .ticket_id {
			font-size: .7em;
			text-transform: uppercase;
		}

		.eticket .ticket_id {
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

		.printable .ticket-qrcode img, .willcall .ticket-qrcode img {
			max-width: 120px;
		}

		.eticket .ticket-qrcode img {
			width: 100%;
			height: auto;
		}

		.ticket_id,
		.ticket-qrcode {
			text-align: right;
		}

		.ticket-venue {
			font-size: .8em;
			text-align: right;
		}

		.eticket .ticket-venue, .eticket .ticket-id {
			text-align: left;
			font-size: 1em;
		}

		@media only screen and (max-width: 800px) {
			.printable {
				padding: 1em;
				width: 90%;
				min-width: 320px;
			}

			.ticket .inside {
				display: block;
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

			.ticket .ticket-qrcode img {
				max-width: 200px;
			}

		}

		@media print {
			.bulk-tickets .ticket {
				page-break-inside: avoid;
			}
		}
	</style>
</head>
<body class="bulk-tickets">
<div class="bulk-checkin">
	<img src="<?php mt_purchase_qrcode(); ?>" alt="<?php _e( 'Check-in all tickets for this purchase', 'my-tickets' ); ?>" />
	<p><?php _e( 'Check-in all tickets on this purchase.', 'my-tickets' ); ?></p>
</div>
<?php
// load data from the Tickets Page.
if ( have_posts() ) {
	while ( have_posts() ) {
		the_post();
		// load ticket info.
		$purchases = mt_get_payment_tickets();
		foreach ( $purchases as $ticket_id ) {
			if ( ! mt_get_ticket_type( $ticket_id ) ) {
				continue;
			}
			?>
			<div class='panel ticket <?php mt_ticket_method( $ticket_id ); ?>'>
				<div class='inside'>
					<?php
					if ( 'eticket' !== mt_get_ticket_method( $ticket_id ) ) {
						?>
						<div class='post-thumbnail'>
							<?php mt_logo( array(), get_the_ID() ); ?>
						</div>
						<?php
					} else {
						?>
						<div class='ticket-qrcode'>
							<img src="<?php mt_ticket_qrcode( $ticket_id ); ?>" alt="<?php __( 'QR Code Verification Link', 'my-tickets' ); ?>"/>
						</div>
						<?php
					}
					?>
					<div class="ticket-data">
						<h1 class='event-title'>
							<?php mt_event_title( $ticket_id ); ?>
						</h1>

						<div class='event-date'>
							<?php mt_event_date_time( $ticket_id ); ?>
						</div>
						<div class='ticket-type'>
							<?php mt_ticket_type( $ticket_id ); ?>
						</div>
						<div class='ticket-price'>
							<?php mt_ticket_price( $ticket_id ); ?>
						</div>
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
						if ( 'eticket' === mt_get_ticket_method( $ticket_id ) ) {
							?>
							<div class='post-thumbnail'>
								<?php
								if ( has_post_thumbnail() ) {
									the_post_thumbnail();
								} else {
									mt_logo();
								}
								?>
							</div>
							<?php
						}
						?>
						<?php echo apply_filters( 'mt_custom_ticket', '', $ticket_id, mt_get_ticket_method( $ticket_id ) ); ?>
					</div>
					<div class="ticket-references">
						<div class='ticket-venue'>
							<?php mt_ticket_venue( $ticket_id ); ?>
						</div>
						<?php
						if ( 'eticket' !== mt_get_ticket_method( $ticket_id ) ) {
							?>
							<div class='ticket-qrcode'>
								<img src="<?php mt_ticket_qrcode( $ticket_id ); ?>" alt="QR Code Verification Link"/>
							</div>
							<?php
						}
						?>
						<div class='ticket_id'>
							<?php echo $ticket_id; ?>
						</div>
					</div>
				</div>
			</div>
			<?php
		}
	}
}
?>
</body>
</html>
