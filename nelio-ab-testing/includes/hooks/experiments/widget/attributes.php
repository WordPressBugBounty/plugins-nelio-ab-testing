<?php

namespace Nelio_AB_Testing\Experiment_Library\Widget_Experiment;

defined( 'ABSPATH' ) || exit;

use Nelio_AB_Testing\Zod\Schema;
use Nelio_AB_Testing\Zod\Zod as Z;

/**
 * Callback to sanitize alternative attributes.
 *
 * @param TAttributes $alternative Alternative attributes.
 *
 * @return TWidget_Alternative_Attributes
 */
function sanitize_alternative_attributes( $alternative ) {
	/** @var Schema|null */
	static $schema;
	if ( empty( $schema ) ) {
		$schema = Z::object(
			array(
				'name'     => Z::string()->trim()->catch( '' ),
				'sidebars' => Z::array(
					Z::object(
						array(
							'id'      => Z::string()->trim()->min( 1 ),
							'control' => Z::string()->trim()->min( 1 ),
						)
					)->catch( false )
				)->catch( array() )->transform(
					// @phpstan-ignore-next-line offsetAccess.nonOffsetAccessible
					fn( $v ) => array_values( array_filter( $v ) )
				),
			)
		);
	}

	$parsed = $schema->safe_parse( $alternative );
	assert( $parsed['success'] );
	/** @var TWidget_Alternative_Attributes */
	return $parsed['data'];
}
add_filter( 'nab_nab/widget_sanitize_alternative_attributes', __NAMESPACE__ . '\sanitize_alternative_attributes' );
