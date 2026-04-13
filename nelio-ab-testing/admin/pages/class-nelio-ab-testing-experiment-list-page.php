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
			array(
				'mode' => 'extends-existing-page',
				'help' => true,
			)
		);
	}

	/**
	 * Hooks into WordPress.
	 *
	 * @return void
	 */
	public function init() {

		parent::init();

		add_action( 'current_screen', array( $this, 'maybe_redirect_to_experiment_page' ) );

		add_filter( 'display_post_states', array( $this, 'hide_post_states_in_experiments' ), 10, 2 );
		add_filter( 'manage_nab_experiment_posts_columns', array( $this, 'get_experiment_columns' ) );
		add_filter( 'manage_edit-nab_experiment_sortable_columns', array( $this, 'get_sortable_experiment_columns' ) );
		add_action( 'manage_nab_experiment_posts_custom_column', array( $this, 'get_experiment_column_value' ), 10, 2 );

		add_filter( 'map_meta_cap', array( $this, 'maybe_change_meta_caps_to_disable_link' ), 10, 4 );
		add_filter( 'post_row_actions', array( $this, 'fix_experiment_list_row_actions' ), 10, 2 );
		add_filter( 'bulk_actions-edit-nab_experiment', array( $this, 'remove_edit_from_bulk_actions' ) );

		add_action( 'admin_init', array( $this, 'manage_experiment_custom_actions' ) );
		add_action( 'admin_notices', array( $this, 'maybe_show_admin_notices_regarding_experiment_status_changes' ) );
		add_filter( 'removable_query_args', array( $this, 'extend_removable_query_args_with_experiment_status_changes' ) );
	}

	/**
	 * Callback to redirect to experiment page.
	 *
	 * @return void
	 */
	public function maybe_redirect_to_experiment_page() {

		if ( ! $this->is_current_screen_this_page() ) {
			return; // @codeCoverageIgnore
		}

		if ( ! $this->is_request_a_valid_action_on_experiment() ) {
			return;
		}

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$action = sanitize_text_field( wp_unslash( $_GET['action'] ?? 0 ) );
		wp_safe_redirect(
			add_query_arg(
				array(
					'page'       => "nelio-ab-experiment-{$action}",
					// phpcs:ignore WordPress.Security.NonceVerification.Recommended
					'experiment' => absint( $_GET['experiment'] ?? 0 ),
				),
				admin_url( 'admin.php' )
			)
		);
		exit; // @codeCoverageIgnore
	}

	/**
	 * Callback to hide post states in experiments.
	 *
	 * @param list<string> $post_states Post states.
	 * @param WP_Post      $post        Experiment.
	 *
	 * @return list<string>
	 */
	public function hide_post_states_in_experiments( $post_states, $post ) {

		if ( 'nab_experiment' !== $post->post_type ) {
			return $post_states;
		}

		return array();
	}

	/**
	 * Callback to specify the columns used in an experiment.
	 *
	 * @param array<string,string> $columns Column labels by type.
	 *
	 * @return array<string,string>
	 */
	public function get_experiment_columns( $columns ) {

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
		}

		if ( $this->should_status_column_be_hidden() ) {
			unset( $columns['status'] );
		}

		return $columns;
	}

	/**
	 * Callback to specify which columns are sortable.
	 *
	 * @return array<string, string>
	 */
	public function get_sortable_experiment_columns() {
		return array(
			'title'    => 'title',
			'status'   => 'status',
			'nab_date' => 'date',
		);
	}

	/**
	 * Callback to print the appropriate value in each column.
	 *
	 * @param string $column_name Name of the column.
	 * @param int    $post_id     Experiment ID.
	 *
	 * @return void
	 */
	public function get_experiment_column_value( $column_name, $post_id ) {

		$experiment = nab_get_experiment( $post_id );
		if ( is_wp_error( $experiment ) ) {
			return;
		}

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

		}
	}

	/**
	 * Callback to disable title link if user can’t edit the test.
	 *
	 * This callback was created to account for PHP tests.
	 *
	 * @param array<string> $caps    List of capabilities.
	 * @param string        $cap     Capability.
	 * @param int           $user_id User ID.
	 * @param list<mixed>   $args    Arguments.
	 *
	 * @return array<string>
	 */
	public function maybe_change_meta_caps_to_disable_link( $caps, $cap, $user_id, $args ) {
		if ( ! $this->is_current_screen_this_page() ) {
			return $caps; // @codeCoverageIgnore
		}

		if ( 'edit_nab_experiments' !== $cap ) {
			return $caps;
		}

		$post_id = absint( $args[0] ?? 0 );
		$status  = get_post_status( $post_id );
		if ( in_array( $status, array( 'nab_running', 'nab_finished' ), true ) ) {
			return current_user_can( 'read_nab_results' ) ? $caps : array( 'do_not_allow' );
		}

		$type = get_post_meta( $post_id, '_nab_experiment_type', true );
		if ( 'nab/php' === $type ) {
			return current_user_can( 'edit_nab_php_experiments' ) ? $caps : array( 'do_not_allow' );
		}

		return $caps;
	}

	/**
	 * Callback to customize the actions available in each experiment.
	 *
	 * @param array<string,string> $actions List of actions.
	 * @param WP_Post              $post    The experiment.
	 *
	 * @return array<string,string>
	 */
	public function fix_experiment_list_row_actions( $actions, $post ) {

		$experiment = nab_get_experiment( $post->ID );
		if ( is_wp_error( $experiment ) ) {
			return $actions;
		}

		$actions = array_filter(
			$actions,
			function ( $key ) {
				return in_array( $key, array( 'edit', 'trash', 'delete', 'untrash' ), true );
			},
			ARRAY_FILTER_USE_KEY
		);

		if ( is_wp_error( $experiment->can_be_edited() ) && isset( $actions['edit'] ) ) {
			unset( $actions['edit'] );
		}

		if ( ! is_wp_error( $experiment->can_be_started( 'ignore-scope-overlap' ) ) ) {
			$actions['start'] = $this->get_start_experiment_action( $experiment );
		}

		if ( ! is_wp_error( $experiment->can_be_resumed( 'ignore-scope-overlap' ) ) ) {
			$actions['resume'] = $this->get_resume_experiment_action( $experiment );
		}

		if ( current_user_can( 'read_nab_results' ) && in_array( $experiment->get_status(), array( 'running', 'finished' ), true ) ) {
			$actions['results'] = $this->get_view_results_action( $experiment );
		}

		if ( ! is_wp_error( $experiment->can_be_paused() ) ) {
			$actions['pause'] = $this->get_pause_experiment_action( $experiment );
		}

		if ( ! is_wp_error( $experiment->can_be_duplicated() ) ) {
			$actions['duplicate'] = $this->get_duplicate_experiment_action( $experiment );
		}

		if ( ! is_wp_error( $experiment->can_be_restarted( 'ignore-scope-overlap' ) ) ) {
			$actions['restart'] = $this->get_restart_experiment_action( $experiment );
		}

		if ( ! is_wp_error( $experiment->can_be_stopped() ) ) {
			$actions['stop'] = $this->get_stop_experiment_action( $experiment );
		}

		$actions = $this->set_trash_as_last_action( $actions );
		if ( 'running' === $experiment->get_status() ) {
			unset( $actions['trash'] );
		}

		return $actions;
	}

	/**
	 * Callback to remove edit from bulk actions.
	 *
	 * @param array<string,string> $actions List of actions.
	 *
	 * @return array<string,string>
	 */
	public function remove_edit_from_bulk_actions( $actions ) {

		unset( $actions['edit'] );
		return $actions;
	}

	/**
	 * Callback to show the appropriate admin notice depending on the provided query args.
	 *
	 * @return void
	 */
	public function maybe_show_admin_notices_regarding_experiment_status_changes() {

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		if ( isset( $_GET['nab_started'] ) ) {
			printf( '<div class="updated notice is-dismissible"><p>%s</p></div>', esc_html_x( 'Test started.', 'text', 'nelio-ab-testing' ) );
		}

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		if ( isset( $_GET['nab_restarted'] ) ) {
			printf( '<div class="updated notice is-dismissible"><p>%s</p></div>', esc_html_x( 'Test restarted.', 'text', 'nelio-ab-testing' ) );
		}

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		if ( isset( $_GET['nab_paused'] ) ) {
			printf( '<div class="updated notice is-dismissible"><p>%s</p></div>', esc_html_x( 'Test paused.', 'text', 'nelio-ab-testing' ) );
		}

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		if ( isset( $_GET['nab_resumed'] ) ) {
			printf( '<div class="updated notice is-dismissible"><p>%s</p></div>', esc_html_x( 'Test resumed.', 'text', 'nelio-ab-testing' ) );
		}

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		if ( isset( $_GET['nab_stopped'] ) ) {
			printf( '<div class="updated notice is-dismissible"><p>%s</p></div>', esc_html_x( 'Test stopped.', 'text', 'nelio-ab-testing' ) );
		}

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		if ( isset( $_GET['nab_duplicated'] ) ) {
			printf( '<div class="updated notice is-dismissible"><p>%s</p></div>', esc_html_x( 'Test duplicated.', 'text', 'nelio-ab-testing' ) );
		}
	}

	/**
	 * Callback to extend removable query args that signal which admin notices should be shown.
	 *
	 * @param list<string> $args Arguments.
	 *
	 * @return list<string>
	 */
	public function extend_removable_query_args_with_experiment_status_changes( $args ) {

		return array_merge(
			$args,
			array(
				'nab_started',
				'nab_restarted',
				'nab_paused',
				'nab_resumed',
				'nab_stopped',
				'nab_duplicated',
			)
		);
	}

	/**
	 * Callback to react to the actions triggered by the user.
	 *
	 * @return void
	 */
	public function manage_experiment_custom_actions() {

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$action = sanitize_text_field( wp_unslash( $_GET['action'] ?? '' ) );
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$experiment_id = absint( $_GET['experiment'] ?? 0 );

		if ( empty( $action ) || empty( $experiment_id ) ) {
			return;
		}

		$experiment = nab_get_experiment( $experiment_id );
		if ( is_wp_error( $experiment ) ) {
			wp_die( esc_html_x( 'Test that doesn’t exist. Perhaps it was deleted?', 'text', 'nelio-ab-testing' ) );
		}

		$behaviors = $this->get_action_behaviors( $experiment );
		$behavior  = $behaviors[ $action ] ?? null;
		if ( empty( $behavior ) ) {
			return;
		}

		check_admin_referer( "nab_{$action}_experiment_" . $experiment->get_id() );

		$can_run = $behavior['check-capability']();
		if ( is_wp_error( $can_run ) ) {
			$message = $can_run->get_error_message();
			if ( 'equivalent-experiment-running' === $can_run->get_error_code() && ! empty( $behavior['force-action'] ) ) {
				$message .= ' ' . $behavior['force-action']() . '.';
			}
			wp_die( wp_kses( $message, 'a' ), '', array( 'back_link' => true ) );
		}

		$behavior['run']();

		wp_safe_redirect( add_query_arg( $behavior['redirect-argument'], 1, admin_url( 'edit.php?post_type=nab_experiment' ) ) );
		exit; // @codeCoverageIgnore
	}

	/**
	 * Callback to enqueue assets.
	 *
	 * @return void
	 */
	protected function enqueue_assets() {

		$script = '
		( function() {
			wp.domReady( function() {
				nab.initPage( "nab-experiment-list", %s );
			} );
		} )();';

		$settings = array(
			'subscription' => nab_get_subscription(),
			'staging'      => nab_is_staging(),
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
	}

	// @Implements
	public function display() {
		// Nothing to be done.
	}

	/**
	 * Prints the experient type column.
	 *
	 * @param Nelio_AB_Testing_Experiment $experiment An experiment.
	 *
	 * @return void
	 */
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
	}

	/**
	 * Prints experiment status column.
	 *
	 * @param Nelio_AB_Testing_Experiment $experiment An experiment.
	 *
	 * @return void
	 */
	private function print_experiment_status_column( $experiment ) {

		$status        = $experiment->get_status();
		$status_object = get_post_status_object( $status );

		if ( empty( $status_object ) ) {
			$status_object = get_post_status_object( "nab_$status" );
		}

		$label = ! empty( $status_object->label ) ? $status_object->label : $status;
		$label = is_string( $label ) ? $label : $status;

		printf(
			'<span class="nab-experiment__status %s">%s</span>',
			esc_attr( "nab-experiment__status--$status" ),
			esc_html( $label )
		);
	}

	/**
	 * Prints the page views column.
	 *
	 * @param Nelio_AB_Testing_Experiment $experiment An experiment.
	 *
	 * @return void
	 */
	private function print_experiment_page_views_column( $experiment ) {

		$exp_id = $experiment->get_id();
		$status = $experiment->get_status();

		$has_local_results = get_post_meta( $exp_id, '_nab_are_timeline_results_definitive', true );
		if ( 'finished' === $status && $has_local_results ) {
			$results = get_post_meta( $exp_id, '_nab_timeline_results', true );

			$page_views = 0;
			if ( is_array( $results ) ) {
				foreach ( $results as $key => $value ) {
					$page_views += 'a' === $key[0] ? absint( $value['v'] ?? '' ) : 0;
				}
			}

			printf(
				'<span class="nab-page-views-wrapper" data-value="%s">%s</span>',
				esc_html( $page_views ),
				esc_html( _x( 'Loading…', 'text', 'nelio-ab-testing' ) )
			);
			return;
		}

		if ( in_array( $status, array( 'finished', 'running', 'paused' ), true ) ) {
			printf(
				'<span class="nab-pending-page-views-wrapper" data-id="%s">%s</span>',
				esc_attr( $exp_id ),
				esc_html( _x( 'Loading…', 'text', 'nelio-ab-testing' ) )
			);
			return;
		}

		echo '0';
	}

	/**
	 * Prints the experiment date column.
	 *
	 * @param Nelio_AB_Testing_Experiment $experiment An experiment.
	 *
	 * @return void
	 */
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

		}
	}

	/**
	 * Prints the given label and date.
	 *
	 * @param string       $label Experiment label.
	 * @param string|false $date  Date.
	 *
	 * @return void
	 */
	private function print_label_and_date( $label, $date ) {

		if ( empty( $date ) ) {
			echo esc_html( $label ); // @codeCoverageIgnore
			return;                  // @codeCoverageIgnore
		}

		$time = strtotime( $date );
		if ( empty( $time ) ) {
			echo esc_html( $label ); // @codeCoverageIgnore
			return;                  // @codeCoverageIgnore
		}

		$time_diff = time() - $time;
		if ( $time_diff > 0 && $time_diff < DAY_IN_SECONDS ) {
			/* translators: %s: Amount of time. */
			$h_time = sprintf( _x( '%s ago', 'text', 'nelio-ab-testing' ), human_time_diff( $time ) );
		} elseif ( $time_diff < 0 && absint( $time_diff ) < DAY_IN_SECONDS ) {
			/* translators: %s: Amount of time. */
			$h_time = sprintf( _x( 'in %s', 'text', 'nelio-ab-testing' ), human_time_diff( $time ) );
		} else {
			$h_time = wp_date( _x( 'Y/m/d g:i:s a', 'text', 'nelio-ab-testing' ), $time );
		}

		$time = wp_date( 'Y/m/d g:i:s a', $time );
		if ( empty( $time ) || empty( $h_time ) ) {
			echo esc_html( $label ); // @codeCoverageIgnore
			return;                  // @codeCoverageIgnore
		}

		printf(
			'%s<br><abbr title="%s">%s</abbr>',
			esc_html( $label ),
			esc_attr( $time ),
			esc_html( $h_time )
		);
	}

	/**
	 * Returns the link to duplicate the given experiment.
	 *
	 * @param Nelio_AB_Testing_Experiment $experiment An experiment.
	 *
	 * @return string
	 */
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
	}

	/**
	 * Returns the link to start the given experiment.
	 *
	 * @param Nelio_AB_Testing_Experiment $experiment An experiment.
	 * @param string                      $action     Optional. Action.
	 *
	 * @return string
	 */
	private function get_start_experiment_action( $experiment, $action = 'start' ) {

		// INFO. $action is either “start” or “force-start.” Default: “start”.
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
	}

	/**
	 * Returns the link to restart the given experiment.
	 *
	 * @param Nelio_AB_Testing_Experiment $experiment An experiment.
	 * @param string                      $action     Optional. Action.
	 *
	 * @return string
	 */
	private function get_restart_experiment_action( $experiment, $action = 'restart' ) {

		// INFO. $action is either “restart” or “force-restart.” Default: “restart”.
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
	}

	/**
	 * Returns the link to pause the given experiment.
	 *
	 * @param Nelio_AB_Testing_Experiment $experiment An experiment.
	 *
	 * @return string
	 */
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
	}

	/**
	 * Returns the link to resume the given experiment.
	 *
	 * @param Nelio_AB_Testing_Experiment $experiment An experiment.
	 * @param string                      $action     Optional. Action.
	 *
	 * @return string
	 */
	private function get_resume_experiment_action( $experiment, $action = 'resume' ) {

		// INFO. $action is either “resume” or “force-resume.” Default: “resume”.
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
	}

	/**
	 * Returns the link to view the results of the given experiment.
	 *
	 * @param Nelio_AB_Testing_Experiment $experiment An experiment.
	 *
	 * @return string
	 */
	private function get_view_results_action( $experiment ) {

		$action = add_query_arg( 'experiment', $experiment->get_id(), admin_url( 'admin.php?page=nelio-ab-testing-experiment-view' ) );
		return sprintf(
			'<a href="%1$s">%2$s</a>',
			esc_url( $action ),
			esc_html_x( 'View Results', 'command', 'nelio-ab-testing' )
		);
	}

	/**
	 * Returns the link to stop the given experiment.
	 *
	 * @param Nelio_AB_Testing_Experiment $experiment An experiment.
	 *
	 * @return string
	 */
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
	}

	/**
	 * Reorders the given actions so that "trash" is the last one.
	 *
	 * @param array<string,string> $actions List of actions.
	 *
	 * @return array<string,string>
	 */
	private function set_trash_as_last_action( $actions ) {

		if ( ! isset( $actions['trash'] ) ) {
			return $actions;
		}

		$trash = $actions['trash'];
		unset( $actions['trash'] );
		$actions['trash'] = $trash;

		return $actions;
	}

	/**
	 * Whether page views column should be hidden or not, based on the request’s post_status.
	 *
	 * @return bool
	 */
	private function should_page_views_column_be_hidden() {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		return 'trash' === sanitize_text_field( wp_unslash( $_REQUEST['post_status'] ?? '' ) );
	}

	/**
	 * Whether the status column should be hidden or not, based on the request’s post_status.
	 *
	 * @return bool
	 */
	private function should_status_column_be_hidden() {

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		if ( ! isset( $_REQUEST['post_status'] ) ) {
			return false;
		}

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$status        = sanitize_text_field( wp_unslash( $_REQUEST['post_status'] ) );
		$status_object = get_post_status_object( $status );
		return ! empty( $status_object );
	}

	/**
	 * Checks if the GET action is either `edit` or `view` and applies to an experiment.
	 *
	 * @return bool
	 */
	private function is_request_a_valid_action_on_experiment() {

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		if ( ! isset( $_GET['experiment'] ) ) {
			return false;
		}

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		if ( ! isset( $_GET['action'] ) ) {
			return false;
		}

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$action        = sanitize_text_field( wp_unslash( $_GET['action'] ) );
		$valid_actions = array( 'edit', 'view' );
		return in_array( $action, $valid_actions, true );
	}

	/**
	 * Returns action behaviors for the given experiment.
	 *
	 * @param Nelio_AB_Testing_Experiment $experiment Experiment.
	 *
	 * @return array<
	 *   string,
	 *   array{
	 *     check-capability: Closure(): (true|WP_Error),
	 *     force-action?: Closure(): string,
	 *     run: Closure(): mixed,
	 *     redirect-argument: string
	 *   }
	 * >
	 */
	private function get_action_behaviors( $experiment ) {
		return array(
			'start'         => array(
				'check-capability'  => fn() => $experiment->can_be_started( 'check-scope-overlap' ),
				'force-action'      => fn() => $this->get_start_experiment_action( $experiment, 'force-start' ),
				'run'               => fn() => $experiment->start( 'ignore-scope-overlap' ),
				'redirect-argument' => 'nab_started',
			),
			'force-start'   => array(
				'check-capability'  => fn() => $experiment->can_be_started( 'ignore-scope-overlap' ),
				'run'               => fn() => $experiment->start( 'ignore-scope-overlap' ),
				'redirect-argument' => 'nab_started',
			),

			'pause'         => array(
				'check-capability'  => fn() => $experiment->can_be_paused(),
				'run'               => fn() => $experiment->pause(),
				'redirect-argument' => 'nab_paused',
			),

			'resume'        => array(
				'check-capability'  => fn() => $experiment->can_be_resumed( 'check-scope-overlap' ),
				'force-action'      => fn() => $this->get_resume_experiment_action( $experiment, 'force-resume' ),
				'run'               => fn() => $experiment->resume( 'ignore-scope-overlap' ),
				'redirect-argument' => 'nab_resumed',
			),
			'force-resume'  => array(
				'check-capability'  => fn() => $experiment->can_be_resumed( 'ignore-scope-overlap' ),
				'run'               => fn() => $experiment->resume( 'ignore-scope-overlap' ),
				'redirect-argument' => 'nab_resumed',
			),

			'stop'          => array(
				'check-capability'  => fn() => $experiment->can_be_stopped(),
				'run'               => fn() => $experiment->stop(),
				'redirect-argument' => 'nab_stopped',
			),

			'restart'       => array(
				'check-capability'  => fn() => $experiment->can_be_restarted( 'check-scope-overlap' ),
				'force-action'      => fn() => $this->get_restart_experiment_action( $experiment, 'force-restart' ),
				'run'               => fn() => $experiment->restart( 'ignore-scope-overlap' ),
				'redirect-argument' => 'nab_restarted',
			),
			'force-restart' => array(
				'check-capability'  => fn() => $experiment->can_be_restarted( 'ignore-scope-overlap' ),
				'run'               => fn() => $experiment->restart( 'ignore-scope-overlap' ),
				'redirect-argument' => 'nab_restarted',
			),

			'duplicate'     => array(
				'check-capability'  => fn() => $experiment->can_be_duplicated(),
				'run'               => fn() => $experiment->duplicate(),
				'redirect-argument' => 'nab_restarted',
			),
		);
	}
}
