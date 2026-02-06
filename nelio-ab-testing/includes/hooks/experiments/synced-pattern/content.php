<?php

namespace Nelio_AB_Testing\Experiment_Library\Synced_Pattern_Experiment;

defined( 'ABSPATH' ) || exit;

use Nelio_AB_Testing_Post_Helper;

use function add_action;
use function add_filter;
use function update_post_meta;
use function wp_delete_post;

/**
 * Callback to add synced pattern type.
 *
 * @param array<string,TPost_Type> $types Types.
 *
 * @return array<string,TPost_Type>
 */
function add_synced_pattern_type( $types ) {
	$type = get_post_type_object( 'wp_block' );
	if ( empty( $type ) ) {
		return $types;
	}

	$types['wp_block'] = array(
		'name'   => $type->name,
		'label'  => $type->label,
		'labels' => array(
			'singular_name' => is_string( $type->labels->singular_name ) ? $type->labels->singular_name : $type->name,
		),
		'kind'   => 'entity',
	);
	return $types;
}
add_filter( 'nab_get_post_types', __NAMESPACE__ . '\add_synced_pattern_type' );

/**
 * Callback to select synced patterns only.
 *
 * @param array<mixed> $args Args.
 *
 * @return array<mixed>
 */
function select_synced_patterns_only( $args ) {
	$type = $args['post_type'] ?? '';
	if ( 'wp_block' !== $type ) {
		return $args;
	}

	// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
	$args['meta_query'] = array(
		array(
			'key'     => 'wp_pattern_sync_status',
			'compare' => 'NOT EXISTS',
		),
	);
	return $args;
}
add_filter( 'nab_wp_post_search_args', __NAMESPACE__ . '\select_synced_patterns_only' );

/**
 * Callback to remove alternative content.
 *
 * @param TSynced_Pattern_Alternative_Attributes $alternative Alternative.
 *
 * @return void
 */
function remove_alternative_content( $alternative ) {

	if ( ! empty( $alternative['isExistingContent'] ) ) {
		return;
	}

	// DEPRECATED. This code is here because we used to create backups using control attributes.
	/** @var array{testAgainstExistingPatterns?:bool} $deprecated */
	$deprecated = $alternative;
	if ( ! empty( $deprecated['testAgainstExistingPatterns'] ) ) {
		return;
	}

	if ( empty( $alternative['patternId'] ) ) {
		return;
	}

	wp_delete_post( $alternative['patternId'], true );
}
add_action( 'nab_nab/synced-pattern_remove_alternative_content', __NAMESPACE__ . '\remove_alternative_content' );

/**
 * Callback to create alternative content.
 *
 * @param TSynced_Pattern_Alternative_Attributes $alternative    Alternative.
 * @param TSynced_Pattern_Control_Attributes     $control        Control.
 * @param int                                    $experiment_id  Experiment ID.
 *
 * @return TSynced_Pattern_Alternative_Attributes
 */
function create_alternative_content( $alternative, $control, $experiment_id ) {

	if ( ! empty( $alternative['isExistingContent'] ) ) {
		return $alternative;
	}

	if ( empty( $control['patternId'] ) ) {
		return $alternative;
	}

	$post_helper = Nelio_AB_Testing_Post_Helper::instance();
	$new_post_id = $post_helper->duplicate( $control['patternId'] );
	if ( empty( $new_post_id ) ) {
		$alternative['unableToCreateVariant'] = true;
		return $alternative;
	}

	update_post_meta( $new_post_id, '_nab_experiment', $experiment_id );
	$alternative['patternId'] = $new_post_id;

	return $alternative;
}
add_filter( 'nab_nab/synced-pattern_create_alternative_content', __NAMESPACE__ . '\create_alternative_content', 10, 3 );

// Duplicating content is exactly the same as creating it from scratch, as long as “control” is set to the “old alternative” (which it is).
add_filter( 'nab_nab/synced-pattern_duplicate_alternative_content', __NAMESPACE__ . '\create_alternative_content', 10, 3 );

/**
 * Callback to create alternative content.
 *
 * @param TAttributes                        $backup         Backup.
 * @param TSynced_Pattern_Control_Attributes $control        Control.
 * @param int                                $experiment_id  Experiment ID.
 *
 * @return TSynced_Pattern_Alternative_Attributes
 */
function backup_control( $backup, $control, $experiment_id ) {
	if ( empty( $control['testAgainstExistingPatterns'] ) ) {
		$alternative = array(
			'name'      => '',
			'patternId' => 0,
		);
		return create_alternative_content( $alternative, $control, $experiment_id );
	}

	return array(
		'name'              => '',
		'patternId'         => $control['patternId'],
		'isExistingContent' => true,
	);
}
add_filter( 'nab_nab/synced-pattern_backup_control', __NAMESPACE__ . '\backup_control', 10, 3 );

/**
 * Callback to apply the given alternative.
 *
 * @param bool                                   $applied     Applied.
 * @param TSynced_Pattern_Alternative_Attributes $alternative Alternative.
 * @param TSynced_Pattern_Control_Attributes     $control     Control.
 *
 * @return bool
 */
function apply_alternative( $applied, $alternative, $control ) {

	if ( ! empty( $control['testAgainstExistingPatterns'] ) ) {
		return false;
	}

	$control_id     = $control['patternId'];
	$tested_element = get_post( $control_id );
	if ( empty( $tested_element ) ) {
		return false;
	}

	$alternative_id   = $alternative['patternId'];
	$alternative_post = get_post( $alternative_id );
	if ( empty( $alternative_post ) ) {
		return false;
	}

	$post_helper = Nelio_AB_Testing_Post_Helper::instance();
	$post_helper->overwrite( $control_id, $alternative_id );
	return true;
}
add_filter( 'nab_nab/synced-pattern_apply_alternative', __NAMESPACE__ . '\apply_alternative', 10, 3 );
