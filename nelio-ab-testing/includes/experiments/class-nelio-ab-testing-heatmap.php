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
	 * @var string
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
	 * @var integer
	 */
	private $tracked_post_type;

	/**
	 * The URL this experiment is tracking.
	 *
	 * @var integer
	 */
	private $tracked_url;

	/**
	 * The list of participation conditions.
	 *
	 * @var array
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

		$this->tracking_mode            = $this->get_meta( '_nab_tracking_mode', 'post' );
		$this->tracked_post_id          = $this->get_meta( '_nab_tracked_post_id', 0 );
		$this->tracked_post_type        = $this->get_meta( '_nab_tracked_post_type', '' );
		$this->tracked_url              = $this->get_meta( '_nab_tracked_url', '' );
		$this->participation_conditions = $this->get_meta( '_nab_participation_conditions', array() );
	}//end __construct()

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
		}//end if

		return $this->tracked_url;
	}//end get_tested_element()

	/**
	 * Returns the tracking mode of this heatmap.
	 *
	 * @return string the tracking mode of this heatmap.
	 *
	 * @since  5.0.0
	 */
	public function get_tracking_mode() {
		return $this->tracking_mode;
	}//end get_tracking_mode()

	/**
	 * Sets the tracking mode of this experiment to the given value.
	 *
	 * @param string $tracking_mode A tracking mode. Either `post` or `url`.
	 *
	 * @since  5.0.0
	 */
	public function set_tracking_mode( $tracking_mode ) {
		$this->tracking_mode = 'post' === $tracking_mode ? 'post' : 'url';
	}//end set_tracking_mode()

	/**
	 * Returns the tracked post id of this heatmap.
	 *
	 * @return string the tracked post id of this heatmap.
	 *
	 * @since  5.0.0
	 */
	public function get_tracked_post_id() {
		return absint( $this->tracked_post_id );
	}//end get_tracked_post_id()

	/**
	 * Sets the tracked post id of this experiment to the given value.
	 *
	 * @param integer $tracked_post_id A tracked post id.
	 *
	 * @since  5.0.0
	 */
	public function set_tracked_post_id( $tracked_post_id ) {
		$this->tracked_post_id = absint( $tracked_post_id );
	}//end set_tracked_post_id()

	/**
	 * Returns the tracked post type of this heatmap.
	 *
	 * @return string the tracked post type of this heatmap.
	 *
	 * @since  5.0.0
	 */
	public function get_tracked_post_type() {
		return $this->tracked_post_type;
	}//end get_tracked_post_type()

	/**
	 * Sets the tracked post type of this experiment to the given value.
	 *
	 * @param string $tracked_post_type A tracked post type.
	 *
	 * @since  5.0.0
	 */
	public function set_tracked_post_type( $tracked_post_type ) {
		$this->tracked_post_type = $tracked_post_type;
	}//end set_tracked_post_type()

	/**
	 * Returns the tracked url of this heatmap.
	 *
	 * @return string the tracked url of this heatmap.
	 *
	 * @since  5.0.0
	 */
	public function get_tracked_url() {
		return $this->tracked_url;
	}//end get_tracked_url()

	/**
	 * Sets the tracked url of this experiment to the given value.
	 *
	 * @param string $tracked_url A tracked url.
	 *
	 * @since  5.0.0
	 */
	public function set_tracked_url( $tracked_url ) {
		$this->tracked_url = $tracked_url;
	}//end set_tracked_url()

	/**
	 * Returns the list of participation conditions.
	 *
	 * Each condition is like a segmentation rule.
	 *
	 * @return array the list of participation conditions.
	 *
	 * @since  7.0.0
	 */
	public function get_participation_conditions() {
		return $this->participation_conditions;
	}//end get_participation_conditions()

	/**
	 * Sets the list of participation conditions.
	 *
	 * @param array $conditions A list of segmentation rules.
	 *
	 * @since  7.0.0
	 */
	public function set_participation_conditions( $conditions ) {
		$this->participation_conditions = $conditions;
	}//end set_participation_conditions()

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
		}//end if

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
	}//end get_preview_url()

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
		}//end if

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
	}//end get_heatmap_url()

	/** . @Overrides */
	public function duplicate() {

		/**
		 * .
		 *
		 * @var Nelio_AB_Testing_Heatmap $new_heatmap
		 */
		$new_heatmap = parent::duplicate();

		$new_heatmap->set_tracking_mode( $this->get_tracking_mode() );
		$new_heatmap->set_tracked_post_id( $this->get_tracked_post_id() );
		$new_heatmap->set_tracked_post_type( $this->get_tracked_post_type() );
		$new_heatmap->set_tracked_url( $this->get_tracked_url() );

		$new_heatmap->save();

		return $new_heatmap;
	}//end duplicate()

	/** . @Overrides */
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
	}//end save()

	public function get_alternatives( $mode = 'full' ) {
		// Heatmaps don’t have any alternatives, so...
		return array();
	}//end get_alternatives()

	/** . @Overrides */
	public function get_goals() {
		// Heatmaps don’t have any goals, so...
		return array();
	}//end get_goals()

	/** . @Overrides */
	public function get_scope() {
		// Heatmaps don’t have a scope, so...
		return array();
	}//end get_scope()
}//end class
