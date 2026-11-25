<?php
/**
 * Ability that describes the test-building capabilities exposed to agents.
 *
 * @package Nelio_AB_Testing
 */

defined( 'ABSPATH' ) || exit;

/** Describes supported test-building operations. */
class Nelio_AB_Testing_Get_Test_Capabilities_Ability extends Nelio_AB_Testing_Ability {

	/** Register the ability. */
	public function register() {
		wp_register_ability(
			'nelio-ab-testing/get-test-capabilities',
			array(
				'label'               => _x( 'Get Nelio test capabilities', 'text (ability name)', 'nelio-ab-testing' ),
				'description'         => 'Describes the registered test types, conversion goals, and segmentation rules available in Nelio A/B Testing.',
				'category'            => 'nelio-ab-testing',
				'input_schema'        => $this->object_schema( array(), array() ),
				'output_schema'       => $this->object_schema(
					array(
						'testTypes'         => array(
							'type'        => 'array',
							'description' => 'Registered test type definitions. Only types that include an ai definition can be configured through the AI abilities. The `expected.pageViews` values provide average, median, 25th-percentile, and 75th-percentile estimates of the page views needed to obtain reliable results. For a test type with `expected.pageStatsOnSingleScope` set to true, the `expected.pageViews` values from `nab/page` can also be used when its scope is limited to exactly one page, because it then behaves like a page test. Do not apply those `nab/page` estimates to multi-page scopes.',
						),
						'conversionGoals'   => array(
							'type'        => 'array',
							'description' => 'Registered conversion action type definitions that can be used to configure test goals.',
						),
						'segmentationRules' => array(
							'type'        => 'array',
							'description' => 'Registered segmentation rule type definitions that can be used to target tests.',
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
	 * Check whether the current user may inspect capabilities.
	 *
	 * @param array<array-key,mixed> $input Input.
	 *
	 * @return true|WP_Error
	 */
	public function permissions( $input ) {
		return current_user_can( 'edit_nab_experiments' )
			? true
			: new WP_Error( 'missing-capability', 'You cannot configure Nelio tests on this site.', array( 'status' => 403 ) );
	}

	/**
	 * Return supported capabilities.
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
		$segmentation_rule_types = array_map(
			function ( $definition ) {
				unset( $definition['options'] );
				return $definition;
			},
			nelioab()->get_segmentation_rule_types()
		);
		return array(
			'testTypes'         => $this->serialize_types( nelioab()->get_experiment_types() ),
			'conversionGoals'   => $this->serialize_types( nelioab()->get_conversion_action_types() ),
			'segmentationRules' => $this->serialize_types( $segmentation_rule_types ),
		);
	}

	/**
	 * Serialize registered type definitions for an ability response.
	 *
	 * Callable values, such as PHP-only scope callbacks, cannot be represented
	 * in an MCP response and are omitted.
	 *
	 * @param array<string,array<string,mixed>> $types Registered definitions.
	 *
	 * @return list<array<string,mixed>> Serializable definitions.
	 */
	private function serialize_types( $types ) {
		$serialized = array();
		foreach ( $types as $name => $definition ) {
			$serialized_item = $this->serialize_value( $definition );
			if ( ! is_array( $serialized_item ) ) {
				continue; // @codeCoverageIgnore
			}
			$item = array();
			foreach ( $serialized_item as $key => $value ) {
				if ( is_string( $key ) ) {
					$item[ $key ] = $value;
				}
			}
			$item['type'] = is_string( $definition['name'] ?? null ) ? $definition['name'] : $name;
			$serialized[] = $item;
		}
		return $serialized;
	}

	/**
	 * Recursively remove values that cannot be encoded in an ability response.
	 *
	 * @param mixed $value Value to serialize.
	 *
	 * @return mixed
	 */
	private function serialize_value( $value ) {
		if ( is_callable( $value ) || is_resource( $value ) ) {
			return null; // @codeCoverageIgnore
		}
		if ( is_array( $value ) ) {
			$result = array();
			foreach ( $value as $key => $item ) {
				if ( is_callable( $item ) || is_resource( $item ) ) {
					continue;
				}
				$result[ $key ] = $this->serialize_value( $item );
			}
			return $result;
		}
		if ( is_object( $value ) ) {
			return null; // @codeCoverageIgnore
		}
		return $value;
	}
}
