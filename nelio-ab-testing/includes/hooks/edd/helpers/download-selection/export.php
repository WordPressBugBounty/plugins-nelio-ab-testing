<?php

namespace Nelio_AB_Testing\EasyDigitalDownloads\Helpers\Download_Selection;

use function Nelio_AB_Testing\EasyDigitalDownloads\Helpers\Download_Selection\Internal\do_downloads_match_by_id;
use function Nelio_AB_Testing\EasyDigitalDownloads\Helpers\Download_Selection\Internal\do_downloads_match_by_taxonomy;

defined( 'ABSPATH' ) || exit;

function do_downloads_match( $download_selection, $download_ids ) {
	if ( ! is_array( $download_ids ) ) {
		$download_ids = array( $download_ids );
	}//end if

	if ( 'all-downloads' === $download_selection['type'] ) {
		return true;
	}//end if

	if ( 'some-downloads' !== $download_selection['type'] ) {
		return false;
	}//end if

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
	}//end switch
}//end do_downloads_match()
