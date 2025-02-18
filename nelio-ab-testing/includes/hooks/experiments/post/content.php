<?php

namespace Nelio_AB_Testing\Experiment_Library\Post_Experiment;

defined( 'ABSPATH' ) || exit;

use Nelio_AB_Testing_Post_Helper;

use function add_action;
use function add_filter;
use function update_post_meta;
use function wp_delete_post;

function get_tested_posts( $_, $experiment ) {
	$alts = $experiment->get_alternatives();
	$pids = wp_list_pluck( wp_list_pluck( $alts, 'attributes' ), 'postId' );
	$pids = array_values( array_filter( $pids ) );
	return $pids;
}//end get_tested_posts()
add_filter( 'nab_nab/page_get_tested_posts', __NAMESPACE__ . '\get_tested_posts', 10, 2 );
add_filter( 'nab_nab/post_get_tested_posts', __NAMESPACE__ . '\get_tested_posts', 10, 2 );
add_filter( 'nab_nab/custom-post-type_get_tested_posts', __NAMESPACE__ . '\get_tested_posts', 10, 2 );

function remove_alternative_content( $alternative ) {

	if ( ! empty( $alternative['isExistingContent'] ) ) {
		return;
	}//end if

	if ( ! empty( $alternative['testAgainstExistingContent'] ) ) {
		return;
	}//end if

	if ( empty( $alternative['postId'] ) ) {
		return;
	}//end if

	wp_delete_post( $alternative['postId'], true );
}//end remove_alternative_content()
add_action( 'nab_nab/page_remove_alternative_content', __NAMESPACE__ . '\remove_alternative_content' );
add_action( 'nab_nab/post_remove_alternative_content', __NAMESPACE__ . '\remove_alternative_content' );
add_action( 'nab_nab/custom-post-type_remove_alternative_content', __NAMESPACE__ . '\remove_alternative_content' );

function create_alternative_content( $alternative, $control, $experiment_id ) {

	if ( ! empty( $alternative['isExistingContent'] ) ) {
		return $alternative;
	}//end if

	if ( empty( $control['postId'] ) ) {
		return $alternative;
	}//end if

	$post_helper = Nelio_AB_Testing_Post_Helper::instance();
	$new_post_id = $post_helper->duplicate( $control['postId'] );
	if ( is_wp_error( $new_post_id ) ) {
		$alternative['unableToCreateVariant'] = true;
		return $alternative;
	}//end if

	update_post_meta( $new_post_id, '_nab_experiment', $experiment_id );
	$alternative['postId'] = $new_post_id;

	return $alternative;
}//end create_alternative_content()
add_filter( 'nab_nab/page_create_alternative_content', __NAMESPACE__ . '\create_alternative_content', 10, 3 );
add_filter( 'nab_nab/post_create_alternative_content', __NAMESPACE__ . '\create_alternative_content', 10, 3 );
add_filter( 'nab_nab/custom-post-type_create_alternative_content', __NAMESPACE__ . '\create_alternative_content', 10, 3 );

// Duplicating content is exactly the same as creating it from scratch, as long as “control” is set to the “old alternative” (which it is).
add_filter( 'nab_nab/page_duplicate_alternative_content', __NAMESPACE__ . '\create_alternative_content', 10, 3 );
add_filter( 'nab_nab/post_duplicate_alternative_content', __NAMESPACE__ . '\create_alternative_content', 10, 3 );
add_filter( 'nab_nab/custom-post-type_duplicate_alternative_content', __NAMESPACE__ . '\create_alternative_content', 10, 3 );

function backup_control( $alternative, $control, $experiment_id ) {
	return empty( $alternative['testAgainstExistingContent'] )
		? create_alternative_content( $alternative, $control, $experiment_id )
		: $alternative;
}//end backup_control()
add_filter( 'nab_nab/page_backup_control', __NAMESPACE__ . '\backup_control', 10, 3 );
add_filter( 'nab_nab/post_backup_control', __NAMESPACE__ . '\backup_control', 10, 3 );
add_filter( 'nab_nab/custom-post-type_backup_control', __NAMESPACE__ . '\backup_control', 10, 3 );

// Remove control backup.
add_filter( 'nab_remove_nab/page_control_backup', __NAMESPACE__ . '\remove_alternative_content' );
add_filter( 'nab_remove_nab/post_control_backup', __NAMESPACE__ . '\remove_alternative_content' );
add_filter( 'nab_remove_nab/custom-post-type_control_backup', __NAMESPACE__ . '\remove_alternative_content' );

function apply_alternative( $applied, $alternative, $control ) {

	if ( ! empty( $control['testAgainstExistingContent'] ) ) {
		return false;
	}//end if

	$control_id     = isset( $control['postId'] ) ? $control['postId'] : 0;
	$tested_element = get_post( $control_id );
	if ( empty( $tested_element ) || is_wp_error( $tested_element ) ) {
		return false;
	}//end if

	$alternative_id   = isset( $alternative['postId'] ) ? $alternative['postId'] : 0;
	$alternative_post = get_post( $alternative_id );
	if ( empty( $alternative_post ) || is_wp_error( $alternative_post ) ) {
		return false;
	}//end if

	$post_helper = Nelio_AB_Testing_Post_Helper::instance();
	$post_helper->overwrite( $control_id, $alternative_id );
	return true;
}//end apply_alternative()
add_filter( 'nab_nab/page_apply_alternative', __NAMESPACE__ . '\apply_alternative', 10, 3 );
add_filter( 'nab_nab/post_apply_alternative', __NAMESPACE__ . '\apply_alternative', 10, 3 );
add_filter( 'nab_nab/custom-post-type_apply_alternative', __NAMESPACE__ . '\apply_alternative', 10, 3 );

// Heatmap link is essentially the preview link which will need some extra params to load the heatmap renderer on top of it.
add_filter( 'nab_nab/page_heatmap_link_alternative', __NAMESPACE__ . '\get_preview_link', 10, 5 );
add_filter( 'nab_nab/post_heatmap_link_alternative', __NAMESPACE__ . '\get_preview_link', 10, 5 );
add_filter( 'nab_nab/custom-post-type_heatmap_link_alternative', __NAMESPACE__ . '\get_preview_link', 10, 5 );
