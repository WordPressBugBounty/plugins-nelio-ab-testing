<?php

namespace Nelio_AB_Testing\Experiment_Library\Post_Experiment;

defined( 'ABSPATH' ) || exit;

use function add_filter;
use function function_exists;
use function is_home;
use function is_singular;
use function nab_get_experiment;
use function nab_get_queried_object_id;
use function wp_list_pluck;

add_filter( 'nab_nab/page_supports_heatmaps', '__return_true' );
add_filter( 'nab_nab/post_supports_heatmaps', '__return_true' );
add_filter( 'nab_nab/custom-post-type_supports_heatmaps', '__return_true' );

function should_be_inactive_in_frontend( $inactive, $experiment ) {
	$scope = $experiment->get_scope();

	$runs_on_tested_page_only = ! empty( $scope );
	if ( $runs_on_tested_page_only ) {
		return false;
	}//end if

	$context = array(
		'postId' => nab_get_queried_object_id(),
	);
	$experiment->set_scope(
		array(
			array(
				'id'         => 'fake',
				'attributes' => array( 'type' => 'tested-post' ),
			),
		)
	);
	$is_tested_page = nab_is_experiment_relevant( $context, $experiment );
	$experiment->set_scope( $scope );
	if ( $is_tested_page ) {
		return false;
	}//end if

	return true;
}//end should_be_inactive_in_frontend()
add_filter( 'nab_nab/page_should_be_inactive_in_frontend', __NAMESPACE__ . '\should_be_inactive_in_frontend', 10, 2 );
add_filter( 'nab_nab/post_should_be_inactive_in_frontend', __NAMESPACE__ . '\should_be_inactive_in_frontend', 10, 2 );
add_filter( 'nab_nab/custom-post-type_should_be_inactive_in_frontend', __NAMESPACE__ . '\should_be_inactive_in_frontend', 10, 2 );

function is_current_post_under_test( $_, $__, $___, $experiment_id ) {
	$post_id  = get_current_post_id();
	$post_ids = get_alternative_post_ids( $experiment_id );
	return in_array( $post_id, $post_ids, true );
}//end is_current_post_under_test()

function get_current_post_id() {
	if ( ! is_singular() ) {
		return 0;
	}//end if

	if ( is_home() ) {
		return get_option( 'page_on_front', 0 );
	}//end if

	if ( function_exists( 'is_shop' ) && function_exists( 'wc_get_page_id' ) && is_shop() ) {
		return absint( wc_get_page_id( 'shop' ) );
	}//end if

	return nab_get_queried_object_id();
}//end get_current_post_id()

function get_alternative_post_ids( $experiment_id ) {
	$experiment   = nab_get_experiment( $experiment_id );
	$alternatives = $experiment->get_alternatives();
	$alternatives = wp_list_pluck( $alternatives, 'attributes' );

	$post_ids = wp_list_pluck( $alternatives, 'postId' );
	$post_ids = array_map( 'absint', $post_ids );
	return array_values( array_filter( $post_ids ) );
}//end get_alternative_post_ids()
