<?php

namespace Nelio_AB_Testing\Experiment_Library\Headline_Experiment;

defined( 'ABSPATH' ) || exit;

use function add_filter;

/**
 * Callback to get alternative loaders.
 *
 * @param list<\Nelio_AB_Testing_Alternative_Loader<THeadline_Control_Attributes,THeadline_Alternative_Attributes>> $loaders        Loaders.
 * @param THeadline_Alternative_Attributes|THeadline_Control_Attributes                                             $alternative    Alternative.
 * @param THeadline_Control_Attributes                                                                              $control        Alternative.
 * @param int                                                                                                       $experiment_id  Experiment ID.
 * @param string                                                                                                    $alternative_id Alternative ID.
 *
 * @return list<\Nelio_AB_Testing_Alternative_Loader<THeadline_Control_Attributes,THeadline_Alternative_Attributes>>
 */
function get_alternative_loaders( $loaders, $alternative, $control, $experiment_id, $alternative_id ) {
	if ( is_control( $alternative, $control ) ) {
		return $loaders;
	}

	$loaders[] = new Alternative_Headline_Loader( $alternative, $control, $experiment_id, $alternative_id );
	return $loaders;
}
add_filter( 'nab_get_nab/headline_alternative_loaders', __NAMESPACE__ . '\get_alternative_loaders', 10, 5 );

/**
 * Whether the given alternative is the control alternative or not.
 *
 * @param THeadline_Alternative_Attributes|THeadline_Control_Attributes $alternative    Alternative.
 * @param THeadline_Control_Attributes                                  $control        Control.
 *
 * @return bool
 *
 * @phpstan-assert-if-true THeadline_Control_Attributes $alternative
 */
function is_control( $alternative, $control ) {
	return ! empty( $alternative['postId'] ) && $alternative['postId'] === $control['postId'];
}
