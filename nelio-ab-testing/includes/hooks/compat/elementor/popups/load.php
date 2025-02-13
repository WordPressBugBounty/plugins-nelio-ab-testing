<?php

namespace Nelio_AB_Testing\Compat\Elementor\Popups;

defined( 'ABSPATH' ) || exit;

use function add_action;
use function add_filter;

function prepare_alternative_popups() {
	if ( is_admin() ) {
		return;
	}//end if

	$experiments = nab_get_running_experiments();
	$experiments = array_filter( $experiments, __NAMESPACE__ . '\is_testing_elementor_popup' );

	if ( empty( $experiments ) ) {
		return;
	}//end if

	$all_popups = array_reduce(
		$experiments,
		function ( $result, $e ) {
			$popup_ids = array_map(
				fn( $a ) => absint( nab_array_get( $a, 'attributes.postId', 0 ) ),
				$e->get_alternatives()
			);
			return array_merge( $result, $popup_ids );
		},
		array()
	);

	$runtime       = \Nelio_AB_Testing_Runtime::instance();
	$alt           = $runtime->get_alternative_from_request();
	$active_popups = array_reduce(
		$experiments,
		function ( $result, $e ) use ( $alt ) {
			$alternatives = $e->get_alternatives();
			$alternative  = $alternatives[ $alt % count( $alternatives ) ];
			$alternative  = nab_array_get( $alternative, 'attributes.postId', 0 );
			$result[]     = absint( $alternative );
			return $result;
		},
		array()
	);

	add_filter(
		'get_post_status',
		fn( $status, $popup ) =>
			in_array( $popup->ID, $all_popups, true ) ? 'draft' : $status,
		10,
		2
	);

	add_filter(
		'get_post_status',
		fn( $status, $popup ) =>
			in_array( $popup->ID, $active_popups, true ) ? 'publish' : $status,
		11,
		2
	);
}//end prepare_alternative_popups()
add_action( 'plugins_loaded', __NAMESPACE__ . '\prepare_alternative_popups', 100 );

function is_relevant( $relevant, $experiment_id ) {
	if ( ! is_testing_elementor_popup( $experiment_id ) ) {
		return $relevant;
	}//end if

	if ( ! class_exists( '\ElementorPro\Modules\ThemeBuilder\Module' ) ) {
		return false;
	}//end if

	$experiment = nab_get_experiment( $experiment_id );
	if ( is_wp_error( $experiment ) ) {
		return false;
	}//end if

	$instance      = \ElementorPro\Modules\ThemeBuilder\Module::instance();
	$active_popups = array_keys( $instance->get_conditions_manager()->get_documents_for_location( 'popup' ) );

	$tested_popups = array_map(
		fn( $a ) => absint( nab_array_get( $a, 'attributes.postId', 0 ) ),
		$experiment->get_alternatives()
	);

	return ! empty( array_intersect( $active_popups, $tested_popups ) );
}//end is_relevant()
add_action( 'nab_is_nab/popup_relevant_in_url', __NAMESPACE__ . '\is_relevant', 10, 2 );
