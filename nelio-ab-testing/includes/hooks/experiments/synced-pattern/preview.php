<?php

namespace Nelio_AB_Testing\Experiment_Library\Synced_Pattern_Experiment;

use function add_filter;

defined( 'ABSPATH' ) || exit;

/**
 * Callback to get preview link.
 *
 * @param string|false                                                              $preview_link   Preview link.
 * @param TSynced_Pattern_Alternative_Attributes|TSynced_Pattern_Control_Attributes $alternative    Alternative.
 *
 * @return string|false
 */
function get_preview_link( $preview_link, $alternative ) {
	$pattern_id = $alternative['patternId'];
	$pattern    = get_post( $pattern_id );
	return $pattern
		? add_query_arg( 'nab-synced-pattern-preview-mode', 'true', nab_home_url() )
		: $preview_link;
}
add_filter( 'nab_nab/synced-pattern_preview_link_alternative', __NAMESPACE__ . '\get_preview_link', 10, 2 );

/**
 * Callback to get alternative loaders during preview.
 *
 * @param list<\Nelio_AB_Testing_Alternative_Loader<TSynced_Pattern_Control_Attributes,TSynced_Pattern_Alternative_Attributes>> $loaders        Loaders.
 * @param TSynced_Pattern_Alternative_Attributes|TSynced_Pattern_Control_Attributes                                             $alternative    Alternative.
 * @param TSynced_Pattern_Control_Attributes                                                                                    $control        Control.
 * @param int                                                                                                                   $experiment_id  Experiment ID.
 * @param string                                                                                                                $alternative_id Alternative ID.
 *
 * @return list<\Nelio_AB_Testing_Alternative_Loader<TSynced_Pattern_Control_Attributes,TSynced_Pattern_Alternative_Attributes>>
 */
function get_alternative_loaders_during_preview( $loaders, $alternative, $control, $experiment_id, $alternative_id ) {
	$loaders[] = new Alternative_Pattern_Preview_Loader( $alternative, $control, $experiment_id, $alternative_id );
	return $loaders;
}
add_filter( 'nab_get_nab/synced-pattern_alternative_loaders_during_preview', __NAMESPACE__ . '\get_alternative_loaders_during_preview', 10, 5 );
