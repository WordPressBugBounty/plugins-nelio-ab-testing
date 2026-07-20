<?php

namespace Nelio_AB_Testing\Recommendation_Engine;

final class Tested_Content {

	/** @var TOpportunity_Target */
	private $target;

	/** @var int */
	private $experiment_id;

	/** @var string */
	private $last_tested_at;

	/**
	 * Creates a new instance of this class.
	 *
	 * @param TOpportunity_Target $target         Tested content target.
	 * @param int                 $experiment_id  Experiment ID.
	 * @param string              $last_tested_at Experiment end date.
	 *
	 * @return void
	 */
	public function __construct( $target, $experiment_id, $last_tested_at ) {
		$this->target         = $target;
		$this->experiment_id  = $experiment_id;
		$this->last_tested_at = $last_tested_at;
	}

	/**
	 * Returns the test content target.
	 *
	 * @return TOpportunity_Target
	 */
	public function get_target() {
		return $this->target;
	}

	/**
	 * Returns the experiment ID.
	 *
	 * @return int
	 */
	public function get_experiment_id() {
		return $this->experiment_id;
	}

	/**
	 * Returns the experiment end date.
	 *
	 * @return string
	 */
	public function get_last_tested_at() {
		return $this->last_tested_at;
	}
}
