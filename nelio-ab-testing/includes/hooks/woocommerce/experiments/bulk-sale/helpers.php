<?php

namespace Nelio_AB_Testing\WooCommerce\Experiment_Library\Bulk_Sale_Experiment;

use function Nelio_AB_Testing\WooCommerce\Helpers\Product_Selection\do_products_match;

defined( 'ABSPATH' ) || exit;

function is_product_under_test( $experiment_id, $control, $product_id ) {
	static $cache = array();
	$cache_key    = "{$experiment_id}:{$product_id}";

	if ( isset( $cache[ $cache_key ] ) ) {
		return $cache[ $cache_key ];
	}//end if

	$cache[ $cache_key ] = nab_some(
		function ( $selection ) use ( $product_id ) {
			return do_products_match( $selection, $product_id );
		},
		$control['productSelections']
	);

	return $cache[ $cache_key ];
}//end is_product_under_test()
