<?php

namespace Nelio_AB_Testing\WooCommerce\Conversion_Action_Library\Order_Completed;

defined( 'ABSPATH' ) || exit;

use function add_filter;

/**
 * Callback to sanitize conversion action attributes.
 *
 * @param TAttributes        $attributes Attributes.
 * @param TConversion_Action $action Action.
 *
 * @return TAttributes
 */
function sanitize_conversion_action_attributes( $attributes, $action ) {
	if ( 'nab/wc-order' !== $action['type'] ) {
		return $attributes;
	}

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
add_filter( 'nab_sanitize_conversion_action_attributes', __NAMESPACE__ . '\sanitize_conversion_action_attributes', 10, 2 );

/**
 * Modernizes conversion action.
 *
 * @param TAttributes $attributes Attributes.
 *
 * @return TWC_Order_Attributes
 */
function modernize( $attributes ) {
	if ( isset( $attributes['type'] ) && 'product-selection' === $attributes['type'] && ! isset( $attributes['productId'] ) ) {
		// NOTE. Remove this on December 2027. This removes a prop that was added incorrectly in the past.
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
