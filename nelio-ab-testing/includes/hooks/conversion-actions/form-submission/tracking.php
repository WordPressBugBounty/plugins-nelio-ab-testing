<?php

namespace Nelio_AB_Testing\Conversion_Action_Library\Form_Submission;

defined( 'ABSPATH' ) || exit;

use function nab_get_experiments_with_page_view_from_request;
use function nab_get_segments_from_request;
use function nab_get_unique_views_from_request;
use function nab_track_conversion;

function maybe_sync_event_submission( $experiment_id, $goal_index ) {

	$experiments = nab_get_experiments_with_page_view_from_request();
	if ( ! isset( $experiments[ $experiment_id ] ) ) {
		return;
	}//end if

	$all_views      = nab_get_experiments_with_page_view_from_request();
	$all_unique_ids = nab_get_unique_views_from_request();
	$all_segments   = nab_get_segments_from_request();

	$alternative = nab_array_get( $all_views, $experiment_id, false );
	$unique_id   = nab_array_get( $all_unique_ids, $experiment_id, false );
	$segments    = nab_array_get( $all_segments, $experiment_id, array( 0 ) );

	nab_track_conversion(
		$experiment_id,
		$goal_index,
		$alternative,
		array(
			'unique_id' => $unique_id,
			'segments'  => $segments,
		)
	);
}//end maybe_sync_event_submission()
