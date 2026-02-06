<?php

namespace Nelio_AB_Testing\WooCommerce\Experiment_Library\Bulk_Sale_Experiment;

defined( 'ABSPATH' ) || exit;

use function add_action;
use function add_filter;

/**
 * Callback to get preview link.
 *
 * @param string|false                                                          $preview_link Preview link.
 * @param TWC_Bulk_Sale_Alternative_Attributes|TWC_Bulk_Sale_Control_Attributes $alternative Alternative.
 * @param TWC_Bulk_Sale_Control_Attributes                                      $control     Control.
 *
 * @return string|false
 */
function get_preview_link( $preview_link, $alternative, $control ) {
	$selection = $control['productSelections'][0];
	foreach ( $control['productSelections'] as $selection ) {
		if ( 'some-products' !== $selection['type'] ) {
			continue;
		}

		$selection = $selection['value'];
		if ( 'product-ids' === $selection['type'] ) {
			foreach ( $selection['productIds'] as $pid ) {
				$link = get_permalink( $pid );
				if ( ! empty( $link ) ) {
					return $link;
				}
			}
			continue;
		}

		$args  = array(
			'post_type'  => 'product',
			'post_count' => 1,
			// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query
			'tax_query'  => array_map(
				function ( $terms ) {
					return array(
						'taxonomy' => $terms['taxonomy'],
						'field'    => 'term_id',
						'terms'    => $terms['termIds'],
					);
				},
				$selection['value']
			),
		);
		$posts = get_posts( $args );
		$link  = empty( $posts ) ? '' : get_permalink( $posts[0]->ID );
		if ( ! empty( $link ) ) {
			return $link;
		}
	}

	$link = get_permalink( wc_get_page_id( 'shop' ) );
	return ! empty( $link ) ? $link : $preview_link;
}
add_filter( 'nab_nab/wc-bulk-sale_preview_link_alternative', __NAMESPACE__ . '\get_preview_link', 10, 3 );

/**
 * Callback to enable browsing in bulk sale previews.
 *
 * @param bool   $enabled Enabled.
 * @param string $type    Test type.
 *
 * @return bool
 */
function can_browse_preview( $enabled, $type ) {
	return 'nab/wc-bulk-sale' === $type ? true : $enabled;
}
add_filter( 'nab_is_preview_browsing_enabled', __NAMESPACE__ . '\can_browse_preview', 10, 2 );

add_action( 'nab_nab/wc-bulk-sale_preview_alternative', __NAMESPACE__ . '\load_alternative_discount', 10, 3 );
