<?php
/**
 * Ability for setting the goal list of an editable Nelio test.
 *
 * @package Nelio_AB_Testing
 */

defined( 'ABSPATH' ) || exit;

/** Replaces the goal list of an editable test while preserving goals by UUID. */
class Nelio_AB_Testing_Set_Test_Goals_Ability extends Nelio_AB_Testing_Ability {

	/** Register the ability. */
	public function register() {
		$goal = $this->object_schema(
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
		);
		wp_register_ability(
			'nelio-ab-testing/set-test-goals',
			array(
				'label'               => _x( 'Set test goals', 'text (ability name)', 'nelio-ab-testing' ),
				'description'         => 'Replaces the complete goal list of an existing editable Nelio test. Use it after the test exists to establish which goals should be present, then configure each goal with the dedicated attribute and action abilities. Identify existing goals by UUID to preserve them and omit goals that should be removed. Goals without a UUID are created with a new UUID; their attributes and conversion actions can then be configured with the dedicated goal abilities. The first goal of a test with automatic goal setup is retained, and its conversion actions remain managed by the plugin. When the first goal’s actions are managed automatically, preserve that goal and do not use this ability or another ability to replace or alter those actions. If the requested change requires those actions, report that it cannot be performed.',
				'category'            => 'nelio-ab-testing',
				'input_schema'        => $this->object_schema(
					array(
						'testId' => array(
							'type'        => 'integer',
							'minimum'     => 1,
							'description' => 'Identifier of the editable test.',
						),
						'goals'  => array(
							'type'        => 'array',
							'description' => 'Complete desired goal list. Include an existing goal UUID to preserve it, omit it to remove that goal, or omit the UUID to create a new goal.',
							'items'       => $this->object_schema(
								array(
									'id' => array(
										'type'        => 'string',
										'minLength'   => 1,
										'description' => 'UUID of an existing goal to preserve. Omit it when creating a new goal.',
									),
								),
								array()
							),
						),
					),
					array( 'testId', 'goals' )
				),
				'output_schema'       => $this->object_schema(
					array(
						'testId' => array(
							'type'        => 'integer',
							'description' => 'Identifier of the updated test.',
						),
						'goals'  => array(
							'type'        => 'array',
							'description' => 'Complete resulting list of goals, including their generated UUIDs.',
							'items'       => $goal,
						),
					),
					array( 'testId', 'goals' )
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
	 * Checks whether the current user may configure test goals.
	 *
	 * @param array<array-key,mixed> $input Input containing the test identifier.
	 *
	 * @return true|WP_Error True when the user can configure goals; otherwise, an error.
	 */
	public function permissions( $input ) {
		$experiment = nab_get_experiment( $this->integer_input( $input, 'testId' ) );
		if ( is_wp_error( $experiment ) ) {
			return $experiment;
		}
		$heatmap_check = $this->reject_heatmap( $experiment );
		if ( is_wp_error( $heatmap_check ) ) {
			return $heatmap_check;
		}

		if ( ! nelioab()->does_test_type_support_ai( $experiment->get_type() ) ) {
			return new WP_Error( 'unsupported-test-type', 'This test type is registered but cannot be configured through AI abilities.' );
		}

		return $experiment->can_be_edited();
	}

	/**
	 * Replaces the goal list while preserving goals identified by UUID.
	 *
	 * @param array<array-key,mixed> $input Input containing the test identifier and goal UUIDs.
	 *
	 * @return array<string,mixed>|WP_Error The resulting goals, or an error.
	 */
	public function execute( $input ) {
		$allowed = $this->permissions( $input );
		if ( is_wp_error( $allowed ) ) {
			return $allowed;
		}

		$experiment = nab_get_experiment( $this->integer_input( $input, 'testId' ) );
		assert( ! is_wp_error( $experiment ) );

		$current_goals = $experiment->get_goals();
		$goals_by_id   = array();
		foreach ( $current_goals as $goal ) {
			$goals_by_id[ $goal['id'] ] = $goal;
		}

		$requested = is_array( $input['goals'] ?? null ) ? $input['goals'] : array();
		$result    = array();
		$seen      = array();
		foreach ( $requested as $goal ) {
			if ( ! is_array( $goal ) ) {
				return new WP_Error( 'invalid-goal', 'Every goal entry must be an object containing an optional UUID.' );
			}

			$goal_id = is_string( $goal['id'] ?? null ) ? trim( $goal['id'] ) : '';
			if ( '' === $goal_id ) {
				$result[] = array(
					'id'                => nab_uuid(),
					'attributes'        => array( 'name' => '' ),
					'conversionActions' => array(),
				);
				continue;
			}

			if ( isset( $seen[ $goal_id ] ) ) {
				return new WP_Error( 'duplicate-goal', 'The same goal UUID cannot appear more than once in the goal list.' );
			}
			if ( ! isset( $goals_by_id[ $goal_id ] ) ) {
				return new WP_Error( 'invalid-goal', 'The supplied goal UUID does not belong to this test.' );
			}

			$seen[ $goal_id ] = true;
			$result[]         = $goals_by_id[ $goal_id ];
		}

		if ( $this->is_first_goal_automatic( $experiment ) && ! empty( $current_goals ) ) {
			$automatic_goal  = $current_goals[0];
			$automatic_index = null;
			foreach ( $result as $result_index => $goal ) {
				if ( $goal['id'] === $automatic_goal['id'] ) {
					$automatic_index = $result_index;
					break;
				}
			}
			if ( is_null( $automatic_index ) ) {
				array_unshift( $result, $automatic_goal );
			} elseif ( 0 !== $automatic_index ) {
				array_splice( $result, $automatic_index, 1 );
				array_unshift( $result, $automatic_goal );
			}
		}

		if ( empty( $result ) ) {
			return new WP_Error( 'invalid-goal', 'The test must contain at least one goal.' );
		}

		$experiment->set_goals( $result );
		nab_ai_refresh_experiment_status( $experiment );
		$experiment->save();
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

	/**
	 * Returns whether the first goal is automatically configured for this test type.
	 *
	 * @param Nelio_AB_Testing_Experiment $experiment Test.
	 *
	 * @return bool Whether the first goal is automatic.
	 */
	private function is_first_goal_automatic( $experiment ) {
		$definition = nelioab()->get_experiment_type( $experiment->get_type() );
		return is_array( $definition ) && true === ( $definition['automaticGoalSetup'] ?? false );
	}
}
