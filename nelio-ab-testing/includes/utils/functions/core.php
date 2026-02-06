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
	$exp_id = absint( $_GET['experiment'] ?? 0 );
	// phpcs:ignore WordPress.Security.NonceVerification.Recommended
	$alt_idx = sanitize_text_field( wp_unslash( $_GET['alternative'] ?? '' ) );

	if ( empty( $exp_id ) || ! is_numeric( $alt_idx ) ) {
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

	if ( ! nab_is_experiment_result_public( $exp_id ) ) {
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

/**
 * Returns the active alternative for the given experiment.
 * If no experiment is given or the experiment is not active or no alternative has been requested, it returns `false`.
 *
 * @param int $experiment_id The ID of the experiment.
 *
 * @return int The active alternative.
 *
 * @since 7.4.0
 */
function nab_get_requested_alternative( $experiment_id = 0 ) {
	if ( nab_is_preview() ) {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$eid = absint( $_GET['experiment'] ?? 0 );
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$aid = absint( $_GET['alternative'] ?? 0 );
		return empty( $experiment_id ) || $experiment_id === $eid ? $aid : 0;
	}

	$experiments = nab_get_running_experiment_ids();
	if ( empty( $experiments ) ) {
		return 0;
	}

	$runtime     = Nelio_AB_Testing_Runtime::instance();
	$alternative = $runtime->get_alternative_from_request();
	if ( empty( $experiment_id ) ) {
		return $alternative;
	}

	$experiment = nab_get_experiment( $experiment_id );
	if ( is_wp_error( $experiment ) ) {
		return $alternative;
	}

	return $alternative % count( $experiment->get_alternatives() );
}
