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

function duplicate_sidebars_for_alternative( $relevant_sidebars, $experiment_id, $alternative_id ) {

	$helper = Widgets_Helper::instance();

	$experiment = nab_get_experiment( $experiment_id );
	if ( ! empty( $experiment ) ) {
		$alternative = $experiment->get_alternative( $alternative_id );
		$sidebars    = get_alternative_sidebars( $alternative );
		$helper->remove_alternative_sidebars( $sidebars );
	}//end if

	$sidebar_prefix = get_sidebar_prefix( $experiment_id, $alternative_id );
	$new_sidebars   = array_map(
		function ( $sidebar ) use ( $sidebar_prefix ) {
			if ( is_array( $sidebar ) && isset( $sidebar['id'] ) ) {
				$sidebar = $sidebar['id'];
			}//end if

			$sidebar = preg_replace( '/^nab_alt_sidebar_.*_for_control_/', '', $sidebar );
			return array(
				'id'      => "$sidebar_prefix$sidebar",
				'control' => $sidebar,
			);
		},
		$relevant_sidebars
	);

	$alternative_sidebar_ids = wp_list_pluck( $new_sidebars, 'id' );
	$helper->duplicate_sidebars( $relevant_sidebars, $alternative_sidebar_ids );

	return $new_sidebars;
}//end duplicate_sidebars_for_alternative()

function duplicate_control_widgets_in_alternative( $experiment, $alternative ) {

	$sidebars       = get_control_sidebars();
	$experiment_id  = $experiment->get_id();
	$alternative_id = $alternative['id'];

	$alternative['attributes']['sidebars'] = duplicate_sidebars_for_alternative( $sidebars, $experiment_id, $alternative_id );

	$experiment->set_alternative( $alternative );
	$experiment->save();
}//end duplicate_control_widgets_in_alternative()

function get_control_sidebars() {

	global $wp_registered_sidebars;
	$sidebar_ids = wp_list_pluck( $wp_registered_sidebars, 'id' );
	return array_values(
		array_filter(
			$sidebar_ids,
			function ( $sidebar ) {
				return ! in_array( $sidebar, array( 'wp_inactive_widgets', 'array_version' ), true ) && false === strpos( $sidebar, 'nab_alt_sidebar_' );
			}
		)
	);
}//end get_control_sidebars()

function get_sidebar_prefix( $experiment_id, $alternative_id ) {
	return str_replace( '-', '_', strtolower( "nab_alt_sidebar_{$experiment_id}_{$alternative_id}_for_control_" ) );
}//end get_sidebar_prefix()

function get_widget_experiment_ids() {

	global $wpdb;
	$ids = $wpdb->get_col( // phpcs:ignore
		$wpdb->prepare(
			"SELECT post_id FROM $wpdb->postmeta WHERE meta_key = %s AND meta_value = %s",
			'_nab_experiment_type',
			'nab/widget'
		)
	);
	return array_map( '\absint', $ids );
}//end get_widget_experiment_ids()

function register_sidebars_in_experiment( $experiment_id ) {

	$control_backup = get_post_meta( $experiment_id, '_nab_control_backup', true );
	$control_backup = ! empty( $control_backup ) ? $control_backup : false;
	$control_backup = empty( $control_backup ) ? array() : array( $control_backup );

	$alternatives = (array) get_post_meta( $experiment_id, '_nab_alternatives', true );
	$alternatives = array_merge( $control_backup, $alternatives );
	$alternatives = filter_alternatives_with_attributes( $alternatives );
	$alternatives = wp_list_pluck( $alternatives, 'attributes' );
	$alternatives = array_map( __NAMESPACE__ . '\get_alternative_sidebars', $alternatives );

	array_walk(
		$alternatives,
		function ( $sidebars ) {
			array_walk( $sidebars, __NAMESPACE__ . '\register_alternative_sidebar' );
		}
	);
}//end register_sidebars_in_experiment()

function register_alternative_sidebar( $sidebar ) {

	$control_sidebar = get_control_sidebar( $sidebar['control'] );
	if ( ! $control_sidebar ) {
		return;
	}//end if

	$alternative_sidebar       = $control_sidebar;
	$alternative_sidebar['id'] = $sidebar['id'];
	register_sidebar( $alternative_sidebar );
}//end register_alternative_sidebar()

function get_control_sidebar( $sidebar_id ) {

	global $wp_registered_sidebars;
	if ( ! in_array( $sidebar_id, array_keys( $wp_registered_sidebars ), true ) ) {
		return false;
	}//end if

	return $wp_registered_sidebars[ $sidebar_id ];
}//end get_control_sidebar()

function get_alternative_sidebars( $alternative ) {

	if ( empty( $alternative ) ) {
		return array();
	}//end if

	if ( ! isset( $alternative['sidebars'] ) || empty( $alternative['sidebars'] ) ) {
		return array();
	}//end if

	return (array) $alternative['sidebars'];
}//end get_alternative_sidebars()

function filter_alternatives_with_attributes( $alternatives ) {

	return array_values(
		array_filter(
			$alternatives,
			function ( $alternative ) {
				return (
					isset( $alternative['attributes'] ) &&
					! empty( $alternative['attributes'] )
				);
			}
		)
	);
}//end filter_alternatives_with_attributes()

function get_sidebar_ids( $experiment_id, $alternative_id ) {

	$experiment  = nab_get_experiment( $experiment_id );
	$alternative = $experiment->get_alternative( $alternative_id );

	if ( empty( $alternative ) ) {
		return array();
	}//end if

	if ( ! isset( $alternative['attributes'] ) || ! isset( $alternative['attributes']['sidebars'] ) ) {
		return array();
	}//end if

	return wp_list_pluck( $alternative['attributes']['sidebars'], 'id' );
}//end get_sidebar_ids()
