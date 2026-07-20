<?php

namespace Nelio_AB_Testing\Recommendation_Engine;

use Nelio_AB_Testing_Experiment;

final class Opportunity implements \JsonSerializable {

	/**
	 * @var TOpportunity_Type
	 */
	private $type;

	/**
	 * @var int
	 */
	private $score;

	/**
	 * @var TOpportunity_Target
	 */
	private $target;

	/**
	 * @var TOpportunity_Meta
	 */
	private $meta;

	/**
	 * Creates a new instance of this class.
	 *
	 * @param TOpportunity_Type   $type   Type of opportunity.
	 * @param int                 $score  Score of the opportunity.
	 * @param TOpportunity_Target $target Tested post type.
	 * @param TOpportunity_Meta   $meta   Optional. Additional meta info.
	 *
	 * @template TType of TOpportunity_Type
	 * @phpstan-param TType $type
	 * @phpstan-param (
	 *       TType is 'key-page'                     ? TOpportunity_Key_Page_Meta
	 *     : TType is 'multiple-reasons'             ? TOpportunity_Multiple_Reasons_Meta
	 *     : TType is 'high-traffic'                 ? TOpportunity_Views_Meta
	 *     : TType is 'recently-finished-experiment' ? TOpportunity_Experiment_Meta
	 *     : TType is 'recently-published'           ? TOpportunity_Post_Meta
	 *     : TType is 'stale-experiment'             ? TOpportunity_Experiment_Meta
	 *     : never
	 * ) $meta
	 *
	 * @return void
	 */
	public function __construct( $type, $score, $target, $meta ) {
		$this->type   = $type;
		$this->score  = max( 0, min( 100, absint( $score ) ) );
		$this->target = $target;
		$this->meta   = $meta;
	}

	/**
	 * Returns a key that identifies this opportunity.
	 *
	 * @return string
	 */
	public function get_key() {
		$key = 'post' === $this->target['type']
		? "{$this->target['postType']}:{$this->target['postId']}"
		: "{$this->target['url']}:{$this->type}";

		return $key;
	}

	/**
	 * Returns the type.
	 *
	 * @return TOpportunity_Type
	 */
	public function get_type() {
		return $this->type;
	}

	/**
	 * Returns the score.
	 *
	 * @return int
	 */
	public function get_score() {
		return $this->score;
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
	 * Returns meta data.
	 *
	 * @return TOpportunity_Meta
	 */
	public function get_meta() {
		return $this->meta;
	}

	/**
	 * Serializes the object.
	 *
	 * @return array<string,mixed>
	 */
	#[\ReturnTypeWillChange]
	public function jsonSerialize() {
		/** @var array{opportunities?:list<Opportunity>} $meta */
		$meta = $this->meta;
		$meta = isset( $meta['opportunities'] )
			? array(
				'opportunities' => array_map(
					fn( $o ) => array(
						'type' => $o->get_type(),
						'meta' => $o->get_meta(),
					),
					$meta['opportunities']
				),
			)
			: $meta;

		return array(
			'type'   => $this->type,
			'score'  => $this->score,
			'target' => $this->target,
			'meta'   => $meta,
		);
	}

	/**
	 * Extracts experiment metas.
	 *
	 * @param Nelio_AB_Testing_Experiment $experiment Experiment.
	 *
	 * @return TOpportunity_Experiment_Meta
	 */
	public static function get_experiment_metas( $experiment ) {
		return array(
			'experiment' => array(
				'id'           => $experiment->get_id(),
				'name'         => $experiment->get_name(),
				'type'         => $experiment->get_type(),
				'description'  => $experiment->get_description(),
				'alternatives' => array_map(
					fn( $a ) => array(
						'id'         => $a['id'],
						'attributes' => $a['attributes'],
					),
					$experiment->get_alternatives()
				),
				'goals'        => array_map(
					fn( $g ) => array(
						'id'                => $g['id'],
						'attributes'        => $g['attributes'],
						'conversionActions' => $g['conversionActions'],
					),
					$experiment->get_goals()
				),
				'segments'     => array_map(
					fn( $s ) => array(
						'id'                => $s['id'],
						'attributes'        => $s['attributes'],
						'segmentationRules' => $s['segmentationRules'],
					),
					$experiment->get_segments()
				),
				'testedAt'     => $experiment->get_end_date(),
			),
		);
	}
}
