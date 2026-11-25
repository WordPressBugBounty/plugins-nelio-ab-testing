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
				'chance'                      => Z::number()->optional()->catch( null ),
				'patternId'                   => Z::number()->transform( fn( $v ) => absint( $v ) )->catch( 0 ),
				'testAgainstExistingPatterns' => Z::boolean()->optional()->transform( 'nab_nullify' )->catch( null ),
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
				'name'      => Z::string()->default( '' )->trim()->catch( '' ),
				'chance'    => Z::number()->optional()->catch( null ),
				'patternId' => Z::number()->transform( fn( $v ) => absint( $v ) )->default( 0 )->catch( 0 ),
			)
		)->catch(
			array(
				'name'      => '',
				'patternId' => 0,
			)
		);
	}

	$parsed = $schema->safe_parse( $alternative );
	assert( $parsed['success'] );
	/** @var TSynced_Pattern_Alternative_Attributes */
	return $parsed['data'];
}
add_filter( 'nab_nab/synced-pattern_sanitize_alternative_attributes', __NAMESPACE__ . '\sanitize_alternative_attributes' );
