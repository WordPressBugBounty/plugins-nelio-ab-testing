<?php

namespace Nelio_AB_Testing\Compat\Yith_Woocommerce_Advanced_Reviews_Premium;

defined( 'ABSPATH' ) || exit;

use function add_filter;
use function add_action;
use function is_plugin_active;

function maybe_load_control_reviews() {
	if ( 'yith_ywar_frontend_ajax_action' !== nab_array_get( $_POST, 'action' ) ) { // phpcs:ignore
		return;
	}//end if

	$referrer = nab_array_get( $_SERVER, 'HTTP_REFERER' );
	$referrer = is_string( $referrer ) ? $referrer : '';
	$query    = nab_array_get( wp_parse_url( $referrer ), 'query' );
	$query    = is_string( $query ) ? $query : '';
	$query    = wp_parse_args( $query );
	if ( is_previewing_alternative( $query ) ) {
		$experiment_id = absint( nab_array_get( $query, 'experiment' ) );
		$experiment    = nab_get_experiment( $experiment_id );
		if ( ! is_wp_error( $experiment ) && 'nab/wc-product' === $experiment->get_type() ) {
			$control_id          = nab_array_get( $experiment->get_alternative( 'control' ), 'attributes.postId' );
			$_POST['product_id'] = "{$control_id}";
		}//end if
		return;
	}//end if

	if ( 'none' === nab_array_get( $_COOKIE, 'nabAlternative', 'none' ) ) { // phpcs:ignore
		return;
	}//end if

	$experiments = nab_get_running_experiments();
	$experiments = array_filter(
		$experiments,
		function ( $e ) {
			if ( 'nab/wc-product' !== $e->get_type() ) {
				return false;
			}//end if
			$cookie = absint( nab_array_get( $_COOKIE, 'nabAlternative', 'none' ) ); // phpcs:ignore
			$alts   = $e->get_alternatives();
			$alt    = $alts[ $cookie % count( $alts ) ];
			return absint( nab_array_get( $alt, 'attributes.postId' ) ) === absint( nab_array_get( $_POST, 'product_id' ) ); // phpcs:ignore
		}
	);
	$experiments = array_values( $experiments );
	if ( empty( $experiments ) ) {
		return;
	}//end if

	$experiment          = $experiments[0];
	$control_id          = nab_array_get( $experiment->get_alternative( 'control' ), 'attributes.postId' );
	$_POST['product_id'] = "{$control_id}";
}//end maybe_load_control_reviews()

/**
 * Nothing.
 *
 * @param \Nelio_AB_Testing\WooCommerce\Experiment_Library\Product_Experiment\IRunning_Alternative_Product $alt_product .
 */
function load_control_stats( $alt_product ) {
	add_filter(
		'woocommerce_product_get__ywar_stats',
		function ( $value, $product ) use ( $alt_product ) {
			if ( $product->get_id() !== $alt_product->get_id() ) {
				return $value;
			}//end if

			$control = $alt_product->get_control();
			return $control->get_meta( '_ywar_stats' );
		},
		10,
		2
	);
}//end load_control_stats()

function is_previewing_alternative( array $args ) {
	if ( ! isset( $args['nab-preview'] ) ) {
		return;
	}//end if

	$experiment_id = nab_array_get( $args, 'experiment' );
	$alt_idx       = nab_array_get( $args, 'alternative' );
	$timestamp     = nab_array_get( $args, 'timestamp' );
	$nonce         = nab_array_get( $args, 'nabnonce' );
	$secret        = nab_get_api_secret();

	return md5( "nab-preview-{$experiment_id}-{$alt_idx}-{$timestamp}-{$secret}" ) === $nonce;
}//end is_previewing_alternative()

add_action(
	'plugins_loaded',
	function () {
		if ( ! function_exists( 'is_plugin_active' ) ) {
			include_once ABSPATH . 'wp-admin/includes/plugin.php';
		}//end if

		if ( ! is_plugin_active( 'yith-woocommerce-advanced-reviews-premium/init.php' ) ) {
			return;
		}//end if

		add_action( 'init', __NAMESPACE__ . '\maybe_load_control_reviews' );
		add_action( 'nab_load_proper_alternative_woocommerce_product', __NAMESPACE__ . '\load_control_stats' );
	}
);
