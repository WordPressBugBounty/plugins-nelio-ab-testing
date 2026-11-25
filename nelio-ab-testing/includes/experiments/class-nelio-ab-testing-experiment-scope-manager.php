<?php
/**
 * Sanitizes and normalizes experiment scope.
 *
 * @package Nelio_AB_Testing
 */

defined( 'ABSPATH' ) || exit;

/**
 * Manages experiment scope.
 */
class Nelio_AB_Testing_Experiment_Scope_Manager {

	/**
	 * Sanitizes experiment scope.
	 *
	 * @param list<TScope_Rule>           $scope      Scope rules.
	 * @param Nelio_AB_Testing_Experiment $experiment Experiment.
	 *
	 * @return list<TScope_Rule> Sanitized scope rules.
	 */
	public static function sanitize( $scope, $experiment ) {
		/**
		 * Sanitizes test scope.
		 *
		 * @param list<TScope_Rule>           $scope      Scope rules.
		 * @param Nelio_AB_Testing_Experiment $experiment Experiment.
		 *
		 * @since 7.3.0
		 */
		return apply_filters( 'nab_sanitize_experiment_scope', $scope, $experiment );
	}

	/**
	 * Normalizes experiment scope before saving it.
	 *
	 * @param list<TScope_Rule>           $scope      Scope rules.
	 * @param Nelio_AB_Testing_Experiment $experiment Experiment.
	 *
	 * @return list<TScope_Rule> Normalized scope rules.
	 */
	public static function normalize_pre_save( $scope, $experiment ) {
		/**
		 * Normalizes test scope.
		 *
		 * @param list<TScope_Rule>           $scope      Scope rules.
		 * @param Nelio_AB_Testing_Experiment $experiment Experiment.
		 *
		 * @since 9.0.0
		 */
		return apply_filters( 'nab_normalize_experiment_scope_pre_save', $scope, $experiment );
	}

	/**
	 * Validates experiment scope.
	 *
	 * @param array<array-key,mixed>      $scope      Scope rules.
	 * @param Nelio_AB_Testing_Experiment $experiment Experiment.
	 *
	 * @return TAttributes_Validation Validation result.
	 */
	public static function ai_validate( $scope, $experiment ) {
		$issues     = array();
		$definition = nelioab()->get_experiment_type( $experiment->get_type() );
		$scope_type = is_array( $definition ) ? $definition['scope'] ?? '' : '';
		$exclusions = $experiment->get_page_view_scope_exclusions();

		$issues = array_merge( $issues, self::ai_validate_urls_or_php_scope( $scope, $scope_type ) );

		$url_rules = array();
		foreach ( $scope as $rule ) {
			$validation = self::ai_validate_rule( $rule, $scope_type );
			$issues     = array_merge( $issues, $validation['issues'] );
			if ( $validation['isUrl'] ) {
				$url_rules[] = $rule;
			}
		}

		$excluded_url_rules = 0;
		foreach ( $url_rules as $rule ) {
			if ( is_array( $rule ) && is_string( $rule['id'] ?? null ) && in_array( $rule['id'], $exclusions, true ) ) {
				++$excluded_url_rules;
			}
		}

		if ( ! empty( $url_rules ) && count( $url_rules ) === $excluded_url_rules ) {
			$issues[] = 'No rules in the test scope have page-view tracking enabled.';
		}

		$issues = array_values( array_unique( $issues ) );
		return array(
			'status' => empty( $issues ) ? 'valid' : 'invalid',
			'issues' => $issues,
		);
	}

	/**
	 * Validates the relationship between URL and PHP rules in a scope.
	 *
	 * @param array<array-key,mixed> $scope      Scope rules.
	 * @param string                 $scope_type Experiment type scope support.
	 *
	 * @return list<string> Validation issues.
	 */
	private static function ai_validate_urls_or_php_scope( $scope, $scope_type ) {
		if ( 'urls-or-php' !== $scope_type ) {
			return array();
		}

		$php_rule_count = 0;
		foreach ( $scope as $rule ) {
			if ( ! is_array( $rule ) || ! is_array( $rule['attributes'] ?? null ) ) {
				continue;
			}

			$type = $rule['attributes']['type'] ?? '';
			if ( 'php-snippet' === $type ) {
				++$php_rule_count;
			}
		}

		$issues = array();
		if ( $php_rule_count > 0 && count( $scope ) > 1 ) {
			$issues[] = 'The test scope must contain either one PHP rule or one or more URL rules.';
		}

		return $issues;
	}

	/**
	 * Validates one experiment scope rule.
	 *
	 * @param mixed  $rule       Scope rule.
	 * @param string $scope_type Experiment type scope support.
	 *
	 * @return array{issues:list<string>,isUrl:bool} Rule validation result.
	 */
	private static function ai_validate_rule( $rule, $scope_type ) {
		if ( ! is_array( $rule ) || ! is_array( $rule['attributes'] ?? null ) ) {
			return array(
				'issues' => array( 'Every test scope rule must be an object with attributes.' ),
				'isUrl'  => false,
			);
		}

		$attributes = $rule['attributes'];
		$type       = $attributes['type'] ?? '';
		if ( ! is_string( $type ) ) {
			return array(
				'issues' => array( 'Every test scope rule must specify a type.' ),
				'isUrl'  => false,
			);
		}

		$allowed_types = array();
		switch ( $scope_type ) {
			case 'tested-post-with-consistency':
			case 'tested-product-with-consistency':
				$allowed_types = array( 'tested-post' );
				break;

			case 'urls':
				$allowed_types = array( 'exact', 'partial', 'partial-not-included', 'different' );
				break;

			case 'urls-with-tested-post':
				$allowed_types = array( 'exact', 'partial', 'partial-not-included', 'different' );
				break;

			case 'tested-url-with-query-args':
				$allowed_types = array( 'tested-url-with-query-args' );
				break;

			case 'urls-or-php':
				$allowed_types = array( 'exact', 'partial', 'partial-not-included', 'different', 'php-snippet' );
				break;
		}

		if ( ! in_array( $type, $allowed_types, true ) ) {
			return array(
				'issues' => array( sprintf( 'The `%s` scope rule is not supported by this test type.', $type ) ),
				'isUrl'  => false,
			);
		}

		switch ( $type ) {
			case 'exact':
			case 'different':
				$value = is_string( $attributes['value'] ?? null ) ? trim( $attributes['value'] ) : '';
				return array(
					'issues' => '' === $value || nab_ai_is_url_invalid( $value ) || 0 !== strpos( untrailingslashit( $value ), untrailingslashit( nab_home_url() ) )
						? array( 'Exact and different scope rules must contain a complete local URL.' )
						: array(),
					'isUrl'  => true,
				);

			case 'partial':
			case 'partial-not-included':
				$value = is_string( $attributes['value'] ?? null ) ? trim( $attributes['value'] ) : '';
				return array(
					'issues' => '' === $value || nab_ai_is_url_fragment_invalid( $value )
						? array( 'Partial scope rules must contain a valid URL fragment or local URL.' )
						: array(),
					'isUrl'  => true,
				);

			case 'tested-url-with-query-args':
				$value = $attributes['value'] ?? array();
				$args  = is_array( $value ) && is_array( $value['args'] ?? null ) ? $value['args'] : array();
				$names = array();
				foreach ( $args as $arg ) {
					$name = is_array( $arg ) && is_string( $arg['name'] ?? null ) ? $arg['name'] : '';
					if ( '' !== $name ) {
						$names[ $name ] = ( $names[ $name ] ?? 0 ) + 1;
					}
				}
				$issues = array();
				foreach ( $names as $name => $count ) {
					if ( $count > 1 ) {
						$issues[] = sprintf(
							'Query parameter `%s` should only appear once in the test scope.',
							$name
						);
					}
				}
				return array(
					'issues' => $issues,
					'isUrl'  => false,
				);

			case 'php-snippet':
				$value = $attributes['value'] ?? array();
				return array(
					'issues' => ! is_array( $value ) || ! empty( $value['errorMessage'] )
						? array( 'The PHP-function scope contains an error.' )
						: array(),
					'isUrl'  => false,
				);

			case 'tested-post':
				return array(
					'issues' => array(),
					'isUrl'  => false,
				);

			default:
				// @codeCoverageIgnoreStart
				return array(
					'issues' => array( 'The test scope contains an unsupported rule type.' ),
					'isUrl'  => false,
				);
				// @codeCoverageIgnoreEnd
		}
	}
}
