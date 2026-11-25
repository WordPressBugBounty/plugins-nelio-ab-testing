<?php
/**
 * Ability for reading a complete test variant.
 *
 * @package Nelio_AB_Testing
 */

defined( 'ABSPATH' ) || exit;

/**
 * Reads a complete test variant definition.
 */
class Nelio_AB_Testing_Get_Test_Variant_Ability extends Nelio_AB_Testing_Ability {

	/**
	 * Registers the ability with WordPress.
	 *
	 * @return void
	 */
	public function register() {
		wp_register_ability(
			'nelio-ab-testing/get-test-variant',
			array(
				'label'               => _x( 'Read a test variant', 'text (ability name)', 'nelio-ab-testing' ),
				'description'         => 'Reads the complete user-defined definition of one test variant, including its attributes. If no explicit `chance` value is returned, Nelio uses equal distribution across all variants. Use the variant identifier returned by the test summary—`control` for the control variant or the variant UUID for an alternative. Use the positional label only when referring to the variant to the user.',
				'category'            => 'nelio-ab-testing',
				'input_schema'        => $this->object_schema(
					array(
						'testId'    => array(
							'type'        => 'integer',
							'description' => 'Identifier of the test containing the variant.',
						),
						'variantId' => array(
							'type'        => 'string',
							'minLength'   => 1,
							'description' => 'Identifier of the variant to read: use the literal `control` for the control variant or the alternative variant UUID.',
						),
					)
				),
				'output_schema'       => $this->object_schema(
					array(
						'testId'  => array(
							'type'        => 'integer',
							'description' => 'Identifier of the test.',
						),
						'variant' => $this->object_schema(
							array(
								'id'            => array(
									'type'        => 'string',
									'description' => 'Identifier of the variant: the literal `control` for the control or an internal UUID for an alternative.',
								),
								'index'         => array(
									'type'        => 'integer',
									'description' => 'Zero-based position of the variant. The control is index 0.',
								),
								'label'         => array(
									'type'        => 'string',
									'description' => 'Positional label used when presenting the variant to a user.',
								),
								'isLastApplied' => array(
									'type'        => 'boolean',
									'description' => 'Whether this variant is currently applied as the last manually selected variant.',
								),
								'attributes'    => array(
									'type'                 => 'object',
									'description'          => 'Variant attributes defined by the selected test type. When custom distribution is enabled and the distribution is not even, this object may include the `chance` percentage.',
									'properties'           => array(
										'chance' => array(
											'type'        => 'number',
											'description' => 'Explicit percentage of visitors assigned to the variant.',
										),
									),
									'additionalProperties' => true,
								),
								'links'         => array(
									'type'                 => 'object',
									'description'          => 'Links related to this variant. A link may be false when that view is unavailable.',
									'properties'           => array(
										'edit'    => $this->variant_link_schema( 'Editor link for this variant.' ),
										'heatmap' => $this->variant_link_schema( 'Heatmap link for this variant.' ),
										'preview' => $this->variant_link_schema( 'Preview link for this variant.' ),
									),
									'required'             => array( 'edit', 'heatmap', 'preview' ),
									'additionalProperties' => false,
								),
							),
							array( 'id', 'index', 'label', 'attributes' )
						),
					),
					array( 'testId', 'variant' )
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
						'readonly'    => true,
						'destructive' => false,
						'idempotent'  => true,
					),
				),
			)
		);
	}

	/**
	 * Generates the schema for an optional variant link.
	 *
	 * @param string $description Link description.
	 *
	 * @return array<string,mixed> Link schema.
	 */
	private function variant_link_schema( $description ) {
		return array(
			'oneOf'       => array(
				array(
					'type'   => 'string',
					'format' => 'uri',
				),
				array(
					'type'  => 'boolean',
					'const' => false,
				),
			),
			'description' => $description,
		);
	}

	/**
	 * Checks whether the current user may read the variant.
	 *
	 * @param array<array-key,mixed> $input Input containing the test identifier.
	 *
	 * @return true|WP_Error True when the user may read the variant; otherwise, an error.
	 */
	public function permissions( $input ) {
		$experiment = nab_get_experiment( $this->integer_input( $input, 'testId' ) );
		if ( is_wp_error( $experiment ) ) {
			return $experiment;
		}
		return Nelio_AB_Testing_Test_Response_Formatter::can_read( $experiment );
	}

	/**
	 * Returns the complete variant definition.
	 *
	 * @param array<array-key,mixed> $input Input containing the test and variant identifiers.
	 *
	 * @return array<string,mixed>|WP_Error Variant definition or an error.
	 */
	public function execute( $input ) {
		$allowed = $this->permissions( $input );
		if ( is_wp_error( $allowed ) ) {
			return $allowed;
		}
		$experiment = nab_get_experiment( $this->integer_input( $input, 'testId' ) );
		assert( ! is_wp_error( $experiment ) );
		$variant_id = is_string( $input['variantId'] ?? null ) ? $input['variantId'] : '';
		$variant    = Nelio_AB_Testing_Test_Response_Formatter::variant( $experiment, $variant_id );
		if ( is_wp_error( $variant ) ) {
			return $variant;
		}
		return $this->bounded_response(
			array(
				'testId'  => $experiment->ID,
				'variant' => $variant,
			)
		);
	}
}
