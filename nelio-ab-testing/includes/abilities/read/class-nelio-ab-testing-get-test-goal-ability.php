<?php
/**
 * Ability for reading a complete Nelio test goal.
 *
 * @package Nelio_AB_Testing
 */

defined( 'ABSPATH' ) || exit;

/** Reads a complete test goal definition. */
class Nelio_AB_Testing_Get_Test_Goal_Ability extends Nelio_AB_Testing_Ability {

	/**
	 * Registers the ability with WordPress.
	 *
	 * @return void
	 */
	public function register() {
		wp_register_ability(
			'nelio-ab-testing/get-test-goal',
			array(
				'label'               => _x( 'Read a test goal', 'text (ability name)', 'nelio-ab-testing' ),
				'description'         => 'Reads the complete user-defined definition of one goal in a Nelio test, including its conversion actions and whether those actions can be edited. Action scopes are returned only when the site goal-tracking mode is `custom` and the action type supports customizable scopes; otherwise, actions inherit an automatic or integration-specific scope and do not include a scope. Treat the `actionsEditable` value as authoritative. When it is false, do not attempt to replace or alter the goal’s actions through this or another ability; report that the requested change cannot be performed.',
				'category'            => 'nelio-ab-testing',
				'input_schema'        => $this->object_schema(
					array(
						'testId' => array(
							'type'        => 'integer',
							'minimum'     => 1,
							'description' => 'Identifier of the test containing the goal.',
						),
						'goalId' => array(
							'type'        => 'string',
							'minLength'   => 1,
							'description' => 'Internal UUID of the goal to read.',
						),
					)
				),
				'output_schema'       => $this->object_schema(
					array(
						'testId' => array(
							'type'        => 'integer',
							'description' => 'Identifier of the test.',
						),
						'goal'   => $this->object_schema(
							array(
								'id'                => array(
									'type'        => 'string',
									'description' => 'Internal UUID of the goal.',
								),
								'label'             => array(
									'type'        => 'string',
									'description' => 'Positional label used when presenting the goal to a user.',
								),
								'attributes'        => array(
									'type'                 => 'object',
									'description'          => 'Goal attributes. Their available fields depend on the configured conversion actions.',
									'additionalProperties' => true,
								),
								'conversionActions' => array(
									'type'        => 'array',
									'description' => 'Conversion actions defining when this goal converts. Their attributes depend on their registered action types.',
									'items'       => array(
										'type'        => 'object',
										'description' => 'Conversion action with a registered type and type-specific attributes.',
									),
								),
								'actionsEditable'   => array(
									'type'        => 'boolean',
									'description' => 'Whether the conversion actions of this goal may be replaced through the AI abilities.',
								),
							),
							array( 'id', 'label', 'attributes', 'conversionActions', 'actionsEditable' )
						),
					),
					array( 'testId', 'goal' )
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
	 * Checks whether the current user may read the goal.
	 *
	 * @param array<array-key,mixed> $input Input containing the test identifier.
	 *
	 * @return true|WP_Error True when the user may read the goal; otherwise, an error.
	 */
	public function permissions( $input ) {
		$experiment = nab_get_experiment( $this->integer_input( $input, 'testId' ) );
		if ( is_wp_error( $experiment ) ) {
			return $experiment;
		}
		return Nelio_AB_Testing_Test_Response_Formatter::can_read( $experiment );
	}

	/**
	 * Returns the complete goal definition.
	 *
	 * @param array<array-key,mixed> $input Input containing the test and goal identifiers.
	 *
	 * @return array<string,mixed>|WP_Error Goal definition or an error.
	 */
	public function execute( $input ) {
		$allowed = $this->permissions( $input );
		if ( is_wp_error( $allowed ) ) {
			return $allowed;
		}

		$experiment = nab_get_experiment( $this->integer_input( $input, 'testId' ) );
		assert( ! is_wp_error( $experiment ) );

		$goal_id = is_string( $input['goalId'] ?? null ) ? $input['goalId'] : '';
		$goal    = Nelio_AB_Testing_Test_Response_Formatter::goal( $experiment, $goal_id );
		if ( is_wp_error( $goal ) ) {
			return $goal;
		}

		$goal_index = 0;
		foreach ( $experiment->get_goals() as $index => $configured_goal ) {
			if ( $configured_goal['id'] === $goal_id ) {
				$goal_index = $index;
				break;
			}
		}
		$goal['actionsEditable'] = ! $this->is_first_goal_automatic( $experiment, $goal_index );
		return $this->bounded_response(
			array(
				'testId' => $experiment->ID,
				'goal'   => $goal,
			)
		);
	}

	/**
	 * Returns whether the given goal is the first automatically configured goal.
	 *
	 * @param Nelio_AB_Testing_Experiment $experiment  Test.
	 * @param int                         $goal_index Goal index.
	 *
	 * @return bool Whether the goal is the first automatic goal.
	 */
	private function is_first_goal_automatic( $experiment, $goal_index ) {
		$definition = nelioab()->get_experiment_type( $experiment->get_type() );
		return 0 === $goal_index && is_array( $definition ) && true === ( $definition['automaticGoalSetup'] ?? false );
	}
}
