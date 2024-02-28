<?php
/**
 * Bulk verification template.
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
	<title><?php bloginfo( 'blogname' ); ?> &bull; <?php _e( 'Bulk Verify Tickets', 'my-tickets' ); ?> &bull; <?php mt_cart_purchaser(); ?></title>
	<meta name='viewport' content='width=device-width, initial-scale=1.0'>
	<style>
		body {
			font-family: HelveticaNeue, Arial, Verdana, sans-serif;
			padding: 0;
			margin: 0;
			background: #fff;
		}
		header {
			display: grid;
			grid-template-columns: 120px 1fr;
			align-items: center;
			padding: .5rem;
			gap: 20px;
		}
		h1 {
			margin: 0;
		}
		.purchase-date {
			margin: 0;
			padding: 0;
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
			font-size: 1.3em;
		}

		.ticket-data {
			font-size: 1.2em;
		}

		.confirmation {
			margin-bottom: 1rem;
			padding: .5rem;	
		}

		.confirmation:nth-of-type(even) {
			background: rgba( 0,0,0,.03 );
			border-top: 1px solid #ccc;
			border-bottom: 1px solid #ccc;
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
<div class='panel bulk-verify'>
	<header>
		<?php mt_logo( array( 'alt' => get_bloginfo( 'blogname' ) ) ); ?>
		<div>
			<h1 class='event-title'><?php mt_cart_purchaser(); ?></h1>
			<p class='purchase-date'>
				<strong><?php _e( 'Purchased on:', 'my-tickets' ); ?></strong>
				<?php
					$date = mt_cart_purchase_date();
					echo esc_html( $date );
				?>
			</p>
		</div>
	</header>
	<main>
		<div class="mt-verified-tickets">
<?php
$purchases = mt_get_payment_tickets();
$int       = 0;
$count     = count( $purchases );
foreach ( $purchases as $ticket_id ) {
	++$int;
	?>
	<div class="confirmation">
		<h2>Ticket <?php echo $int . '/' . $count; ?></h2>
		<div class="ticket-data">
		<?php
		$ticket = mt_get_ticket( $ticket_id );
		if ( ! mt_get_ticket_validity( $ticket ) ) {
			?>
			<p><?php mt_event_date_time( $ticket_id ); ?></p>
			<?php
		}
		?>
		</div>
		<div class='mt-verification'><?php mt_verification( $ticket_id ); ?></div>
	</div>
	<?php
}
?>
		</div>
	</main>
</div>

</body>
</html>
