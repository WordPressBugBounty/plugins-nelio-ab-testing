<?php

namespace Nelio_AB_Testing\Experiment_Library\Widget_Experiment;

defined( 'ABSPATH' ) || exit;

use function add_action;
use function add_filter;
use function array_walk;
use function wp_list_pluck;

/**
 * Callback to create alternative content.
 *
 * @param TWidget_Alternative_Attributes $alternative    Alternative.
 * @param TWidget_Control_Attributes     $control        Control.
 * @param int                            $experiment_id  Experiment ID.
 * @param string                         $alternative_id Alternative ID.
 *
 * @return TWidget_Alternative_Attributes
 */
function create_alternative_content( $alternative, $control, $experiment_id, $alternative_id ) {
	$control_sidebars = get_control_sidebars();
	$new_sidebars     = duplicate_sidebars_for_alternative( $control_sidebars, $experiment_id, $alternative_id );
	return array(
		'name'     => $alternative['name'],
		'sidebars' => $new_sidebars,
	);
}
add_filter( 'nab_nab/widget_create_alternative_content', __NAMESPACE__ . '\create_alternative_content', 10, 4 );

// Control backup is equivalent to creating/removing variants.
add_filter( 'nab_nab/widget_backup_control', __NAMESPACE__ . '\create_alternative_content', 10, 4 );

/**
 * Callback to duplicate alternative content.
 *
 * @param TWidget_Alternative_Attributes $new_alternative New Alternative.
 * @param TWidget_Alternative_Attributes $old_alternative Old Alternative.
 * @param int                            $new_experiment_id  Experiment ID.
 * @param string                         $new_alternative_id Alternative ID.
 *
 * @return TWidget_Alternative_Attributes
 */
function duplicate_alternative_content( $new_alternative, $old_alternative, $new_experiment_id, $new_alternative_id ) {
	$sidebars                    = get_alternative_sidebars( $old_alternative );
	$sidebars                    = array_map( fn( $s )=>$s['id'], $sidebars );
	$new_alternative['sidebars'] = duplicate_sidebars_for_alternative( $sidebars, $new_experiment_id, $new_alternative_id );
	return $new_alternative;
}
add_filter( 'nab_nab/widget_duplicate_alternative_content', __NAMESPACE__ . '\duplicate_alternative_content', 10, 4 );

/**
 * Callback to apply alternative.
 *
 * @param bool                           $applied Applied.
 * @param TWidget_Alternative_Attributes $alternative Alternative.
 *
 * @return bool
 */
function apply_alternative( $applied, $alternative ) {
	$alternative_sidebars = array_map( fn( $s ) => $s['id'], $alternative['sidebars'] );
	$control_sidebars     = array_map( fn( $s ) => $s['control'], $alternative['sidebars'] );

	$helper = Widgets_Helper::instance();
	$helper->remove_alternative_sidebars( $control_sidebars );
	$helper->duplicate_sidebars( $alternative_sidebars, $control_sidebars );
	return true;
}
add_filter( 'nab_nab/widget_apply_alternative', __NAMESPACE__ . '\apply_alternative', 10, 2 );

/**
 * Callback to remove alternative content.
 *
 * @param TWidget_Alternative_Attributes $alternative Alternative.
 *
 * @return void
 */
function remove_alternative_content( $alternative ) {
	$alternative_sidebar_ids = array_map( fn( $s ) => $s['id'], $alternative['sidebars'] );

	$helper = Widgets_Helper::instance();
	$helper->remove_alternative_sidebars( $alternative_sidebar_ids );
}
add_action( 'nab_nab/widget_remove_alternative_content', __NAMESPACE__ . '\remove_alternative_content' );

/**
 * Callback to register sidebars for all widget experiments.
 *
 * @return void
 */
function register_sidebars_for_all_widget_experiments() {
	$experiment_ids = get_widget_experiment_ids();
	array_walk( $experiment_ids, __NAMESPACE__ . '\register_sidebars_in_experiment' );
}
add_action( 'widgets_init', __NAMESPACE__ . '\register_sidebars_for_all_widget_experiments', 99 );
