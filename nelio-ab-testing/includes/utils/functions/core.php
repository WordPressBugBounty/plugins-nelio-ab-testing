<?php
/**
 * Nelio A/B Testing core functions.
 *
 * @package    Nelio_AB_Testing
 * @subpackage Nelio_AB_Testing/includes/utils/functions
 * @since      5.0.0
 */

/**
 * Returns this site's ID.
 *
 * @return string This site's ID. This option is used for accessing AWS.
 *
 * @since 5.0.0
 */
function nab_get_site_id() {

	return get_option( 'nab_site_id', false );
}//end nab_get_site_id()


/**
 * Returns whether the current request is a test preview render or not.
 *
 * @return boolean whether the current request is a test preview render or not.
 *
 * @since 5.0.16
 */
function nab_is_preview() {

	if ( ! isset( $_GET['nab-preview'] ) ) { // phpcs:ignore
		return false;
	}//end if

	$exp_id  = isset( $_GET['experiment'] ) ? absint( $_GET['experiment'] ) : 0; // phpcs:ignore
	$alt_idx = isset( $_GET['alternative'] ) ? sanitize_text_field( $_GET['alternative'] ) : ''; // phpcs:ignore

	if ( empty( $exp_id ) || ! is_numeric( $alt_idx ) ) {
		return false;
	}//end if

	return true;
}//end nab_is_preview()


/**
 * Returns whether the current request is a public result render or not.
 *
 * @return boolean whether the current request is a public result render or not.
 *
 * @since 7.2
 */
function nab_is_public_result_view() {

	if ( ! isset( $_GET['preview'] ) ) { // phpcs:ignore
		return false;
	}//end if

	if ( ! isset( $_GET['nab-result'] ) ) { // phpcs:ignore
		return false;
	}//end if

	$exp_id = isset( $_GET['experiment'] ) ? absint( $_GET['experiment'] ) : 0; // phpcs:ignore
	if ( empty( $exp_id ) ) {
		return false;
	}//end if

	if ( ! nab_is_experiment_result_public( $exp_id ) ) {
		wp_die( esc_html_x( 'No public result view available.', 'text', 'nelio-ab-testing' ), 404 );
	}//end if

	return true;
}//end nab_is_public_result_view()


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
		isset( $_GET['nab-heatmap-renderer'] ) // phpcs:ignore
	);
}//end nab_is_heatmap()

/**
 * Returns the maximum number of different values the cookie `nabAlternative` can take.
 *
 * @return int the maximum number of different values the cookie `nabAlternative` can take.
 *
 * @since 7.0.0
 */
function nab_max_combinations() {
	// NOTE. Should this filterable at some point?
	/**
	 * Filters the maximum number of different values the cookie `nabAlternative` can take.
	 *
	 * @param int $value the maximum number of different values the cookie `nabAlternative` can take. Default: `24`
	 *
	 * @since 7.0.0
	 */
	$value = apply_filters( 'nab_max_combinations', 24 );
	return max( 2, $value );
}//end nab_max_combinations()

/**
 * Returns the active alternative for the given experiment.
 * If no experiment is given or the experiment is not active or no alternative has been requested, it returns `false`.
 *
 * @param number|0 $experiment_id The ID of the experiment.
 *
 * @return number|false The active alternative.
 *
 * @since 7.4.0
 */
function nab_get_requested_alternative( $experiment_id = 0 ) {
	if ( nab_is_preview() ) {
		$eid = absint( $_GET['experiment'] ); // phpcs:ignore
		$aid = absint( $_GET['alternative'] ); // phpcs:ignore
		return empty( $experiment_id ) || $experiment_id === $eid ? $aid : false;
	}//end if

	$experiments = nab_get_running_experiment_ids();
	if ( empty( $experiments ) ) {
		return false;
	}//end if

	$runtime     = Nelio_AB_Testing_Runtime::instance();
	$alternative = $runtime->get_alternative_from_request();
	if ( empty( $experiment_id ) ) {
		return $alternative;
	}//end if

	$experiment = nab_get_experiment( $experiment_id );
	if ( is_wp_error( $experiment ) ) {
		return false;
	}//end if

	return $alternative % count( $experiment->get_alternatives() );
}//end nab_get_requested_alternative()
