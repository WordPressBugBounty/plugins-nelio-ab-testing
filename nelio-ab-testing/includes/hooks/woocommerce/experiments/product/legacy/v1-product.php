<?php

namespace Nelio_AB_Testing\WooCommerce\Experiment_Library\Product_Experiment;

defined( 'ABSPATH' ) || exit;

function is_v1_alternative( $alternative ) {
	return (
		isset( $alternative['excerpt'] ) ||
		isset( $alternative['imageId'] ) ||
		isset( $alternative['imageUrl'] ) ||
		isset( $alternative['price'] )
	);
}//end is_v1_alternative()
