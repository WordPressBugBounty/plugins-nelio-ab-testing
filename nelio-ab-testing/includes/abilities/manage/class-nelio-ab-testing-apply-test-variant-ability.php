<?php
/**
 * Ability for applying a variant of a Nelio test.
 *
 * @package Nelio_AB_Testing
 */

defined( 'ABSPATH' ) || exit;

/** Applies a finished test variant or restores its control version. */
class Nelio_AB_Testing_Apply_Test_Variant_Ability extends Nelio_AB_Testing_Ability {

	/**
	 * Registers the ability with WordPress.
	 *
	 * @return void
	 */
	public function register() {
		wp_register_ability(
			'nelio-ab-testing/apply-test-variant',
			array(
				'label'               => _x( 'Apply a test variant', 'text (ability name)', 'nelio-ab-testing' ),
				'description'         => 'Applies a variant from a finished Nelio test to the live site. Use a variant UUID to apply that variant, or use `control` to restore the original control version after a variant has been applied. The operation is available only when the test type supports applying variants and the current user can edit tests.',
				'category'            => 'nelio-ab-testing',
				'input_schema'        => $this->object_schema(
					array(
						'testId'    => array(
							'type'        => 'integer',
							'minimum'     => 1,
							'description' => 'Identifier of the finished test whose variant should be applied.',
						),
						'variantId' => array(
							'type'        => 'string',
							'minLength'   => 1,
							'description' => 'UUID of the variant to apply, or the literal `control` to restore the original control version.',
						),
					),
					array( 'testId', 'variantId' )
				),
				'output_schema'       => $this->object_schema(
					array(
						'testId'    => array(
							'type'        => 'integer',
							'description' => 'Identifier of the test whose variant was applied.',
						),
						'variantId' => array(
							'type'        => 'string',
							'description' => 'Identifier of the applied variant, or `control` when the original version was restored.',
						),
						'status'    => array(
							'type'        => 'string',
							'description' => 'Status of the test after the variant was applied.',
						),
					),
					array( 'testId', 'variantId', 'status' )
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
						'destructive' => true,
						'idempotent'  => false,
					),
				),
			)
		);
	}

	/**
	 * Checks whether the current user may apply the requested variant.
	 *
	 * The experiment performs the complete permission, status, variant, and test-type support validation.
	 *
	 * @param array<array-key,mixed> $input Input containing the test and variant identifiers.
	 *
	 * @return true|WP_Error True when the variant may be applied; otherwise, an error.
	 */
	public function permissions( $input ) {
		$experiment = nab_get_experiment( $this->integer_input( $input, 'testId' ) );
		if ( is_wp_error( $experiment ) ) {
			return $experiment;
		}

		$variant_id = is_string( $input['variantId'] ?? null ) ? $input['variantId'] : '';
		return $experiment->can_apply_alternative( $variant_id );
	}

	/**
	 * Applies the requested variant or restores the control version.
	 *
	 * @param array<array-key,mixed> $input Input containing the test and variant identifiers.
	 *
	 * @return array<string,int|string>|WP_Error The applied variant and resulting test status, or an error.
	 */
	public function execute( $input ) {
		$allowed = $this->permissions( $input );
		if ( is_wp_error( $allowed ) ) {
			return $allowed;
		}

		$experiment = nab_get_experiment( $this->integer_input( $input, 'testId' ) );
		assert( ! is_wp_error( $experiment ) );

		$variant_id = is_string( $input['variantId'] ?? null ) ? $input['variantId'] : '';
		$applied    = $experiment->apply_alternative( $variant_id );
		if ( is_wp_error( $applied ) ) {
			return $applied; // @codeCoverageIgnore
		}

		return array(
			'testId'    => $experiment->ID,
			'variantId' => $variant_id,
			'status'    => $experiment->get_status(),
		);
	}
}
