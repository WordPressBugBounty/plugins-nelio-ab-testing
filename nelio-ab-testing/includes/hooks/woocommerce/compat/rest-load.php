<?php

namespace Nelio_AB_Testing\WooCommerce\Compat;

defined( 'ABSPATH' ) || exit;

use function add_action;
use function apply_filters;
use function do_action;
use function nab_get_running_experiments;
use function nab_is_split_testing_disabled;
use function WC;

function load_alternatives_during_rest_request() {

	if ( ! function_exists( 'WC' ) ) {
		return;
	}//end if

	if ( nab_is_split_testing_disabled() ) {
		return;
	}//end if

	// TODO replace this function once core WP function is available: https://core.trac.wordpress.org/ticket/42061.
	if ( ! WC()->is_rest_api_request() ) {
		return;
	}//end if

	if ( empty( WC()->session ) ) {
		return;
	}//end if

	if ( ! WC()->session->has_session() ) {
		return;
	}//end if

	$nab_query_arg = WC()->session->get( 'nab_alternative', false );
	if ( false === $nab_query_arg ) {
		return;
	}//end if

	$experiments = nab_get_running_experiments();
	foreach ( $experiments as $experiment ) {

		$experiment_type = $experiment->get_type();

		/**
		 * Filters whether the experiment type (included in the filter name) is related to WooCommerce or not.
		 *
		 * @param boolean $is_woocommerce_experiment Whether the experiment type is a WooCommerce-related
		 *                                           experiment or not. Default: `false`.
		 *
		 * @since 5.0.0
		 */
		if ( ! apply_filters( "nab_is_{$experiment_type}_woocommerce_experiment", false ) ) {
			continue;
		}//end if

		$control      = $experiment->get_alternative( 'control' );
		$alternatives = $experiment->get_alternatives();
		$alternative  = $alternatives[ $nab_query_arg % count( $alternatives ) ];

		/** This action is documented in public/helpers/class-nelio-ab-testing-runtime.php */
		do_action( "nab_{$experiment_type}_load_alternative", $alternative['attributes'], $control['attributes'], $experiment->get_id(), $alternative['id'] );

	}//end foreach
}//end load_alternatives_during_rest_request()
add_action( 'rest_api_init', __NAMESPACE__ . '\load_alternatives_during_rest_request', 999 );
