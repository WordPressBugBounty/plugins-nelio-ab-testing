<?php

namespace Nelio_AB_Testing\Experiment_Library\Synced_Pattern_Experiment;

defined( 'ABSPATH' ) || exit;

use Nelio_AB_Testing_Post_Helper;

use function add_action;
use function add_filter;
use function update_post_meta;
use function wp_delete_post;

function add_synced_pattern_type( $types ) {
	$type = get_post_type_object( 'wp_block' );
	if ( empty( $type ) ) {
		return $types;
	}//end if

	$types[] = array(
		'name'   => $type->name,
		'label'  => $type->label,
		'labels' => array(
			'singular_name' => $type->labels->singular_name,
		),
		'kind'   => 'entity',
	);
	return $types;
}//end add_synced_pattern_type()
add_filter( 'nab_get_post_types', __NAMESPACE__ . '\add_synced_pattern_type' );

function select_synced_patterns_only( $args ) {
	$type = nab_array_get( $args, 'post_type' );
	if ( 'wp_block' !== $type ) {
		return $args;
	}//end if

	$args['meta_query'] = array( // phpcs:ignore
		array(
			'key'     => 'wp_pattern_sync_status',
			'compare' => 'NOT EXISTS',
		),
	);
	return $args;
}//end select_synced_patterns_only()
add_filter( 'nab_wp_post_search_args', __NAMESPACE__ . '\select_synced_patterns_only' );

function remove_alternative_content( $alternative ) {

	if ( ! empty( $alternative['isExistingContent'] ) ) {
		return;
	}//end if

	if ( ! empty( $alternative['testAgainstExistingPatterns'] ) ) {
		return;
	}//end if

	if ( empty( $alternative['patternId'] ) ) {
		return;
	}//end if

	wp_delete_post( $alternative['patternId'], true );
}//end remove_alternative_content()
add_action( 'nab_nab/synced-pattern_remove_alternative_content', __NAMESPACE__ . '\remove_alternative_content' );

function create_alternative_content( $alternative, $control, $experiment_id ) {

	if ( ! empty( $alternative['isExistingContent'] ) ) {
		return $alternative;
	}//end if

	if ( empty( $control['patternId'] ) ) {
		return $alternative;
	}//end if

	$post_helper = Nelio_AB_Testing_Post_Helper::instance();
	$new_post_id = $post_helper->duplicate( $control['patternId'] );
	if ( empty( $new_post_id ) ) {
		$alternative['unableToCreateVariant'] = true;
		return $alternative;
	}//end if

	update_post_meta( $new_post_id, '_nab_experiment', $experiment_id );
	$alternative['patternId'] = $new_post_id;

	return $alternative;
}//end create_alternative_content()
add_filter( 'nab_nab/synced-pattern_create_alternative_content', __NAMESPACE__ . '\create_alternative_content', 10, 3 );

// Duplicating content is exactly the same as creating it from scratch, as long as “control” is set to the “old alternative” (which it is).
add_filter( 'nab_nab/synced-pattern_duplicate_alternative_content', __NAMESPACE__ . '\create_alternative_content', 10, 3 );

function backup_control( $alternative, $control, $experiment_id ) {
	return empty( $alternative['testAgainstExistingPatterns'] )
		? create_alternative_content( $alternative, $control, $experiment_id )
		: $alternative;
}//end backup_control()
add_filter( 'nab_nab/synced-pattern_backup_control', __NAMESPACE__ . '\backup_control', 10, 3 );

// Remove control backup.
add_filter( 'nab_remove_nab/synced-pattern_control_backup', __NAMESPACE__ . '\remove_alternative_content' );

function apply_alternative( $_, $alternative, $control ) {

	if ( ! empty( $control['testAgainstExistingPatterns'] ) ) {
		return false;
	}//end if

	$control_id     = nab_array_get( $control, 'patternId', 0 );
	$tested_element = get_post( $control_id );
	if ( empty( $tested_element ) ) {
		return false;
	}//end if

	$alternative_id   = nab_array_get( $alternative, 'patternId', 0 );
	$alternative_post = get_post( $alternative_id );
	if ( empty( $alternative_post ) ) {
		return false;
	}//end if

	$post_helper = Nelio_AB_Testing_Post_Helper::instance();
	$post_helper->overwrite( $control_id, $alternative_id );
	return true;
}//end apply_alternative()
add_filter( 'nab_nab/synced-pattern_apply_alternative', __NAMESPACE__ . '\apply_alternative', 10, 3 );
