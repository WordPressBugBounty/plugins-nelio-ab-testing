<?php
namespace Nelio_AB_Testing\Hooks\Experiment_Scope\Evaluate;

defined( 'ABSPATH' ) || exit;

function do_scopes_overlap( \Nelio_AB_Testing_Experiment $e1, \Nelio_AB_Testing_Experiment $e2 ): bool {
	if ( do_post_alternatives_overlap( $e1, $e2 ) ) {
		return true;
	}

	if ( are_experiments_equivalent( $e1, $e2 ) ) {
		return true;
	}

	$s1 = get_scope_to_compute_overlapping( $e1 );
	$s2 = get_scope_to_compute_overlapping( $e2 );
	return (
		does_scope_overlap_another_scope( $s1, $s2 ) ||
		does_scope_overlap_another_scope( $s2, $s1 )
	);
}

/**
 * Whether the rule applies to the given URL or not.
 *
 * @param TScope_Rule_Attributes $rule   The attributes of an experiment scope rule.
 * @param string                 $url    A URL.
 * @param array<string,mixed>    $args   Optional. Query arguments in the URL. Default: `[]`.
 *
 * @return bool whether the rule applies to the given URL or not.
 *
 * @since 7.3.0
 */
function does_rule_apply_to_url( $rule, $url, $args = array() ) {
	// NOTE. The URL in a $rule and the $url itself may contain query args.
	// Which is weird when considering there’s also the $args attribute.
	// Review this in the future.
	switch ( $rule['type'] ) {
		case 'exact':
			return are_urls_equal( $rule['value'], $url );

		case 'different':
			return ! are_urls_equal( $rule['value'], $url );

		case 'partial':
			return is_value_in_url( $rule['value'], $url );

		case 'partial-not-included':
			return ! is_value_in_url( $rule['value'], $url );

		case 'tested-url-with-query-args':
			return does_rule_with_query_args_apply( $rule, $url, $args );

		case 'php-snippet':
			try {
				return ! empty( nab_eval_php( $rule['value']['snippet'] ) );
			} catch ( \Error $_ ) {
				return false;
			}

		default:
			return false;
	}
}
