<?php

namespace Nelio_AB_Testing\Experiment_Library\Url_Experiment;

defined( 'ABSPATH' ) || exit;

use function add_filter;

/**
 * Callback to get alternative loaders.
 *
 * @param list<\Nelio_AB_Testing_Alternative_Loader<TUrl_Control_Attributes,TUrl_Alternative_Attributes>> $loaders        Loaders.
 * @param TUrl_Alternative_Attributes|TUrl_Control_Attributes                                             $alternative    Alternative.
 * @param TUrl_Control_Attributes                                                                         $control        Alternative.
 * @param int                                                                                             $experiment_id  Experiment ID.
 * @param string                                                                                          $alternative_id Alternative ID.
 *
 * @return list<\Nelio_AB_Testing_Alternative_Loader<TUrl_Control_Attributes,TUrl_Alternative_Attributes>>
 */
function get_alternative_loaders( $loaders, $alternative, $control, $experiment_id, $alternative_id ) {
	$loaders[] = new Alternative_Url_Loader( $alternative, $control, $experiment_id, $alternative_id );
	return $loaders;
}
add_filter( 'nab_get_nab/url_alternative_loaders', __NAMESPACE__ . '\get_alternative_loaders', 10, 5 );
