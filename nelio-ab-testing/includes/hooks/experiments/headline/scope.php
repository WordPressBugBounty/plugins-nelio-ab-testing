<?php
namespace Nelio_AB_Testing\Experiment_Library\Headline_Experiment;

defined( 'ABSPATH' ) || exit;


function sanitize_experiment_scope( $scope, $experiment ) {
	if ( 'nab/headline' !== $experiment->get_type() ) {
		return $scope;
	}//end if

	if ( empty( $scope ) ) {
		return $scope;
	}//end if

	$first_rule =
		'tested-post' === nab_array_get( $scope, '0.attributes.type' )
			? $scope[0]
			: array(
				'id'         => nab_uuid(),
				'attributes' => array(
					'type' => 'tested-post',
				),
			);

	$scope = array_filter(
		$scope,
		fn( $r ) => 'tested-post' !== nab_array_get( $r, 'attributes.type' )
	);
	return array_merge( array( $first_rule ), array_values( $scope ) );
}//end sanitize_experiment_scope()
add_filter( 'nab_sanitize_experiment_scope', __NAMESPACE__ . '\sanitize_experiment_scope', 10, 2 );
