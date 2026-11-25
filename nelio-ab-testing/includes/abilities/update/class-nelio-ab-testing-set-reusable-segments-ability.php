<?php
/**
 * Ability for replacing reusable segments.
 *
 * @package Nelio_AB_Testing
 */

defined( 'ABSPATH' ) || exit;

/** Replaces the site's reusable segments. */
class Nelio_AB_Testing_Set_Reusable_Segments_Ability extends Nelio_AB_Testing_Ability {

	/**
	 * Registers the ability with WordPress.
	 *
	 * @return void
	 */
	public function register() {
		wp_register_ability(
			'nelio-ab-testing/set-reusable-segments',
			array(
				'label'               => _x( 'Set reusable segments', 'text (ability name)', 'nelio-ab-testing' ),
				'description'         => 'Replaces the complete list of reusable segments available on the site. The plugin validates and stores the resulting segments without changing any existing test.',
				'category'            => 'nelio-ab-testing',
				'input_schema'        => $this->object_schema(
					array(
						'segments' => array(
							'type'        => 'array',
							'description' => 'Complete list of reusable segments. Each segment must have attributes, including a name, and its segmentation rules. Do not provide segment identifiers; reusable segments do not expose them.',
							'items'       => $this->reusable_segment_input_schema(),
						),
					)
				),
				'output_schema'       => $this->object_schema(
					array(
						'segments' => array(
							'type'        => 'array',
							'description' => 'Complete stored list of reusable segments.',
							'items'       => $this->reusable_segment_output_schema(),
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
	 * Checks whether the current user may replace reusable segments.
	 *
	 * @param array<array-key,mixed> $input Input values.
	 *
	 * @return true|WP_Error True when the user may replace reusable segments; otherwise, an error.
	 */
	public function permissions( $input ) {
		unset( $input );
		return current_user_can( 'edit_nab_experiments' ) ? true : new WP_Error( 'missing-capability', 'You cannot manage reusable segments.' );
	}

	/**
	 * Replaces and validates all reusable segments.
	 *
	 * @param array<array-key,mixed> $input Input containing reusable segments.
	 *
	 * @return array<string,mixed>|WP_Error The stored segments or an error.
	 */
	public function execute( $input ) {
		$allowed = $this->permissions( $input );
		if ( is_wp_error( $allowed ) ) {
			return $allowed;
		}

		$segments = is_array( $input['segments'] ?? null ) ? $input['segments'] : array();
		foreach ( $segments as &$segment ) {
			if ( ! is_array( $segment ) ) {
				continue; // @codeCoverageIgnore
			}
			$rules = is_array( $segment['segmentationRules'] ?? null ) ? $segment['segmentationRules'] : array();
			foreach ( $rules as &$rule ) {
				if ( is_array( $rule ) && empty( $rule['id'] ) ) {
					$rule['id'] = nab_uuid();
				}
			}
			$segment['segmentationRules'] = $rules;
		}
		unset( $segment, $rule );

		$result = Nelio_AB_Testing_Reusable_Entity_Manager::set_reusable_segments( $segments );
		return is_wp_error( $result ) ? $result : array( 'segments' => $result );
	}

	/**
	 * Returns the input schema for one reusable segment.
	 *
	 * @return array<string,mixed> Reusable-segment schema.
	 */
	private function reusable_segment_input_schema() {
		return $this->object_schema(
			array(
				'attributes'        => $this->object_schema(
					array(
						'name' => array(
							'type'        => 'string',
							'description' => 'Name of the reusable segment.',
						),
					),
					array( 'name' )
				),
				'segmentationRules' => array(
					'type'        => 'array',
					'description' => 'Segmentation rules that define which visitors belong to this segment.',
					'items'       => $this->object_schema(
						array(
							'id'         => array(
								'type'        => 'string',
								'description' => 'Optional internal identifier. The plugin generates one when it is omitted.',
							),
							'type'       => array(
								'type'        => 'string',
								'description' => 'Registered segmentation rule type.',
							),
							'attributes' => array(
								'type'                 => 'object',
								'additionalProperties' => true,
								'description'          => 'Attributes for the selected segmentation rule type.',
							),
						),
						array( 'type', 'attributes' )
					),
				),
			),
			array( 'attributes', 'segmentationRules' )
		);
	}

	/**
	 * Returns the output schema for one reusable segment.
	 *
	 * @return array<string,mixed> Reusable-segment output schema.
	 */
	private function reusable_segment_output_schema() {
		return $this->object_schema(
			array(
				'attributes'        => array(
					'type'                 => 'object',
					'description'          => 'Persisted reusable segment attributes, including its name.',
					'additionalProperties' => true,
				),
				'segmentationRules' => array(
					'type'        => 'array',
					'description' => 'Persisted segmentation rules defining which visitors belong to the reusable segment.',
					'items'       => $this->object_schema(
						array(
							'id'         => array(
								'type'        => 'string',
								'description' => 'Internal identifier of the stored segmentation rule.',
							),
							'type'       => array(
								'type'        => 'string',
								'description' => 'Registered segmentation rule type.',
							),
							'attributes' => array(
								'type'                 => 'object',
								'additionalProperties' => true,
								'description'          => 'Persisted attributes for the segmentation rule type.',
							),
						),
						array( 'id', 'type', 'attributes' )
					),
				),
			),
			array( 'attributes', 'segmentationRules' )
		);
	}
}
