<?php

namespace Nelio_AB_Testing\Experiment_Library\Url_Experiment;

defined( 'ABSPATH' ) || exit;

/**
 * Registers the URL experiment type definition.
 *
 * @param array<string,TExperiment_Type_Definition> $types Registered definitions.
 *
 * @return array<string,TExperiment_Type_Definition>
 */
function register_type( $types ) {
	$types['nab/url'] = array(
		'name'        => 'nab/url',
		'category'    => 'page',
		'title'       => _x( 'URL', 'text (experiment name)', 'nelio-ab-testing' ),
		'description' => _x( 'Compares independently published pages or other URLs.', 'text', 'nelio-ab-testing' ),
		'labels'      => array( 'create' => _x( 'Add new A/B test of URLs', 'command', 'nelio-ab-testing' ) ),
		'scope'       => 'tested-url-with-query-args',
		'help'        => array(
			'original'    => _x( 'URL split tests help you to improve the conversion rate of your WordPress site by defining one or more variants. The control version (commonly known as the “A” version) is the selected URL from your site (such as, for instance, the home page or a contact us page) you want to run the test on.', 'user', 'nelio-ab-testing' ),
			'alternative' => _x( 'You can create one or more variants of your tested URL. When the visitor visits the original URL, she may be redirected to one of the variants in the list.', 'user', 'nelio-ab-testing' ),
		),
		'defaults'    => array(
			'original'    => array( 'url' => '' ),
			'alternative' => array( 'url' => '' ),
		),
		'expected'    => array(
			'pageViews' => array(
				'average' => 5000,
				'median'  => 1000,
				'p25'     => 500,
				'p75'     => 3500,
			),
		),
	);
	return $types;
}
add_filter( 'nab_experiment_types', __NAMESPACE__ . '\register_type', 15 );
