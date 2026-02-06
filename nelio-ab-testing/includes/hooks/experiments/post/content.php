<?php

namespace Nelio_AB_Testing\Experiment_Library\Post_Experiment;

defined( 'ABSPATH' ) || exit;

use Nelio_AB_Testing_Post_Helper;

use function add_action;
use function add_filter;
use function update_post_meta;
use function wp_delete_post;

/**
 * Callback to get the tested posts.
 *
 * @param list<int>                    $posts      Posts.
 * @param \Nelio_AB_Testing_Experiment $experiment Experiment.
 *
 * @return list<int>
 */
function get_tested_posts( $posts, $experiment ) {
	$alts = $experiment->get_alternatives();
	$pids = array_map( fn( $a ) => absint( $a['attributes']['postId'] ?? 0 ), $alts );
	$pids = array_values( array_filter( $pids ) );
	return $pids;
}
add_filter( 'nab_nab/page_get_tested_posts', __NAMESPACE__ . '\get_tested_posts', 10, 2 );
add_filter( 'nab_nab/post_get_tested_posts', __NAMESPACE__ . '\get_tested_posts', 10, 2 );
add_filter( 'nab_nab/custom-post-type_get_tested_posts', __NAMESPACE__ . '\get_tested_posts', 10, 2 );

/**
 * Callback to remove alternative content.
 *
 * @param TPost_Alternative_Attributes $alternative Alternative.
 *
 * @return void
 */
function remove_alternative_content( $alternative ) {

	if ( ! empty( $alternative['isExistingContent'] ) ) {
		return;
	}

	// DEPRECATED. This code is here because we used to create backups using control attributes.
	/** @var array{testAgainstExistingContent?:bool} $deprecated */
	$deprecated = $alternative;
	if ( ! empty( $deprecated['testAgainstExistingContent'] ) ) {
		return;
	}

	if ( empty( $alternative['postId'] ) ) {
		return;
	}

	wp_delete_post( $alternative['postId'], true );
}
add_action( 'nab_nab/page_remove_alternative_content', __NAMESPACE__ . '\remove_alternative_content' );
add_action( 'nab_nab/post_remove_alternative_content', __NAMESPACE__ . '\remove_alternative_content' );
add_action( 'nab_nab/custom-post-type_remove_alternative_content', __NAMESPACE__ . '\remove_alternative_content' );

/**
 * Callback to create alternative content.
 *
 * @param TPost_Alternative_Attributes $alternative   Alternative.
 * @param TPost_Control_Attributes     $control       Control.
 * @param int                          $experiment_id Experiment ID.
 *
 * @return TPost_Alternative_Attributes
 */
function create_alternative_content( $alternative, $control, $experiment_id ) {

	if ( ! empty( $alternative['isExistingContent'] ) ) {
		return $alternative;
	}

	if ( empty( $control['postId'] ) ) {
		return $alternative;
	}

	$post_helper = Nelio_AB_Testing_Post_Helper::instance();
	$new_post_id = $post_helper->duplicate( $control['postId'] );
	if ( empty( $new_post_id ) ) {
		$alternative['unableToCreateVariant'] = true;
		return $alternative;
	}

	update_post_meta( $new_post_id, '_nab_experiment', $experiment_id );
	$alternative['postId'] = $new_post_id;

	return $alternative;
}
add_filter( 'nab_nab/page_create_alternative_content', __NAMESPACE__ . '\create_alternative_content', 10, 3 );
add_filter( 'nab_nab/post_create_alternative_content', __NAMESPACE__ . '\create_alternative_content', 10, 3 );
add_filter( 'nab_nab/custom-post-type_create_alternative_content', __NAMESPACE__ . '\create_alternative_content', 10, 3 );

// Duplicating content is exactly the same as creating it from scratch, as long as “control” is set to the “old alternative” (which it is).
add_filter( 'nab_nab/page_duplicate_alternative_content', __NAMESPACE__ . '\create_alternative_content', 10, 3 );
add_filter( 'nab_nab/post_duplicate_alternative_content', __NAMESPACE__ . '\create_alternative_content', 10, 3 );
add_filter( 'nab_nab/custom-post-type_duplicate_alternative_content', __NAMESPACE__ . '\create_alternative_content', 10, 3 );

/**
 * Callback to create alternative content.
 *
 * @param TAttributes              $backup         Backup.
 * @param TPost_Control_Attributes $control        Control.
 * @param int                      $experiment_id  Experiment ID.
 *
 * @return TPost_Alternative_Attributes
 */
function backup_control( $backup, $control, $experiment_id ) {
	if ( empty( $control['testAgainstExistingContent'] ) ) {
		$alternative = array(
			'name'   => '',
			'postId' => 0,
		);
		return create_alternative_content( $alternative, $control, $experiment_id );
	}

	return array(
		'name'              => '',
		'postId'            => $control['postId'],
		'isExistingContent' => true,
	);
}
add_filter( 'nab_nab/page_backup_control', __NAMESPACE__ . '\backup_control', 10, 3 );
add_filter( 'nab_nab/post_backup_control', __NAMESPACE__ . '\backup_control', 10, 3 );
add_filter( 'nab_nab/custom-post-type_backup_control', __NAMESPACE__ . '\backup_control', 10, 3 );

/**
 * Callback to apply alternative.
 *
 * @param bool                         $applied     Control.
 * @param TPost_Alternative_Attributes $alternative Alternative.
 * @param TPost_Control_Attributes     $control     Control.
 *
 * @return bool
 */
function apply_alternative( $applied, $alternative, $control ) {
	if ( ! empty( $control['testAgainstExistingContent'] ) ) {
		return false;
	}

	$tested_element = get_post( $control['postId'] );
	if ( empty( $tested_element ) ) {
		return false;
	}

	$alternative_post = get_post( $alternative['postId'] );
	if ( empty( $alternative_post ) ) {
		return false;
	}

	$post_helper = Nelio_AB_Testing_Post_Helper::instance();
	$post_helper->overwrite( $control['postId'], $alternative['postId'] );
	return true;
}
add_filter( 'nab_nab/page_apply_alternative', __NAMESPACE__ . '\apply_alternative', 10, 3 );
add_filter( 'nab_nab/post_apply_alternative', __NAMESPACE__ . '\apply_alternative', 10, 3 );
add_filter( 'nab_nab/custom-post-type_apply_alternative', __NAMESPACE__ . '\apply_alternative', 10, 3 );

// Heatmap link is essentially the preview link which will need some extra params to load the heatmap renderer on top of it.
add_filter( 'nab_nab/page_heatmap_link_alternative', __NAMESPACE__ . '\get_preview_link', 10, 3 );
add_filter( 'nab_nab/post_heatmap_link_alternative', __NAMESPACE__ . '\get_preview_link', 10, 3 );
add_filter( 'nab_nab/custom-post-type_heatmap_link_alternative', __NAMESPACE__ . '\get_preview_link', 10, 3 );
