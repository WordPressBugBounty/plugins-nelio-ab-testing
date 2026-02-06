<?php
namespace Nelio_AB_Testing\Hooks\Auto_Alternative_Application;

defined( 'ABSPATH' ) || exit;

/**
 * Callback to apply winning variant (if possible) when experiment stops.
 *
 * @param \Nelio_AB_Testing_Experiment $experiment Experiment.
 *
 * @return void
 */
function maybe_apply_winning_alternative( $experiment ) {
	if ( 'nab/heatmap' === $experiment->get_type() ) {
		return;
	}

	if ( ! $experiment->is_auto_alternative_application_enabled() ) {
		return;
	}

	$results = \Nelio_AB_Testing_Experiment_Results::get_experiment_results( $experiment );
	if ( is_wp_error( $results ) ) {
		return;
	}

	$results = $results->results;
	if ( is_null( $results ) ) {
		return;
	}

	$winner = get_alternative_to_apply( $experiment, $results );
	if ( 'control' === $winner ) {
		return;
	}

	$experiment->apply_alternative( $winner );
}
add_action( 'nab_stop_experiment', __NAMESPACE__ . '\maybe_apply_winning_alternative' );

/**
 * Returns the ID of the winning alternative.
 *
 * @param \Nelio_AB_Testing_Experiment $experiment Experiment.
 * @param TAWS_Experiment_Results      $results    Experiment results.
 *
 * @return string
 */
function get_alternative_to_apply( $experiment, $results ) {
	$settings = \Nelio_AB_Testing_Settings::instance();

	$min_sample_size = $settings->get( 'min_sample_size' );
	$page_views      = get_page_views( $results );
	if ( $page_views < $min_sample_size ) {
		return 'control';
	}

	$min_confidence = $settings->get( 'min_confidence' );
	$winners        = get_clear_winners( $results, $min_confidence );
	if ( empty( $winners ) ) {
		return 'control';
	}

	$winner = $winners[0];
	foreach ( $winners as $w ) {
		if ( $winner !== $w ) {
			return 'control';
		}
	}

	/** @var list<string> */
	$alternative_ids = wp_list_pluck( $experiment->get_alternatives(), 'id' );
	return isset( $alternative_ids[ $winner ] ) ? $alternative_ids[ $winner ] : 'control';
}

/**
 * Gets the total number of page views.
 *
 * @param TAWS_Experiment_Results $results    Experiment results.
 *
 * @return int
 */
function get_page_views( $results ) {
	/** @var array<string,mixed> */
	$ax_keys = $results;
	$ax_keys = array_keys( $ax_keys );
	$ax_keys = array_filter( $ax_keys, fn( $k ) => 1 === preg_match( '/^a[0-9]+$/', $k ) );
	$ax_keys = array_values( $ax_keys );

	$views = array_map( fn( $k ) => $results[ $k ]['v'] ?? 0, $ax_keys );
	return array_sum( $views );
}

/**
 * Gets the list of clear winners.
 *
 * @param TAWS_Experiment_Results $results    Experiment results.
 * @param int                     $min_confidence Minimum confidence required to call a winner.
 *
 * @return list<int>
 */
function get_clear_winners( $results, $min_confidence ) {
	$r  = $results['results'] ?? array();
	$ur = $results['uniqueResults'] ?? array();


	$winners = array_merge( $r, $ur );
	$winners = array_filter( $winners, fn( $w ) => 'win' === $w['status'] );
	$winners = array_filter( $winners, fn( $w ) => $min_confidence <= $w['confidence'] );
	$winners = array_values( $winners );
	/** @var list<int> */
	return wp_list_pluck( $winners, 'winner' );
}
