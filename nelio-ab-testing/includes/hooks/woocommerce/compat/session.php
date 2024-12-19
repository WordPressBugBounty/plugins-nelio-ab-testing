<?php

namespace Nelio_AB_Testing\WooCommerce\Compat;

defined( 'ABSPATH' ) || exit;

use function add_action;
use function add_filter;
use function WC;

function maybe_customize_main_script( $settings ) {
	$settings['ajaxUrl'] = admin_url( 'admin-ajax.php' );
	if ( function_exists( 'is_checkout' ) && is_checkout() ) {
		$settings['forceECommerceSessionSync'] = true;
	}//end if

	if ( function_exists( 'is_cart' ) && is_cart() ) {
		$settings['forceECommerceSessionSync'] = true;
	}//end if

	return $settings;
}//end maybe_customize_main_script()
add_filter( 'nab_main_script_settings', __NAMESPACE__ . '\maybe_customize_main_script' );

function sync_ecommerce_session() {

	if ( ! function_exists( 'WC' ) ) {
		return;
	}//end if

	if ( empty( WC()->session ) ) {
		return;
	}//end if

	if ( ! WC()->session->has_session() ) {
		return;
	}//end if

	if (
		! isset( $_REQUEST['alternative'] ) || // phpcs:ignore
		! isset( $_REQUEST['expsWithView'] ) || // phpcs:ignore
		! isset( $_REQUEST['expSegments'] ) || // phpcs:ignore
		! isset( $_REQUEST['uniqueViews'] ) // phpcs:ignore
	) {
		return;
	}//end if

	$alternative    = intval( $_REQUEST['alternative'] ); // phpcs:ignore
	$exps_with_view = json_decode( sanitize_text_field( wp_unslash( $_REQUEST['expsWithView'] ) ), ARRAY_A ); // phpcs:ignore
	$exp_segments   = json_decode( sanitize_text_field( wp_unslash( $_REQUEST['expSegments'] ) ), ARRAY_A ); // phpcs:ignore
	$unique_views   = json_decode( sanitize_text_field( wp_unslash( $_REQUEST['uniqueViews'] ) ), ARRAY_A ); // phpcs:ignore

	if ( null === $exps_with_view || null === $unique_views ) {
		return;
	}//end if

	WC()->session->set( 'nab_alternative', $alternative );
	WC()->session->set( 'nab_experiments_with_page_view', $exps_with_view );
	WC()->session->set( 'nab_segments', $exp_segments );
	WC()->session->set( 'nab_unique_views', $unique_views );
}//end sync_ecommerce_session()
add_action( 'wp_ajax_nab_sync_ecommerce_session', __NAMESPACE__ . '\sync_ecommerce_session' );
add_action( 'wp_ajax_nopriv_nab_sync_ecommerce_session', __NAMESPACE__ . '\sync_ecommerce_session' );


function process_result( $key ) {
	return function ( $result ) use ( $key ) {
		if ( null !== $result ) {
			return $result;
		}//end if

		if ( isset( $_REQUEST[ $key ] ) ) { // phpcs:ignore
			return $result;
		}//end if

		if ( ! empty( WC()->session ) && ! empty( WC()->session->get( $key, array() ) ) ) {
			return WC()->session->get( $key );
		}//end if

		return $result;
	};
}//end process_result()
add_filter( 'nab_pre_get_experiments_with_page_view_from_request', process_result( 'nab_experiments_with_page_view' ) );
add_filter( 'nab_pre_get_segments_from_request', process_result( 'nab_segments' ) );
add_filter( 'nab_pre_get_unique_views_from_request', process_result( 'nab_unique_views' ) );
