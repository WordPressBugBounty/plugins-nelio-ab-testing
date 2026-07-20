<?php

namespace Nelio_AB_Testing\Recommendation_Engine\Providers;

use Nelio_AB_Testing\Recommendation_Engine\Experiment_Repository;
use Nelio_AB_Testing\Recommendation_Engine\Opportunity;

final class Stale_Experiments_Provider implements Opportunity_Provider {

	/**
	 * @var Experiment_Repository
	 */
	private $experiment_repo;

	/**
	 * Creates a new instance of this class.
	 *
	 * @param Experiment_Repository $experiment_repo Tested content repository.
	 *
	 * @return void
	 */
	public function __construct( $experiment_repo ) {
		$this->experiment_repo = $experiment_repo;
	}

	// @Implements
	public function get_opportunities() {
		$opportunities = array();
		$recent        = array();
		$recent_cutoff = gmdate( 'Y-m-01\T00:00:00.000\Z', strtotime( '-3 months' ) );
		$stale_cutoff  = gmdate( 'Y-m-01\T00:00:00.000\Z', strtotime( '-6 months' ) );

		foreach ( $this->experiment_repo->get_tested_content( 'last50' ) as $content ) {
			$target = $content->get_target();
			if ( in_array( $target['url'], $recent, true ) ) {
				continue;
			}

			if ( $content->get_last_tested_at() >= $recent_cutoff ) {
				$recent[] = $target['url'];
				continue;
			}

			if ( $content->get_last_tested_at() > $stale_cutoff ) {
				continue;
			}

			$experiment = nab_get_experiment( $content->get_experiment_id() );
			if ( is_wp_error( $experiment ) ) {
				continue;
			}

			$opportunities[] = new Opportunity(
				'stale-experiment',
				65,
				$content->get_target(),
				Opportunity::get_experiment_metas( $experiment )
			);
		}

		return $opportunities;
	}
}
