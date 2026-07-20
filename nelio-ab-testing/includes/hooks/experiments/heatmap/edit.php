<?php

namespace Nelio_AB_Testing\Experiment_Library\Heatmap_Experiment;

use function add_filter;

defined( 'ABSPATH' ) || exit;

/**
 * Filters whether an experiment can be started or not.
 *
 * @param false|string              $reason     Reason why the experiment can’t be started. Default: `false`.
 * @param \Nelio_AB_Testing_Heatmap $experiment The experiment.
 *
 * @return false|string Reason why the experiment can’t be started. Default: `false`.
 *
 * @since 8.5.0
 */
function can_be_started( $reason, $experiment ) {
	$tracking_mode = $experiment->get_tracking_mode();
	if ( 'post' === $tracking_mode ) {
		$tested_post_id = $experiment->get_tracked_post_id();
		if ( 'publish' !== get_post_status( $tested_post_id ) ) {
			return _x( 'The tracked post is not published.', 'text', 'nelio-ab-testing' );
		}
	}

	return $reason;
}
add_filter( 'nab_can_nab/heatmap_be_started', __NAMESPACE__ . '\can_be_started', 10, 2 );
