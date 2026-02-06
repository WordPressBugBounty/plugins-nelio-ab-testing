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

	if ( empty( $alternative_id ) ) {
		return false;
	}

	$url = nab_home_url();
	if ( ! empty( $scope ) ) {
		$url = find_preview_url_in_scope( $scope );
	}

	if ( $url ) {
		return $url;
	}

	return false;
}
