<?php

namespace Nelio_AB_Testing\Conversion_Action_Library\Click;

defined( 'ABSPATH' ) || exit;

use Nelio_AB_Testing\Zod\Schema;
use Nelio_AB_Testing\Zod\Zod as Z;

/**
 * Sanitizes click conversion action attributes.
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
				'mode'  => Z::union(
					array(
						Z::literal( 'css' ),
						Z::literal( 'id' ),
						Z::literal( 'class' ),
					)
				)->default( 'css' )->catch( 'css' ),
				'value' => Z::string()->trim()->catch( '' ),
				'delay' => Z::number()->optional()->catch( null ),
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
add_filter( 'nab_nab/click_sanitize_conversion_action_attributes', __NAMESPACE__ . '\sanitize' );
