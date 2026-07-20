<?php

namespace Nelio_AB_Testing\Recommendation_Engine\Providers;

use Nelio_AB_Testing\Recommendation_Engine\Experiment_Repository;
use Nelio_AB_Testing\Recommendation_Engine\Opportunity;

final class Recently_Published_Content_Provider implements Opportunity_Provider {

	/**
	 * @var Experiment_Repository
	 */
	private $experiment_repo;

	/**
	 * @var list<string>
	 */
	private $post_types;

	/**
	 * @var int Number of days to detect recent content.
	 */
	private $days;

	/**
	 * @var int Max number of opportunities.
	 */
	private $limit;

	/**
	 * Creates a new instance of this class.
	 *
	 * @param Experiment_Repository $experiment_repo Tested content repository.
	 * @param list<string>          $post_types      Optional. Post types. Default: `['post','page']`.
	 * @param int                   $days            Optional. Number of days to detect recent content. Default: `30`.
	 * @param int                   $limit           Optional. Max number of opportunties. Default: `20`.
	 *
	 * @return void
	 */
	public function __construct(
		$experiment_repo,
		$post_types = array( 'post', 'page' ),
		$days = 30,
		$limit = 20
	) {
		$this->experiment_repo = $experiment_repo;
		$this->post_types      = $post_types;
		$this->days            = absint( $days );
		$this->limit           = absint( $limit );
	}

	// @Implements
	public function get_opportunities() {
		$query = new \WP_Query(
			array(
				'post_type'      => $this->post_types,
				'post_status'    => 'publish',
				'posts_per_page' => $this->limit,
				'date_query'     => array(
					array(
						'after' => "{$this->days} days ago",
					),
				),
				'orderby'        => 'date',
				'order'          => 'DESC',
				'fields'         => 'ids',
			)
		);

		$opportunities = array();

		/** @var list<int> */
		$post_ids = $query->posts;
		foreach ( $post_ids as $post_id ) {
			$post_type = get_post_type( $post_id );

			if ( ! $post_type ) {
				continue;
			}

			if ( $this->experiment_repo->was_tested( $post_id, 'last-6-months' ) ) {
				continue;
			}

			$permalink = get_permalink( $post_id );
			if ( empty( $permalink ) ) {
				continue;
			}

			$publish_date = get_post_time( DATE_ATOM, false, $post_id );
			if ( ! is_string( $publish_date ) ) {
				continue;
			}

			$opportunities[] = new Opportunity(
				'recently-published',
				55,
				array(
					'type'     => 'post',
					'postType' => $post_type,
					'postId'   => $post_id,
					'title'    => get_the_title( $post_id ),
					'url'      => $permalink,
				),
				array(
					'publishDate' => $publish_date,
				)
			);
		}

		return $opportunities;
	}
}
