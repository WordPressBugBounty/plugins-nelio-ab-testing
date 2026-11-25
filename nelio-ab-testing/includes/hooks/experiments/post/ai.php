<?php

namespace Nelio_AB_Testing\Experiment_Library\Post_Experiment;

defined( 'ABSPATH' ) || exit;

/**
 * Registers the AI definition for the nab/page experiment type.
 *
 * @return TExperiment_Type_AI
 */
function register_page_ai_definition() {
	return array(
		'selection' => array(
			'strategy'                => 'fallback',
			'requiresExplicitRequest' => false,
			'useWhen'                 => 'Use it when the alternative should replace substantial page content or when CSS and JavaScript cannot express the requested change conveniently. For simple changes, request selector discovery and choose a CSS test if every required change is feasible with the selectors returned for the target URLs; otherwise, consider JavaScript before using a full content test. Before assigning the control content, discover an existing page and use its returned identifier.',
		),
		'schema'    => array(
			'original'    => array(
				'type'                 => 'object',
				'properties'           => array(
					'chance'                     => array(
						'type'        => 'number',
						'description' => 'Controls how often this variant is shown compared with the other variants. Do not set it unless the user explicitly requests unequal variant chances; otherwise, leave it unset so all variants have equal chances.',
					),
					'postType'                   => array(
						'type'        => 'string',
						'const'       => 'page',
						'description' => 'Registered content type of the control content.',
					),
					'postId'                     => array(
						'type'        => 'integer',
						'description' => 'Identifier of the control content.',
					),
					'testAgainstExistingContent' => array(
						'type'        => 'boolean',
						'description' => 'Indicates that the alternative uses existing published content.',
					),
					'useControlUrl'              => array(
						'type'        => 'boolean',
						'description' => 'Replaces the alternative URL with the control URL after the alternative loads.',
					),
				),
				'required'             => array( 'postType', 'postId' ),
				'additionalProperties' => false,
			),
			'alternative' => array(
				'type'                 => 'object',
				'properties'           => array(
					'chance'            => array(
						'type'        => 'number',
						'description' => 'Controls how often this variant is shown compared with the other variants. Do not set it unless the user explicitly requests unequal variant chances; otherwise, leave it unset so all variants have equal chances.',
					),
					'name'              => array(
						'type'        => 'string',
						'description' => 'Name of the alternative content.',
					),
					'postId'            => array(
						'type'        => 'integer',
						'description' => 'Identifier of existing alternative content. Leave it unset when the plugin should create the alternative automatically; set it only when testing existing content.',
					),
					'isExistingContent' => array(
						'type'        => 'boolean',
						'const'       => true,
						'description' => 'Indicates that the alternative uses existing published content.',
					),
				),
				'required'             => array( 'name' ),
				'additionalProperties' => false,
			),
		),
	);
}
add_filter( 'nab_nab/page_ai_definition', __NAMESPACE__ . '\register_page_ai_definition' );

/**
 * Registers the AI definition for the nab/post experiment type.
 *
 * @return TExperiment_Type_AI
 */
function register_post_ai_definition() {
	return array(
		'selection' => array(
			'strategy'                => 'fallback',
			'requiresExplicitRequest' => false,
			'useWhen'                 => 'Use it when the alternative should replace substantial post content or when CSS and JavaScript cannot express the requested change conveniently. For simple changes, request selector discovery and choose a CSS test if every required change is feasible with the selectors returned for the target URLs; otherwise, consider JavaScript before using a full content test. Before assigning the control content, discover an existing post and use its returned identifier.',
		),
		'schema'    => array(
			'original'    => array(
				'type'                 => 'object',
				'properties'           => array(
					'chance'                     => array(
						'type'        => 'number',
						'description' => 'Controls how often this variant is shown compared with the other variants. Do not set it unless the user explicitly requests unequal variant chances; otherwise, leave it unset so all variants have equal chances.',
					),
					'postType'                   => array(
						'type'        => 'string',
						'const'       => 'post',
						'description' => 'Registered content type of the control content.',
					),
					'postId'                     => array(
						'type'        => 'integer',
						'description' => 'Identifier of the control content.',
					),
					'testAgainstExistingContent' => array(
						'type'        => 'boolean',
						'description' => 'Indicates that the alternative uses existing published content.',
					),
					'useControlUrl'              => array(
						'type'        => 'boolean',
						'description' => 'Replaces the alternative URL with the control URL after the alternative loads.',
					),
				),
				'required'             => array( 'postType', 'postId' ),
				'additionalProperties' => false,
			),
			'alternative' => array(
				'type'                 => 'object',
				'properties'           => array(
					'chance'            => array(
						'type'        => 'number',
						'description' => 'Controls how often this variant is shown compared with the other variants. Do not set it unless the user explicitly requests unequal variant chances; otherwise, leave it unset so all variants have equal chances.',
					),
					'name'              => array(
						'type'        => 'string',
						'description' => 'Name of the alternative content.',
					),
					'postId'            => array(
						'type'        => 'integer',
						'description' => 'Identifier of existing alternative content. Leave it unset when the plugin should create the alternative automatically; set it only when testing existing content.',
					),
					'isExistingContent' => array(
						'type'        => 'boolean',
						'const'       => true,
						'description' => 'Indicates that the alternative uses existing published content.',
					),
				),
				'required'             => array( 'name' ),
				'additionalProperties' => false,
			),
		),
	);
}
add_filter( 'nab_nab/post_ai_definition', __NAMESPACE__ . '\register_post_ai_definition' );

/**
 * Registers the AI definition for the nab/custom-post-type experiment type.
 *
 * @return TExperiment_Type_AI
 */
function register_custom_post_type_ai_definition() {
	return array(
		'selection' => array(
			'strategy'                => 'fallback',
			'requiresExplicitRequest' => false,
			'useWhen'                 => 'Use it when the alternative should replace substantial custom post type content or when CSS and JavaScript cannot express the requested change conveniently. For simple changes, request selector discovery and choose a CSS test if every required change is feasible with the selectors returned for the target URLs; otherwise, consider JavaScript before using a full content test. Before assigning the control content, discover an existing item of the required custom post type and use its returned identifier.',
		),
		'schema'    => array(
			'original'    => array(
				'type'                 => 'object',
				'properties'           => array(
					'chance'                     => array(
						'type'        => 'number',
						'description' => 'Controls how often this variant is shown compared with the other variants. Do not set it unless the user explicitly requests unequal variant chances; otherwise, leave it unset so all variants have equal chances.',
					),
					'postType'                   => array(
						'type'        => 'string',
						'description' => 'Registered content type of the control content.',
					),
					'postId'                     => array(
						'type'        => 'integer',
						'description' => 'Identifier of the control content.',
					),
					'testAgainstExistingContent' => array(
						'type'        => 'boolean',
						'description' => 'Indicates that the alternative uses existing published content.',
					),
					'useControlUrl'              => array(
						'type'        => 'boolean',
						'description' => 'Replaces the alternative URL with the control URL after the alternative loads.',
					),
				),
				'required'             => array( 'postType', 'postId' ),
				'additionalProperties' => false,
			),
			'alternative' => array(
				'type'                 => 'object',
				'properties'           => array(
					'chance'            => array(
						'type'        => 'number',
						'description' => 'Controls how often this variant is shown compared with the other variants. Do not set it unless the user explicitly requests unequal variant chances; otherwise, leave it unset so all variants have equal chances.',
					),
					'name'              => array(
						'type'        => 'string',
						'description' => 'Name of the alternative content.',
					),
					'postId'            => array(
						'type'        => 'integer',
						'description' => 'Identifier of existing alternative content. Leave it unset when the plugin should create the alternative automatically; set it only when testing existing content.',
					),
					'isExistingContent' => array(
						'type'        => 'boolean',
						'const'       => true,
						'description' => 'Indicates that the alternative uses existing published content.',
					),
				),
				'required'             => array( 'name' ),
				'additionalProperties' => false,
			),
		),
	);
}
add_filter( 'nab_nab/custom-post-type_ai_definition', __NAMESPACE__ . '\register_custom_post_type_ai_definition' );

/**
 * Validates page, post, and custom post type test control attributes.
 *
 * @param TAttributes_Validation $validation Previous validation result.
 * @param TAttributes            $attributes Control attributes.
 * @param int                    $experiment_id Experiment identifier.
 *
 * @return TAttributes_Validation Validation result.
 */
function ai_validate_control_attributes( $validation, $attributes, $experiment_id ) {
	$sanitized = sanitize_control_attributes( $attributes );
	$issues    = nab_ai_compare_attributes( $attributes, $sanitized );
	foreach ( $issues as $issue ) {
		if ( in_array( $issue['type'], array( 'removed', 'changed' ), true ) ) {
			$validation['issues'][] = $issue['message'];
		}
	}

	$experiment      = $experiment_id ? nab_get_experiment( absint( $experiment_id ) ) : null;
	$experiment_type = is_object( $experiment ) && ! is_wp_error( $experiment ) ? $experiment->get_type() : '';
	$post_type       = isset( $attributes['postType'] ) && is_string( $attributes['postType'] ) ? $attributes['postType'] : null;

	if ( 'nab/page' === $experiment_type ) {
		if ( 'page' !== ( $attributes['postType'] ?? null ) ) {
			$validation['issues'][] = 'The `postType` attribute is not `page`, which is required for page tests.';
		}
	} elseif ( 'nab/post' === $experiment_type ) {
		if ( 'post' !== ( $attributes['postType'] ?? null ) ) {
			$validation['issues'][] = 'The `postType` attribute is not `post`, which is required for post tests.';
		}
	} elseif ( ! is_string( $post_type ) || ! in_array( $post_type, get_testable_custom_post_types(), true ) ) {
		$validation['issues'][] = 'The `postType` attribute does not contain a supported custom post type.';
	}

	if ( ! empty( $attributes['useControlUrl'] ) && empty( $attributes['testAgainstExistingContent'] ) ) {
		$validation['issues'][] = 'The `useControlUrl` attribute is enabled while `testAgainstExistingContent` is disabled.';
	}

	$post_id = absint( $attributes['postId'] ?? 0 );
	if ( ! $post_id ) {
		$validation['issues'][] = 'The control `postId` is missing or invalid.';
	} else {
		$post = get_post( $post_id );
		if ( ! $post ) {
			$validation['issues'][] = 'The `postId` attribute does not identify existing WordPress content.';
		} elseif ( is_string( $post_type ) && $post->post_type !== $post_type ) {
			$validation['issues'][] = sprintf(
				'The content identified by `postId` has type `%1$s`, but the supplied `postType` is `%2$s`.',
				$post->post_type,
				$post_type
			);
		}
	}

	return array(
		'status' => empty( $validation['issues'] ) ? 'valid' : 'invalid',
		'issues' => $validation['issues'],
	);
}
add_filter( 'nab_nab/page_ai_validate_control_attributes', __NAMESPACE__ . '\ai_validate_control_attributes', 10, 3 );
add_filter( 'nab_nab/post_ai_validate_control_attributes', __NAMESPACE__ . '\ai_validate_control_attributes', 10, 3 );
add_filter( 'nab_nab/custom-post-type_ai_validate_control_attributes', __NAMESPACE__ . '\ai_validate_control_attributes', 10, 3 );

/**
 * Validates page, post, and custom post type test variant attributes.
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

	$experiment         = $experiment_id ? nab_get_experiment( absint( $experiment_id ) ) : null;
	$control            = is_object( $experiment ) && ! is_wp_error( $experiment ) ? $experiment->get_alternative( 'control' ) : array();
	$control_attributes = is_array( $control['attributes'] ?? null ) ? $control['attributes'] : array();
	$control_id         = absint( $control_attributes['postId'] ?? 0 );
	if ( ! $control_id ) {
		$validation['issues'][] = 'A variant cannot be defined until the test control has been selected.';
	} else {
		$control_post_type = $control_attributes['postType'] ?? null;
		$alternative_id    = absint( $attributes['postId'] ?? 0 );

		if ( ! empty( $control_attributes['testAgainstExistingContent'] ) && ! $alternative_id ) {
			$validation['issues'][] = 'The `postId` attribute is missing even though the test compares existing content.';
		}

		if ( $alternative_id ) {
			$alternative_post = get_post( $alternative_id );
			if ( ! $alternative_post ) {
				$validation['issues'][] = 'The `postId` attribute does not identify existing WordPress content.';
			} elseif ( is_string( $control_post_type ) && $alternative_post->post_type !== $control_post_type ) {
				$validation['issues'][] = sprintf(
					'The variant content must have the same post type as the control. The control uses `%1$s`, but the variant uses `%2$s`.',
					$control_post_type,
					$alternative_post->post_type
				);
			}
		}
	}

	return array(
		'status' => empty( $validation['issues'] ) ? 'valid' : 'invalid',
		'issues' => $validation['issues'],
	);
}
add_filter( 'nab_nab/page_ai_validate_alternative_attributes', __NAMESPACE__ . '\ai_validate_alternative_attributes', 10, 3 );
add_filter( 'nab_nab/post_ai_validate_alternative_attributes', __NAMESPACE__ . '\ai_validate_alternative_attributes', 10, 3 );
add_filter( 'nab_nab/custom-post-type_ai_validate_alternative_attributes', __NAMESPACE__ . '\ai_validate_alternative_attributes', 10, 3 );
