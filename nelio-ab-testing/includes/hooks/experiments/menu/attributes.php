<?php

namespace Nelio_AB_Testing\Experiment_Library\Menu_Experiment;

defined( 'ABSPATH' ) || exit;

use Nelio_AB_Testing\Zod\Schema;
use Nelio_AB_Testing\Zod\Zod as Z;

/**
 * Sanitizes control attributes.
 *
 * @param TAttributes $control Control.
 *
 * @return TMenu_Control_Attributes
 */
function sanitize_control_attributes( $control ) {
	/** @var Schema|null */
	static $schema;
	if ( empty( $schema ) ) {
		$schema = Z::object(
			array(
				'menuId'                  => Z::number()->catch( 0 ),
				'testAgainstExistingMenu' => Z::boolean()->optional()->transform( 'nab_nullify' ),
			)
		)->catch( array( 'menuId' => 0 ) );
	}

	$parsed = $schema->safe_parse( $control );
	assert( $parsed['success'] );
	/** @var TMenu_Control_Attributes */
	return $parsed['data'];
}
add_filter( 'nab_nab/menu_sanitize_control_attributes', __NAMESPACE__ . '\sanitize_control_attributes' );

/**
 * Sanitizes alternative attributes.
 *
 * @param TAttributes $alternative Alternative.
 *
 * @return TMenu_Alternative_Attributes
 */
function sanitize_alternative_attributes( $alternative ) {
	/** @var Schema|null */
	static $schema;
	if ( empty( $schema ) ) {
		$schema = Z::object(
			array(
				'name'                    => Z::string()->default( '' )->trim(),
				'menuId'                  => Z::number()->default( 0 ),
				'isExistingMenu'          => Z::boolean()->optional()->transform( 'nab_nullify' ),
				// DEPRECATED. This attribute is here because we used to backup control attributes.
				'testAgainstExistingMenu' => Z::boolean()->optional()->transform( 'nab_nullify' ),
			)
		)->transform(
			// DEPRECATED. This transform is here because we used to backup control attributes and we need to modernize them.
			function ( $v ) {
				/** @var array<mixed> $v */
				if ( ! empty( $v['testAgainstExistingMenu'] ) ) {
					$v['isExistingMenu'] = true;
					unset( $v['testAgainstExistingMenu'] );
				}
				return $v;
			}
		);
	}

	$parsed = $schema->safe_parse( $alternative );
	assert( $parsed['success'] );
	/** @var TMenu_Alternative_Attributes */
	return $parsed['data'];
}
add_filter( 'nab_nab/menu_sanitize_alternative_attributes', __NAMESPACE__ . '\sanitize_alternative_attributes' );
