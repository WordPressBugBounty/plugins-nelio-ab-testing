<?php

namespace Nelio_AB_Testing\SureCart\Compat;

defined( 'ABSPATH' ) || exit;

use function add_filter;

/**
 * Removes SureCart types from the list of testeable post types.
 *
 * @param array<string,TPost_Type> $data Post types.
 *
 * @return array<string,TPost_Type>
 */
function remove_surecart_types( $data ) {
	unset( $data['sc_collection'] );
	unset( $data['sc_upsell'] );
	return $data;
}
add_filter( 'nab_get_post_types', __NAMESPACE__ . '\remove_surecart_types' );

/**
 * Callback to return the appropriate product.
 *
 * @param null|TPost|\WP_Post|\WP_Error $post The post to filter.
 * @param int|string                    $post_id The id of the post.
 * @param string                        $post_type The post type.
 *
 * @return null|TPost|\WP_Post|\WP_Error
 */
function get_surecart_product( $post, $post_id, $post_type ) {
	if ( null !== $post ) {
		return $post;
	}

	if ( 'sc_product' !== $post_type ) {
		return $post;
	}

	$post_id = "$post_id";
	$product = \SureCart\Models\Product::find( $post_id );
	if ( is_wp_error( $product ) ) {
		return new \WP_Error(
			'not-found',
			sprintf(
				/* translators: %s: SureCart Product ID. */
				_x( 'SureCart product with ID “%s” not found.', 'text', 'nelio-ab-testing' ),
				$post_id
			)
		);
	}

	return array(
		'id'           => $product->id,
		'author'       => 0,
		'authorName'   => '',
		'date'         => wp_date( 'c', $product->getAttribute( 'created_at' ) ),
		'excerpt'      => $product->getAttribute( 'description' ) ?? '',
		'imageId'      => 0,
		'imageSrc'     => $product->getAttribute( 'image_url' ) ?? '',
		'link'         => $product->getPermalinkAttribute(),
		'status'       => $product->getIsPublishedAttribute() ? 'publish' : '',
		'statusLabel'  => $product->getIsPublishedAttribute() ? _x( 'Published', 'text', 'nelio-ab-testing' ) : '',
		'thumbnailSrc' => $product->getAttribute( 'image_url' ) ?? '',
		'title'        => $product->getAttribute( 'name' ),
		'type'         => 'sc_product',
		'typeLabel'    => _x( 'SureCart Product', 'text', 'nelio-ab-testing' ),
		'extra'        => array(),
	);
}
add_filter( 'nab_pre_get_post', __NAMESPACE__ . '\get_surecart_product', 10, 3 );

/**
 * Returns the list of products matching the search query.
 *
 * @param null|array{results:list<TPost>, pagination: array{more:bool, pages:int}} $result    The result data.
 * @param string                                                                   $post_type The post type.
 * @param string                                                                   $query     The query term.
 * @param int                                                                      $per_page  The number of posts to show per page.
 * @param int                                                                      $page      The number of the current page.
 *
 * @return null|array{results:list<TPost>, pagination: array{more:bool, pages:int}}
 */
function search_surecart_products( $result, $post_type, $query, $per_page, $page ) {
	if ( null !== $result ) {
		return $result;
	}

	if ( 'sc_product' !== $post_type ) {
		return $result;
	}

	$product_id = $query;

	if ( ! empty( $query ) ) {
		$products = \SureCart\Models\Product::where(
			array(
				'query'    => $product_id,
				'archived' => false,
			)
		)->paginate(
			array(
				'per_page' => $per_page,
				'page'     => $page,
			)
		)->getAttribute( 'data' );
		$products = array_values(
			array_filter(
				$products,
				function ( $product ) use ( $query, $product_id ) {
					return $product_id === $product->id || false !== strpos( strtolower( $product->getAttribute( 'name' ) ), strtolower( $query ) );
				}
			)
		);
	} else {
		$products = \SureCart\Models\Product::where(
			array(
				'archived' => false,
			)
		)->paginate(
			array(
				'per_page' => $per_page,
				'page'     => $page,
			)
		)->getAttribute( 'data' );
	}

	$resulting_products = array_map(
		function ( $product ) {
			return array(
				'id'           => $product->id,
				'author'       => 0,
				'authorName'   => '',
				'date'         => wp_date( 'c', $product->getAttribute( 'created_at' ) ),
				'excerpt'      => $product->getAttribute( 'description' ) ?? '',
				'imageId'      => 0,
				'imageSrc'     => $product->getAttribute( 'image_url' ) ?? '',
				'link'         => $product->getPermalinkAttribute(),
				'status'       => $product->getIsPublishedAttribute() ? 'publish' : '',
				'statusLabel'  => $product->getIsPublishedAttribute() ? _x( 'Published', 'text', 'nelio-ab-testing' ) : '',
				'thumbnailSrc' => $product->getAttribute( 'image_url' ) ?? '',
				'title'        => $product->getAttribute( 'name' ),
				'type'         => 'sc_product',
				'typeLabel'    => _x( 'SureCart Product', 'text', 'nelio-ab-testing' ),
				'extra'        => array(),
			);
		},
		$products
	);

	return array(
		'results'    => $resulting_products,
		'pagination' => array(
			'more'  => count( $products ) === $per_page,
			'pages' => empty( $page ) ? 1 : $page,
		),
	);
}
add_filter( 'nab_pre_get_posts', __NAMESPACE__ . '\search_surecart_products', 10, 5 );
