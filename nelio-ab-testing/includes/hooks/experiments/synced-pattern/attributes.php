<?php

namespace Nelio_AB_Testing\Experiment_Library\Synced_Pattern_Experiment;

defined( 'ABSPATH' ) || exit;

use Nelio_AB_Testing\Zod\Schema;
use Nelio_AB_Testing\Zod\Zod as Z;

/**
 * Sanitizes control attributes.
 *
 * @param TAttributes $control Control.
 *
 * @return TSynced_Pattern_Control_Attributes
 */
function sanitize_control_attributes( $control ) {
	/** @var Schema|null */
	static $schema;
	if ( empty( $schema ) ) {
		$schema = Z::object(
			array(
				'patternId' => Z::number()->catch( 0 ),
			)
		)->catch( array( 'patternId' => 0 ) );
	}

	$parsed = $schema->safe_parse( $control );
	assert( $parsed['success'] );
	/** @var TSynced_Pattern_Control_Attributes */
	return $parsed['data'];
}
add_filter( 'nab_nab/synced-pattern_sanitize_control_attributes', __NAMESPACE__ . '\sanitize_control_attributes' );

/**
 * Sanitizes alternative attributes.
 *
 * @param TAttributes $alternative Alternative.
 *
 * @return TSynced_Pattern_Alternative_Attributes
 */
function sanitize_alternative_attributes( $alternative ) {
	/** @var Schema|null */
	static $schema;
	if ( empty( $schema ) ) {
		$schema = Z::object(
			array(
				'name'      => Z::string()->default( '' )->trim(),
				'patternId' => Z::number()->default( 0 ),
			)
		);
	}

	$parsed = $schema->safe_parse( $alternative );
	assert( $parsed['success'] );
	/** @var TSynced_Pattern_Alternative_Attributes */
	return $parsed['data'];
}
add_filter( 'nab_nab/synced-pattern_sanitize_alternative_attributes', __NAMESPACE__ . '\sanitize_alternative_attributes' );
