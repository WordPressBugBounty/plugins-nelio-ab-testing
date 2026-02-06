<?php

namespace Nelio_AB_Testing\Conversion_Action_Library\Form_Submission;

defined( 'ABSPATH' ) || exit;

use function nab_get_experiments_with_page_view_from_request;
use function nab_get_segments_from_request;
use function nab_get_unique_views_from_request;
use function nab_track_conversion;

/**
 * Synchronizes form submission event for the given experiment ID and goal index if the visitor saw given test.
 *
 * @param int $experiment_id Experiment ID.
 * @param int $goal_index    Goal index.
 *
 * @return void
 */
function maybe_sync_event_submission( $experiment_id, $goal_index ) {

	$experiments = nab_get_experiments_with_page_view_from_request();
	if ( ! isset( $experiments[ $experiment_id ] ) ) {
		return;
	}

	$all_views      = nab_get_experiments_with_page_view_from_request();
	$all_unique_ids = nab_get_unique_views_from_request();
	$all_segments   = nab_get_segments_from_request();

	$alternative = $all_views[ $experiment_id ] ?? false;
	$unique_id   = $all_unique_ids[ $experiment_id ] ?? false;
	$segments    = $all_segments[ $experiment_id ] ?? array( 0 );

	$options = array(
		'segments' => $segments,
	);

	if ( ! empty( $unique_id ) ) {
		$options['unique_id'] = $unique_id;
	}

	$ga4_client_id = nab_get_ga4_client_id_from_request();
	if ( ! empty( $ga4_client_id ) ) {
		$options['ga4_client_id'] = $ga4_client_id;
	}

	nab_track_conversion(
		$experiment_id,
		$goal_index,
		$alternative,
		$options
	);
}
