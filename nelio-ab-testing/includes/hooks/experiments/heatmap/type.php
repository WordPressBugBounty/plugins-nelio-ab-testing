<?php

namespace Nelio_AB_Testing\Experiment_Library\Heatmap_Experiment;

defined( 'ABSPATH' ) || exit;

/**
 * Registers the heatmap experiment type definition.
 *
 * @param array<string,TExperiment_Type_Definition> $types Registered definitions.
 *
 * @return array<string,TExperiment_Type_Definition>
 */
function register_type( $types ) {
	$types['nab/heatmap'] = array(
		'name'        => 'nab/heatmap',
		'category'    => 'page',
		'title'       => _x( 'Heatmap', 'text (experiment name)', 'nelio-ab-testing' ),
		'description' => _x( 'Observes which parts of a web page attract the most attention.', 'text', 'nelio-ab-testing' ),
		'labels'      => array( 'create' => _x( 'Add new heatmap', 'command', 'nelio-ab-testing' ) ),
		'help'        => array(
			'original'    => '',
			'alternative' => '',
		),
		'defaults'    => array(
			'original'    => array(),
			'alternative' => array(),
		),
		'expected'    => array(
			'pageViews' => array(
				'average' => 0,
				'median'  => 0,
				'p25'     => 0,
				'p75'     => 0,
			),
		),
	);
	return $types;
}
add_filter( 'nab_experiment_types', __NAMESPACE__ . '\register_type', 16 );
