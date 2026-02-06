<?php
namespace Nelio_AB_Testing\Experiment_Library\Url_Experiment;

defined( 'ABSPATH' ) || exit;


/**
 * Callback to sanitize experiment scope.
 *
 * @param list<TScope_Rule>            $scope      Scope.
 * @param \Nelio_AB_Testing_Experiment $experiment Experiment.
 *
 * @return list<TScope_Rule>
 */
function sanitize_experiment_scope( $scope, $experiment ) {
	if ( 'nab/url' !== $experiment->get_type() ) {
		return $scope;
	}

	if ( ! empty( $scope ) ) {
		return $scope;
	}

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
}
add_filter( 'nab_sanitize_experiment_scope', __NAMESPACE__ . '\sanitize_experiment_scope', 10, 2 );
