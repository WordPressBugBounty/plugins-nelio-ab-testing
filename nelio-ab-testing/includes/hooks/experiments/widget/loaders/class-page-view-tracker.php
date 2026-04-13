<?php

namespace Nelio_AB_Testing\Experiment_Library\Widget_Experiment;

defined( 'ABSPATH' ) || exit;

use function add_action;
use function add_filter;

/**
 * Class responsible for tracking which experiments have been seen and, therefore, require page view tracking.
 *
 * @extends \Nelio_AB_Testing_Alternative_Loader<TWidget_Control_Attributes,TWidget_Alternative_Attributes>
 */
class Page_View_Tracker extends \Nelio_AB_Testing_Alternative_Loader {

	/** @var bool */
	private $did_experiment_show_alternative_content = false;

	public function init() {
		add_action( 'dynamic_sidebar_after', array( $this, 'maybe_mark_experiment_as_shown' ) );
		add_filter( 'nab_nab/menu_should_trigger_footer_page_view', array( $this, 'should_footer_page_view_be_triggered' ), 10, 2 );
	}

	/**
	 * Callback to add this experiment as a tracked experiment.
	 *
	 * @return void
	 */
	public function maybe_mark_experiment_as_shown() {
		$this->did_experiment_show_alternative_content = true;
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
		return $this->did_experiment_show_alternative_content;
	}
}
