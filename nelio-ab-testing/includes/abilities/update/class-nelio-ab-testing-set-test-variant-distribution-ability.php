<?php
/**
 * Ability for setting the complete variant distribution of an editable Nelio test.
 *
 * @package Nelio_AB_Testing
 */

defined( 'ABSPATH' ) || exit;

/** Sets the chance of every variant in an editable test. */
class Nelio_AB_Testing_Set_Test_Variant_Distribution_Ability extends Nelio_AB_Testing_Ability {

	/**
	 * Registers the ability with WordPress.
	 *
	 * @return void
	 */
	public function register() {
		wp_register_ability(
			'nelio-ab-testing/set-test-variant-distribution',
			array(
				'label'               => _x( 'Set the test variant distribution', 'text (ability name)', 'nelio-ab-testing' ),
				'description'         => 'Replaces the complete chance distribution of an existing editable Nelio test. Provide every variant, including the control, exactly once. The plugin may adjust the supplied percentages to the available allocation slots. If the resulting distribution is even, explicit `chance` values are removed and Nelio’s default equal distribution is used. The ability returns the final persisted values.',
				'category'            => 'nelio-ab-testing',
				'input_schema'        => $this->object_schema(
					array(
						'testId'       => array(
							'type'        => 'integer',
							'description' => 'Identifier of the editable test.',
						),
						'distribution' => array(
							'type'        => 'array',
							'description' => 'Complete distribution containing one entry for every test variant, including the control. The chances are percentages between 0 and 100.',
							'items'       => $this->object_schema(
								array(
									'id'     => array(
										'type'        => 'string',
										'description' => 'Variant identifier returned by the test-reading abilities. Use `control` for the control and the UUID for every other variant.',
									),
									'chance' => array(
										'type'        => 'number',
										'description' => 'Percentage of visitors assigned to this variant. All supplied values should describe the complete distribution.',
									),
								),
								array( 'id', 'chance' )
							),
						),
					),
					array( 'testId', 'distribution' )
				),
				'output_schema'       => $this->object_schema(
					array(
						'distribution' => array(
							'type'        => 'array',
							'description' => 'Final distribution persisted by the plugin, including the control and every other test variant.',
							'items'       => $this->object_schema(
								array(
									'id'     => array(
										'type'        => 'string',
										'description' => 'Variant identifier.',
									),
									'index'  => array(
										'type'        => 'integer',
										'description' => 'Zero-based position of the variant in the test.',
									),
									'label'  => array(
										'type'        => 'string',
										'description' => 'Current positional label of the variant.',
									),
									'chance' => array(
										'type'        => 'number',
										'description' => 'Final percentage of visitors assigned to the variant. The `chance` property is omitted when the distribution is even.',
									),
								),
								array( 'id', 'index', 'label' )
							),
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
						'idempotent'  => true,
					),
				),
			)
		);
	}

	/**
	 * Checks whether the current user may set the complete variant distribution.
	 *
	 * @param array<array-key,mixed> $input Input containing the test identifier.
	 *
	 * @return true|WP_Error True when the distribution can be set; otherwise, an error.
	 */
	public function permissions( $input ) {
		$experiment = nab_get_experiment( absint( $input['testId'] ?? 0 ) );
		if ( is_wp_error( $experiment ) ) {
			return $experiment;
		}
		$heatmap_check = $this->reject_heatmap( $experiment );
		if ( is_wp_error( $heatmap_check ) ) {
			return $heatmap_check;
		}

		$can_be_edited = $experiment->can_be_edited();
		if ( is_wp_error( $can_be_edited ) ) {
			return $can_be_edited;
		}

		if ( ! nelioab()->does_test_type_support_ai( $experiment->get_type() ) ) {
			return new WP_Error( 'unsupported-test-type', 'This test type is registered but cannot be configured through AI abilities.' );
		}

		if ( empty( Nelio_AB_Testing_Settings::instance()->get( 'is_alternative_distribution_allowed' ) ) ) {
			return new WP_Error( 'variant-distribution-disabled', 'Custom variant distribution is disabled in Nelio A/B Testing settings.' );
		}

		return true;
	}

	/**
	 * Sets and returns the complete chance distribution of a test.
	 *
	 * The supplied percentages are converted to the same allocation slots used by
	 * the editor, then persisted on every variant in a single operation.
	 *
	 * @param array<array-key,mixed> $input Input containing the test identifier and complete distribution.
	 *
	 * @return array<string,mixed>|WP_Error The persisted distribution, or an error.
	 */
	public function execute( $input ) {
		$allowed = $this->permissions( $input );
		if ( is_wp_error( $allowed ) ) {
			return $allowed;
		}

		$experiment = nab_get_experiment( absint( $input['testId'] ?? 0 ) );
		assert( ! is_wp_error( $experiment ) );

		$distribution = isset( $input['distribution'] ) && is_array( $input['distribution'] ) ? $input['distribution'] : array();
		$alternatives = $experiment->get_alternatives();
		$by_id        = array();
		foreach ( $distribution as $entry ) {
			if ( ! is_array( $entry ) || ! is_string( $entry['id'] ?? null ) || '' === $entry['id'] || array_key_exists( $entry['id'], $by_id ) ) {
				return new WP_Error( 'invalid-variant-distribution', 'The variant distribution must contain each variant identifier exactly once.' );
			}

			$chance = $entry['chance'] ?? null;
			if ( ! is_numeric( $chance ) || ! is_finite( (float) $chance ) || (float) $chance < 0 || (float) $chance > 100 ) {
				return new WP_Error(
					'invalid-variant-distribution',
					'Each variant `chance` value must be a number between 0 and 100.'
				);
			}
			$by_id[ $entry['id'] ] = (float) $chance;
		}

		$expected_ids = array_column( $alternatives, 'id' );
		if ( count( $by_id ) !== count( $expected_ids ) || array_diff( $expected_ids, array_keys( $by_id ) ) || array_diff( array_keys( $by_id ), $expected_ids ) ) {
			return new WP_Error( 'invalid-variant-distribution', 'The variant distribution must include the control and every other test variant exactly once.' );
		}

		$slots = nab_max_combinations();
		$sizes = array_map( fn( $variant ) => $by_id[ $variant['id'] ], $alternatives );
		$cuts  = $this->allocate_slots( $sizes, $slots );
		$even_sizes = array_map( fn() => 1.0, $alternatives );
		$even_cuts  = $this->allocate_slots( $even_sizes, $slots );

		foreach ( $alternatives as $index => &$alternative ) {
			if ( $cuts === $even_cuts ) {
				unset( $alternative['attributes']['chance'] );
				continue;
			}
			$alternative['attributes']['chance'] = round( ( $cuts[ $index ] / $slots ) * 100, 2 );
		}
		unset( $alternative );

		$experiment->set_alternatives( $alternatives );
		nab_ai_refresh_experiment_status( $experiment );
		$experiment->save();

		$summary = Nelio_AB_Testing_Test_Response_Formatter::variant_summaries( $experiment );
		$result  = array_map(
			function ( $variant ) {
				$row = array(
					'id'    => $variant['id'],
					'index' => $variant['index'],
					'label' => $variant['label'],
				);
				if ( is_int( $variant['chance'] ?? null ) || is_float( $variant['chance'] ?? null ) ) {
					$row['chance'] = (float) $variant['chance'];
				}
				return $row;
			},
			$summary
		);

		return array( 'distribution' => $result );
	}

	/**
	 * Converts percentages into the editor's allocation slots.
	 *
	 * @param list<float> $sizes Percentages.
	 * @param int          $slots Number of allocation slots.
	 *
	 * @return list<int> Allocation slots.
	 */
	private function allocate_slots( $sizes, $slots ) {
		$minimum = 100 / $slots;
		$sizes   = array_map( fn( $size ) => max( $size, $minimum ), $sizes );
		$result  = array_map( fn( $size ) => absint( round( $slots * ( $size / 100 ) ) ), $sizes );
		if ( empty( $result ) ) {
			return array();
		}

		while ( array_sum( $result ) > $slots ) {
			$max       = max( $result );
			$keys      = array_keys( $result, $max, true );
			$max_index = absint( end( $keys ) );
			--$result[ $max_index ];
		}

		while ( array_sum( $result ) < $slots ) {
			foreach ( $result as $index => $value ) {
				if ( array_sum( $result ) >= $slots ) {
					break;
				}
				++$result[ $index ];
			}
		}

		return array_values( $result );
	}
}
