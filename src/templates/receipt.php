<?php
/**
 * Receipt template.
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
	<title><?php bloginfo( 'blogname' ); ?> &bull; <?php _e( 'Receipts', 'my-tickets' ); ?> &bull; <?php mt_receipt_id(); ?></title>
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<link type="text/css" rel="stylesheet" href="<?php echo plugins_url( 'css/generic.css', __FILE__ ); ?>"/>
	<link type="text/css" rel="stylesheet" href="<?php echo plugins_url( 'css/receipt.css', __FILE__ ); ?>"/>
</head>
<body>
<div class='panel receipt'>
	<?php
	// load data from the Receipts Page.
	if ( have_posts() ) {
		while ( have_posts() ) {
			the_post();
			?>
			<div class='post-thumbnail'>
				<?php if ( has_post_thumbnail() ) { ?>
					<?php the_post_thumbnail(); ?>
				<?php } else { ?>
					<?php mt_logo(); ?>
				<?php } ?>
			</div>
			<div class='post-content'>
				<?php
				$content = get_the_content();
				if ( trim( strip_tags( $content ) ) === '' ) {
					$content = ( current_user_can( 'edit_pages' ) ) ? wpautop( __( 'Add your business name and address to the post content.', 'my-tickets' ) ) : '';
				}
				echo wpautop( $content );
				?>
				<?php edit_post_link(); ?>
			</div>
			<?php
		}
	}

	// Receipt template.
	?>
	<h1><?php _e( 'Receipt', 'my-tickets' ); ?></h1>

	<div class='receipt'>
		<p>
			<code>#<?php mt_receipt_id(); ?></code>
		</p>
	</div>
	<div class='purchaser'>
		<strong><?php _e( 'Purchaser:', 'my-tickets' ); ?></strong> <?php mt_cart_purchaser(); ?>
	</div>
	<div class='purchase-date'>
		<strong><?php _e( 'Purchased on:', 'my-tickets' ); ?></strong> <?php mt_cart_purchase_date(); ?>
	</div>
	<div class='cart-order'>
		<h2><?php _e( 'Tickets Purchased:', 'my-tickets' ); ?></h2> <?php mt_cart_order(); ?>
	</div>
	<div class='payment-details'>
		<h2><?php _e( 'Payment Details', 'my-tickets' ); ?></h2> <?php mt_payment_details(); ?>
	</div>
	<?php echo apply_filters( 'mt_custom_receipt', '' ); ?>
</div>
<a href="javascript:window.print()" class="print">Print</a>
</body>
</html>
