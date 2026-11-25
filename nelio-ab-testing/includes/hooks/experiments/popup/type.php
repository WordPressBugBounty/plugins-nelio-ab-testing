<?php

namespace Nelio_AB_Testing\Experiment_Library\Popup_Experiment;

defined( 'ABSPATH' ) || exit;

/**
 * Registers the popup experiment type definition.
 *
 * @param array<string,TExperiment_Type_Definition> $types Registered definitions.
 *
 * @return array<string,TExperiment_Type_Definition>
 */
function register_type( $types ) {
	$popup_types = get_testable_popup_types();
	if ( empty( $popup_types ) ) {
		return $types;
	}

	$types['nab/popup'] = array(
		'name'               => 'nab/popup',
		'category'           => 'global',
		'title'              => _x( 'Popup', 'text (experiment name)', 'nelio-ab-testing' ),
		'description'        => _x( 'Tests alternative versions of a popup.', 'text', 'nelio-ab-testing' ),
		'labels'             => array( 'create' => _x( 'Add new A/B test of popups', 'command', 'nelio-ab-testing' ) ),
		'supportedPostTypes' => $popup_types,
		'help'               => array(
			'original'    => _x( 'Split tests of popups help you to improve the conversion rate of your WordPress site by testing popups against each other. The control version (commonly known as the “A” version) is a popup you’re currently using on your site.', 'user', 'nelio-ab-testing' ),
			'alternative' => _x( 'You can select one or more alternative popups to test them against your control popup.', 'user', 'nelio-ab-testing' ),
		),
		'defaults'           => array(
			'original'    => array(
				'postType' => '',
				'postId'   => 0,
			),
			'alternative' => array( 'postId' => 0 ),
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
add_filter( 'nab_experiment_types', __NAMESPACE__ . '\register_type', 19 );
