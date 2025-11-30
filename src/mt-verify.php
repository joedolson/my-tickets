<?php
/**
 * Verification template.
 *
 * @category Core
 * @package  My Tickets
 * @author   Joe Dolson
 * @license  GPLv3
 * @link     https://www.joedolson.com/my-tickets/
 */
$options  = mt_get_settings();
$post_url = add_query_arg( 'receipt_id', mt_get_receipt_id(), get_permalink( $options['mt_receipt_page'] ) );
get_header()
	?>
		<style>
			.mt-entry { width: 100%; max-width: 1080px; padding: 1rem; margin: 0 auto; }
			.verify-data form { display: flex; align-items: end; }
			.mt-error { padding: .5rem; outline: 2px solid currentColor; margin-bottom: 1rem; }
		</style>
		<article class="mt-entry entry-content">
			<h1 class='entry-title'><?php esc_html_e( 'Verify your purchase email', 'my-tickets' ); ?></h1>
			<div class="entry-content">
				<div class="verify-data">
					<?php
					if ( isset( $_POST['mt-verify-email'] ) ) {
						?>
						<div class="mt-error">
							<p><?php esc_html_e( 'Sorry! That email address does not match our records for this purchase.', 'my-tickets' ); ?></p>
						</div>
						<?php
					}
					?>
					<form action="<?php echo esc_url( $post_url ); ?>" method="post">
						<?php wp_nonce_field( 'mt-verify-email' ); ?>
						<p>
							<label for="mt-verify-email"><?php esc_html_e( 'Your Email', 'my-tickets' ); ?></label><br />
							<input type="email" id="mt-verify-email" name="mt-verify-email" autocomplete="email" required />
						</p>
						<p>
							<button type="submit"><?php esc_html_e( 'Submit', 'my-tickets' ); ?></button>
						</p>
					</form>
				</div>
			</div>
		</article>
	<?php
get_footer();
