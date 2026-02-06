<?php
namespace Nelio_AB_Testing\EasyDigitalDownloads\Helpers\Download_Selection\Internal;

defined( 'ABSPATH' ) || exit;

/**
 * Whether the downloaded items match the tracked selection.
 *
 * @param TEdd_Selected_Download_Ids $selection Tracked selection.
 * @param list<int>                  $download_ids Downloaded item IDs.
 * @return bool
 */
function do_downloads_match_by_id( $selection, $download_ids ) {
	$tracked_dids  = $selection['downloadIds'];
	$matching_dids = array_intersect( $download_ids, $tracked_dids );

	$excluded = ! empty( $selection['excluded'] );
	$mode     = $selection['mode'];
	if ( $excluded ) {
		return 'and' === $mode
			? empty( $matching_dids )
			: count( $matching_dids ) < count( $tracked_dids );
	}

	$mode = $selection['mode'];
	return 'and' === $mode
		? count( $tracked_dids ) === count( $matching_dids )
		: ! empty( $tracked_dids );
}

/**
 * Whether the downloaded items match the tracked selection.
 *
 * @param TEdd_Selected_Download_Terms $selection Tracked selection.
 * @param list<int>                    $download_ids Downloaded item IDs.
 * @return bool
 */
function do_downloads_match_by_taxonomy( $selection, $download_ids ) {
	$tracked_terms  = $selection['termIds'];
	$actual_terms   = get_all_terms( $selection['taxonomy'], $download_ids );
	$matching_terms = array_intersect( $actual_terms, $tracked_terms );

	$excluded = ! empty( $selection['excluded'] );
	$mode     = $selection['mode'];
	if ( $excluded ) {
		return 'and' === $mode
			? empty( $matching_terms )
			: count( $matching_terms ) < count( $tracked_terms );
	}

	$mode = $selection['mode'];
	return 'and' === $mode
		? count( $matching_terms ) === count( $tracked_terms )
		: ! empty( $matching_terms );
}

/**
 * Returns all terms in a taxonomy.
 *
 * @param string    $taxonomy Taxonomy name.
 * @param list<int> $download_ids Downloaded item IDs.
 *
 * @return list<int>
 */
function get_all_terms( $taxonomy, $download_ids ) {
	$term_ids = array_map(
		function ( $did ) use ( $taxonomy ) {
			$terms = wp_get_post_terms( $did, $taxonomy, array( 'fields' => 'ids' ) );
			return is_wp_error( $terms ) ? array() : $terms;
		},
		$download_ids
	);
	return array_values( array_unique( array_merge( array(), ...$term_ids ) ) );
}
