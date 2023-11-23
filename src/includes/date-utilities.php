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
 * Wrapper for date()
 *
 * @param string $format Format to use.
 * @param int    $timestamp Timestamp.
 * @param bool   $offset False to not add offset; if already provided with offset.
 *
 * @return string Formatted date.
 */
function mt_date( $format, $timestamp = false, $offset = true ) {
	if ( ! $timestamp ) {
		$timestamp = time();
	}
	if ( $offset ) {
		$offset = intval( get_option( 'gmt_offset', 0 ) ) * 60 * 60;
	} else {
		$offset = 0;
	}
	$timestamp = $timestamp + $offset;

	return gmdate( $format, $timestamp );
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
