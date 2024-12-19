<?php
/**
 * This file defines hooks to filters and actions to make the plugin compatible with Permalink Manager.
 *
 * @package    Nelio_AB_Testing
 * @subpackage Nelio_AB_Testing/includes/experiments/library
 * @since      7.2.3
 */

namespace Nelio_AB_Testing\Compat\Permalink_Manager;

defined( 'ABSPATH' ) || exit;

function exclude_alternative_posts( $ids ) {
	if ( ! is_array( $ids ) ) {
		return $ids;
	}//end if

	$alternatives = array_reduce(
		nab_get_running_experiments(),
		fn( $r, $e ) => array_merge(
			$r,
			$e->get_alternatives( 'basic' )
		),
		array()
	);

	$exclude_ids = array_map(
		fn( $a ) => nab_array_get( $a, 'attributes.postId', 0 ),
		$alternatives
	);
	$exclude_ids = array_values( array_filter( $exclude_ids ) );
	return array_merge( $ids, $exclude_ids );
}//end exclude_alternative_posts()
add_filter( 'permalink_manager_excluded_post_ids', __NAMESPACE__ . '\exclude_alternative_posts' );
