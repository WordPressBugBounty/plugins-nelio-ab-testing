<?php

namespace Nelio_AB_Testing\Conversion_Action_Library\Form_Submission;

defined( 'ABSPATH' ) || exit;

use Nelio_AB_Testing\Zod\Schema;
use Nelio_AB_Testing\Zod\Zod as Z;

/**
 * Sanitizes form-submission conversion action attributes.
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
				'formType' => Z::string()->trim()->catch( '' ),
				'formId'   => Z::number()->transform( fn( $v ) => absint( $v ) )->default( 0 )->catch( 0 ),
				'formName' => Z::string()->trim()->optional()->catch( null ),
			)
		)->catch(
			array(
				'formType' => '',
				'formId'   => 0,
			)
		);
	}

	$parsed = $schema->safe_parse( $attributes );
	assert( $parsed['success'] );
	/** @var TAttributes */
	return $parsed['data'];
}
add_filter( 'nab_nab/form-submission_sanitize_conversion_action_attributes', __NAMESPACE__ . '\sanitize', 9 );
