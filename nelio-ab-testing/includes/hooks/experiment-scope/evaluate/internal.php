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
 * @return array An array of rules whose types are limited to exact or partial URLs (included or excluded).
 */
function get_scope_to_compute_overlapping( $experiment ) {
	$rules = $experiment->get_scope();
	$rules = array_reduce(
		$rules,
		function ( $result, $rule ) use ( &$experiment ) {
			$type = nab_array_get( $rule, 'attributes.type', '' );
			switch ( $type ) {
				case 'tested-post':
					$post_id  = $experiment->get_tested_post();
					$result[] = array(
						'type'  => 'exact',
						'value' => get_permalink( $post_id ),
					);
					return $result;

				case 'tested-url-with-query-args':
					$alts = $experiment->get_alternatives();
					$urls = array_map( fn( $a ) => nab_array_get( $a, 'attributes.url', '' ), $alts );
					$urls = array_values( array_filter( $urls ) );
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
						'type'  => $type,
						'value' => nab_array_get( $rule, 'attributes.value', '' ),
					);
					return $result;

				case 'partial':
				case 'partial-not-included':
					$result[] = array(
						'type'  => $type,
						'value' => nab_array_get( $rule, 'attributes.value', '' ),
					);
					return $result;

				default:
					return $result;
			}//end switch
		},
		array()
	);

	return $rules;
}//end get_scope_to_compute_overlapping()

function do_post_alternatives_overlap( \Nelio_AB_Testing_Experiment $e1, \Nelio_AB_Testing_Experiment $e2 ) {
	$ids1    = get_tested_post_ids_in_experiment( $e1 );
	$ids2    = get_tested_post_ids_in_experiment( $e2 );
	$all_ids = array_merge( $ids1, $ids2 );
	return count( array_unique( $all_ids ) ) < count( $all_ids );
}//end do_post_alternatives_overlap()

function are_experiments_equivalent( \Nelio_AB_Testing_Experiment $e1, \Nelio_AB_Testing_Experiment $e2 ) {
	if ( $e1->get_type() !== $e2->get_type() ) {
		return false;
	}//end if

	if ( ! empty( $e1->get_scope() ) || ! empty( $e2->get_scope() ) ) {
		return false;
	}//end if

	$control1 = nab_array_get( $e1->get_alternative( 'control' ), 'attributes' );
	$control1 = is_array( $control1 ) ? $control1 : array();
	ksort( $control1 );

	$control2 = nab_array_get( $e2->get_alternative( 'control' ), 'attributes' );
	$control2 = is_array( $control2 ) ? $control2 : array();
	ksort( $control2 );

	return wp_json_encode( $control1 ) === wp_json_encode( $control2 );
}//end are_experiments_equivalent()

function get_tested_post_ids_in_experiment( $exp ) {
	$alts = $exp->get_alternatives();
	$alts = array_map( fn( $a ) => nab_array_get( $a, 'attributes.postId', 0 ), $alts );
	return array_values( array_filter( array_unique( $alts ) ) );
}//end get_tested_post_ids_in_experiment()

function does_scope_overlap_another_scope( $scope1, $scope2 ) {
	foreach ( $scope1 as $r1 ) {
		foreach ( $scope2 as $r2 ) {
			switch ( $r2['type'] ) {
				case 'exact':
					if ( does_rule_apply_to_url( $r1, $r2['value'] ) ) {
						return true;
					}//end if
					break;

				case 'partial':
					if ( does_rule_apply_to_url( $r1, $r2['value'] ) ) {
						return true;
					}//end if
					break;

				case 'different':
					if ( does_rule_apply_to_excluded_url( $r1, $r2['value'] ) ) {
						return true;
					}//end if
					break;

				case 'partial-not-included':
					if ( does_rule_apply_to_excluded_url( $r1, $r2['value'] ) ) {
						return true;
					}//end if
					break;
			}//end switch
		}//end foreach
	}//end foreach
	return false;
}//end does_scope_overlap_another_scope()

function does_rule_apply_to_excluded_url( array $rule, string $excluded_url ): bool {
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
	}//end switch
}//end does_rule_apply_to_excluded_url()

function are_urls_equal( $expected_url, $actual_url ) {

	$actual_url   = strtolower( preg_replace( '/^[^:]+:\/\//', '', $actual_url ) );
	$expected_url = strtolower( preg_replace( '/^[^:]+:\/\//', '', $expected_url ) );

	$actual_args   = wp_parse_args( wp_parse_url( $actual_url, PHP_URL_QUERY ) );
	$expected_args = wp_parse_args( wp_parse_url( $expected_url, PHP_URL_QUERY ), $actual_args );

	ksort( $actual_args );
	ksort( $expected_args );

	$actual_url   = untrailingslashit( preg_replace( '/\?.*$/', '', $actual_url ) );
	$expected_url = untrailingslashit( preg_replace( '/\?.*$/', '', $expected_url ) );

	/**
	 * Whether to ignore query args when trying to match the current URL with a URL specified in an experiment scope.
	 *
	 * @param boolean $ignore whether to ignore query args when trying to match the URL with a URL specified in an experiment scope. Default: `false`.
	 *
	 * @since 5.0.0
	 */
	if ( ! apply_filters( 'nab_ignore_query_args_in_scope', false ) ) {
		$actual_url   = add_query_arg( $actual_args, $actual_url );
		$expected_url = add_query_arg( $expected_args, $expected_url );
	}//end if

	return $actual_url === $expected_url;
}//end are_urls_equal()

function is_value_in_url( $expected_value, $actual_url ) {
	return false !== strpos( $actual_url, $expected_value );
}//end is_value_in_url()

function does_rule_with_query_args_apply( $rule, $actual_url, $actual_args ) {
	$urls = nab_array_get( $rule, 'value.urls', array() );
	$urls = is_array( $urls ) ? $urls : array();
	if ( nab_ignore_trailing_slash_in_alternative_loading() ) {
		$actual_url = preg_replace( '/\/$/', '', $actual_url );
		$urls       = array_map( fn( $url ) => preg_replace( '/\/$/', '', $url ), $urls );
	}//end if
	if ( ! in_array( $actual_url, $urls, true ) ) {
		return false;
	}//end if

	$expected_args = nab_array_get( $rule, 'value.args', array() );
	$expected_args = is_array( $expected_args ) ? $expected_args : array();

	foreach ( $expected_args as $arg ) {
		$actual_value = nab_array_get( $actual_args, $arg['name'], null );
		switch ( $arg['condition'] ) {
			case 'exists':
				if ( null === $actual_value ) {
					return false;
				}//end if
				break;
			case 'does-not-exist':
				if ( null !== $actual_value ) {
					return false;
				}//end if
				break;

			case 'is-equal-to':
				if ( $arg['value'] !== $actual_value ) {
					return false;
				}//end if
				break;
			case 'is-not-equal-to':
				if ( $arg['value'] === $actual_value ) {
					return false;
				}//end if
				break;

			case 'contains':
				if ( ! is_string( $actual_value ) ) {
					return false;
				}//end if
				if ( false === strpos( $actual_value, $arg['value'] ) ) {
					return false;
				}//end if
				break;
			case 'does-not-contain':
				if ( null === $actual_value ) {
					return true;
				}//end if
				if ( ! is_string( $actual_value ) ) {
					return false;
				}//end if
				if ( false !== strpos( $actual_value, $arg['value'] ) ) {
					return false;
				}//end if
				break;

			case 'is-any-of':
				if ( ! is_string( $actual_value ) ) {
					return false;
				}//end if
				if ( ! in_array( $actual_value, explode( "\n", $arg['value'] ), true ) ) {
					return false;
				}//end if
				break;
			case 'is-none-of':
				if ( null === $actual_value ) {
					return true;
				}//end if
				if ( ! is_string( $actual_value ) ) {
					return false;
				}//end if
				if ( in_array( $actual_value, explode( "\n", $arg['value'] ), true ) ) {
					return false;
				}//end if
				break;
		}//end switch
	}//end foreach

	return true;
}//end does_rule_with_query_args_apply()
