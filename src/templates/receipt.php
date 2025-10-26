<?php
/**
 * Receipt template.
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
	<title><?php bloginfo( 'blogname' ); ?> &bull; <?php _e( 'Receipts', 'my-tickets' ); ?> &bull; <?php mt_receipt_id(); ?></title>
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<style>
		<?php echo mt_generate_css(); ?>
		body {
			font-family: -apple-system,BlinkMacSystemFont,"Segoe UI",Roboto,Oxygen-Sans,Ubuntu,Cantarell,"Helvetica Neue",sans-serif;
			background: var(--mt-receipt-secondary-background);
		}

		body > * {
			box-sizing: border-box;
		}

		.panel {
			margin: 0 auto;
			border: var(--mt-receipt-border);
			background: var(--mt-receipt-background);
			color: var(--mt-receipt-color);
			font-size: 1rem;
			display: grid;
			gap: 20px;
		}

		.panel > * {
			padding: 0 1rem;
		}

		.cart-order {
			padding: .5rem;
			background: var(--mt-receipt-secondary-background);
			color: var(--mt-receipt-secondary-color);
		}

		h1 {
			font-size: 1.5rem;
			margin: 0;
		}

		h2 {
			font-size: 1.3rem;
		}

		.panel a {
			color: var(--mt-receipt-link-color);
		}

		.panel img {
			display: block;
			margin: 0 auto;
			max-width: 100%;
			height: auto;
		}

		.panel * {
			word-wrap: breakword;
		}

		a.print {
			display: block;
			width: 100%;
			text-align: center;
		}

		@media print {
			a.print {
				display: none;
			}
		}
		.receipt {
			width: 100%;
			max-width: 480px;
		}

		.receipt .post-thumbnail .default {
			width: 160px;
			height: auto;
			margin-bottom: 1em;
		}

		.receipt .post-content {
			background: var(--mt-receipt-secondary-background);
			color: var(--mt-receipt-secondary-color);
			padding: 1em;
		}

		code {
			font-size: 1.2em;
		}
	</style>
</head>
<body class="my-tickets">
<div class='panel receipt'>
	<div class="organization-info">
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
				<h1><?php the_title(); ?></h1>
				<?php
				$content = get_the_content();
				if ( trim( strip_tags( $content ) ) === '' ) {
					$content  = ( current_user_can( 'edit_pages' ) ) ? wpautop( __( 'Add your business name and address to the post content.', 'my-tickets' ) ) : '';
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
	</div>
	<div class='receipt-info'>
		<code>#<?php mt_receipt_id(); ?></code>
	</div>
	<div class="purchase-info">
		<div class='purchaser'>
			<strong><?php _e( 'Purchaser:', 'my-tickets' ); ?></strong> <?php mt_cart_purchaser(); ?>
		</div>
		<div class='purchase-date'>
			<strong><?php _e( 'Purchase date:', 'my-tickets' ); ?></strong> <?php mt_cart_purchase_date(); ?>
		</div>
	</div>
	<div class="order-info">
		<div class='cart-order'>
			<h2><?php _e( 'Tickets Purchased', 'my-tickets' ); ?></h2> <?php mt_cart_order(); ?>
		</div>
		<div class='payment-details'>
			<h2><?php _e( 'Payment Details', 'my-tickets' ); ?></h2> <?php mt_payment_details(); ?>
		</div>
	</div>
	<?php echo apply_filters( 'mt_custom_receipt', '' ); ?>
</div>
<a href="javascript:window.print()" class="print">Print</a>
</body>
</html>
