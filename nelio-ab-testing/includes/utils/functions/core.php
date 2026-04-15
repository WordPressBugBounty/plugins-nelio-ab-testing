<?php
/**
 * Nelio A/B Testing core functions.
 *
 * @package    Nelio_AB_Testing
 * @subpackage Nelio_AB_Testing/includes/utils/functions
 * @since      5.0.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * Returns this site's ID.
 *
 * @return string This site's ID. This option is used for accessing AWS.
 *
 * @since 5.0.0
 */
function nab_get_site_id() {
	return get_option( 'nab_site_id', '' );
}

/**
 * Returns whether the current request is a test preview render or not.
 *
 * @return boolean whether the current request is a test preview render or not.
 *
 * @since 5.0.16
 */
function nab_is_preview() {

	// phpcs:ignore WordPress.Security.NonceVerification.Recommended
	if ( ! isset( $_GET['nab-preview'] ) ) {
		return false;
	}

	// phpcs:ignore WordPress.Security.NonceVerification.Recommended
	$experiment_id = absint( $_GET['experiment'] ?? 0 );
	// phpcs:ignore WordPress.Security.NonceVerification.Recommended
	$alt_idx = isset( $_GET['alternative'] ) ? absint( $_GET['alternative'] ) : null;
	// phpcs:ignore WordPress.Security.NonceVerification.Recommended
	$timestamp = isset( $_GET['timestamp'] ) ? absint( $_GET['timestamp'] ) : null;
	// phpcs:ignore WordPress.Security.NonceVerification.Recommended
	$nonce = sanitize_text_field( wp_unslash( $_GET['nabnonce'] ?? '' ) );

	if ( empty( $experiment_id ) || is_null( $alt_idx ) || is_null( $timestamp ) || empty( $nonce ) ) {
		return false;
	}

	$secret = nab_get_api_secret();
	if ( md5( "nab-preview-{$experiment_id}-{$alt_idx}-{$timestamp}-{$secret}" ) !== $nonce ) {
		return false;
	}

	$experiment = nab_get_experiment( $experiment_id );
	if ( is_wp_error( $experiment ) ) {
		return false;
	}

	$alternative = $experiment->get_alternatives()[ $alt_idx ] ?? null;
	if ( $experiment->get_type() !== 'nab/heatmap' && is_null( $alternative ) ) {
		return false;
	}

	/**
		* Filters the alternative preview duration in minutes. If set to 0, the preview link never expires.
		*
		* @param number $duration Duration in minutes. If 0, the preview link never expires. Default: 30.
		*
		* @since 5.1.2
		*/
	$duration = absint( apply_filters( 'nab_alternative_preview_link_duration', 30 ) );
	if ( ! empty( $duration ) && 60 * $duration < absint( time() - $timestamp ) ) {
		return false;
	}

	return true;
}

/**
 * Returns whether the current request is a public result render or not.
 *
 * @return boolean whether the current request is a public result render or not.
 *
 * @since 7.2
 */
function nab_is_public_result_view() {

	// phpcs:ignore WordPress.Security.NonceVerification.Recommended
	if ( ! isset( $_GET['preview'] ) ) {
		return false;
	}

	// phpcs:ignore WordPress.Security.NonceVerification.Recommended
	if ( ! isset( $_GET['nab-result'] ) ) {
		return false;
	}

	// phpcs:ignore WordPress.Security.NonceVerification.Recommended
	$exp_id = isset( $_GET['experiment'] ) ? absint( $_GET['experiment'] ) : 0;
	if ( empty( $exp_id ) ) {
		return false;
	}

	$experiment = nab_get_experiment( $exp_id );
	if ( is_wp_error( $experiment ) ) {
		wp_die( esc_html( $experiment->get_error_message() ) );
	}

	if ( ! $experiment->has_public_results() ) {
		wp_die( esc_html_x( 'No public result view available.', 'text', 'nelio-ab-testing' ), 404 );
	}

	return true;
}

/**
 * Returns whether the current request is a heatmap render or not.
 *
 * @return boolean whether the current request is a heatmap render or not.
 *
 * @since 5.0.16
 */
function nab_is_heatmap() {
	return (
		nab_is_preview() &&
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		isset( $_GET['nab-heatmap-renderer'] )
	);
}

/**
 * Returns the maximum number of different values the cookie `nabAlternative` can take.
 *
 * @return int the maximum number of different values the cookie `nabAlternative` can take.
 *
 * @since 7.0.0
 */
function nab_max_combinations() {
	/**
	 * Filters the maximum number of different values the cookie `nabAlternative` can take.
	 *
	 * @param int $value the maximum number of different values the cookie `nabAlternative` can take. Default: `24`
	 *
	 * @since 7.0.0
	 */
	$value = apply_filters( 'nab_max_combinations', 24 );
	return max( 2, $value );
}
