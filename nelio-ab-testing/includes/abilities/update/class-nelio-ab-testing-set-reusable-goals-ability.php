<?php
/**
 * Ability for replacing reusable conversion goals.
 *
 * @package Nelio_AB_Testing
 */

defined( 'ABSPATH' ) || exit;

/** Replaces the site's reusable conversion goals. */
class Nelio_AB_Testing_Set_Reusable_Goals_Ability extends Nelio_AB_Testing_Ability {

	/**
	 * Registers the ability with WordPress.
	 *
	 * @return void
	 */
	public function register() {
		wp_register_ability(
			'nelio-ab-testing/set-reusable-goals',
			array(
				'label'               => _x( 'Set reusable goals', 'text (ability name)', 'nelio-ab-testing' ),
				'description'         => 'Replaces the complete list of reusable conversion goals available on the site. The plugin validates and stores the resulting goals without changing any existing test.',
				'category'            => 'nelio-ab-testing',
				'input_schema'        => $this->object_schema(
					array(
						'goals' => array(
							'type'        => 'array',
							'description' => 'Complete list of reusable conversion goals. Each goal must have attributes, including a name, and at least one conversion action. Do not provide goal identifiers; reusable goals do not expose them.',
							'items'       => $this->reusable_goal_input_schema(),
						),
					)
				),
				'output_schema'       => $this->object_schema(
					array(
						'goals' => array(
							'type'        => 'array',
							'description' => 'Complete stored list of reusable goals.',
							'items'       => $this->reusable_goal_output_schema(),
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
						'idempotent'  => false,
					),
				),
			)
		);
	}

	/**
	 * Checks whether the current user may replace reusable goals.
	 *
	 * @param array<array-key,mixed> $input Input values.
	 *
	 * @return true|WP_Error True when the user may replace reusable goals; otherwise, an error.
	 */
	public function permissions( $input ) {
		unset( $input );
		return current_user_can( 'edit_nab_experiments' ) ? true : new WP_Error( 'missing-capability', 'You cannot manage reusable goals.' );
	}

	/**
	 * Replaces and validates all reusable goals.
	 *
	 * @param array<array-key,mixed> $input Input containing reusable goals.
	 *
	 * @return array<string,mixed>|WP_Error The stored goals or an error.
	 */
	public function execute( $input ) {
		$allowed = $this->permissions( $input );
		if ( is_wp_error( $allowed ) ) {
			return $allowed;
		}

		$goals = is_array( $input['goals'] ?? null ) ? $input['goals'] : array();
		foreach ( $goals as &$goal ) {
			if ( ! is_array( $goal ) ) {
				continue; // @codeCoverageIgnore
			}
			$actions = is_array( $goal['conversionActions'] ?? null ) ? $goal['conversionActions'] : array();
			foreach ( $actions as &$action ) {
				if ( is_array( $action ) && empty( $action['id'] ) ) {
					$action['id'] = nab_uuid();
				}
			}
			$goal['conversionActions'] = $actions;
		}
		unset( $goal, $action );

		$result = Nelio_AB_Testing_Reusable_Entity_Manager::set_reusable_goals( $goals );
		return is_wp_error( $result ) ? $result : array( 'goals' => $result );
	}

	/**
	 * Returns the input schema for one reusable goal.
	 *
	 * @return array<string,mixed> Reusable-goal schema.
	 */
	private function reusable_goal_input_schema() {
		return $this->object_schema(
			array(
				'attributes'        => $this->object_schema(
					array(
						'name' => array(
							'type'        => 'string',
							'description' => 'Name of the reusable goal.',
						),
					),
					array( 'name' )
				),
				'conversionActions' => array(
					'type'        => 'array',
					'description' => 'Conversion actions that define when this goal converts.',
					'items'       => $this->object_schema(
						array(
							'id'         => array(
								'type'        => 'string',
								'description' => 'Optional internal identifier. The plugin generates one when it is omitted.',
							),
							'type'       => array(
								'type'        => 'string',
								'description' => 'Registered conversion action type.',
							),
							'attributes' => array(
								'type'                 => 'object',
								'additionalProperties' => true,
								'description'          => 'Attributes for the selected conversion action type.',
							),
							'scope'      => array(
								'type'                 => 'object',
								'additionalProperties' => true,
								'description'          => 'Scope in which the conversion action is tracked. For URL-scoped actions, each URL value may contain `*` as a wildcard matching any sequence of characters; URL values are not regular expressions.',
							),
						),
						array( 'type', 'attributes', 'scope' )
					),
				),
			),
			array( 'attributes', 'conversionActions' )
		);
	}

	/**
	 * Returns the output schema for one reusable goal.
	 *
	 * @return array<string,mixed> Reusable-goal output schema.
	 */
	private function reusable_goal_output_schema() {
		return $this->object_schema(
			array(
				'attributes'        => array(
					'type'                 => 'object',
					'description'          => 'Persisted reusable goal attributes, including its name.',
					'additionalProperties' => true,
				),
				'conversionActions' => array(
					'type'        => 'array',
					'description' => 'Persisted conversion actions defining when the reusable goal converts.',
					'items'       => $this->object_schema(
						array(
							'id'         => array(
								'type'        => 'string',
								'description' => 'Internal identifier of the stored conversion action.',
							),
							'type'       => array(
								'type'        => 'string',
								'description' => 'Registered conversion action type.',
							),
							'attributes' => array(
								'type'                 => 'object',
								'additionalProperties' => true,
								'description'          => 'Persisted attributes for the conversion action type.',
							),
							'scope'      => array(
								'type'                 => 'object',
								'additionalProperties' => true,
								'description'          => 'Persisted scope in which the conversion action is tracked.',
							),
						),
						array( 'id', 'type', 'attributes', 'scope' )
					),
				),
			),
			array( 'attributes', 'conversionActions' )
		);
	}
}
