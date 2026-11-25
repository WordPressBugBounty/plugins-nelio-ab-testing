<?php

namespace Nelio_AB_Testing\Experiment_Library\JavaScript_Experiment;

defined( 'ABSPATH' ) || exit;

/**
 * Registers the AI definition for the nab/javascript experiment type.
 *
 * @return TExperiment_Type_AI
 */
function register_ai_definition() {
	return array(
		'selection' => array(
			'strategy'                => 'preferred',
			'requiresExplicitRequest' => false,
			'useWhen'                 => 'Prefer it after checking whether a CSS test is feasible. For every URL where an element or content change is required, request selector discovery first and choose CSS when all requested changes can be implemented with the exact selectors returned for that URL; use JavaScript when CSS cannot express the required changes or when custom browser-side logic is needed. It can target one page or multiple pages through its scope. When using it instead of a full content test for a single piece of content, set its scope to that content’s exact URL and no other URL.',
		),
		'api'       => array(
			'done'               => array(
				'signature'   => '() => void',
				'description' => 'Shows the variant and enables event tracking. A non-empty JavaScript snippet must call this function at least once.',
			),
			'utils.domReady'     => array(
				'signature'   => '(callback: () => unknown) => void',
				'description' => 'Runs a callback when the document’s DOM is ready.',
			),
			'utils.elementReady' => array(
				'signature'   => '(selector: string, callback: (element: HTMLElement, setCss: (style: string) => void) => unknown) => void',
				'description' => 'Runs a callback when an element matching the selector is available in the DOM. The callback receives the element and a helper for applying CSS styles to it.',
			),
			'utils.appendStyle'  => array(
				'signature'   => '(css: string) => void',
				'description' => 'Adds the provided CSS rules to a style element in the document head.',
			),
			'utils.showContent'  => array(
				'signature'   => '() => void',
				'description' => sprintf(
					/* translators: %s: JavaScript function name. */
					'Shows the variant without enabling event tracking. Call `%s` instead when the variant should also be tracked.',
					'done'
				),
			),
		),
		'schema'    => array(
			'original'    => array(
				'type'                 => 'object',
				'properties'           => array(
					'chance' => array(
						'type'        => 'number',
						'description' => 'Controls how often this variant is shown compared with the other variants. Do not set it unless the user explicitly requests unequal variant chances; otherwise, leave it unset so all variants have equal chances.',
					),
					'code'   => array(
						'type'        => 'string',
						'description' => 'JavaScript code applied by the alternative. Leave it empty unless the user explicitly asks for JavaScript code to run on the control version.',
					),
				),
				'additionalProperties' => false,
			),
			'alternative' => array(
				'type'                 => 'object',
				'properties'           => array(
					'chance' => array(
						'type'        => 'number',
						'description' => 'Controls how often this variant is shown compared with the other variants. Do not set it unless the user explicitly requests unequal variant chances; otherwise, leave it unset so all variants have equal chances.',
					),
					'name'   => array(
						'type'        => 'string',
						'description' => 'Name of the alternative.',
					),
					'code'   => array(
						'type'        => 'string',
						'description' => 'JavaScript code applied by the alternative.',
					),
				),
				'required'             => array( 'name', 'code' ),
				'additionalProperties' => false,
			),
		),
	);
}
add_filter( 'nab_nab/javascript_ai_definition', __NAMESPACE__ . '\register_ai_definition' );

/**
 * Validates JavaScript test control attributes.
 *
 * @param TAttributes_Validation $validation Previous validation result.
 * @param TAttributes            $attributes Control attributes.
 *
 * @return TAttributes_Validation Validation result.
 */
function ai_validate_control_attributes( $validation, $attributes ) {
	$sanitized = sanitize_control_attributes( $attributes );
	$issues    = nab_ai_compare_attributes( $attributes, $sanitized );
	foreach ( $issues as $issue ) {
		if ( in_array( $issue['type'], array( 'removed', 'changed' ), true ) ) {
			$validation['issues'][] = $issue['message'];
		}
	}
	return array(
		'status' => empty( $validation['issues'] ) ? 'valid' : 'invalid',
		'issues' => $validation['issues'],
	);
}
add_filter( 'nab_nab/javascript_ai_validate_control_attributes', __NAMESPACE__ . '\ai_validate_control_attributes', 10, 2 );
/**
 * Validates JavaScript test variant attributes.
 *
 * @param TAttributes_Validation $validation Previous validation result.
 * @param TAttributes            $attributes Variant attributes.
 *
 * @return TAttributes_Validation Validation result.
 */
function ai_validate_alternative_attributes( $validation, $attributes ) {
	$sanitized = sanitize_alternative_attributes( $attributes );
	$issues    = nab_ai_compare_attributes( $attributes, $sanitized );
	foreach ( $issues as $issue ) {
		if ( in_array( $issue['type'], array( 'removed', 'changed' ), true ) ) {
			$validation['issues'][] = $issue['message'];
		}
	}
	$code = $attributes['code'] ?? '';
	if ( ! is_string( $code ) || '' === trim( $code ) ) {
		$validation['issues'][] = 'The variant JavaScript code is empty.';
	} elseif ( false === strpos( $code, 'done()' ) ) {
		$validation['issues'][] = 'The variant JavaScript code does not call `done()`.';
	}
	return array(
		'status' => empty( $validation['issues'] ) ? 'valid' : 'invalid',
		'issues' => $validation['issues'],
	);
}
add_filter( 'nab_nab/javascript_ai_validate_alternative_attributes', __NAMESPACE__ . '\ai_validate_alternative_attributes', 10, 2 );
