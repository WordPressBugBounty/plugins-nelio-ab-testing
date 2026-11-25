<?php
/**
 * Ability for setting the attributes of one test goal.
 *
 * @package Nelio_AB_Testing
 */

defined( 'ABSPATH' ) || exit;

/** Replaces the attributes of one goal in an editable test. */
class Nelio_AB_Testing_Set_Test_Goal_Attributes_Ability extends Nelio_AB_Testing_Ability {

	/** Register the ability. */
	public function register() {
		wp_register_ability(
			'nelio-ab-testing/set-test-goal-attributes',
			array(
				'label'               => _x( 'Set test goal attributes', 'text (ability name)', 'nelio-ab-testing' ),
				'description'         => 'Replaces all attributes of one goal in an existing editable Nelio test. It does not merge the supplied attributes with the current ones, and it can also update the attributes of a goal whose conversion actions are managed automatically. Configure the goal’s conversion actions before using this ability, including when you only want to change its name.',
				'category'            => 'nelio-ab-testing',
				'input_schema'        => $this->object_schema(
					array(
						'testId'     => array(
							'type'        => 'integer',
							'minimum'     => 1,
							'description' => 'Identifier of the editable test.',
						),
						'goalId'     => array(
							'type'        => 'string',
							'minLength'   => 1,
							'description' => 'UUID of the goal whose attributes should be replaced.',
						),
						'attributes' => array(
							'type'                 => 'object',
							'additionalProperties' => true,
							'description'          => 'Complete replacement attributes for the goal. Configure the goal’s conversion actions before using this ability. Every property is optional, but omitted properties are removed because this ability does not merge attributes with the current ones.',
							'properties'           => array(
								'name'                     => array(
									'type'        => 'string',
									'description' => 'Optional display name for the goal.',
								),
								'ga4'                      => array(
									'type'        => 'object',
									'description' => 'Optional Google Analytics 4 settings. GA4 tracking must be enabled on the site when this property is supplied.',
									'properties'  => array(
										'disabled'  => array(
											'type'        => 'boolean',
											'description' => 'Optional flag that disables tracking of this goal in Google Analytics 4.',
										),
										'eventName' => array(
											'type'        => 'string',
											'description' => 'Optional Google Analytics 4 event name for this goal.',
										),
										'goalName'  => array(
											'type'        => 'string',
											'description' => 'Optional name for this goal in Google Analytics 4 reports.',
										),
									),
								),
								'useOrderRevenue'          => array(
									'type'        => 'boolean',
									'description' => 'Optional flag that enables revenue tracking for this goal. Use it only when every conversion action is a purchase action.',
								),
								'useFullOrderRevenue'      => array(
									'type'        => 'boolean',
									'description' => 'Optional flag that tracks the complete order value instead of only the revenue associated with the tested product or download. Use it only when every conversion action is a purchase action.',
								),
								'orderStatusForConversion' => array(
									'type'        => 'string',
									'description' => 'Optional order status that qualifies a purchase as a conversion. Use it only when every conversion action is a purchase action.',
								),
							),
						),
					),
					array( 'testId', 'goalId', 'attributes' )
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
									'description'          => 'Resulting goal attributes.',
									'additionalProperties' => true,
								),
								'conversionActions' => array(
									'type'        => 'array',
									'description' => 'Resulting conversion-action list.',
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
	 * Checks whether the current user may edit goal attributes.
	 *
	 * @param array<array-key,mixed> $input Input containing the test identifier.
	 *
	 * @return true|WP_Error True when the user may edit attributes; otherwise, an error.
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
	 * Replaces one goal's attributes and validates the resulting goal.
	 *
	 * @param array<array-key,mixed> $input Input containing the test, goal, and attributes.
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

		$goal_id         = is_string( $input['goalId'] ?? null ) ? trim( $input['goalId'] ) : '';
		$goals           = $experiment->get_goals();
		$found           = false;
		$goal_index      = null;
		$current_actions = array();
		$attributes      = array();
		foreach ( $goals as $index => &$goal ) {
			if ( $goal['id'] !== $goal_id ) {
				continue;
			}
			$attributes         = is_array( $input['attributes'] ?? null ) ? $input['attributes'] : array();
			$goal['attributes'] = $attributes;
			$current_actions    = $goal['conversionActions'];
			$goal_index         = $index;
			$found              = true;
			break;
		}
		unset( $goal );

		if ( ! $found || ! is_int( $goal_index ) ) {
			return new WP_Error( 'invalid-goal', 'The requested goal does not exist in this test.' );
		}

		$special_attributes = $this->validate_special_attributes( $attributes, $current_actions );
		if ( is_wp_error( $special_attributes ) ) {
			return $special_attributes;
		}

		$validation = Nelio_AB_Testing_Goal_Manager::ai_validate_goal( $goals[ $goal_index ], $experiment->ID );
		if ( is_wp_error( $validation ) ) {
			return $validation;
		}

		$goals = Nelio_AB_Testing_Goal_Manager::sanitize_experiment_goals( $goals, $experiment->get_type(), $experiment->ID );
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
	 * Validates attributes that depend on the goal's conversion actions or site settings.
	 *
	 * @param array<array-key,mixed>   $attributes Goal attributes.
	 * @param list<TConversion_Action> $actions Goal conversion actions.
	 *
	 * @return true|WP_Error True when the attributes are applicable; otherwise, an error.
	 */
	private function validate_special_attributes( $attributes, $actions ) {
		if ( array_key_exists( 'ga4', $attributes ) ) {
			$settings = Nelio_AB_Testing_Settings::instance()->get( 'google_analytics_tracking' );
			if ( empty( $settings['enabled'] ) ) {
				return new WP_Error(
					'ga4-disabled',
					'Google Analytics 4 tracking must be enabled before goal-level GA4 attributes can be configured.'
				);
			}
		}

		$ecommerce_attributes     = array( 'useOrderRevenue', 'useFullOrderRevenue', 'orderStatusForConversion' );
		$has_ecommerce_attributes = false;
		foreach ( $ecommerce_attributes as $attribute ) {
			if ( array_key_exists( $attribute, $attributes ) ) {
				$has_ecommerce_attributes = true;
				break;
			}
		}

		if ( $has_ecommerce_attributes && ! $this->are_purchase_actions( $actions ) ) {
			if ( empty( $actions ) ) {
				return new WP_Error(
					'goal-actions-required',
					'Action-dependent goal attributes cannot be configured until the goal has at least one conversion action. Configure the conversion actions first.'
				);
			}

			return new WP_Error(
				'invalid-ecommerce-goal-attributes',
				'E-commerce goal attributes require one or more conversion actions, all of which must be purchase actions (`' . ( implode( '`, `', Nelio_AB_Testing_Goal_Manager::get_purchase_action_types() ) ) . '`).'
			);
		}

		return true;
	}

	/**
	 * Returns whether all actions are supported purchase actions.
	 *
	 * @param list<TConversion_Action> $actions Conversion actions.
	 *
	 * @return bool Whether all actions are purchase actions and at least one exists.
	 */
	private function are_purchase_actions( $actions ) {
		if ( empty( $actions ) ) {
			return false;
		}

		$purchase_action_types = Nelio_AB_Testing_Goal_Manager::get_purchase_action_types();
		foreach ( $actions as $action ) {
			if ( ! in_array( $action['type'], $purchase_action_types, true ) ) {
				return false;
			}
		}

		return true;
	}
}
