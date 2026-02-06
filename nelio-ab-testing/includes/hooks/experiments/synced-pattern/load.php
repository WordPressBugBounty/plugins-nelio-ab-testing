<?php

namespace Nelio_AB_Testing\Experiment_Library\Synced_Pattern_Experiment;

defined( 'ABSPATH' ) || exit;

use function add_action;
use function add_filter;

add_filter( 'nab_nab/synced-pattern_experiment_priority', fn() => 'custom' );
add_filter( 'nab_nab/synced-pattern_disable_query_arg_preloading', '__return_true' );

/**
 * Callback to enable relevant tests.
 *
 * @param string $result Result.
 *
 * @return string
 */
function enable_relevant_tests( $result ) {
	remove_filter( 'pre_render_block', __NAMESPACE__ . '\enable_relevant_tests' );

	$runtime                 = \Nelio_AB_Testing_Runtime::instance();
	$experiment_patterns_map = get_all_tested_pattern_ids();
	if ( empty( $experiment_patterns_map ) ) {
		return $result;
	}

	if ( wp_is_block_theme() ) {
		/** @var string $_wp_current_template_id */
		global $_wp_current_template_id;
		$template = ! empty( $_wp_current_template_id ) ? get_block_template( $_wp_current_template_id, 'wp_template' ) : false;
		$template = ! empty( $template ) ? $template->content : '';
		/** @var list<TWP_Parsed_Block> $blocks */
		$blocks   = parse_blocks( $template );
		$relevant = get_relevant_experiment_ids( $experiment_patterns_map, $blocks );
		foreach ( $relevant as $experiment_id ) {
			$runtime->add_custom_priority_experiment( $experiment_id );
		}
	}

	if ( is_singular() ) {
		/** @var \WP_Post $post */
		global $post;
		/** @var list<TWP_Parsed_Block> $blocks */
		$blocks   = parse_blocks( $post->post_content );
		$relevant = get_relevant_experiment_ids( $experiment_patterns_map, $blocks );
		foreach ( $relevant as $experiment_id ) {
			$runtime->add_custom_priority_experiment( $experiment_id );
		}
	}

	return $result;
}
add_filter( 'pre_render_block', __NAMESPACE__ . '\enable_relevant_tests' );

/**
 * Callback to enable relevant tests on legacy themes.
 *
 * @return void
 */
function enable_relevant_tests_on_legacy_themes() {
	$experiment_patterns_map = get_all_tested_pattern_ids();
	if ( empty( $experiment_patterns_map ) ) {
		return;
	}

	if ( ! is_singular() ) {
		return;
	}

	ob_start();
	do_blocks( get_the_content() );
	ob_end_clean();
}
add_action( 'wp', __NAMESPACE__ . '\enable_relevant_tests_on_legacy_themes' );

/**
 * Callback to add required hooks to load alternative content.
 *
 * @param TSynced_Pattern_Alternative_Attributes|TSynced_Pattern_Control_Attributes $alternative   Alternative.
 * @param TSynced_Pattern_Control_Attributes                                        $control       Control.
 * @param int                                                                       $experiment_id Experiment ID.
 *
 * @return void
 */
function load_alternative( $alternative, $control, $experiment_id ) {
	$tested_pattern_ids = get_tested_pattern_ids( $experiment_id );
	add_filter(
		'pre_render_block',
		function ( $result, $parsed_block ) use ( &$alternative, &$tested_pattern_ids ) {
			/** @var string           $result       */
			/** @var TWP_Parsed_Block $parsed_block */

			$name = $parsed_block['blockName'];
			if ( 'core/block' !== $name ) {
				return $result;
			}

			$pattern_id = absint( $parsed_block['attrs']['ref'] ?? 0 );
			if ( ! in_array( $pattern_id, $tested_pattern_ids, true ) ) {
				return $result;
			}

			if ( $pattern_id === $alternative['patternId'] ) {
				return $result;
			}

			$alternative_pattern = get_post( $alternative['patternId'] );
			if ( empty( $alternative_pattern ) ) {
				return '';
			}

			$post = get_post( $alternative['patternId'] );
			if ( is_null( $post ) ) {
				return '';
			}
			return do_blocks( $post->post_content );
		},
		10,
		2
	);
}
add_action( 'nab_nab/synced-pattern_load_alternative', __NAMESPACE__ . '\load_alternative', 10, 3 );

// =======
// HELPERS
// =======

/**
 * Returns all tested pattern IDs.
 *
 * @return array<int,list<int>>
 */
function get_all_tested_pattern_ids() {
	$experiments    = nab_get_running_experiments();
	$experiments    = array_filter( $experiments, fn( $e ) => 'nab/synced-pattern' === $e->get_type() );
	$experiments    = array_values( $experiments );
	$experiment_ids = array_map( fn( $e ) => $e->get_id(), $experiments );
	$pattern_ids    = array_map( __NAMESPACE__ . '\get_tested_pattern_ids', $experiment_ids );
	return array_combine( $experiment_ids, $pattern_ids );
}

/**
 * Returns tested pattern IDs.
 *
 * @param int $experiment_id Experiment ID.
 *
 * @return list<int>
 */
function get_tested_pattern_ids( $experiment_id ) {
	$experiment = nab_get_experiment( $experiment_id );
	if ( is_wp_error( $experiment ) ) {
		return array();
	}

	/** @var \Nelio_AB_Testing_Experiment $experiment */
	$experiment = $experiment;
	return array_map(
		fn( $a ) => absint( $a['attributes']['patternId'] ?? 0 ),
		$experiment->get_alternatives()
	);
}

/**
 * Returns the list of relevant experiment IDs.
 *
 * @param array<int,list<int>>   $experiment_patterns_map Experiment patterns map.
 * @param list<TWP_Parsed_Block> $blocks                  Blocks.
 *
 * @return list<int>
 */
function get_relevant_experiment_ids( $experiment_patterns_map, $blocks ) {
	$blocks             = block_array_flatten( $blocks );
	$blocks             = array_filter( $blocks, fn( $b ) => 'core/block' === $b['blockName'] );
	$patterns_in_blocks = array_map( fn( $b ) => absint( $b['attrs']['ref'] ?? 0 ), $blocks );
	$patterns_in_blocks = array_values( array_filter( $patterns_in_blocks ) );

	$result = array();
	foreach ( $experiment_patterns_map as $experiment_id => $expected_patterns ) {
		if ( array_intersect( $expected_patterns, $patterns_in_blocks ) ) {
			$result[] = $experiment_id;
		}
	}
	return $result;
}

/**
 * Flattens the list of blocks.
 *
 * @param list<TWP_Parsed_Block> $blocks Blocks.
 *
 * @return list<TWP_Parsed_Block>
 */
function block_array_flatten( $blocks ) {
	if ( empty( $blocks ) ) {
		return array();
	}

	/** @var list<list<TWP_Parsed_Block>> */
	$all_children = array_map( fn( $b ) => $b['innerBlocks'] ?? array(), $blocks );
	$all_children = array_map( fn( $cs ) => block_array_flatten( $cs ), $all_children );
	$result       = $blocks;
	foreach ( $all_children as $children ) {
		$result = array_merge( $result, $children );
	}
	return $result;
}
