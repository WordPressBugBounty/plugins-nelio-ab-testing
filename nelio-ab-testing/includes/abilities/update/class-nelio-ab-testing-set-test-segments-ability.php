<?php
/**
 * Ability for setting the list of segments on an editable test.
 *
 * @package Nelio_AB_Testing
 */

defined( 'ABSPATH' ) || exit;

/** Replaces the user-defined segment list of an editable test. */
class Nelio_AB_Testing_Set_Test_Segments_Ability extends Nelio_AB_Testing_Ability {

	/** Register the ability. */
	public function register() {
		$segment = $this->object_schema(
			array(
				'id'        => array(
					'type'        => 'string',
					'description' => 'Internal UUID of the segment.',
				),
				'label'     => array(
					'type'        => 'string',
					'description' => 'Positional label used when presenting the segment to a user.',
				),
				'name'      => array(
					'type'        => 'string',
					'description' => 'Name of the segment.',
				),
				'isVirtual' => array(
					'type'        => 'boolean',
					'description' => 'Whether this is the virtual combined segment used for result analysis.',
				),
			),
			array( 'id', 'label', 'name' )
		);
		wp_register_ability(
			'nelio-ab-testing/set-test-segments',
			array(
				'label'               => _x( 'Set test segments', 'text (ability name)', 'nelio-ab-testing' ),
				'description'         => 'Replaces the user-defined segment list and evaluation strategy of an existing editable Nelio test. Use it after the test exists to establish which user-defined segments should be present, then configure each segment with the dedicated attribute and rule abilities. Existing segments are preserved by UUID, omitted segments are removed, and entries without a UUID create new empty segments whose attributes and rules can be configured with the dedicated segment abilities. Nelio automatically creates a virtual combined segment for result analysis when two or more segments exist; do not provide that virtual segment.',
				'category'            => 'nelio-ab-testing',
				'input_schema'        => $this->object_schema(
					array(
						'testId'            => array(
							'type'        => 'integer',
							'description' => 'Identifier of the editable test.',
						),
						'segmentEvaluation' => array(
							'type'        => 'string',
							'enum'        => array( 'site', 'tested-page' ),
							'description' => 'Determines whether segmentation rules are evaluated across the whole site or only on the tested page.',
						),
						'segments'          => array(
							'type'        => 'array',
							'minItems'    => 1,
							'description' => 'Complete desired user-defined segment list. Include an existing segment UUID to preserve it, omit it to remove that segment, or omit the UUID to create a new empty segment. Do not include the virtual combined segment.',
							'items'       => $this->object_schema(
								array(
									'id' => array(
										'type'        => 'string',
										'description' => 'Optional UUID of an existing segment to preserve. Omit it when creating a new empty segment.',
									),
								),
								array()
							),
						),
					),
					array( 'testId', 'segmentEvaluation', 'segments' )
				),
				'output_schema'       => $this->object_schema(
					array(
						'testId'            => array(
							'type'        => 'integer',
							'description' => 'Identifier of the updated test.',
						),
						'segmentEvaluation' => array(
							'type'        => 'string',
							'enum'        => array( 'site', 'tested-page' ),
							'description' => 'Final segmentation evaluation strategy.',
						),
						'segments'          => array(
							'type'        => 'array',
							'description' => 'Compact summaries of the resulting user-defined segments, including the virtual `default` combined segment when applicable.',
							'items'       => $segment,
						),
					),
					array( 'testId', 'segmentEvaluation', 'segments' )
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
	 * Checks whether the current user may configure test segmentation.
	 *
	 * @param array<array-key,mixed> $input Input containing the test identifier.
	 *
	 * @return true|WP_Error True when the user can configure segmentation; otherwise, an error.
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
	 * Replaces and validates the user-defined segment list.
	 *
	 * @param array<array-key,mixed> $input Input containing the test identifier, evaluation strategy, and segment UUIDs.
	 *
	 * @return array<string,mixed>|WP_Error The configured segmentation, or an error.
	 */
	public function execute( $input ) {
		$allowed = $this->permissions( $input );
		if ( is_wp_error( $allowed ) ) {
			return $allowed;
		}

		$experiment = nab_get_experiment( $this->integer_input( $input, 'testId' ) );
		assert( ! is_wp_error( $experiment ) );

		$evaluation = is_string( $input['segmentEvaluation'] ?? null ) ? $input['segmentEvaluation'] : '';
		if ( ! in_array( $evaluation, array( 'site', 'tested-page' ), true ) ) {
			return new WP_Error( 'invalid-segmentation', 'The segment evaluation strategy is invalid.' );
		}

		$current_segments = $experiment->get_segments();
		$segments_by_id   = array();
		foreach ( $current_segments as $segment ) {
			$segments_by_id[ $segment['id'] ] = $segment;
		}

		$requested = is_array( $input['segments'] ?? null ) ? $input['segments'] : array();
		if ( empty( $requested ) ) {
			return new WP_Error( 'invalid-segment', 'The test must contain at least one segment.' );
		}
		$result = array();
		$seen   = array();
		foreach ( $requested as $segment ) {
			if ( ! is_array( $segment ) ) {
				return new WP_Error( 'invalid-segment', 'Every segment entry must be an object containing an optional UUID.' );
			}

			$segment_id = is_string( $segment['id'] ?? null ) ? trim( $segment['id'] ) : '';
			if ( '' === $segment_id ) {
				$result[] = array(
					'id'                => nab_uuid(),
					'attributes'        => array( 'name' => '' ),
					'segmentationRules' => array(),
				);
				continue;
			}

			if ( 'default' === $segment_id ) {
				return new WP_Error( 'invalid-segment', 'The virtual combined segment cannot be included in the user-defined segment list.' );
			}
			if ( isset( $seen[ $segment_id ] ) ) {
				return new WP_Error( 'duplicate-segment', 'The same segment UUID cannot appear more than once in the segment list.' );
			}
			if ( ! isset( $segments_by_id[ $segment_id ] ) ) {
				return new WP_Error( 'invalid-segment', 'The supplied segment UUID does not belong to this test.' );
			}

			$seen[ $segment_id ] = true;
			$result[]            = $segments_by_id[ $segment_id ];
		}

		$experiment->set_segment_evaluation( $evaluation );
		$experiment->set_segments( $result );
		nab_ai_refresh_experiment_status( $experiment );
		$experiment->save();

		return $this->bounded_response(
			array(
				'testId'            => $experiment->ID,
				'segmentEvaluation' => $experiment->get_segment_evaluation(),
				'segments'          => Nelio_AB_Testing_Test_Response_Formatter::segment_summaries( $experiment ),
			)
		);
	}
}
