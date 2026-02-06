<?php

namespace Nelio_AB_Testing\Compat\Nelio_Popups;

defined( 'ABSPATH' ) || exit;

use function add_action;
use function add_filter;

/**
 * Loads the alternative popup.
 *
 * @param TPopup_Control_Attributes|TPopup_Alternative_Attributes $alternative   Alternative.
 * @param TPopup_Control_Attributes                               $control       Alternative.
 * @param int                                                     $experiment_id Experiment ID.
 *
 * @return void
 */
function load_alternative( $alternative, $control, $experiment_id ) {
	if ( ! is_nelio_popup( $control ) ) {
		return;
	}

	$experiment = nab_get_experiment( $experiment_id );
	if ( is_wp_error( $experiment ) ) {
		return;
	}

	$tested_ids    = array_map( fn( $a ) => absint( $a['attributes']['postId'] ?? 0 ), $experiment->get_alternatives() );
	$replace_popup = function ( $posts ) use ( &$replace_popup, &$alternative, &$tested_ids ) {
		/** @var list<\WP_Post> $posts */

		$has_tested_popup = array_reduce(
			$posts,
			fn( $r, $p ) => $r || ( 'nelio_popup' === $p->post_type && in_array( $p->ID, $tested_ids, true ) ),
			false
		);
		if ( ! $has_tested_popup ) {
			return $posts;
		}

		remove_filter( 'posts_results', $replace_popup );
		$posts    = array_filter( $posts, fn( $p ) => ! in_array( $p->ID, $tested_ids, true ) );
		$posts    = array_values( $posts );
		$alt_post = get_post( $alternative['postId'] );
		$posts    = array_merge( $posts, array( $alt_post ) );
		add_filter( 'posts_results', $replace_popup );
		return $posts;
	};
	add_filter( 'posts_results', $replace_popup );
}
add_action( 'nab_nab/popup_load_alternative', __NAMESPACE__ . '\load_alternative', 10, 3 );

/**
 * Whether the popup is relevant in the current URL or not.
 *
 * @param bool|null $relevant      Relevant.
 * @param int       $experiment_id Experiment ID.
 *
 * @return bool|null
 */
function is_relevant( $relevant, $experiment_id ) {
	// NOTE. Ideally, we want to be able to detect where a certain popup will show up.
	// Unfortunately, that’s currently not possible in Nelio Popups so... it is what it is.
	return is_testing_nelio_popup( $experiment_id ) ? true : $relevant;
}
add_filter( 'nab_is_nab/popup_relevant_in_url', __NAMESPACE__ . '\is_relevant', 10, 2 );
