<?php

namespace Nelio_AB_Testing\Experiment_Library\Post_Experiment;

defined( 'ABSPATH' ) || exit;

function sanitize_control_attributes( $control ) {
	$defaults = array(
		'postId'   => 0,
		'postType' => '',
	);
	return wp_parse_args( $control, $defaults );
}//end sanitize_control_attributes()
add_filter( 'nab_nab/page_sanitize_control_attributes', __NAMESPACE__ . '\sanitize_control_attributes' );
add_filter( 'nab_nab/post_sanitize_control_attributes', __NAMESPACE__ . '\sanitize_control_attributes' );
add_filter( 'nab_nab/custom-post-type_sanitize_control_attributes', __NAMESPACE__ . '\sanitize_control_attributes' );

function sanitize_alternative_attributes( $alternative ) {
	$defaults = array(
		'name'   => '',
		'postId' => 0,
	);
	return wp_parse_args( $alternative, $defaults );
}//end sanitize_alternative_attributes()
add_filter( 'nab_nab/page_sanitize_alternative_attributes', __NAMESPACE__ . '\sanitize_alternative_attributes' );
add_filter( 'nab_nab/post_sanitize_alternative_attributes', __NAMESPACE__ . '\sanitize_alternative_attributes' );
add_filter( 'nab_nab/custom-post-type_sanitize_alternative_attributes', __NAMESPACE__ . '\sanitize_alternative_attributes' );
