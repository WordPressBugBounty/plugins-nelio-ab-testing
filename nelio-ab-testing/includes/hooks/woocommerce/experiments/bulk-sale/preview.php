<?php

namespace Nelio_AB_Testing\WooCommerce\Experiment_Library\Bulk_Sale_Experiment;

defined( 'ABSPATH' ) || exit;

use function add_action;
use function add_filter;

function get_preview_link( $preview_link, $alternative, $control ) {
	$selection = $control['productSelections'][0];
	if ( 'some-products' === $selection['type'] ) {
		$args  = array(
			'post_type'  => 'product',
			'post_count' => 1,
			// phpcs:ignore
			'tax_query'  => array_map(
				function ( $terms ) {
					return array(
						'taxonomy' => $terms['taxonomy'],
						'field'    => 'term_id',
						'terms'    => $terms['termIds'],
					);
				},
				$selection['value']['value']
			),
		);
		$posts = get_posts( $args );
		$link  = empty( $posts ) ? '' : get_permalink( $posts[0]->ID );
	}//end if

	$link = empty( $link ) ? get_permalink( wc_get_page_id( 'shop' ) ) : $link;

	return empty( $link ) ? $preview_link : $link;
}//end get_preview_link()
add_filter( 'nab_nab/wc-bulk-sale_preview_link_alternative', __NAMESPACE__ . '\get_preview_link', 10, 3 );

function can_browse_preview( $enabled, $type ) {
	return 'nab/wc-bulk-sale' === $type ? true : $enabled;
}//end can_browse_preview()
add_filter( 'nab_is_preview_browsing_enabled', __NAMESPACE__ . '\can_browse_preview', 10, 2 );

add_action( 'nab_nab/wc-bulk-sale_preview_alternative', __NAMESPACE__ . '\load_alternative_discount', 10, 3 );
