<?php
namespace Nelio_AB_Testing\Experiment_Library\Template_Experiment;

defined( 'ABSPATH' ) || exit;

/**
 * Returns the template used by the given post ID.
 *
 * @param int $post_id Post ID.
 *
 * @return string
 */
function get_actual_template( $post_id ) {
	/** @var \wpdb $wpdb */
	global $wpdb;
	// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
	$template = $wpdb->get_var(
		$wpdb->prepare(
			'SELECT meta_value FROM %i  WHERE meta_key = %s AND post_id = %d',
			$wpdb->postmeta,
			'_wp_page_template',
			$post_id
		)
	);

	if ( empty( $template ) || ! locate_template( $template ) ) {
		$template = 'default';
	}

	return $template;
}
