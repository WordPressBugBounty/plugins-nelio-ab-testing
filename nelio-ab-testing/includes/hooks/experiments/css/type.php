<?php

namespace Nelio_AB_Testing\Experiment_Library\Css_Experiment;

defined( 'ABSPATH' ) || exit;

/**
 * Registers the CSS experiment type definition.
 *
 * @param array<string,TExperiment_Type_Definition> $types Registered definitions.
 *
 * @return array<string,TExperiment_Type_Definition>
 */
function register_type( $types ) {
	return array(
		'nab/css' => array(
			'name'        => 'nab/css',
			'category'    => 'global',
			'title'       => _x( 'CSS and Text', 'text (experiment name)', 'nelio-ab-testing' ),
			'description' => _x( 'Tests small visual or wording changes to page elements.', 'text', 'nelio-ab-testing' ),
			'labels'      => array( 'create' => _x( 'Add new A/B test of CSS', 'command', 'nelio-ab-testing' ) ),
			'scope'       => 'urls',
			'help'        => array(
				'original'    => _x( 'CSS split tests help you to improve the conversion rate of your WordPress site by changing its look and feel. The control version (commonly known as the “A” version) is how your site currently looks with the active theme, with no extra CSS snippets loaded in the front-end.', 'user', 'nelio-ab-testing' ),
				'alternative' => _x( 'Here you can create one or more CSS snippets that will be loaded after your theme’s own styles. This way, you can test what style works the best for you. Click on the “Edit” link of each variant to view and edit the corresponding CSS snippet.', 'user', 'nelio-ab-testing' ),
			),
			'defaults'    => array(
				'original'    => array(),
				'alternative' => array(
					'name'    => '',
					'css'     => '',
					'content' => array(),
				),
			),
			'expected'    => array(
				'pageStatsOnSingleScope' => true,
				'pageViews'              => array(
					'average' => 20000,
					'median'  => 2500,
					'p25'     => 600,
					'p75'     => 15000,
				),
			),
		),
	) + $types;
}
add_filter( 'nab_experiment_types', __NAMESPACE__ . '\register_type', 22 );
