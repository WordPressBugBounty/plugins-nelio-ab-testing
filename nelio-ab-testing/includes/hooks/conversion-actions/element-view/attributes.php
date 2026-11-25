<?php

namespace Nelio_AB_Testing\Conversion_Action_Library\Element_View;

defined( 'ABSPATH' ) || exit;

use Nelio_AB_Testing\Zod\Schema;
use Nelio_AB_Testing\Zod\Zod as Z;

/**
 * Sanitizes element-view conversion action attributes.
 *
 * @param TAttributes $attributes Attributes.
 *
 * @return TAttributes
 */
function sanitize( $attributes ) {
	/** @var Schema|null */
	static $schema;
	if ( empty( $schema ) ) {
		$schema = Z::object(
			array(
				'mode'  => Z::union( array( Z::literal( 'css' ), Z::literal( 'id' ), Z::literal( 'class' ) ) )->default( 'css' )->catch( 'css' ),
				'value' => Z::string()->trim()->catch( '' ),
			)
		)->catch(
			array(
				'mode'  => 'css',
				'value' => '',
			)
		);
	}

	$parsed = $schema->safe_parse( $attributes );
	assert( $parsed['success'] );
	/** @var TAttributes */
	return $parsed['data'];
}
add_filter( 'nab_nab/element-view_sanitize_conversion_action_attributes', __NAMESPACE__ . '\sanitize' );
