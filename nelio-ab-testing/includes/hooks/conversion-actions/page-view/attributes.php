<?php

namespace Nelio_AB_Testing\Conversion_Action_Library\Page_View;

defined( 'ABSPATH' ) || exit;

use function add_filter;

use Nelio_AB_Testing\Zod\Schema;
use Nelio_AB_Testing\Zod\Zod as Z;

/**
 * Sanitizes attributes.
 *
 * @param TAttributes        $attributes Attributes.
 * @param TConversion_Action $action     Action.
 *
 * @return TAttributes
 */
function sanitize_conversion_action_attributes( $attributes, $action ) {
	if ( 'nab/page-view' !== $action['type'] ) {
		return $attributes;
	}

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
						'url'      => Z::string()->default( '' )->transform( fn() => '' ),
					)
				),
				Z::object(
					array(
						'mode'     => Z::literal( 'url' ),
						'postId'   => Z::number()->default( 0 )->transform( fn() => 0 ),
						'postType' => Z::string()->default( '' )->transform( fn() => 'page' ),
						'url'      => Z::string()->default( '' )->trim(),
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
add_filter( 'nab_sanitize_conversion_action_attributes', __NAMESPACE__ . '\sanitize_conversion_action_attributes', 10, 2 );
