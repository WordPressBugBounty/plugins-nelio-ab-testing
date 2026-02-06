<?php

namespace Nelio_AB_Testing\WooCommerce\Experiment_Library\Product_Experiment;

defined( 'ABSPATH' ) || exit;

/**
 * Whether the alternative is a v1 alternative.
 *
 * @param TAttributes $alternative Alternative.
 *
 * @return bool
 *
 * @phpstan-assert-if-true TWC_Product_Alternative_Attributes_V1 $alternative
 */
function is_v1_alternative( $alternative ) {
	return (
		isset( $alternative['excerpt'] ) ||
		isset( $alternative['imageId'] ) ||
		isset( $alternative['imageUrl'] ) ||
		isset( $alternative['price'] )
	);
}//end is_v1_alternative()
