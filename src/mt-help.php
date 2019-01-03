<?php
/**
 * Plugin Help and support info.
 *
 * @category Support
 * @package  My Tickets
 * @author   Joe Dolson
 * @license  GPLv2 or later
 * @link     https://www.joedolson.com/my-tickets/
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
} // Exit if accessed directly.

/**
 * Display My Tickets internal help page.
 */
function mt_help() {
	?>
	<div class='wrap my-tickets'>
		<h2><?php _e( 'My Tickets Help', 'my-tickets' ); ?></h2>

		<div class="postbox-container jcd-wide">
			<div class="metabox-holder">

				<div class="ui-sortable meta-box-sortables">
					<div class="postbox">
						<h3 id="get-started"><?php _e( 'Getting Started', 'my-tickets' ); ?></h3>

						<div class="inside mt-help">
							<p>
								<?php _e( 'There are a few minimum settings to configure before you get started with My Tickets.', 'my-tickets' ); ?>
							</p>
							<h4><?php _e( 'Basic Settings', 'my-tickets' ); ?></h4>
							<ul>
								<li><?php _e( 'Define what post types My Tickets should be activated for', 'my-tickets' ); ?>. (<?php _e( 'If My Calendar is installed, activate the "Registration" panel in the My Calendar input settings', 'my-tickets' ); ?>)</li>
								<li><?php _e( 'Set up a new post and click "Sell Tickets on this post"', 'my-tickets' ); ?></li>
							</ul>
							<h4><?php _e( 'Payment Settings', 'my-tickets' ); ?></h4>
							<ul>
								<li><?php _e( 'Set your accepted payment currency', 'my-tickets' ); ?></li>
								<li><?php _e( 'Enable your preferred payment gateways', 'my-tickets' ); ?></li>
								<li><?php _e( 'Add merchant data for your enabled gateways and set the default gateway.', 'my-tickets' ); ?>
									<ul>
										<li><a href="https://www.authorize.net/support/CP/helpfiles/Account/Settings/Security_Settings/General_Settings/API_Login_ID_and_Transaction_Key.htm"><?php _e( 'How to get your Authorize.net API Login ID and Transaction Key', 'my-tickets' ); ?></a></li>
										<li><a href="https://www.paypal.com/businessprofile/settings/"><?php _e( 'Find your PayPal primary email and merchant ID', 'my-tickets' ); ?></a></li>
									</ul>
								</li>
								<li><?php _e( 'Turn on/off testing mode.', 'my-tickets' ); ?></li>
							</ul>
							<h4><?php _e( 'Ticket Settings', 'my-tickets' ); ?></h4>
							<ul>
								<li><?php _e( 'Choose what types of tickets you provide.', 'my-tickets' ); ?></li>
								<li><?php _e( 'Define shipping costs, if you will ship tickets.', 'my-tickets' ); ?></li>
								<li><?php _e( 'Set defaults to be pre-filled when you add new events for ticketing.', 'my-tickets' ); ?></li>
							</ul>
						</div>
					</div>

					<div class="postbox">
						<h3 id="faq" tabindex="-1"><?php _e( 'Shortcodes', 'my-tickets' ); ?></h3>

						<div class="inside">
							<p>
								<?php _e( 'Display the Add to Cart form for a single event.', 'my-tickets' ); ?> <?php _e( '"Event" attribute is the post ID for that event.', 'my-tickets' ); ?>
							</p>

							<textarea readonly='readonly'>[ticket event="1"]</textarea>

							<p>
								<?php _e( 'Shows a list of events. Provide a template with HTML and tags for what information to display. Add to cart form is added to the end of the template automatically.', 'my-tickets' ); ?>
							</p>

							<textarea readonly='readonly'>[tickets events="1,2" template="&lt;h3&gt;{post_title}&lt;/h3&gt;"]</textarea>

							<p>
								<?php _e( 'Shows a reduced version of the cart with number of tickets, total value of cart, and a link to the shopping cart.', 'my-tickets' ); ?>
							</p>

							<textarea readonly='readonly'>[quick-cart]</textarea>

						</div>
					</div>

					<div class="postbox">
						<h3 id="faq" tabindex="-1"><?php _e( 'Frequently Asked Questions', 'my-tickets' ); ?></h3>

						<div class="inside">
							<dl>
								<dt><?php _e( 'What is the difference between "discrete" and "continuous" ticket counting?', 'my-tickets' ); ?></dt>
								<dd><?php _e( 'Whether it matters which group a ticket is purchased in. If you\'re selling tickets based on the purchaser (Adult, Child, etc.), it doesn\'t matter which group it\'s in. You have 250 tickets, and it\'s perfectly plausible for all 250 to be in one category. This is "continuous" counting. You have a specific total number of tickets, and whichever type of ticket is sold, the number is subtracted from the total number of tickets. With "discrete" sections (Gallery, Balcony, etc.), it <strong>does</strong> matter which group a ticket is sold from. You have only 50 tickets in Section A, but 100 in Section B. If you were to sell 100 tickets for Section A, you\'d have a serious problems!', 'my-tickets' ); ?></dd>
								<dt><?php _e( 'What\'s the difference between Types of Sales?', 'my-tickets' ); ?></dt>
								<dd><?php _e( 'When you choose "Ticket Sales", the language on buttons and in your cart will reflect that this is a ticket sale. It will also add the ability to change the number of tickets you purchase in any given category.', 'my-tickets' ); ?></dd>
								<dt><?php _e( 'What is an "event" in My Tickets?', 'my-tickets' ); ?></dt>
								<dd><?php _e( 'Any post, Page, or custom post type can be an "Event" for My Tickets. When you create a post or Page, you can decide to sell tickets on it; at which point it becomes an "event". If you\'re using My Calendar, creating an event also creates a post, in a hidden custom post type. Your tickets are associated with that post.', 'my-tickets' ); ?></dd>

								<dt><?php _e( 'How are e-tickets and printable tickets different?', 'my-tickets' ); ?></dt>
								<dd><?php _e( 'The main difference between e-tickets and printable tickets is in format. E-tickets are formatted for optimum fit on a mobile device, but printable tickets are shaped more like traditional tickets. These are defaults, however, since both types of tickets are fully templatable.', 'my-tickets' ); ?></dd>
								<dt><?php _e( 'How do e-tickets and printable tickets work?', 'my-tickets' ); ?></dt>
								<dd><?php _e( 'Both types of tickets include a QR Code. If you\'re logged in to your web site, you can scan that QR code to verify that the ticket is valid, and it will be registered as having been used. If you\'re not logged in, you can still verify that the ticket is valid, but nothing will be saved to your site.', 'my-tickets' ); ?></dd>
							</dl>
						</div>
					</div>
					<div class="postbox">
						<h3 id="get-support"><?php _e( 'Get Support', 'my-tickets' ); ?></h3>

						<div class="inside">
							<div class="mt-support-me">
								<p>
									<?php
									// Translators: URL to donate, URL to purchase.
									printf( __( 'Please, consider a <a href="%1$s">donation</a> or a <a href="%2$s">purchase</a> to support My Tickets!', 'my-tickets' ), 'https://www.joedolson.com/donate/', 'https://www.joedolson.com/my-tickets/add-ons/' );
									?>
								</p>
							</div>
							<?php
							mt_get_support_form();
							?>
						</div>
					</div>
				</div>
			</div>
		</div>
		<?php mt_show_support_box(); ?>
	</div>
	<?php
}

/**
 * Display support form
 */
function mt_get_support_form() {
	$current_user = wp_get_current_user();
	$request      = '';
	$version = mt_get_current_version();
	// send fields for all plugins.
	$wp_version = get_bloginfo( 'version' );
	$home_url   = home_url();
	$wp_url     = site_url();
	$language   = get_bloginfo( 'language' );
	$charset    = get_bloginfo( 'charset' );
	// server.
	$php_version = phpversion();
	$db_version  = $wpdb->db_version();

	// theme data.
	$theme         = wp_get_theme();
	$theme_name    = $theme->get( 'Name' );
	$theme_uri     = $theme->get( 'ThemeURI' );
	$theme_parent  = $theme->get( 'Template' );
	$theme_version = $theme->get( 'Version' );

	// plugin data.
	$plugins        = get_plugins();
	$plugins_string = '';
	foreach ( array_keys( $plugins ) as $key ) {
		if ( is_plugin_active( $key ) ) {
			$plugin          =& $plugins[ $key ];
			$plugin_name     = $plugin['Name'];
			$plugin_uri      = $plugin['PluginURI'];
			$plugin_version  = $plugin['Version'];
			$plugins_string .= "$plugin_name: $plugin_version; $plugin_uri\n";
		}
	}
	$data = "
================ Installation Data ====================
==My Tickets==
Version: $version

==WordPress:==
Version: $wp_version
URL: $home_url
Install: $wp_url
Language: $language
Charset: $charset
User Email: $current_user->user_email

==Extra info:==
PHP Version: $php_version
Server Software: $_SERVER[SERVER_SOFTWARE]
User Agent: $_SERVER[HTTP_USER_AGENT]
Database: $db_version

==Theme:==
Name: $theme_name
URI: $theme_uri
Parent: $theme_parent
Version: $theme_version

==Active Plugins:==
$plugins_string
";
	if ( isset( $_POST['mt_support'] ) ) {
		$nonce = $_REQUEST['_wpnonce'];
		if ( ! wp_verify_nonce( $nonce, 'my-tickets-nonce' ) ) {
			die( 'Security check failed' );
		}
		$request      = ( ! empty( $_POST['support_request'] ) ) ? stripslashes( $_POST['support_request'] ) : false;
		$has_read_faq = ( 'on' == $_POST['has_read_faq'] ) ? 'Read FAQ' : false;
		$subject      = 'My Tickets support request.';
		$message      = $request . "\n\n" . $data;
		// Get the site domain and get rid of www. from pluggable.php.
		$sitename = strtolower( $_SERVER['SERVER_NAME'] );
		if ( substr( $sitename, 0, 4 ) == 'www.' ) {
			$sitename = substr( $sitename, 4 );
		}
		$from_email = 'wordpress@' . $sitename;
		$from       = "From: \"$current_user->display_name\" <$from_email>\r\nReply-to: \"$current_user->display_name\" <$current_user->user_email>\r\n";

		if ( ! $has_read_faq ) {
			echo "<div class='message error'><p>" . __( 'Please read the FAQ and other Help documents before making a support request.', 'my-tickets' ) . '</p></div>';
		} elseif ( ! $request ) {
			echo "<div class='message error'><p>" . __( 'Please describe your problem. I\'m not psychic.', 'my-tickets' ) . '</p></div>';
		} else {
			$sent = wp_mail( 'plugins@joedolson.com', $subject, $message, $from );
			if ( ! $sent ) {
				// If mail sends, try without custom headers.
				$sent = wp_mail( 'plugins@joedolson.com', $subject, $message );
			}
			if ( $sent ) {
				// Translators: email address.
				echo "<div class='message updated'><p>" . sprintf( __( 'Thank you for supporting the continuing development of this plug-in! I\'ll get back to you as soon as I can. Please ensure that you can receive email at <code>%s</code>.', 'my-tickets' ), $current_user->user_email ) . '</p></div>';
			} else {
				// Translators: Contact URL.
				echo "<div class='message error'><p>" . __( "Sorry! I couldn't send that message. Here's the text of your request:", 'my-calendar' ) . '</p><p>' . sprintf( __( '<a href="%s">Contact me here</a>, instead</p>', 'my-tickets' ), 'https://www.joedolson.com/contact/' ) . "<pre>$request</pre></div>";
			}
		}
	}
	$admin_url = admin_url( 'admin.php?page=mt-help' );
	echo "
	<form method='post' action='$admin_url'>
		<div><input type='hidden' name='_wpnonce' value='" . wp_create_nonce( 'my-tickets-nonce' ) . "' /></div>
		<div>";
	echo '
		<p>
		<code>' . __( 'Reply to:', 'my-tickets' ) . " \"$current_user->display_name\" &lt;$current_user->user_email&gt;</code>
		</p>
		<p>
		<input type='checkbox' name='has_read_faq' id='has_read_faq' value='on' required='required' aria-required='true' /> <label for='has_read_faq'>";
	// Translators: FAQ URL.
	printf( __( 'I have read <a href="%s">the FAQ for this plug-in</a> <span>(required)</span>', 'my-tickets' ), '#faq' );
	echo "</p>
		<p>
		<label for='support_request'>" . __( 'Support Request:', 'my-tickets' ) . "</label><br /><textarea class='support-request' name='support_request' id='support_request' cols='80' rows='10'>" . stripslashes( $request ) . "</textarea>
		</p>
		<p>
		<input type='submit' value='" . __( 'Send Support Request', 'my-tickets' ) . "' name='mt_support' class='button-primary' />
		</p>
		<p>" . __( 'The following additional information will be sent with your support request:', 'my-tickets' ) . "</p>
		<div class='mt_support'>" . wpautop( $data ) . '
		</div>
		</div>
	</form>';
}
