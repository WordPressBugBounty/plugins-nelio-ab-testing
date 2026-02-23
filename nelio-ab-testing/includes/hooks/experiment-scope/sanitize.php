<?php
namespace Nelio_AB_Testing\Hooks\Experiment_Scope\Sanitize;

defined( 'ABSPATH' ) || exit;

/**
 * Sanitizes experiment scope.
 *
 * @param list<array{id:string,attributes:array<string,mixed>}> $scope Scope.
 * @param \Nelio_AB_Testing_Experiment                          $experiment Experiment.
 *
 * @return list<TScope_Rule>
 */
function sanitize_experiment_scope( $scope, $experiment ) {
	$scope = array_map(
		function ( $rule ) use ( $experiment ) {
			$type = $rule['attributes']['type'] ?? '';
			$type = is_string( $type ) ? $type : '';
			switch ( $type ) {
				case 'tested-post':
					return sanitize_tested_post_scope( $rule );

				case 'exact':
				case 'different':
				case 'partial':
				case 'partial-not-included':
					return sanitize_custom_url_scope( $rule, $type );

				case 'tested-url-with-query-args':
					return sanitize_tested_url_with_query_args( $rule, $experiment );

				case 'php-snippet':
					return 'nab/php' === $experiment->get_type() ? sanitize_php_snippet( $rule ) : false;

				default:
					return false;
			}
		},
		$scope
	);

	return array_values( array_filter( $scope ) );
}
add_filter( 'nab_sanitize_experiment_scope', __NAMESPACE__ . '\sanitize_experiment_scope', 5, 2 );

/**
 * Sanitizes tested post scope rule.
 *
 * @param array{id:string,attributes:array<string,mixed>} $rule Rule.
 *
 * @return array{id:string, attributes: TTested_Post_Scope_Rule}
 */
function sanitize_tested_post_scope( $rule ) {
	return array(
		'id'         => $rule['id'],
		'attributes' => array( 'type' => 'tested-post' ),
	);
}

/**
 * Sanitizes a custom URL scope rule.
 *
 * @param array{id:string,attributes:array<string,mixed>}      $rule Rule.
 * @param 'exact'|'different'|'partial'|'partial-not-included' $type Rule type.
 *
 * @return array{id:string, attributes: TCustom_Url_Scope_Rule}|false
 */
function sanitize_custom_url_scope( $rule, $type ) {
	$value = $rule['attributes']['value'] ?? '';
	$value = is_string( $value ) ? $value : '';
	$value = trim( $value );
	if ( empty( $value ) ) {
		return false;
	}

	return array(
		'id'         => $rule['id'],
		'attributes' => array(
			'type'  => $type,
			'value' => $value,
		),
	);
}

/**
 * Sanitizes a custom URL scope rule.
 *
 * @param array{id:string,attributes:array<string,mixed>} $rule Rule.
 * @param \Nelio_AB_Testing_Experiment                    $experiment Experiment.
 *
 * @return array{id:string, attributes: TTested_Url_With_Query_Args_Scope_Rule}
 */
function sanitize_tested_url_with_query_args( $rule, $experiment ) {
	$value = $rule['attributes']['value'] ?? array();
	$value = is_array( $value ) ? $value : array();

	$args = $value['args'] ?? array();
	/** @var list<TQuery_Arg_Setting> */
	$args = is_array( $args ) ? $args : array();
	$args = array_filter( $args, fn( $q ) => ! empty( $q['name'] ) );
	$args = array_values( $args );

	$urls = $experiment->get_alternatives();
	$urls = array_map( fn( $a ) => $a['attributes']['url'] ?? '', $urls );
	$urls = array_map( fn( $u ) => is_string( $u ) ? $u : '', $urls );
	$urls = array_values( array_filter( $urls ) );

	return array(
		'id'         => $rule['id'],
		'attributes' => array(
			'type'  => 'tested-url-with-query-args',
			'value' => array(
				'args' => $args,
				'urls' => $urls,
			),
		),
	);
}

/**
 * Sanitizes a custom URL scope rule.
 *
 * @param array{id:string,attributes:array<string,mixed>} $rule Rule.
 *
 * @return array{id:string, attributes: TCustom_Php_Scope_Rule}|false
 */
function sanitize_php_snippet( $rule ) {
	$ori_value = $rule['attributes']['value'] ?? array();
	$ori_value = is_array( $ori_value ) ? $ori_value : array();

	$snippet = trim( is_string( $ori_value['snippet'] ) ? $ori_value['snippet'] : '' );
	if ( empty( $snippet ) ) {
		return false;
	}

	$priority = $ori_value['priority'] ?? 'low';
	$priority = in_array( $priority, array( 'low', 'mid', 'high' ), true ) ? $priority : 'low';

	$preview_url = $ori_value['previewUrl'] ?? '';
	$preview_url = is_string( $preview_url ) ? $preview_url : '';

	$error_message   = is_string( $ori_value['errorMessage'] ?? '' ) ? ( $ori_value['errorMessage'] ?? '' ) : '';
	$warning_message = is_string( $ori_value['warningMessage'] ?? '' ) ? ( $ori_value['warningMessage'] ?? '' ) : '';

	if ( isset( $ori_value['validateSnippet'] ) ) {
		try {
			$error_message   = '';
			$warning_message = '';
			nab_eval_php( $snippet );
		} catch ( \Nelio_AB_Testing_Php_Evaluation_Exception $e ) {
			$error_message = $e->getMessage();
		} catch ( \ParseError $e ) {
			$error_message = $e->getMessage();
		} catch ( \Error $e ) {
			$warning_message = $e->getMessage();
		}
	}

	$value = array(
		'priority'   => $priority,
		'snippet'    => $snippet,
		'previewUrl' => $preview_url,
	);
	if ( ! empty( $error_message ) ) {
		$value['errorMessage'] = $error_message;
	}
	if ( ! empty( $warning_message ) ) {
		$value['warningMessage'] = $warning_message;
	}

	return array(
		'id'         => $rule['id'],
		'attributes' => array(
			'type'  => 'php-snippet',
			'value' => $value,
		),
	);
}
