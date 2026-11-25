<?php
/**
 * Ability for setting the scope of an editable Nelio test.
 *
 * @package Nelio_AB_Testing
 */

defined( 'ABSPATH' ) || exit;

/**
 * Sets the scope of editable tests.
 */
class Nelio_AB_Testing_Set_Test_Scope_Ability extends Nelio_AB_Testing_Ability {

	/**
	 * Registers the ability with WordPress.
	 *
	 * @return void
	 */
	public function register() {
		$url_scope_rule    = $this->object_schema(
			array(
				'type'           => array(
					'type'        => 'string',
					'enum'        => array( 'exact', 'partial', 'partial-not-included', 'different' ),
					'description' => 'URL matching strategy for the rule.',
				),
				'value'          => array(
					'type'        => 'string',
					'description' => 'Value matched by the rule.',
				),
				'trackPageViews' => array(
					'type'        => 'boolean',
					'description' => 'Whether page views from matching URLs are tracked.',
				),
			),
			array( 'type', 'value', 'trackPageViews' )
		);
		$query_scope_value = $this->object_schema(
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
					'items'       => $this->object_schema(
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
								'description' => 'Value compared with the query parameter.',
							),
						),
						array( 'name', 'condition' )
					),
				),
			),
			array( 'urls', 'args' )
		);
		$query_scope_rule  = $this->object_schema(
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
						'value' => $query_scope_value,
					),
					array( 'type', 'value' )
				),
			),
			array( 'attributes' )
		);
		wp_register_ability(
			'nelio-ab-testing/set-test-scope',
			array(
				'label'               => _x( 'Set the test scope', 'text (ability name)', 'nelio-ab-testing' ),
				'description'         => 'Replaces the complete scope of an editable Nelio test. The accepted input depends on the experiment type and, in particular, on the scope support declared by that type: consistency-based tests use a Global Consistency flag, URL tests use URL rules, and URL tests with query arguments use typed scope rules. The `urls-or-php` scope is not supported by this ability.',
				'category'            => 'nelio-ab-testing',
				'input_schema'        => array_merge(
					$this->object_schema(
						array(
							'testId'            => array(
								'type'        => 'integer',
								'description' => 'Identifier of the editable test whose complete scope will be replaced.',
							),
							'globalConsistency' => array(
								'type'        => 'boolean',
								'description' => 'Whether the test should use Global Consistency. Provide this only for `tested-post-with-consistency` and `tested-product-with-consistency` test types.',
							),
							'rules'             => array(
								'type'        => 'array',
								'description' => 'Complete list of URL scope rules. Provide this only for `urls` and `urls-with-tested-post` test types. Each rule specifies its URL matching type, matching value, and whether page-view tracking is enabled there.',
								'items'       => $this->object_schema(
									array(
										'type'           => array(
											'type'        => 'string',
											'enum'        => array( 'exact', 'partial', 'partial-not-included', 'different' ),
											'description' => 'URL matching strategy for the rule.',
										),
										'value'          => array(
											'type'        => 'string',
											'description' => 'Value matched by the rule. For `exact` and `different`, provide a complete local URL. For `partial` and `partial-not-included`, provide either a URL fragment such as `/some-path/` or a complete local URL; both forms are accepted.',
										),
										'trackPageViews' => array(
											'type'        => 'boolean',
											'description' => 'Whether page views from URLs matching this rule should be tracked.',
										),
									),
									array( 'type', 'value', 'trackPageViews' )
								),
							),
							'scope'             => array(
								'type'        => 'array',
								'description' => 'Complete list of `tested-url-with-query-args` scope rules, using the same typed scope-rule structure returned by the test. Provide this only for this test type.',
								'items'       => $this->object_schema(
									array(
										'attributes' => $this->object_schema(
											array(
												'type'  => array(
													'type' => 'string',
													'enum' => array( 'tested-url-with-query-args' ),
													'description' => 'Scope-rule type. It must be `tested-url-with-query-args`.',
												),
												'value' => $this->object_schema(
													array(
														'urls' => array(
															'type'        => 'array',
															'description' => 'URLs tested by the test. The plugin may derive these URLs from the test variants.',
														),
														'args' => array(
															'type'        => 'array',
															'description' => 'Query-parameter conditions that must be satisfied by a tested URL.',
															'items'       => $this->object_schema(
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
																		'description' => 'Value compared with the query parameter. It may be empty for existence conditions. For `is-any-of` and `is-none-of`, provide exactly one value per line.',
																	),
																),
																array( 'name', 'condition' )
															),
														),
													),
												),
											),
											array( 'type', 'value' )
										),
									),
									array( 'attributes' )
								),
							),
						),
						array( 'testId' )
					),
					array(
						'oneOf' => array(
							array( 'required' => array( 'testId', 'globalConsistency' ) ),
							array( 'required' => array( 'testId', 'rules' ) ),
							array( 'required' => array( 'testId', 'scope' ) ),
						),
					)
				),
				'output_schema'       => array_merge(
					$this->object_schema(
						array(
							'testId'            => array(
								'type'        => 'integer',
								'description' => 'Identifier of the updated test.',
							),
							'globalConsistency' => array(
								'type'        => 'boolean',
								'description' => 'Whether Global Consistency is enabled after the update.',
							),
							'rules'             => array(
								'type'        => 'array',
								'description' => 'The URL scope rules after the update, preserving the supplied matching values and page-view tracking flags.',
								'items'       => $url_scope_rule,
							),
							'scope'             => array(
								'type'        => 'array',
								'description' => 'The typed tested URL scope rules after the update, using full URLs.',
								'items'       => $query_scope_rule,
							),
						),
						array( 'testId' )
					),
					array(
						'oneOf' => array(
							array( 'required' => array( 'testId', 'globalConsistency' ) ),
							array( 'required' => array( 'testId', 'rules' ) ),
							array( 'required' => array( 'testId', 'scope' ) ),
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
						'idempotent'  => false,
					),
				),
			)
		);
	}

	/**
	 * Checks whether the current user may update the test scope.
	 *
	 * @param array<array-key,mixed> $input Input containing the test identifier.
	 *
	 * @return true|WP_Error True when the user may update the scope; otherwise, an error.
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
	 * Replaces and saves the complete test scope.
	 *
	 * @param array<array-key,mixed> $input Input containing the test identifier and type-specific scope data.
	 *
	 * @return array<string,mixed>|WP_Error The saved scope, or an error when the scope is unsupported or invalid.
	 */
	public function execute( $input ) {
		$allowed = $this->permissions( $input );
		if ( is_wp_error( $allowed ) ) {
			return $allowed;
		}

		$experiment = nab_get_experiment( $this->integer_input( $input, 'testId' ) );
		assert( ! is_wp_error( $experiment ) );

		$definition      = nelioab()->get_experiment_type( $experiment->get_type() );
		$scope_support   = null !== $definition ? $definition['scope'] ?? '' : '';
		$page_exclusions = array();
		$existing_scope  = $experiment->get_scope();

		switch ( $scope_support ) {
			case 'tested-post-with-consistency':
			case 'tested-product-with-consistency':
				$scope = $this->get_consistency_scope( $input, $existing_scope );
				break;

			case 'urls':
			case 'urls-with-tested-post':
				$scope = $this->get_custom_url_scope( $input, $page_exclusions, $existing_scope );
				break;

			case 'tested-url-with-query-args':
				$scope = $this->get_tested_url_scope( $input, $existing_scope );
				break;

			case 'urls-or-php':
				return new WP_Error(
					'unsupported-scope',
					'The `urls-or-php` scope is not currently supported by this ability.'
				);

			default:
				// @codeCoverageIgnoreStart
				return new WP_Error(
					'unsupported-scope',
					'The selected test type does not support a configurable scope, so this ability cannot set one.'
				);
				// @codeCoverageIgnoreEnd
		}

		if ( is_wp_error( $scope ) ) {
			return $scope;
		}

		$experiment->set_page_view_scope_exclusions( $page_exclusions );
		$validation = Nelio_AB_Testing_Experiment_Scope_Manager::ai_validate( $scope, $experiment );
		if ( 'valid' !== $validation['status'] ) {
			$issues = implode( ' ', $validation['issues'] );
			// @codeCoverageIgnoreStart
			if ( '' === $issues ) {
				$issues = 'The test scope could not be validated.';
			}
			// @codeCoverageIgnoreEnd
			return new WP_Error( 'invalid-scope', $issues );
		}

		$experiment->set_scope( $scope );
		$experiment->set_page_view_scope_exclusions( $page_exclusions );
		nab_ai_refresh_experiment_status( $experiment );
		$experiment->save();

		return array_merge(
			array( 'testId' => $experiment->ID ),
			Nelio_AB_Testing_Scope_Formatter::format( $scope_support, $experiment )
		);
	}

	/**
	 * Builds a consistency-based scope.
	 *
	 * @param array<array-key,mixed> $input          Input.
	 * @param list<TScope_Rule>      $existing_scope Existing scope rules.
	 *
	 * @return list<TScope_Rule>|WP_Error Scope rules, or an error when the flag is missing or invalid.
	 */
	private function get_consistency_scope( $input, $existing_scope ) {
		if ( ! array_key_exists( 'globalConsistency', $input ) || ! is_bool( $input['globalConsistency'] ) ) {
			return new WP_Error(
				'invalid-scope',
				'The `globalConsistency` value must be a boolean for this test type.'
			);
		}

		if ( $input['globalConsistency'] ) {
			return array();
		}

		return array(
			array(
				'id'         => is_string( $existing_scope[0]['id'] ?? null ) ? $existing_scope[0]['id'] : nab_uuid(),
				'attributes' => array( 'type' => 'tested-post' ),
			),
		);
	}

	/**
	 * Builds custom URL scope rules and page-view exclusions.
	 *
	 * @param array<array-key,mixed> $input          Input.
	 * @param list<string>           $page_exclusions Page-view exclusion IDs, passed by reference.
	 * @param list<TScope_Rule>      $existing_scope Existing scope rules.
	 *
	 * @return list<TScope_Rule>|WP_Error Scope rules, or an error when a rule is invalid.
	 */
	private function get_custom_url_scope( $input, &$page_exclusions, $existing_scope ) {
		$rules = $input['rules'] ?? null;
		if ( ! is_array( $rules ) ) {
			return new WP_Error(
				'invalid-scope',
				'The `rules` value must be an array for this test type.'
			);
		}

		$scope = array();
		foreach ( $rules as $index => $rule ) {
			if ( ! is_array( $rule ) ) {
				return new WP_Error( 'invalid-scope-rule', 'Every URL scope rule must be an object.' );
			}

			$type            = $rule['type'] ?? null;
			$value           = $rule['value'] ?? null;
			$track_pageviews = $rule['trackPageViews'] ?? null;
			if ( ! is_string( $type ) || ! in_array( $type, array( 'exact', 'partial', 'partial-not-included', 'different' ), true ) ) {
				return new WP_Error( 'invalid-scope-rule', 'Every URL scope rule must use a supported matching type.' );
			}

			if ( ! is_string( $value ) || '' === trim( $value ) ) {
				return new WP_Error( 'invalid-scope-rule', 'Every URL scope rule must provide a non-empty URL value.' );
			}

			if ( ! is_bool( $track_pageviews ) ) {
				return new WP_Error( 'invalid-scope-rule', 'Every URL scope rule must specify whether page views should be tracked.' );
			}

			$id      = is_string( $existing_scope[ $index ]['id'] ?? null ) ? $existing_scope[ $index ]['id'] : nab_uuid();
			$scope[] = array(
				'id'         => $id,
				'attributes' => array(
					'type'  => $type,
					'value' => $value,
				),
			);

			if ( ! $track_pageviews ) {
				$page_exclusions[] = $id;
			}
		}

		return $scope;
	}

	/**
	 * Builds tested URL scope rules with query-argument conditions.
	 *
	 * @param array<array-key,mixed> $input          Input.
	 * @param list<TScope_Rule>      $existing_scope Existing scope rules.
	 *
	 * @return list<TScope_Rule>|WP_Error Scope rules, or an error when a rule is invalid.
	 */
	private function get_tested_url_scope( $input, $existing_scope ) {
		$rules = $input['scope'] ?? null;
		if ( ! is_array( $rules ) ) {
			return new WP_Error(
				'invalid-scope',
				'The `scope` value must be an array for this test type.'
			);
		}

		$scope = array();
		foreach ( $rules as $index => $rule ) {
			if ( ! is_array( $rule ) ) {
				return new WP_Error( 'invalid-scope-rule', 'Every tested URL scope rule must be an object.' );
			}

			$id         = is_string( $existing_scope[ $index ]['id'] ?? null ) ? $existing_scope[ $index ]['id'] : nab_uuid();
			$attributes = is_array( $rule['attributes'] ?? null ) ? $rule['attributes'] : $rule;
			$type       = $attributes['type'] ?? null;
			$value      = $attributes['value'] ?? null;

			if ( 'tested-url-with-query-args' !== $type || ! is_array( $value ) ) {
				return new WP_Error(
					'invalid-scope-rule',
					'Every scope rule for this test type must use the `tested-url-with-query-args` type and provide an object value.'
				);
			}

			$urls = $value['urls'] ?? null;
			if ( ! is_array( $urls ) ) {
				return new WP_Error(
					'invalid-scope-rule',
					'The `urls` value of a tested URL scope rule must be an array.'
				);
			}

			$normalized_urls = array();
			foreach ( $urls as $url ) {
				if ( ! is_string( $url ) ) {
					return new WP_Error( 'invalid-scope-rule', 'Every tested URL scope rule must contain only string URLs.' );
				}
				$normalized_urls[] = $url;
			}

			$args = $value['args'] ?? array();
			if ( ! is_array( $args ) ) {
				return new WP_Error(
					'invalid-scope-rule',
					'The `args` value of a tested URL scope rule must be an array.'
				);
			}

			$normalized_args = array();
			foreach ( $args as $arg ) {
				$name      = is_array( $arg ) ? ( $arg['name'] ?? null ) : null;
				$condition = is_array( $arg ) ? ( $arg['condition'] ?? null ) : null;
				$arg_value = is_array( $arg ) ? ( $arg['value'] ?? '' ) : '';
				if ( ! is_string( $name ) || ! is_string( $condition ) || ! is_string( $arg_value ) ) {
					return new WP_Error( 'invalid-scope-rule', 'Every query-parameter condition must provide a name, a supported condition, and a string value.' );
				}
				if ( ! in_array( $condition, array( 'is-equal-to', 'is-not-equal-to', 'contains', 'does-not-contain', 'is-any-of', 'is-none-of', 'exists', 'does-not-exist' ), true ) ) {
					return new WP_Error( 'invalid-scope-rule', 'Every query-parameter condition must use a supported condition.' );
				}
				$normalized_args[] = array(
					'name'      => $name,
					'condition' => $condition,
					'value'     => $arg_value,
				);
			}

			$scope[] = array(
				'id'         => $id,
				'attributes' => array(
					'type'  => 'tested-url-with-query-args',
					'value' => array(
						'urls' => $normalized_urls,
						'args' => $normalized_args,
					),
				),
			);
		}

		return $scope;
	}
}
