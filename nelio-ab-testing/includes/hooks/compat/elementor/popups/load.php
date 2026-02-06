<?php

namespace Nelio_AB_Testing\Compat\Elementor\Popups;

defined( 'ABSPATH' ) || exit;

use function add_action;
use function add_filter;

/**
 * Callback to prepare alternative poups.
 *
 * @return void
 */
function prepare_alternative_popups() {
	if ( is_admin() ) {
		return;
	}

	$experiments = nab_get_running_experiments();
	$experiments = array_filter( $experiments, __NAMESPACE__ . '\is_testing_elementor_popup' );

	if ( empty( $experiments ) ) {
		return;
	}

	$alt = nab_get_requested_alternative();
	if ( empty( $alt ) ) {
		return;
	}

	$all_popups = array_reduce(
		$experiments,
		function ( $result, $e ) {
			$popup_ids = array_map(
				fn( $a ) => absint( $a['attributes']['postId'] ?? 0 ),
				$e->get_alternatives()
			);
			return array_merge( $result, $popup_ids );
		},
		array()
	);

	$active_popups = array_reduce(
		$experiments,
		function ( $result, $e ) use ( $alt ) {
			/** @var list<int> $result */

			$alternatives = $e->get_alternatives();
			$alternative  = $alternatives[ $alt % count( $alternatives ) ];
			$result[]     = absint( $alternative['attributes']['postId'] ?? 0 );
			return $result;
		},
		array()
	);

	add_filter(
		'get_post_status',
		function ( $status, $popup ) use ( &$all_popups ) {
			/** @var string   $status */
			/** @var \WP_Post $popup  */
			return in_array( $popup->ID, $all_popups, true ) ? 'draft' : $status;
		},
		10,
		2
	);

	add_filter(
		'get_post_status',
		function ( $status, $popup ) use ( &$active_popups ) {
			/** @var string   $status */
			/** @var \WP_Post $popup  */
			return in_array( $popup->ID, $active_popups, true ) ? 'publish' : $status;
		},
		11,
		2
	);
}
add_action( 'plugins_loaded', __NAMESPACE__ . '\prepare_alternative_popups', 100 );

/**
 * Callback to determine if  the experiment is relevant or not.
 *
 * @param bool $relevant Relevant.
 * @param int  $experiment_id Experiment ID.
 *
 * @return bool
 */
function is_relevant( $relevant, $experiment_id ) {
	if ( ! is_testing_elementor_popup( $experiment_id ) ) {
		return $relevant;
	}

	if ( ! class_exists( '\ElementorPro\Modules\ThemeBuilder\Module' ) ) {
		return false;
	}

	$experiment = nab_get_experiment( $experiment_id );
	if ( is_wp_error( $experiment ) ) {
		return false;
	}

	$instance      = \ElementorPro\Modules\ThemeBuilder\Module::instance();
	$active_popups = array_keys( $instance->get_conditions_manager()->get_documents_for_location( 'popup' ) );

	$tested_popups = array_map(
		fn( $a ) => absint( $a['attributes']['postId'] ?? 0 ),
		$experiment->get_alternatives()
	);

	return ! empty( array_intersect( $active_popups, $tested_popups ) );
}
add_filter( 'nab_is_nab/popup_relevant_in_url', __NAMESPACE__ . '\is_relevant', 10, 2 );
