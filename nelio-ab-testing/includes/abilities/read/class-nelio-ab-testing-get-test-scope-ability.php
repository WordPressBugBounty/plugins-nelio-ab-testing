<?php
/**
 * Ability for reading a Nelio test scope.
 *
 * @package Nelio_AB_Testing
 */

defined( 'ABSPATH' ) || exit;

/** Reads a test scope using the same representation accepted by set-test-scope. */
class Nelio_AB_Testing_Get_Test_Scope_Ability extends Nelio_AB_Testing_Ability {

	/**
	 * Registers the ability with WordPress.
	 *
	 * @return void
	 */
	public function register() {
		$rule             = $this->object_schema(
			array(
				'type'           => array(
					'type'        => 'string',
					'enum'        => array( 'exact', 'partial', 'partial-not-included', 'different' ),
					'description' => 'How the rule matches the requested URL.',
				),
				'value'          => array(
					'type'        => 'string',
					'description' => 'URL fragment or local URL used by the rule. The `*` character is the only supported wildcard.',
				),
				'trackPageViews' => array(
					'type'        => 'boolean',
					'description' => 'Whether page views are tracked for visitors matching this rule.',
				),
			),
			array( 'type', 'value', 'trackPageViews' )
		);
		$query_argument   = $this->object_schema(
			array(
				'name'      => array(
					'type'        => 'string',
					'description' => 'Name of the query parameter.',
				),
				'condition' => array(
					'type'        => 'string',
					'enum'        => array( 'is-equal-to', 'is-not-equal-to', 'contains', 'does-not-contain', 'is-any-of', 'is-none-of', 'exists', 'does-not-exist' ),
					'description' => 'Comparison applied to the query parameter.',
				),
				'value'     => array(
					'type'        => 'string',
					'description' => 'Value compared with the query parameter, or an empty value for existence conditions.',
				),
			),
			array( 'name', 'condition' )
		);
		$query_scope_rule = $this->object_schema(
			array(
				'id'         => array(
					'type'        => 'string',
					'description' => 'Internal identifier of the scope rule.',
				),
				'attributes' => $this->object_schema(
					array(
						'type'  => array(
							'type'        => 'string',
							'enum'        => array( 'tested-url-with-query-args' ),
							'description' => 'Scope-rule type.',
						),
						'value' => $this->object_schema(
							array(
								'urls' => array(
									'type'        => 'array',
									'description' => 'Full URLs tested by the test.',
									'items'       => array(
										'type'        => 'string',
										'description' => 'Full tested URL.',
									),
								),
								'args' => array(
									'type'        => 'array',
									'description' => 'Query-parameter conditions applied to the tested URLs.',
									'items'       => $query_argument,
								),
							),
							array( 'urls', 'args' )
						),
					),
					array( 'type', 'value' )
				),
			),
			array( 'attributes' )
		);
		wp_register_ability(
			'nelio-ab-testing/get-test-scope',
			array(
				'label'               => _x( 'Read a test scope', 'text (ability name)', 'nelio-ab-testing' ),
				'description'         => 'Reads a Nelio test scope in a type-specific agent-facing format. The returned fields depend on the scope supported by the test type. For consistency-based test types, report whether Global Consistency is enabled and do not mention URL or page-scope rules, because those rules are not applicable.',
				'category'            => 'nelio-ab-testing',
				'input_schema'        => $this->object_schema(
					array(
						'testId' => array(
							'type'        => 'integer',
							'minimum'     => 1,
							'description' => 'Identifier of the test whose scope should be read.',
						),
					)
				),
				'output_schema'       => array(
					'type'  => 'object',
					'oneOf' => array(
						$this->object_schema(
							array(
								'testId'            => array(
									'type'        => 'integer',
									'description' => 'Identifier of the test whose scope is being returned.',
								),
								'globalConsistency' => array(
									'type'        => 'boolean',
									'description' => 'Whether Global Consistency is enabled for this test. This field is returned only for consistency-based test types; those tests do not have URL scope rules.',
								),
							),
							array( 'testId', 'globalConsistency' )
						),
						$this->object_schema(
							array(
								'testId' => array(
									'type'        => 'integer',
									'description' => 'Identifier of the test whose scope is being returned.',
								),
								'rules'  => array(
									'type'        => 'array',
									'description' => 'URL scope rules returned for test types that support URL scopes.',
									'items'       => $rule,
								),
							),
							array( 'testId', 'rules' )
						),
						$this->object_schema(
							array(
								'testId' => array(
									'type'        => 'integer',
									'description' => 'Identifier of the test whose scope is being returned.',
								),
								'scope'  => array(
									'type'        => 'array',
									'description' => 'Typed scope rules returned for test types that support tested URLs with query arguments.',
									'items'       => $query_scope_rule,
								),
							),
							array( 'testId', 'scope' )
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
						'readonly'    => true,
						'destructive' => false,
						'idempotent'  => true,
					),
				),
			)
		);
	}

	/**
	 * Checks whether the current user may read the scope.
	 *
	 * @param array<array-key,mixed> $input Input containing the test identifier.
	 *
	 * @return true|WP_Error True when the user may read the scope; otherwise, an error.
	 */
	public function permissions( $input ) {
		$experiment = nab_get_experiment( $this->integer_input( $input, 'testId' ) );
		if ( is_wp_error( $experiment ) ) {
			return $experiment;
		}
		return Nelio_AB_Testing_Test_Response_Formatter::can_read( $experiment );
	}

	/**
	 * Returns the test scope in agent-facing form.
	 *
	 * @param array<array-key,mixed> $input Input containing the test identifier.
	 *
	 * @return array<string,mixed>|WP_Error Scope data or an error.
	 */
	public function execute( $input ) {
		$allowed = $this->permissions( $input );
		if ( is_wp_error( $allowed ) ) {
			return $allowed;
		}
		$experiment = nab_get_experiment( $this->integer_input( $input, 'testId' ) );
		assert( ! is_wp_error( $experiment ) );
		$definition = nelioab()->get_experiment_type( $experiment->get_type() );
		if ( null === $definition ) {
			return new WP_Error( 'unsupported-scope', 'The scope of this test cannot be read because its test type does not define a supported scope.' );
		}
		$supported_scopes = array(
			'tested-post-with-consistency',
			'tested-product-with-consistency',
			'urls',
			'urls-with-tested-post',
			'tested-url-with-query-args',
		);
		$scope_support    = $definition['scope'] ?? null;
		if ( ! is_string( $scope_support ) || ! in_array( $scope_support, $supported_scopes, true ) ) {
			return new WP_Error( 'unsupported-scope', 'This test type uses a scope format that is not supported by this ability.' );
		}
		$scope = Nelio_AB_Testing_Scope_Formatter::format( $scope_support, $experiment );
		return $this->bounded_response( array_merge( array( 'testId' => $experiment->ID ), $scope ) );
	}
}
