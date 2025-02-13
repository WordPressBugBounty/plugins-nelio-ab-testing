<?php

namespace Nelio_AB_Testing\Compat\Nelio_Popups;

defined( 'ABSPATH' ) || exit;

use function add_action;
use function add_filter;

function prepare_alternative_popups() {
	if ( is_admin() ) {
		return;
	}//end if

	$experiments = nab_get_running_experiments();
	$experiments = array_filter( $experiments, __NAMESPACE__ . '\is_testing_nelio_popup' );

	if ( empty( $experiments ) ) {
		return;
	}//end if

	$all_popups = array_reduce(
		$experiments,
		function ( $result, $e ) {
			$popup_ids = array_map(
				fn( $a ) => absint( nab_array_get( $a, 'attributes.postId', 0 ) ),
				$e->get_alternatives()
			);
			return array_merge( $result, $popup_ids );
		},
		array()
	);

	$runtime       = \Nelio_AB_Testing_Runtime::instance();
	$alt           = $runtime->get_alternative_from_request();
	$active_popups = array_reduce(
		$experiments,
		function ( $result, $e ) use ( $alt ) {
			$alternatives = $e->get_alternatives();
			$alternative  = $alternatives[ $alt % count( $alternatives ) ];
			$alternative  = nab_array_get( $alternative, 'attributes.postId', 0 );
			$result[]     = absint( $alternative );
			return $result;
		},
		array()
	);

	$replace_popups = function ( $posts ) use ( &$replace_popups, $all_popups, $active_popups ) {
		$has_popups = array_reduce(
			$posts,
			fn( $r, $p ) => $r || 'nelio_popup' === $p->post_type,
			false
		);
		if ( ! $has_popups ) {
			return $posts;
		}//end if

		remove_filter( 'posts_results', $replace_popups );
		$posts = array_filter( $posts, fn( $p ) => ! in_array( $p->ID, $all_popups, true ) );
		$posts = array_values( $posts );
		$more  = get_posts(
			array(
				'post_in'   => $active_popups,
				'post_type' => 'nelio_popup',
			)
		);
		$posts = array_merge( $posts, $more );
		add_filter( 'posts_results', $replace_popups );
		return $posts;
	};
	add_filter( 'posts_results', $replace_popups );
}//end prepare_alternative_popups()
add_action( 'plugins_loaded', __NAMESPACE__ . '\prepare_alternative_popups', 100 );

function is_relevant( $relevant, $experiment_id ) {
	// NOTE. Ideally, we want to be able to detect where a certain popup will show up.
	// Unfortunately, that’s currently not possible in Nelio Popups so... it is what it is.
	return (
		is_testing_nelio_popup( $experiment_id ) ||
		$relevant
	);
}//end is_relevant()
add_action( 'nab_is_nab/popup_relevant_in_url', __NAMESPACE__ . '\is_relevant', 10, 2 );
