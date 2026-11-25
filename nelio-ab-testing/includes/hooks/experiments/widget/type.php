<?php

namespace Nelio_AB_Testing\Experiment_Library\Widget_Experiment;

defined( 'ABSPATH' ) || exit;

/**
 * Registers the widget experiment type definition.
 *
 * @param array<string,TExperiment_Type_Definition> $types Registered definitions.
 *
 * @return array<string,TExperiment_Type_Definition>
 */
function register_type( $types ) {
	if ( ! current_theme_supports( 'widgets' ) ) {
		return $types;
	}

	$types['nab/widget'] = array(
		'name'        => 'nab/widget',
		'category'    => 'global',
		'title'       => _x( 'Widget', 'text (experiment name)', 'nelio-ab-testing' ),
		'description' => _x( 'Tests alternative widget configurations on the site.', 'text', 'nelio-ab-testing' ) . ' ' . _x( 'Use it when the test concerns widgets or sidebars rather than the broader content and layout of a page.', 'user (experiment type)', 'nelio-ab-testing' ),
		'labels'      => array( 'create' => _x( 'Add new A/B test of widgets', 'command', 'nelio-ab-testing' ) ),
		'scope'       => 'urls',
		'help'        => array(
			'original'    => _x( 'Widget split tests help you to improve the conversion rate of your WordPress site by changing the widgets you show on your sidebars (or widget areas). The control version (commonly known as the “A” version) is your current setup.', 'user', 'nelio-ab-testing' ),
			'alternative' => _x( 'Here you can create one or more variants of the widgets you use in your website. By default, each variant you create will be an exact copy of your control widgets. Click on the “Edit” link of each variant to modify its widgets.', 'user', 'nelio-ab-testing' ),
		),
		'defaults'    => array(
			'original'    => array(),
			'alternative' => array(
				'name'     => '',
				'sidebars' => array(),
			),
		),
		'expected'    => array(
			'pageStatsOnSingleScope' => true,
			'pageViews'              => array(
				'average' => 50000,
				'median'  => 7000,
				'p25'     => 2500,
				'p75'     => 45000,
			),
		),
	);
	return $types;
}
add_filter( 'nab_experiment_types', __NAMESPACE__ . '\register_type', 20 );
