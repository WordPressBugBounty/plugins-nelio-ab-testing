<?php

namespace Nelio_AB_Testing\WooCommerce\Experiment_Library\Product_Experiment;

defined( 'ABSPATH' ) || exit;

/**
 * Registers the WooCommerce product experiment type definition.
 *
 * @param array<string,TExperiment_Type_Definition> $types Registered definitions.
 *
 * @return array<string,TExperiment_Type_Definition>
 */
function register_type( $types ) {
	$types['nab/wc-product'] = array(
		'name'               => 'nab/wc-product',
		'category'           => 'woocommerce',
		'title'              => _x( 'WooCommerce Product', 'text (experiment name)', 'nelio-ab-testing' ),
		'shortTitle'         => _x( 'Product', 'text (experiment name)', 'nelio-ab-testing' ),
		'description'        => _x( 'Tests alternative versions of a WooCommerce product.', 'text', 'nelio-ab-testing' ),
		'labels'             => array( 'create' => _x( 'Add new A/B test of WooCommerce products', 'command', 'nelio-ab-testing' ) ),
		'supportedPostTypes' => array( 'product' ),
		'automaticGoalSetup' => true,
		'scope'              => 'tested-product-with-consistency',
		'help'               => array(
			'original'    => _x( 'Product split tests help you to improve the interest your customers show in your products by creating one or more variants of the name, pricing, short description, and image of a WooCommerce Product. The control version (commonly known as the “A” version) is the already-existing product you want to test.', 'user', 'nelio-ab-testing' ),
			'alternative' => _x( 'Here you can create and edit one or more variants of your tested product. By default, each variant you create will use the product’s current values.', 'user', 'nelio-ab-testing' ),
		),
		'defaults'           => array(
			'original'    => array(
				'postId'   => 0,
				'postType' => 'product',
			),
			'alternative' => array(
				'name'   => '',
				'postId' => 0,
			),
		),
		'expected'           => array(
			'pageViews' => array(
				'average' => 7000,
				'median'  => 2500,
				'p25'     => 1000,
				'p75'     => 7000,
			),
		),
	);
	return $types;
}
add_filter( 'nab_experiment_types', __NAMESPACE__ . '\register_type', 25 );
