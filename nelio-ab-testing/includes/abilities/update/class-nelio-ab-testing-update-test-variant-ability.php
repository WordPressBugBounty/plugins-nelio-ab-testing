<?php
/**
 * Ability for updating a variant in an editable Nelio test.
 */

defined( 'ABSPATH' ) || exit;

/**
 * Updates variants in editable tests.
 */
class Nelio_AB_Testing_Update_Test_Variant_Ability extends Nelio_AB_Testing_Ability {

	/**
	 * Registers the ability with WordPress.
	 *
	 * @return void
	 */
	public function register() {
		wp_register_ability(
			'nelio-ab-testing/update-test-variant',
			array(
				'label'               => _x( 'Update a test variant', 'text (ability name)', 'nelio-ab-testing' ),
				'description'         => 'Replaces and validates a variant in an existing editable Nelio test. Provide the complete set of variant attributes because the supplied attributes replace the current ones. Variant labels are positional, so use the variant UUID when invoking abilities and Variant B, Variant C, or another current label when referring to the variant in messages to the user. Do not set the `chance` attribute here; configure the complete distribution with the dedicated distribution ability.',
				'category'            => 'nelio-ab-testing',
				'input_schema'        => $this->object_schema(
					array(
						'testId'     => array(
							'type'        => 'integer',
							'description' => 'Identifier of the editable test.',
						),
						'variantId'  => array(
							'type'        => 'string',
							'minLength'   => 1,
							'description' => 'Internal UUID returned when the variant was created. Do not use its positional label.',
						),
						'name'       => array(
							'type'        => 'string',
							'description' => 'Brief human-readable description of the changes applied to the variant.',
						),
						'attributes' => array(
							'type'        => 'object',
							'description' => 'Complete set of variant attributes following the test type’s alternative schema. These replace all existing variant attributes. Do not include `chance`; configure it together with every other variant through the dedicated distribution ability.',
						),
					),
					array( 'testId', 'variantId', 'attributes' )
				),
				'output_schema'       => $this->object_schema(
					array(
						'variant' => $this->object_schema(
							array(
								'id'         => array(
									'type'        => 'string',
									'description' => 'Internal UUID of the updated variant.',
								),
								'attributes' => array(
									'type'                 => 'object',
									'description'          => 'Resulting variant attributes defined by the selected test type.',
									'additionalProperties' => true,
								),
							),
							array( 'id', 'attributes' )
						),
					)
				),
				'permission_callback' => array( $this, 'permissions' ),
				'execute_callback'    => array( $this, 'execute' ),
				'meta'                => array(
					'show_in_rest' => false,
					'mcp'          => array(
						'public' => true,
						'type'   => 'tool',
						'server' => 'nelio-ab-testing',
					),
					'annotations'  => array(
						'readonly'    => false,
						'destructive' => false,
						'idempotent'  => true,
					),
				),
			)
		);
	}

	/**
	 * Checks whether the current user may update a test variant.
	 *
	 * @param array<array-key,mixed> $input Input containing the test identifier.
	 *
	 * @return true|WP_Error True when the user can update the variant; otherwise, an error.
	 */
	public function permissions( $input ) {
		$experiment = nab_get_experiment( absint( $input['testId'] ?? 0 ) );
		if ( is_wp_error( $experiment ) ) {
			return $experiment;
		}
		$heatmap_check = $this->reject_heatmap( $experiment );
		if ( is_wp_error( $heatmap_check ) ) {
			return $heatmap_check;
		}

		$can_be_edited = $experiment->can_be_edited();
		if ( is_wp_error( $can_be_edited ) ) {
			return $can_be_edited;
		}

		if ( ! nelioab()->does_test_type_support_ai( $experiment->get_type() ) ) {
			return new WP_Error( 'unsupported-test-type', 'This test type is registered but cannot be configured through AI abilities.' );
		}

		return true;
	}

	/**
	 * Updates and validates a test variant.
	 *
	 * @param array<array-key,mixed> $input Input containing the test identifier, variant identifier, and attributes.
	 *
	 * @return array<string,mixed>|WP_Error The updated variant, or an error.
	 */
	public function execute( $input ) {
		$allowed = $this->permissions( $input );
		if ( is_wp_error( $allowed ) ) {
			return $allowed;
		}
		$experiment = nab_get_experiment( absint( $input['testId'] ?? 0 ) );
		assert( ! is_wp_error( $experiment ) );

		$id = isset( $input['variantId'] ) && is_string( $input['variantId'] ) ? $input['variantId'] : '';
		if ( '' === $id ) {
			return new WP_Error( 'missing-variant-id', 'A variant ID is required to update a variant.' );
		}
		if ( 'control' === $id ) {
			$issue  = 'The control cannot be updated with this ability.';
			$issue .= ' Use the ability that sets the test control instead.';
			return new WP_Error( 'invalid-variant', $issue );
		}

		$current = $experiment->get_alternative( $id );
		if ( ! is_array( $current ) ) {
			return new WP_Error( 'invalid-variant', 'The requested variant does not exist or cannot be updated.' );
		}

		/** @var TAttributes $attributes */
		$attributes        = isset( $input['attributes'] ) && is_array( $input['attributes'] ) ? $input['attributes'] : array();
		$chance_validation = $this->validate_chance_attribute( $attributes );
		if ( is_wp_error( $chance_validation ) ) {
			return $chance_validation;
		}
		if ( array_key_exists( 'chance', $current['attributes'] ) ) {
			$attributes['chance'] = $current['attributes']['chance'];
		}
		if ( is_string( $input['name'] ?? null ) ) {
			$attributes['name'] = sanitize_text_field( $input['name'] );
		}

		$validation = Nelio_AB_Testing_Variant_Manager::ai_validate(
			$experiment->get_type(),
			'alternative',
			$attributes,
			$experiment->ID
		);

		if ( 'valid' !== $validation['status'] ) {
			$issues = implode( ' ', $validation['issues'] );
			if ( empty( $issues ) ) {
				$issues = 'The variant attributes could not be validated.';
			}
			return new WP_Error( 'invalid-variant-attributes', $issues );
		}

		$current['attributes'] = $attributes;
		if ( is_string( $input['name'] ?? null ) ) {
			$current['name'] = sanitize_text_field( $input['name'] );
		}
		$experiment->set_alternative( $current );
		nab_ai_refresh_experiment_status( $experiment );
		$experiment->save();
		return array(
			'variant' => array(
				'id'         => $id,
				'attributes' => $attributes,
			),
		);
	}

	/**
	 * Rejects a chance supplied with the variant attributes.
	 *
	 * Chance values must be configured for every variant together through the
	 * variant distribution ability.
	 *
	 * @param array<array-key,mixed> $attributes Variant attributes.
	 *
	 * @return true|WP_Error True when no chance was supplied; otherwise, an error.
	 */
	private function validate_chance_attribute( $attributes ) {
		if ( ! array_key_exists( 'chance', $attributes ) ) {
			return true;
		}

		return new WP_Error(
			'chance-must-be-set-with-distribution',
			'The `chance` attribute can only be configured by setting the complete test variant distribution.'
		);
	}
}
