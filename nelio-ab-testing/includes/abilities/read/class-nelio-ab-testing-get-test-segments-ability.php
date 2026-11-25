<?php
/**
 * Ability for reading the segments of a Nelio test.
 *
 * @package Nelio_AB_Testing
 */

defined( 'ABSPATH' ) || exit;

/** Reads compact summaries of a test's segments. */
class Nelio_AB_Testing_Get_Test_Segments_Ability extends Nelio_AB_Testing_Ability {

	/** Register the ability. */
	public function register() {
		wp_register_ability(
			'nelio-ab-testing/get-test-segments',
			array(
				'label'               => _x( 'Read test segments', 'text (ability name)', 'nelio-ab-testing' ),
				'description'         => 'Reads compact summaries of the user-defined segments configured in a Nelio test. When two or more segments exist, the response also includes the virtual `default` combined segment used for result analysis; that segment is not directly configured and has no rules to read or edit.',
				'category'            => 'nelio-ab-testing',
				'input_schema'        => $this->object_schema(
					array(
						'testId' => array(
							'type'        => 'integer',
							'minimum'     => 1,
							'description' => 'Identifier of the test whose segments should be read.',
						),
					)
				),
				'output_schema'       => $this->object_schema(
					array(
						'testId'   => array(
							'type'        => 'integer',
							'description' => 'Identifier of the test.',
						),
						'segments' => array(
							'type'        => 'array',
							'description' => 'Compact segment summaries ordered as they appear in the test, with the virtual `default` combined segment first when applicable.',
							'items'       => $this->object_schema(
								array(
									'id'        => array(
										'type'        => 'string',
										'description' => 'Internal UUID of the segment, or `default` for the virtual combined segment.',
									),
									'label'     => array(
										'type'        => 'string',
										'description' => 'Positional label used when presenting the segment to a user.',
									),
									'name'      => array(
										'type'        => 'string',
										'description' => 'Name of the segment.',
									),
									'isVirtual' => array(
										'type'        => 'boolean',
										'description' => 'Whether this is the virtual combined segment used for result analysis rather than a configured segment.',
									),
								),
								array( 'id', 'label', 'name' )
							),
						),
					),
					array( 'testId', 'segments' )
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
	 * Checks whether the current user may read test segments.
	 *
	 * @param array<array-key,mixed> $input Input containing the test identifier.
	 *
	 * @return true|WP_Error True when the user may read segments; otherwise, an error.
	 */
	public function permissions( $input ) {
		$experiment = nab_get_experiment( $this->integer_input( $input, 'testId' ) );
		if ( is_wp_error( $experiment ) ) {
			return $experiment;
		}
		return Nelio_AB_Testing_Test_Response_Formatter::can_read( $experiment );
	}

	/**
	 * Returns compact segment summaries.
	 *
	 * @param array<array-key,mixed> $input Input containing the test identifier.
	 *
	 * @return array<string,mixed>|WP_Error Segment summaries or an error.
	 */
	public function execute( $input ) {
		$allowed = $this->permissions( $input );
		if ( is_wp_error( $allowed ) ) {
			return $allowed;
		}
		$experiment = nab_get_experiment( $this->integer_input( $input, 'testId' ) );
		assert( ! is_wp_error( $experiment ) );
		return $this->bounded_response(
			array(
				'testId'   => $experiment->ID,
				'segments' => Nelio_AB_Testing_Test_Response_Formatter::segment_summaries( $experiment ),
			)
		);
	}
}
