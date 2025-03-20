<?php
/**
 * This file customizes the Experiment list page added by WordPress.
 *
 * @package    Nelio_AB_Testing
 * @subpackage Nelio_AB_Testing/admin/pages
 * @since      5.0.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * This class contains several methods to customize the Experiment list page added
 * by WordPress.
 *
 * @package    Nelio_AB_Testing
 * @subpackage Nelio_AB_Testing/admin/pages
 * @since      5.0.0
 */
class Nelio_AB_Testing_Experiment_List_Page extends Nelio_AB_Testing_Abstract_Page {

	public function __construct() {

		parent::__construct(
			'nelio-ab-testing',
			_x( 'Tests', 'text', 'nelio-ab-testing' ),
			_x( 'Tests', 'text', 'nelio-ab-testing' ),
			'edit_nab_experiments',
			'edit.php?post_type=nab_experiment',
			'extends-existing-page'
		);
	}//end __construct()

	// @Overrides
	// phpcs:ignore
	public function init() {

		parent::init();

		add_action( 'current_screen', array( $this, 'maybe_redirect_to_experiment_page' ) );
		add_action( 'current_screen', array( $this, 'upgrade_experiments' ) );

		add_filter( 'display_post_states', array( $this, 'hide_post_states_in_experiments' ), 10, 2 );
		add_filter( 'manage_nab_experiment_posts_columns', array( $this, 'set_experiment_columns' ) );
		add_filter( 'manage_edit-nab_experiment_sortable_columns', array( $this, 'set_sortable_experiment_columns' ) );
		add_action( 'manage_nab_experiment_posts_custom_column', array( $this, 'set_experiment_column_values' ), 10, 2 );

		add_filter( 'post_row_actions', array( $this, 'fix_experiment_list_row_actions' ), 10, 2 );
		add_filter( 'bulk_actions-edit-nab_experiment', array( $this, 'remove_edit_from_bulk_actions' ) );

		add_action( 'admin_init', array( $this, 'manage_experiment_custom_actions' ) );
		add_action( 'admin_notices', array( $this, 'maybe_show_admin_notices_regarding_experiment_status_changes' ) );
		add_action( 'removable_query_args', array( $this, 'extend_removable_query_args_with_experiment_status_changes' ) );
	}//end init()

	public function maybe_redirect_to_experiment_page() {

		if ( ! $this->is_current_screen_this_page() ) {
			return;
		}//end if

		if ( $this->is_request_a_valid_action_on_experiment() ) {
			$action_url = $this->get_action_url();
			wp_safe_redirect( $action_url );
			exit;
		}//end if
	}//end maybe_redirect_to_experiment_page()

	public function upgrade_experiments() {

		if ( ! $this->is_current_screen_this_page() ) {
			return;
		}//end if

		global $wpdb;
		// phpcs:ignore
		$wpdb->update(
			$wpdb->posts,
			array( 'post_status' => 'nab_paused_draft' ),
			array(
				'post_type'   => 'nab_experiment',
				'post_status' => 'paused_draft',
			)
		);
	}//end upgrade_experiments()

	public function hide_post_states_in_experiments( $post_states, $post ) {

		if ( 'nab_experiment' !== $post->post_type ) {
			return $post_states;
		}//end if

		return array();
	}//end hide_post_states_in_experiments()

	public function set_experiment_columns( $columns ) {

		$columns = array(
			'cb'             => $columns['cb'],
			'type'           => _x( 'Type', 'text', 'nelio-ab-testing' ),
			'title'          => _x( 'Name', 'text', 'nelio-ab-testing' ),
			'status'         => _x( 'Status', 'text', 'nelio-ab-testing' ),
			'nab_page_views' => _x( 'Page Views', 'text', 'nelio-ab-testing' ),
			'nab_date'       => $columns['date'],
		);

		if ( $this->should_page_views_column_be_hidden() ) {
			unset( $columns['nab_page_views'] );
		}//end if

		if ( $this->should_status_column_be_hidden() ) {
			unset( $columns['status'] );
		}//end if

		return $columns;
	}//end set_experiment_columns()

	public function set_sortable_experiment_columns() {
		return array(
			'title'    => 'title',
			'status'   => 'status',
			'nab_date' => 'date',
		);
	}//end set_sortable_experiment_columns()

	public function set_experiment_column_values( $column_name, $post_id ) {

		$experiment = nab_get_experiment( $post_id );

		switch ( $column_name ) {

			case 'type':
				$this->print_experiment_type_column( $experiment );
				break;

			case 'status':
				$this->print_experiment_status_column( $experiment );
				break;

			case 'nab_page_views':
				$this->print_experiment_page_views_column( $experiment );
				break;

			case 'nab_date':
				$this->print_experiment_date_column( $experiment );
				break;

		}//end switch
	}//end set_experiment_column_values()

	public function fix_experiment_list_row_actions( $actions, $post ) {

		if ( 'nab_experiment' !== $post->post_type ) {
			return $actions;
		}//end if

		$experiment = nab_get_experiment( $post->ID );

		$actions = array_filter(
			$actions,
			function ( $key ) {
				return in_array( $key, array( 'edit', 'trash', 'delete', 'untrash' ), true );
			},
			ARRAY_FILTER_USE_KEY
		);

		if ( ! $experiment->can_be_edited() && isset( $actions['edit'] ) ) {
			unset( $actions['edit'] );
		}//end if

		$can_be_started = $experiment->can_be_started( 'ignore-scope-overlap' );
		if ( ! is_wp_error( $can_be_started ) ) {

			$actions['start'] = $this->get_start_experiment_action( $experiment );

		} elseif ( in_array( $can_be_started->get_error_code(), array( 'equivalent-experiment-running', 'experiment-type-not-allowed-in-free', 'experiments-already-running-in-free' ), true ) ) {

			$actions['start'] = sprintf(
				'<span title="%s" style="cursor:default">%s</span>',
				esc_attr( $can_be_started->get_error_message() ),
				esc_html( _x( 'Start', 'command', 'nelio-ab-testing' ) )
			);

		}//end if

		$can_be_resumed = $experiment->can_be_resumed( 'ignore-scope-overlap' );
		if ( ! is_wp_error( $can_be_resumed ) ) {
			$actions['resume'] = $this->get_resume_experiment_action( $experiment );
		} elseif ( in_array( $can_be_resumed->get_error_code(), array( 'equivalent-experiment-running', 'experiment-type-not-allowed-in-free', 'experiments-already-running-in-free' ), true ) ) {
			$actions['resume'] = sprintf(
				'<span title="%s" style="cursor:default">%s</span>',
				esc_attr( $can_be_resumed->get_error_message() ),
				esc_html( _x( 'Resume', 'command', 'nelio-ab-testing' ) )
			);
		}//end if

		if (
			current_user_can( 'read_nab_results' ) &&
			in_array( $experiment->get_status(), array( 'running', 'finished' ), true )
		) {
			$actions['results'] = $this->get_view_results_action( $experiment );
		}//end if

		if ( ! is_wp_error( $experiment->can_be_paused() ) ) {
			$actions['pause'] = $this->get_pause_experiment_action( $experiment );
		}//end if

		if ( 'trash' !== $experiment->get_status() ) {
			$actions['duplicate'] = $this->get_duplicate_experiment_action( $experiment );
		}//end if

		if ( ! is_wp_error( $experiment->can_be_restarted( 'ignore-scope-overlap' ) ) ) {
			$actions['restart'] = $this->get_restart_experiment_action( $experiment );
		}//end if

		if ( ! is_wp_error( $experiment->can_be_stopped() ) ) {
			$actions['stop'] = $this->get_stop_experiment_action( $experiment );
		}//end if

		$actions = $this->set_trash_as_last_action( $actions );
		if ( 'running' === $experiment->get_status() ) {
			unset( $actions['trash'] );
		}//end if

		return $actions;
	}//end fix_experiment_list_row_actions()

	public function remove_edit_from_bulk_actions( $actions ) {

		unset( $actions['edit'] );
		return $actions;
	}//end remove_edit_from_bulk_actions()

	private function print_experiment_type_column( $experiment ) {

		$type = $experiment->get_type();

		/**
		 * Filters the experiment type value in the experiment type column.
		 *
		 * @param string $type current experiment type.
		 *
		 * @since 5.1.0
		 */
		$type = apply_filters( 'nab_experiment_type_column_in_experiment_list', $type );

		printf(
			'<span class="nab-experiment__icon js-nab-experiment__icon" data-experiment-type="%s"></span>',
			esc_attr( $type )
		);
	}//end print_experiment_type_column()

	private function print_experiment_status_column( $experiment ) {

		$status        = $experiment->get_status();
		$status_object = get_post_status_object( $status );

		if ( empty( $status_object ) ) {
			$status_object = get_post_status_object( "nab_$status" );
		}//end if

		if ( ! empty( $status_object ) ) {
			$label = $status_object->label;
		} else {
			$label = $status;
		}//end if

		printf(
			'<span class="nab-experiment__status %s">%s</span>',
			esc_attr( "nab-experiment__status--$status" ),
			esc_html( $label )
		);
	}//end print_experiment_status_column()

	private function print_experiment_page_views_column( $experiment ) {

		$exp_id = $experiment->get_id();
		$status = $experiment->get_status();

		$has_local_results = get_post_meta( $exp_id, '_nab_are_timeline_results_definitive', true );
		if ( 'finished' === $status && $has_local_results ) {
			$results = get_post_meta( $exp_id, '_nab_timeline_results', true );

			$page_views = 0;
			if ( is_array( $results ) ) {
				foreach ( $results as $key => $value ) {
					$page_views += 'a' === $key[0] ? $value['v'] : 0;
				}//end foreach
			}//end if

			printf(
				'<span class="nab-page-views-wrapper" data-value="%s">%s</span>',
				esc_html( $page_views ),
				esc_html( _x( 'Loading…', 'text', 'nelio-ab-testing' ) )
			);
			return;
		}//end if

		if ( in_array( $status, array( 'finished', 'running', 'paused' ), true ) ) {
			printf(
				'<span class="nab-pending-page-views-wrapper" data-id="%s">%s</span>',
				esc_attr( $exp_id ),
				esc_html( _x( 'Loading…', 'text', 'nelio-ab-testing' ) )
			);
			return;
		}//end if

		echo '0';
	}//end print_experiment_page_views_column()

	private function print_experiment_date_column( $experiment ) {

		switch ( $experiment->get_status() ) {

			case 'scheduled':
				$this->print_label_and_date( _x( 'Starts', 'text (experiment status)', 'nelio-ab-testing' ), $experiment->get_start_date() );
				break;

			case 'running':
				$this->print_label_and_date( _x( 'Started', 'text (experiment status)', 'nelio-ab-testing' ), $experiment->get_start_date() );
				break;

			case 'finished':
				$this->print_label_and_date( _x( 'Finished', 'text (experiment status)', 'nelio-ab-testing' ), $experiment->get_end_date() );
				break;

			default:
				$table = new WP_Posts_List_Table();
				$table->column_date( $experiment->get_post() );

		}//end switch
	}//end print_experiment_date_column()

	private function print_label_and_date( $label, $date ) {

		$time      = strtotime( $date );
		$time_diff = time() - $time;

		if ( $time_diff > 0 && $time_diff < DAY_IN_SECONDS ) {
			/* translators: an amount of time */
			$h_time = sprintf( _x( '%s ago', 'text', 'nelio-ab-testing' ), human_time_diff( $time ) );
		} elseif ( $time_diff < 0 && absint( $time_diff ) < DAY_IN_SECONDS ) {
			/* translators: an amount of time */
			$h_time = sprintf( _x( 'in %s', 'text', 'nelio-ab-testing' ), human_time_diff( $time ) );
		} else {
			$h_time = wp_date( _x( 'Y/m/d g:i:s a', 'text', 'nelio-ab-testing' ), $time );
		}//end if

		printf(
			'%s<br><abbr title="%s">%s</abbr>',
			esc_html( $label ),
			esc_attr( wp_date( 'Y/m/d g:i:s a', $time ) ),
			esc_html( $h_time )
		);
	}//end print_label_and_date()

	private function get_duplicate_experiment_action( $experiment ) {

		$action = wp_nonce_url(
			add_query_arg(
				array(
					'experiment' => $experiment->get_id(),
					'action'     => 'duplicate',
				),
				admin_url( 'edit.php?post_type=nab_experiment' )
			),
			'nab_duplicate_experiment_' . $experiment->get_id()
		);

		return sprintf(
			'<a href="%1$s">%2$s</a>',
			esc_url( $action ),
			esc_html_x( 'Duplicate', 'command', 'nelio-ab-testing' )
		);
	}//end get_duplicate_experiment_action()

	private function get_start_experiment_action( $experiment, $action = 'start' ) {

		// NOTE. $action is either “start” or “force-start.” Default: “start”.
		$action_url = wp_nonce_url(
			add_query_arg(
				array(
					'experiment' => $experiment->get_id(),
					'action'     => $action,
				),
				admin_url( 'edit.php?post_type=nab_experiment' )
			),
			"nab_{$action}_experiment_" . $experiment->get_id()
		);

		return sprintf(
			'<a href="%1$s">%2$s</a>',
			esc_url( $action_url ),
			'start' === $action
				? esc_html_x( 'Start', 'command', 'nelio-ab-testing' )
				: esc_html_x( 'Start anyway', 'command', 'nelio-ab-testing' )
		);
	}//end get_start_experiment_action()

	private function get_restart_experiment_action( $experiment, $action = 'restart' ) {

		// NOTE. $action is either “restart” or “force-restart.” Default: “restart”.
		$action_url = wp_nonce_url(
			add_query_arg(
				array(
					'experiment' => $experiment->get_id(),
					'action'     => $action,
				),
				admin_url( 'edit.php?post_type=nab_experiment' )
			),
			"nab_{$action}_experiment_" . $experiment->get_id()
		);

		return sprintf(
			'<a href="%1$s">%2$s</a>',
			esc_url( $action_url ),
			'restart' === $action
				? esc_html_x( 'Restart', 'command', 'nelio-ab-testing' )
				: esc_html_x( 'Restart anyway', 'command', 'nelio-ab-testing' )
		);
	}//end get_restart_experiment_action()

	private function get_pause_experiment_action( $experiment ) {

		$action = wp_nonce_url(
			add_query_arg(
				array(
					'experiment' => $experiment->get_id(),
					'action'     => 'pause',
				),
				admin_url( 'edit.php?post_type=nab_experiment' )
			),
			'nab_pause_experiment_' . $experiment->get_id()
		);

		return sprintf(
			'<a href="%1$s">%2$s</a>',
			esc_url( $action ),
			esc_html_x( 'Pause', 'command', 'nelio-ab-testing' )
		);
	}//end get_pause_experiment_action()

	private function get_resume_experiment_action( $experiment, $action = 'resume' ) {

		// NOTE. $action is either “resume” or “force-resume.” Default: “resume”.
		$action_url = wp_nonce_url(
			add_query_arg(
				array(
					'experiment' => $experiment->get_id(),
					'action'     => $action,
				),
				admin_url( 'edit.php?post_type=nab_experiment' )
			),
			"nab_{$action}_experiment_" . $experiment->get_id()
		);

		return sprintf(
			'<a href="%1$s">%2$s</a>',
			esc_url( $action_url ),
			'resume' === $action
				? esc_html_x( 'Resume', 'command', 'nelio-ab-testing' )
				: esc_html_x( 'Resume anyway', 'command', 'nelio-ab-testing' )
		);
	}//end get_resume_experiment_action()

	private function get_view_results_action( $experiment ) {

		$action = add_query_arg( 'experiment', $experiment->get_id(), admin_url( 'admin.php?page=nelio-ab-testing-experiment-view' ) );
		return sprintf(
			'<a href="%1$s">%2$s</a>',
			esc_url( $action ),
			esc_html_x( 'View Results', 'command', 'nelio-ab-testing' )
		);
	}//end get_view_results_action()

	private function get_stop_experiment_action( $experiment ) {

		$action = wp_nonce_url(
			add_query_arg(
				array(
					'experiment' => $experiment->get_id(),
					'action'     => 'stop',
				),
				admin_url( 'edit.php?post_type=nab_experiment' )
			),
			'nab_stop_experiment_' . $experiment->get_id()
		);

		return sprintf(
			'<a href="%1$s">%2$s</a>',
			esc_url( $action ),
			esc_html_x( 'Stop', 'command', 'nelio-ab-testing' )
		);
	}//end get_stop_experiment_action()

	private function set_trash_as_last_action( $actions ) {

		if ( ! isset( $actions['trash'] ) ) {
			return $actions;
		}//end if

		$trash = $actions['trash'];
		unset( $actions['trash'] );
		$actions['trash'] = $trash;

		return $actions;
	}//end set_trash_as_last_action()

	private function should_page_views_column_be_hidden() {
		if ( ! isset( $_REQUEST['post_status'] ) ) { // phpcs:ignore
			return false;
		}//end if

		$status = sanitize_text_field( wp_unslash( $_REQUEST['post_status'] ) ); // phpcs:ignore
		return 'trash' === $status; // phpcs:ignore
	}//end should_page_views_column_be_hidden()

	private function should_status_column_be_hidden() {

		if ( ! isset( $_REQUEST['post_status'] ) ) { // phpcs:ignore
			return false;
		}//end if

		$status        = sanitize_text_field( wp_unslash( $_REQUEST['post_status'] ) ); // phpcs:ignore
		$status_object = get_post_status_object( $status );

		return ! empty( $status_object );
	}//end should_status_column_be_hidden()

	public function maybe_show_admin_notices_regarding_experiment_status_changes() {

		if ( isset( $_GET['nab_started'] ) ) { // phpcs:ignore
			printf( '<div class="updated notice is-dismissible"><p>%s</p></div>', esc_html_x( 'Test started.', 'text', 'nelio-ab-testing' ) ); // phpcs:ignore
		}//end if

		if ( isset( $_GET['nab_restarted'] ) ) { // phpcs:ignore
			printf( '<div class="updated notice is-dismissible"><p>%s</p></div>', esc_html_x( 'Test restarted.', 'text', 'nelio-ab-testing' ) ); // phpcs:ignore
		}//end if

		if ( isset( $_GET['nab_paused'] ) ) { // phpcs:ignore
			printf( '<div class="updated notice is-dismissible"><p>%s</p></div>', esc_html_x( 'Test paused.', 'text', 'nelio-ab-testing' ) ); // phpcs:ignore
		}//end if

		if ( isset( $_GET['nab_resumed'] ) ) { // phpcs:ignore
			printf( '<div class="updated notice is-dismissible"><p>%s</p></div>', esc_html_x( 'Test resumed.', 'text', 'nelio-ab-testing' ) ); // phpcs:ignore
		}//end if

		if ( isset( $_GET['nab_stopped'] ) ) { // phpcs:ignore
			printf( '<div class="updated notice is-dismissible"><p>%s</p></div>', esc_html_x( 'Test stopped.', 'text', 'nelio-ab-testing' ) ); // phpcs:ignore
		}//end if

		if ( isset( $_GET['nab_duplicated'] ) ) { // phpcs:ignore
			printf( '<div class="updated notice is-dismissible"><p>%s</p></div>', esc_html_x( 'Test duplicated.', 'text', 'nelio-ab-testing' ) ); // phpcs:ignore
		}//end if
	}//end maybe_show_admin_notices_regarding_experiment_status_changes()

	public function extend_removable_query_args_with_experiment_status_changes( $args ) {

		return array_merge( $args, array( 'nab_started', 'nab_resumed', 'nab_stopped', 'nab_duplicated' ) );
	}//end extend_removable_query_args_with_experiment_status_changes()

	public function manage_experiment_custom_actions() {

		if ( ! isset( $_GET['action'] ) || ! isset( $_GET['experiment'] ) || ! absint( $_GET['experiment'] ) ) { // phpcs:ignore
			return;
		}//end if

		$action     = sanitize_text_field( wp_unslash( $_GET['action'] ) ); // phpcs:ignore
		$experiment = nab_get_experiment( absint( $_GET['experiment'] ) ); // phpcs:ignore

		$die = function ( $message ) {
			wp_die(
				wp_kses( $message, 'a' ),
				null,
				array( 'back_link' => esc_url( admin_url( 'edit.php?post_type=nab_experiment' ) ) )
			);
		};

		switch ( $action ) {

			case 'start':
			case 'force-start':
				if ( ! $experiment ) {
					$die( _x( 'You attempted to start a test that doesn’t exist. Perhaps it was deleted?', 'user', 'nelio-ab-testing' ) );
				}//end if

				check_admin_referer( "nab_{$action}_experiment_" . $experiment->get_id() );
				$can_be_started = $experiment->can_be_started( 'force-start' === $action ? 'ignore-scope-overlap' : 'check-scope-overlap' );
				if ( is_wp_error( $can_be_started ) ) {
					$message = $can_be_started->get_error_message();
					if ( 'equivalent-experiment-running' === $can_be_started->get_error_code() ) {
						$message .= ' ';
						$message .= $this->get_start_experiment_action( $experiment, 'force-start' );
						$message .= '.';
					}//end if
					$die( $message );
				}//end if
				$experiment->start( 'ignore-scope-overlap' );

				wp_safe_redirect( admin_url( 'edit.php?post_type=nab_experiment&nab_started=1' ) );
				exit( 0 );

			case 'pause':
				if ( ! $experiment ) {
					$die( _x( 'You attempted to pause a test that doesn’t exist. Perhaps it was deleted?', 'user', 'nelio-ab-testing' ) );
				}//end if

				check_admin_referer( 'nab_pause_experiment_' . $experiment->get_id() );
				$paused = $experiment->pause();
				if ( is_wp_error( $paused ) ) {
					$die( $paused->get_error_message() );
				}//end if

				wp_safe_redirect( admin_url( 'edit.php?post_type=nab_experiment&nab_paused=1' ) );
				exit( 0 );

			case 'resume':
			case 'force-resume':
				if ( ! $experiment ) {
					$die( _x( 'You attempted to resume a test that doesn’t exist. Perhaps it was deleted?', 'user', 'nelio-ab-testing' ) );
				}//end if

				check_admin_referer( "nab_{$action}_experiment_" . $experiment->get_id() );
				$can_be_resumed = $experiment->can_be_resumed( 'force-resume' === $action ? 'ignore-scope-overlap' : 'check-scope-overlap' );
				if ( is_wp_error( $can_be_resumed ) ) {
					$message = $can_be_resumed->get_error_message();
					if ( 'equivalent-experiment-running' === $can_be_resumed->get_error_code() ) {
						$message .= ' ';
						$message .= $this->get_resume_experiment_action( $experiment, 'force-resume' );
						$message .= '.';
					}//end if
					$die( $message );
				}//end if
				$experiment->resume( 'ignore-scope-overlap' );

				wp_safe_redirect( admin_url( 'edit.php?post_type=nab_experiment&nab_resumed=1' ) );
				exit( 0 );

			case 'stop':
				if ( ! $experiment ) {
					$die( _x( 'You attempted to stop a test that doesn’t exist. Perhaps it was deleted?', 'user', 'nelio-ab-testing' ) );
				}//end if

				check_admin_referer( 'nab_stop_experiment_' . $experiment->get_id() );
				$stopped = $experiment->stop();
				if ( is_wp_error( $stopped ) ) {
					$die( $stopped->get_error_message() );
				}//end if

				wp_safe_redirect( admin_url( 'edit.php?post_type=nab_experiment&nab_stopped=1' ) );
				exit( 0 );

			case 'restart':
			case 'force-restart':
				if ( ! $experiment ) {
					$die( _x( 'You attempted to restart a test that doesn’t exist. Perhaps it was deleted?', 'user', 'nelio-ab-testing' ) );
				}//end if

				check_admin_referer( "nab_{$action}_experiment_" . $experiment->get_id() );
				$can_be_restarted = $experiment->can_be_restarted( 'force-restart' === $action ? 'ignore-scope-overlap' : 'check-scope-overlap' );
				if ( is_wp_error( $can_be_restarted ) ) {
					$message = $can_be_restarted->get_error_message();
					if ( 'equivalent-experiment-running' === $can_be_restarted->get_error_code() ) {
						$message .= ' ';
						$message .= $this->get_restart_experiment_action( $experiment, 'force-restart' );
						$message .= '.';
					}//end if
					$die( $message );
				}//end if
				$experiment->restart( 'ignore-scope-overlap' );

				wp_safe_redirect( admin_url( 'edit.php?post_type=nab_experiment&nab_restarted=1' ) );
				exit( 0 );

			case 'duplicate':
				if ( ! $experiment ) {
					$die( _x( 'You attempted to dupliacte a test that doesn’t exist. Perhaps it was deleted?', 'user', 'nelio-ab-testing' ) );
				}//end if

				check_admin_referer( 'nab_duplicate_experiment_' . $experiment->get_id() );
				$experiment->duplicate();

				wp_safe_redirect( admin_url( 'edit.php?post_type=nab_experiment&nab_duplicated=1' ) );
				exit( 0 );

		}//end switch
	}//end manage_experiment_custom_actions()

	public function enqueue_assets() {

		$script = '
		( function() {
			wp.domReady( function() {
				nab.initPage( "experiment-list", %s );
			} );
		} )();';

		$settings = array(
			'subscription' => nab_get_subscription(),
			'staging'      => nab_is_staging(),
			'isDeprecated' => get_option( 'nab_is_subscription_deprecated', false ),
		);

		wp_enqueue_style(
			'nab-experiment-list-page',
			nelioab()->plugin_url . '/assets/dist/css/experiment-list-page.css',
			array( 'nab-components' ),
			nelioab()->plugin_version
		);
		nab_enqueue_script_with_auto_deps( 'nab-experiment-list-page', 'experiment-list-page', false );

		wp_add_inline_script(
			'nab-experiment-list-page',
			sprintf(
				$script,
				wp_json_encode( $settings )
			)
		);
	}//end enqueue_assets()

	public function display() {
		// Nothing to be done.
	}//end display()

	private function is_request_a_valid_action_on_experiment() {

		if ( ! isset( $_GET['experiment'] ) ) { // phpcs:ignore
			return false;
		}//end if

		if ( ! isset( $_GET['action'] ) ) { // phpcs:ignore
			return false;
		}//end if

		$action        = sanitize_text_field( wp_unslash( $_GET['action'] ) ); // phpcs:ignore
		$valid_actions = array( 'edit', 'view' );
		return in_array( $action, $valid_actions, true );
	}//end is_request_a_valid_action_on_experiment()

	private function get_action_url() {

		return add_query_arg(
			array(
				'page'       => 'nelio-ab-experiment-' . sanitize_text_field( wp_unslash( nab_array_get( $_GET, 'action', '' ) ) ), // phpcs:ignore
				'experiment' => absint( nab_array_get( $_GET, 'experiment', 0 ) ), // phpcs:ignore
			),
			admin_url( 'admin.php' )
		);
	}//end get_action_url()
}//end class
