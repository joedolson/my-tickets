<?php
/**
 * Verification template.
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
	<title><?php bloginfo( 'blogname' ); ?> &bull; <?php _e( 'Verify Ticket', 'my-tickets' ); ?> &bull; <?php mt_ticket_id(); ?></title>
	<meta name='viewport' content='width=device-width, initial-scale=1.0'>
	<link type="text/css" rel="stylesheet" href="<?php echo plugins_url( 'css/generic.css', __FILE__ ); ?>" />
</head>
<body>
<div class='panel verify <?php mt_ticket_method(); ?>'>

	<h1 class='event-title'><?php mt_event_title(); ?></h1>

	<p><?php mt_event_date(); ?> @ <span class='time'><?php mt_event_time(); ?></span></p>

	<div class='mt-verification'><?php mt_verification(); ?></div>

</div>

</body>
</html>
