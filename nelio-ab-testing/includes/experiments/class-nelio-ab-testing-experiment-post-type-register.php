<?php
/**
 * This file contains a class for registering the Experiment post type.
 *
 * @package    Nelio_AB_Testing
 * @subpackage Nelio_AB_Testing/includes/utils
 * @since      5.0.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * This class registers the Experiment post type and its statuses.
 *
 * @package    Nelio_AB_Testing
 * @subpackage Nelio_AB_Testing/includes/utils
 * @since      5.0.0
 */
class Nelio_AB_Testing_Experiment_Post_Type_Register {

	/**
	 * The single instance of this class.
	 *
	 * @since  5.0.0
	 * @var    Nelio_AB_Testing_Experiment_Post_Type_Register
	 */
	protected static $instance;

	/**
	 * Returns the single instance of this class.
	 *
	 * @return Nelio_AB_Testing_Experiment_Post_Type_Register the single instance of this class.
	 *
	 * @since  5.0.0
	 */
	public static function instance() {

		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
		}//end if

		return self::$instance;
	}//end instance()

	/**
	 * Hooks into WordPress.
	 *
	 * @since  5.0.0
	 */
	public function init() {

		add_action( 'init', array( $this, 'register_post_types' ), 5 );
		add_action( 'init', array( $this, 'register_post_statuses' ), 9 );

		add_filter( 'post_updated_messages', array( $this, 'get_update_messages_for_an_experiment' ) );
		add_filter( 'bulk_post_updated_messages', array( $this, 'get_bulk_update_messages_for_an_experiment' ), 10, 2 );

		add_filter( 'get_edit_post_link', array( $this, 'get_edit_experiment_link' ), 10, 2 );

		add_action( 'nab_stop_experiment', array( $this, 'save_results_on_stop' ) );
		add_action( 'before_delete_post', array( $this, 'on_before_delete_post' ), 9 );
	}//end init()

	/**
	 * Returns the proper edit link for an experiment, assuming $post_id is an experiment.
	 *
	 * @param string $link    the current link.
	 * @param int    $post_id the post (or experiment) whose edit link we want.
	 *
	 * @return string the link we want.
	 *
	 * @since  5.0.0
	 */
	public function get_edit_experiment_link( $link, $post_id ) {

		if ( 'nab_experiment' !== get_post_type( $post_id ) ) {
			return $link;
		}//end if

		if ( in_array( get_post_status( $post_id ), array( 'nab_running', 'nab_finished' ), true ) ) {
			$page = 'nelio-ab-testing-experiment-view';
		} else {
			$page = 'nelio-ab-testing-experiment-edit';
		}//end if

		return add_query_arg(
			array(
				'page'       => $page,
				'experiment' => $post_id,
			),
			admin_url( 'admin.php' )
		);
	}//end get_edit_experiment_link()

	/**
	 * Callback for registering the Experiment post type.
	 *
	 * @since  5.0.0
	 */
	public function register_post_types() {

		if ( ! nab_get_site_id() ) {
			return;
		}//end if

		if ( post_type_exists( 'nab_experiment' ) ) {
			return;
		}//end if

		/**
		 * This action fires right before registering the “Experiment” post type.
		 *
		 * @since 5.0.0
		 */
		do_action( 'nab_register_post_types' );

		$args = array(
			'labels'       => array(
				'name'               => _x( 'Tests', 'text', 'nelio-ab-testing' ),
				'singular_name'      => _x( 'Test', 'text', 'nelio-ab-testing' ),
				'menu_name'          => _x( 'Test', 'text', 'nelio-ab-testing' ),
				'all_items'          => _x( 'Tests', 'text (admin menu)', 'nelio-ab-testing' ),
				'add_new'            => _x( 'Add Test', 'command', 'nelio-ab-testing' ),
				'add_new_item'       => _x( 'Add Test', 'command', 'nelio-ab-testing' ),
				'edit_item'          => _x( 'Edit Test', 'command', 'nelio-ab-testing' ),
				'new_item'           => _x( 'New Test', 'command', 'nelio-ab-testing' ),
				'search_items'       => _x( 'Search Tests', 'command', 'nelio-ab-testing' ),
				'not_found'          => _x( 'No tests found', 'text', 'nelio-ab-testing' ),
				'not_found_in_trash' => _x( 'No tests found in trash', 'text', 'nelio-ab-testing' ),
			),
			'can_export'   => true,
			'capabilities' => array(
				'create_posts'           => 'do_not_allow',
				// Meta capabilities.
				'edit_post'              => 'edit_nab_experiments',
				'read_post'              => 'do_not_allow',
				'delete_post'            => 'delete_nab_experiments',
				// Primitive capabilities used outside of map_meta_cap().
				'edit_posts'             => 'edit_nab_experiments',
				'edit_others_posts'      => 'edit_nab_experiments',
				'delete_posts'           => 'delete_nab_experiments',
				'publish_posts'          => 'do_not_allow',
				'read_private_posts'     => 'do_not_allow',
				// Primitive capabilities used within map_meta_cap().
				'read'                   => 'do_not_allow',
				'delete_private_posts'   => 'do_not_allow',
				'delete_published_posts' => 'do_not_allow',
				'delete_others_posts'    => 'delete_nab_experiments',
				'edit_private_posts'     => 'do_not_allow',
				'edit_published_posts'   => 'do_not_allow',
			),
			'hierarchical' => false,
			'map_meta_cap' => false,
			'public'       => false,
			'query_var'    => false,
			'rewrite'      => false,
			'show_in_menu' => false,
			'show_in_rest' => false,
			'show_ui'      => true,
			'supports'     => array( 'title' ),
		);

		/**
		 * Filters the args of the “Experiment” post type.
		 *
		 * The Experiment post type defined by Nelio A/B Testing is `nab_experiment`.
		 *
		 * @param array $args The arguments, as defined in WordPress function `register_post_type`.
		 *
		 * @since 5.0.0
		 */
		$args = apply_filters( 'nab_register_experiment_post_type', $args );
		register_post_type( 'nab_experiment', $args );
	}//end register_post_types()

	/**
	 * This function registers all possible experiment statuses:
	 *
	 *  * Complete.   The experiment contains all the required information.
	 *  * Improvable. Some information is missing.
	 *  * Pending.    Information has never been automatically loaded. In other
	 *                words, it's the status in which a experiment is created.
	 *  * Broken.     Last time we looked at the link, it returned a 404.
	 *  * Check.      XXX.
	 *
	 * @since  5.0.0
	 */
	public function register_post_statuses() {

		$args = array(
			'protected'   => true,
			'label'       => _x( 'Ready', 'text (experiment status)', 'nelio-ab-testing' ),
			/* translators: experiment count */
			'label_count' => _nx_noop( 'Ready <span class="count">(%s)</span>', 'Ready <span class="count">(%s)</span>', 'text (experiment status)', 'nelio-ab-testing' ),
		);
		register_post_status( 'nab_ready', $args );

		$args = array(
			'protected'   => true,
			'label'       => _x( 'Scheduled', 'text (experiment status)', 'nelio-ab-testing' ),
			/* translators: experiment count */
			'label_count' => _nx_noop( 'Scheduled <span class="count">(%s)</span>', 'Scheduled <span class="count">(%s)</span>', 'text (experiment status)', 'nelio-ab-testing' ),
		);
		register_post_status( 'nab_scheduled', $args );

		$args = array(
			'protected'   => true,
			'label'       => _x( 'Running', 'text (experiment status)', 'nelio-ab-testing' ),
			/* translators: experiment count */
			'label_count' => _nx_noop( 'Running <span class="count">(%s)</span>', 'Running <span class="count">(%s)</span>', 'text (experiment status)', 'nelio-ab-testing' ),
		);
		register_post_status( 'nab_running', $args );

		$args = array(
			'protected'   => true,
			'label'       => _x( 'Paused', 'text (experiment status)', 'nelio-ab-testing' ),
			/* translators: experiment count */
			'label_count' => _nx_noop( 'Paused <span class="count">(%s)</span>', 'Paused <span class="count">(%s)</span>', 'text (experiment status)', 'nelio-ab-testing' ),
		);
		register_post_status( 'nab_paused', $args );

		$args = array(
			'protected'   => true,
			'label'       => _x( 'Paused Draft', 'text (experiment status)', 'nelio-ab-testing' ),
			/* translators: experiment count */
			'label_count' => _nx_noop( 'Paused Draft <span class="count">(%s)</span>', 'Paused Draft <span class="count">(%s)</span>', 'text (experiment status)', 'nelio-ab-testing' ),
		);
		register_post_status( 'nab_paused_draft', $args );

		$args = array(
			'protected'   => true,
			'label'       => _x( 'Finished', 'text (experiment status)', 'nelio-ab-testing' ),
			/* translators: experiment count */
			'label_count' => _nx_noop( 'Finished <span class="count">(%s)</span>', 'Finished <span class="count">(%s)</span>', 'text (experiment status)', 'nelio-ab-testing' ),
		);
		register_post_status( 'nab_finished', $args );
	}//end register_post_statuses()

	/**
	 * Modifies the messages for the experiment post type.
	 *
	 * @param array $messages the messages that might be shown to a user when a post is saved.
	 *
	 * @return array the messages that might be shown to a user when a post is saved.
	 *
	 * @since  5.0.0
	 */
	public function get_update_messages_for_an_experiment( $messages ) {

		$messages['nab_experiment'][1]  = _x( 'Test updated.', 'text', 'nelio-ab-testing' );
		$messages['nab_experiment'][4]  = _x( 'Test updated.', 'text', 'nelio-ab-testing' );
		$messages['nab_experiment'][7]  = _x( 'Test saved.', 'text', 'nelio-ab-testing' );
		$messages['nab_experiment'][10] = _x( 'Test updated.', 'text', 'nelio-ab-testing' );

		return $messages;
	}//end get_update_messages_for_an_experiment()

	/**
	 * Modifies the messages for the experiment post type.
	 *
	 * @param array $messages    the messages that might be shown to a user when a post is saved.
	 * @param array $bulk_counts array of item counts for each message, used to build internationalized strings.
	 *
	 * @return array the messages that might be shown to a user when a post is saved.
	 *
	 * @since  5.0.0
	 */
	public function get_bulk_update_messages_for_an_experiment( $messages, $bulk_counts ) {

		$messages['nab_experiment'] = array(
			/* translators: number of tests updated */
			'updated'   => _nx( '%s test updated.', '%s tests updated.', $bulk_counts['updated'], 'text', 'nelio-ab-testing' ),
			/* translators: number of tests not updated */
			'locked'    => _nx( '%s test not updated, somebody is editing it.', '%s tests not updated, somebody is editing them.', $bulk_counts['locked'], 'text', 'nelio-ab-testing' ),
			/* translators: number of tests permanently deleted */
			'deleted'   => _nx( '%s test permanently deleted.', '%s tests permanently deleted.', $bulk_counts['deleted'], 'text', 'nelio-ab-testing' ),
			/* translators: number of tests moved to the Trash */
			'trashed'   => _nx( '%s test moved to the Trash.', '%s tests moved to the Trash.', $bulk_counts['trashed'], 'text', 'nelio-ab-testing' ),
			/* translators: number of tests restored from the Trash */
			'untrashed' => _nx( '%s test restored from the Trash.', '%s tests restored from the Trash.', $bulk_counts['untrashed'], 'text', 'nelio-ab-testing' ),
		);

		return $messages;
	}//end get_bulk_update_messages_for_an_experiment()

	/**
	 * Retrieves the latest resutls available of the given experiment.
	 *
	 * @param Nelio_AB_Testing_Experiment $experiment the experiment.
	 *
	 * @since  5.0.0
	 */
	public function save_results_on_stop( $experiment ) {
		// Simulate a request to view the results, which effectively saves them in the database as a post meta.
		nab_get_experiment_results( $experiment );
	}//end save_results_on_stop()

	/**
	 * Checks if the current post we're about to delete is an experiment and, if
	 * it is, it makes sure all its related information is removed too.
	 *
	 * @param integer $post_id the post we're about to delete.
	 *
	 * @since  5.0.0
	 */
	public function on_before_delete_post( $post_id ) {

		if ( 'nab_experiment' !== get_post_type( $post_id ) ) {
			return;
		}//end if

		$experiment = nab_get_experiment( $post_id );
		if ( is_wp_error( $experiment ) ) {
			return;
		}//end if

		$experiment->delete_related_information();
	}//end on_before_delete_post()
}//end class
