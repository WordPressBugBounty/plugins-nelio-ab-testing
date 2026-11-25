<?php

namespace Nelio_AB_Testing\Conversion_Action_Library\Click_External_Link;

defined( 'ABSPATH' ) || exit;

use Nelio_AB_Testing\Zod\Schema;
use Nelio_AB_Testing\Zod\Zod as Z;

/**
 * Sanitizes click-external-link conversion action attributes.
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
						Z::literal( 'exact' ),
						Z::literal( 'partial' ),
						Z::literal( 'start' ),
						Z::literal( 'end' ),
						Z::literal( 'regex' ),
					)
				)->default( 'exact' )->catch( 'exact' ),
				'value' => Z::string()->trim()->catch( '' ),
				'delay' => Z::number()->optional()->catch( null ),
			)
		)->catch(
			array(
				'mode'  => 'exact',
				'value' => '',
			)
		);
	}

	$parsed = $schema->safe_parse( $attributes );
	assert( $parsed['success'] );
	/** @var TAttributes */
	return $parsed['data'];
}
add_filter( 'nab_nab/click-external-link_sanitize_conversion_action_attributes', __NAMESPACE__ . '\sanitize' );
