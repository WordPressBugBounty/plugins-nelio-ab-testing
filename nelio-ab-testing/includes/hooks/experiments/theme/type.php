<?php

namespace Nelio_AB_Testing\Experiment_Library\Theme_Experiment;

defined( 'ABSPATH' ) || exit;

/**
 * Registers the theme experiment type definition.
 *
 * @param array<string,TExperiment_Type_Definition> $types Registered definitions.
 *
 * @return array<string,TExperiment_Type_Definition>
 */
function register_type( $types ) {
	$types['nab/theme'] = array(
		'name'        => 'nab/theme',
		'category'    => 'global',
		'title'       => _x( 'Theme', 'text (experiment name)', 'nelio-ab-testing' ),
		'description' => _x( 'Tests different WordPress themes across the site.', 'text', 'nelio-ab-testing' ),
		'labels'      => array( 'create' => _x( 'Add new A/B test of themes', 'command', 'nelio-ab-testing' ) ),
		'scope'       => 'urls',
		'help'        => array(
			'original'    => _x( 'Split tests of WordPress themes help you to improve the conversion rate of your site by showing different themes to different visitors. The control version (commonly known as the “A” version) uses your current theme.', 'user', 'nelio-ab-testing' ),
			'alternative' => _x( 'You can test your active theme against one or more alternative themes from those installed in your WordPress site.', 'user', 'nelio-ab-testing' ),
		),
		'defaults'    => array(
			'original'    => array(),
			'alternative' => array(
				'name'    => '',
				'themeId' => '',
			),
		),
		'expected'    => array(
			'pageStatsOnSingleScope' => true,
			'pageViews'              => array(
				'average' => 20000,
				'median'  => 5000,
				'p25'     => 1000,
				'p75'     => 25000,
			),
		),
	);
	return $types;
}
add_filter( 'nab_experiment_types', __NAMESPACE__ . '\register_type', 18 );
