<?php

namespace Nelio_AB_Testing\AI;

function allow_orderby_rand_for_rest( $params ) {
	if ( isset( $params['orderby']['enum'] ) ) {
		$result = nab_array_get( $params, 'orderby.enum', array() );
		$result = is_array( $result ) ? $result : array();
		$result = array_merge(
			$params['orderby']['enum'],
			array( 'rand' )
		);
		$result = array_unique( $result );
		$result = array_values( $result );

		$params['orderby']['enum'] = $result;
	}//end if
	return $params;
}//end allow_orderby_rand_for_rest()

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
		}//end foreach
	}
);

if ( isset( $_GET['nab-ai-preview'] ) ) { // phpcs:ignore
	add_filter( 'show_admin_bar', '__return_false' ); // phpcs:ignore
}//end if
