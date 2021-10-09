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
<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
	<title><?php bloginfo( 'blogname' ); ?> &bull; <?php _e( 'Receipts', 'my-tickets' ); ?> &bull; <?php mt_receipt_id(); ?></title>
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
		.receipt {
			width: 100%;
			max-width: 400px;
		}

		.receipt .post-thumbnail .default {
			width: 120px;
			height: auto;
			margin-bottom: 1em;
		}

		.receipt .post-content {
			background: #eee;
			padding: 1em;
			margin: 0 -1em;
		}

		code {
			font-size: 1.2em;
		}
	</style>
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
