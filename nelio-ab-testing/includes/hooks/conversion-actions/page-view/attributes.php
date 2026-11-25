<?php

namespace Nelio_AB_Testing\Conversion_Action_Library\Page_View;

defined( 'ABSPATH' ) || exit;

use function add_filter;

use Nelio_AB_Testing\Zod\Schema;
use Nelio_AB_Testing\Zod\Zod as Z;

/**
 * Sanitizes attributes.
 *
 * @param TAttributes $attributes Attributes.
 *
 * @return TAttributes
 */
function sanitize( $attributes ) {
	/** @var Schema|null */
	static $schema;
	if ( empty( $schema ) ) {
		$schema = Z::union(
			array(
				Z::object(
					array(
						'mode'     => Z::literal( 'id' )->default( 'id' ),
						'postId'   => Z::number()->default( 0 ),
						'postType' => Z::string()->default( 'page' ),
						'url'      => Z::literal( '' )->catch( '' ),
					)
				),
				Z::object(
					array(
						'mode'     => Z::literal( 'url' ),
						'postId'   => Z::literal( 0 )->catch( 0 ),
						'postType' => Z::literal( 'page' )->catch( 'page' ),
						'url'      => Z::string()->default( '' )->trim()->transform(
							fn( $url ) => is_string( $url ) ? nab_resolve_normalized_url( $url ) : $url
						),
					)
				),
			)
		)->catch(
			array(
				'mode'     => 'id',
				'postId'   => 0,
				'postType' => 'page',
				'url'      => '',
			)
		);
	}

	$parsed = $schema->safe_parse( $attributes );
	assert( $parsed['success'] );
	/** @var TAttributes */
	return $parsed['data'];
}
add_filter( 'nab_nab/page-view_sanitize_conversion_action_attributes', __NAMESPACE__ . '\sanitize' );
add_filter( 'nab_nab/page-view_sanitize_page_view_trigger_attributes', __NAMESPACE__ . '\sanitize' );

/**
 * Normalizes URL attribute.
 *
 * @param TAttributes $attributes Attributes.
 *
 * @return TAttributes
 */
function normalize_url_attr( $attributes ) {
	if ( isset( $attributes['url'] ) && is_string( $attributes['url'] ) ) {
		$attributes['url'] = nab_normalize_url( $attributes['url'] );
	}

	return $attributes;
}
add_filter( 'nab_nab/page-view_normalize_conversion_action_attributes_pre_save', __NAMESPACE__ . '\normalize_url_attr' );
add_filter( 'nab_nab/page-view_normalize_page_view_trigger_attributes_pre_save', __NAMESPACE__ . '\normalize_url_attr' );
