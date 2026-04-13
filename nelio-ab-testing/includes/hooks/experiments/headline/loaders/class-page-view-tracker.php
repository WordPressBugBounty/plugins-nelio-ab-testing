<?php

namespace Nelio_AB_Testing\Experiment_Library\Headline_Experiment;

defined( 'ABSPATH' ) || exit;

use function add_filter;

/**
 * Class responsible for tracking which experiments have been seen and, therefore, require page view tracking.
 *
 * @extends \Nelio_AB_Testing_Alternative_Loader<THeadline_Control_Attributes,THeadline_Alternative_Attributes>
 */
class Page_View_Tracker extends \Nelio_AB_Testing_Alternative_Loader {

	/** @var bool */
	private $did_experiment_show_alternative_content = false;

	public function init() {
		add_filter( 'the_title', array( $this, 'maybe_mark_experiment_as_shown' ), 10, 2 );
		add_filter( 'nab_nab/headline_should_trigger_footer_page_view', array( $this, 'should_footer_page_view_be_triggered' ), 10, 2 );
	}

	/**
	 * Callback to add this experiment as a tracked experiment.
	 *
	 * @param string $title   Title.
	 * @param int    $post_id Post ID.
	 *
	 * @return string
	 */
	public function maybe_mark_experiment_as_shown( $title, $post_id ) {
		if ( $post_id === $this->control['postId'] ) {
			$this->did_experiment_show_alternative_content = true;
		}
		return $title;
	}

	/**
	 * Callback to determine whether page view should be triggered in the footer or not.
	 *
	 * @param bool $result        Result.
	 * @param int  $experiment_id Experiment ID.
	 *
	 * @return bool
	 */
	public function should_footer_page_view_be_triggered( $result, $experiment_id ) {
		if ( $this->experiment_id !== $experiment_id ) {
			return $result;
		}
		if ( is_singular() && nab_get_queried_object_id() === $this->control['postId'] ) {
			return false;
		}
		return $this->did_experiment_show_alternative_content;
	}
}
