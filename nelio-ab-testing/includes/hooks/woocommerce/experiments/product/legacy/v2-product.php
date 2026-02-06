<?php

namespace Nelio_AB_Testing\WooCommerce\Experiment_Library\Product_Experiment;

defined( 'ABSPATH' ) || exit;

/**
 * Whether the alternative is a v2 alternative.
 *
 * @param TAttributes $alternative Alternative.
 *
 * @return bool
 *
 * @phpstan-assert-if-true TWC_Product_Alternative_Attributes_V2 $alternative
 */
function is_v2_alternative( $alternative ) {
	return (
		isset( $alternative['postId'] ) &&
		'nab_alt_product' === get_post_type( absint( $alternative['postId'] ) )
	);
}//end is_v2_alternative()
