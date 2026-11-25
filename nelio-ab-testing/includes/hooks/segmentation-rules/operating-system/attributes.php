<?php

namespace Nelio_AB_Testing\Segmentation_Rule_Library\Operating_System;

defined( 'ABSPATH' ) || exit;

use Nelio_AB_Testing\Zod\Schema;
use Nelio_AB_Testing\Zod\Zod as Z;

/**
 * Sanitizes operating-system segmentation rule attributes.
 *
 * @param TAttributes $attributes Attributes.
 *
 * @return TAttributes Sanitized attributes.
 */
function sanitize( $attributes ) {
	$definition = nelioab()->get_segmentation_rule_type( 'nab/operating-system' );
	$values     = is_array( $definition['options'] ?? null ) ? $definition['options'] : array();
	/** @var Schema|null $schema */
	static $schema;
	if ( empty( $schema ) ) {
		$schema = Z::object(
			array(
				'condition' => Z::enum( array( 'is-equal-to', 'is-not-equal-to' ) )->default( 'is-equal-to' )->catch( 'is-equal-to' ),
				'values'    => Z::array(
					Z::object(
						array(
							'value' => Z::enum( array_keys( $values ) ? array_keys( $values ) : array( '' ) )->catch( '' ),
							'label' => Z::string()->trim()->catch( '' ),
						)
					)->catch(
						array(
							'value' => '',
							'label' => '',
						)
					)
				)->catch( array() ),
			)
		)->catch(
			array(
				'condition' => 'is-equal-to',
				'values'    => array(),
			)
		);
	}
	$parsed = $schema->safe_parse( $attributes );
	assert( $parsed['success'] );
	/** @var TAttributes $result */
	$result = $parsed['data'];
	return $result;
}
add_filter( 'nab_nab/operating-system_sanitize_segmentation_rule_attributes', __NAMESPACE__ . '\sanitize' );
