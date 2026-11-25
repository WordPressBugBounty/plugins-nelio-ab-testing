<?php

namespace Nelio_AB_Testing\Segmentation_Rule_Library\Location;

defined( 'ABSPATH' ) || exit;

use Nelio_AB_Testing\Zod\Schema;
use Nelio_AB_Testing\Zod\Zod as Z;

/**
 * Sanitizes location segmentation rule attributes.
 *
 * @param TAttributes $attributes Attributes.
 *
 * @return TAttributes Sanitized attributes.
 */
function sanitize( $attributes ) {
	$definition = nelioab()->get_segmentation_rule_type( 'nab/location' );
	$values     = is_array( $definition['options'] ?? null ) ? $definition['options'] : array();
	/** @var Schema|null $schema */
	static $schema;
	if ( empty( $schema ) ) {
		$schema = Z::object(
			array(
				'condition' => Z::enum( array( 'is-equal-to', 'is-not-equal-to' ) )->default( 'is-equal-to' )->catch( 'is-equal-to' ),
				'location'  => Z::array(
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
				'location'  => array(),
			)
		);
	}
	$parsed = $schema->safe_parse( $attributes );
	assert( $parsed['success'] );
	/** @var TAttributes $result */
	$result = $parsed['data'];
	return $result;
}
add_filter( 'nab_nab/location_sanitize_segmentation_rule_attributes', __NAMESPACE__ . '\sanitize' );
