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
		return; // @codeCoverageIgnore
	}

	if ( ! $experiment->is_auto_alternative_application_enabled() ) {
		return;
	}

	$results = \Nelio_AB_Testing_Experiment_Results::get_experiment_results( $experiment );
	if ( is_wp_error( $results ) ) {
		return; // @codeCoverageIgnore
	}

	$results = $results->results;
	$winner  = get_alternative_to_apply( $experiment, $results );
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
 * @param TResults                     $results    Processed experiment results.
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
 * @param TResults $results    Processed experiment results.
 *
 * @return int
 */
function get_page_views( $results ) {
	return array_sum( wp_list_pluck( $results['alternatives'], 'visits' ) );
}

/**
 * Gets the list of clear winners.
 *
 * @param TResults $results    Processed experiment results.
 * @param int      $min_confidence Minimum confidence required to call a winner.
 *
 * @return list<int>
 */
function get_clear_winners( $results, $min_confidence ) {
	$winners = array_merge(
		array_values( $results['winners'] ),
		array_values( $results['winnersUnique'] ?? array() )
	);
	$winners = array_filter(
		$winners,
		fn( $winner ) => $min_confidence <= $winner['confidence']
	);
	/** @var list<int> */
	return wp_list_pluck( $winners, 'alternative' );
}
