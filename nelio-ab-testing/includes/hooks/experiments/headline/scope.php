<?php
namespace Nelio_AB_Testing\Experiment_Library\Headline_Experiment;

defined( 'ABSPATH' ) || exit;


/**
 * Sanitizes experiment scope.
 *
 * @param list<TScope_Rule>            $scope      Scope.
 * @param \Nelio_AB_Testing_Experiment $experiment Experiment.
 *
 * @return list<TScope_Rule>
 */
function sanitize_experiment_scope( $scope, $experiment ) {
	if ( 'nab/headline' !== $experiment->get_type() ) {
		return $scope;
	}

	if ( empty( $scope ) ) {
		return $scope;
	}

	$first_rule =
		'tested-post' === $scope[0]['attributes']['type']
			? $scope[0]
			: array(
				'id'         => nab_uuid(),
				'attributes' => array(
					'type' => 'tested-post',
				),
			);

	$scope = array_filter(
		$scope,
		fn( $r ) => 'tested-post' !== $r['attributes']['type']
	);
	return array_merge( array( $first_rule ), array_values( $scope ) );
}
add_filter( 'nab_sanitize_experiment_scope', __NAMESPACE__ . '\sanitize_experiment_scope', 10, 2 );
