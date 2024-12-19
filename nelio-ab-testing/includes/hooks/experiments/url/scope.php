<?php
namespace Nelio_AB_Testing\Experiment_Library\Url_Experiment;

defined( 'ABSPATH' ) || exit;


function sanitize_experiment_scope( $scope, $experiment ) {
	if ( 'nab/url' !== $experiment->get_type() ) {
		return $scope;
	}//end if

	if ( ! empty( $scope ) ) {
		return $scope;
	}//end if

	return array(
		array(
			'id'         => nab_uuid(),
			'attributes' => array(
				'type'  => 'tested-url-with-query-args',
				'value' => array(
					'urls' => array(),
					'args' => array(),
				),
			),
		),
	);
}//end sanitize_experiment_scope()
add_filter( 'nab_sanitize_experiment_scope', __NAMESPACE__ . '\sanitize_experiment_scope', 10, 2 );
