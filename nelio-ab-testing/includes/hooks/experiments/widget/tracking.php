<?php
namespace Nelio_AB_Testing\Experiment_Library\Widget_Experiment;

defined( 'ABSPATH' ) || exit;

use function add_filter;

add_filter( 'nab_nab/widget_get_page_view_tracking_location', 'nab_return_footer' );

/**
 * Callback to include page view tracker in alternative loaders.
 *
 * @param list<\Nelio_AB_Testing_Alternative_Loader<TWidget_Control_Attributes,TWidget_Alternative_Attributes>> $loaders        Loaders.
 * @param TWidget_Alternative_Attributes|TWidget_Control_Attributes                                             $alternative    Alternative.
 * @param TWidget_Control_Attributes                                                                            $control        Alternative.
 * @param int                                                                                                   $experiment_id  Experiment ID.
 * @param string                                                                                                $alternative_id Alternative ID.
 *
 * @return list<\Nelio_AB_Testing_Alternative_Loader<TWidget_Control_Attributes,TWidget_Alternative_Attributes>>
 */
function include_tracker_in_alternative_loaders( $loaders, $alternative, $control, $experiment_id, $alternative_id ) {
	$loaders[] = new Page_View_Tracker( $alternative, $control, $experiment_id, $alternative_id );
	return $loaders;
}
add_filter( 'nab_get_nab/widget_alternative_loaders', __NAMESPACE__ . '\include_tracker_in_alternative_loaders', 10, 5 );
