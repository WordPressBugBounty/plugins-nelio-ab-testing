<?php

namespace Nelio_AB_Testing\Experiment_Library\Headline_Experiment;

defined( 'ABSPATH' ) || exit;

use function add_filter;
use function get_post;
use function get_post_meta;

/**
 * Callback to get the list of tested posts.
 *
 * @param list<int>                    $posts Posts.
 * @param \Nelio_AB_Testing_Experiment $experiment Experiment.
 *
 * @return list<int>
 */
function get_tested_posts( $posts, $experiment ) {
	$control = $experiment->get_alternative( 'control' );
	/** @var THeadline_Control_Attributes */
	$control = $control['attributes'];
	return array( $control['postId'] );
}
add_filter( 'nab_nab/headline_get_tested_posts', __NAMESPACE__ . '\get_tested_posts', 10, 2 );

/**
 * Callback to backup the control version.
 *
 * @param TAttributes                  $backup  Backup.
 * @param THeadline_Control_Attributes $control Control.
 *
 * @return THeadline_Alternative_Attributes
 */
function backup_control( $backup, $control ) {

	$post = get_post( $control['postId'] );
	if ( empty( $post ) ) {
		return array(
			'name'     => '',
			'excerpt'  => '',
			'imageId'  => 0,
			'imageUrl' => '',
		);
	}

	$image_id  = absint( get_post_meta( $post->ID, '_thumbnail_id', true ) );
	$image_url = wp_get_attachment_image_src( $image_id, 'thumbnail' );
	$image_url = ! empty( $image_url ) ? $image_url : array();
	$image_url = $image_url[0] ?? '';

	$backup = array(
		'name'     => $post->post_title,
		'excerpt'  => $post->post_excerpt,
		'imageId'  => $image_id,
		'imageUrl' => $image_url,
	);
	return $backup;
}
add_filter( 'nab_nab/headline_backup_control', __NAMESPACE__ . '\backup_control', 10, 2 );

/**
 * Callback to apply the given alternative.
 *
 * @param bool                             $applied        Applied.
 * @param THeadline_Alternative_Attributes $alternative    Alternative.
 * @param THeadline_Control_Attributes     $control        Control.
 * @param int                              $experiment_id  Experiment ID.
 * @param string                           $alternative_id Alternative ID.
 *
 * @return bool
 */
function apply_alternative( $applied, $alternative, $control, $experiment_id, $alternative_id ) {

	$post = get_post( $control['postId'] );
	if ( empty( $post ) ) {
		return false;
	}

	if ( ! empty( trim( $alternative['name'] ) ) ) {
		$post->post_title = $alternative['name'];
	}

	if ( ! empty( trim( $alternative['excerpt'] ) ) ) {
		$post->post_excerpt = $alternative['excerpt'];
	}

	if ( ! empty( absint( $alternative['imageId'] ) ) ) {
		update_post_meta( $control['postId'], '_thumbnail_id', absint( $alternative['imageId'] ) );
	} elseif ( 'control_backup' === $alternative_id ) {
		delete_post_meta( $control['postId'], '_thumbnail_id' );
	}

	$postarr                = (array) $post;
	$postarr['post_author'] = absint( $postarr['post_author'] );
	$result                 = wp_update_post( $postarr );
	if ( empty( $result ) ) {
		return false;
	}

	return true;
}
add_filter( 'nab_nab/headline_apply_alternative', __NAMESPACE__ . '\apply_alternative', 10, 5 );
