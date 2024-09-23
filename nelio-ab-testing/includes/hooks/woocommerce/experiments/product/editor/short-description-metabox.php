<?php

namespace Nelio_AB_Testing\WooCommerce\Experiment_Library\Product_Experiment\Editor;

defined( 'ABSPATH' ) || exit;

use function add_action;
use function add_meta_box;

function add_short_description_metabox() {
	if ( ! is_callable( 'WC_Meta_Box_Product_Short_Description::output' ) ) {
		return;
	}//end if

	add_meta_box(
		'postexcerpt',
		__( 'Product short description', 'woocommerce' ),
		'WC_Meta_Box_Product_Short_Description::output',
		'nab_alt_product',
		'normal',
		'low',
		array(
			'__back_compat_meta_box' => true,
		)
	);
}//end add_short_description_metabox()
add_action( 'add_meta_boxes', __NAMESPACE__ . '\add_short_description_metabox' );
