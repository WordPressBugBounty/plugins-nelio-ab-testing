<?php

namespace Nelio_AB_Testing\WooCommerce\Experiment_Library\Product_Experiment;

defined( 'ABSPATH' ) || exit;

function is_v2_alternative( $alternative ) {
	return (
		isset( $alternative['postId'] ) &&
		'nab_alt_product' === get_post_type( $alternative['postId'] )
	);
}//end is_v2_alternative()
