<?php

namespace Nelio_AB_Testing\Experiment_Library\Theme_Experiment;

defined( 'ABSPATH' ) || exit;

use Nelio_AB_Testing\Zod\Schema;
use Nelio_AB_Testing\Zod\Zod as Z;

/**
 * Callback to sanitize alternative attributes.
 *
 * @param TAttributes $alternative Alternative.
 *
 * @return TTheme_Alternative_Attributes
 */
function sanitize_alternative_attributes( $alternative ) {
	/** @var Schema|null */
	static $schema;
	if ( empty( $schema ) ) {
		$schema = Z::object(
			array(
				'name'    => Z::string()->trim()->catch( '' ),
				'themeId' => Z::string()->catch( '' ),
			)
		)->catch(
			array(
				'name'    => '',
				'themeId' => '',
			)
		);
	}

	$parsed = $schema->safe_parse( $alternative );
	assert( $parsed['success'] );
	/** @var TTheme_Alternative_Attributes */
	return $parsed['data'];
}
add_filter( 'nab_nab/theme_sanitize_alternative_attributes', __NAMESPACE__ . '\sanitize_alternative_attributes' );
