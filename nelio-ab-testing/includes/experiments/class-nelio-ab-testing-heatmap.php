<?php
/**
 * This file defines the class of a heatmap test.
 *
 * @package    Nelio_AB_Testing
 * @subpackage Nelio_AB_Testing/includes/utils/experiments
 * @since      5.0.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * A Heatmap in Nelio A/B Testing.
 *
 * @package    Nelio_AB_Testing
 * @subpackage Nelio_AB_Testing/includes/utils/experiments
 * @since      5.0.0
 */
class Nelio_AB_Testing_Heatmap extends Nelio_AB_Testing_Experiment {

	/**
	 * What this experiment is tracking: a WordPress post (post_id + post_type) or a URL.
	 *
	 * @var 'post'|'url'
	 */
	private $tracking_mode;

	/**
	 * The post ID this experiment is tracking.
	 *
	 * @var integer
	 */
	private $tracked_post_id;

	/**
	 * The post type this experiment is tracking.
	 *
	 * @var string
	 */
	private $tracked_post_type;

	/**
	 * The URL this experiment is tracking.
	 *
	 * @var string
	 */
	private $tracked_url;

	/**
	 * The list of participation conditions.
	 *
	 * @var list<TSegmentation_Rule>
	 */
	private $participation_conditions;

	/**
	 * Creates a new instance of this class.
	 *
	 * @param integer|WP_Post $experiment The identifier of an experiment
	 *            in the database, or a WP_Post instance with it.
	 *
	 * @since  5.0.0
	 */
	protected function __construct( $experiment ) {
		parent::__construct( $experiment );

		/** @var 'post'|'url' */
		$value               = $this->get_meta( '_nab_tracking_mode', 'post' );
		$this->tracking_mode = $value;

		/** @var int */
		$value                 = $this->get_meta( '_nab_tracked_post_id', 0 );
		$this->tracked_post_id = $value;

		/** @var string */
		$value                   = $this->get_meta( '_nab_tracked_post_type', '' );
		$this->tracked_post_type = $value;

		/** @var string */
		$value             = $this->get_meta( '_nab_tracked_url', '' );
		$this->tracked_url = $value;

		/** @var list<TSegmentation_Rule> */
		$value                          = $this->get_meta( '_nab_participation_conditions', array() );
		$this->participation_conditions = $value;
	}

	/**
	 * Returns the tested element of this experiment. If the mode is set to “post,” it returns a post ID. Otherwise, a URL.
	 *
	 * @return integer|string the tested element of this experiment.
	 *
	 * @since  5.0.0
	 */
	public function get_tested_element() {

		if ( 'post' === $this->tracking_mode ) {
			return absint( $this->tracked_post_id );
		}

		return $this->tracked_url;
	}

	/**
	 * Returns the tracking mode of this heatmap.
	 *
	 * @return 'post'|'url' the tracking mode of this heatmap.
	 *
	 * @since  5.0.0
	 */
	public function get_tracking_mode() {
		return $this->tracking_mode;
	}

	/**
	 * Sets the tracking mode of this experiment to the given value.
	 *
	 * @param 'post'|'url' $tracking_mode A tracking mode.
	 *
	 * @return void
	 *
	 * @since  5.0.0
	 */
	public function set_tracking_mode( $tracking_mode ) {
		$this->tracking_mode = 'post' === $tracking_mode ? 'post' : 'url';
	}

	/**
	 * Returns the tracked post id of this heatmap.
	 *
	 * @return int the tracked post id of this heatmap.
	 *
	 * @since  5.0.0
	 */
	public function get_tracked_post_id() {
		return absint( $this->tracked_post_id );
	}

	/**
	 * Sets the tracked post id of this experiment to the given value.
	 *
	 * @param integer $tracked_post_id A tracked post id.
	 *
	 * @return void
	 *
	 * @since  5.0.0
	 */
	public function set_tracked_post_id( $tracked_post_id ) {
		$this->tracked_post_id = absint( $tracked_post_id );
	}

	/**
	 * Returns the tracked post type of this heatmap.
	 *
	 * @return string the tracked post type of this heatmap.
	 *
	 * @since  5.0.0
	 */
	public function get_tracked_post_type() {
		return $this->tracked_post_type;
	}

	/**
	 * Sets the tracked post type of this experiment to the given value.
	 *
	 * @param string $tracked_post_type A tracked post type.
	 *
	 * @return void
	 *
	 * @since  5.0.0
	 */
	public function set_tracked_post_type( $tracked_post_type ) {
		$this->tracked_post_type = $tracked_post_type;
	}

	/**
	 * Returns the tracked url of this heatmap.
	 *
	 * @return string the tracked url of this heatmap.
	 *
	 * @since  5.0.0
	 */
	public function get_tracked_url() {
		return $this->tracked_url;
	}

	/**
	 * Sets the tracked url of this experiment to the given value.
	 *
	 * @param string $tracked_url A tracked url.
	 *
	 * @return void
	 *
	 * @since  5.0.0
	 */
	public function set_tracked_url( $tracked_url ) {
		$this->tracked_url = $tracked_url;
	}

	/**
	 * Returns the list of participation conditions.
	 *
	 * Each condition is like a segmentation rule.
	 *
	 * @return list<TSegmentation_Rule> the list of participation conditions.
	 *
	 * @since  7.0.0
	 */
	public function get_participation_conditions() {
		return $this->participation_conditions;
	}

	/**
	 * Sets the list of participation conditions.
	 *
	 * @param list<TSegmentation_Rule> $conditions A list of segmentation rules.
	 *
	 * @return void
	 *
	 * @since  7.0.0
	 */
	public function set_participation_conditions( $conditions ) {
		$this->participation_conditions = $conditions;
	}

	/**
	 * Returns the preview url of this test.
	 *
	 * @return string the preview url of this test.
	 *
	 * @since  5.0.0
	 */
	public function get_preview_url() {

		$url = $this->get_tracked_url();
		if ( 'post' === $this->get_tracking_mode() ) {
			$url = get_permalink( $this->get_tracked_post_id() );
		}

		$experiment_id = $this->get_id();
		$preview_time  = time();
		$secret        = nab_get_api_secret();
		return add_query_arg(
			array(
				'nab-preview'         => true,
				'nab-heatmap-preview' => true,
				'experiment'          => $experiment_id,
				'alternative'         => 0,
				'timestamp'           => $preview_time,
				'nabnonce'            => md5( "nab-preview-{$experiment_id}-0-{$preview_time}-{$secret}" ),
			),
			$url
		);
	}

	/**
	 * Returns the heatmap url of this test.
	 *
	 * @return string the heatmap url of this test.
	 *
	 * @since  5.0.0
	 */
	public function get_heatmap_url() {

		$url = $this->get_tracked_url();
		if ( 'post' === $this->get_tracking_mode() ) {
			$url = get_permalink( $this->get_tracked_post_id() );
		}

		$experiment_id = $this->get_id();
		$preview_time  = time();
		$secret        = nab_get_api_secret();
		return add_query_arg(
			array(
				'nab-preview'          => true,
				'nab-heatmap-renderer' => true,
				'experiment'           => $experiment_id,
				'alternative'          => 0,
				'timestamp'            => $preview_time,
				'nabnonce'             => md5( "nab-preview-{$experiment_id}-0-{$preview_time}-{$secret}" ),
			),
			$url
		);
	}

	// @Overrides
	public function duplicate() {

		/** @var Nelio_AB_Testing_Heatmap */
		$new_heatmap = parent::duplicate();

		$new_heatmap->set_tracking_mode( $this->get_tracking_mode() );
		$new_heatmap->set_tracked_post_id( $this->get_tracked_post_id() );
		$new_heatmap->set_tracked_post_type( $this->get_tracked_post_type() );
		$new_heatmap->set_tracked_url( $this->get_tracked_url() );

		$new_heatmap->save();

		return $new_heatmap;
	}

	// @Overrides
	public function save() {

		$this->set_meta( '_nab_tracking_mode', $this->tracking_mode );
		$this->set_meta( '_nab_tracked_post_id', $this->tracked_post_id );
		$this->set_meta( '_nab_tracked_post_type', $this->tracked_post_type );
		$this->set_meta( '_nab_tracked_url', $this->tracked_url );
		$this->set_meta( '_nab_participation_conditions', $this->participation_conditions );

		parent::save();

		delete_post_meta( $this->ID, '_nab_alternatives' );
		delete_post_meta( $this->ID, '_nab_goals' );
		delete_post_meta( $this->ID, '_nab_scope' );

		delete_post_meta( $this->ID, '_nab_control_backup' );
		delete_post_meta( $this->ID, '_nab_last_alternative_applied' );
	}

	public function get_alternatives( $mode = 'full' ) {
		// Heatmaps don’t have any alternatives, so...
		return array();
	}

	// @Overrides
	public function get_goals() {
		// Heatmaps don’t have any goals, so...
		return array();
	}

	// @Overrides
	public function get_scope() {
		// Heatmaps don’t have a scope, so...
		return array();
	}
}
