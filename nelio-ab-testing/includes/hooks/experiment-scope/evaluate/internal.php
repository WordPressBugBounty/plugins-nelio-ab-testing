<?php
namespace Nelio_AB_Testing\Hooks\Experiment_Scope\Evaluate;

defined( 'ABSPATH' ) || exit;

/**
 * Given an experiment, returns an array of rules whose types are limited to exact or partial URLs (included or excluded).
 *
 * If the array is empty, it means the test runs everywhere.
 *
 * @param \Nelio_AB_Testing_Experiment $experiment The experiment.
 *
 * @return list<TCustom_Url_Scope_Rule> An array of rules whose types are limited to exact or partial URLs (included or excluded).
 */
function get_scope_to_compute_overlapping( $experiment ) {
	$rules = $experiment->get_scope();
	$rules = array_reduce(
		$rules,
		function ( $result, $rule ) use ( &$experiment ) {
			return transform_rule_to_custom_url_scope_rule( $result, $rule, $experiment );
		},
		array()
	);

	return $rules;
}

/**
 * Transforms the given rule (if possible) to one or more custom URL scope rules and appends them to the result.
 *
 * @param list<TCustom_Url_Scope_Rule> $result     Result.
 * @param TScope_Rule                  $rule       Scope rule.
 * @param \Nelio_AB_Testing_Experiment $experiment Experiment.
 *
 * @return list<TCustom_Url_Scope_Rule>
 */
function transform_rule_to_custom_url_scope_rule( $result, $rule, $experiment ) {
	switch ( $rule['attributes']['type'] ) {
		case 'tested-post':
			$post_id   = $experiment->get_tested_post();
			$permalink = get_permalink( $post_id );
			if ( empty( $permalink ) ) {
				return $result;
			}

			$result[] = array(
				'type'  => 'exact',
				'value' => $permalink,
			);
			return $result;

		case 'tested-url-with-query-args':
			$alts = $experiment->get_alternatives();
			$urls = array_map( fn( $a ) => $a['attributes']['url'] ?? '', $alts );
			$urls = array_values( array_filter( $urls, fn( $u ) => is_string( $u ) ) );
			$urls = array_map(
				fn( $url ) => array(
					'type'  => 'exact',
					'value' => $url,
				),
				$urls
			);
			return array_merge( $result, $urls );

		case 'exact':
		case 'different':
			$result[] = array(
				'type'  => $rule['attributes']['type'],
				'value' => $rule['attributes']['value'],
			);
			return $result;

		case 'partial':
		case 'partial-not-included':
			$result[] = array(
				'type'  => $rule['attributes']['type'],
				'value' => $rule['attributes']['value'],
			);
			return $result;

		default:
			return $result;
	}
}

/**
 * Whether the two experiments tested posts overlap with each other.
 *
 * @param \Nelio_AB_Testing_Experiment $e1 Experiment 1.
 * @param \Nelio_AB_Testing_Experiment $e2 Experiment 2.
 *
 * @return bool
 */
function do_post_alternatives_overlap( $e1, $e2 ) {
	$ids1    = get_tested_post_ids_in_experiment( $e1 );
	$ids2    = get_tested_post_ids_in_experiment( $e2 );
	$all_ids = array_merge( $ids1, $ids2 );
	return count( array_unique( $all_ids ) ) < count( $all_ids );
}

/**
 * Whether the two experiments are equivalent.
 *
 * @param \Nelio_AB_Testing_Experiment $e1 Experiment 1.
 * @param \Nelio_AB_Testing_Experiment $e2 Experiment 2.
 *
 * @return bool
 */
function are_experiments_equivalent( \Nelio_AB_Testing_Experiment $e1, \Nelio_AB_Testing_Experiment $e2 ) {
	if ( $e1->get_type() !== $e2->get_type() ) {
		return false;
	}

	if ( ! empty( $e1->get_scope() ) || ! empty( $e2->get_scope() ) ) {
		return false;
	}

	$control1 = $e1->get_alternative( 'control' )['attributes'];
	ksort( $control1 );

	$control2 = $e2->get_alternative( 'control' )['attributes'];
	ksort( $control2 );

	return wp_json_encode( $control1 ) === wp_json_encode( $control2 );
}

/**
 * Returns the list of post IDs tested by the given experiment.
 *
 * @param \Nelio_AB_Testing_Experiment $exp Experiment.
 *
 * @return list<int>
 */
function get_tested_post_ids_in_experiment( $exp ) {
	$alts = $exp->get_alternatives();
	$alts = array_map( fn( $a ) => absint( $a['attributes']['postId'] ?? 0 ), $alts );
	return array_values( array_filter( array_unique( $alts ) ) );
}

/**
 * Whether one scope overlaps with another scope.
 *
 * @param list<TCustom_Url_Scope_Rule> $scope1 Scope 1.
 * @param list<TCustom_Url_Scope_Rule> $scope2 Scope 2.
 *
 * @return bool
 */
function does_scope_overlap_another_scope( $scope1, $scope2 ) {
	foreach ( $scope1 as $r1 ) {
		foreach ( $scope2 as $r2 ) {
			switch ( $r2['type'] ) {
				case 'exact':
					if ( does_rule_apply_to_url( $r1, $r2['value'] ) ) {
						return true;
					}
					break;

				case 'partial':
					if ( does_rule_apply_to_url( $r1, $r2['value'] ) ) {
						return true;
					}
					break;

				case 'different':
					if ( does_rule_apply_to_excluded_url( $r1, $r2['value'] ) ) {
						return true;
					}
					break;

				case 'partial-not-included':
					if ( does_rule_apply_to_excluded_url( $r1, $r2['value'] ) ) {
						return true;
					}
					break;
			}
		}
	}
	return false;
}

/**
 * Whether the given rule applies to the excluded URL.
 *
 * @param TCustom_Url_Scope_Rule $rule         Scope rule.
 * @param string                 $excluded_url Excluded URL.
 *
 * @return bool
 */
function does_rule_apply_to_excluded_url( $rule, $excluded_url ) {
	switch ( $rule['type'] ) {
		case 'exact':
			return ! are_urls_equal( $excluded_url, $rule['value'] );

		case 'partial':
			return ! is_value_in_url( $excluded_url, $rule['value'] );

		case 'different':
		case 'partial-not-included':
			// Two exclusion scopes (i.e. this $rule and the one that specified the $excluded_url)
			// are very likely to overlap, so the safest solution is to assume they will.
			return true;

		default:
			return false;
	}
}

/**
 * Whether the given URLs are the same.
 *
 * @param string $expected_url Expected URL.
 * @param string $actual_url   Actual URL.
 *
 * @return bool
 */
function are_urls_equal( $expected_url, $actual_url ) {
	$expected_url = clean_url_for_equality_comparison( $expected_url );
	$actual_url   = clean_url_for_equality_comparison( $actual_url );
	return ! empty( $actual_url ) && $actual_url === $expected_url;
}

/**
 * Clean URL to compare it with another URL for equality.
 *
 * @param string $url URL.
 *
 * @return string
 */
function clean_url_for_equality_comparison( $url ) {
	$url = preg_replace( '/^[^:]+:\/\//', '', strtolower( $url ) );
	$url = is_string( $url ) ? $url : '';

	$args = wp_parse_url( $url, PHP_URL_QUERY );
	$args = is_string( $args ) ? $args : '';
	$args = wp_parse_args( $args );

	ksort( $args );

	$url = preg_replace( '/\?.*$/', '', $url );
	$url = is_string( $url ) ? $url : '';
	$url = untrailingslashit( $url );

	/**
	 * Whether to ignore query args when trying to match the current URL with a URL specified in an experiment scope.
	 *
	 * @param boolean $ignore whether to ignore query args when trying to match the URL with a URL specified in an experiment scope. Default: `false`.
	 *
	 * @since 5.0.0
	 */
	if ( ! apply_filters( 'nab_ignore_query_args_in_scope', false ) ) {
		$url = add_query_arg( $args, $url );
	}

	return $url;
}

/**
 * Whether the expected value shows up in the URL or not.
 *
 * @param string $expected_value Expected value.
 * @param string $actual_url     Actual URL.
 *
 * @return bool
 */
function is_value_in_url( $expected_value, $actual_url ) {
	return false !== strpos( $actual_url, $expected_value );
}

/**
 * Whether the rule applies to the given URL and arguments or not.
 *
 * @param TTested_Url_With_Query_Args_Scope_Rule $rule       Rule.
 * @param string                                 $actual_url Actual URL.
 * @param array<string,mixed>                    $actual_args Arguments.
 *
 * @return bool
 */
function does_rule_with_query_args_apply( $rule, $actual_url, $actual_args ) {
	$urls = $rule['value']['urls'];
	if ( nab_ignore_trailing_slash_in_alternative_loading() ) {
		$actual_url = preg_replace( '/\/$/', '', $actual_url );
		$urls       = array_map( fn( $url ) => preg_replace( '/\/$/', '', $url ), $urls );
	}
	if ( ! in_array( $actual_url, $urls, true ) ) {
		return false;
	}

	$expected_args = $rule['value']['args'];
	foreach ( $expected_args as $arg ) {
		$actual_value = $actual_args[ $arg['name'] ] ?? null;
		switch ( $arg['condition'] ) {
			case 'exists':
				if ( null === $actual_value ) {
					return false;
				}
				break;
			case 'does-not-exist':
				if ( null !== $actual_value ) {
					return false;
				}
				break;

			case 'is-equal-to':
				if ( $arg['value'] !== $actual_value ) {
					return false;
				}
				break;
			case 'is-not-equal-to':
				if ( $arg['value'] === $actual_value ) {
					return false;
				}
				break;

			case 'contains':
				if ( ! is_string( $actual_value ) ) {
					return false;
				}
				if ( false === strpos( $actual_value, $arg['value'] ) ) {
					return false;
				}
				break;
			case 'does-not-contain':
				if ( null === $actual_value ) {
					return true;
				}
				if ( ! is_string( $actual_value ) ) {
					return false;
				}
				if ( false !== strpos( $actual_value, $arg['value'] ) ) {
					return false;
				}
				break;

			case 'is-any-of':
				if ( ! is_string( $actual_value ) ) {
					return false;
				}
				if ( ! in_array( $actual_value, explode( "\n", $arg['value'] ), true ) ) {
					return false;
				}
				break;
			case 'is-none-of':
				if ( null === $actual_value ) {
					return true;
				}
				if ( ! is_string( $actual_value ) ) {
					return false;
				}
				if ( in_array( $actual_value, explode( "\n", $arg['value'] ), true ) ) {
					return false;
				}
				break;
		}
	}

	return true;
}
