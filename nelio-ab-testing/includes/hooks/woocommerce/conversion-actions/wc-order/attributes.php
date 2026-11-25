<?php

namespace Nelio_AB_Testing\WooCommerce\Conversion_Action_Library\Order_Completed;

defined( 'ABSPATH' ) || exit;

use function add_filter;


/**
 * Callback to sanitize conversion action attributes.
 *
 * @param TAttributes $attributes Attributes.
 *
 * @return TAttributes
 */
function sanitize( $attributes ) {
	$attributes = modernize( $attributes );
	$selection  = $attributes['value'];

	if (
		'some-products' === $selection['type'] &&
		empty( $selection['value']['productIds'] )
	) {
		$attributes = array(
			'type'  => 'product-selection',
			'value' => array( 'type' => 'all-products' ),
		);
	}

	return $attributes;
}
add_filter( 'nab_nab/wc-order_sanitize_conversion_action_attributes', __NAMESPACE__ . '\sanitize' );

/**
 * Sanitizes goal attributes that configure WooCommerce purchase statuses.
 *
 * @param TAttributes $attributes Goal attributes.
 * @param TGoal       $goal       Goal.
 *
 * @return TAttributes Sanitized goal attributes.
 */
function sanitize_goal_attributes( $attributes, $goal ) {
	$action_types = array_map( fn( $action ) => $action['type'], $goal['conversionActions'] );
	if ( ! in_array( 'nab/wc-order', $action_types, true ) ) {
		return $attributes;
	}

	if ( ! array_key_exists( 'orderStatusForConversion', $attributes ) ) {
		return $attributes;
	}

	$statuses = function_exists( 'wc_get_order_statuses' ) ? wc_get_order_statuses() : array();
	$status   = $attributes['orderStatusForConversion'];
	if ( ! is_string( $status ) || ! array_key_exists( $status, $statuses ) ) {
		if ( array_key_exists( 'wc-completed', $statuses ) ) {
			$attributes['orderStatusForConversion'] = 'wc-completed';
		} elseif ( ! empty( $statuses ) ) {
			$attributes['orderStatusForConversion'] = (string) array_key_first( $statuses );
		} else {
			unset( $attributes['orderStatusForConversion'] );
		}
	}

	return $attributes;
}
add_filter( 'nab_sanitize_goal_attributes', __NAMESPACE__ . '\sanitize_goal_attributes', 10, 2 );


/**
 * Modernizes conversion action.
 *
 * @param TAttributes $attributes Attributes.
 *
 * @return TWC_Order_Attributes
 */
function modernize( $attributes ) {
	if ( isset( $attributes['type'] ) && 'product-selection' === $attributes['type'] && ! isset( $attributes['productId'] ) ) {
		// DEPRECATED. Remove this on December 2027. This removes a prop that was added incorrectly in the past.
		if ( isset( $attributes['selection'] ) ) {
			unset( $attributes['selection'] );
		}

		/** @var TWC_Order_Attributes */
		return $attributes;
	}

	$any = ! empty( $attributes['anyProduct'] );
	if ( $any ) {
		return array(
			'type'  => 'product-selection',
			'value' => array( 'type' => 'all-products' ),
		);
	}

	$pid = isset( $attributes['productId'] ) ? absint( $attributes['productId'] ) : 0;
	return array(
		'type'  => 'product-selection',
		'value' => array(
			'type'  => 'some-products',
			'value' => array(
				'type'       => 'product-ids',
				'mode'       => 'and',
				'productIds' => ! empty( $pid ) ? array( $pid ) : array(),
			),
		),
	);
}
