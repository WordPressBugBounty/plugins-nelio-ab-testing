<?php
/**
 * Ability for updating basic Nelio test settings.
 *
 * @package Nelio_AB_Testing
 */

defined( 'ABSPATH' ) || exit;

/**
 * Updates basic settings in editable tests.
 */
class Nelio_AB_Testing_Set_Test_Settings_Ability extends Nelio_AB_Testing_Ability {

	/**
	 * Register the ability.
	 *
	 * @return void
	 */
	public function register() {
		wp_register_ability(
			'nelio-ab-testing/set-test-settings',
			array(
				'label'               => _x( 'Update test settings', 'text (ability name)', 'nelio-ab-testing' ),
				'description'         => 'Updates basic settings of an editable Nelio test, including renaming the test, changing its description or schedule, configuring its end conditions, and enabling or disabling automatic winner application. It never starts the test.',
				'category'            => 'nelio-ab-testing',
				'input_schema'        => $this->object_schema(
					array(
						'testId'                     => array(
							'type'        => 'integer',
							'description' => 'Identifier of the editable test.',
						),
						'name'                       => array(
							'type'        => 'string',
							'description' => 'Optional new name for the test.',
						),
						'description'                => array(
							'type'        => 'string',
							'description' => 'Optional explanation of the test hypothesis or objective.',
						),
						'startDate'                  => array(
							'type'        => 'string',
							'description' => 'Optional start date and time as an ISO 8601 UTC string. Use an empty string to start the test immediately when it is started. This setting may be unavailable depending on the site configuration.',
						),
						'endMode'                    => array(
							'type'        => 'string',
							'enum'        => array( 'manual', 'duration', 'page-views', 'confidence' ),
							'description' => 'Optional condition that determines when the test should end: manual, duration, page-views, or confidence. Some options may be unavailable depending on the site configuration or test type.',
						),
						'endValue'                   => array(
							'type'        => 'integer',
							'minimum'     => 0,
							'description' => 'Optional value for the selected end mode, such as a number of days, page views, or a confidence percentage.',
						),
						'autoAlternativeApplication' => array(
							'type'        => 'boolean',
							'description' => 'Optional flag that determines whether Nelio should automatically apply the winning variant when the test ends.',
						),
					),
					array( 'testId' )
				),
				'output_schema'       => $this->object_schema(
					array(
						'test' => $this->object_schema(
							array(
								'id'          => array(
									'type'        => 'integer',
									'description' => 'Identifier of the updated test.',
								),
								'name'        => array(
									'type'        => 'string',
									'description' => 'Current name of the test.',
								),
								'description' => array(
									'type'        => 'string',
									'description' => 'Current explanation of the test hypothesis or objective.',
								),
								'status'      => array(
									'type'        => 'string',
									'description' => 'Current status of the test. This ability never starts the test.',
								),
								'startDate'   => array(
									'type'        => 'string',
									'description' => 'Current start date and time, or an empty string when the test starts immediately.',
								),
								'endMode'     => array(
									'type'        => 'string',
									'enum'        => array( 'manual', 'duration', 'page-views', 'confidence' ),
									'description' => 'Current condition that determines when the test should end: manual, duration, page-views, or confidence.',
								),
								'endValue'    => array(
									'type'        => 'integer',
									'description' => 'Current value for the selected end mode.',
								),
								'autoAlternativeApplication' => array(
									'type'        => 'boolean',
									'description' => 'Whether the winning variant will be applied automatically when the test ends.',
								),
							),
							array( 'id', 'name', 'description', 'status', 'startDate', 'endMode', 'endValue', 'autoAlternativeApplication' )
						),
					),
					array( 'test' )
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
	 * Check whether the current user may update test settings.
	 *
	 * @param array<array-key,mixed> $input Input.
	 *
	 * @return true|WP_Error
	 */
	public function permissions( $input ) {
		$experiment = nab_get_experiment( absint( $input['testId'] ?? 0 ) );
		if ( is_wp_error( $experiment ) ) {
			return $experiment;
		}

		$can_be_edited = $experiment->can_be_edited();
		if ( is_wp_error( $can_be_edited ) ) {
			return $can_be_edited;
		}

		if ( ! nelioab()->does_test_type_support_ai( $experiment->get_type() ) ) {
			return new WP_Error( 'unsupported-test-type', 'This test type is registered but cannot be configured through AI abilities.' );
		}

		return true;
	}

	/**
	 * Update basic settings in an editable test.
	 *
	 * @param array<array-key,mixed> $input Input.
	 *
	 * @return array<string,mixed>|WP_Error
	 */
	public function execute( $input ) {
		$allowed = $this->permissions( $input );
		if ( is_wp_error( $allowed ) ) {
			return $allowed;
		}

		$experiment = nab_get_experiment( absint( $input['testId'] ?? 0 ) );
		assert( ! is_wp_error( $experiment ) );

		$requested_end_mode = $experiment->get_end_mode();
		if ( array_key_exists( 'endMode', $input ) ) {
			$requested_end_mode = is_string( $input['endMode'] ) ? $input['endMode'] : '';
			if ( ! in_array( $requested_end_mode, array( 'confidence', 'duration', 'manual', 'page-views' ), true ) ) {
				return new WP_Error( 'invalid-end-mode', 'The selected test end mode is not supported.' );
			}
		}

		if ( 'nab/heatmap' === $experiment->get_type() && 'confidence' === $requested_end_mode ) {
			return new WP_Error( 'unsupported-setting', 'Confidence-based ending is not available for heatmap tests.' );
		}

		$requests_scheduling = ( array_key_exists( 'startDate', $input ) && ! empty( $input['startDate'] ) ) || ( array_key_exists( 'endMode', $input ) && 'manual' !== $requested_end_mode ) || array_key_exists( 'endValue', $input );
		if ( $requests_scheduling && ! nab_is_subscribed_to( 'professional' ) ) {
			return new WP_Error( 'unsupported-setting', 'Scheduling test start and end conditions requires a Professional-or-above subscription.' );
		}

		if ( array_key_exists( 'name', $input ) ) {
			$name = trim( is_string( $input['name'] ) ? sanitize_text_field( $input['name'] ) : '' );
			if ( empty( $name ) ) {
				return new WP_Error( 'invalid-test-name', 'The test name cannot be empty.' );
			}
			$experiment->set_name( $name );
		}

		if ( array_key_exists( 'description', $input ) ) {
			$experiment->set_description( trim( is_string( $input['description'] ) ? $this->text( $input['description'] ) : '' ) );
		}

		if ( array_key_exists( 'startDate', $input ) ) {
			$experiment->set_start_date( is_string( $input['startDate'] ) ? sanitize_text_field( $input['startDate'] ) : '' );
		}

		$end_mode  = $experiment->get_end_mode();
		$end_value = $experiment->get_end_value();
		if ( array_key_exists( 'endMode', $input ) ) {
			$end_mode = $requested_end_mode;
		}

		if ( array_key_exists( 'endValue', $input ) ) {
			$end_value = absint( $input['endValue'] );
		}

		if ( 'confidence' === $end_mode && ( $end_value < 95 || $end_value > 99 ) ) {
			return new WP_Error( 'invalid-end-value', 'Confidence-based ending requires a value from 95 to 99, inclusive.' );
		}

		if ( array_key_exists( 'endMode', $input ) || array_key_exists( 'endValue', $input ) ) {
			$experiment->set_end_mode_and_value( $end_mode, $end_value );
		}

		if ( array_key_exists( 'autoAlternativeApplication', $input ) ) {
			$experiment->set_auto_alternative_application( (bool) $input['autoAlternativeApplication'] );
		}

		if ( $experiment instanceof Nelio_AB_Testing_Heatmap ) {
			nab_ai_refresh_heatmap_status( $experiment );
		} else {
			nab_ai_refresh_experiment_status( $experiment );
		}
		$experiment->save();

		$start_date = $experiment->get_start_date();
		return array(
			'test' => array(
				'id'                         => $experiment->ID,
				'name'                       => $experiment->get_name(),
				'description'                => $experiment->get_description(),
				'status'                     => $experiment->get_status(),
				'startDate'                  => false === $start_date ? '' : $start_date,
				'endMode'                    => $experiment->get_end_mode(),
				'endValue'                   => $experiment->get_end_value(),
				'autoAlternativeApplication' => $experiment->is_auto_alternative_application_enabled(),
			),
		);
	}
}
