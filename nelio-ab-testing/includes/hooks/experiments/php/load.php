<?php

namespace Nelio_AB_Testing\Experiment_Library\Php_Experiment;

defined( 'ABSPATH' ) || exit;

use function add_filter;

/**
 * Callback to filter PHP experiment priority.
 *
 * @param 'low'|'mid'|'high'|'custom' $priority      Experiment priority.
 * @param TPhp_Control_Attributes     $control       Control attributes.
 * @param int                         $experiment_id Experiment ID.
 *
 * @return 'low'|'mid'|'high'|'custom'
 */
function get_experiment_priority( $priority, $control, $experiment_id ) {
	$experiment = nab_get_experiment( $experiment_id );
	assert( ! is_wp_error( $experiment ) );

	$scope = $experiment->get_scope();
	$scope = $scope[0]['attributes'] ?? false;
	if ( empty( $scope ) || 'php-snippet' !== $scope['type'] ) {
		return 'high';
	}

	return $scope['value']['priority'];
}
add_filter( 'nab_nab/php_experiment_priority', __NAMESPACE__ . '\get_experiment_priority', 10, 3 );

/**
 * Callback to get alternative loaders.
 *
 * @param list<\Nelio_AB_Testing_Alternative_Loader<TPhp_Control_Attributes,TPhp_Alternative_Attributes>> $loaders        Loaders.
 * @param TPhp_Alternative_Attributes|TPhp_Control_Attributes                                             $alternative    Alternative.
 * @param TPhp_Control_Attributes                                                                         $control        Alternative.
 * @param int                                                                                             $experiment_id  Experiment ID.
 * @param string                                                                                          $alternative_id Alternative ID.
 *
 * @return list<\Nelio_AB_Testing_Alternative_Loader<TPhp_Control_Attributes,TPhp_Alternative_Attributes>>
 */
function get_alternative_loaders( $loaders, $alternative, $control, $experiment_id, $alternative_id ) {
	if ( ! empty( $alternative['errorMessage'] ) ) {
		return $loaders;
	}

	$loaders[] = new Alternative_Php_Loader( $alternative, $control, $experiment_id, $alternative_id );
	return $loaders;
}
add_filter( 'nab_get_nab/php_alternative_loaders', __NAMESPACE__ . '\get_alternative_loaders', 10, 5 );

add_filter( 'nab_nab/php_get_alternative_summary', '__return_empty_array' );
