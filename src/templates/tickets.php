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
<html>
<head>
	<title><?php bloginfo( 'blogname' ); ?> &bull; <?php _e( 'Tickets', 'my-tickets' ); ?> &bull; <?php mt_ticket_id(); ?></title>
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<link type="text/css" rel="stylesheet" href="<?php echo plugins_url( 'css/generic.css', __FILE__ ); ?>"/>
	<link type="text/css" rel="stylesheet" href="<?php echo plugins_url( 'css/ticket.css', __FILE__ ); ?>"/>
</head>
<body>
<div class='panel ticket <?php mt_ticket_method(); ?>'>
	<div class='inside'>
		<?php
		// load data from the Tickets Page.
		if ( have_posts() ) {
			while ( have_posts() ) {
				the_post();
				if ( 'eticket' != mt_get_ticket_method() ) {
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
						<?php mt_event_date(); ?> @ <span class='time'><?php mt_event_time(); ?></span>
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
					if ( mt_get_ticket_method() != 'eticket' ) {
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
					if ( '' == trim( strip_tags( $content ) ) ) {
						$content = ( current_user_can( 'edit_pages' ) ) ? __( 'Add your custom text into the post content.', 'my-tickets' ) : '';
					}
					echo $content;
					?>
					<?php edit_post_link(); ?>
					</div>
					<?php
					if ( 'eticket' == mt_get_ticket_method() ) {
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
if ( 'printable' == mt_get_ticket_method() ) {
	?>
	<a href="javascript:window.print()" class="print">Print</a>
	<?php
}
?>
</body>
</html>
