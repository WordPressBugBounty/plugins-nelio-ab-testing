<?php

namespace Nelio_AB_Testing\Experiment_Library\Headline_Experiment;

defined( 'ABSPATH' ) || exit;

use Nelio_AB_Testing\Zod\Schema;
use Nelio_AB_Testing\Zod\Zod as Z;

/**
 * Sanitizes control attributes.
 *
 * @param TAttributes $control Control.
 *
 * @return THeadline_Control_Attributes
 */
function sanitize_control_attributes( $control ) {
	/** @var Schema|null */
	static $schema;
	if ( empty( $schema ) ) {
		$schema = Z::object(
			array(
				'postId'   => Z::number()->catch( 0 ),
				'postType' => Z::string()->catch( 'post' ),
			)
		)->catch(
			array(
				'postId'   => 0,
				'postType' => 'post',
			)
		);
	}

	$parsed = $schema->safe_parse( $control );
	assert( $parsed['success'] );
	/** @var THeadline_Control_Attributes */
	return $parsed['data'];
}
add_filter( 'nab_nab/headline_sanitize_control_attributes', __NAMESPACE__ . '\sanitize_control_attributes' );

/**
 * Sanitizes alternative attributes.
 *
 * @param TAttributes $alternative Alternative.
 *
 * @return THeadline_Alternative_Attributes
 */
function sanitize_alternative_attributes( $alternative ) {
	/** @var Schema|null */
	static $schema;
	if ( empty( $schema ) ) {
		$schema = Z::object(
			array(
				'name'     => Z::string()->default( '' )->trim(),
				'excerpt'  => Z::string()->default( '' )->trim(),
				'imageId'  => Z::number()->default( 0 ),
				'imageUrl' => Z::string()->default( '' )->trim(),
			)
		);
	}

	$parsed = $schema->safe_parse( $alternative );
	assert( $parsed['success'] );
	/** @var THeadline_Alternative_Attributes */
	return $parsed['data'];
}
add_filter( 'nab_nab/headline_sanitize_alternative_attributes', __NAMESPACE__ . '\sanitize_alternative_attributes' );
