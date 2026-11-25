<?php

namespace Nelio_AB_Testing\Experiment_Library\Menu_Experiment;

defined( 'ABSPATH' ) || exit;

/**
 * Registers the AI definition for the nab/menu experiment type.
 *
 * @return TExperiment_Type_AI
 */
function register_ai_definition() {
	return array(
		'selection' => array(
			'strategy'                => 'explicit_only',
			'requiresExplicitRequest' => true,
			'useWhen'                 => 'Use it only when the user explicitly asks to compare navigation menus or menu structures rather than the broader content and layout of a page. Before assigning a menu ID, identify the corresponding existing menu and inspect its structure; this applies whether the menu is used as the original or as an existing variant.',
		),
		'schema'    => array(
			'original'    => array(
				'type'                 => 'object',
				'properties'           => array(
					'chance'                  => array(
						'type'        => 'number',
						'description' => 'Controls how often this variant is shown compared with the other variants. Do not set it unless the user explicitly requests unequal variant chances; otherwise, leave it unset so all variants have equal chances.',
					),
					'menuId'                  => array(
						'type'        => 'integer',
						'description' => 'Identifier of the control menu.',
					),
					'testAgainstExistingMenu' => array(
						'type'        => 'boolean',
						'description' => 'Indicates that the alternative uses an existing published menu.',
					),
				),
				'required'             => array( 'menuId' ),
				'additionalProperties' => false,
			),
			'alternative' => array(
				'type'                 => 'object',
				'properties'           => array(
					'chance'         => array(
						'type'        => 'number',
						'description' => 'Controls how often this variant is shown compared with the other variants. Do not set it unless the user explicitly requests unequal variant chances; otherwise, leave it unset so all variants have equal chances.',
					),
					'name'           => array(
						'type'        => 'string',
						'description' => 'Name of the alternative menu.',
					),
					'menuId'         => array(
						'type'        => 'integer',
						'description' => 'Identifier of an existing alternative menu. Leave it unset when the plugin should create the alternative automatically; set it only when testing an existing menu.',
					),
					'isExistingMenu' => array(
						'type'        => 'boolean',
						'const'       => true,
						'description' => 'Indicates that the alternative uses an existing published menu.',
					),
				),
				'required'             => array( 'name' ),
				'additionalProperties' => false,
			),
		),
	);
}
add_filter( 'nab_nab/menu_ai_definition', __NAMESPACE__ . '\register_ai_definition' );

/**
 * Validates menu test control attributes.
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
	if ( empty( $attributes['menuId'] ) ) {
		$validation['issues'][] = 'The control menu ID is missing.';
	}
	return array(
		'status' => empty( $validation['issues'] ) ? 'valid' : 'invalid',
		'issues' => $validation['issues'],
	);
}
add_filter( 'nab_nab/menu_ai_validate_control_attributes', __NAMESPACE__ . '\ai_validate_control_attributes', 10, 2 );
/**
 * Validates menu test variant attributes.
 *
 * @param TAttributes_Validation $validation Previous validation result.
 * @param TAttributes            $attributes Variant attributes.
 * @param int                    $experiment_id Experiment identifier.
 *
 * @return TAttributes_Validation Validation result.
 */
function ai_validate_alternative_attributes( $validation, $attributes, $experiment_id ) {
	$sanitized = sanitize_alternative_attributes( $attributes );
	$issues    = nab_ai_compare_attributes( $attributes, $sanitized );
	foreach ( $issues as $issue ) {
		if ( in_array( $issue['type'], array( 'removed', 'changed' ), true ) ) {
			$validation['issues'][] = $issue['message'];
		}
	}
	$experiment = $experiment_id ? nab_get_experiment( absint( $experiment_id ) ) : null;
	$control    = is_object( $experiment ) && ! is_wp_error( $experiment ) ? $experiment->get_alternative( 'control' ) : array();
	if ( ! empty( $control['attributes']['testAgainstExistingMenu'] ) && empty( $attributes['menuId'] ) ) {
		$validation['issues'][] = 'An existing menu ID is required when the test compares existing menus.';
	}
	return array(
		'status' => empty( $validation['issues'] ) ? 'valid' : 'invalid',
		'issues' => $validation['issues'],
	);
}
add_filter( 'nab_nab/menu_ai_validate_alternative_attributes', __NAMESPACE__ . '\ai_validate_alternative_attributes', 10, 3 );
