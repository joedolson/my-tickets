<?php
/**
 * Utilities for saving, deleting, and managing cart data stores.
 *
 * @category Cart
 * @package  My Tickets
 * @author   Joe Dolson
 * @license  GPLv2 or later
 * @link     https://www.joedolson.com/my-tickets/
 */

/**
 * Abstract function for saving user data (cookie or meta). Saves as cookie is not logged in, as user meta if is.
 *
 * @param array  $passed Data passed to save.
 * @param string $type Type of data to save.
 * @param bool   $override Whether to override this.
 *
 * @return bool
 */
function mt_save_data( $passed, $type = 'cart', $override = false ) {
	$type = sanitize_title( $type );
	if ( true === $override ) {
		$save = $passed;
	} else {
		switch ( $type ) {
			case 'cart':
				$save              = mt_get_cart();
				$options           = $passed['options'];
				$event_id          = $passed['event_id'];
				$save[ $event_id ] = $options;
				break;
			case 'payment':
				$save = $passed;
				break;
			default:
				$save = $passed;
		}
	}
	$current_user = wp_get_current_user();
	mt_refresh_cache();
	$expiration = mt_expiration_window();
	if ( is_user_logged_in() ) {
		$data_age = get_user_meta( $current_user->ID, "_mt_user_init_$type", true );
		if ( ! $data_age ) {
			update_user_meta( $current_user->ID, "_mt_user_init_$type", time() + $expiration );
		}
		update_user_meta( $current_user->ID, "_mt_user_$type", $save );

		return true;
	} else {
		$unique_id = mt_get_unique_id();
		if ( get_transient( 'mt_' . $unique_id . '_' . $type ) ) {
			delete_transient( 'mt_' . $unique_id . '_' . $type );
		}
		set_transient( 'mt_' . $unique_id . '_' . $type, $save, time() + $expiration );

		return true;
	}
}


/**
 * Abstract function to delete data. Defaults to delete user's shopping cart.
 *
 * @param string $data Type of data to delete.
 */
function mt_delete_data( $data = 'cart' ) {
	$unique_id = mt_get_unique_id();
	if ( is_user_logged_in() ) {
		$current_user = wp_get_current_user();
		delete_user_meta( $current_user->ID, "_mt_user_$data" );
	}
	if ( $unique_id ) {
		delete_transient( 'mt_' . $unique_id . '_' . $data );
	}
}

/**
 * Abstract function to retrieve data for current user/public user.
 *
 * @param string       $type Type of data.
 * @param bool|integer $user_ID User ID or false if not logged in.
 *
 * @return array|mixed
 */
function mt_get_data( $type, $user_ID = false ) {
	if ( $user_ID ) {
		$data = get_user_meta( $user_ID, "_mt_user_$type", true );
	} else {
		if ( is_user_logged_in() ) {
			$current_user = wp_get_current_user();
			$data_age     = get_user_meta( $current_user->ID, "_mt_user_init_$type", true );
			if ( ! $data_age ) {
				$expiration = mt_expiration_window();
				update_user_meta( $current_user->ID, "_mt_user_init_$type", time() + $expiration );
			}
			if ( time() > $data_age ) {
				// Expire user's cart after the data ages out.
				delete_user_meta( $current_user->ID, "_mt_user_$type" );
			}
			$data = get_user_meta( $current_user->ID, "_mt_user_$type", true );
		} else {
			$unique_id = mt_get_unique_id();
			if ( $unique_id ) {
				$data = get_transient( 'mt_' . $unique_id . '_' . $type );
			} else {
				$data = '[]';
			}
			if ( $data ) {
				if ( '' !== $data && ! is_numeric( $data ) && ! is_array( $data ) ) {
					// Data could be JSON and needs to be decoded.
					$decoded = json_decode( $data );
					// If it was valid JSON, use the decoded value. Otherwise, use the original.
					if ( JSON_ERROR_NONE === json_last_error() ) {
						$data = $decoded;
					}
				}
			} else {
				$data = false;
			}
		}
	}

	return $data;
}

add_action( 'init', 'mt_set_user_unique_id' );
/**
 * Note: if sitecookiepath doesn't match the site's render location, this won't work.
 * It'll also create a secondary issue where AJAX actions read the sitecookiepath cookie.
 */
function mt_set_user_unique_id() {
	if ( ! defined( 'DOING_CRON' ) ) {
		$unique_id  = mt_get_unique_id();
		$expiration = mt_expiration_window();
		if ( ! $unique_id ) {
			$unique_id = mt_generate_unique_id();
			if ( version_compare( PHP_VERSION, '7.3.0', '>' ) ) {
				// Fix syntax.
				$options = array(
					'expires'  => time() + $expiration,
					'path'     => COOKIEPATH,
					'domain'   => COOKIE_DOMAIN,
					'secure'   => false,
					'httponly' => true,
					'samesite' => 'Lax',
				);
				setcookie( 'mt_unique_id', $unique_id, $options );
			} else {
				setcookie( 'mt_unique_id', $unique_id, time() + $expiration, COOKIEPATH, COOKIE_DOMAIN, false, true );
			}
		}
	}
}

/**
 * Generate a unique ID to track the current cart process.
 *
 * @return string
 */
function mt_generate_unique_id() {
	$length     = 32;
	$characters = '0123456789AaBbCcDdEeFfGgHhIiJjKkLlMmNnOoPpQqRrSsTtUuVvWwXxYyZz-_';
	$string     = '';
	for ( $p = 0; $p < $length; $p++ ) {
		$string .= $characters[ wp_rand( 0, strlen( $characters ) - 1 ) ];
	}

	return $string;
}

/**
 * Fetch a unique ID if it exists.
 *
 * @return bool|string
 */
function mt_get_unique_id() {
	$unique_id = ( isset( $_COOKIE['mt_unique_id'] ) ) ? sanitize_text_field( $_COOKIE['mt_unique_id'] ) : false;

	return $unique_id;
}

/**
 * Get cart expiration time.
 *
 * @return int Number of seconds cart will last.
 */
function mt_expiration_window() {
	$options    = array_merge( mt_default_settings(), get_option( 'mt_settings', array() ) );
	$expiration = $options['mt_expiration'];
	// Doesn't support less than 10 minutes.
	if ( ! $expiration || (int) $expiration < 600 ) {
		$return = WEEK_IN_SECONDS;
	}
	$return = absint( $expiration );
	/**
	 * Filter the length of time data is stored. (Shopping carts, unique IDs).
	 *
	 * @hook mt_expiration_window
	 *
	 * @param {int}    $time Number of seconds before data will expire. Default WEEK_IN_SECONDS.
	 *
	 * @return {int}
	 */
	$expiration = apply_filters( 'mt_expiration_window', $return );

	return $expiration;
}
