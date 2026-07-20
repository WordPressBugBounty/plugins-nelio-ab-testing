<?php

namespace Nelio_AB_Testing\Recommendation_Engine;

use Nelio_AB_Testing_Experiment;
use Nelio_AB_Testing\Recommendation_Engine\Providers\Opportunity_Provider;

final class Engine {

	/**
	 * @var list<Opportunity_Provider>
	 */
	private $providers;

	/**
	 * @var Experiment_Repository
	 */
	private $experiment_repo;

	/**
	 * Creates a new instance of this class.
	 *
	 * @param list<Opportunity_Provider> $providers       List of opportunity providers.
	 * @param Experiment_Repository      $experiment_repo Experiment repository.
	 *
	 * @return void
	 */
	public function __construct( $providers, $experiment_repo ) {
		$this->providers       = $providers;
		$this->experiment_repo = $experiment_repo;
	}

	/**
	 * Returns a list of opportunities.
	 *
	 * @return list<Opportunity>
	 */
	public function get_opportunities() {
		/** @var array<string,Opportunity> */
		$opportunities = array();

		foreach ( $this->providers as $provider ) {
			foreach ( $provider->get_opportunities() as $opportunity ) {
				if ( $this->is_being_tested( $opportunity ) ) {
					continue;
				}
				if ( $this->has_pending_test( $opportunity ) ) {
					continue;
				}
				$opportunities[ $opportunity->get_key() ] = $this->merge(
					$opportunities[ $opportunity->get_key() ] ?? null,
					$opportunity
				);
			}
		}

		$opportunities = array_values( $opportunities );

		usort(
			$opportunities,
			fn( $a, $b ) => $b->get_score() <=> $a->get_score()
		);

		return $opportunities;
	}

	/**
	 * Whether the opportunity is related to a content that’s already being tested.
	 *
	 * @param Opportunity $opportunity $opportunity.
	 *
	 * @return boolean
	 */
	private function is_being_tested( $opportunity ) {
		$target = $opportunity->get_target();
		if ( 'post' !== $target['type'] ) {
			return false;
		}

		$experiments = nab_get_running_experiments();
		return array_reduce(
			$experiments,
			fn( $tested, $experiment ) => $tested || $experiment->get_tested_post() === $target['postId'] || $this->does_scope_match( $experiment, $target['url'] ),
			false
		);
	}

	/**
	 * Whether the opportunity is related to a content that has a draft/ready test.
	 *
	 * @param Opportunity $opportunity $opportunity.
	 *
	 * @return boolean
	 */
	private function has_pending_test( $opportunity ) {
		$target = $opportunity->get_target();
		if ( 'post' !== $target['type'] ) {
			return false;
		}

		$experiments = $this->experiment_repo->get_recent_drafts();
		return array_reduce(
			$experiments,
			fn( $tested, $experiment ) => $tested || $experiment->get_tested_post() === $target['postId'] || $this->does_scope_match( $experiment, $target['url'] ),
			false
		);
	}

	/**
	 * Merges two opportunities targeting the same content.
	 *
	 * If either opportunity already represents multiple reasons, its underlying
	 * opportunities are flattened before merging. The resulting opportunity
	 * aggregates all the reasons why the target content should be tested.
	 *
	 * @param Opportunity|null $a First opportunity.
	 * @param Opportunity      $b Second opportunity.
	 *
	 * @return Opportunity
	 */
	private function merge( $a, $b ) {
		if ( empty( $a ) ) {
			return $b;
		}

		$opportunities = array_merge(
			$this->flatten( $a ),
			$this->flatten( $b )
		);

		return new Opportunity(
			'multiple-reasons',
			$this->compute_score( $opportunities ),
			$a->get_target(),
			array( 'opportunities' => $opportunities )
		);
	}

	/**
	 * Returns the underlying opportunities represented by the given opportunity.
	 *
	 * If the opportunity represents multiple reasons, its constituent
	 * opportunities are returned. Otherwise, the opportunity itself is returned
	 * as the sole element of the list.
	 *
	 * @param Opportunity $opportunity Opportunity to flatten.
	 *
	 * @return list<Opportunity>
	 */
	private function flatten( $opportunity ) {
		if ( 'multiple-reasons' !== $opportunity->get_type() ) {
			return array( $opportunity );
		}

		/** @var array{opportunities?:list<Opportunity>} */
		$meta = $opportunity->get_meta();
		return isset( $meta['opportunities'] ) ? $meta['opportunities'] : array();
	}

	/**
	 * Computes the score of a merged opportunity.
	 *
	 * The resulting score is the highest score among the constituent
	 * opportunities, plus a small bonus for each additional reason, capped at
	 * 100.
	 *
	 * @param list<Opportunity> $opportunities Opportunities being merged.
	 *
	 * @return int
	 */
	private function compute_score( $opportunities ) {
		if ( empty( $opportunities ) ) {
			return 0;
		}

		$scores = array_map(
			fn ( $opportunity ) => $opportunity->get_score(),
			$opportunities
		);

		return min(
			100,
			max( $scores ) + 10 * ( count( $scores ) - 1 )
		);
	}

	/**
	 * Whether the experiment’s scope matches the URL or not.
	 *
	 * @param \Nelio_AB_Testing_Experiment $experiment Experiment.
	 * @param string                       $url        URL.
	 *
	 * @return boolean
	 */
	private function does_scope_match( $experiment, $url ) {
		$scope = $experiment->get_scope();
		$rule  = $scope[0] ?? null;
		if ( count( $scope ) !== 1 || empty( $rule ) ) {
			return false;
		}

		return 'exact' === $rule['attributes']['type'] && $url === $rule['attributes']['value'];
	}
}
