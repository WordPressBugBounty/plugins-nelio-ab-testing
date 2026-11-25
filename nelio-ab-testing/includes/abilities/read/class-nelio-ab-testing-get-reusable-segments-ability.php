<?php
/**
 * Ability for reading reusable segments.
 *
 * @package Nelio_AB_Testing
 */

defined( 'ABSPATH' ) || exit;

/** Reads the site's reusable segments. */
class Nelio_AB_Testing_Get_Reusable_Segments_Ability extends Nelio_AB_Testing_Ability {

	/**
	 * Registers the ability with WordPress.
	 *
	 * @return void
	 */
	public function register() {
		$segment = $this->object_schema(
			array(
				'attributes'        => array(
					'type'                 => 'object',
					'description'          => 'Reusable segment attributes, including its name.',
					'additionalProperties' => true,
				),
				'segmentationRules' => array(
					'type'        => 'array',
					'description' => 'Segmentation rules defining which visitors belong to the segment. Their attributes depend on their registered types.',
					'items'       => array(
						'type'        => 'object',
						'description' => 'Segmentation rule with a registered type and attributes.',
					),
				),
			),
			array( 'attributes', 'segmentationRules' )
		);
		wp_register_ability(
			'nelio-ab-testing/get-reusable-segments',
			array(
				'label'               => _x( 'Read reusable segments', 'text (ability name)', 'nelio-ab-testing' ),
				'description'         => 'Reads the complete list of reusable segments configured on the site.',
				'category'            => 'nelio-ab-testing',
				'input_schema'        => $this->object_schema( array() ),
				'output_schema'       => $this->object_schema(
					array(
						'segments' => array(
							'type'        => 'array',
							'description' => 'Reusable segments currently configured on the site.',
							'items'       => $segment,
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
	 * Checks whether the current user may read reusable segments.
	 *
	 * @param array<array-key,mixed> $input Input values.
	 *
	 * @return true|WP_Error True when the user may read reusable segments; otherwise, an error.
	 */
	public function permissions( $input ) {
		unset( $input );
		return current_user_can( 'edit_nab_experiments' ) ? true : new WP_Error( 'missing-capability', 'You cannot read reusable segments.' );
	}

	/**
	 * Returns all reusable segments.
	 *
	 * @param array<array-key,mixed> $input Input values.
	 *
	 * @return array<string,mixed>|WP_Error Reusable segments or an error.
	 */
	public function execute( $input ) {
		$allowed = $this->permissions( $input );
		if ( is_wp_error( $allowed ) ) {
			return $allowed;
		}
		return array( 'segments' => Nelio_AB_Testing_Reusable_Entity_Manager::get_reusable_segments() );
	}
}
