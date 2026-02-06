<?php

namespace Nelio_AB_Testing\WooCommerce\Compat;

defined( 'ABSPATH' ) || exit;

use function add_action;
use function add_filter;
use function WC;

/**
 * Callback to use session alternative if possible.
 *
 * @param number|false $alternative Alternative.
 *
 * @return number|false
 */
function maybe_use_session_alternative( $alternative ) {
	if ( ! nab_is_rest_api_request() && ! wp_doing_ajax() ) {
		return $alternative;
	}

	if ( ! function_exists( 'WC' ) ) {
		return $alternative;
	}

	if ( empty( WC()->session ) ) {
		return $alternative;
	}

	if ( ! WC()->session->has_session() ) {
		return $alternative;
	}

	$session_alternative = WC()->session->get( 'nab_alternative', false );
	// @phpstan-ignore-next-line identical.alwaysFalse
	if ( false === $session_alternative ) {
		return $alternative;
	}

	return is_numeric( $session_alternative ) ? absint( $session_alternative ) : false;
}
add_filter( 'nab_requested_alternative', __NAMESPACE__ . '\maybe_use_session_alternative' );

/**
 * Callback to customize main script and set `forceECommerceSessionSync` to `true` if needed.
 *
 * @param TPublic_Settings $settings Settings.
 *
 * @return TPublic_Settings
 */
function maybe_customize_main_script( $settings ) {
	$settings['ajaxUrl'] = admin_url( 'admin-ajax.php' );
	if ( function_exists( 'is_checkout' ) && is_checkout() ) {
		$settings['forceECommerceSessionSync'] = true;
	}

	if ( function_exists( 'is_cart' ) && is_cart() ) {
		$settings['forceECommerceSessionSync'] = true;
	}

	return $settings;
}
add_filter( 'nab_main_script_settings', __NAMESPACE__ . '\maybe_customize_main_script' );

/**
 * Callback to sync ecommerce session.
 *
 * @return void
 */
function sync_ecommerce_session() {

	if ( ! function_exists( 'WC' ) ) {
		return;
	}

	if ( empty( WC()->session ) ) {
		return;
	}

	if ( ! WC()->session->has_session() ) {
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

	WC()->session->set( 'nab_alternative', $alternative );
	WC()->session->set( 'nab_experiments_with_page_view', $exps_with_view );
	WC()->session->set( 'nab_segments', $exp_segments );
	WC()->session->set( 'nab_unique_views', $unique_views );

	if ( ! empty( $ga4_client_id ) ) {
		WC()->session->set( 'nab_ga4_client_id', $ga4_client_id );
	}
}
add_action( 'wp_ajax_nab_sync_ecommerce_session', __NAMESPACE__ . '\sync_ecommerce_session' );
add_action( 'wp_ajax_nopriv_nab_sync_ecommerce_session', __NAMESPACE__ . '\sync_ecommerce_session' );

/**
 * Helper function to get a callback that processes the key.
 *
 * @param string $key Key.
 *
 * @return \Closure(mixed):mixed
 */
function process_result( $key ) {
	return function ( $result ) use ( $key ) {
		if ( null !== $result ) {
			return $result;
		}

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		if ( isset( $_REQUEST[ $key ] ) ) {
			return $result;
		}

		if ( ! empty( WC()->session ) && ! empty( WC()->session->get( $key, array() ) ) ) {
			return WC()->session->get( $key );
		}

		return $result;
	};
}
add_filter( 'nab_pre_get_experiments_with_page_view_from_request', process_result( 'nab_experiments_with_page_view' ) );
add_filter( 'nab_pre_get_segments_from_request', process_result( 'nab_segments' ) );
add_filter( 'nab_pre_get_unique_views_from_request', process_result( 'nab_unique_views' ) );
add_filter( 'nab_pre_get_ga4_client_id_from_request', process_result( 'nab_ga4_client_id' ) );
