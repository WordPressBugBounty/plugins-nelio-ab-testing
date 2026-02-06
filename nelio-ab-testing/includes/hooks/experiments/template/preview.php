<?php

namespace Nelio_AB_Testing\Experiment_Library\Template_Experiment;

defined( 'ABSPATH' ) || exit;

use function add_filter;

/**
 * Adds preview link hooks.
 *
 * @return void
 */
function add_preview_link_hooks() {

	/** @var array<string,string|false> $links */
	$links = array();

	add_filter(
		'nab_nab/template_preview_link_alternative',
		function ( $preview_link, $alternative, $control ) use ( &$links ) {
			/** @var string|false                     $preview_link */
			/** @var TTemplate_Alternative_Attributes $alternative  */
			/** @var TTemplate_Control_Attributes     $control      */

			if ( empty( $control['templateId'] ) ) {
				return false;
			}

			if ( '_nab_front_page_template' === $control['templateId'] ) {
				return home_url();
			}

			$post_type = $control['postType'] ?? '';
			$key       = $post_type . '-' . $control['templateId'];
			if ( isset( $links[ $key ] ) ) {
				return $links[ $key ];
			}
			$links[ $key ] = $preview_link;

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
				'post_type'      => $post_type,
				'post_status'    => 'publish',
				'posts_per_page' => 1,
				// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
				'meta_query'     => $meta_query,
				'no_found_rows'  => true,
			);

			$posts = get_posts( $args );
			if ( ! empty( $posts ) ) {
				$links[ $key ] = get_permalink( $posts[0] );
			}

			return $links[ $key ];
		},
		10,
		3
	);
}
add_preview_link_hooks();

add_action( 'nab_nab/template_preview_alternative', __NAMESPACE__ . '\load_alternative', 10, 2 );
