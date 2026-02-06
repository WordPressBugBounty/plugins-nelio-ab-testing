<?php

namespace Nelio_AB_Testing\EasyDigitalDownloads\Helpers\Download_Selection;

use function Nelio_AB_Testing\EasyDigitalDownloads\Helpers\Download_Selection\Internal\do_downloads_match_by_id;
use function Nelio_AB_Testing\EasyDigitalDownloads\Helpers\Download_Selection\Internal\do_downloads_match_by_taxonomy;

defined( 'ABSPATH' ) || exit;

/**
 * Whether the downloaded items match the tracked selection.
 *
 * @param TEdd_Download_Selection $download_selection Tracked selection.
 * @param int|list<int>           $download_ids Downloaded item IDs.
 * @return bool
 */
function do_downloads_match( $download_selection, $download_ids ) {
	if ( ! is_array( $download_ids ) ) {
		$download_ids = array( $download_ids );
	}

	if ( 'all-downloads' === $download_selection['type'] ) {
		return true;
	}

	$selection = $download_selection['value'];
	switch ( $selection['type'] ) {
		case 'download-ids':
			return do_downloads_match_by_id( $selection, $download_ids );

		case 'download-taxonomies':
			return nab_every(
				function ( $download_term_selection ) use ( &$download_ids ) {
					return do_downloads_match_by_taxonomy( $download_term_selection, $download_ids );
				},
				$selection['value']
			);

		default:
			return false;
	}
}
