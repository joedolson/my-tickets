<?php
/**
 * Utilities for calculating dates and times.
 *
 * @category Cart
 * @package  My Tickets
 * @author   Joe Dolson
 * @license  GPLv2 or later
 * @link     https://www.joedolson.com/my-tickets/
 */

/**
 * Wrapper for date(). Used for date comparisons and non-translatable dates.
 *
 * @param string    $format Default ''. Format to use. Empty string for timestamp.
 * @param int|false $timestamp Default false. Timestamp or false if current time..
 * @param bool      $offset Default true. False to not add offset; if already a timestamp.
 *
 * @return string|int Formatted date or timestamp if no format provided.
 */
function mt_date( $format = '', $timestamp = false, $offset = true ) {
	if ( ! $timestamp ) {
		// Timestamp is UTC.
		$timestamp = time();
	}
	if ( $offset ) {
		$offset = intval( get_option( 'gmt_offset', 0 ) ) * 60 * 60;
	} else {
		$offset = 0;
	}
	$timestamp = $timestamp + $offset;

	return ( '' === $format ) ? $timestamp : gmdate( $format, $timestamp );
}

/**
 *  Get current time in the format of timestamp.
 *
 * @return int timestamp-like data.
 */
function mt_current_time() {
	$timestamp = time();
	$offset    = 60 * 60 * intval( get_option( 'gmt_offset', 0 ) );
	$timestamp = $timestamp + $offset;

	return $timestamp;
}
