<?php

namespace Nelio_AB_Testing\Recommendation_Engine\Providers;

use Nelio_AB_Testing\Recommendation_Engine\Experiment_Repository;
use Nelio_AB_Testing\Recommendation_Engine\Opportunity;
use Nelio_AB_Testing\Recommendation_Engine\Providers\Traffic\Traffic_Provider;

final class High_Traffic_Provider implements Opportunity_Provider {

	/**
	 * @var Traffic_Provider
	 */
	private $traffic;

	/**
	 * @var Experiment_Repository
	 */
	private $experiment_repo;

	/**
	 * @var int Max number of opportunities.
	 */
	private $limit;

	/**
	 * Creates a new instance of this class.
	 *
	 * @param Traffic_Provider      $traffic         Traffic provider.
	 * @param Experiment_Repository $experiment_repo Tested content repository.
	 * @param int                   $limit           Optional. Max number of opportunties. Default: `20`.
	 *
	 * @return void
	 */
	public function __construct( $traffic, $experiment_repo, $limit = 20 ) {
		$this->traffic         = $traffic;
		$this->experiment_repo = $experiment_repo;
		$this->limit           = absint( $limit );
	}

	// @Implements
	public function get_opportunities() {
		$opportunities = array();

		try {
			$top_pages = $this->traffic->get_top_pages( $this->limit );
		} catch ( \Throwable $e ) {
			$top_pages = array();
		}

		foreach ( $top_pages as $content ) {
			// High traffic pages should probably be tested every three months.
			if ( $this->experiment_repo->was_tested( $content->get_post_id(), 'last-3-months' ) ) {
				continue;
			}

			$views = $content->get_views();

			$opportunities[] = new Opportunity(
				'high-traffic',
				min( 100, 60 + (int) log( max( 1, $views ), 1.3 ) ),
				array(
					'type'     => 'post',
					'postType' => $content->get_post_type(),
					'postId'   => $content->get_post_id(),
					'title'    => get_the_title( $content->get_post_id() ),
					'url'      => $content->get_url(),
				),
				array(
					'views' => $views,
				)
			);
		}

		return $opportunities;
	}
}
