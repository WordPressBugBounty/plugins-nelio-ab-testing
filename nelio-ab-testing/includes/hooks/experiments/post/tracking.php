<?php

namespace Nelio_AB_Testing\Experiment_Library\Post_Experiment;

defined( 'ABSPATH' ) || exit;

use function add_filter;
use function nab_get_queried_object_id;

function get_page_view_tracking_location( $location, $experiment ) {
	return is_on_tested_page( $experiment ) ? $location : 'disabled';
}//end get_page_view_tracking_location()
add_filter( 'nab_nab/page_get_page_view_tracking_location', __NAMESPACE__ . '\get_page_view_tracking_location', 10, 2 );
add_filter( 'nab_nab/post_get_page_view_tracking_location', __NAMESPACE__ . '\get_page_view_tracking_location', 10, 2 );
add_filter( 'nab_nab/custom-post-type_get_page_view_tracking_location', __NAMESPACE__ . '\get_page_view_tracking_location', 10, 2 );

function supports_heatmaps( $_, $experiment ) {
	return is_on_tested_page( $experiment );
}//end supports_heatmaps()
add_filter( 'nab_nab/page_supports_heatmaps', __NAMESPACE__ . '\supports_heatmaps', 10, 2 );
add_filter( 'nab_nab/post_supports_heatmaps', __NAMESPACE__ . '\supports_heatmaps', 10, 2 );
add_filter( 'nab_nab/custom-post-type_supports_heatmaps', __NAMESPACE__ . '\supports_heatmaps', 10, 2 );

function is_on_tested_page( $experiment ) {
	$scope   = $experiment->get_scope();
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
	return $is_tested_page;
}//end is_on_tested_page()
