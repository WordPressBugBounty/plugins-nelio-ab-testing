<?php
/**
 * This file defines the class of a Nelio A/B Testing Experiment.
 *
 * @package    Nelio_AB_Testing
 * @subpackage Nelio_AB_Testing/includes/utils/experiments
 * @since      5.0.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * An Experiment in Nelio A/B Testing.
 *
 * @package    Nelio_AB_Testing
 * @subpackage Nelio_AB_Testing/includes/utils/experiments
 * @since      5.0.0
 */
class Nelio_AB_Testing_Experiment {

	/**
	 * The experiment (post) ID.
	 *
	 * @var int
	 */
	public $ID = 0;

	/**
	 * Stores post data.
	 *
	 * @var WP_Post
	 */
	public $post = null;

	/**
	 * Stores the experiment type.
	 *
	 * @var string
	 */
	private $type = null;

	/**
	 * The UTC date in which the experiment started (or will start, if it's scheduled).
	 *
	 * @var bool|string
	 */
	private $start_date = false;

	/**
	 * The UTC date in which the experiment ended.
	 *
	 * @var bool|string
	 */
	private $end_date = false;

	/**
	 * How the experiment should end.
	 *
	 * @var string
	 */
	private $end_mode = 'manual';

	/**
	 * The starter of the experiment.
	 *
	 * @var bool|string|integer
	 */
	private $starter = false;

	/**
	 * The stopper of the experiment.
	 *
	 * @var bool|string|integer
	 */
	private $stopper = false;

	/**
	 * If the end mode is other than manual, this value specifies the concrete
	 * value at which the experiment should end.
	 *
	 * @var array
	 */
	private $end_value = 0;

	/**
	 * List of alternatives.
	 *
	 * @var array
	 */
	private $alternatives = array();

	/**
	 * List of goals.
	 *
	 * @var array
	 */
	private $goals = array();

	/**
	 * Whether goals in this test have already been sanitized or not.
	 *
	 * @var bool
	 */
	private $are_goals_sanitized = false;

	/**
	 * List of segments.
	 *
	 * @var array
	 */
	private $segments = array();

	/**
	 * List of pairs type/value with the URLs where the test should run.
	 *
	 * @var array
	 */
	private $scope = array();

	/**
	 * Backup of the control version.
	 *
	 * @var array
	 */
	private $control_backup = false;

	/**
	 * Alternative applied (if any).
	 *
	 * @var array
	 */
	private $last_alternative_applied = false;

	/**
	 * Segment evaluation (either “site” or “tested-page”).
	 *
	 * @var string
	 */
	private $segment_evaluation = 'tested-page';

	/**
	 * Whether the winning alternative should be auto-applied on test stop or not.
	 *
	 * @var string
	 */
	private $auto_alternative_application = false;

	/**
	 * Experiment version.
	 *
	 * @var string
	 */
	private $version = '0.0.0';

	/**
	 * Creates a new instance of this class.
	 *
	 * @param integer|WP_Post $experiment The identifier of an experiment
	 *            in the database, or a WP_Post instance with it.
	 *
	 * @since  5.0.0
	 */
	protected function __construct( $experiment ) {

		if ( is_numeric( $experiment ) ) {
			$experiment = get_post( $experiment );
		}//end if

		if ( isset( $experiment->ID ) ) {

			$this->ID   = absint( $experiment->ID );
			$this->post = $experiment;
			$this->type = $this->get_meta( '_nab_experiment_type' );

			$this->start_date = $this->get_meta( '_nab_start_date', false );
			$this->end_date   = $this->get_meta( '_nab_end_date', false );
			$this->end_mode   = $this->get_meta( '_nab_end_mode' );
			$this->end_value  = $this->get_meta( '_nab_end_value' );
			$this->set_segment_evaluation( $this->get_meta( '_nab_segment_evaluation' ) );

			$this->auto_alternative_application = ! empty( $this->get_meta( '_nab_auto_alternative_application' ) );

			$this->alternatives = $this->get_meta( '_nab_alternatives' );
			$this->set_goals( $this->get_meta( '_nab_goals' ) );
			$this->set_segments( $this->get_meta( '_nab_segments' ) );
			$this->set_scope( $this->get_meta( '_nab_scope' ) );

			$this->starter = $this->get_meta( '_nab_starter', false );
			$this->stopper = $this->get_meta( '_nab_stopper', false );

			$this->control_backup = $this->get_meta( '_nab_control_backup', false );

			$this->last_alternative_applied = $this->get_meta( '_nab_last_alternative_applied', false );

			$this->version = $this->get_meta( '_nab_version', '0.0.0' );

			$this->are_goals_sanitized = false;

		}//end if

		if ( empty( $this->end_mode ) ) {
			$this->end_mode  = 'manual';
			$this->end_value = 0;
		}//end if

		$this->end_value = absint( $this->end_value );
	}//end __construct()

	public static function get_experiment( $experiment ) {

		if ( is_numeric( $experiment ) ) {
			$experiment_id = $experiment;
		} elseif ( isset( $experiment->ID ) ) {
			$experiment_id = absint( $experiment->ID );
		}//end if

		if ( ! $experiment_id ) {
			return new WP_Error( 'experiment-id-not-found', _x( 'Test not found.', 'text', 'nelio-ab-testing' ) );
		}//end if

		$experiment = get_post( $experiment_id );
		if ( empty( $experiment ) ) {
			return new WP_Error( 'experiment-id-not-found', _x( 'Test not found.', 'text', 'nelio-ab-testing' ) );
		}//end if

		if ( 'nab_experiment' !== $experiment->post_type ) {
			return new WP_Error( 'invalid-experiment', _x( 'Invalid test.', 'text', 'nelio-ab-testing' ) );
		}//end if

		$experiment_type = get_post_meta( $experiment->ID, '_nab_experiment_type', true );
		if ( empty( $experiment_type ) ) {
			return new WP_Error( 'invalid-experiment', _x( 'Invalid test.', 'text', 'nelio-ab-testing' ) );
		}//end if

		if ( 'nab/heatmap' === $experiment_type ) {
			return new Nelio_AB_Testing_Heatmap( $experiment );
		}//end if

		return new Nelio_AB_Testing_Experiment( $experiment );
	}//end get_experiment()

	/**
	 * Creates a new experiment of the given type and returns it.
	 *
	 * @param string $experiment_type the experiment type.
	 *
	 * @return Nelio_AB_Testing_Experiment|WP_Error Experiment object or an error
	 *            if the experiment couldn't be created.
	 *
	 * @since  5.0.0
	 */
	public static function create_experiment( $experiment_type ) {

		$post_id = wp_insert_post(
			array(
				'post_type'   => 'nab_experiment',
				'post_status' => 'draft',
			)
		);

		if ( is_wp_error( $post_id ) ) {
			return $post_id;
		}//end if

		update_post_meta( $post_id, '_nab_experiment_type', $experiment_type );
		update_post_meta( $post_id, '_nab_version', nelioab()->plugin_version );

		return self::get_experiment( $post_id );
	}//end create_experiment()

	/**
	 * Returns the ID of this experiment.
	 *
	 * @return integer the ID of this experiment.
	 *
	 * @since  5.0.0
	 */
	public function get_id() {
		return $this->ID;
	}//end get_id()

	/**
	 * Returns the version of this experiment.
	 *
	 * @return string the version of this experiment.
	 *
	 * @since  7.3.0
	 */
	public function get_version() {
		return $this->version;
	}//end get_version()

	/**
	 * Returns the post ID tested in the control version of this test (if any). Otherwise, it returns `0`.
	 *
	 * @return number the post ID tested in the control version.
	 */
	public function get_tested_post() {
		return nab_array_get( $this->get_tested_posts(), '0', 0 );
	}//end get_tested_post()

	/**
	 * Returns the list of tested post IDs if this experiment tests one or more IDs.
	 *
	 * @return array list of tested post IDs.
	 */
	public function get_tested_posts() {
		$experiment_type = $this->get_type();

		/*
		 * Filters the list of tested post IDs.
		 *
		 * @param array                       $post_ids   list of tested post IDs.
		 * @param Nelio_AB_Testing_Experiment $experiment the experiment that tests these posts.
		 *
		 * @since 7.3.0
		 */
		return apply_filters( "nab_{$experiment_type}_get_tested_posts", array(), $this );
	}//end get_tested_posts()

	/**
	 * Returns the type of this experiment.
	 *
	 * @return string the type of this experiment.
	 *
	 * @since  5.0.0
	 */
	public function get_type() {
		return $this->type;
	}//end get_type()

	/**
	 * Sets the type of this experiment.
	 *
	 * Warning! In general, you should **not** change the type of an experiment,
	 * unless you have a really good reason to and you know what you’re doing.
	 *
	 * @param string $type the type of this experiment.
	 *
	 * @since  7.3.0
	 */
	public function set_type( $type ) {
		$this->type = $type;
	}//end set_type()

	/**
	 * Returns the WordPress post of this experiment.
	 *
	 * @return WP_Post the post of this experiment.
	 *
	 * @since  5.0.0
	 */
	public function get_post() {

		return $this->post;
	}//end get_post()

	/**
	 * Returns the name of this experiment.
	 *
	 * @return string the name of this experiment.
	 *
	 * @since  5.0.0
	 */
	public function get_name() {

		return $this->post->post_title;
	}//end get_name()

	/**
	 * Sets the name of this experiment.
	 *
	 * @param string $name the name of this experiment.
	 *
	 * @since  5.0.0
	 */
	public function set_name( $name ) {

		$this->post->post_title = $name;
	}//end set_name()

	/**
	 * Returns whether the experiment can be edited or not.
	 *
	 * @return bool whether the experiment can be edited or not.
	 *
	 * @since  5.0.0
	 */
	public function can_be_edited() {

		if ( ! current_user_can( 'edit_nab_experiments' ) ) {
			return new WP_Error(
				'missing-capability',
				__( 'Sorry, you are not allowed to do that.' ) // phpcs:ignore WordPress.WP.I18n.MissingArgDomain
			);
		}//end if

		return in_array( $this->post->post_status, array( 'draft', 'nab_ready', 'nab_scheduled', 'nab_paused', 'nab_paused_draft' ), true );
	}//end can_be_edited()

	/**
	 * Returns whether the experiment can be started or not.
	 *
	 * @param string $scope Optional. Either `ignore-scope-overlap` or `check-scope-overlap`. Default: `check-scope-overlap`.
	 *
	 * @return bool|WP_Error whether the experiment can be started or not. If it can't, it returns an error with the explanation.
	 *
	 * @since  5.0.0
	 */
	public function can_be_started( $scope = 'check-scope-overlap' ) {

		if ( ! current_user_can( 'start_nab_experiments' ) ) {
			return new WP_Error(
				'missing-capability',
				__( 'Sorry, you are not allowed to do that.' ) // phpcs:ignore WordPress.WP.I18n.MissingArgDomain
			);
		}//end if

		$helper = Nelio_AB_Testing_Experiment_Helper::instance();

		if ( 'running' === $this->get_status() ) {
			return new WP_Error(
				'experiment-already-running',
				sprintf(
					/* translators: 1 -> experiment name */
					_x( 'Test %1$s is already running.', 'text', 'nelio-ab-testing' ),
					$helper->get_non_empty_name( $this )
				)
			);
		}//end if

		if ( ! in_array( $this->get_status(), array( 'ready', 'scheduled' ), true ) ) {
			return new WP_Error(
				'experiment-cannot-be-started-due-to-invalid-status',
				sprintf(
					/* translators: 1 -> experiment name, 2 -> experiment status */
					_x( 'Test %1$s can’t be started because its status is “%2$s.”', 'text', 'nelio-ab-testing' ),
					$helper->get_non_empty_name( $this ),
					$this->get_status()
				)
			);
		}//end if

		if ( ! nab_is_subscribed() ) {
			if ( ! empty( nab_get_running_experiment_ids() ) || ! empty( nab_get_running_experiment_ids() ) ) {
				return new WP_Error(
					'experiments-already-running-in-free',
					esc_html_x( 'There’s another test currently running. Subscribe to Nelio A/B Testing Premium to run more than one test at a time.', 'user', 'nelio-ab-testing' )
				);
			}//end if
		}//end if

		if ( 'check-scope-overlap' === $scope ) {
			$running_experiment = nab_does_overlap_with_running_experiment( $this );
			if ( ! empty( $running_experiment ) ) {
				return new WP_Error(
					'equivalent-experiment-running',
					sprintf(
						/* translators: 1 -> one experiment name, 2 -> another experiment name */
						_x( 'Test %1$s can’t be started because there’s another running test that may be testing the same element(s): %2$s.', 'text', 'nelio-ab-testing' ),
						$helper->get_non_empty_name( $this ),
						$helper->get_non_empty_name( $running_experiment )
					)
				);
			}//end if
		}//end if

		return true;
	}//end can_be_started()

	/**
	 * Returns whether the experiment can be stopped or not.
	 *
	 * @return bool|WP_Error whether the experiment can be stopped or not. If it can't, it returns an error with the explanation.
	 *
	 * @since  6.0.1
	 */
	public function can_be_stopped() {
		if ( ! current_user_can( 'stop_nab_experiments' ) ) {
			return new WP_Error(
				'missing-capability',
				__( 'Sorry, you are not allowed to do that.' ) // phpcs:ignore WordPress.WP.I18n.MissingArgDomain
			);
		}//end if

		if ( ! in_array( $this->get_status(), array( 'running', 'paused' ), true ) ) {
			$helper = Nelio_AB_Testing_Experiment_Helper::instance();
			return new WP_Error(
				'experiment-cannot-be-stopped',
				sprintf(
					/* translators: experiment name */
					_x( 'Test %1$s can’t be stopped because it’s not running.', 'text', 'nelio-ab-testing' ),
					$helper->get_non_empty_name( $this )
				)
			);
		}//end if

		return true;
	}//end can_be_stopped()


	/**
	 * Returns whether the experiment can be paused or not.
	 *
	 * @return bool|WP_Error whether the experiment can be paused or not. If it can't, it returns an error with the explanation.
	 *
	 * @since  6.0.1
	 */
	public function can_be_paused() {
		if ( ! current_user_can( 'pause_nab_experiments' ) ) {
			return new WP_Error(
				'missing-capability',
				__( 'Sorry, you are not allowed to do that.' ) // phpcs:ignore WordPress.WP.I18n.MissingArgDomain
			);
		}//end if

		if ( ! in_array( $this->get_status(), array( 'running' ), true ) ) {
			$helper = Nelio_AB_Testing_Experiment_Helper::instance();
			return new WP_Error(
				'experiment-cannot-be-paused',
				sprintf(
					/* translators: experiment name */
					_x( 'Test %1$s can’t be paused because it’s not running.', 'text', 'nelio-ab-testing' ),
					$helper->get_non_empty_name( $this )
				)
			);
		}//end if

		return true;
	}//end can_be_paused()


	/**
	 * Returns whether the experiment can be restarted after it’s been stopped.
	 *
	 * @param string $scope Optional. Either `ignore-scope-overlap` or `check-scope-overlap`. Default: `check-scope-overlap`.
	 *
	 * @return bool|WP_Error whether the experiment can be restarted or not. If it can't, it returns an error with the explanation.
	 *
	 * @since  6.2.0
	 */
	public function can_be_restarted( $scope = 'check-scope-overlap' ) {
		if ( ! current_user_can( 'start_nab_experiments' ) ) {
			return new WP_Error(
				'missing-capability',
				__( 'Sorry, you are not allowed to do that.' ) // phpcs:ignore WordPress.WP.I18n.MissingArgDomain
			);
		}//end if

		if ( ! in_array( $this->get_status(), array( 'finished' ), true ) ) {
			$helper = Nelio_AB_Testing_Experiment_Helper::instance();
			return new WP_Error(
				'experiment-cannot-be-restarted',
				sprintf(
					/* translators: experiment name */
					_x( 'Test %1$s can’t be restarted because it’s not stopped.', 'text', 'nelio-ab-testing' ),
					$helper->get_non_empty_name( $this )
				)
			);
		}//end if

		/**
		 * Filters the number of hours after a test has been stopped that limit if it can or can’t be resumed.
		 *
		 * @param int $hours number of hours. Default: 24.
		 *
		 * @since 6.2.0
		 */
		$max_age = apply_filters( 'nab_max_hours_to_restart_test', 24 );
		$age     = floor( ( strtotime( 'now' ) - strtotime( $this->get_end_date() ) ) / 3600 );
		$too_old = $age >= $max_age;
		if ( $too_old ) {
			$helper = Nelio_AB_Testing_Experiment_Helper::instance();
			return new WP_Error(
				'experiment-cannot-be-restarted',
				sprintf(
					/* translators: experiment name */
					_x( 'Test %1$s can’t be restarted because it was stopped too long ago.', 'text', 'nelio-ab-testing' ),
					$helper->get_non_empty_name( $this )
				)
			);
		}//end if

		if ( 'check-scope-overlap' === $scope ) {
			$helper             = Nelio_AB_Testing_Experiment_Helper::instance();
			$running_experiment = nab_does_overlap_with_running_experiment( $this );
			if ( ! empty( $running_experiment ) ) {
				return new WP_Error(
					'equivalent-experiment-running',
					sprintf(
						/* translators: 1 -> one experiment name, 2 -> another experiment name */
						_x( 'Test %1$s can’t be restarted because there’s another running test that may be testing the same element(s): %2$s.', 'text', 'nelio-ab-testing' ),
						$helper->get_non_empty_name( $this ),
						$helper->get_non_empty_name( $running_experiment )
					)
				);
			}//end if
		}//end if

		return true;
	}//end can_be_restarted()


	/**
	 * Returns whether the experiment can be resumed or not.
	 *
	 * @param string $scope Optional. Either `ignore-scope-overlap` or `check-scope-overlap`. Default: `check-scope-overlap`.
	 *
	 * @return bool|WP_Error whether the experiment can be resumed or not. If it can't, it returns an error with the explanation.
	 *
	 * @since  5.0.0
	 */
	public function can_be_resumed( $scope = 'check-scope-overlap' ) {

		if ( ! current_user_can( 'resume_nab_experiments' ) ) {
			return new WP_Error(
				'missing-capability',
				__( 'Sorry, you are not allowed to do that.' ) // phpcs:ignore WordPress.WP.I18n.MissingArgDomain
			);
		}//end if

		$helper = Nelio_AB_Testing_Experiment_Helper::instance();

		if ( 'running' === $this->get_status() ) {
			return new WP_Error(
				'experiment-already-running',
				sprintf(
					/* translators: 1 -> experiment name */
					_x( 'Test %1$s is already running.', 'text', 'nelio-ab-testing' ),
					$helper->get_non_empty_name( $this )
				)
			);
		}//end if

		if ( 'paused' !== $this->get_status() ) {
			return new WP_Error(
				'experiment-cannot-be-resumed',
				sprintf(
					/* translators: 1 -> experiment name, 2 -> experiment status */
					_x( 'Test %1$s can’t be resumed because its status is “%2$s.”', 'text', 'nelio-ab-testing' ),
					$helper->get_non_empty_name( $this ),
					$this->get_status()
				)
			);
		}//end if

		if ( ! nab_is_subscribed() ) {
			if ( ! empty( nab_get_running_experiment_ids() ) || ! empty( nab_get_running_experiment_ids() ) ) {
				return new WP_Error(
					'experiments-already-running-in-free',
					esc_html_x( 'There’s another test currently running. Subscribe to Nelio A/B Testing Premium to run more than one test at a time.', 'user', 'nelio-ab-testing' )
				);
			}//end if
		}//end if

		if ( 'check-scope-overlap' === $scope ) {
			$running_experiment = nab_does_overlap_with_running_experiment( $this );
			if ( ! empty( $running_experiment ) ) {
				return new WP_Error(
					'equivalent-experiment-running',
					sprintf(
						/* translators: 1 -> one experiment name, 2 -> another experiment name */
						_x( 'Test %1$s can’t be resumed because there’s another running test that may be testing the same element(s): %2$s.', 'text', 'nelio-ab-testing' ),
						$helper->get_non_empty_name( $this ),
						$helper->get_non_empty_name( $running_experiment )
					)
				);
			}//end if
		}//end if

		return true;
	}//end can_be_resumed()

	/**
	 * Returns the date in which the experiment should be started/was started.
	 *
	 * @return bool|string the date in which the experiment should be started/was started.
	 *
	 * @since  5.0.0
	 */
	public function get_start_date() {

		return $this->start_date;
	}//end get_start_date()

	/**
	 * Sets the date in which the experiment should be started/was started.
	 *
	 * @param string $start_date the date in which the experiment should be started/was started.
	 *
	 * @since  5.0.0
	 */
	public function set_start_date( $start_date ) {

		$this->start_date = $start_date;
	}//end set_start_date()

	/**
	 * Returns the date in which the experiment ended.
	 *
	 * @return string the date in which the experiment ended.
	 *
	 * @since  5.0.0
	 */
	public function get_end_date() {

		return $this->end_date;
	}//end get_end_date()

	/**
	 * Sets the date in which the experiment ended.
	 *
	 * @param string $end_date the date in which the experiment ended.
	 *
	 * @since  5.0.0
	 */
	public function set_end_date( $end_date ) {

		$this->end_date = $end_date;
	}//end set_end_date()

	/**
	 * Returns the end mode.
	 *
	 * @return string the end mode.
	 *
	 * @since  5.0.0
	 */
	public function get_end_mode() {

		return $this->end_mode;
	}//end get_end_mode()

	/**
	 * Returns the end value, which depends on the end mode.
	 *
	 * For instance, if the end mode is set to "duration", the end value would be
	 * the number of days the experiment should run until it automatically stops.
	 *
	 * @return mixed the end value.
	 *
	 * @since  5.0.0
	 */
	public function get_end_value() {

		return $this->end_value;
	}//end get_end_value()

	/**
	 * Sets the ending properties of this experiment.
	 *
	 * @param string $end_mode  the end mode of this experiment (manual, duration, etc).
	 * @param mixed  $end_value the specific value at which the experiment should end
	 *                          when its mode is other than manual.
	 *
	 * @since  5.0.0
	 */
	public function set_end_mode_and_value( $end_mode, $end_value ) {

		$accepted_modes = array( 'manual', 'page-views', 'duration', 'confidence' );
		if ( ! in_array( $end_mode, $accepted_modes, true ) ) {
			$end_mode  = 'manual';
			$end_value = 0;
		}//end if

		$this->end_mode  = $end_mode;
		$this->end_value = $end_value;
	}//end set_end_mode_and_value()

	/**
	 * Returns whether the winning alternative should be auto-applied on test stop or not.
	 *
	 * @return boolean whether the winning alternative should be auto-applied on test stop or not.
	 *
	 * @since  7.3.0
	 */
	public function is_auto_alternative_application_enabled() {
		return $this->auto_alternative_application;
	}//end is_auto_alternative_application_enabled()

	/**
	 * Sets whether the winning alternative should be auto-applied on test stop or not.
	 *
	 * @param boolean $enabled whether the winning alternative should be auto-applied on test stop or not.
	 *
	 * @since  7.3.0
	 */
	public function set_auto_alternative_application( $enabled ) {
		$this->auto_alternative_application = $enabled;
	}//end set_auto_alternative_application()

	/**
	 * Gets the starter of the experiment.
	 *
	 * @return mixed The user id of the starter or 'system' if the starter is WordPress.
	 *
	 * @since  5.0.0
	 */
	public function get_starter() {
		return $this->starter;
	}//end get_starter()

	/**
	 * Gets the stopper of the experiment.
	 *
	 * @return mixed The user id of the stopper or 'system' if the stopper is WordPress.
	 *
	 * @since  5.0.0
	 */
	public function get_stopper() {
		return $this->stopper;
	}//end get_stopper()

	/**
	 * Sets the starter of the experiment.
	 *
	 * @param mixed $starter The user id of the starter or 'system' if the starter is WordPress.
	 *
	 * @since  5.0.0
	 */
	public function set_starter( $starter ) {
		$this->starter = $starter;
	}//end set_starter()

	/**
	 * Sets the stopper of the experiment.
	 *
	 * @param mixed $stopper The user id of the stopper or 'system' if the stopper is WordPress.
	 *
	 * @since  5.0.0
	 */
	public function set_stopper( $stopper ) {
		$this->stopper = $stopper;
	}//end set_stopper()

	/**
	 * Returns the description of this experiment.
	 *
	 * @return string the description of this experiment.
	 *
	 * @since  5.0.0
	 */
	public function get_description() {

		return $this->post->post_excerpt;
	}//end get_description()

	/**
	 * Sets the description of this experiment.
	 *
	 * @param string $description the description of this experiment.
	 *
	 * @since  5.0.0
	 */
	public function set_description( $description ) {

		$this->post->post_excerpt = $description;
	}//end set_description()

	/**
	 * Returns the alternatives.
	 *
	 * @param string $mode What info to include. “full” to include all info; “basic” not to.
	 *
	 * @return array the alternatives.
	 *
	 * @since  5.0.0
	 */
	public function get_alternatives( $mode = 'full' ) {

		$alternatives = $this->alternatives;
		if ( ! is_array( $alternatives ) ) {
			$alternatives = array(
				array(
					'id'         => 'control',
					'attributes' => array(),
				),
			);
		}//end if

		// Set default attributes.
		$experiment_type = $this->get_type();

		$control = $alternatives[0];

		/*
		 * Sanitizes control attributes.
		 *
		 * @param array                       $attributes current attributes.
		 * @param Nelio_AB_Testing_Experiment $experiment current attributes.
		 *
		 * @since 5.4.0
		 */
		$control['attributes'] = apply_filters( "nab_{$experiment_type}_sanitize_control_attributes", $control['attributes'], $this );

		$alternatives = array_values( array_slice( $alternatives, 1 ) );
		$alternatives = array_map(
			function ( $alternative ) use ( $experiment_type, $control ) {
				/**
				 * Sanitizes alternative attributes.
				 *
				 * @param array                       $attributes current attributes.
				 * @param array                       $control    control attributes.
				 * @param Nelio_AB_Testing_Experiment $experiment current attributes.
				 *
				 * @since 5.4.0
				 */
				$alternative['attributes'] = apply_filters( "nab_{$experiment_type}_sanitize_alternative_attributes", $alternative['attributes'], $control['attributes'], $this );
				return $alternative;
			},
			$alternatives
		);

		$alternatives             = array_merge( array( $control ), $alternatives );
		$last_alternative_applied = ! empty( $this->last_alternative_applied ) ? $this->last_alternative_applied : 'control';

		if ( 'basic' === $mode ) {
			return $alternatives;
		}//end if

		return array_map(
			function ( $alternative, $index ) use ( $control, $last_alternative_applied ) {
				$alternative['isLastApplied'] = $alternative['id'] === $last_alternative_applied;
				$alternative['links']         = array(
					'edit'    => $this->get_alternative_edit_link( $alternative, $control ),
					'heatmap' => $this->get_alternative_heatmap_link( $index, $alternative, $control ),
					'preview' => $this->get_alternative_preview_link( $index, $alternative, $control ),
				);
				return $alternative;
			},
			$alternatives,
			array_keys( $alternatives )
		);
	}//end get_alternatives()

	/**
	 * Returns the alternative.
	 *
	 * @param string $alternative_id the ID of the alternative.
	 *
	 * @return array|bool the alternative with the given ID or false.
	 *
	 * @since  5.0.0
	 */
	public function get_alternative( $alternative_id ) {

		if ( 'control_backup' === $alternative_id ) {
			return $this->control_backup;
		}//end if

		$alternatives = $this->get_alternatives();

		foreach ( $alternatives as $alternative ) {
			if ( $alternative_id === $alternative['id'] ) {
				return $alternative;
			}//end if
		}//end foreach

		return false;
	}//end get_alternative()

	/**
	 * Applies the alternative.
	 *
	 * @param string $alternative_id the ID of the alternative.
	 *
	 * @return bool|WP_Error whether the alternative has been applied or not.
	 *
	 * @since  5.0.0
	 */
	public function apply_alternative( $alternative_id ) {

		if ( 'control' === $alternative_id ) {
			$alternative_id = 'control_backup';
		}//end if

		$alternative = $this->get_alternative( $alternative_id );
		if ( ! $alternative ) {
			$helper = Nelio_AB_Testing_Experiment_Helper::instance();
			return new WP_Error(
				'alternative-not-found',
				sprintf(
					/* translators: 1 -> variant ID, 2 -> experiment name */
					_x( 'Variant %1$s not found in test %2$s.', 'text', 'nelio-ab-testing' ),
					$alternative_id,
					$helper->get_non_empty_name( $this )
				)
			);
		}//end if

		$control         = $this->get_alternative( 'control' );
		$experiment_type = $this->get_type();

		/**
		 * Filter to apply the given alternative.
		 *
		 * This filter is used to apply the given alternative. It returns `true` if the
		 * alternative was properly applied and `false` otherwise.
		 *
		 * @param bool $applied        whether the alternative was properly applied or not. Default: `false`.
		 * @param array   $alternative    alternative to apply.
		 * @param array   $control        original version.
		 * @param int     $experiment_id  id of the experiment.
		 * @param string  $alternative_id id of the alternative to apply.
		 *
		 * @since 5.0.0
		 */
		$was_alternative_applied = apply_filters( "nab_{$experiment_type}_apply_alternative", false, $alternative['attributes'], $control['attributes'], $this->ID, $alternative_id );

		if ( ! $was_alternative_applied ) {
			$helper = Nelio_AB_Testing_Experiment_Helper::instance();
			return new WP_Error(
				'alternative-could-not-be-applied',
				sprintf(
					/* translators: 1 -> variant ID, 2 -> experiment name */
					_x( 'Variant %1$s in test %2$s couldn’t be applied.', 'text', 'nelio-ab-testing' ),
					$alternative_id,
					$helper->get_non_empty_name( $this )
				)
			);
		}//end if

		if ( 'control_backup' === $alternative_id ) {
			$alternative_id = 'control';
		}//end if
		$this->last_alternative_applied = $alternative_id;
		$this->save();

		return $was_alternative_applied;
	}//end apply_alternative()

	/**
	 * Overwrites the given alternative in the list of alternatives of this experiment.
	 *
	 * @param array $alternative the alternative to overwrite.
	 *
	 * @since  5.0.0
	 */
	public function set_alternative( $alternative ) {

		$alternatives = $this->get_alternatives();

		foreach ( $alternatives as $pos => $existing_alternative ) {
			if ( $existing_alternative['id'] !== $alternative['id'] ) {
				continue;
			}//end if
			$alternatives[ $pos ] = $alternative;
		}//end foreach

		$this->set_alternatives( $alternatives );
	}//end set_alternative()

	/**
	 * Sets the alternative list to the given list.
	 *
	 * @param array $alternatives list of alternatives.
	 *
	 * @since  5.0.0
	 */
	public function set_alternatives( $alternatives ) {

		$experiment_type = $this->get_type();
		$alternatives    = $this->set_ids_as_keys( $alternatives );

		// Create alternatives that require duplication.
		foreach ( $alternatives as $key => $alt ) {
			if ( ! isset( $alt['base'] ) || 'control' === $alt['base'] ) {
				continue;
			}//end if
			$alternatives[ $key ]['attributes'] = $this->duplicate_alternative( $experiment_type, $alt['attributes'], $alt['attributes'], $this->ID, $alt['id'], $this->ID, $alt['base'] );
		}//end foreach

		// Remove old alternatives.
		$old_alternatives     = $this->set_ids_as_keys( $this->get_alternatives() );
		$removed_alternatives = array_diff_key( $old_alternatives, $alternatives );
		unset( $removed_alternatives['control'] );
		foreach ( $removed_alternatives as $alt ) {
			$this->remove_alternative_content( $alt );
		}//end foreach

		// Create new alternatives from scratch.
		foreach ( $alternatives as $key => $alt ) {
			if ( ! isset( $alt['base'] ) || 'control' !== $alt['base'] ) {
				continue;
			}//end if

			/**
			 * This filter is triggered when a new alternative has been added to an experiment.
			 *
			 * Hook into this filter if you want to perform additional actions to create alternative
			 * content related to this new alternative. Add any extra options/fields in the new
			 * alternative.
			 *
			 * @param array   $new_alternative  current alternative.
			 * @param array   $control          original version.
			 * @param int     $experiment_id    id of the experiment.
			 * @param string  $alternative_id   id of the current alternative.
			 *
			 * @since 5.0.0
			 */
			$alternatives[ $key ]['attributes'] = apply_filters( "nab_{$experiment_type}_create_alternative_content", $alt['attributes'], $alternatives['control']['attributes'], $this->ID, $key );
		}//end foreach

		$alternatives       = $this->set_keys_as_ids( $alternatives );
		$alternatives       = $this->clean_alternatives( $alternatives );
		$alternatives       = array_values( $alternatives );
		$this->alternatives = $alternatives;
	}//end set_alternatives()

	/**
	 * Returns the goals.
	 *
	 * @return array the goals.
	 *
	 * @since  5.0.0
	 */
	public function get_goals() {
		if ( ! $this->are_goals_sanitized ) {
			$this->goals               = $this->sanitize_goals( $this->goals );
			$this->are_goals_sanitized = true;
		}//end if
		return $this->goals;
	}//end get_goals()

	/**
	 * Sets the goal list to the given list.
	 *
	 * @param array $goals list of goals.
	 *
	 * @since  5.0.0
	 */
	public function set_goals( $goals ) {
		$this->are_goals_sanitized = false;
		if ( ! is_array( $goals ) ) {
			return;
		}//end if

		$this->goals = $goals;
	}//end set_goals()

	/**
	 * Returns the segments.
	 *
	 * @return array the segments.
	 *
	 * @since  5.0.0
	 */
	public function get_segments() {
		return is_array( $this->segments ) ? $this->segments : array();
	}//end get_segments()

	/**
	 * Sets the segment list to the given list.
	 *
	 * @param array $segments list of segments.
	 *
	 * @since  5.0.0
	 */
	public function set_segments( $segments ) {
		if ( ! is_array( $segments ) ) {
			return;
		}//end if
		$this->segments = $segments;
	}//end set_segments()

	/**
	 * Returns the segment evaluation strategy.
	 *
	 * @return string Segment evaluation strategy. Either `site` or `tested-page`.
	 *
	 * @since 6.5.0
	 */
	public function get_segment_evaluation() {
		return $this->segment_evaluation;
	}//end get_segment_evaluation()

	/**
	 * Sets the segment evaluation strategy.
	 *
	 * @param string $strategy Segment evaluation mode.
	 *
	 * @since 6.5.0
	 */
	public function set_segment_evaluation( $strategy ) {
		$this->segment_evaluation = in_array( $strategy, array( 'site', 'tested-page' ), true ) ? $strategy : 'tested-page';
	}//end set_segment_evaluation()

	/**
	 * Returns the scope.
	 *
	 * @return array scope.
	 *
	 * @since  5.0.0
	 */
	public function get_scope() {
		/**
		 * Sanitizes test scope.
		 *
		 * @param array                       $scope      the scope.
		 * @param Nelio_AB_Testing_Experiment $experiment experiment type.
		 *
		 * @since 7.3.0
		 */
		$this->scope = apply_filters( 'nab_sanitize_experiment_scope', $this->scope, $this );

		return $this->scope;
	}//end get_scope()

	/**
	 * Sets the scope of this experiment.
	 *
	 * @param array $scope list of alternatives.
	 *
	 * @since  5.0.0
	 */
	public function set_scope( $scope ) {
		if ( ! is_array( $scope ) ) {
			$scope = array();
		}//end if

		$this->scope = $scope;
	}//end set_scope()

	/**
	 * Saves this experiment to the database.
	 *
	 * @since  5.0.0
	 */
	public function save() {

		if ( doing_action( 'nab_pre_save_experiment' ) ) {
			wp_die( 'You can’t save an experiment during the “nab_pre_save_experiment” hook.' );
			return;
		}//end if

		/**
		 * Fires before the experiment is saved.
		 *
		 * @param Nelio_AB_Testing_Experiment $experiment the experiment to save.
		 *
		 * @since 7.3.0
		 */
		do_action( 'nab_pre_save_experiment', $this );

		$post_id = wp_update_post( $this->post );
		if ( is_wp_error( $post_id ) ) {
			return;
		}//end if

		$this->ID = $post_id;

		$this->set_meta( '_nab_start_date', $this->start_date );
		$this->set_meta( '_nab_end_date', $this->end_date );
		$this->set_meta( '_nab_end_mode', $this->end_mode );
		$this->set_meta( '_nab_end_value', $this->end_value );

		$this->set_meta(
			'_nab_auto_alternative_application',
			$this->is_auto_alternative_application_enabled() ? $this->auto_alternative_application : false
		);

		$alternatives = $this->get_alternatives();
		$alternatives = $this->remove_dynamic_properties( $alternatives );
		$this->set_meta( '_nab_alternatives', $alternatives );

		$goals = $this->get_serializable_goals();
		$this->set_meta( '_nab_goals', $goals );

		$segments = $this->get_segments();
		$this->set_meta( '_nab_segments', $segments );

		$strategy = $this->get_segment_evaluation();
		$this->set_meta(
			'_nab_segment_evaluation',
			'tested-page' !== $strategy ? $strategy : false
		);

		$scope = $this->get_scope();
		$this->set_meta( '_nab_scope', $scope );

		$starter = $this->get_starter();
		$this->set_meta( '_nab_starter', $starter );

		$stopper = $this->get_stopper();
		$this->set_meta( '_nab_stopper', $stopper );

		$this->set_meta( '_nab_control_backup', $this->control_backup );

		$this->set_meta( '_nab_last_alternative_applied', $this->last_alternative_applied );

		$tested_post_id = $this->get_tested_post();
		$this->set_meta( '_nab_tested_post_id', $tested_post_id );

		$this->set_meta( '_nab_experiment_type', $this->get_type() );
		$this->set_meta( '_nab_version', nelioab()->plugin_version );

		/**
		 * Fires after an experiment has been saved.
		 *
		 * @param Nelio_AB_Testing_Experiment $experiment the experiment that has been saved.
		 *
		 * @since 5.0.0
		 */
		do_action( 'nab_save_experiment', $this );
	}//end save()

	/**
	 * Starts this experiment, assuming it's ready.
	 *
	 * @param string $scope Optional. Either `ignore-scope-overlap` or `check-scope-overlap`. Default: `check-scope-overlap`.
	 *
	 * @return bool|WP_Error whether this experiment has been started or not.
	 *
	 * @since  5.0.0
	 */
	public function start( $scope = 'check-scope-overlap' ) {

		$skip_check = defined( 'DOING_CRON' ) && 'scheduled' === $this->get_status();
		if ( ! $skip_check ) {
			$can_be_started = $this->can_be_started( $scope );
			if ( is_wp_error( $can_be_started ) ) {
				return $can_be_started;
			}//end if
		}//end if

		$this->set_start_date( str_replace( '+00:00', '.000Z', gmdate( 'c' ) ) );
		$this->post->post_status = 'nab_running';
		if ( empty( $this->get_starter() ) ) {
			$this->set_starter( get_current_user_id() );
		}//end if
		$this->save();

		/**
		 * Fires after an experiment has been started.
		 *
		 * @param Nelio_AB_Testing_Experiment $experiment the experiment that has been started.
		 *
		 * @since 5.0.0
		 */
		do_action( 'nab_start_experiment', $this );

		return true;
	}//end start()

	/**
	 * Restarts this experiment, assuming it's possible.
	 *
	 * @param string $scope Optional. Either `ignore-scope-overlap` or `check-scope-overlap`. Default: `check-scope-overlap`.
	 *
	 * @return bool|WP_Error whether this experiment has been restarted or not.
	 *
	 * @since  6.2.0
	 */
	public function restart( $scope = 'check-scope-overlap' ) {

		$can_be_restarted = $this->can_be_restarted( $scope );
		if ( is_wp_error( $can_be_restarted ) ) {
			return $can_be_restarted;
		}//end if

		$this->set_status( 'ready' );
		if ( $this->can_be_started( $scope ) ) {
			$this->stopper  = false;
			$this->end_date = false;
			$this->remove_control_backup();
			$this->start( $scope );
			return true;
		}//end if

		$this->set_status( 'running' );
		if ( $this->can_be_paused() ) {
			$this->stopper  = false;
			$this->end_date = false;
			$this->remove_control_backup();
			$this->pause();
			return true;
		}//end if

		$this->set_status( 'finished' );
		return new WP_Error(
			'unable-to-restart',
			_x( 'Something went wrong.', 'text', 'nelio-ab-testing' )
		);
	}//end restart()

	/**
	 * Resumes this experiment, assuming it's paused.
	 *
	 * @param string $scope Optional. Either `ignore-scope-overlap` or `check-scope-overlap`. Default: `check-scope-overlap`.
	 *
	 * @return bool|WP_Error whether this experiment has been resumed or not.
	 *
	 * @since  5.0.0
	 */
	public function resume( $scope = 'check-scope-overlap' ) {

		$can_be_resumed = $this->can_be_resumed( $scope );
		if ( is_wp_error( $can_be_resumed ) ) {
			return $can_be_resumed;
		}//end if

		$this->post->post_status = 'nab_running';
		$this->save();

		/**
		 * Fires after an experiment has been resumed.
		 *
		 * @param Nelio_AB_Testing_Experiment $experiment the experiment that has been resumed.
		 *
		 * @since 5.0.0
		 */
		do_action( 'nab_resume_experiment', $this );

		return true;
	}//end resume()

	/**
	 * Stops this experiment.
	 *
	 * @since  5.0.0
	 */
	public function stop() {

		$skip_check = defined( 'DOING_CRON' ) && 'running' === $this->get_status();
		if ( ! $skip_check ) {
			$can_be_stopped = $this->can_be_stopped();
			if ( is_wp_error( $can_be_stopped ) ) {
				return $can_be_stopped;
			}//end if
		}//end if

		$this->set_end_date( gmdate( 'c' ) );
		$this->post->post_status = 'nab_finished';
		if ( empty( $this->get_stopper() ) ) {
			$this->set_stopper( get_current_user_id() );
		}//end if

		$this->backup_control_version();
		$this->save();

		/**
		 * Fires after an experiment has been stopped.
		 *
		 * @param Nelio_AB_Testing_Experiment $experiment the experiment that has been stopped.
		 *
		 * @since 5.0.0
		 */
		do_action( 'nab_stop_experiment', $this );

		return true;
	}//end stop()

	/**
	 * Pauses this experiment, assuming it's running.
	 *
	 * @return bool|WP_Error whether this experiment has been paused or not.
	 *
	 * @since  5.0.0
	 */
	public function pause() {

		if ( ! current_user_can( 'pause_nab_experiments' ) ) {
			return new WP_Error(
				'missing-capability',
				__( 'Sorry, you are not allowed to do that.' ) // phpcs:ignore WordPress.WP.I18n.MissingArgDomain
			);
		}//end if

		if ( 'running' !== $this->get_status() ) {
			$helper = Nelio_AB_Testing_Experiment_Helper::instance();
			return new WP_Error(
				'experiment-cannot-be-paused',
				sprintf(
					/* translators: experiment name */
					_x( 'Test %1$s can’t be paused because it’s not running.', 'text', 'nelio-ab-testing' ),
					$helper->get_non_empty_name( $this )
				)
			);
		}//end if

		$this->post->post_status = 'nab_paused';
		$this->save();

		/**
		 * Fires after an experiment has been paused.
		 *
		 * @param Nelio_AB_Testing_Experiment $experiment the experiment that has been paused.
		 *
		 * @since 5.0.0
		 */
		do_action( 'nab_pause_experiment', $this );

		return true;
	}//end pause()

	/**
	 * Returns the status of this experiment.
	 *
	 * @return string the status of this experiment.
	 *
	 * @since  5.0.0
	 */
	public function get_status() {
		return str_replace( 'nab_', '', $this->post->post_status );
	}//end get_status()


	/**
	 * Sets the status of this experiment.
	 *
	 * @param string $status the status of this experiment.
	 *
	 * @since  5.0.0
	 */
	public function set_status( $status ) {

		if ( in_array( $status, array( 'ready', 'scheduled', 'running', 'paused', 'paused_draft', 'finished' ), true ) ) {
			$status = 'nab_' . $status;
		}//end if

		$this->post->post_status = $status;
	}//end set_status()

	/**
	 * Returns whether the experiment is inline (and thus doesn't require a redirection) or not.
	 *
	 * @return bool whether the experiment is inline or not.
	 *
	 * @since  6.0.0
	 */
	public function get_inline_settings() {

		$experiment_type = $this->get_type();

		/**
		 * Filters whether the experiment is inline (and thus doesn’t require a redirection to load alternative content) or not.
		 *
		 * If it is an inline experiment, it should return an array with two properties:
		 *
		 * * `load`: specifies when the alternative should be loaded:
		 *    - `header`: when `<head>` is ready (i.e. as soon as `<body>` is no longer `null`)
		 *    - `footer`: on `domReady`
		 *
		 * * `mode`:
		 *    - `unwrap`: alternative is included in a `<noscript>` tag that requires unwrapping.
		 *    - `visibility`: alternative is wrapped in a tag with `display:none` style.
		 *    - `script`: alternative is a JS script and, thus, stored in `nab` global object.
		 *
		 * If it’s not, it should return `false`.
		 *
		 * @param false|string $mode       whether the experiment is inline or not. Default: `false`.
		 * @param Experiment   $experiment the experiment.
		 *
		 * @since 6.0.0
		 */
		return apply_filters( "nab_{$experiment_type}_get_inline_settings", false, $this );
	}//end get_inline_settings()

	/**
	 * Returns whether the experiment variants are each in a different URL.
	 *
	 * @return bool whether the experiment variants are each in a different URL.
	 *
	 * @since  7.0.0
	 */
	public function has_multi_url_alternative() {

		$experiment_type = $this->get_type();

		/**
		 * Filters whether the experiment variants are each in a different URL.
		 *
		 * @param bool       $mode       whether the experiment variants are each in a different URL.
		 * @param Experiment $experiment the experiment.
		 *
		 * @since 7.0.0
		 */
		return apply_filters( "nab_has_{$experiment_type}_multi_url_alternative", false, $this );
	}//end has_multi_url_alternative()

	/**
	 * Returns the experiment URL.
	 *
	 * If the experiment is running or finished, this URL is the results URL. Otherwise, it's the edit URL.
	 *
	 * @return string the experiment URL
	 *
	 * @since  5.0.0
	 */
	public function get_url() {

		if ( in_array( $this->get_status(), array( 'running', 'finished' ), true ) ) {
			$page = 'nelio-ab-testing-experiment-view';
		} else {
			$page = 'nelio-ab-testing-experiment-edit';
		}//end if

		return add_query_arg(
			array(
				'page'       => $page,
				'experiment' => $this->get_id(),
			),
			admin_url( 'admin.php' )
		);
	}//end get_url()

	/**
	 * Returns the preview url of this experiment.
	 *
	 * @return string the preview url of this experiment.
	 *
	 * @since  5.0.0
	 */
	public function get_preview_url() {

		$control = $this->get_alternative( 'control' );
		return $control['links']['preview'];
	}//end get_preview_url()

	/**
	 * Callback function to call when an experiment is about to be deleted.
	 *
	 * @since  5.0.0
	 */
	public function delete_related_information() {
		$alternatives = $this->get_alternatives();
		foreach ( $alternatives as $alternative ) {
			if ( 'control' === $alternative['id'] ) {
				continue;
			}//end if
			$this->remove_alternative_content( $alternative );
		}//end foreach

		if ( $this->control_backup ) {
			$this->remove_alternative_content( $this->control_backup );
		}//end if
	}//end delete_related_information()

	/**
	 * Duplicates the current experiment.
	 *
	 * @return Nelio_AB_Testing_Experiment the new, duplicated experiment.
	 *
	 * @since  5.0.0
	 */
	public function duplicate() {

		$experiment_type = $this->get_type();

		$new_experiment = self::create_experiment( $experiment_type );
		$new_experiment->set_name(
			sprintf(
				/* translators: name of a split test */
				_x( 'Copy of %s', 'text', 'nelio-ab-testing' ),
				$this->get_name()
			)
		);
		$new_experiment->set_description( $this->get_description() );
		$new_experiment->set_end_mode_and_value( $this->get_end_mode(), $this->get_end_value() );
		$new_experiment->set_status( 'draft' === $this->get_status() ? 'draft' : 'ready' );

		$goals = $this->get_goals();
		foreach ( $goals as &$goal ) {
			$goal['id'] = nab_uuid();
			foreach ( $goal['conversionActions'] as &$action ) {
				$action['id'] = nab_uuid();
			}//end foreach
		}//end foreach
		$new_experiment->set_goals( $goals );

		$segments = $this->get_segments();
		foreach ( $segments as &$segment ) {
			$segment['id'] = nab_uuid();
			foreach ( $segment['segmentationRules'] as &$rule ) {
				$rule['id'] = nab_uuid();
			}//end foreach
		}//end foreach
		$new_experiment->set_segments( $segments );

		$new_experiment->set_scope( $this->get_scope() );

		$alternatives = $this->get_alternatives();

		foreach ( $alternatives as &$alternative ) {

			unset( $alternative['links'] );

			if ( 'control' === $alternative['id'] ) {
				continue;
			}//end if

			$old_alternative_id        = $alternative['id'];
			$alternative['id']         = nab_uuid();
			$alternative['attributes'] = $this->duplicate_alternative( $experiment_type, $alternative['attributes'], $alternative['attributes'], $new_experiment->ID, $alternative['id'], $this->ID, $old_alternative_id );

		}//end foreach
		$new_experiment->alternatives = $alternatives;

		$new_experiment->save();

		/**
		 * Fires after an experiment has been duplicated.
		 *
		 * @param Nelio_AB_Testing_Experiment $new_experiment the new experiment.
		 *
		 * @since 5.1.0
		 */
		do_action( 'nab_duplicate_experiment', $new_experiment );

		$new_experiment->save();

		return $new_experiment;
	}//end duplicate()

	/**
	 * Returns a summary of the experiment, which can be used in the frontend.
	 *
	 * @param bool $active whether the experiment loads alternative content (and therefore the summary needs to contain more data) or not.
	 *
	 * @return array a summary of the experiment.
	 *
	 * @since 6.0.0
	 */
	public function summarize( $active ) {
		$settings     = Nelio_AB_Testing_Settings::instance();
		$alternatives = array_merge(
			array( $this->get_tested_post() ),
			array_slice( array_map( '__return_zero', $this->get_alternatives() ), 1 )
		);

		$experiment_type = $this->get_type();

		/**
		 * Filters whether an active experiment (i.e. an experiment that loaded alternative content in the current request) should show up as inactive in the front-end.
		 *
		 * @param boolean                     $inactive   whether an active experiment should show up as inactive. Default: `false`.
		 * @param Nelio_AB_Testing_Experiment $experiment the experiment.
		 *
		 * @since 7.3.0
		 */
		if ( $active && apply_filters( "nab_{$experiment_type}_should_be_inactive_in_frontend", false, $this ) ) {
			$active = false;
		}//end if

		$result = array(
			'active'            => false,
			'id'                => $this->get_id(),
			'type'              => $this->get_type(),
			'alternatives'      => $alternatives,
			'goals'             => $this->get_goals_summary( $active ),
			'segments'          => $this->get_segments_summary(),
			'segmentEvaluation' => 'custom' === $settings->get( 'segment_evaluation' )
				? $this->get_segment_evaluation()
				: $settings->get( 'segment_evaluation' ),
		);
		if ( ! $active ) {
			return $result;
		}//end if

		/**
		 * Whether an experiment type supports heatmap tracking on requests in which it’s active.
		 *
		 * @param boolean $support_heatmaps whether the experiment supports heatmaps or not. Default: `false`.
		 *
		 * @since 7.0.0
		 */
		$heatmap_tracking = apply_filters( "nab_{$experiment_type}_supports_heatmaps", false );
		$heatmap_tracking = nab_is_subscribed() ? $heatmap_tracking : false;

		$result = array_merge(
			$result,
			array(
				'active'           => true,
				'alternatives'     => $this->get_alternatives_summary(),
				'heatmapTracking'  => $heatmap_tracking,
				'pageViewTracking' => $this->get_page_view_tracking_location(),
			)
		);
		$inline = $this->get_inline_settings();
		if ( $inline ) {
			$result = array_merge( $result, array( 'inline' => $inline ) );
		}//end if
		return $result;
	}//end summarize()

	/**
	 * Location (either “header” or “footer”) where page view events should be triggered.
	 *
	 * @return string Location (either "header" or "footer") where page view events should be triggered.
	 *
	 * @since 6.0.0
	 */
	public function get_page_view_tracking_location() {
		$experiment_type = $this->get_type();

		/**
		 * Filters the "moment" in which an active test should trigger a page view.
		 *
		 * @param string $location Either `header`, `footer`, or `script`. Default: `header`.
		 *
		 * @since 7.4.0
		 */
		return apply_filters( "nab_{$experiment_type}_get_page_view_tracking_location", 'header' );
	}//end get_page_view_tracking_location()

	private function get_alternatives_summary() {
		return array_map(
			function ( $alternative ) {
				$type = $this->get_type();
				/**
				 * Filters the experiment attributes that will be passed to front-end script.
				 *
				 * @param array  $attributes     Experiment attrs included in front-end script.
				 * @param int    $experiment_id  id of the experiment.
				 * @param string $alternative_id id of the alternative to apply.
				 *
				 * @since 6.0.0
				 */
				$attrs = apply_filters( "nab_{$type}_get_alternative_summary", $alternative['attributes'], $this->get_id(), $alternative['id'] );
				return $attrs;
			},
			$this->get_alternatives()
		);
	}//end get_alternatives_summary()

	private function get_goals_summary( $is_test_active ) {
		$goals = $this->get_goals();
		$goals = array_map(
			function ( $index ) use ( $goals ) {
				$goal = $goals[ $index ];
				return array(
					'id'                => $index,
					'conversionActions' => array_map(
						function ( $action ) use ( $index ) {
							$type = $action['type'];
							/**
							 * Filters a conversion action attributes that will be passed to front-end script.
							 *
							 * @param array  $attributes     Conversion action attrs included in front-end script.
							 * @param int    $experiment_id  Experiment ID.
							 * @param int    $goal_index     Goal index.
							 * @param string $action_id      Conversion action ID.
							 *
							 * @since 7.4.3
							 */
							$action['attributes'] = apply_filters( "nab_get_{$type}_conversion_action_summary", $action['attributes'], $this->get_id(), $index, $action['id'] );
							return $action;
						},
						$goal['conversionActions']
					),
				);
			},
			array_keys( $goals )
		);

		$settings      = Nelio_AB_Testing_Settings::instance();
		$goal_tracking = $settings->get( 'goal_tracking' );

		$helper  = Nelio_AB_Testing_Experiment_Helper::instance();
		$runtime = Nelio_AB_Testing_Runtime::instance();
		foreach ( $goals as &$goal ) {
			foreach ( $goal['conversionActions'] as &$action ) {
				$scope = 'custom' === $goal_tracking
					? $action['scope']
					: array( 'type' => $goal_tracking );
				$scope = $this->sanitize_conversion_action_scope( $scope, $action );

				switch ( $scope['type'] ) {
					case 'all-pages':
						$action['active'] = true;
						break;
					case 'test-scope':
						$action['active'] = $is_test_active;
						break;
					case 'php-function':
						$is_action_active = $scope['enabled'];
						$action['active'] = is_callable( $is_action_active ) && $is_action_active();
						break;
					case 'post-ids':
						$expected_ids     = nab_array_get( $scope, 'ids', array() );
						$expected_ids     = is_array( $expected_ids ) ? $expected_ids : array();
						$expected_ids     = $helper->add_alternative_post_ids( $expected_ids );
						$action['active'] = in_array( $this->get_current_post_id(), $expected_ids, true );
						break;
					case 'urls':
						$regexes          = nab_array_get( $scope, 'regexes', array() );
						$regexes          = is_array( $regexes ) ? $regexes : array();
						$action['active'] = $this->do_regexes_match_url( $regexes, $runtime->get_untested_url() );
						break;
					default:
						$action['active'] = false;
						break;
				}//end switch
				unset( $action['id'] );
				unset( $action['scope'] );
			}//end foreach
		}//end foreach

		return $goals;
	}//end get_goals_summary()

	private function get_segments_summary() {
		$segments = $this->get_segments();
		$segments = array_map(
			function ( $index ) use ( $segments ) {
				$segment = $segments[ $index ];
				return array(
					'id'                => $index,
					'segmentationRules' => $segment['segmentationRules'],
				);
			},
			array_keys( $segments )
		);

		foreach ( $segments as &$segment ) {
			foreach ( $segment['segmentationRules'] as &$rule ) {
				unset( $rule['id'] );
			}//end foreach
		}//end foreach

		return $segments;
	}//end get_segments_summary()

	private function do_regexes_match_url( $regexes, $url ) {
		$url = strtolower( $url );
		foreach ( $regexes as $regex ) {
			$parts = explode( '*', strtolower( $regex ) );
			$found = false;
			$start = 0;

			if ( count( $parts ) === 1 ) {
				$found = $url === $parts[0];
			} else {
				foreach ( $parts as $part ) {
					$found = empty( $part ) || false !== mb_strpos( $url, $part, $start );
					if ( ! $found ) {
						break;
					}//end if
					$start += mb_strlen( $part );
				}//end foreach
			}//end if

			if ( $found ) {
				return true;
			}//end if
		}//end foreach

		return false;
	}//end do_regexes_match_url()

	private function remove_alternative_content( $alternative ) {

		$experiment_type = $this->get_type();

		/**
		 * Fires when an alternative is being removed.
		 *
		 * Hook into this filter if the given alternative has some related content that has to
		 * be removed from the database too. For example, when removing a page alternative in
		 * a page experiment, the related page and all its metas have to be removed from
		 * `wp_posts` and `wp_postmeta` respectively.
		 *
		 * @param object $attributes     attributes of this alternative
		 * @param int    $experiment_id  ID of this experiment
		 * @param string $alternative_id ID of the alternative we want to clean
		 *
		 * @since 5.0.0
		 */
		do_action( "nab_{$experiment_type}_remove_alternative_content", $alternative['attributes'], $this->ID, $alternative['id'] );
	}//end remove_alternative_content()

	private function remove_dynamic_properties( $alternatives ) {

		return array_map(
			function ( $alternative ) {
				if ( isset( $alternative['links'] ) ) {
					unset( $alternative['links'] );
				}//end if
				if ( isset( $alternative['isLastApplied'] ) ) {
					unset( $alternative['isLastApplied'] );
				}//end if
				return $alternative;
			},
			$alternatives
		);
	}//end remove_dynamic_properties()

	private function clean_alternatives( $alternatives ) {

		return array_map(
			function ( $alternative ) {
				return array(
					'id'         => $alternative['id'],
					'attributes' => $alternative['attributes'],
				);
			},
			$alternatives
		);
	}//end clean_alternatives()

	private function get_alternative_edit_link( $alternative, $control ) {

		$experiment_id   = $this->get_id();
		$alternative_id  = $alternative['id'];
		$experiment_type = $this->get_type();

		/**
		 * Filters the edit link of the given alternative.
		 *
		 * @param mixed   $edit_link      link for editing the given alternative. Default: `false`.
		 * @param array   $alternative    current alternative.
		 * @param array   $control        original version.
		 * @param int     $experiment_id  id of the experiment.
		 * @param string  $alternative_id id of the current alternative.
		 *
		 * @since 5.0.0
		 */
		return apply_filters( "nab_{$experiment_type}_edit_link_alternative", false, $alternative['attributes'], $control['attributes'], $experiment_id, $alternative_id );
	}//end get_alternative_edit_link()

	private function get_alternative_heatmap_link( $alt_index, $alternative, $control ) {

		if ( ! did_action( 'init' ) ) {
			return false;
		}//end if

		$experiment_id     = $this->get_id();
		$alternative_id    = $alternative['id'];
		$experiment_type   = $this->get_type();
		$alternative_attrs = $alternative['attributes'];

		if ( 'finished' === $this->get_status() && 0 === $alt_index ) {
			if ( empty( $this->control_backup ) ) {
				return false;
			}//end if
			$alternative_attrs = $this->control_backup['attributes'];
		}//end if

		/**
		 * Filters the heatmap link of the given alternative.
		 *
		 * @param mixed   $heatmap_link   link for viewing the heatmaps of the given alternative. Default: `false`.
		 * @param array   $alternative    current alternative.
		 * @param array   $control        original version.
		 * @param int     $experiment_id  id of the experiment.
		 * @param string  $alternative_id id of the current alternative.
		 *
		 * @since 5.0.0
		 */
		$heatmap_url = apply_filters( "nab_{$experiment_type}_heatmap_link_alternative", false, $alternative_attrs, $control['attributes'], $experiment_id, $alternative_id );
		if ( ! $heatmap_url ) {
			return false;
		}//end if

		$secret       = nab_get_api_secret();
		$preview_time = time();
		return add_query_arg(
			array(
				'nab-preview'          => true,
				'nab-heatmap-renderer' => true,
				'experiment'           => $experiment_id,
				'alternative'          => $alt_index,
				'timestamp'            => $preview_time,
				'nabnonce'             => md5( "nab-preview-{$experiment_id}-{$alt_index}-{$preview_time}-{$secret}" ),
			),
			$heatmap_url
		);
	}//end get_alternative_heatmap_link()

	private function get_alternative_preview_link( $alt_index, $alternative, $control ) {

		if ( ! did_action( 'init' ) ) {
			return false;
		}//end if

		$experiment_id     = $this->get_id();
		$alternative_id    = $alternative['id'];
		$experiment_type   = $this->get_type();
		$alternative_attrs = $alternative['attributes'];

		if ( 'finished' === $this->get_status() && 0 === $alt_index ) {
			if ( empty( $this->control_backup ) ) {
				return false;
			}//end if
			$alternative_attrs = $this->control_backup['attributes'];
		}//end if

		/**
		 * Filters the preview link of the given alternative.
		 *
		 * @param mixed   $edit_link      link for previewing the given alternative. Default: `false`.
		 * @param array   $alternative    current alternative.
		 * @param array   $control        original version.
		 * @param int     $experiment_id  id of the experiment.
		 * @param string  $alternative_id id of the current alternative.
		 *
		 * @since 5.0.0
		 */
		$preview_url = apply_filters( "nab_{$experiment_type}_preview_link_alternative", false, $alternative_attrs, $control['attributes'], $experiment_id, $alternative_id );
		if ( ! $preview_url ) {
			return false;
		}//end if

		/**
		 * Filters whether custom query args should be added to the URL or not.
		 *
		 * @param mixed   $skip_args      whether query args should be skip or not. Default: `false`.
		 * @param array   $alternative    current alternative.
		 * @param array   $control        original version.
		 * @param int     $experiment_id  id of the experiment.
		 * @param string  $alternative_id id of the current alternative.
		 *
		 * @since 7.4.0
		 */
		if ( apply_filters( "nab_{$experiment_type}_skip_preview_args_alternative", false, $alternative_attrs, $control['attributes'], $experiment_id, $alternative_id ) ) {
			return $preview_url;
		}//end if

		$secret       = nab_get_api_secret();
		$preview_time = time();
		return add_query_arg(
			array(
				'nab-preview' => true,
				'experiment'  => $experiment_id,
				'alternative' => $alt_index,
				'timestamp'   => $preview_time,
				'nabnonce'    => md5( "nab-preview-{$experiment_id}-{$alt_index}-{$preview_time}-{$secret}" ),
			),
			$preview_url
		);
	}//end get_alternative_preview_link()

	/**
	 * Creates a new associative array where the keys are the IDs of the objects included in the original array.
	 *
	 * @param array $elements the alternative list.
	 *
	 * @return array an associative array where the keys are the IDs of the objects included in the original array.
	 *
	 * @since  5.0.0
	 */
	private function set_ids_as_keys( $elements ) {

		$result = array();
		if ( empty( $elements ) ) {
			return $result;
		}//end if

		foreach ( $elements as $elem ) {
			$element_id = $elem['id'];
			unset( $elem['id'] );
			$result[ $element_id ] = $elem;
		}//end foreach

		return $result;
	}//end set_ids_as_keys()

	/**
	 * Creates a non associative array where the keys in the original array are an attribute in each object.
	 *
	 * @param array $elements the alternative list.
	 *
	 * @return array a non associative array where the keys in the original array are an attribute in each object.
	 *
	 * @since  5.0.0
	 */
	private function set_keys_as_ids( $elements ) {

		$result = array();
		foreach ( $elements as $key => $elem ) {
			$elem['id'] = $key;
			array_push( $result, $elem );
		}//end foreach
		return $result;
	}//end set_keys_as_ids()

	private function backup_control_version() {

		$experiment_type = $this->get_type();
		$control         = $this->get_alternative( 'control' );

		$backup = array(
			'id'         => 'control_backup',
			'attributes' => nab_array_get( $control, 'attributes', array() ),
		);

		/**
		 * Fires when an experiment is stopped and a backup of the control version has to be generated.
		 *
		 * @param array   $backup           the backup object.
		 * @param array   $control          original version.
		 * @param int     $experiment_id    id of the experiment.
		 * @param string  $alternative_id   id of the current alternative.
		 *
		 * @since 5.0.0
		 */
		$backup['attributes'] = apply_filters( "nab_{$experiment_type}_backup_control", $backup['attributes'], $control['attributes'], $this->ID, $backup['id'] );

		if ( empty( $backup['attributes'] ) ) {
			$this->control_backup = false;
		}//end if

		$this->control_backup = $backup;
	}//end backup_control_version()

	private function remove_control_backup() {

		$experiment_type = $this->get_type();

		$backup = $this->control_backup;
		if ( empty( $backup ) ) {
			return;
		}//end if

		/**
		 * Fires when an experiment is restarted and its control backup (if any) should be removed.
		 *
		 * @param array   $backup           the backup object.
		 * @param int     $experiment_id    id of the experiment.
		 * @param string  $alternative_id   id of the current alternative.
		 *
		 * @since 6.2.0
		 */
		do_action( "nab_remove_{$experiment_type}_control_backup", $backup['attributes'], $this->ID, $backup['id'] );

		$this->control_backup = false;
	}//end remove_control_backup()

	private function get_current_post_id() {
		if ( $this->is_blog_page() ) {
			return absint( get_option( 'page_for_posts' ) );
		}//end if

		if ( $this->is_woocommerce_shop_page() ) {
			return absint( wc_get_page_id( 'shop' ) );
		}//end if

		if ( ! is_singular() ) {
			return 0;
		}//end if

		return nab_get_queried_object_id();
	}//end get_current_post_id()

	private function is_blog_page() {
		return ! is_front_page() && is_home();
	}//end is_blog_page()

	private function is_woocommerce_shop_page() {
		return function_exists( 'is_shop' ) && function_exists( 'wc_get_page_id' ) && is_shop();
	}//end is_woocommerce_shop_page()

	private function get_serializable_goals() {
		return json_decode( wp_json_encode( $this->get_goals() ), ARRAY_A );
	}//end get_serializable_goals()

	private function sanitize_goals( $goals ) {
		foreach ( $goals as &$goal ) {
			foreach ( $goal['conversionActions'] as &$action ) {
				$attributes = nab_array_get( $action, 'attributes', array() );
				$scope      = nab_array_get( $action, 'scope', array( 'type' => 'test-scope' ) );

				$action['attributes'] = $this->sanitize_conversion_action_attributes( $attributes, $action );
				$action['scope']      = $this->sanitize_conversion_action_scope( $scope, $action );
			}//end foreach
		}//end foreach

		return $goals;
	}//end sanitize_goals()

	private function sanitize_conversion_action_attributes( $attributes, $action ) {
		/**
		 * Filters a conversion action’s attributes.
		 *
		 * @param array                       $attributes conversion action’s attributes.
		 * @param array                       $action     conversion.
		 * @param Nelio_AB_Testing_Experiment $experiment experiment.
		 *
		 * @since 6.0.4
		*/
		return apply_filters( 'nab_sanitize_conversion_action_attributes', $attributes, $action, $this );
	}//end sanitize_conversion_action_attributes()

	private function sanitize_conversion_action_scope( $scope, $action ) {
		/**
		 * Filters a conversion action’s scope.
		 *
		 * @param array $scope  conversion action’s scope.
		 * @param array $action conversion action.
		 *
		 * @since 6.0.4
		 */
		return apply_filters( 'nab_sanitize_conversion_action_scope', $scope, $action );
	}//end sanitize_conversion_action_scope()

	private function duplicate_alternative( $experiment_type, $new_alternative, $old_alternative, $new_experiment_id, $new_alternative_id, $old_experiment_id, $old_alternative_id ) {
			/**
			 * Fires when an experiment is being duplicated and one of its alternatives is to be duplicated into the new experiment.
			 *
			 * @param array   $new_alternative     new alternative (by default, an exact copy of old alternative).
			 * @param array   $old_alternative     old alternative.
			 * @param int     $new_experiment_id   id of the new experiment.
			 * @param string  $new_alternative_id  id of the new alternative.
			 * @param int     $old_experiment_id   id of the old experiment.
			 * @param string  $old_alternative_id  id of the old alternative.
			 *
			 * @since 5.0.0
			 */
			return apply_filters( "nab_{$experiment_type}_duplicate_alternative_content", $new_alternative, $old_alternative, $new_experiment_id, $new_alternative_id, $old_experiment_id, $old_alternative_id );
	}//end duplicate_alternative()

	protected function get_meta( $name, $default = '' ) { // phpcs:ignore
		$value = get_post_meta( $this->ID, $name, true );
		return empty( $value ) ? $default : $value;
	}//end get_meta()

	protected function set_meta( $name, $value ) {
		if ( empty( $value ) ) {
			delete_post_meta( $this->ID, $name );
		} else {
			update_post_meta( $this->ID, $name, $value );
		}//end if
	}//end set_meta()
}//end class
