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
				'url'           => Z::string()->trim()->catch( '' ),
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
				'name' => Z::string()->default( '' )->trim(),
				'url'  => Z::string()->default( '' )->trim(),
			)
		);
	}

	$parsed = $schema->safe_parse( $attrs );
	assert( $parsed['success'] );
	/** @var TUrl_Alternative_Attributes */
	return $parsed['data'];
}
add_filter( 'nab_nab/url_sanitize_alternative_attributes', __NAMESPACE__ . '\sanitize_alternative_attributes' );
