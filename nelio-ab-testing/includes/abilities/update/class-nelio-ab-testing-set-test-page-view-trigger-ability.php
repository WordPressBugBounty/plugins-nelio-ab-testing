<?php
/**
 * Ability for setting the page-view trigger of an editable Nelio test.
 *
 * @package Nelio_AB_Testing
 */

defined( 'ABSPATH' ) || exit;

/** Sets when page views are counted for an editable test. */
class Nelio_AB_Testing_Set_Test_Page_View_Trigger_Ability extends Nelio_AB_Testing_Ability {

	/**
	 * Registers the ability with WordPress.
	 *
	 * @return void
	 */
	public function register() {
		$test_id         = array(
			'type'        => 'integer',
			'minimum'     => 1,
			'description' => 'Identifier of the editable test whose page-view trigger will be replaced.',
		);
		$trigger         = array(
			'type'        => 'string',
			'enum'        => array( 'on-page-visit', 'when-element-is-visible' ),
			'description' => 'Determines when a page view should be counted.',
		);
		$element         = $this->object_schema(
			array(
				'mode'  => array(
					'type'        => 'string',
					'enum'        => array( 'css', 'id', 'class' ),
					'description' => 'Specifies whether the target is identified by a CSS selector, an HTML id, or a class name.',
				),
				'value' => array(
					'type'        => 'string',
					'description' => 'Identifies the HTML element whose visibility should trigger the page view.',
				),
			),
			array( 'mode', 'value' )
		);
		$on_page_visit   = $this->object_schema(
			array(
				'testId'  => $test_id,
				'trigger' => array_merge( $trigger, array( 'enum' => array( 'on-page-visit' ) ) ),
			),
			array( 'testId', 'trigger' )
		);
		$element_visible = $this->object_schema(
			array(
				'testId'  => $test_id,
				'trigger' => array_merge( $trigger, array( 'enum' => array( 'when-element-is-visible' ) ) ),
				'element' => array_merge(
					array( 'description' => 'Element to observe. This is required when page views are counted after an element becomes visible.' ),
					$element
				),
			),
			array( 'testId', 'trigger', 'element' )
		);

		wp_register_ability(
			'nelio-ab-testing/set-test-page-view-trigger',
			array(
				'label'               => _x( 'Set the test page-view trigger', 'text (ability name)', 'nelio-ab-testing' ),
				'description'         => 'Replaces when page views are counted for an editable Nelio test. Page views can be counted on a page visit or when a selected element becomes visible. When an element is used, provide its targeting mode and value; the internal conversion-action representation is managed by the plugin.',
				'category'            => 'nelio-ab-testing',
				'input_schema'        => array(
					'type'  => 'object',
					'oneOf' => array( $on_page_visit, $element_visible ),
				),
				'output_schema'       => array(
					'type'  => 'object',
					'oneOf' => array(
						$this->object_schema(
							array(
								'testId'  => array(
									'type'        => 'integer',
									'description' => 'Identifier of the updated test.',
								),
								'trigger' => array(
									'type'        => 'string',
									'enum'        => array( 'on-page-visit' ),
									'description' => 'Final page-view trigger mode.',
								),
							),
							array( 'testId', 'trigger' )
						),
						$this->object_schema(
							array(
								'testId'  => array(
									'type'        => 'integer',
									'description' => 'Identifier of the updated test.',
								),
								'trigger' => array(
									'type'        => 'string',
									'enum'        => array( 'when-element-is-visible' ),
									'description' => 'Final page-view trigger mode.',
								),
								'element' => $element,
							),
							array( 'testId', 'trigger', 'element' )
						),
					),
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
	 * Checks whether the current user may configure the page-view trigger.
	 *
	 * @param array<array-key,mixed> $input Input containing the test identifier.
	 *
	 * @return true|WP_Error True when the user may configure the trigger; otherwise, an error.
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
	 * Replaces and saves the page-view trigger configuration.
	 *
	 * @param array<array-key,mixed> $input Input containing the test identifier and public trigger configuration.

	 * @return array<string,mixed>|WP_Error The configured public trigger, or an error.
	 */
	public function execute( $input ) {
		$allowed = $this->permissions( $input );
		if ( is_wp_error( $allowed ) ) {
			return $allowed;
		}

		$experiment = nab_get_experiment( $this->integer_input( $input, 'testId' ) );
		assert( ! is_wp_error( $experiment ) );

		$trigger = is_string( $input['trigger'] ?? null ) ? $input['trigger'] : '';
		if ( 'on-page-visit' === $trigger ) {
			$page_view_triggers = array();
		} elseif ( 'when-element-is-visible' === $trigger ) {
			$element = is_array( $input['element'] ?? null ) ? $input['element'] : array();
			$mode    = is_string( $element['mode'] ?? null ) ? $element['mode'] : '';
			$value   = is_string( $element['value'] ?? null ) ? trim( $element['value'] ) : '';

			$page_view_triggers = array(
				array(
					'id'         => nab_uuid(),
					'type'       => 'nab/element-view',
					'attributes' => array(
						'mode'  => $mode,
						'value' => $value,
					),
					'scope'      => array( 'type' => 'test-scope' ),
				),
			);
		} else {
			return new WP_Error( 'invalid-page-view-trigger', 'The page-view trigger is invalid.' );
		}

		$validation = Nelio_AB_Testing_Page_View_Trigger_Manager::ai_validate( $page_view_triggers, $experiment->ID );
		if ( 'valid' !== $validation['status'] ) {
			$issues = implode( ' ', $validation['issues'] );
			if ( empty( $issues ) ) {
				$issues = 'The page-view trigger could not be validated.';
			}
			return new WP_Error( 'invalid-page-view-trigger', $issues );
		}

		$experiment->set_page_view_triggers( $page_view_triggers );
		nab_ai_refresh_experiment_status( $experiment );
		$experiment->save();

		$page_view_trigger = Nelio_AB_Testing_Test_Response_Formatter::page_view_trigger( $experiment );
		if ( is_wp_error( $page_view_trigger ) ) {
			return $page_view_trigger;
		}
		return array_merge(
			array( 'testId' => $experiment->get_id() ),
			$page_view_trigger
		);
	}
}
