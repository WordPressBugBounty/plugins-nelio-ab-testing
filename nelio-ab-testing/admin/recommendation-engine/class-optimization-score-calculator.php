<?php

namespace Nelio_AB_Testing\Recommendation_Engine;

use Nelio_AB_Testing_Experiment;

final class Optimization_Score_Calculator {

	const MAX_SCORE = 100;

	/**
	 * Computes the site optimization score.
	 *
	 * The score rewards active experimentation, recent completed experiments,
	 * testing cadence, and tested-content coverage. Opportunities do not reduce
	 * the score unless there is no current/recent testing activity.
	 *
	 * @param list<Nelio_AB_Testing_Experiment> $running_experiments           Running experiments.
	 * @param list<Nelio_AB_Testing_Experiment> $recently_finished_experiments Recently finished experiments.
	 * @param list<Opportunity>                 $opportunities                 Available opportunities.
	 * @param int                               $tested_content_count          Number of tested posts/pages.
	 * @param int                               $testable_content_count        Number of testable posts/pages.
	 *
	 * @return TOptimization_Score
	 */
	public function compute(
		$running_experiments,
		$recently_finished_experiments,
		$opportunities,
		$tested_content_count,
		$testable_content_count
	) {
		$running  = $this->compute_running_score( count( $running_experiments ) );
		$recent   = $this->compute_recent_score( count( $recently_finished_experiments ) );
		$cadence  = $this->compute_cadence_score( $running_experiments, $recently_finished_experiments );
		$coverage = $this->compute_coverage_score( $tested_content_count, $testable_content_count );

		$score = $running + $recent + $cadence + $coverage;

		$penalty = $this->compute_inactivity_penalty(
			$running_experiments,
			$recently_finished_experiments,
			$opportunities
		);

		/** @var int $score */
		$score = max( 0, min( self::MAX_SCORE, $score - $penalty ) );

		return array(
			'value'     => $score,
			'breakdown' => array(
				'running'           => $running,
				'recentlyFinished'  => $recent,
				'cadence'           => $cadence,
				'coverage'          => $coverage,
				'inactivityPenalty' => $penalty,
			),
		);
	}

	/**
	 * Computes the score for currently running experiments.
	 *
	 * @param int $count Number of running experiments.
	 *
	 * @return int
	 */
	private function compute_running_score( $count ) {
		if ( $count <= 0 ) {
			return 0;
		}

		if ( 1 === $count ) {
			return 20;
		}

		if ( 2 === $count ) {
			return 30;
		}

		return 35;
	}

	/**
	 * Computes the score for recently finished experiments.
	 *
	 * @param int $count Number of recently finished experiments.
	 *
	 * @return int
	 */
	private function compute_recent_score( $count ) {
		if ( $count <= 0 ) {
			return 0;
		}

		if ( 1 === $count ) {
			return 10;
		}

		if ( 2 === $count ) {
			return 18;
		}

		return 25;
	}

	/**
	 * Computes the testing cadence score.
	 *
	 * Running experiments get full cadence credit. Otherwise, the score depends
	 * on how recently the latest finished experiment ended.
	 *
	 * @param list<Nelio_AB_Testing_Experiment> $running_experiments           Running experiments.
	 * @param list<Nelio_AB_Testing_Experiment> $recently_finished_experiments Recently finished experiments.
	 *
	 * @return int
	 */
	private function compute_cadence_score( $running_experiments, $recently_finished_experiments ) {
		if ( ! empty( $running_experiments ) ) {
			return 25;
		}

		$last_end = $this->get_latest_end_timestamp( $recently_finished_experiments );

		if ( ! $last_end ) {
			return 0;
		}

		$days = ( time() - $last_end ) / DAY_IN_SECONDS;

		if ( $days <= 14 ) {
			return 25;
		}

		if ( $days <= 30 ) {
			return 18;
		}

		if ( $days <= 90 ) {
			return 8;
		}

		return 0;
	}

	/**
	 * Computes tested-content coverage score.
	 *
	 * @param int $tested_content_count   Number of tested posts/pages.
	 * @param int $testable_content_count Number of testable posts/pages.
	 *
	 * @return int
	 */
	private function compute_coverage_score( $tested_content_count, $testable_content_count ) {
		if ( $testable_content_count <= 0 ) {
			return 0;
		}

		$ratio = max( 0, min( 1, $tested_content_count / $testable_content_count ) );

		return (int) round( 15 * $ratio );
	}

	/**
	 * Computes a penalty when there are opportunities but no testing activity.
	 *
	 * @param list<Nelio_AB_Testing_Experiment> $running_experiments           Running experiments.
	 * @param list<Nelio_AB_Testing_Experiment> $recently_finished_experiments Recently finished experiments.
	 * @param list<Opportunity>                 $opportunities                 Available opportunities.
	 *
	 * @return int
	 */
	private function compute_inactivity_penalty( $running_experiments, $recently_finished_experiments, $opportunities ) {
		if ( ! empty( $running_experiments ) || ! empty( $recently_finished_experiments ) ) {
			return 0;
		}

		$high_priority = array_filter(
			$opportunities,
			function ( Opportunity $opportunity ) {
				return $opportunity->get_score() >= 70;
			}
		);

		return min( 30, 5 * count( $high_priority ) );
	}

	/**
	 * Returns the latest end timestamp among the given experiments.
	 *
	 * @param list<Nelio_AB_Testing_Experiment> $experiments Experiments.
	 *
	 * @return int|null
	 */
	private function get_latest_end_timestamp( $experiments ) {
		$latest = null;

		foreach ( $experiments as $experiment ) {
			$timestamp = $this->get_experiment_end_timestamp( $experiment );

			if ( ! $timestamp ) {
				continue;
			}

			$latest = max( $latest ? $latest : 0, $timestamp );
		}

		return $latest;
	}

	/**
	 * Returns the end timestamp of an experiment.
	 *
	 * @param Nelio_AB_Testing_Experiment $experiment Experiment.
	 *
	 * @return int|null
	 */
	private function get_experiment_end_timestamp( $experiment ) {
		$end_date = $experiment->get_end_date();

		if ( empty( $end_date ) ) {
			return null;
		}

		$timestamp = strtotime( $end_date );

		return $timestamp ? $timestamp : null;
	}
}
