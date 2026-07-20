<?php

namespace Nelio_AB_Testing\Experiment_Library\Url_Experiment;

defined( 'ABSPATH' ) || exit;

use Nelio_AB_Testing\Zod\Schema;
use Nelio_AB_Testing\Zod\Zod as Z;

/**
 * Callback to sanitize control attributes.
 *
 * @param TAttributes $attrs Attrs.
 *
 * @return TUrl_Control_Attributes
 */
function sanitize_control_attributes( $attrs ) {
	/** @var Schema|null */
	static $schema;
	if ( empty( $schema ) ) {
		$schema = Z::object(
			array(
				'url'           => Z::string()->trim()->catch( '' )->transform(
					fn( $url ) => is_string( $url ) ? nab_resolve_normalized_url( $url ) : $url
				),
				'useControlUrl' => Z::boolean()->optional()->transform( 'nab_nullify' ),
			)
		)->catch( array( 'url' => '' ) );
	}

	$parsed = $schema->safe_parse( $attrs );
	assert( $parsed['success'] );
	/** @var TUrl_Control_Attributes */
	return $parsed['data'];
}
add_filter( 'nab_nab/url_sanitize_control_attributes', __NAMESPACE__ . '\sanitize_control_attributes' );

/**
 * Callback to sanitize alternative attributes.
 *
 * @param TAttributes $attrs Attrs.
 *
 * @return TUrl_Alternative_Attributes
 */
function sanitize_alternative_attributes( $attrs ) {
	/** @var Schema|null */
	static $schema;
	if ( empty( $schema ) ) {
		$schema = Z::object(
			array(
				'name'   => Z::string()->default( '' )->trim(),
				'chance' => Z::number()->optional(),
				'url'    => Z::string()->default( '' )->trim()->transform(
					fn( $url ) => is_string( $url ) ? nab_resolve_normalized_url( $url ) : $url
				),
			)
		);
	}

	$parsed = $schema->safe_parse( $attrs );
	assert( $parsed['success'] );
	/** @var TUrl_Alternative_Attributes */
	return $parsed['data'];
}
add_filter( 'nab_nab/url_sanitize_alternative_attributes', __NAMESPACE__ . '\sanitize_alternative_attributes' );

/**
 * Normalizes URL attribute.
 *
 * @param TAttributes $attrs Attrs.
 *
 * @return TAttributes
 */
function normalize_url_attr( $attrs ) {
	if ( isset( $attrs['url'] ) && is_string( $attrs['url'] ) ) {
		$attrs['url'] = nab_normalize_url( $attrs['url'] );
	}
	return $attrs;
}
add_filter( 'nab_nab/url_sanitize_alternative_attributes_pre_save', __NAMESPACE__ . '\normalize_url_attr' );
