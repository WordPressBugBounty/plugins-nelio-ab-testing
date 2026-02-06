<?php

namespace Nelio_AB_Testing\WooCommerce\Compat\Yith_Woocommerce_Advanced_Reviews_Premium;

defined( 'ABSPATH' ) || exit;

use function add_filter;
use function add_action;
use function is_plugin_active;

/**
 * Callback to load control reviews.
 *
 * @return void
 */
function maybe_load_control_reviews() {
	// Frontend actions don't require nonce check.
	// phpcs:ignore WordPress.Security.NonceVerification.Missing
	$action = sanitize_text_field( wp_unslash( $_POST['action'] ?? '' ) );
	if ( 'yith_ywar_frontend_ajax_action' !== $action ) {
		return;
	}

	$referrer = sanitize_url( is_string( $_SERVER['HTTP_REFERER'] ?? '' ) ? wp_unslash( $_SERVER['HTTP_REFERER'] ?? '' ) : '' );
	$query    = wp_parse_url( $referrer, PHP_URL_QUERY );
	$query    = is_string( $query ) ? $query : '';
	$query    = wp_parse_args( $query );
	if ( is_previewing_alternative( $query ) ) {
		$experiment_id = absint( $query['experiment'] ?? '' );
		$experiment    = nab_get_experiment( $experiment_id );
		if ( ! is_wp_error( $experiment ) && 'nab/wc-product' === $experiment->get_type() ) {
			$control    = $experiment->get_alternative( 'control' );
			$control_id = absint( $control['attributes']['postId'] );
			if ( ! empty( $control_id ) ) {
				$_POST['product_id'] = "{$control_id}";
			}
		}
		return;
	}

	$cookie = sanitize_text_field( wp_unslash( $_COOKIE['nabAlternative'] ?? 'none' ) );
	if ( 'none' === $cookie ) {
		return;
	}

	$cookie      = absint( $cookie );
	$experiments = nab_get_running_experiments();
	$experiments = array_filter(
		$experiments,
		function ( $e ) use ( $cookie ) {
			if ( 'nab/wc-product' !== $e->get_type() ) {
				return false;
			}
			$alts = $e->get_alternatives();
			$alt  = $alts[ $cookie % count( $alts ) ];
			// Frontend actions don't require nonce check.
			// phpcs:ignore WordPress.Security.NonceVerification.Missing
			return absint( $alt['attributes']['postId'] ) === absint( $_POST['product_id'] ?? 0 );
		}
	);
	$experiments = array_values( $experiments );
	if ( empty( $experiments ) ) {
		return;
	}

	$experiment = $experiments[0];
	$control    = $experiment->get_alternative( 'control' );
	$control_id = absint( $control['attributes']['postId'] );
	if ( ! empty( $control_id ) ) {
		$_POST['product_id'] = "{$control_id}";
	}
}

/**
 * Adds hook to load control stats to alternative products.
 *
 * @param \Nelio_AB_Testing\WooCommerce\Experiment_Library\Product_Experiment\IRunning_Alternative_Product $alt_product .
 *
 * @return void
 */
function load_control_stats( $alt_product ) {
	add_filter(
		'woocommerce_product_get__ywar_stats',
		function ( $value, $product ) use ( $alt_product ) {
			/** @var mixed       $value   */
			/** @var \WC_Product $product */

			if ( $product->get_id() !== $alt_product->get_id() ) {
				return $value;
			}

			$control = $alt_product->get_control();
			if ( empty( $control ) ) {
				return $value;
			}

			return $control->get_meta( '_ywar_stats' );
		},
		10,
		2
	);
}

/**
 * Whether we’re previewing an alternative or not.
 *
 * @param array<mixed> $args Args.
 *
 * @return bool
 */
function is_previewing_alternative( $args ) {
	if ( ! isset( $args['nab-preview'] ) ) {
		return false;
	}

	$experiment_id = absint( $args['experiment'] ?? 0 );
	$alt_idx       = absint( $args['alternative'] ?? 0 );
	$timestamp     = $args['timestamp'] ?? '';
	$timestamp     = is_string( $timestamp ) ? $timestamp : '';
	$nonce         = $args['nabnonce'] ?? '';
	$nonce         = is_string( $nonce ) ? $nonce : '';
	$secret        = nab_get_api_secret();

	return md5( "nab-preview-{$experiment_id}-{$alt_idx}-{$timestamp}-{$secret}" ) === $nonce;
}

add_action(
	'plugins_loaded',
	function () {
		if ( ! function_exists( 'is_plugin_active' ) ) {
			nab_require_wp_file( '/wp-admin/includes/plugin.php' );
		}

		if ( ! is_plugin_active( 'yith-woocommerce-advanced-reviews-premium/init.php' ) ) {
			return;
		}

		add_action( 'init', __NAMESPACE__ . '\maybe_load_control_reviews' );
		add_action( 'nab_load_proper_alternative_woocommerce_product', __NAMESPACE__ . '\load_control_stats' );
	}
);
