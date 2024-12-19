<?php
namespace Nelio_AB_Testing\Hooks\Experiment_Scope\Preview;

defined( 'ABSPATH' ) || exit;

function get_preview_url_from_scope( $scope, $alternative_id ) {

	if ( empty( $alternative_id ) ) {
		return false;
	}//end if

	$url = nab_home_url();
	if ( ! empty( $scope ) ) {
		$url = find_preview_url_in_scope( $scope );
	}//end if

	if ( $url ) {
		return $url;
	}//end if

	return false;
}//end get_preview_url_from_scope()
