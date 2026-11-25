<?php

namespace Nelio_AB_Testing\Experiment_Library\Synced_Pattern_Experiment;

defined( 'ABSPATH' ) || exit;

/**
 * Registers the synced-pattern experiment type definition.
 *
 * @param array<string,TExperiment_Type_Definition> $types Registered definitions.
 *
 * @return array<string,TExperiment_Type_Definition>
 */
function register_type( $types ) {
	$types['nab/synced-pattern'] = array(
		'name'               => 'nab/synced-pattern',
		'category'           => 'page',
		'title'              => _x( 'WordPress Patterns', 'text (experiment name)', 'nelio-ab-testing' ),
		'description'        => _x( 'Tests alternative versions of a reusable synced pattern.', 'text', 'nelio-ab-testing' ),
		'labels'             => array( 'create' => _x( 'Add new A/B test of synced patterns', 'command', 'nelio-ab-testing' ) ),
		'supportedPostTypes' => array( 'wp_block' ),
		'help'               => array(
			'original'    => _x( 'Pattern split tests help you to improve the conversion rate of your WordPress blog by creating one or more variants of your WordPress patterns. The control version (commonly known as the “A” version) is an already-existing pattern, and the test will apply to all pages that use said pattern in their content.', 'user', 'nelio-ab-testing' ),
			'alternative' => _x( 'You can create one or more variants of your tested pattern. By default, each variant you create will be an exact copy of the tested pattern. Click on the “Edit” link of each variant to tweak it as you please.', 'user', 'nelio-ab-testing' ),
		),
		'defaults'           => array(
			'original'    => array( 'patternId' => 0 ),
			'alternative' => array(
				'name'      => '',
				'patternId' => 0,
			),
		),
		'expected'           => array(
			'pageStatsOnSingleScope' => true,
			'pageViews'              => array(
				'average' => 5000,
				'median'  => 1000,
				'p25'     => 500,
				'p75'     => 3500,
			),
		),
	);
	return $types;
}
add_filter( 'nab_experiment_types', __NAMESPACE__ . '\register_type', 13 );
