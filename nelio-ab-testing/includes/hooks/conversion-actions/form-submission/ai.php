<?php

namespace Nelio_AB_Testing\Conversion_Action_Library\Form_Submission;

defined( 'ABSPATH' ) || exit;

/**
 * Registers the AI definition for the nab/form-submission conversion action type.
 *
 * @return TConversion_Action_Type_AI
 */
function register_ai_definition() {
	return array(
		'useWhen' => 'Use it when generating a form lead or completing a form is the goal.',
		'schema'  => array(
			'type'                 => 'object',
			'properties'           => array(
				'formType' => array(
					'type'        => 'string',
					'description' => 'WordPress entity type of the form.',
				),
				'formId'   => array(
					'type'        => 'integer',
					'description' => 'Identifier of the form.',
				),
				'formName' => array(
					'type'        => 'string',
					'description' => 'Name of the form.',
				),
			),
			'required'             => array( 'formType', 'formId' ),
			'additionalProperties' => false,
		),
	);
}
add_filter( 'nab_nab/form-submission_ai_definition', __NAMESPACE__ . '\register_ai_definition' );

/**
 * Validates form-submission conversion action attributes.
 *
 * @param TAttributes_Validation $validation Previous validation result.
 * @param TConversion_Action     $action     Conversion action.
 *
 * @return TAttributes_Validation Validation result.
 */
function ai_validate( $validation, $action ) {
	$attributes = $action['attributes'];
	$sanitized  = sanitize( $attributes );
	$issues     = array_map( fn( $issue ) => $issue['message'], nab_ai_compare_attributes( $attributes, $sanitized ) );

	return array(
		'status' => empty( $issues ) ? 'valid' : 'invalid',
		'issues' => $issues,
	);
}
add_filter( 'nab_nab/form-submission_ai_validate_conversion_action_attributes', __NAMESPACE__ . '\ai_validate', 9, 2 );
