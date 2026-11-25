<?php

namespace Nelio_AB_Testing\Experiment_Library\Php_Experiment;

defined( 'ABSPATH' ) || exit;

/**
 * Registers the PHP experiment type definition.
 *
 * @param array<string,TExperiment_Type_Definition> $types Registered definitions.
 *
 * @return array<string,TExperiment_Type_Definition>
 */
function register_type( $types ) {
	$types['nab/php'] = array(
		'name'        => 'nab/php',
		'category'    => 'global',
		'title'       => _x( 'PHP', 'text (experiment name)', 'nelio-ab-testing' ),
		'description' => _x( 'Loads alternative content by executing PHP code.', 'text', 'nelio-ab-testing' ) . ' ' . _x( 'Use it only when a server-side PHP implementation is required.', 'user (experiment type)', 'nelio-ab-testing' ),
		'labels'      => array( 'create' => _x( 'Add new A/B test using PHP', 'command', 'nelio-ab-testing' ) ),
		'scope'       => 'urls-or-php',
		'help'        => array(
			'original'    => _x( 'PHP split tests help you to improve the conversion rate of your WordPress site. The control version (commonly known as the “A” version) is how your site currently looks.', 'user', 'nelio-ab-testing' ),
			'alternative' => _x( 'Here you can create one or more PHP snippets that will run during the plugins_loaded action (or the action you selected in the test scope). This way, you’ll be able to hook into WordPress actions and filters and change the content of pages in the test scope.', 'user', 'nelio-ab-testing' ),
		),
		'defaults'    => array(
			'original'    => array(),
			'alternative' => array(
				'name'    => '',
				'snippet' => '',
			),
		),
		'expected'    => array(
			'pageStatsOnSingleScope' => true,
			'pageViews'              => array(
				'average' => 40000,
				'median'  => 30000,
				'p25'     => 5000,
				'p75'     => 75000,
			),
		),
	);
	return $types;
}
add_filter( 'nab_experiment_types', __NAMESPACE__ . '\register_type', 24 );
