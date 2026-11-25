<?php

namespace Nelio_AB_Testing\Experiment_Library\JavaScript_Experiment;

defined( 'ABSPATH' ) || exit;

/**
 * Registers the JavaScript experiment type definition.
 *
 * @param array<string,TExperiment_Type_Definition> $types Registered definitions.
 *
 * @return array<string,TExperiment_Type_Definition>
 */
function register_type( $types ) {
	$types['nab/javascript'] = array(
		'name'        => 'nab/javascript',
		'category'    => 'global',
		'title'       => _x( 'JavaScript', 'text (experiment name)', 'nelio-ab-testing' ),
		'description' => _x( 'Tests broad changes by running JavaScript in the browser.', 'text', 'nelio-ab-testing' ),
		'labels'      => array( 'create' => _x( 'Add new A/B test of JavaScript', 'command', 'nelio-ab-testing' ) ),
		'scope'       => 'urls',
		'help'        => array(
			'original'    => _x( 'JavaScript split tests help you to improve the conversion rate of your WordPress site by running custom code to tweak the tested page(s). The control version (commonly known as the “A” version) won’t run any code.', 'user', 'nelio-ab-testing' ),
			'alternative' => _x( 'Here you can create one or more JavaScript snippets that will be loaded when your page’s DOM is ready. Click on the “Edit” link of each variant to view and edit its associated snippet.', 'user', 'nelio-ab-testing' ),
		),
		'defaults'    => array(
			'original'    => array(),
			'alternative' => array(
				'name' => '',
				'code' => '',
			),
		),
		'expected'    => array(
			'pageStatsOnSingleScope' => true,
			'pageViews'              => array(
				'average' => 20000,
				'median'  => 3000,
				'p25'     => 750,
				'p75'     => 25000,
			),
		),
	);
	return $types;
}
add_filter( 'nab_experiment_types', __NAMESPACE__ . '\register_type', 23 );
