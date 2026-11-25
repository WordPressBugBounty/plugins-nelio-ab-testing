<?php

namespace Nelio_AB_Testing\Experiment_Library\Css_Experiment;

defined( 'ABSPATH' ) || exit;

use Nelio_AB_Testing\Zod\Schema;
use Nelio_AB_Testing\Zod\Zod as Z;

/**
 * Callback to sanitize control attributes.
 *
 * @param TAttributes $control Control attributes.
 *
 * @return TCss_Control_Attributes
 */
function sanitize_control_attributes( $control ) {
	/** @var Schema|null */
	static $schema;
	if ( empty( $schema ) ) {
		$schema = Z::object(
			array(
				'chance' => Z::number()->optional()->catch( null ),
			)
		)->catch( array() );
	}

	$parsed = $schema->safe_parse( $control );
	assert( $parsed['success'] );
	/** @var TCss_Control_Attributes */
	return $parsed['data'];
}
add_filter( 'nab_nab/css_sanitize_control_attributes', __NAMESPACE__ . '\sanitize_control_attributes' );

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
				'name'    => Z::string()->default( '' )->trim()->catch( '' ),
				'chance'  => Z::number()->optional()->catch( null ),
				'css'     => Z::string()->default( '' )->trim()->catch( '' ),
				'content' => Z::array(
					Z::union(
						array(
							Z::object(
								array(
									'type'     => Z::literal( 'element' ),
									'selector' => Z::string()->default( '' )->trim()->catch( '' ),
									'html'     => Z::string()->default( '' )->transform(
										fn ( $v ) => wp_kses(
											// @phpstan-ignore-next-line argument.type
											trim( $v ),
											array(
												'strong' => array(),
												'b'      => array(),
												'em'     => array(),
												'i'      => array(),
												'u'      => array(),
												'li'     => array(),
												'a'      => array(
													'target' => true,
													'href' => true,
													'rel'  => true,
												),
											)
										)
									)->catch( '' ),
								)
							)->transform(
								// @phpstan-ignore-next-line offsetAccess.nonOffsetAccessible
								fn( $v ) => ! empty( $v['selector'] ) && ! empty( $v['html'] ) ? $v : false
							),
							Z::object(
								array(
									'type'     => Z::literal( 'image' ),
									'selector' => Z::string()->default( '' )->trim()->catch( '' ),
									'alt'      => Z::string()->default( '' )->trim()->catch( '' ),
									'src'      => Z::string()->default( '' )->trim()->catch( '' ),
								)
							)->transform(
								// @phpstan-ignore-next-line offsetAccess.nonOffsetAccessible
								fn( $v ) => ! empty( $v['selector'] ) && ( ! empty( $v['alt'] ) || ! empty( $v['src'] ) ) ? $v : false
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
		)->catch(
			array(
				'name'    => '',
				'css'     => '',
				'content' => array(),
			)
		);
	}

	$parsed = $schema->safe_parse( $alternative );
	assert( $parsed['success'] );
	/** @var TCss_Alternative_Attributes */
	return $parsed['data'];
}
add_filter( 'nab_nab/css_sanitize_alternative_attributes', __NAMESPACE__ . '\sanitize_alternative_attributes' );
