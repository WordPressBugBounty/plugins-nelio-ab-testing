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

	protected static $instance;

	public static function instance() {

		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
		}//end if

		return self::$instance;
	}//end instance()

	public function init() {
		add_action( 'init', array( $this, 'add_wp_conversion_action_hooks' ) );
		add_action(
			'nab_public_init',
			function () {
				if ( nab_is_split_testing_disabled() ) {
					return;
				}//end if
				add_action( 'wp_footer', array( $this, 'print_inline_script_to_track_footer_views' ), 99 );
			}
		);
	}//end init()

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
					 * @param array $action        properties of the action.
					 * @param int   $experiment_id ID of the experiment that contains this action.
					 * @param int   $goal_index    index (in the goals array of an experiment) of the goal that contains this action.
					 * @param Goal  $goal          the goal.
					 *
					 * @since 5.0.0
					 * @since 5.1.0 Add goal.
					 */
					do_action( "nab_{$action_type}_add_hooks_for_tracking", $action['attributes'], $experiment->get_id(), $goal_index, $goal );

				}//end foreach
			}//end foreach
		}//end foreach
	}//end add_wp_conversion_action_hooks()

	public function print_inline_script_to_track_footer_views() {
		$experiments = $this->get_footer_views();
		if ( empty( $experiments ) ) {
			return;
		}//end if

		printf(
			'<script type="text/javascript">window.nabFooterViews=Object.freeze(%s);</script>',
			wp_json_encode( $experiments )
		);
	}//end print_inline_script_to_track_footer_views()

	private function should_experiment_trigger_footer_page_view( $experiment ) {

		$runtime         = Nelio_AB_Testing_Runtime::instance();
		$requested_alt   = $runtime->get_alternative_from_request();
		$experiment_type = $experiment->get_type();

		$tracking_location = $experiment->get_page_view_tracking_location();
		if ( 'footer' !== $tracking_location ) {
			return false;
		}//end if

		$control      = $experiment->get_alternative( 'control' );
		$alternatives = $experiment->get_alternatives();
		$alternative  = $alternatives[ $requested_alt % count( $alternatives ) ];

		$experiment_id  = $experiment->get_id();
		$alternative_id = $alternative['id'];

		/**
		 * Whether the given experiment should trigger a page view in the current page/alternative combination.
		 *
		 * @param boolean $should_trigger_page_view whether the given experiment should trigger a page view. Default: `false`.
		 * @param array   $alternative              the current alternative.
		 * @param array   $control                  original version.
		 * @param int     $experiment_id            id of the experiment.
		 * @param string  $alternative_id           id of the current alternative.
		 *
		 * @since 7.0.0
		 */
		return apply_filters( "nab_{$experiment_type}_should_trigger_footer_page_view", false, $alternative['attributes'], $control['attributes'], $experiment_id, $alternative_id );
	}//end should_experiment_trigger_footer_page_view()

	private function get_footer_views() {
		$runtime     = Nelio_AB_Testing_Runtime::instance();
		$experiments = $runtime->get_relevant_running_experiments();
		$experiments = array_filter(
			$experiments,
			function ( $experiment ) {
				return $this->should_experiment_trigger_footer_page_view( $experiment );
			}
		);
		return array_values( wp_list_pluck( $experiments, 'ID' ) );
	}//end get_footer_views()
}//end class
