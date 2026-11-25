<?php
/**
 * Ability for configuring heatmap participation conditions.
 *
 * @package Nelio_AB_Testing
 */

defined( 'ABSPATH' ) || exit;

/** Configures the segmentation rules that determine heatmap participation. */
class Nelio_AB_Testing_Set_Heatmap_Participation_Ability extends Nelio_AB_Testing_Ability {

	/**
	 * Registers the ability with WordPress.
	 *
	 * @return void
	 */
	public function register() {
		$rule = $this->object_schema(
			array(
				'type'       => array(
					'type'        => 'string',
					'description' => 'Registered segmentation rule type.',
				),
				'attributes' => array(
					'type'                 => 'object',
					'additionalProperties' => true,
					'description'          => 'Complete attributes for the selected segmentation rule type.',
				),
			),
			array( 'type', 'attributes' )
		);
		wp_register_ability(
			'nelio-ab-testing/set-heatmap-participation',
			array(
				'label'               => _x( 'Set heatmap participation', 'text (ability name)', 'nelio-ab-testing' ),
				'description'         => 'Replaces the segmentation rules that determine which visitors participate in an editable heatmap test. Provide the complete list of rules; provide an empty list to clear participation conditions. Retrieve reusable segments separately and provide their rules when they should define participation.',
				'category'            => 'nelio-ab-testing',
				'input_schema'        => $this->object_schema(
					array(
						'testId' => array(
							'type'        => 'integer',
							'minimum'     => 1,
							'description' => 'Identifier of the editable heatmap test.',
						),
						'rules'  => array(
							'type'        => 'array',
							'description' => 'Complete replacement list of segmentation rules. Do not provide internal rule IDs.',
							'items'       => $rule,
						),
					),
					array( 'testId', 'rules' )
				),
				'output_schema'       => $this->object_schema(
					array(
						'testId' => array(
							'type'        => 'integer',
							'description' => 'Identifier of the updated heatmap test.',
						),
						'rules'  => array(
							'type'        => 'array',
							'description' => 'Final segmentation rules used to determine heatmap participation.',
							'items'       => $rule,
						),
					),
					array( 'testId', 'rules' )
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
	 * Checks whether the current user may configure heatmap participation.
	 *
	 * @param array<array-key,mixed> $input Input containing the test identifier.
	 *
	 * @return true|WP_Error True when the user may configure participation; otherwise, an error.
	 */
	public function permissions( $input ) {
		$experiment = nab_get_experiment( $this->integer_input( $input, 'testId' ) );
		if ( is_wp_error( $experiment ) ) {
			return $experiment;
		}
		if ( ! $experiment instanceof Nelio_AB_Testing_Heatmap ) {
			return new WP_Error( 'invalid-test-type', 'This ability only configures heatmap tests.' );
		}
		if ( ! nelioab()->does_test_type_support_ai( $experiment->get_type() ) ) {
			return new WP_Error( 'unsupported-test-type', 'This heatmap test is not configured for AI abilities.' );
		}
		return $experiment->can_be_edited();
	}

	/**
	 * Sets the segmentation rules that determine heatmap participation.
	 *
	 * @param array<array-key,mixed> $input Input containing the test and complete rule list.
	 *
	 * @return array<string,mixed>|WP_Error Updated participation rules or an error.
	 */
	public function execute( $input ) {
		$allowed = $this->permissions( $input );
		if ( is_wp_error( $allowed ) ) {
			return $allowed;
		}

		$experiment = nab_get_experiment( $this->integer_input( $input, 'testId' ) );
		assert( $experiment instanceof Nelio_AB_Testing_Heatmap );
		$rules      = is_array( $input['rules'] ?? null ) ? $input['rules'] : array();
		$uuids      = array_map(
			static fn( $rule ) => $rule['id'],
			$experiment->get_participation_conditions()
		);
		$normalized = $this->normalize_rules( $rules, $uuids );
		if ( is_wp_error( $normalized ) ) {
			return $normalized;
		}

		if ( ! empty( $normalized ) ) {
			$segment    = array(
				'id'                => nab_uuid(),
				'attributes'        => array( 'name' => '' ),
				'segmentationRules' => $normalized,
			);
			$validation = Nelio_AB_Testing_Segment_Manager::ai_validate_segment( $segment );
			if ( is_wp_error( $validation ) ) {
				return $validation;
			}
			$normalized = Nelio_AB_Testing_Segment_Manager::sanitize( array( $segment ) )[0]['segmentationRules'];
		}

		$experiment->set_participation_conditions( $normalized );
		nab_ai_refresh_heatmap_status( $experiment );
		$experiment->save();

		return array(
			'testId' => $experiment->get_id(),
			'rules'  => $normalized,
		);
	}

	/**
	 * Normalizes rule input and generates missing rule UUIDs.
	 *
	 * @param mixed        $rules Segmentation rules.
	 * @param list<string> $uuids Existing rule UUIDs.
	 *
	 * @return list<TSegmentation_Rule>|WP_Error Normalized rules or an error.
	 */
	private function normalize_rules( $rules, $uuids ) {
		if ( ! is_array( $rules ) ) {
			return new WP_Error( 'invalid-segmentation-rules', 'Heatmap participation rules must be an array.' );
		}

		$result          = array();
		$used_singletons = array();
		foreach ( $rules as $index => $rule ) {
			if ( ! is_array( $rule ) ) {
				return new WP_Error( 'invalid-segmentation-rule', 'Every heatmap participation rule must be an object.' );
			}
			$type       = is_string( $rule['type'] ?? null ) ? trim( $rule['type'] ) : '';
			$definition = '' !== $type ? nelioab()->get_segmentation_rule_type( $type ) : null;
			if ( ! is_array( $definition ) || ! isset( $definition['ai'] ) ) {
				return new WP_Error( 'unsupported-segmentation-rule', 'Every heatmap participation rule must use a type configured for AI abilities.' );
			}
			$attributes = is_array( $rule['attributes'] ?? null ) ? $rule['attributes'] : null;
			if ( is_null( $attributes ) ) {
				return new WP_Error( 'invalid-segmentation-rule', 'Every heatmap participation rule must provide an attributes object.' );
			}
			$normalized_attributes = array();
			foreach ( $attributes as $attribute => $value ) {
				if ( ! is_string( $attribute ) ) {
					return new WP_Error( 'invalid-segmentation-rule', 'Heatmap participation rule attributes must use string property names.' );
				}
				$normalized_attributes[ $attribute ] = $value;
			}
			if ( ! empty( $definition['singleton'] ) && isset( $used_singletons[ $type ] ) ) {
				return new WP_Error( 'invalid-segmentation-rule', 'Heatmap participation cannot contain the same singleton rule type more than once.' );
			}
			$used_singletons[ $type ] = true;
			$result[]                 = array(
				'id'         => $uuids[ $index ] ?? nab_uuid(),
				'type'       => $type,
				'attributes' => $normalized_attributes,
			);
		}

		return $result;
	}
}
