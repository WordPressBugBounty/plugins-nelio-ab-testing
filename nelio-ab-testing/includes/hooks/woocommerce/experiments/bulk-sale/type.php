<?php

namespace Nelio_AB_Testing\WooCommerce\Experiment_Library\Bulk_Sale_Experiment;

defined( 'ABSPATH' ) || exit;

/**
 * Registers the WooCommerce bulk-sale experiment type definition.
 *
 * @param array<string,TExperiment_Type_Definition> $types Registered definitions.
 *
 * @return array<string,TExperiment_Type_Definition>
 */
function register_type( $types ) {
	$types['nab/wc-bulk-sale'] = array(
		'name'               => 'nab/wc-bulk-sale',
		'category'           => 'woocommerce',
		'title'              => _x( 'WooCommerce Bulk Sale', 'text (experiment name)', 'nelio-ab-testing' ),
		'shortTitle'         => _x( 'Bulk Sale', 'text (experiment name)', 'nelio-ab-testing' ),
		'description'        => _x( 'Tests a percentage discount across selected WooCommerce products.', 'text', 'nelio-ab-testing' ),
		'labels'             => array( 'create' => _x( 'Add new A/B test of bulk sales in WooCommerce', 'command', 'nelio-ab-testing' ) ),
		'supportedPostTypes' => array( 'product' ),
		'automaticGoalSetup' => true,
		'help'               => array(
			'original'    => _x( 'This type of test lets you apply a bulk sale to all your (selected) products. The control version doesn’t apply any discount and simply shows the regular price of your selected products.', 'user', 'nelio-ab-testing' ),
			'alternative' => _x( 'Here you can create one or more bulk sale discounts that will apply to all your tested products.', 'user', 'nelio-ab-testing' ),
		),
		'defaults'           => array(
			'original'    => array( 'productSelections' => array( array( 'type' => 'all-products' ) ) ),
			'alternative' => array(
				'name'                        => '',
				'discount'                    => 20,
				'overwritesExistingSalePrice' => true,
			),
		),
		'expected'           => array(
			'pageViews' => array(
				'average' => 4500,
				'median'  => 4500,
				'p25'     => 3000,
				'p75'     => 6500,
			),
		),
	);
	return $types;
}
add_filter( 'nab_experiment_types', __NAMESPACE__ . '\register_type', 26 );
