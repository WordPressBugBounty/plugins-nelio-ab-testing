<?php

namespace Nelio_AB_Testing\Experiment_Library\Headline_Experiment;

defined( 'ABSPATH' ) || exit;

use function add_filter;
use function get_post;
use function get_post_meta;

function get_tested_posts( $_, $experiment ) {
	$control = $experiment->get_alternative( 'control' );
	$control = $control['attributes'];
	return array( $control['postId'] );
}//end get_tested_posts()
add_filter( 'nab_nab/headline_get_tested_posts', __NAMESPACE__ . '\get_tested_posts', 10, 2 );

function backup_control( $backup, $control ) {

	$post = get_post( $control['postId'] );
	if ( empty( $post ) || is_wp_error( $post ) ) {
		return array();
	}//end if

	$backup = array(
		'name'    => $post->post_title,
		'excerpt' => $post->post_excerpt,
		'imageId' => absint( get_post_meta( $post->ID, '_thumbnail_id', true ) ),
	);
	return $backup;
}//end backup_control()
add_filter( 'nab_nab/headline_backup_control', __NAMESPACE__ . '\backup_control', 10, 2 );

function apply_alternative( $applied, $alternative, $control, $experiment_id, $alternative_id ) {

	$post = get_post( $control['postId'] );
	if ( empty( $post ) || is_wp_error( $post ) ) {
		return false;
	}//end if

	if ( ! empty( trim( $alternative['name'] ) ) ) {
		$post->post_title = $alternative['name'];
	}//end if

	if ( ! empty( trim( $alternative['excerpt'] ) ) ) {
		$post->post_excerpt = $alternative['excerpt'];
	}//end if

	if ( ! empty( absint( $alternative['imageId'] ) ) ) {
		update_post_meta( $control['postId'], '_thumbnail_id', absint( $alternative['imageId'] ) );
	} elseif ( 'control_backup' === $alternative_id ) {
		delete_post_meta( $control['postId'], '_thumbnail_id' );
	}//end if

	$result = wp_update_post( $post );
	if ( is_wp_error( $result ) ) {
		return false;
	}//end if

	return true;
}//end apply_alternative()
add_filter( 'nab_nab/headline_apply_alternative', __NAMESPACE__ . '\apply_alternative', 10, 5 );
