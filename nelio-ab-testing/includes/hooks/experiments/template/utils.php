<?php
namespace Nelio_AB_Testing\Experiment_Library\Template_Experiment;

defined( 'ABSPATH' ) || exit;

function get_actual_template( $post_id ) {
	global $wpdb;
	$template = $wpdb->get_var( // phpcs:ignore
		$wpdb->prepare(
			"SELECT meta_value FROM $wpdb->postmeta WHERE meta_key = %s AND post_id = %d",
			array( '_wp_page_template', $post_id )
		)
	);

	if ( ! locate_template( $template ) ) {
		$template = 'default';
	}//end if

	return $template;
}//end get_actual_template()
