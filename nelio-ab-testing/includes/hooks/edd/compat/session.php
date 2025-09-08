<?php

namespace Nelio_AB_Testing\EasyDigitalDownloads\Compat;

defined( 'ABSPATH' ) || exit;

use function add_action;
use function add_filter;
use function EDD;


function maybe_use_session_alternative( $alternative ) {
	if ( ! nab_is_rest_api_request() && ! wp_doing_ajax() ) {
		return $alternative;
	}//end if

	if ( ! function_exists( 'EDD' ) ) {
		return $alternative;
	}//end if

	if ( empty( EDD()->session ) ) {
		return $alternative;
	}//end if

	$session_alternative = EDD()->session->get( 'nab_alternative' );
	if ( false === $session_alternative ) {
		return $alternative;
	}//end if

	return is_numeric( $session_alternative ) ? absint( $session_alternative ) : false;
}//end maybe_use_session_alternative()
add_filter( 'nab_requested_alternative', __NAMESPACE__ . '\maybe_use_session_alternative' );


function maybe_customize_main_script( $settings ) {
	$settings['ajaxUrl'] = admin_url( 'admin-ajax.php' );
	if ( function_exists( 'edd_is_checkout' ) && edd_is_checkout() ) {
		$settings['forceECommerceSessionSync'] = true;
	}//end if

	return $settings;
}//end maybe_customize_main_script()
add_filter( 'nab_main_script_settings', __NAMESPACE__ . '\maybe_customize_main_script' );

function sync_ecommerce_session() {

	if ( ! function_exists( 'EDD' ) ) {
		return;
	}//end if

	if ( empty( EDD()->session ) ) {
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
	$exps_with_view = json_decode( sanitize_text_field( wp_unslash( $_REQUEST['expsWithView'] ) ), true ); // phpcs:ignore
	$exp_segments   = json_decode( sanitize_text_field( wp_unslash( $_REQUEST['expSegments'] ) ), true ); // phpcs:ignore
	$unique_views   = json_decode( sanitize_text_field( wp_unslash( $_REQUEST['uniqueViews'] ) ), true ); // phpcs:ignore
	$ga4_client_id  = sanitize_text_field( $_REQUEST['ga4ClientId'] ); // phpcs:ignore

	if ( null === $exps_with_view || null === $unique_views ) {
		return;
	}//end if

	EDD()->session->set( 'nab_alternative', $alternative );
	EDD()->session->set( 'nab_experiments_with_page_view', $exps_with_view );
	EDD()->session->set( 'nab_segments', $exp_segments );
	EDD()->session->set( 'nab_unique_views', $unique_views );

	$plugin_settings = \Nelio_AB_Testing_Settings::instance();
	if ( $plugin_settings->get( 'integrate_ga4' ) && ! empty( $ga4_client_id ) ) {
		EDD()->session->set( 'nab_ga4_client_id', $ga4_client_id );
	}//end if
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

		return $result;
	};
}//end process_result()
add_filter( 'nab_pre_get_experiments_with_page_view_from_request', process_result( 'nab_experiments_with_page_view' ) );
add_filter( 'nab_pre_get_segments_from_request', process_result( 'nab_segments' ) );
add_filter( 'nab_pre_get_unique_views_from_request', process_result( 'nab_unique_views' ) );
add_filter( 'nab_pre_get_ga4_client_id_from_request', process_result( 'nab_ga4_client_id' ) );
