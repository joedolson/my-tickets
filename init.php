<?php
/**
 * My Tickets, Accessible ticket sales for WordPress
 *
 * @package     My Tickets
 * @author      Joe Dolson
 * @copyright   2014-2025 Joe Dolson
 * @license     GPL-2.0+
 *
 * @wordpress-plugin
 * Plugin Name: My Tickets
 * Plugin URI:  https://www.joedolson.com/my-tickets/
 * Description: Sell Tickets and take registrations for your events. Integrates with My Calendar.
 * Author:      Joe Dolson
 * Author URI:  https://www.joedolson.com
 * Text Domain: my-tickets
 * License:     GPL-2.0+
 * License URI: http://www.gnu.org/license/gpl-2.0.txt
 * Domain Path: lang
 * Version:     2.0.15
 */

/*
	Copyright 2014-2025  Joe Dolson (email : joe@joedolson.com)

	This program is free software; you can redistribute it and/or modify
	it under the terms of the GNU General Public License as published by
	the Free Software Foundation; either version 2 of the License, or
	(at your option) any later version.

	This program is distributed in the hope that it will be useful,
	but WITHOUT ANY WARRANTY; without even the implied warranty of
	MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
	GNU General Public License for more details.

	You should have received a copy of the GNU General Public License
	along with this program; if not, write to the Free Software
	Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

require __DIR__ . '/src/my-tickets.php';

register_activation_hook( __FILE__, 'mt_activation' );
register_deactivation_hook( __FILE__, 'mt_plugin_deactivated' );
