<?php
namespace Nelio_AB_Testing\Hooks\Experiment_Scope\Preview;

defined( 'ABSPATH' ) || exit;

/**
 * Given a test scope, it returns the appropriate preview URL for the given alternative ID.
 *
 * @param list<TScope_Rule> $scope          Test scope.
 * @param string            $alternative_id Alternative ID.
 *
 * @return string|false
 *
 * @since 7.3.0
 */
function get_preview_url_from_scope( $scope, $alternative_id ) {

	/**
	 * Short-circuits the function to find a preview URL from scope.
	 *
	 * @param null|false|string $url            Preview URL.
	 * @param string            $alternative_id Alternative ID.
	 *
	 * @since 8.3.0
	 */
	$url = apply_filters( 'nab_pre_get_preview_url_from_scope', null, $alternative_id );
	if ( ! is_null( $url ) ) {
		return $url;
	}

	if ( empty( $alternative_id ) ) {
		return false;
	}

	return find_preview_url_in_scope( $scope );
}
