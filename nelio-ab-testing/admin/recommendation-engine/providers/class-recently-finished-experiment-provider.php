<?php

namespace Nelio_AB_Testing\Recommendation_Engine\Providers;

use Nelio_AB_Testing\Recommendation_Engine\Experiment_Repository;
use Nelio_AB_Testing\Recommendation_Engine\Opportunity;

final class Recently_Finished_Experiment_Provider implements Opportunity_Provider {

	/**
	 * @var Experiment_Repository
	 */
	private $experiment_repo;

	/**
	 * Creates a new instance of this class.
	 *
	 * @param Experiment_Repository $experiment_repo Experiment repository.
	 *
	 * @return void
	 */
	public function __construct( $experiment_repo ) {
		$this->experiment_repo = $experiment_repo;
	}

	// @Implements
	public function get_opportunities() {
		$opportunities = array();
		$recent_cutoff = gmdate( 'Y-m-01\T00:00:00.000\Z', strtotime( '-3 months' ) );
		foreach ( $this->experiment_repo->get_tested_content( 'last50' ) as $content ) {
			if ( $content->get_last_tested_at() < $recent_cutoff ) {
				continue;
			}

			$experiment = nab_get_experiment( $content->get_experiment_id() );
			if ( is_wp_error( $experiment ) ) {
				continue;
			}

			$opportunities[] = new Opportunity(
				'recently-finished-experiment',
				75,
				$content->get_target(),
				Opportunity::get_experiment_metas( $experiment )
			);
		}

		return $opportunities;
	}
}
