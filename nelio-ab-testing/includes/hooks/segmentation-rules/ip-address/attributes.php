<?php

namespace Nelio_AB_Testing\Segmentation_Rule_Library\Ip_Address;

defined( 'ABSPATH' ) || exit;

use Nelio_AB_Testing\Zod\Schema;
use Nelio_AB_Testing\Zod\Zod as Z;

/**
 * Sanitizes IP-address segmentation rule attributes.
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
				'condition' => Z::enum( array( 'is-equal-to', 'is-not-equal-to' ) )->default( 'is-equal-to' )->catch( 'is-equal-to' ),
				'values'    => Z::array( Z::string()->trim()->catch( '' ) )->catch( array() ),
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
add_filter( 'nab_nab/ip-address_sanitize_segmentation_rule_attributes', __NAMESPACE__ . '\sanitize' );
