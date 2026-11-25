<?php
/**
 * Ability for reading a Nelio test summary.
 *
 * @package Nelio_AB_Testing
 */

defined( 'ABSPATH' ) || exit;

/**
 * Reads the basic configuration of a Nelio test.
 */
class Nelio_AB_Testing_Get_Test_Ability extends Nelio_AB_Testing_Ability {

	/**
	 * Registers the ability with WordPress.
	 *
	 * @return void
	 */
	public function register() {
		$summary           = array(
			'id'       => array(
				'type'        => 'integer',
				'description' => 'Identifier of the test.',
			),
			'siteId'   => array(
				'type'        => 'integer',
				'description' => 'Identifier of the site containing the test.',
			),
			'name'     => array(
				'type'        => 'string',
				'description' => 'Name of the test.',
			),
			'type'     => array(
				'type'        => 'string',
				'description' => 'Registered type of the test.',
			),
			'status'   => array(
				'type'        => 'string',
				'enum'        => array( 'draft', 'ready', 'scheduled', 'running', 'paused', 'paused_draft', 'finished' ),
				'description' => 'Current status of the test.',
			),
			'adminUrl' => array(
				'type'        => 'string',
				'description' => 'Dashboard URL associated with the test.',
			),
		);
		$configuration     = $this->object_schema(
			array(
				'startDate'                  => array(
					'type'        => 'string',
					'description' => 'Configured start date, or an empty string when the test starts manually.',
				),
				'endDate'                    => array(
					'type'        => 'string',
					'description' => 'Configured end date, or an empty string when no date-based end is set.',
				),
				'endMode'                    => array(
					'type'        => 'string',
					'description' => 'Configured condition that ends the test.',
				),
				'endValue'                   => array(
					'type'        => 'integer',
					'description' => 'Value used by the configured end condition.',
				),
				'autoAlternativeApplication' => array(
					'type'        => 'boolean',
					'description' => 'Whether Nelio automatically applies the winning variant when the test ends.',
				),
			),
			array( 'startDate', 'endDate', 'endMode', 'endValue', 'autoAlternativeApplication' )
		);
		$variant           = $this->object_schema(
			array(
				'id'     => array(
					'type'        => 'string',
					'description' => 'Identifier used to identify the variant in subsequent operations: the literal `control` for the control or an internal UUID for an alternative.',
				),
				'index'  => array(
					'type'        => 'integer',
					'description' => 'Zero-based position of the variant. The control is index 0.',
				),
				'label'  => array(
					'type'        => 'string',
					'description' => 'Positional label used when presenting the variant to a user.',
				),
				'name'   => array(
					'type'        => 'string',
					'description' => 'Short human-readable summary of the changes represented by the variant.',
				),
				'chance' => array(
					'type'        => 'number',
					'description' => 'Explicit percentage of visitors assigned to the variant. This field is omitted when Nelio uses its default equal distribution across all variants.',
				),
			),
			array( 'id', 'index', 'label', 'name' )
		);
		$named_object      = $this->object_schema(
			array(
				'id'    => array(
					'type'        => 'string',
					'description' => 'Internal UUID used to identify the item in subsequent operations.',
				),
				'label' => array(
					'type'        => 'string',
					'description' => 'Positional label used when presenting the item to a user.',
				),
				'name'  => array(
					'type'        => 'string',
					'description' => 'Human-readable name of the item.',
				),
			),
			array( 'id', 'label', 'name' )
		);
		$segment           = $this->object_schema(
			array(
				'id'        => array(
					'type'        => 'string',
					'description' => 'Internal UUID of the segment, or `default` for the virtual combined segment.',
				),
				'label'     => array(
					'type'        => 'string',
					'description' => 'Positional label used when presenting the segment to a user.',
				),
				'name'      => array(
					'type'        => 'string',
					'description' => 'Human-readable name of the segment.',
				),
				'isVirtual' => array(
					'type'        => 'boolean',
					'description' => 'Whether this is a virtual segment used for result analysis rather than a configured segment with its own UUID and rules.',
				),
			),
			array( 'id', 'label', 'name' )
		);
		$page_view_element = $this->object_schema(
			array(
				'mode'  => array(
					'type'        => 'string',
					'enum'        => array( 'css', 'id', 'class' ),
					'description' => 'Specifies whether the target is identified by a CSS selector, an HTML id, or a class name.',
				),
				'value' => array(
					'type'        => 'string',
					'description' => 'Identifies the HTML element whose visibility triggers the page view.',
				),
			),
			array( 'mode', 'value' )
		);
		$page_view_trigger = array(
			'type'  => 'object',
			'oneOf' => array(
				$this->object_schema(
					array(
						'trigger' => array(
							'type'        => 'string',
							'enum'        => array( 'on-page-visit' ),
							'description' => 'Indicates that page views are counted when the page is visited.',
						),
					),
					array( 'trigger' )
				),
				$this->object_schema(
					array(
						'trigger' => array(
							'type'        => 'string',
							'enum'        => array( 'when-element-is-visible' ),
							'description' => 'Indicates that page views are counted when the selected element becomes visible.',
						),
						'element' => $page_view_element,
					),
					array( 'trigger', 'element' )
				),
			),
		);

		wp_register_ability(
			'nelio-ab-testing/get-test',
			array(
				'label'               => _x( 'Read a Nelio test', 'text (ability name)', 'nelio-ab-testing' ),
				'description'         => 'Reads the basic configuration of a Nelio test, including its name, description, status, start and end settings, automatic winner application, and page-view trigger. It returns summaries of the variants, goals, and segments; when two or more segments exist, the segment summaries include the virtual `default` segment used to analyze visitors who belong to any configured segment. This virtual segment has no UUID or segmentation rules. Use the dedicated abilities to read complete definitions of configured segments.',
				'category'            => 'nelio-ab-testing',
				'input_schema'        => $this->object_schema(
					array(
						'id' => array(
							'type'        => 'integer',
							'minimum'     => 1,
							'description' => 'Identifier of the test on the connected site.',
						),
					)
				),
				'output_schema'       => $this->object_schema(
					$summary + array(
						'description'     => array(
							'type'        => 'string',
							'description' => 'Explanation of the test hypothesis or objective.',
						),
						'configuration'   => $configuration,
						'variants'        => array(
							'type'        => 'array',
							'description' => 'Summaries of the control and alternative variants. Use the dedicated variant ability for complete attributes.',
							'items'       => $variant,
						),
						'goals'           => array(
							'type'        => 'array',
							'description' => 'Summaries of the test goals. Use the dedicated goal abilities for complete attributes and actions.',
							'items'       => $named_object,
						),
						'segments'        => array(
							'type'        => 'array',
							'description' => 'Configured segment summaries and, when the test has two or more configured segments, the virtual `default` segment used for result analysis. The virtual segment has no UUID or rules.',
							'items'       => $segment,
						),
						'pageViewTrigger' => array_merge( $page_view_trigger, array( 'description' => 'Configuration that determines when page views are counted for the test.' ) ),
					),
					array_merge( array_keys( $summary ), array( 'description', 'configuration', 'variants', 'goals', 'segments', 'pageViewTrigger' ) )
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
	 * Checks whether the current user may read the test.
	 *
	 * @param array<array-key,mixed> $input Input containing the test identifier.
	 *
	 * @return true|WP_Error True when the user may read the test; otherwise, an error.
	 */
	public function permissions( $input ) {
		$experiment = nab_get_experiment( $this->integer_input( $input, 'id' ) );
		if ( is_wp_error( $experiment ) ) {
			return $experiment;
		}
		return Nelio_AB_Testing_Test_Response_Formatter::can_read( $experiment );
	}

	/**
	 * Returns the basic test configuration and related-object summaries.
	 *
	 * @param array<array-key,mixed> $input Input containing the test identifier.
	 *
	 * @return array<string,mixed>|WP_Error Test summary or an error.
	 */
	public function execute( $input ) {
		$allowed = $this->permissions( $input );
		if ( is_wp_error( $allowed ) ) {
			return $allowed;
		}
		$experiment = nab_get_experiment( $this->integer_input( $input, 'id' ) );
		assert( ! is_wp_error( $experiment ) );
		if ( null === nelioab()->get_experiment_type( $experiment->get_type() ) ) {
			return new WP_Error( 'invalid-test-type', 'This test was created with a test type that is no longer registered or supported on this site.' );
		}
		$start_date                = $experiment->get_start_date();
		$end_date                  = $experiment->get_end_date();
		$result                    = Nelio_AB_Testing_Test_Response_Formatter::summary( $experiment );
		$result['description']     = $experiment->get_description();
		$result['configuration']   = array(
			'startDate'                  => false === $start_date ? '' : $start_date,
			'endDate'                    => false === $end_date ? '' : $end_date,
			'endMode'                    => $experiment->get_end_mode(),
			'endValue'                   => $experiment->get_end_value(),
			'autoAlternativeApplication' => $experiment->is_auto_alternative_application_enabled(),
		);
		$result['variants']        = Nelio_AB_Testing_Test_Response_Formatter::variant_summaries( $experiment );
		$result['goals']           = Nelio_AB_Testing_Test_Response_Formatter::goal_summaries( $experiment );
		$result['segments']        = Nelio_AB_Testing_Test_Response_Formatter::segment_summaries( $experiment );
		$result['pageViewTrigger'] = Nelio_AB_Testing_Test_Response_Formatter::page_view_trigger( $experiment );
		if ( is_wp_error( $result['pageViewTrigger'] ) ) {
			return $result['pageViewTrigger'];
		}
		if ( $experiment instanceof Nelio_AB_Testing_Heatmap ) {
			$result['segments'] = array();
		}
		return $this->bounded_response( $result );
	}
}
