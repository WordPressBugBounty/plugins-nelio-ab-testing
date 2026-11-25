<?php

namespace Nelio_AB_Testing\Experiment_Library\Menu_Experiment;

defined( 'ABSPATH' ) || exit;

/**
 * Registers the menu experiment type definition, if menu tests are supported.
 *
 * @param array<string,TExperiment_Type_Definition> $types Registered definitions.
 *
 * @return array<string,TExperiment_Type_Definition>
 */
function register_type( $types ) {
	$menus = wp_get_nav_menus();
	if ( empty( $menus ) || ! current_theme_supports( 'menus' ) ) {
		return $types;
	}

	$types['nab/menu'] = array(
		'name'        => 'nab/menu',
		'category'    => 'global',
		'title'       => _x( 'Menu', 'text (experiment name)', 'nelio-ab-testing' ),
		'description' => _x( 'Tests alternative navigation menus.', 'text', 'nelio-ab-testing' ),
		'labels'      => array( 'create' => _x( 'Add new A/B test of menus', 'command', 'nelio-ab-testing' ) ),
		'scope'       => 'urls',
		'help'        => array(
			'original'    => _x( 'Menu split tests help you to improve the conversion rate of your WordPress site by creating one or more variants of your navigation menus. The control version (commonly known as the “A” version) is the already-existing navigation menu you want to run the test on.', 'user', 'nelio-ab-testing' ),
			'alternative' => _x( 'Here you can create one or more variants of your tested menu. By default, each variant you create will be an exact copy of the tested menu. Click on the “Edit” link of each variant to tweak it as you please.', 'user', 'nelio-ab-testing' ),
		),
		'defaults'    => array(
			'original'    => array( 'menuId' => 0 ),
			'alternative' => array(
				'name'   => '',
				'menuId' => 0,
			),
		),
		'expected'    => array(
			'pageStatsOnSingleScope' => true,
			'pageViews'              => array(
				'average' => 25000,
				'median'  => 5000,
				'p25'     => 1000,
				'p75'     => 17000,
			),
		),
	);
	return $types;
}
add_filter( 'nab_experiment_types', __NAMESPACE__ . '\register_type', 21 );
