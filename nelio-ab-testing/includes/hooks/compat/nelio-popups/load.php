<?php

namespace Nelio_AB_Testing\Compat\Nelio_Popups;

defined( 'ABSPATH' ) || exit;

use function add_action;
use function add_filter;

function load_alternative( $alternative, $control ) {
	if ( ! is_nelio_popup( $control ) ) {
		return;
	}//end if

	$replace_popup = function ( $posts ) use ( &$replace_popup, &$alternative, &$control ) {
		$has_tested_popup = array_reduce(
			$posts,
			fn( $r, $p ) => $r || ( 'nelio_popup' === $p->post_type && $control['postId'] === $p->ID ),
			false
		);
		if ( ! $has_tested_popup ) {
			return $posts;
		}//end if

		remove_filter( 'posts_results', $replace_popup );
		$posts    = array_filter( $posts, fn( $p ) => $p->ID !== $control['postId'] );
		$posts    = array_values( $posts );
		$alt_post = get_post( $alternative['postId'] );
		$posts    = array_merge( $posts, array( $alt_post ) );
		add_filter( 'posts_results', $replace_popup );
		return $posts;
	};
	add_filter( 'posts_results', $replace_popup );
}//end load_alternative()
add_action( 'nab_nab/popup_load_alternative', __NAMESPACE__ . '\load_alternative', 10, 2 );

function is_relevant( $relevant, $experiment_id ) {
	// NOTE. Ideally, we want to be able to detect where a certain popup will show up.
	// Unfortunately, that’s currently not possible in Nelio Popups so... it is what it is.
	return (
		is_testing_nelio_popup( $experiment_id ) ||
		$relevant
	);
}//end is_relevant()
add_action( 'nab_is_nab/popup_relevant_in_url', __NAMESPACE__ . '\is_relevant', 10, 2 );
