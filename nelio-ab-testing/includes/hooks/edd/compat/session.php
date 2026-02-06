<?php

namespace Nelio_AB_Testing\EasyDigitalDownloads\Compat;

defined( 'ABSPATH' ) || exit;

use function add_action;
use function add_filter;
use function EDD;

/**
 * Callback to filter the alternative defined in EDD’s session (if any).
 *
 * @param int|false $alternative Requested alternative.
 *
 * @return int|false
 */
function maybe_use_session_alternative( $alternative ) {
	if ( ! nab_is_rest_api_request() && ! wp_doing_ajax() ) {
		return $alternative;
	}

	if ( ! function_exists( 'EDD' ) ) {
		return $alternative;
	}

	if ( empty( EDD()->session ) ) {
		return $alternative;
	}

	$session_alternative = EDD()->session->get( 'nab_alternative' );
	if ( false === $session_alternative ) {
		return $alternative;
	}

	return is_numeric( $session_alternative ) ? absint( $session_alternative ) : false;
}
add_filter( 'nab_requested_alternative', __NAMESPACE__ . '\maybe_use_session_alternative' );

/**
 * Callback to force e-commerce session sync if EDD is active.
 *
 * @param TPublic_Settings $settings Settings.
 *
 * @return TPublic_Settings
 */
function maybe_customize_main_script( $settings ) {
	$settings['ajaxUrl'] = admin_url( 'admin-ajax.php' );
	if ( function_exists( 'edd_is_checkout' ) && edd_is_checkout() ) {
		$settings['forceECommerceSessionSync'] = true;
	}

	return $settings;
}
add_filter( 'nab_main_script_settings', __NAMESPACE__ . '\maybe_customize_main_script' );

/**
 * Callback to sync testing info with EDD’s session.
 *
 * @return void
 */
function sync_ecommerce_session() {

	if ( ! function_exists( 'EDD' ) ) {
		return;
	}

	if ( empty( EDD()->session ) ) {
		return;
	}

	if (
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		! isset( $_REQUEST['alternative'] ) ||
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		! isset( $_REQUEST['expsWithView'] ) ||
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		! isset( $_REQUEST['expSegments'] ) ||
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		! isset( $_REQUEST['uniqueViews'] )
	) {
		return;
	}

	// phpcs:ignore WordPress.Security.NonceVerification.Recommended
	if ( 'none' === $_REQUEST['alternative'] ) {
		return;
	}

	// phpcs:ignore WordPress.Security.NonceVerification.Recommended
	$alternative = absint( $_REQUEST['alternative'] );
	// phpcs:ignore WordPress.Security.NonceVerification.Recommended
	$exps_with_view = json_decode( sanitize_text_field( wp_unslash( $_REQUEST['expsWithView'] ) ), true );
	// phpcs:ignore WordPress.Security.NonceVerification.Recommended
	$exp_segments = json_decode( sanitize_text_field( wp_unslash( $_REQUEST['expSegments'] ) ), true );
	// phpcs:ignore WordPress.Security.NonceVerification.Recommended
	$unique_views = json_decode( sanitize_text_field( wp_unslash( $_REQUEST['uniqueViews'] ) ), true );
	// phpcs:ignore WordPress.Security.NonceVerification.Recommended
	$ga4_client_id = sanitize_text_field( wp_unslash( $_REQUEST['ga4ClientId'] ?? '' ) );

	if ( null === $exps_with_view || null === $unique_views ) {
		return;
	}

	EDD()->session->set( 'nab_alternative', $alternative );
	EDD()->session->set( 'nab_experiments_with_page_view', $exps_with_view );
	EDD()->session->set( 'nab_segments', $exp_segments );
	EDD()->session->set( 'nab_unique_views', $unique_views );

	if ( ! empty( $ga4_client_id ) ) {
		EDD()->session->set( 'nab_ga4_client_id', $ga4_client_id );
	}
}
add_action( 'wp_ajax_nab_sync_ecommerce_session', __NAMESPACE__ . '\sync_ecommerce_session' );
add_action( 'wp_ajax_nopriv_nab_sync_ecommerce_session', __NAMESPACE__ . '\sync_ecommerce_session' );
