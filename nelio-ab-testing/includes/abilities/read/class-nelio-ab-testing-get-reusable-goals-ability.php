<?php
/**
 * Ability for reading reusable conversion goals.
 *
 * @package Nelio_AB_Testing
 */

defined( 'ABSPATH' ) || exit;

/** Reads the site's reusable conversion goals. */
class Nelio_AB_Testing_Get_Reusable_Goals_Ability extends Nelio_AB_Testing_Ability {

	/**
	 * Registers the ability with WordPress.
	 *
	 * @return void
	 */
	public function register() {
		$goal = $this->object_schema(
			array(
				'attributes'        => array(
					'type'                 => 'object',
					'description'          => 'Reusable goal attributes, including its name.',
					'additionalProperties' => true,
				),
				'conversionActions' => array(
					'type'        => 'array',
					'description' => 'Conversion actions defining when the goal converts. Their attributes depend on their registered types.',
					'items'       => array(
						'type'        => 'object',
						'description' => 'Conversion action with a registered type, attributes, and scope.',
					),
				),
			),
			array( 'attributes', 'conversionActions' )
		);
		wp_register_ability(
			'nelio-ab-testing/get-reusable-goals',
			array(
				'label'               => _x( 'Read reusable goals', 'text (ability name)', 'nelio-ab-testing' ),
				'description'         => 'Reads the complete list of reusable conversion goals configured on the site.',
				'category'            => 'nelio-ab-testing',
				'input_schema'        => $this->object_schema( array() ),
				'output_schema'       => $this->object_schema(
					array(
						'goals' => array(
							'type'        => 'array',
							'description' => 'Reusable conversion goals currently configured on the site.',
							'items'       => $goal,
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
	 * Checks whether the current user may read reusable goals.
	 *
	 * @param array<array-key,mixed> $input Input values.
	 *
	 * @return true|WP_Error True when the user may read reusable goals; otherwise, an error.
	 */
	public function permissions( $input ) {
		unset( $input );
		return current_user_can( 'edit_nab_experiments' ) ? true : new WP_Error( 'missing-capability', 'You cannot read reusable goals.' );
	}

	/**
	 * Returns all reusable goals.
	 *
	 * @param array<array-key,mixed> $input Input values.
	 *
	 * @return array<string,mixed>|WP_Error Reusable goals or an error.
	 */
	public function execute( $input ) {
		$allowed = $this->permissions( $input );
		if ( is_wp_error( $allowed ) ) {
			return $allowed;
		}
		return array( 'goals' => Nelio_AB_Testing_Reusable_Entity_Manager::get_reusable_goals() );
	}
}
