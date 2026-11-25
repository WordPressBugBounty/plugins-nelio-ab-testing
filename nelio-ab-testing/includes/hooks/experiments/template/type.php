<?php

namespace Nelio_AB_Testing\Experiment_Library\Template_Experiment;

defined( 'ABSPATH' ) || exit;

/**
 * Registers the template experiment type definition.
 *
 * @param array<string,TExperiment_Type_Definition> $types Registered definitions.
 *
 * @return array<string,TExperiment_Type_Definition>
 */
function register_type( $types ) {
	$types['nab/template'] = array(
		'name'        => 'nab/template',
		'category'    => 'global',
		'title'       => _x( 'Template', 'text (experiment name)', 'nelio-ab-testing' ),
		'description' => _x( 'Tests alternative WordPress or page-builder templates.', 'text', 'nelio-ab-testing' ),
		'labels'      => array( 'create' => _x( 'Add new A/B test of templates', 'command', 'nelio-ab-testing' ) ),
		'help'        => array(
			'original'    => _x( 'Split tests of page templates help you to improve the conversion rate of your site by switching the appearance of the post types that use a certain template. The control version (commonly known as the “A” version) are those elements that use the specified template.', 'user', 'nelio-ab-testing' ),
			'alternative' => _x( 'You can test the selected template against one or more alternative templates from those available in your WordPress site.', 'user', 'nelio-ab-testing' ),
		),
		'defaults'    => array(
			'original'    => array(
				'postType' => '',
				'name'     => '',
			),
			'alternative' => array( 'name' => '' ),
		),
		'expected'    => array(
			'pageStatsOnSingleScope' => true,
			'pageViews'              => array(
				'average' => 10000,
				'median'  => 2500,
				'p25'     => 1000,
				'p75'     => 9000,
			),
		),
	);
	return $types;
}
add_filter( 'nab_experiment_types', __NAMESPACE__ . '\register_type', 17 );
