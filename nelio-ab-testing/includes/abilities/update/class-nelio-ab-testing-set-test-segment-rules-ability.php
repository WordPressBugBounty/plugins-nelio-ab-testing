<?php
/**
 * Ability for setting one test segment's rules.
 *
 * @package Nelio_AB_Testing
 */

defined( 'ABSPATH' ) || exit;

/** Replaces the complete rule list of one configured test segment. */
class Nelio_AB_Testing_Set_Test_Segment_Rules_Ability extends Nelio_AB_Testing_Ability {

	/** Register the ability. */
	public function register() {
		wp_register_ability(
			'nelio-ab-testing/set-test-segment-rules',
			array(
				'label'               => _x( 'Set test segment rules', 'text (ability name)', 'nelio-ab-testing' ),
				'description'         => 'Replaces and validates the complete segmentation-rule list of one configured segment in an existing editable Nelio test. It does not merge the supplied rules with the current ones. The virtual `default` combined segment cannot be edited.',
				'category'            => 'nelio-ab-testing',
				'input_schema'        => $this->object_schema(
					array(
						'testId'    => array(
							'type'        => 'integer',
							'minimum'     => 1,
							'description' => 'Identifier of the editable test.',
						),
						'segmentId' => array(
							'type'        => 'string',
							'minLength'   => 1,
							'description' => 'UUID of the configured segment whose rules should be replaced. Do not use the virtual `default` combined segment.',
						),
						'rules'     => array(
							'type'        => 'array',
							'minItems'    => 1,
							'description' => 'Complete replacement list of segmentation rules. Do not provide internal rule IDs; the plugin preserves existing IDs by position and generates IDs for new rules. Use the schemas reported for the selected rule types in the test capabilities.',
							'items'       => $this->object_schema(
								array(
									'type'       => array(
										'type'        => 'string',
										'description' => 'Registered segmentation rule type.',
									),
									'attributes' => array(
										'type'        => 'object',
										'additionalProperties' => true,
										'description' => 'Complete attributes for the selected segmentation rule type.',
									),
								),
								array( 'type', 'attributes' )
							),
						),
					),
					array( 'testId', 'segmentId', 'rules' )
				),
				'output_schema'       => $this->object_schema(
					array(
						'testId'  => array(
							'type'        => 'integer',
							'description' => 'Identifier of the updated test.',
						),
						'segment' => $this->object_schema(
							array(
								'id'                => array(
									'type'        => 'string',
									'description' => 'Internal UUID of the segment.',
								),
								'label'             => array(
									'type'        => 'string',
									'description' => 'Positional label used when presenting the segment to a user.',
								),
								'attributes'        => array(
									'type'                 => 'object',
									'description'          => 'Segment attributes.',
									'additionalProperties' => true,
								),
								'segmentationRules' => array(
									'type'        => 'array',
									'description' => 'Complete resulting segmentation-rule list.',
									'items'       => array(
										'type'        => 'object',
										'description' => 'Segmentation rule with type-specific attributes.',
									),
								),
							),
							array( 'id', 'label', 'attributes', 'segmentationRules' )
						),
					),
					array( 'testId', 'segment' )
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
	 * Checks whether the current user may edit segment rules.
	 *
	 * @param array<array-key,mixed> $input Input containing the test identifier.
	 *
	 * @return true|WP_Error True when the user may edit rules; otherwise, an error.
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
	 * Replaces one segment's rules and validates the resulting segmentation.
	 *
	 * @param array<array-key,mixed> $input Input containing the test, segment, and rules.
	 *
	 * @return array<string,mixed>|WP_Error Updated segment or an error.
	 */
	public function execute( $input ) {
		$allowed = $this->permissions( $input );
		if ( is_wp_error( $allowed ) ) {
			return $allowed;
		}
		$experiment = nab_get_experiment( $this->integer_input( $input, 'testId' ) );
		assert( ! is_wp_error( $experiment ) );

		$segment_id = is_string( $input['segmentId'] ?? null ) ? trim( $input['segmentId'] ) : '';
		if ( 'default' === $segment_id ) {
			return new WP_Error( 'invalid-segment', 'The virtual combined segment cannot be edited.' );
		}

		/** @var list<TSegment> $segments */
		$segments = $experiment->get_segments();
		$index    = null;
		foreach ( $segments as $segment_index => $segment ) {
			if ( $segment['id'] === $segment_id ) {
				$index = $segment_index;
				break;
			}
		}
		if ( is_null( $index ) ) {
			return new WP_Error( 'invalid-segment', 'The requested segment does not exist in this test.' );
		}

		$rules_input = is_array( $input['rules'] ?? null ) ? $input['rules'] : array();
		$uuids       = array_map(
			static fn( $rule ) => $rule['id'],
			$segment['segmentationRules']
		);
		$rules       = $this->normalize_rules( $rules_input, $uuids );
		if ( is_wp_error( $rules ) ) {
			return $rules;
		}
		/** @var TSegment $segment */
		$segment                      = $segments[ $index ];
		$segment['segmentationRules'] = $rules;
		$segments[ $index ]           = $segment;

		$validation = Nelio_AB_Testing_Segment_Manager::ai_validate_segment( $segment );
		if ( is_wp_error( $validation ) ) {
			return $validation;
		}
		$segments = Nelio_AB_Testing_Segment_Manager::sanitize( $segments );
		$experiment->set_segments( $segments );
		nab_ai_refresh_experiment_status( $experiment );
		$experiment->save();
		$segment = Nelio_AB_Testing_Test_Response_Formatter::segment( $experiment, $segment_id );
		if ( is_wp_error( $segment ) ) {
			return $segment;
		}
		return $this->bounded_response(
			array(
				'testId'  => $experiment->ID,
				'segment' => $segment,
			)
		);
	}

	/**
	 * Normalizes rule input and generates missing rule UUIDs.
	 *
	 * @param mixed        $rules Segmentation rules.
	 * @param list<string> $uuids Existing segmentation-rule UUIDs.
	 *
	 * @return list<TSegmentation_Rule>|WP_Error Normalized rules or an error.
	 */
	private function normalize_rules( $rules, $uuids ) {
		if ( ! is_array( $rules ) || empty( $rules ) ) {
			return new WP_Error( 'invalid-segmentation-rules', 'A segment must contain at least one segmentation rule.' );
		}

		$result          = array();
		$used_singletons = array();
		foreach ( $rules as $index => $rule ) {
			if ( ! is_array( $rule ) ) {
				return new WP_Error( 'invalid-segmentation-rule', 'Every segmentation rule must be an object.' );
			}

			$type       = is_string( $rule['type'] ?? null ) ? trim( $rule['type'] ) : '';
			$definition = '' !== $type ? nelioab()->get_segmentation_rule_type( $type ) : null;
			if ( ! is_array( $definition ) ) {
				return new WP_Error( 'invalid-segmentation-rule', 'Every segmentation rule must use a registered segmentation rule type.' );
			}

			if ( ! isset( $definition['ai'] ) ) {
				return new WP_Error( 'unsupported-segmentation-rule', 'Every segmentation rule must use a type configured for AI abilities.' );
			}

			$attributes = is_array( $rule['attributes'] ?? null ) ? $rule['attributes'] : null;
			if ( is_null( $attributes ) ) {
				return new WP_Error( 'invalid-segmentation-rule', 'Every segmentation rule must provide an attributes object.' );
			}

			$normalized_attributes = array();
			foreach ( $attributes as $attribute => $value ) {
				if ( ! is_string( $attribute ) ) {
					return new WP_Error( 'invalid-segmentation-rule', 'Segmentation rule attributes must use string property names.' );
				}
				$normalized_attributes[ $attribute ] = $value;
			}

			if ( ! empty( $definition['singleton'] ) && isset( $used_singletons[ $type ] ) ) {
				return new WP_Error( 'invalid-segmentation-rule', 'A segment cannot contain the same singleton segmentation rule type more than once.' );
			}

			$used_singletons[ $type ] = true;
			$rule_id                  = $uuids[ $index ] ?? nab_uuid();
			$result[]                 = array(
				'id'         => $rule_id,
				'type'       => $type,
				'attributes' => $normalized_attributes,
			);
		}

		return $result;
	}
}
