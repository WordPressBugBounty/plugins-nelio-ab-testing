<?php

namespace Nelio_AB_Testing\Segmentation_Rule_Library\Referring_Url;

defined( 'ABSPATH' ) || exit;

use Nelio_AB_Testing\Zod\Schema;
use Nelio_AB_Testing\Zod\Zod as Z;

/**
 * Sanitizes referring-URL segmentation rule attributes.
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
				'condition' => Z::enum( array( 'is-any-of', 'is-none-of', 'is-equal-to', 'is-not-equal-to', 'contains', 'does-not-contain' ) )->default( 'is-equal-to' )->catch( 'is-equal-to' ),
				'value'     => Z::string()->trim()->catch( '' ),
			)
		)->catch(
			array(
				'condition' => 'is-equal-to',
				'value'     => '',
			)
		);
	}
	$parsed = $schema->safe_parse( $attributes );
	assert( $parsed['success'] );
	/** @var TAttributes $result */
	$result = $parsed['data'];
	return $result;
}
add_filter( 'nab_nab/referring-url_sanitize_segmentation_rule_attributes', __NAMESPACE__ . '\sanitize' );
