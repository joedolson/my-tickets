<?php
/**
 * Tickets template (multiple).
 *
 * @category Core
 * @package  My Tickets
 * @author   Joe Dolson
 * @license  GPLv2 or later
 * @link     https://www.joedolson.com/my-tickets/
 */

?>
<html>
<head>
	<title><?php bloginfo( 'blogname' ); ?> &bull; <?php _e( 'Tickets', 'my-tickets' ); ?> &bull; <?php mt_ticket_id(); ?></title>
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<link type="text/css" rel="stylesheet" href="<?php echo plugins_url( 'css/generic.css', __FILE__ ); ?>"/>
	<link type="text/css" rel="stylesheet" href="<?php echo plugins_url( 'css/ticket.css', __FILE__ ); ?>"/>
</head>
<body class="bulk-tickets">
<?php
// load data from the Receipts Page.
if ( have_posts() ) {
	while ( have_posts() ) {
		the_post();
		// load data from the Tickets Page.
		$purchases = mt_get_payment_tickets();
		foreach ( $purchases as $ticket_id ) {
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
							<?php mt_event_date( $ticket_id ); ?> @ <span
									class='time'><?php mt_event_time( $ticket_id ); ?></span>
						</div>
						<div class='ticket-type'>
							<?php mt_ticket_type( $ticket_id ); ?>
						</div>
						<div class='ticket-price'>
							<?php mt_ticket_price( $ticket_id ); ?>
						</div>
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
						<div class='ticket_id'>
							<?php echo $ticket_id; ?>
						</div>
						<?php echo apply_filters( 'mt_custom_ticket', '', $ticket_id, mt_get_ticket_method() ); ?>
					</div>

				</div>
			</div>
			<?php
		}

		if ( 'printable' === mt_get_ticket_method( $ticket_id ) ) {
			?>
			<a href="javascript:window.print()" class="print">Print</a>
			<?php
		}
	}
}
?>
</body>
</html>
