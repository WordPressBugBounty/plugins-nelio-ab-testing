<?php
/**
 * Some helper functions to add tracking capabilities.
 *
 * @package    Nelio_AB_Testing
 * @subpackage Nelio_AB_Testing/public/helpers
 * @since      5.0.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * Some helper functions to add tracking capabilities.
 */
class Nelio_AB_Testing_Tracking {

	/**
	 * This instance.
	 *
	 * @var Nelio_AB_Testing_Tracking|null
	 */
	protected static $instance;

	/**
	 * Returns this instance.
	 *
	 * @return Nelio_AB_Testing_Tracking
	 */
	public static function instance() {

		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Hooks into WordPress.
	 *
	 * @return void
	 */
	public function init() {
		add_action( 'init', array( $this, 'add_wp_conversion_action_hooks' ) );
		add_action( 'wp_footer', array( $this, 'maybe_print_inline_script_to_track_footer_views' ), 99 );
	}

	/**
	 * Callback to define conversion action hooks.
	 *
	 * @return void
	 */
	public function add_wp_conversion_action_hooks() {

		$experiments = nab_get_running_experiments();

		foreach ( $experiments as $experiment ) {

			$goals = $experiment->get_goals();
			foreach ( $goals as $goal_index => $goal ) {

				$actions = $goal['conversionActions'];
				foreach ( $actions as $action ) {

					$action_type = $action['type'];

					/**
					 * Fires for each conversion action in a running experiment.
					 *
					 * Use it to add any hooks required by your conversion action. Action
					 * called during WordPress’ `init` action.
					 *
					 * @param TAttributes $action        Properties of the action.
					 * @param int         $experiment_id ID of the experiment that contains this action.
					 * @param int         $goal_index    Index (in the goals array of an experiment) of the goal that contains this action.
					 * @param TGoal       $goal          The goal.
					 *
					 * @since 5.0.0
					 * @since 5.1.0 Add goal.
					 */
					do_action( "nab_{$action_type}_add_hooks_for_tracking", $action['attributes'], $experiment->get_id(), $goal_index, $goal );

				}
			}
		}
	}

	/**
	 * Callback to print inline script to track footer views.
	 *
	 * @return void
	 */
	public function maybe_print_inline_script_to_track_footer_views() {
		if ( nab_is_split_testing_disabled() ) {
			return;
		}

		$experiments = $this->get_footer_views();
		if ( empty( $experiments ) ) {
			return;
		}

		printf(
			'<script type="text/javascript">window.nabFooterViews=Object.freeze(%s);</script>',
			wp_json_encode( $experiments )
		);
	}

	/**
	 * Returns the list of experiment IDs that should trigger page views in the footer.
	 *
	 * @return list<int> List of experiment IDs.
	 */
	private function get_footer_views() {
		$runtime     = Nelio_AB_Testing_Runtime::instance();
		$experiments = $runtime->get_relevant_running_experiments();
		$experiments = array_filter(
			$experiments,
			function ( $experiment ) {
				return $this->should_experiment_trigger_footer_page_view( $experiment );
			}
		);
		$experiments = array_values( $experiments );
		/** @var list<int> */
		return wp_list_pluck( $experiments, 'ID' );
	}

	/**
	 * Returns whether the given experiment triggers page views in the footer or not.
	 *
	 * @param Nelio_AB_Testing_Experiment $experiment An experiment.
	 *
	 * @return boolean whether the given experiment triggers page views in the footer or not.
	 */
	private function should_experiment_trigger_footer_page_view( $experiment ) {

		$tracking_location = $experiment->get_page_view_tracking_location();
		if ( 'footer' !== $tracking_location ) {
			return false;
		}

		$experiment_type = $experiment->get_type();
		$control         = $experiment->get_alternative( 'control' );
		$alternatives    = $experiment->get_alternatives();
		$alternative     = nab_get_requested_alternative();
		$alternative     = $alternatives[ $alternative % count( $alternatives ) ];

		$experiment_id  = $experiment->get_id();
		$alternative_id = $alternative['id'];

		/**
		 * Whether the given experiment should trigger a page view in the current page/alternative combination.
		 *
		 * @param boolean                                       $should_trigger_page_view Whether the given experiment should trigger a page view. Default: `false`.
		 * @param TAlternative_Attributes|TControl_Attributes   $alternative              The current alternative.
		 * @param TControl_Attributes                           $control                  Original version.
		 * @param int                                           $experiment_id            Id of the experiment.
		 * @param string                                        $alternative_id           Id of the current alternative.
		 *
		 * @since 7.0.0
		 */
		return apply_filters( "nab_{$experiment_type}_should_trigger_footer_page_view", false, $alternative['attributes'], $control['attributes'], $experiment_id, $alternative_id );
	}
}
