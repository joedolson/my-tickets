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
 * Abstract function for saving user data (cookie or meta). Saves as cookie if not logged in, as user meta if is.
 *
 * @param array  $passed Data passed to save.
 * @param string $type Type of data to save.
 * @param bool   $override Whether to override this.
 *
 * @return bool
 */
function mt_save_data( $passed, $type = 'cart', $override = false ) {
	$type = sanitize_title( $type );
	// The shape of the $passed data doesn't match the saved model when updating from the cart page.
	if ( true === $override ) {
		$save  = $passed;
		$saved = mt_get_cart();
	} else {
		switch ( $type ) {
			case 'cart':
				$save              = mt_get_cart();
				$saved             = $save;
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
	if ( 'cart' === $type ) {
		$inventory_change = mt_get_inventory_change( $save, $saved );
		foreach ( $inventory_change as $ticket => $change ) {
			mt_update_inventory( $change['event_id'], $ticket, $change['count'] );
		}
	}

	$current_user = wp_get_current_user();
	mt_refresh_cache();
	$expiration = mt_expiration_window();
	if ( is_user_logged_in() ) {
		update_user_meta( $current_user->ID, '_mt_user_init_expiration', time() + $expiration );
		update_user_meta( $current_user->ID, "_mt_user_$type", $save );

		return true;
	} else {
		$unique_id = mt_get_unique_id();
		if ( mt_get_transient( 'mt_' . $unique_id . '_' . $type ) ) {
			mt_delete_transient( 'mt_' . $unique_id . '_' . $type );
		}
		mt_set_transient( 'mt_' . $unique_id . '_' . $type, $save );
		mt_set_transient( 'mt_' . $unique_id . '_expiration', time() + $expiration );

		return true;
	}
}

/**
 * Extend cart expiration time.
 *
 * @param int $amount Time in seconds to extend cart validity.
 *
 * @return bool|int
 */
function mt_extend_expiration( $amount = 300 ) {
	$amount = absint( $amount );
	if ( is_user_logged_in() ) {
		$current = get_user_meta( wp_get_current_user()->ID, '_mt_user_init_expiration', true );
		$new     = (int) $current + $amount;
		update_user_meta( wp_get_current_user()->ID, '_mt_user_init_expiration', $new );

		return $new;
	} else {
		$unique_id = mt_get_unique_id();
		$current   = mt_get_transient( 'mt_' . $unique_id . '_expiration' );
		$new       = (int) $current + $amount;
		$cart      = mt_get_transient( 'mt_' . $unique_id . '_cart' );
		mt_set_transient( 'mt_' . $unique_id . '_cart', $cart );
		mt_set_transient( 'mt_' . $unique_id . '_expiration', $new );

		return $new;
	}

	return false;
}

/**
 * Abstract function to delete data. Defaults to delete user's shopping cart.
 *
 * @param string $data Type of data to delete.
 * @param string $unique_id Data key to delete.
 */
function mt_delete_data( $data = 'cart', $unique_id = false ) {
	if ( 'cart' === $data ) {
		// With no arguments, this removes the current cart from inventory.
		$inventory_change = mt_get_inventory_change();
		foreach ( $inventory_change as $ticket => $change ) {
			mt_update_inventory( $change['event_id'], $ticket, $change['count'] );
		}
		mt_delete_custom_field_data();
	}
	if ( is_user_logged_in() && ! $unique_id ) {
		$current_user = wp_get_current_user();
		delete_user_meta( $current_user->ID, "_mt_user_$data" );
	}
	$unique_id = ( $unique_id ) ? $unique_id : mt_get_unique_id();
	if ( $unique_id ) {
		mt_delete_transient( 'mt_' . $unique_id . '_' . $data );
	}
}

/**
 * Delete saved custom field data if necessary. Run whenever cart data is deleted.
 */
function mt_delete_custom_field_data() {
	$custom_fields = mt_get_custom_fields( 'delete' );
	if ( empty( $custom_fields ) ) {
		// If no custom fields registered, we're done.
		return;
	} else {
		$user      = false;
		$unique_id = false;
		if ( is_user_logged_in() ) {
			$id   = wp_get_current_user()->ID;
			$user = get_user_meta( $id );
		} else {
			$unique_id = mt_get_unique_id();
		}
		$user = false;
		foreach ( $custom_fields as $name => $field ) {
			if ( $user ) {
				foreach ( $user as $key => $meta ) {
					if ( false !== stripos( $key, '_mt_user_' . $name ) ) {
						delete_user_meta( $id, $key, $meta[0] );
					}
				}
			} elseif ( $unique_id ) {
				// This will delete all stored option data for the active user.
				global $wpdb;
				$like    = '%' . $wpdb->esc_like( $unique_id ) . '%';
				$results = $wpdb->get_results( $wpdb->prepare( 'SELECT * FROM ' . $wpdb->options . ' WHERE option_name LIKE %s', $like ) );
				foreach ( $results as $result ) {
					delete_option( $result->option_name, $result->option_value );
				}
			}
		}
	}
}

/**
 * Abstract function to retrieve data for current user/public user.
 *
 * @param string       $type Type of data.
 * @param bool|integer $user_ID User ID or false if not logged in.
 * @param bool|string  $unique_id Unique ID to fetch.
 *
 * @return array|mixed
 */
function mt_get_data( $type, $user_ID = false, $unique_id = false ) {
	// Get information about a specific user.
	if ( $user_ID && ! $unique_id ) {
		$data = get_user_meta( $user_ID, "_mt_user_$type", true );
	} else {
		$expired = false;
		if ( is_user_logged_in() && ! $unique_id ) {
			$current_user = wp_get_current_user();
			$data_age     = get_user_meta( $current_user->ID, '_mt_user_init_expiration', true );
			if ( $data_age && time() > $data_age ) {
				// Expire user's cart after the data ages out.
				if ( 'cart' === $type ) {
					mt_delete_data( 'cart' );
				} else {
					delete_user_meta( $current_user->ID, "_mt_user_$type" );
				}
				$expired = true;
			}
			if ( ! $data_age && ! $expired ) {
				$expiration = mt_expiration_window();
				update_user_meta( $current_user->ID, '_mt_user_init_expiration', time() + $expiration );
			}

			$data = get_user_meta( $current_user->ID, "_mt_user_$type", true );
		} else {
			$unique_id = ( ! $unique_id ) ? mt_get_unique_id() : $unique_id;
			if ( $unique_id ) {
				$data_age = mt_get_transient( 'mt_' . $unique_id . '_expiration' );
				if ( $data_age && time() > $data_age ) {
					// Expire user's cart after the data ages out.
					if ( 'cart' === $type ) {
						mt_delete_data( 'cart' );
					} else {
						mt_delete_transient( 'mt_' . $unique_id . '_' . $type );
					}
					$expired = true;
				}
				if ( ! $data_age && ! $expired ) {
					$expiration = mt_expiration_window();
					mt_set_transient( 'mt_' . $unique_id . '_expiration', time() + $expiration );
				}

				$data = mt_get_transient( 'mt_' . $unique_id . '_' . $type );
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


/**
 * Get saved cart data for user.
 *
 * @param bool|int    $user_ID User ID.
 * @param bool|string $cart_id Cart identifier.
 *
 * @return array|mixed
 */
function mt_get_cart( $user_ID = false, $cart_id = false ) {
	$cart      = array();
	$unique_id = mt_get_unique_id();
	if ( $user_ID ) {
		// Logged-in user data is saved in user meta.
		$cart = get_user_meta( $user_ID, '_mt_user_cart', true );
	} elseif ( ! $user_ID && $cart_id ) {
		// Public data is saved in transients.
		$cart = mt_get_transient( 'mt_' . $cart_id . '_cart' );
	} else {
		if ( is_user_logged_in() ) {
			$current_user = wp_get_current_user();
			$data_age     = get_user_meta( $current_user->ID, '_mt_user_init_expiration', true );
			if ( $data_age && time() > $data_age ) {
				mt_delete_data( 'cart' );
				delete_user_meta( $current_user->ID, '_mt_user_init_expiration' );
			} else {
				$cart = get_user_meta( $current_user->ID, '_mt_user_cart', true );
			}
		} else {
			if ( $unique_id ) {
				$data_age = mt_get_transient( 'mt_' . $unique_id . '_expiration' );
				if ( $data_age && time() > $data_age ) {
					mt_delete_data( 'cart' );
					mt_delete_transient( 'mt_' . $unique_id . '_expiration' );
				} else {
					$cart = mt_get_transient( 'mt_' . $unique_id . '_cart' );
				}
			}
		}
	}
	if ( is_user_logged_in() && ! $cart ) {
		if ( $unique_id ) {
			$cart = mt_get_transient( 'mt_' . $unique_id . '_cart' );
		}
	}

	return ( $cart ) ? $cart : array();
}

add_action( 'init', 'mt_set_user_unique_id' );
/**
 * Set a cookie with a random ID for the current user.
 *
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
	$characters = '0123456789AaBbCcDdEeFfGgHhIiJjKkLlMmNnOoPpQqRrSsTtUuVvWwXxYyZz-';
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
	$options    = mt_get_settings();
	$expiration = $options['mt_expiration'];
	// Doesn't support less than 10 minutes.
	if ( ! $expiration || (int) $expiration < 600 ) {
		$expiration = WEEK_IN_SECONDS;
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

/**
 * Get cart expiration timestamp.
 */
function mt_get_expiration() {
	$expires_at = 0;
	if ( is_user_logged_in() ) {
		$current_user = wp_get_current_user();
		$expires_at   = get_user_meta( $current_user->ID, '_mt_user_init_expiration', true );
	} else {
		$unique_id = mt_get_unique_id();
		if ( $unique_id ) {
			$expires_at = mt_get_transient( 'mt_' . $unique_id . '_expiration' );
		}
	}

	return absint( $expires_at );
}

/**
 * Check whether a user's cart or payment info is expired at init.
 */
function mt_is_cart_expired() {
	$types = mt_get_data_types();
	if ( is_user_logged_in() ) {
		$current_user = wp_get_current_user();
		$data_age     = get_user_meta( $current_user->ID, '_mt_user_init_expiration', true );
		foreach ( $types as $type ) {
			if ( time() > $data_age ) {
				// Expire user's cart after the data ages out.
				if ( 'cart' === $type ) {
					mt_delete_data( 'cart' );
				} else {
					delete_user_meta( $current_user->ID, "_mt_user_$type" );
				}
				// Since this user's data is expired, also remove their expiration window.
				delete_user_meta( $current_user->ID, '_mt_user_init_expiration' );
			}
		}
	} else {
		$unique_id = mt_get_unique_id();
		if ( $unique_id ) {
			$expiration = mt_get_transient( 'mt_' . $unique_id . '_expiration' );
			if ( $expiration && time() > $expiration ) {
				mt_delete_transient( 'mt_' . $unique_id . '_expiration' );
				foreach ( $types as $type ) {
					mt_delete_transient( 'mt_' . $unique_id . '_' . $type );
				}
			}
		}
	}
}
add_action( 'init', 'mt_is_cart_expired' );

/**
 * Set or update a transient value for data storage.
 *
 * @param string $transient_id Option name.
 * @param mixed  $value Value to save.
 */
function mt_set_transient( $transient_id, $value ) {
	update_option( $transient_id, $value );
}

/**
 * Get a transient value for data storage.
 *
 * @param string $transient_id Option name.
 *
 * @return mixed Option value.
 */
function mt_get_transient( $transient_id ) {
	$value = get_option( $transient_id );

	return $value;
}

/**
 * Delete a transient value for data storage.
 *
 * @param string $transient_id Option name.
 */
function mt_delete_transient( $transient_id ) {
	if ( strpos( $transient_id, '_cart' ) ) {
		// If this is a cart transient, parse out unique ID and update inventory.
		$inventory_change = mt_get_inventory_change();
		foreach ( $inventory_change as $ticket => $change ) {
			mt_update_inventory( $change['event_id'], $ticket, $change['count'] );
		}
	}
	delete_option( $transient_id );
}

/**
 * Poll transient keys. Remove any expired keys.
 */
function mt_check_transients() {
	$transients = get_option( 'mt_transient_keys', array() );
	foreach ( $transients as $key => $unique_id ) {
		$expire = mt_get_transient( 'mt_' . $unique_id . '_expiration' );
		if ( time() > $expire ) {
			// delete all transients for this unique ID.
			$types = mt_get_data_types();
			foreach ( $types as $type ) {
				mt_delete_transient( 'mt_' . $unique_id . '_' . $type );
			}
			unset( $transients[ $key ] );
		}
	}
}

/**
 * Get all standard data types. Does not fetch stored custom fields.
 *
 * @return array Types of data stored.
 */
function mt_get_data_types() {
	$types = array( 'cart', 'payment', 'offline-payment' );

	return $types;
}
