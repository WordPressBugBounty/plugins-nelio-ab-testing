<?php
/**
 * Ability for setting the conversion actions of one test goal.
 *
 * @package Nelio_AB_Testing
 */

defined( 'ABSPATH' ) || exit;

/** Replaces the conversion actions of one goal in an editable test. */
class Nelio_AB_Testing_Set_Test_Goal_Actions_Ability extends Nelio_AB_Testing_Ability {

	/** Register the ability. */
	public function register() {
		wp_register_ability(
			'nelio-ab-testing/set-test-goal-actions',
			array(
				'label'               => _x( 'Set test goal actions', 'text (ability name)', 'nelio-ab-testing' ),
				'description'         => 'Replaces all conversion actions of one goal in an existing editable Nelio test. It does not merge the supplied actions with the current ones, and it cannot change actions that the selected test type configures automatically. Use this ability only when the goal is reported as allowing action edits. If the goal’s actions are managed automatically, do not try to bypass that restriction through another ability; report that the requested change cannot be performed.',
				'category'            => 'nelio-ab-testing',
				'input_schema'        => $this->object_schema(
					array(
						'testId'  => array(
							'type'        => 'integer',
							'minimum'     => 1,
							'description' => 'Identifier of the editable test.',
						),
						'goalId'  => array(
							'type'        => 'string',
							'minLength'   => 1,
							'description' => 'UUID of the goal whose conversion actions should be replaced.',
						),
						'actions' => array(
							'type'        => 'array',
							'minItems'    => 1,
							'description' => 'Complete replacement list of conversion actions. Do not provide internal action IDs; the plugin preserves existing IDs by position and generates IDs for new actions. Use the schemas reported for the selected action types in the test capabilities.',
							'items'       => $this->object_schema(
								array(
									'type'       => array(
										'type'        => 'string',
										'description' => 'Registered conversion action type.',
									),
									'attributes' => array(
										'type'        => 'object',
										'additionalProperties' => true,
										'description' => 'Complete attributes for the selected conversion action type.',
									),
									'scope'      => array(
										'type'        => 'object',
										'additionalProperties' => true,
										'description' => 'Optional scope in which the conversion action is tracked. Provide it only when the site goal-tracking mode is `custom` and the selected action type supports customizable scopes; for other modes or action types, omit this property because the action inherits an automatic or integration-specific scope. For URL-scoped actions, each URL value may contain `*` as a wildcard matching any sequence of characters; URL values are not regular expressions.',
									),
								),
								array( 'type', 'attributes' )
							),
						),
					),
					array( 'testId', 'goalId', 'actions' )
				),
				'output_schema'       => $this->object_schema(
					array(
						'testId' => array(
							'type'        => 'integer',
							'description' => 'Identifier of the updated test.',
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
									'description'          => 'Goal attributes.',
									'additionalProperties' => true,
								),
								'conversionActions' => array(
									'type'        => 'array',
									'description' => 'Complete resulting conversion-action list.',
									'items'       => array(
										'type'        => 'object',
										'description' => 'Conversion action with type-specific attributes.',
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
						'readonly'    => false,
						'destructive' => false,
						'idempotent'  => true,
					),
				),
			)
		);
	}

	/**
	 * Checks whether the current user may edit goal actions.
	 *
	 * @param array<array-key,mixed> $input Input containing the test identifier.
	 *
	 * @return true|WP_Error True when the user may edit actions; otherwise, an error.
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
	 * Replaces one goal's conversion actions and validates the resulting goal.
	 *
	 * @param array<array-key,mixed> $input Input containing the test, goal, and actions.
	 *
	 * @return array<string,mixed>|WP_Error Updated goal or an error.
	 */
	public function execute( $input ) {
		$allowed = $this->permissions( $input );
		if ( is_wp_error( $allowed ) ) {
			return $allowed;
		}

		$experiment = nab_get_experiment( $this->integer_input( $input, 'testId' ) );
		assert( ! is_wp_error( $experiment ) );

		$goal_id = is_string( $input['goalId'] ?? null ) ? trim( $input['goalId'] ) : '';
		$goals   = $experiment->get_goals();
		$index   = null;
		foreach ( $goals as $goal_index => $goal ) {
			if ( $goal['id'] === $goal_id ) {
				$index = $goal_index;
				break;
			}
		}

		if ( is_null( $index ) ) {
			return new WP_Error( 'invalid-goal', 'The requested goal does not exist in this test.' );
		}

		if ( $this->is_first_goal_automatic( $experiment, $index ) ) {
			return $this->automatic_actions_error( $experiment );
		}

		$actions = is_array( $input['actions'] ?? null ) ? $input['actions'] : array();
		$uuids   = array_map(
			static fn( $action ) => $action['id'],
			$goals[ $index ]['conversionActions']
		);
		$actions = $this->normalize_actions( $actions, $uuids );
		if ( is_wp_error( $actions ) ) {
			return $actions;
		}

		if ( $this->has_mixed_purchase_actions( $actions ) ) {
			return new WP_Error(
				'mixed-purchase-actions',
				sprintf(
					'A goal cannot mix purchase actions (`%s`) with non-purchase conversion actions.',
					implode( '`, `', Nelio_AB_Testing_Goal_Manager::get_purchase_action_types() )
				)
			);
		}

		/** @var list<TGoal> $goals */
		$goals[ $index ]['conversionActions'] = $actions;

		$validation = Nelio_AB_Testing_Goal_Manager::ai_validate_goal( $goals[ $index ], $experiment->ID );
		if ( is_wp_error( $validation ) ) {
			return $validation;
		}

		$experiment->set_goals( $goals );
		nab_ai_refresh_experiment_status( $experiment );
		$experiment->save();
		$goal = Nelio_AB_Testing_Test_Response_Formatter::goal( $experiment, $goal_id );
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
		if ( 0 !== $goal_index ) {
			return false;
		}
		$definition = nelioab()->get_experiment_type( $experiment->get_type() );
		return is_array( $definition ) && true === ( $definition['automaticGoalSetup'] ?? false );
	}

	/**
	 * Returns whether the actions mix purchase and non-purchase action types.
	 *
	 * @param list<TConversion_Action> $actions Conversion actions.
	 *
	 * @return bool Whether purchase and non-purchase actions are mixed.
	 */
	private function has_mixed_purchase_actions( $actions ) {
		$purchase_action_types = Nelio_AB_Testing_Goal_Manager::get_purchase_action_types();
		$has_purchase_action   = false;
		$has_other_action      = false;
		foreach ( $actions as $action ) {
			if ( in_array( $action['type'], $purchase_action_types, true ) ) {
				$has_purchase_action = true;
			} else {
				$has_other_action = true;
			}
		}

		return $has_purchase_action && $has_other_action;
	}

	/**
	 * Returns the error for attempts to edit automatic goal actions.
	 *
	 * @param Nelio_AB_Testing_Experiment $experiment Test.
	 *
	 * @return WP_Error Error describing the restriction.
	 */
	private function automatic_actions_error( $experiment ) {
		return new WP_Error(
			'automatic-goal-actions',
			sprintf(
				'The conversion actions of the first goal in test type `%s` are managed automatically and cannot be changed.',
				$experiment->get_type()
			)
		);
	}

	/**
	 * Normalizes action input and generates missing action UUIDs.
	 *
	 * @param mixed        $actions Conversion actions.
	 * @param list<string> $uuids   Existing conversion-action UUIDs.
	 *
	 * @return list<TConversion_Action>|WP_Error Normalized actions or an error.
	 */
	private function normalize_actions( $actions, $uuids ) {
		if ( ! is_array( $actions ) || empty( $actions ) ) {
			return new WP_Error( 'invalid-conversion-actions', 'A goal must contain at least one conversion action.' );
		}

		$result = array();
		foreach ( $actions as $index => $action ) {
			if ( ! is_array( $action ) ) {
				return new WP_Error( 'invalid-conversion-action', 'Every conversion action must be an object.' );
			}

			$type = is_string( $action['type'] ?? null ) ? trim( $action['type'] ) : '';
			if ( '' === $type ) {
				return new WP_Error( 'invalid-conversion-action', 'Every conversion action must specify its type.' );
			}

			$type_definition = nelioab()->get_conversion_action_type( $type );

			$id = $uuids[ $index ] ?? nab_uuid();

			/** @var TAttributes $attributes */
			$attributes = is_array( $action['attributes'] ?? null ) ? $action['attributes'] : array();

			if ( array_key_exists( 'scope', $action ) && ! $this->is_custom_goal_tracking() ) {
				return new WP_Error(
					'invalid-conversion-action-scope',
					sprintf(
						'A conversion action must not define a scope when the site goal-tracking mode is `%s`; omit the `scope` property so the action inherits the global setting.',
						Nelio_AB_Testing_Settings::instance()->get( 'goal_tracking' )
					)
				);
			}

			if ( array_key_exists( 'scope', $action ) && ! $this->supports_custom_scope( $type_definition ) ) {
				return new WP_Error(
					'invalid-conversion-action-scope',
					sprintf(
						'The conversion action type `%s` does not support a customizable scope.',
						$type
					)
				);
			}

			$goal_tracking = Nelio_AB_Testing_Settings::instance()->get( 'goal_tracking' );
			if ( $this->is_custom_goal_tracking() ) {
				$scope = is_array( $action['scope'] ?? null )
					? $action['scope']
					: $this->get_default_scope( $type_definition );
			} elseif ( 'all-pages' === $goal_tracking || 'test-scope' === $goal_tracking ) {
				$scope = array( 'type' => $goal_tracking );
			} else {
				$scope = array( 'type' => 'test-scope' );
			}
			/** @var TConversion_Action_Scope $scope */

			$result[] = array(
				'id'         => $id,
				'type'       => $type,
				'attributes' => $attributes,
				'scope'      => $scope,
			);
		}

		return $result;
	}

	/**
	 * Checks whether an action type supports customizable scopes.
	 *
	 * @param mixed $type_definition Conversion action type definition.
	 *
	 * @return bool Whether the action type supports customizable scopes.
	 */
	private function supports_custom_scope( $type_definition ) {
		return is_array( $type_definition ) && true === ( $type_definition['supportsCustomScope'] ?? false );
	}

	/**
	 * Returns an action type's default scope.
	 *
	 * @param mixed $type_definition Conversion action type definition.
	 *
	 * @return array{type: 'test-scope'}|array{type: 'php-function'} Default action scope.
	 */
	private function get_default_scope( $type_definition ) {
		return $this->supports_custom_scope( $type_definition )
			? array( 'type' => 'test-scope' )
			: array( 'type' => 'php-function' );
	}

	/**
	 * Checks whether conversion actions may define their own scopes.
	 *
	 * @return bool Whether the site goal-tracking mode is custom.
	 */
	private function is_custom_goal_tracking() {
		return 'custom' === Nelio_AB_Testing_Settings::instance()->get( 'goal_tracking' );
	}
}
