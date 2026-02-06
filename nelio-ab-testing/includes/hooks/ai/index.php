<?php

namespace Nelio_AB_Testing\AI;

defined( 'ABSPATH' ) || exit;

/**
 * Callback to add a "rand" option to "orderby" prop in REST collection params.
 *
 * @param array{orderby?:array{enum:list<string>|false}} $params Params.
 *
 * @return array<string,mixed>
 */
function allow_orderby_rand_for_rest( $params ) {
	if ( isset( $params['orderby']['enum'] ) ) {
		$result = $params['orderby']['enum'];
		$result = is_array( $result ) ? $result : array();
		$result = array_merge( $result, array( 'rand' ) );
		$result = array_unique( $result );
		$result = array_values( $result );

		$params['orderby']['enum'] = $result;
	}
	return $params;
}

add_action(
	'init',
	function () {
		$post_types = get_post_types(
			array(
				'public'       => true,
				'show_in_rest' => true,
			),
			'names'
		);
		foreach ( $post_types as $type ) {
			add_filter( "rest_{$type}_collection_params", __NAMESPACE__ . '\allow_orderby_rand_for_rest' );
		}
	}
);

// phpcs:ignore WordPress.Security.NonceVerification.Recommended
if ( isset( $_GET['nab-ai-preview'] ) ) {
	// phpcs:ignore WordPressVIPMinimum.UserExperience.AdminBarRemoval.RemovalDetected
	add_filter( 'show_admin_bar', '__return_false' );
}
