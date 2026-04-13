<?php

namespace Nelio_AB_Testing\Experiment_Library\Css_Experiment;

defined( 'ABSPATH' ) || exit;

use Nelio_AB_Testing\Zod\Schema;
use Nelio_AB_Testing\Zod\Zod as Z;

/**
 * Callback to sanitize alternative attributes.
 *
 * @param TAttributes $alternative Alternative attributes.
 *
 * @return TCss_Alternative_Attributes
 */
function sanitize_alternative_attributes( $alternative ) {
	/** @var Schema|null */
	static $schema;
	if ( empty( $schema ) ) {
		$schema = Z::object(
			array(
				'name'    => Z::string()->default( '' )->trim(),
				'css'     => Z::string()->default( '' )->trim(),
				'content' => Z::array(
					Z::union(
						array(
							Z::object(
								array(
									'type' => Z::literal( 'element' ),
									'html' => Z::string()->default( '' )->transform(
										// @phpstan-ignore-next-line argument.type
										fn ( $v ) =>trim( wp_strip_all_tags( $v ) )
									),
								)
							)->transform(
								// @phpstan-ignore-next-line offsetAccess.nonOffsetAccessible
								fn( $v ) => ! empty( $v['html'] ) ? $v : false
							),
							Z::object(
								array(
									'type' => Z::literal( 'image' ),
									'alt'  => Z::string()->default( '' )->trim(),
									'src'  => Z::string()->default( '' )->trim(),
								)
							)->transform(
								// @phpstan-ignore-next-line offsetAccess.nonOffsetAccessible
								fn( $v ) => ! empty( $v['alt'] ) || ! empty( $v['src'] ) ? $v : false
							),
						)
					)->catch( false ),
				)
					->transform(
						// @phpstan-ignore-next-line argument.type
						fn( $v ) => array_values( array_filter( $v ) )
					)
					->catch( array() ),
			)
		);
	}

	$parsed = $schema->safe_parse( $alternative );
	assert( $parsed['success'] );
	/** @var TCss_Alternative_Attributes */
	return $parsed['data'];
}
add_filter( 'nab_nab/css_sanitize_alternative_attributes', __NAMESPACE__ . '\sanitize_alternative_attributes' );
