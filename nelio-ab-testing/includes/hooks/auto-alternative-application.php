<?php
namespace Nelio_AB_Testing\Hooks\Auto_Alternative_Application;

defined( 'ABSPATH' ) || exit;

function maybe_apply_winning_alternative( $experiment ) {
	/**
	 * .
	 *
	 * @var \Nelio_AB_Testing_Experiment $experiment
	 */
	if ( 'nab/heatmap' === $experiment->get_type() ) {
		return;
	}//end if

	if ( ! $experiment->is_auto_alternative_application_enabled() ) {
		return;
	}//end if

	$results = \Nelio_AB_Testing_Experiment_Results::get_experiment_results( $experiment );
	if ( is_wp_error( $results ) ) {
		return;
	}//end if

	$results = $results->results;
	$winner  = get_alternative_to_apply( $experiment, $results );
	if ( 'control' === $winner ) {
		return;
	}//end if

	$experiment->apply_alternative( $winner );
}//end maybe_apply_winning_alternative()
add_action( 'nab_stop_experiment', __NAMESPACE__ . '\maybe_apply_winning_alternative' );

function get_alternative_to_apply( $experiment, $results ) {
	/**
	 * .
	 *
	 * @var \Nelio_AB_Testing_Experiment $experiment
	 */
	$settings = \Nelio_AB_Testing_Settings::instance();

	$min_sample_size = $settings->get( 'min_sample_size' );
	$page_views      = get_page_views( $results );
	if ( $page_views < $min_sample_size ) {
		return 'control';
	}//end if

	$min_confidence = $settings->get( 'min_confidence' );
	$winners        = get_clear_winners( $results, $min_confidence );
	if ( empty( $winners ) ) {
		return 'control';
	}//end if

	$winner = $winners[0];
	foreach ( $winners as $w ) {
		if ( $winner !== $w ) {
			return 'control';
		}//end if
	}//end foreach

	$alternative_ids = wp_list_pluck( $experiment->get_alternatives(), 'id' );
	return isset( $alternative_ids[ $winner ] ) ? $alternative_ids[ $winner ] : 'control';
}//end get_alternative_to_apply()

function get_page_views( $results ) {
	$ax_keys = array_keys( $results );
	$ax_keys = array_filter( $ax_keys, fn( $k ) => 1 === preg_match( '/^a[0-9]+$/', $k ) );
	$ax_keys = array_values( $ax_keys );

	$views = array_map( fn( $k ) => nab_array_get( $results, "{$k}.v", 0 ), $ax_keys );
	return array_sum( $views );
}//end get_page_views()

function get_clear_winners( $results, $min_confidence ) {
	$r  = nab_array_get( $results, 'results', array() );
	$r  = is_array( $r ) ? $r : array();
	$r  = array_values( $r );
	$ur = nab_array_get( $results, 'uniqueResults', array() );
	$ur = is_array( $ur ) ? $ur : array();
	$ur = array_values( $ur );


	$winners = array_merge( $r, $ur );
	$winners = array_filter( $winners, fn( $w ) => 'win' === nab_array_get( $w, 'status', 'tie' ) );
	$winners = array_filter( $winners, fn( $w ) => $min_confidence <= nab_array_get( $w, 'confidence', 0 ) );
	$winners = wp_list_pluck( $winners, 'winner' );
	return array_values( $winners );
}//end get_clear_winners()
