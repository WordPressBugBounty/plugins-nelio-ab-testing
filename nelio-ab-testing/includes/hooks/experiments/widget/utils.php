<?php

namespace Nelio_AB_Testing\Experiment_Library\Widget_Experiment;

defined( 'ABSPATH' ) || exit;

use function array_filter;
use function array_keys;
use function array_map;
use function array_values;
use function array_walk;
use function get_post_meta;
use function in_array;
use function register_sidebar;
use function wp_list_pluck;

/**
 * Duplicates sidebars for alternative.
 *
 * @param list<string> $relevant_sidebars Relevants sidebars.
 * @param int          $experiment_id     Experiment ID.
 * @param string       $alternative_id    Alternative ID.
 *
 * @return list<TWidget_Alternative_Sidebar>
 */
function duplicate_sidebars_for_alternative( $relevant_sidebars, $experiment_id, $alternative_id ) {

	$helper = Widgets_Helper::instance();

	$experiment = nab_get_experiment( $experiment_id );
	if ( ! is_wp_error( $experiment ) ) {
		$alternative = $experiment->get_alternative( $alternative_id );
		$alternative = is_array( $alternative ) ? $alternative['attributes'] : array();
		$sidebars    = get_alternative_sidebars( $alternative );
		$sidebar_ids = array_map( fn( $s ) => $s['id'], $sidebars );
		$helper->remove_alternative_sidebars( $sidebar_ids );
	}

	$sidebar_prefix = get_sidebar_prefix( $experiment_id, $alternative_id );
	$new_sidebars   = array_map(
		function ( $sidebar ) use ( $sidebar_prefix ) {
			$sidebar = preg_replace( '/^nab_alt_sidebar_.*_for_control_/', '', $sidebar );
			$sidebar = is_string( $sidebar ) ? $sidebar : '';
			return array(
				'id'      => "$sidebar_prefix$sidebar",
				'control' => $sidebar,
			);
		},
		$relevant_sidebars
	);

	$alternative_sidebar_ids = array_map( fn( $s )=>$s['id'], $new_sidebars );
	$helper->duplicate_sidebars( $relevant_sidebars, $alternative_sidebar_ids );

	return $new_sidebars;
}

/**
 * Duplicates control widgets in alternative.
 *
 * @param \Nelio_AB_Testing_Experiment $experiment  Experiment.
 * @param TAlternative                 $alternative Alternative.
 *
 * @return void
 */
function duplicate_control_widgets_in_alternative( $experiment, $alternative ) {

	$sidebars       = get_control_sidebars();
	$experiment_id  = $experiment->get_id();
	$alternative_id = $alternative['id'];

	$alternative['attributes']['sidebars'] = duplicate_sidebars_for_alternative( $sidebars, $experiment_id, $alternative_id );

	$experiment->set_alternative( $alternative );
	$experiment->save();
}

/**
 * Returns control sidebars.
 *
 * @return list<string>
 */
function get_control_sidebars() {
	/** @var array<string,TWP_Widget_Sidebar> $wp_registered_sidebars */
	global $wp_registered_sidebars;

	$sidebar_ids = array_keys( $wp_registered_sidebars );
	return array_values(
		array_filter(
			$sidebar_ids,
			function ( $sidebar ) {
				return ! in_array( $sidebar, array( 'wp_inactive_widgets', 'array_version' ), true ) && false === strpos( $sidebar, 'nab_alt_sidebar_' );
			}
		)
	);
}

/**
 * Returns sidebar prefix.
 *
 * @param int    $experiment_id  Experiment ID.
 * @param string $alternative_id Alternative ID.
 *
 * @return string
 */
function get_sidebar_prefix( $experiment_id, $alternative_id ) {
	return str_replace( '-', '_', strtolower( "nab_alt_sidebar_{$experiment_id}_{$alternative_id}_for_control_" ) );
}

/**
 * Returns IDs of all widget experiments.
 *
 * @return list<int>
 */
function get_widget_experiment_ids() {

	/** @var \wpdb $wpdb */
	global $wpdb;
	// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
	$ids = $wpdb->get_col(
		$wpdb->prepare(
			'SELECT post_id FROM %i WHERE meta_key = %s AND meta_value = %s',
			$wpdb->postmeta,
			'_nab_experiment_type',
			'nab/widget'
		)
	);
	return array_values( array_filter( array_map( fn( $id ) => absint( $id ), $ids ) ) );
}

/**
 * Registers sidebars in experiment.
 *
 * @param int $experiment_id Experiment ID.
 *
 * @return void
 */
function register_sidebars_in_experiment( $experiment_id ) {

	$control_backup = get_post_meta( $experiment_id, '_nab_control_backup', true );
	$control_backup = ! empty( $control_backup ) ? $control_backup : false;
	$control_backup = ! empty( $control_backup ) ? array( $control_backup ) : array();

	$alternatives = get_post_meta( $experiment_id, '_nab_alternatives', true );
	$alternatives = ! empty( $alternatives ) ? $alternatives : array();
	$alternatives = array_merge( $control_backup, $alternatives );
	$alternatives = filter_alternatives_with_attributes( $alternatives );
	$alternatives = array_map( fn( $a ) => get_alternative_sidebars( $a['attributes'] ), $alternatives );

	array_walk(
		$alternatives,
		function ( $sidebars ) {
			array_walk( $sidebars, __NAMESPACE__ . '\register_alternative_sidebar' );
		}
	);
}

/**
 * Registers alternative sidebar.
 *
 * @param TWidget_Alternative_Sidebar $sidebar Sidebar.
 *
 * @return void
 */
function register_alternative_sidebar( $sidebar ) {

	$control_sidebar = get_control_sidebar( $sidebar['control'] );
	if ( ! $control_sidebar ) {
		return;
	}

	$alternative_sidebar       = $control_sidebar;
	$alternative_sidebar['id'] = $sidebar['id'];
	register_sidebar( $alternative_sidebar );
}

/**
 * Returns the control sidebar associated to the given sidebar ID.
 *
 * @param string $sidebar_id Sidebar ID.
 *
 * @return TWP_Widget_Sidebar|false
 */
function get_control_sidebar( $sidebar_id ) {
	/** @var array<string,TWP_Widget_Sidebar> $wp_registered_sidebars */
	global $wp_registered_sidebars;

	if ( ! in_array( $sidebar_id, array_keys( $wp_registered_sidebars ), true ) ) {
		return false;
	}

	return $wp_registered_sidebars[ $sidebar_id ];
}

/**
 * Returns alternative sidebars.
 *
 * @param TAttributes|false $alternative Alternative.
 *
 * @return list<TWidget_Alternative_Sidebar>
 */
function get_alternative_sidebars( $alternative ) {
	if ( empty( $alternative['sidebars'] ) ) {
		return array();
	}

	/** @var TWidget_Alternative_Attributes $alternative */
	return $alternative['sidebars'];
}

/**
 * Filters alternatives with attributes.
 *
 * @param list<array{id?:string,attributes?:array<string,mixed>}> $alternatives Alternatives.
 *
 * @return list<TAlternative>
 */
function filter_alternatives_with_attributes( $alternatives ) {
	$alternatives = array_filter( $alternatives, fn( $a ) => ! empty( $a['id'] ) && ! empty( $a['attributes'] ) );
	return array_values( $alternatives );
}

/**
 * Returns sidebar IDs.
 *
 * @param int    $experiment_id  Experiment ID.
 * @param string $alternative_id Alternative ID.
 *
 * @return list<string>
 */
function get_sidebar_ids( $experiment_id, $alternative_id ) {

	$experiment = nab_get_experiment( $experiment_id );
	if ( is_wp_error( $experiment ) ) {
		return array();
	}

	$alternative = $experiment->get_alternative( $alternative_id );
	if ( empty( $alternative ) ) {
		return array();
	}

	if ( empty( $alternative['attributes']['sidebars'] ) ) {
		return array();
	}

	/** @var TWidget_Alternative_Attributes $alternative */
	$alternative = $alternative['attributes'];
	return array_map( fn( $s ) => $s['id'], $alternative['sidebars'] );
}
