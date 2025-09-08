<?php

namespace Nelio_AB_Testing\Experiment_Library\Synced_Pattern_Experiment;

defined( 'ABSPATH' ) || exit;

use function add_action;
use function add_filter;

add_filter( 'nab_nab/synced-pattern_experiment_priority', fn() => 'custom' );
add_filter( 'nab_nab/synced-pattern_disable_query_arg_preloading', '__return_true' );

function enable_relevant_tests( $result, $parsed_block ) {
	$runtime                 = \Nelio_AB_Testing_Runtime::instance();
	$experiment_patterns_map = get_all_tested_pattern_ids();
	if ( empty( $experiment_patterns_map ) ) {
		return $result;
	}//end if

	if ( wp_is_block_theme() ) {
		global $_wp_current_template_id;
		$template = ! empty( $_wp_current_template_id ) ? get_block_template( $_wp_current_template_id, 'wp_template' ) : false;
		$template = ! empty( $template ) ? $template->content : '';
		$blocks   = parse_blocks( $template );
		$relevant = get_relevant_experiment_ids( $experiment_patterns_map, $blocks );
		foreach ( $relevant as $experiment_id ) {
			$runtime->add_custom_priority_experiment( $experiment_id );
		}//end foreach
	}//end if

	if ( is_singular() ) {
		global $post;
		$blocks   = parse_blocks( $post->post_content );
		$relevant = get_relevant_experiment_ids( $experiment_patterns_map, $blocks );
		foreach ( $relevant as $experiment_id ) {
			$runtime->add_custom_priority_experiment( $experiment_id );
		}//end foreach
	}//end if

	remove_action( 'pre_render_block', __NAMESPACE__ . '\enable_relevant_tests', 10 );
	add_filter( 'pre_render_block', fn( $r, $pb ) => apply_filters( '_nab_pre_render_block', $r, $pb ), 10, 2 );
	return apply_filters( '_nab_pre_render_block', $result, $parsed_block );
}//end enable_relevant_tests()
add_action( 'pre_render_block', __NAMESPACE__ . '\enable_relevant_tests', 10, 2 );

function enable_relevant_tests_on_legacy_themes() {
	$experiment_patterns_map = get_all_tested_pattern_ids();
	if ( empty( $experiment_patterns_map ) ) {
		return;
	}//end if

	if ( ! is_singular() ) {
		return;
	}//end if

	ob_start();
	do_blocks( get_the_content() );
	ob_end_clean();
}//end enable_relevant_tests_on_legacy_themes()
add_action( 'wp', __NAMESPACE__ . '\enable_relevant_tests_on_legacy_themes' );

function load_alternative( $alternative, $_, $experiment_id ) {
	$tested_pattern_ids = get_tested_pattern_ids( $experiment_id );
	add_filter(
		'_nab_pre_render_block',
		function ( $result, $parsed_block ) use ( &$alternative, &$tested_pattern_ids ) {
			$name = nab_array_get( $parsed_block, 'blockName' );
			if ( 'core/block' !== $name ) {
				return $result;
			}//end if

			$pattern_id = absint( nab_array_get( $parsed_block, 'attrs.ref', 0 ) );
			if ( ! in_array( $pattern_id, $tested_pattern_ids, true ) ) {
				return $result;
			}//end if

			if ( $pattern_id === $alternative['patternId'] ) {
				return $result;
			}//end if

			$alternative_pattern = get_post( $alternative['patternId'] );
			if ( empty( $alternative_pattern ) ) {
				return '';
			}//end if

			$parsed_block['attrs']['ref'] = $alternative['patternId'];
			return render_block_core_block( $parsed_block['attrs'] );
		},
		10,
		2
	);
}//end load_alternative()
add_action( 'nab_nab/synced-pattern_load_alternative', __NAMESPACE__ . '\load_alternative', 10, 3 );

// =======
// HELPERS
// =======

function get_all_tested_pattern_ids() {
	$experiments    = nab_get_running_experiments();
	$experiments    = array_filter( $experiments, fn( $e ) => 'nab/synced-pattern' === $e->get_type() );
	$experiments    = array_values( $experiments );
	$experiment_ids = wp_list_pluck( $experiments, 'ID' );
	$pattern_ids    = array_map( __NAMESPACE__ . '\get_tested_pattern_ids', $experiments );
	return array_combine( $experiment_ids, $pattern_ids );
}//end get_all_tested_pattern_ids()

function get_tested_pattern_ids( $experiment_id ) {
	$experiment = nab_get_experiment( $experiment_id );
	if ( is_wp_error( $experiment ) ) {
		return array();
	}//end if

	/** . @var \Nelio_AB_Testing_Experiment $experiment */
	$experiment = $experiment;
	return array_map(
		fn( $a ) => absint( nab_array_get( $a, 'attributes.patternId', 0 ) ),
		$experiment->get_alternatives()
	);
}//end get_tested_pattern_ids()

function get_relevant_experiment_ids( $experiment_patterns_map, $blocks ) {
	$blocks             = array_filter( $blocks, fn( $b ) => 'core/block' === nab_array_get( $b, 'blockName' ) );
	$patterns_in_blocks = array_map( fn( $b ) => absint( nab_array_get( $b, 'attrs.ref' ) ), $blocks );
	$patterns_in_blocks = array_values( array_filter( $patterns_in_blocks ) );

	$result = array();
	foreach ( $experiment_patterns_map as $experiment_id => $expected_patterns ) {
		if ( array_intersect( $expected_patterns, $patterns_in_blocks ) ) {
			$result[] = $experiment_id;
		}//end if
	}//end foreach
	return $result;
}//end get_relevant_experiment_ids()
