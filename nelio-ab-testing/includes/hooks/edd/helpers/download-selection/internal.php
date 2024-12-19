<?php
namespace Nelio_AB_Testing\EasyDigitalDownloads\Helpers\Download_Selection\Internal;

defined( 'ABSPATH' ) || exit;

function do_downloads_match_by_id( $selection, $download_ids ) {
	$tracked_dids  = $selection['downloadIds'];
	$matching_dids = array_intersect( $download_ids, $tracked_dids );

	$excluded = ! empty( $selection['excluded'] );
	$mode     = $selection['mode'];
	if ( $excluded ) {
		return 'and' === $mode
			? empty( $matching_dids )
			: count( $matching_dids ) < $tracked_dids;
	}//end if

	$mode = $selection['mode'];
	return 'and' === $mode
		? count( $tracked_dids ) === count( $matching_dids )
		: ! empty( $tracked_dids );
}//end do_downloads_match_by_id()

function do_downloads_match_by_taxonomy( $selection, $download_ids ) {
	$tracked_terms  = $selection['termIds'];
	$actual_terms   = get_all_terms( $selection['taxonomy'], $download_ids );
	$matching_terms = array_intersect( $actual_terms, $tracked_terms );

	$excluded = ! empty( $selection['excluded'] );
	$mode     = $selection['mode'];
	if ( $excluded ) {
		return 'and' === $mode
			? empty( $matching_terms )
			: count( $matching_terms ) < $tracked_terms;
	}//end if

	$mode = $selection['mode'];
	return 'and' === $mode
		? count( $matching_terms ) === count( $tracked_terms )
		: ! empty( $matching_terms );
}//end do_downloads_match_by_taxonomy()

function get_all_terms( $taxonomy, $download_ids ) {
	$term_ids = array_map(
		function ( $did ) use ( $taxonomy ) {
			$terms = wp_get_post_terms( $did, $taxonomy, array( 'fields' => 'ids' ) );
			return is_wp_error( $terms ) ? array() : $terms;
		},
		$download_ids
	);
	return array_values( array_unique( array_merge( array(), ...$term_ids ) ) );
}//end get_all_terms()
