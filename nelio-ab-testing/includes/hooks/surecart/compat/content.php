<?php

namespace Nelio_AB_Testing\SureCart\Compat;

defined( 'ABSPATH' ) || exit;

use function add_filter;

function remove_surecart_types( $data ) {
	unset( $data['sc_collection'] );
	unset( $data['sc_upsell'] );

	return $data;
}//end remove_surecart_types()
add_filter( 'nab_get_post_types', __NAMESPACE__ . '\remove_surecart_types' );

function get_surecart_product( $post, $post_id, $post_type ) {
	if ( null !== $post ) {
		return $post;
	}//end if

	if ( 'sc_product' !== $post_type ) {
		return $post;
	}//end if

	$product = \SureCart\Models\Product::find( $post_id );
	if ( empty( $product ) ) {
		return new \WP_Error(
			'not-found',
			sprintf(
				/* translators: SureCart Product ID */
				_x( 'SureCart product with ID “%s” not found.', 'text', 'nelio-ab-testing' ),
				$post_id
			)
		);
	}//end if

	return array(
		'id'           => $product->id,
		'title'        => $product->getAttribute( 'name' ),
		'excerpt'      => $product->getAttribute( 'description' ) ?? '',
		'date'         => wp_date( 'c', $product->getAttribute( 'created_at' ) ),
		'imageId'      => 0,
		'imageSrc'     => $product->getAttribute( 'image_url' ) ?? '',
		'thumbnailSrc' => $product->getAttribute( 'image_url' ) ?? '',
		'type'         => 'sc_product',
		'typeLabel'    => _x( 'SureCart Product', 'text', 'nelio-ab-testing' ),
		'status'       => $product->getIsPublishedAttribute() ? 'publish' : '',
		'statusLabel'  => $product->getIsPublishedAttribute() ? __( 'Published' ) : '',
		'link'         => $product->getPermalinkAttribute() ?? '',
	);
}//end get_surecart_product()
add_filter( 'nab_pre_get_post', __NAMESPACE__ . '\get_surecart_product', 10, 3 );

function search_surecart_products( $result, $post_type, $term, $per_page, $page ) {
	if ( null !== $result ) {
		return $result;
	}//end if

	if ( 'sc_product' !== $post_type ) {
		return $result;
	}//end if

	$product_id = $term;

	if ( ! empty( $term ) ) {
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
				function ( $product ) use ( $term, $product_id ) {
					return $product_id === $product->id || false !== strpos( strtolower( $product->getAttribute( 'name' ) ), strtolower( $term ) );
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
	}//end if

	$resulting_products = array_map(
		function ( $product ) {
			return array(
				'id'           => $product->id,
				'title'        => $product->getAttribute( 'name' ),
				'excerpt'      => $product->getAttribute( 'description' ) ?? '',
				'date'         => wp_date( 'c', $product->getAttribute( 'created_at' ) ),
				'imageSrc'     => $product->getAttribute( 'image_url' ) ?? '',
				'thumbnailSrc' => $product->getAttribute( 'image_url' ) ?? '',
				'type'         => 'sc_product',
				'typeLabel'    => _x( 'SureCart Product', 'text', 'nelio-ab-testing' ),
				'status'       => $product->getIsPublishedAttribute() ? 'publish' : '',
				'statusLabel'  => $product->getIsPublishedAttribute() ? __( 'Published' ) : '',
				'link'         => $product->getPermalinkAttribute() ?? '',
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
}//end search_surecart_products()
add_filter( 'nab_pre_get_posts', __NAMESPACE__ . '\search_surecart_products', 10, 5 );
