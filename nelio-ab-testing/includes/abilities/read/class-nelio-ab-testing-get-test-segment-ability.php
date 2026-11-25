<?php
/**
 * Ability for reading a complete Nelio test segment.
 *
 * @package Nelio_AB_Testing
 */

defined( 'ABSPATH' ) || exit;

/** Reads a complete test segment definition. */
class Nelio_AB_Testing_Get_Test_Segment_Ability extends Nelio_AB_Testing_Ability {

	/**
	 * Registers the ability with WordPress.
	 *
	 * @return void
	 */
	public function register() {
		wp_register_ability(
			'nelio-ab-testing/get-test-segment',
			array(
				'label'               => _x( 'Read a test segment', 'text (ability name)', 'nelio-ab-testing' ),
				'description'         => 'Reads the complete definition of one configured segment in a Nelio test, including its segmentation rules. The virtual `default` segment used for result analysis is not a configured segment and has no definition to read.',
				'category'            => 'nelio-ab-testing',
				'input_schema'        => $this->object_schema(
					array(
						'testId'    => array(
							'type'        => 'integer',
							'minimum'     => 1,
							'description' => 'Identifier of the test containing the segment.',
						),
						'segmentId' => array(
							'type'        => 'string',
							'minLength'   => 1,
							'description' => 'Internal UUID of the configured segment to read. Do not use the virtual `default` result segment here.',
						),
					)
				),
				'output_schema'       => $this->object_schema(
					array(
						'testId'  => array(
							'type'        => 'integer',
							'description' => 'Identifier of the test.',
						),
						'segment' => $this->object_schema(
							array(
								'id'                => array(
									'type'        => 'string',
									'description' => 'Internal UUID of the segment.',
								),
								'label'             => array(
									'type'        => 'string',
									'description' => 'Positional label used when presenting the segment to a user.',
								),
								'attributes'        => array(
									'type'                 => 'object',
									'description'          => 'Segment attributes.',
									'additionalProperties' => true,
								),
								'segmentationRules' => array(
									'type'        => 'array',
									'description' => 'Segmentation rules defining which visitors belong to the segment. Their attributes depend on their registered rule types.',
									'items'       => array(
										'type'        => 'object',
										'description' => 'Segmentation rule with a registered type and type-specific attributes.',
									),
								),
							),
							array( 'id', 'label', 'attributes', 'segmentationRules' )
						),
					),
					array( 'testId', 'segment' )
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
	 * Checks whether the current user may read the segment.
	 *
	 * @param array<array-key,mixed> $input Input containing the test identifier.
	 *
	 * @return true|WP_Error True when the user may read the segment; otherwise, an error.
	 */
	public function permissions( $input ) {
		$experiment = nab_get_experiment( $this->integer_input( $input, 'testId' ) );
		if ( is_wp_error( $experiment ) ) {
			return $experiment;
		}
		return Nelio_AB_Testing_Test_Response_Formatter::can_read( $experiment );
	}

	/**
	 * Returns the complete segment definition.
	 *
	 * @param array<array-key,mixed> $input Input containing the test and segment identifiers.
	 *
	 * @return array<string,mixed>|WP_Error Segment definition or an error.
	 */
	public function execute( $input ) {
		$allowed = $this->permissions( $input );
		if ( is_wp_error( $allowed ) ) {
			return $allowed;
		}
		$experiment = nab_get_experiment( $this->integer_input( $input, 'testId' ) );
		assert( ! is_wp_error( $experiment ) );
		$segment_id = is_string( $input['segmentId'] ?? null ) ? $input['segmentId'] : '';
		$segment    = Nelio_AB_Testing_Test_Response_Formatter::segment( $experiment, $segment_id );
		if ( is_wp_error( $segment ) ) {
			return $segment;
		}
		return $this->bounded_response(
			array(
				'testId'  => $experiment->ID,
				'segment' => $segment,
			)
		);
	}
}
