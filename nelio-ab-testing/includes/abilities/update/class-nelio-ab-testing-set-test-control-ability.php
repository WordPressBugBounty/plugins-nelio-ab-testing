<?php
/**
 * Ability for configuring the control of an editable Nelio test.
 */

defined( 'ABSPATH' ) || exit;

/**
 * Configures controls in editable tests.
 */
class Nelio_AB_Testing_Set_Test_Control_Ability extends Nelio_AB_Testing_Ability {

	/**
	 * Registers the ability with WordPress.
	 *
	 * @return void
	 */
	public function register() {
		wp_register_ability(
			'nelio-ab-testing/set-test-control',
			array(
				'label'               => _x( 'Set the test control', 'text (ability name)', 'nelio-ab-testing' ),
				'description'         => 'Replaces and validates the complete control attributes of an existing editable Nelio test. The supplied attributes replace the current control attributes; they are not merged. The control is also called Variant A; use that positional label when referring to it in messages to the user. Do not set the `chance` attribute here; configure the complete distribution with the dedicated distribution ability. Do not use this ability to bypass a goal whose conversion actions are managed automatically. If changing the control would indirectly alter those actions, report that the requested action cannot be performed.',
				'category'            => 'nelio-ab-testing',
				'input_schema'        => $this->object_schema(
					array(
						'testId'     => array(
							'type'        => 'integer',
							'description' => 'Identifier of the editable test.',
						),
						'attributes' => array(
							'type'        => 'object',
							'description' => 'Complete set of attributes for the control, also called Variant A, following the selected test type’s original schema. These replace all existing control attributes; they are not merged. Do not include `chance`; configure it together with every other variant through the dedicated distribution ability.',
						),
					),
					array( 'testId', 'attributes' )
				),
				'output_schema'       => $this->object_schema(
					array(
						'control' => $this->object_schema(
							array(
								'id'         => array(
									'type'        => 'string',
									'description' => 'Identifier of the control variant. The plugin exposes the control using the literal `control` identifier.',
								),
								'attributes' => array(
									'type'                 => 'object',
									'description'          => 'Control attributes defined by the selected test type.',
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
	 * Checks whether the current user may configure the test control.
	 *
	 * @param array<array-key,mixed> $input Input containing the test identifier.
	 *
	 * @return true|WP_Error True when the user can configure the control; otherwise, an error.
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
	 * Replaces and validates the complete set of test control attributes.
	 *
	 * The supplied attributes replace the current control attributes; no partial update or automatic merge is performed.
	 *
	 * @param array<array-key,mixed> $input Input containing the test identifier and control attributes.
	 *
	 * @return array<string,mixed>|WP_Error The configured control, or an error.
	 */
	public function execute( $input ) {
		$allowed = $this->permissions( $input );
		if ( is_wp_error( $allowed ) ) {
			return $allowed;
		}

		$experiment = nab_get_experiment( absint( $input['testId'] ?? 0 ) );
		assert( ! is_wp_error( $experiment ) );

		/** @var TAttributes $attributes */
		$attributes        = isset( $input['attributes'] ) && is_array( $input['attributes'] ) ? $input['attributes'] : array();
		$chance_validation = $this->validate_chance_attribute( $attributes );
		if ( is_wp_error( $chance_validation ) ) {
			return $chance_validation;
		}
		$validation = Nelio_AB_Testing_Variant_Manager::ai_validate(
			$experiment->get_type(),
			'control',
			$attributes,
			$experiment->ID
		);

		if ( 'valid' !== $validation['status'] ) {
			$issues = implode( ' ', $validation['issues'] );
			if ( empty( $issues ) ) {
				$issues = 'The control attributes could not be validated.';
			}
			return new WP_Error( 'invalid-control-attributes', $issues );
		}

		/** @var list<TAlternative> $alternatives */
		$alternatives = $experiment->get_alternatives();
		unset( $alternatives[0] );
		$control      = array(
			'id'         => 'control',
			'attributes' => $attributes,
		);
		$alternatives = array_merge( array( $control ), array_values( $alternatives ) );

		$experiment->set_alternatives( $alternatives );
		nab_ai_refresh_experiment_status( $experiment );
		$experiment->save();
		$control = $experiment->get_alternative( 'control' );
		return array(
			'control' => array(
				'id'         => $control['id'],
				'attributes' => $control['attributes'],
			),
		);
	}

	/**
	 * Rejects a chance supplied with the control attributes.
	 *
	 * Chance values must be configured for every variant together through the
	 * variant distribution ability.
	 *
	 * @param array<array-key,mixed> $attributes Control attributes.
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
