<?php

namespace Nelio_AB_Testing\EasyDigitalDownloads\Compat;

defined( 'ABSPATH' ) || exit;

use function add_action;
use function apply_filters;
use function do_action;
use function nab_get_running_experiments;
use function nab_is_split_testing_disabled;
use function EDD;

function load_alternatives_during_ajax_request() {

	if ( ! function_exists( 'EDD' ) ) {
		return;
	}//end if

	if ( nab_is_split_testing_disabled() ) {
		return;
	}//end if

	if ( ! isset( $_GET['wc-ajax'] ) ) { // phpcs:ignore
		return;
	}//end if

	if ( empty( EDD()->session ) ) {
		return;
	}//end if

	$nab_query_arg = EDD()->session->get( 'nab_alternative' );
	if ( false === $nab_query_arg ) {
		return;
	}//end if

	$experiments = nab_get_running_experiments();
	foreach ( $experiments as $experiment ) {

		$experiment_type = $experiment->get_type();

		/**
		 * Filters whether the experiment type (included in the filter name) is related to Easy Digital Downloads or not.
		 *
		 * @param boolean $is_edd_experiment Whether the experiment type is a EDD-related
		 *                                           experiment or not. Default: `false`.
		 *
		 * @since 6.0.0
		 */
		if ( ! apply_filters( "nab_is_{$experiment_type}_edd_experiment", false ) ) {
			continue;
		}//end if

		$control      = $experiment->get_alternative( 'control' );
		$alternatives = $experiment->get_alternatives();
		$alternative  = $alternatives[ $nab_query_arg % count( $alternatives ) ];

		/** This action is documented in public/helpers/class-nelio-ab-testing-runtime.php */
		do_action( "nab_{$experiment_type}_load_alternative", $alternative['attributes'], $control['attributes'], $experiment->get_id(), $alternative['id'] );

	}//end foreach
}//end load_alternatives_during_ajax_request()
add_action( 'init', __NAMESPACE__ . '\load_alternatives_during_ajax_request' );
