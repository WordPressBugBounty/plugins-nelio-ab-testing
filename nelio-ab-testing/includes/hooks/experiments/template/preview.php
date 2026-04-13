<?php

namespace Nelio_AB_Testing\Experiment_Library\Template_Experiment;

defined( 'ABSPATH' ) || exit;

use function add_filter;

/**
 * Gets preview link for the given alternative.
 *
 * @param string|false                     $preview_link Preview link.
 * @param TTemplate_Alternative_Attributes $alternative  Alternative.
 * @param TTemplate_Control_Attributes     $control      Control.
 *
 * @return string|false
 */
function get_preview_link( $preview_link, $alternative, $control ) {
	if ( empty( $control['templateId'] ) ) {
		return $preview_link;
	}

	if ( '_nab_front_page_template' === $control['templateId'] ) {
		return home_url();
	}

	if ( '_nab_default_template' === $control['templateId'] ) {
		$meta_query = array(
			array(
				'key'     => '_wp_page_template',
				'compare' => 'NOT EXISTS',
			),
		);
	} else {
		$meta_query = array(
			array(
				'key'     => '_wp_page_template',
				'compare' => '=',
				'value'   => $control['templateId'],
			),
		);
	}

	$args = array(
		'post_type'      => $control['postType'] ?? '',
		'post_status'    => 'publish',
		'posts_per_page' => 1,
		// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
		'meta_query'     => $meta_query,
		'no_found_rows'  => true,
	);

	$posts = get_posts( $args );
	if ( empty( $posts ) ) {
		return $preview_link;
	}

	$link = get_permalink( $posts[0] );
	return ! empty( $link ) ? $link : $preview_link;
}

/**
 * Callback to get preview link.
 *
 * It uses an internal static cache.
 *
 * @param string|false                     $preview_link Preview link.
 * @param TTemplate_Alternative_Attributes $alternative  Alternative.
 * @param TTemplate_Control_Attributes     $control      Control.
 *
 * @return string|false
 */
function get_preview_link_cached( $preview_link, $alternative, $control ) {
	/** @var array<string,string|false> $cache */
	static $cache = array();
	$post_type    = $control['postType'] ?? '';
	$key          = $post_type . '-' . $control['templateId'];
	if ( ! isset( $cache[ $key ] ) ) {
		$cache[ $key ] = get_preview_link( $preview_link, $alternative, $control );
	}
	return $cache[ $key ];
}
add_filter( 'nab_nab/template_preview_link_alternative', __NAMESPACE__ . '\get_preview_link_cached', 10, 3 );

add_filter( 'nab_get_nab/template_alternative_loaders_during_preview', __NAMESPACE__ . '\get_alternative_loaders', 10, 5 );
