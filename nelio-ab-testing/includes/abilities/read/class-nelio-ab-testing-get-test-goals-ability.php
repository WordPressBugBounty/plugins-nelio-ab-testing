<?php
/**
 * Ability for reading the goals of a Nelio test.
 *
 * @package Nelio_AB_Testing
 */

defined( 'ABSPATH' ) || exit;

/** Reads the complete list of goals configured in a Nelio test. */
class Nelio_AB_Testing_Get_Test_Goals_Ability extends Nelio_AB_Testing_Ability {

	/** Register the ability. */
	public function register() {
		wp_register_ability(
			'nelio-ab-testing/get-test-goals',
			array(
				'label'               => _x( 'Read test goals', 'text (ability name)', 'nelio-ab-testing' ),
				'description'         => 'Reads the complete list of goals configured in a Nelio test, returning each goal’s UUID and name.',
				'category'            => 'nelio-ab-testing',
				'input_schema'        => $this->object_schema(
					array(
						'testId' => array(
							'type'        => 'integer',
							'minimum'     => 1,
							'description' => 'Identifier of the test whose goals should be read.',
						),
					)
				),
				'output_schema'       => $this->object_schema(
					array(
						'testId' => array(
							'type'        => 'integer',
							'description' => 'Identifier of the test.',
						),
						'goals'  => array(
							'type'        => 'array',
							'description' => 'Goal identifiers and names, ordered as they appear in the test.',
							'items'       => $this->object_schema(
								array(
									'id'   => array(
										'type'        => 'string',
										'description' => 'Internal UUID of the goal.',
									),
									'name' => array(
										'type'        => 'string',
										'description' => 'Name of the goal.',
									),
								),
								array( 'id', 'name' )
							),
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
						'readonly'    => true,
						'destructive' => false,
						'idempotent'  => true,
					),
				),
			)
		);
	}

	/**
	 * Checks whether the current user may read test goals.
	 *
	 * @param array<array-key,mixed> $input Input containing the test identifier.
	 *
	 * @return true|WP_Error True when the user may read goals; otherwise, an error.
	 */
	public function permissions( $input ) {
		$experiment = nab_get_experiment( $this->integer_input( $input, 'testId' ) );
		if ( is_wp_error( $experiment ) ) {
			return $experiment;
		}
		return Nelio_AB_Testing_Test_Response_Formatter::can_read( $experiment );
	}

	/**
	 * Returns all goals configured in a test.
	 *
	 * @param array<array-key,mixed> $input Input containing the test identifier.
	 *
	 * @return array<string,mixed>|WP_Error Goal summaries or an error.
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
				'testId' => $experiment->ID,
				'goals'  => array_map(
					fn( $goal ) => array(
						'id'   => $goal['id'],
						'name' => $goal['attributes']['name'],
					),
					$experiment->get_goals()
				),
			)
		);
	}
}
