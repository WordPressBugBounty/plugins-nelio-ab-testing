<?php

namespace Nelio_AB_Testing\WooCommerce\Conversion_Action_Library\Order_Completed;

defined( 'ABSPATH' ) || exit;

use function add_filter;

add_filter(
	'nab_sanitize_conversion_action_attributes',
	function ( $attributes, $action ) {
		if ( 'nab/wc-order' !== $action['type'] ) {
			return $attributes;
		}//end if

		$attributes = modernize( $attributes );
		$selection  = $attributes['value'];

		if (
			'some-products' === nab_array_get( $selection, 'type' ) &&
			empty( nab_array_get( $selection, 'value.productIds' ) )
		) {
			$attributes = array(
				'type'  => 'product-selection',
				'value' => array( 'type' => 'all-products' ),
			);
		}//end if

		$attributes['selection'] = $selection;
		return $attributes;
	},
	10,
	2
);

function modernize( $attributes ) {
	if ( isset( $attributes['type'] ) && 'product-selection' === $attributes['type'] && ! isset( $attributes['productId'] ) ) {
		return $attributes;
	}//end if

	$any = ! empty( $attributes['anyProduct'] );
	if ( $any ) {
		return array(
			'type'  => 'product-selection',
			'value' => array( 'type' => 'all-products' ),
		);
	}//end if

	$pid = isset( $attributes['productId'] ) ? $attributes['productId'] : 0;
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
}//end modernize()
