<?php
/**
 * Nelio A/B Testing array helpers.
 *
 * @package    Nelio_AB_Testing
 * @subpackage Nelio_AB_Testing/includes/utils/functions
 * @since      5.4.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * Gets the value of a multidimensional array, safe checking the existence of all keys. If one key is not set or empty, it returns the default value.
 *
 * @param array<string|int,mixed> $collection    Multidimensional array.
 * @param string|list<string>     $keys          List of (nested) keys from the multidimensional array.
 * @param mixed                   $default_value Optional. Default value if keys are not found. Default: empty string.
 *
 * @return mixed the compositon of all its arguments (from left to right).
 *
 * @deprecated
 *
 * @since 5.5.5
 */
function nab_array_get( $collection, $keys, $default_value = '' ) {
	if ( ! is_array( $keys ) ) {
		$keys = explode( '.', "{$keys}" );
	}

	$value = $collection;
	foreach ( $keys as $key ) {
		if ( ! is_array( $value ) || ! isset( $value[ $key ] ) ) {
			return $default_value;
		}
		$value = $value[ $key ];
	}

	return $value;
}

/**
 * Checks if a predicate holds true for all the elements in an array.
 *
 * @template T
 *
 * @param callable(T):bool $predicate  Boolean function that takes one item of the array at a time.
 * @param list<T>          $collection Array of items.
 *
 * @return bool whether the preciate holds true for all the elements in an array.
 *
 * @since 5.4.0
 */
function nab_every( $predicate, $collection ) {
	foreach ( $collection as $item ) {
		if ( ! call_user_func( $predicate, $item ) ) {
			return false;
		}
	}
	return true;
}

/**
 * Checks if a predicate holds true for any element in an array.
 *
 * @template T
 *
 * @param callable(T):bool $predicate  Boolean function that takes one item of the array at a time.
 * @param list<T>          $collection Array of items.
 *
 * @return bool whether the preciate holds true for any element in an array.
 *
 * @since 5.4.0
 */
function nab_some( $predicate, $collection ) {
	foreach ( $collection as $item ) {
		if ( call_user_func( $predicate, $item ) ) {
			return true;
		}
	}
	return false;
}

/**
 * Checks if a predicate holds true for none of the elements in an array.
 *
 * @template T
 *
 * @param callable(T):bool $predicate  Boolean function that takes one item of the array at a time.
 * @param list<T>          $collection Array of items.
 *
 * @return bool whether the preciate holds true for none of the elements in an array.
 *
 * @since 5.4.0
 */
function nab_none( $predicate, $collection ) {
	return ! nab_some( $predicate, $collection );
}
