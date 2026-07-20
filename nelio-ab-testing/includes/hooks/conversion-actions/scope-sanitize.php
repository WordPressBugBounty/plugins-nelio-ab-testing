<?php

namespace Nelio_AB_Testing\Conversion_Action_Library\Scope_Sanitize;

/**
 * Normalizes URL attributes.
 *
 * @param TConversion_Action_Scope $scope Conversion action’s scope.
 *
 * @return TConversion_Action_Scope
 */
function normalize_urls_attr( $scope ) {
	if ( 'urls' === $scope['type'] ) {
		$scope['regexes'] = array_map( 'nab_normalize_url', $scope['regexes'] );
	}
	return $scope;
}
add_filter( 'nab_sanitize_conversion_action_scope_pre_save', __NAMESPACE__ . '\normalize_urls_attr', 9999 );
