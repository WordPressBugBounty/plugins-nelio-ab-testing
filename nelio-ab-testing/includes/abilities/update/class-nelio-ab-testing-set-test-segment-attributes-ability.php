<?php
/**
 * Ability for setting one test segment's attributes.
 *
 * @package Nelio_AB_Testing
 */

defined( 'ABSPATH' ) || exit;

/** Replaces the attributes of one configured test segment. */
class Nelio_AB_Testing_Set_Test_Segment_Attributes_Ability extends Nelio_AB_Testing_Ability {

	/** Register the ability. */
	public function register() {
		wp_register_ability(
			'nelio-ab-testing/set-test-segment-attributes',
			array(
				'label'               => _x( 'Set test segment attributes', 'text (ability name)', 'nelio-ab-testing' ),
				'description'         => 'Replaces all attributes of one configured segment in an existing editable Nelio test. Configure the segment’s rules before using this ability, because the attributes are not merged with the current values. The virtual `default` combined segment cannot be edited.',
				'category'            => 'nelio-ab-testing',
				'input_schema'        => $this->object_schema(
					array(
						'testId'     => array(
							'type'        => 'integer',
							'minimum'     => 1,
							'description' => 'Identifier of the editable test.',
						),
						'segmentId'  => array(
							'type'        => 'string',
							'minLength'   => 1,
							'description' => 'UUID of the configured segment whose attributes should be replaced. Do not use the virtual `default` combined segment.',
						),
						'attributes' => array(
							'type'                 => 'object',
							'description'          => 'Complete replacement attributes for the segment. Configure the segment’s rules before using this ability.',
							'properties'           => array(
								'name' => array(
									'type'        => 'string',
									'description' => 'Display name for the segment.',
								),
							),
							'required'             => array( 'name' ),
							'additionalProperties' => false,
						),
					),
					array( 'testId', 'segmentId', 'attributes' )
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
									'description'          => 'Resulting segment attributes.',
									'additionalProperties' => true,
								),
								'segmentationRules' => array(
									'type'        => 'array',
									'description' => 'Resulting segmentation-rule list.',
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
	 * Checks whether the current user may edit segment attributes.
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
	 * Replaces one segment's attributes and validates the resulting segmentation.
	 *
	 * @param array<array-key,mixed> $input Input containing the test, segment, and attributes.
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

		$segments      = $experiment->get_segments();
		$found         = false;
		$segment_index = null;
		foreach ( $segments as $index => &$segment ) {
			if ( $segment['id'] !== $segment_id ) {
				continue;
			}
			$attributes = is_array( $input['attributes'] ?? null ) ? $input['attributes'] : array();
			$name       = is_string( $attributes['name'] ?? null ) ? trim( $attributes['name'] ) : '';
			if ( '' === $name ) {
				return new WP_Error( 'invalid-segment', 'A segment must have a non-empty name.' );
			}
			$segment['attributes'] = array( 'name' => sanitize_text_field( $name ) );
			$segment_index         = $index;
			$found                 = true;
			break;
		}
		unset( $segment );

		if ( ! $found || ! is_int( $segment_index ) ) {
			return new WP_Error( 'invalid-segment', 'The requested segment does not exist in this test.' );
		}

		$validation = Nelio_AB_Testing_Segment_Manager::ai_validate_segment( $segments[ $segment_index ] );
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
}
