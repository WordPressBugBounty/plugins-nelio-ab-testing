<?php

namespace Nelio_AB_Testing\Experiment_Library\Post_Experiment;

defined( 'ABSPATH' ) || exit;

use function add_filter;

/**
 * Returns mid priority.
 *
 * @return 'mid'
 */
add_filter( 'nab_nab/page_experiment_priority', 'nab_return_mid_priority' );
add_filter( 'nab_nab/post_experiment_priority', 'nab_return_mid_priority' );
add_filter( 'nab_nab/custom-post-type_experiment_priority', 'nab_return_mid_priority' );

/**
 * Callback to get alternative loaders.
 *
 * @param list<\Nelio_AB_Testing_Alternative_Loader<TPost_Control_Attributes,TPost_Alternative_Attributes>> $loaders        Loaders.
 * @param TPost_Alternative_Attributes|TPost_Control_Attributes                                             $alternative    Alternative.
 * @param TPost_Control_Attributes                                                                          $control        Alternative.
 * @param int                                                                                               $experiment_id  Experiment ID.
 * @param string                                                                                            $alternative_id Alternative ID.
 *
 * @return list<\Nelio_AB_Testing_Alternative_Loader<TPost_Control_Attributes,TPost_Alternative_Attributes>>
 */
function get_alternative_loaders( $loaders, $alternative, $control, $experiment_id, $alternative_id ) {
	if ( ! empty( $control['testAgainstExistingContent'] ) ) {
		$loaders[] = new Existing_Alternative_Post_Loader( $alternative, $control, $experiment_id, $alternative_id );
		return $loaders;
	}

	$loaders[] = new Alternative_Post_Loader( $alternative, $control, $experiment_id, $alternative_id );
	$loaders[] = new Control_Comments_Loader( $alternative, $control, $experiment_id, $alternative_id );
	return $loaders;
}
add_filter( 'nab_get_nab/page_alternative_loaders', __NAMESPACE__ . '\get_alternative_loaders', 10, 5 );
add_filter( 'nab_get_nab/post_alternative_loaders', __NAMESPACE__ . '\get_alternative_loaders', 10, 5 );
add_filter( 'nab_get_nab/custom-post-type_alternative_loaders', __NAMESPACE__ . '\get_alternative_loaders', 10, 5 );
