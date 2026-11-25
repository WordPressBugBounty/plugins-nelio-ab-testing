<?php
/**
 * Ability for adding a variant to an editable Nelio test.
 *
 * @package Nelio_AB_Testing
 */

defined( 'ABSPATH' ) || exit;

	/**
	 * Adds variants to editable tests.
	 */
class Nelio_AB_Testing_Add_Test_Variant_Ability extends Nelio_AB_Testing_Ability {

	/**
	 * Registers the ability with WordPress.
	 *
	 * @return void
	 */
	public function register() {
		wp_register_ability(
			'nelio-ab-testing/add-test-variant',
			array(
				'label'               => _x( 'Add a test variant', 'text (ability name)', 'nelio-ab-testing' ),
				'description'         => 'Adds and validates a variant for an existing editable Nelio test whose control has already been configured. The first non-control variant is Variant B, the next is Variant C, and so on. The plugin generates the variant identifier; use the UUID when invoking abilities and the positional label when referring to the variant in messages to the user. Do not set the `chance` attribute here; configure the complete distribution with the dedicated distribution ability. Adding a variant clears existing explicit `chance` values, so set the complete distribution again afterward.',
				'category'            => 'nelio-ab-testing',
				'input_schema'        => $this->object_schema(
					array(
						'testId'     => array(
							'type'        => 'integer',
							'description' => 'Identifier of the editable test.',
						),
						'name'       => array(
							'type'        => 'string',
							'description' => 'Brief human-readable description of the changes applied to the variant.',
						),
						'attributes' => array(
							'type'        => 'object',
							'description' => 'Attributes for the variant following the test type’s alternative schema. Do not include `chance`; configure it together with every other variant through the dedicated distribution ability. Use the returned UUID, not its positional label, when referring to the variant later.',
						),
					),
					array( 'testId', 'name', 'attributes' )
				),
				'output_schema'       => $this->object_schema(
					array(
						'variant' => $this->object_schema(
							array(
								'id'         => array(
									'type'        => 'string',
									'description' => 'Internal UUID generated for the variant.',
								),
								'attributes' => array(
									'type'                 => 'object',
									'description'          => 'Variant attributes defined by the selected test type.',
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
						'idempotent'  => false,
					),
				),
			)
		);
	}

	/**
	 * Checks whether the current user can add a variant to a test.
	 *
	 * @param array<array-key,mixed> $input Ability input containing the test identifier.
	 *
	 * @return true|WP_Error True when the user has permission; otherwise, an error explaining why the test cannot be edited.
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
	 * Adds a validated variant to an existing editable test.
	 *
	 * The variant is assigned a new UUID and is based on the test control. The UUID is returned for future ability calls; its positional label should be used only when communicating with the user.
	 *
	 * @param array<array-key,mixed> $input Ability input containing the test identifier, variant name, and variant attributes.
	 *
	 * @return array<string,mixed>|WP_Error The created variant, or an error when the test cannot be edited or its attributes are invalid.
	 */
	public function execute( $input ) {
		$allowed = $this->permissions( $input );
		if ( is_wp_error( $allowed ) ) {
			return $allowed;
		}

		$experiment = nab_get_experiment( absint( $input['testId'] ?? 0 ) );
		assert( ! is_wp_error( $experiment ) );

		$name = is_string( $input['name'] ?? null ) ? sanitize_text_field( $input['name'] ) : '';
		if ( '' === $name ) {
			return new WP_Error( 'invalid-variant-name', 'The variant name cannot be empty.' );
		}

		/** @var TAttributes $attributes */
		$attributes        = isset( $input['attributes'] ) && is_array( $input['attributes'] ) ? $input['attributes'] : array();
		$chance_validation = $this->validate_chance_attribute( $attributes );
		if ( is_wp_error( $chance_validation ) ) {
			return $chance_validation;
		}
		$attributes['name'] = $name;
		$validation         = Nelio_AB_Testing_Variant_Manager::ai_validate(
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

		$id                        = nab_uuid();
		$alternatives              = $experiment->get_alternatives();
		$has_explicit_distribution = false;
		foreach ( $alternatives as $alternative ) {
			if ( is_numeric( $alternative['attributes']['chance'] ?? null ) ) {
				$has_explicit_distribution = true;
				break;
			}
		}
		if ( $has_explicit_distribution ) {
			foreach ( $alternatives as &$alternative ) {
				unset( $alternative['attributes']['chance'] );
			}
			unset( $alternative );
		}
		$alternatives[] = array(
			'id'         => $id,
			'base'       => 'control',
			'attributes' => $attributes,
		);
		$experiment->set_alternatives( $alternatives );
		nab_ai_refresh_experiment_status( $experiment );
		$experiment->save();
		return array(
			'variant' => array(
				'id'         => $id,
				'attributes' => $experiment->get_alternative( $id )['attributes'] ?? array(),
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
