<?php

namespace Nelio_AB_Testing\Segmentation_Rule_Library\Window_Width;

defined( 'ABSPATH' ) || exit;

use Nelio_AB_Testing\Zod\Schema;
use Nelio_AB_Testing\Zod\Zod as Z;

/**
 * Sanitizes window-width segmentation rule attributes.
 *
 * @param TAttributes $attributes Attributes.
 *
 * @return TAttributes Sanitized attributes.
 */
function sanitize( $attributes ) {
	/** @var Schema|null $schema */
	static $schema;
	if ( empty( $schema ) ) {
		$schema = Z::object(
			array(
				'condition' => Z::enum( array( 'is-greater-than', 'is-less-than', 'between' ) )->default( 'is-greater-than' )->catch( 'is-greater-than' ),
				'value'     => Z::number()->optional()->catch( null ),
				'interval'  => Z::object(
					array(
						'min' => Z::number()->optional()->catch( null ),
						'max' => Z::number()->optional()->catch( null ),
					)
				)->catch( array() ),
			)
		)->catch(
			array(
				'condition' => 'is-greater-than',
				'interval'  => array(),
			)
		);
	}
	$parsed = $schema->safe_parse( $attributes );
	assert( $parsed['success'] );
	/** @var TAttributes $result */
	$result = $parsed['data'];
	return $result;
}
add_filter( 'nab_nab/window-width_sanitize_segmentation_rule_attributes', __NAMESPACE__ . '\sanitize' );
