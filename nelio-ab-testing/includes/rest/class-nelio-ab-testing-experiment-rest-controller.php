<?php
/**
 * This file contains the class that defines REST API endpoints for
 * experiments.
 *
 * @package    Nelio_AB_Testing
 * @subpackage Nelio_AB_Testing/includes/rest
 * @since      5.0.0
 */

defined( 'ABSPATH' ) || exit;

class Nelio_AB_Testing_Experiment_REST_Controller extends WP_REST_Controller {

	/**
	 * The single instance of this class.
	 *
	 * @since  5.0.0
	 * @var    Nelio_AB_Testing_Experiment_REST_Controller|null
	 */
	protected static $instance;

	/**
	 * Returns the single instance of this class.
	 *
	 * @return Nelio_AB_Testing_Experiment_REST_Controller the single instance of this class.
	 *
	 * @since  5.0.0
	 */
	public static function instance() {

		if ( empty( self::$instance ) ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Hooks into WordPress.
	 *
	 * @return void
	 * @since  5.0.0
	 */
	public function init() {
		add_action( 'rest_api_init', array( $this, 'register_routes' ) );
	}

	/**
	 * Register the routes for the objects of the controller.
	 *
	 * @return void
	 */
	public function register_routes() {

		register_rest_route(
			nelioab()->rest_namespace,
			'/experiment',
			array(
				array(
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => array( $this, 'create_experiment' ),
					'permission_callback' => nab_capability_checker( 'edit_nab_experiments' ),
					'args'                => array(),
				),
			)
		);

		register_rest_route(
			nelioab()->rest_namespace,
			'/experiment/(?P<id>[\d]+)',
			array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_experiment' ),
					'permission_callback' => function ( $request ) {
						/** @var array<string,mixed> $request */
						return (
							nab_capability_checker( 'edit_nab_experiments' )() ||
							nab_is_experiment_result_public( absint( $request['id'] ) )
						);
					},
					'args'                => array(),
				),
				array(
					'methods'             => WP_REST_Server::EDITABLE,
					'callback'            => array( $this, 'update_experiment' ),
					'permission_callback' => nab_capability_checker( 'edit_nab_experiments' ),
					'args'                => array(),
				),
			)
		);

		register_rest_route(
			nelioab()->rest_namespace,
			'/experiment/(?P<eid>[\d]+)/heatmap/(?P<kind>[\S]+)/(?P<aidx>[\d]+)',
			array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_heatmap_data' ),
					'permission_callback' => function ( $request ) {
						/** @var array<string,mixed> $request */
						return (
							nab_capability_checker( 'read_nab_results' )() ||
							nab_is_experiment_result_public( absint( $request['eid'] ) )
						);
					},
					'args'                => array(),
				),
			)
		);

		register_rest_route(
			nelioab()->rest_namespace,
			'/experiment/(?P<id>[\d]+)/start',
			array(
				array(
					'methods'             => WP_REST_Server::EDITABLE,
					'callback'            => array( $this, 'start_experiment' ),
					'permission_callback' => nab_capability_checker( 'start_nab_experiments' ),
					'args'                => array(
						'ignoreScopeOverlap' => array(
							'required'          => true,
							'type'              => 'boolean',
							'sanitize_callback' => fn( $v ) => ! empty( $v ),
						),
					),
				),
			)
		);

		register_rest_route(
			nelioab()->rest_namespace,
			'/experiment/(?P<id>[\d]+)/resume',
			array(
				array(
					'methods'             => WP_REST_Server::EDITABLE,
					'callback'            => array( $this, 'resume_experiment' ),
					'permission_callback' => nab_capability_checker( 'resume_nab_experiments' ),
					'args'                => array(
						'ignoreScopeOverlap' => array(
							'required'          => true,
							'type'              => 'boolean',
							'sanitize_callback' => fn( $v ) => ! empty( $v ),
						),
					),
				),
			)
		);

		register_rest_route(
			nelioab()->rest_namespace,
			'/experiment/(?P<id>[\d]+)/stop',
			array(
				array(
					'methods'             => WP_REST_Server::EDITABLE,
					'callback'            => array( $this, 'stop_experiment' ),
					'permission_callback' => nab_capability_checker( 'stop_nab_experiments' ),
					'args'                => array(),
				),
			)
		);

		register_rest_route(
			nelioab()->rest_namespace,
			'/experiment/(?P<id>[\d]+)/pause',
			array(
				array(
					'methods'             => WP_REST_Server::EDITABLE,
					'callback'            => array( $this, 'pause_experiment' ),
					'permission_callback' => nab_capability_checker( 'pause_nab_experiments' ),
					'args'                => array(),
				),
			)
		);

		register_rest_route(
			nelioab()->rest_namespace,
			'/experiment/(?P<id>[\d]+)/apply/(?P<alternative>[A-Za-z0-9-]+)',
			array(
				array(
					'methods'             => WP_REST_Server::EDITABLE,
					'callback'            => array( $this, 'apply_alternative' ),
					'permission_callback' => nab_capability_checker( 'edit_nab_experiments' ),
					'args'                => array(),
				),
			)
		);

		register_rest_route(
			nelioab()->rest_namespace,
			'/experiment/(?P<id>[\d]+)/result',
			array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_experiment_results' ),
					'permission_callback' => function ( $request ) {
						/** @var array<string,mixed> $request */
						return (
							nab_capability_checker( 'read_nab_results' )() ||
							nab_is_experiment_result_public( absint( $request['id'] ) )
						);
					},
					'args'                => array(),
				),
			)
		);

		register_rest_route(
			nelioab()->rest_namespace,
			'/experiment/(?P<id>[\d]+)/public-result-status',
			array(
				array(
					'methods'             => WP_REST_Server::EDITABLE,
					'callback'            => array( $this, 'set_public_result_status' ),
					'permission_callback' => nab_capability_checker( 'edit_nab_experiments' ),
					'args'                => array(),
				),
			)
		);

		register_rest_route(
			nelioab()->rest_namespace,
			'/experiments-running',
			array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_running_experiments' ),
					'permission_callback' => nab_capability_checker( 'edit_nab_experiments' ),
					'args'                => array(),
				),
			)
		);
	}

	/**
	 * Create a new experiment
	 *
	 * @param WP_REST_Request<array<string,mixed>> $request Full data about the request.
	 *
	 * @return WP_REST_Response|WP_Error The response
	 */
	public function create_experiment( $request ) {

		$parameters = $request->get_json_params();

		$type = is_string( $parameters['type'] ) ? $parameters['type'] : '';
		$type = trim( sanitize_text_field( $type ) );
		if ( empty( $type ) ) {
			return new WP_Error(
				'bad-request',
				_x( 'Unable to create a new test because the test type is missing.', 'text', 'nelio-ab-testing' )
			);
		}

		if ( 'nab/php' === $type && ! current_user_can( 'edit_nab_php_experiments' ) ) {
			return new WP_Error(
				'missing-capability',
				_x( 'Sorry, you are not allowed to create a PHP test.', 'text', 'nelio-ab-testing' )
			);
		}

		$experiment = nab_create_experiment( $type );
		if ( is_wp_error( $experiment ) ) {
			return new WP_Error(
				'error',
				_x( 'An unknown error occurred while trying to create the test. Please try again later.', 'user', 'nelio-ab-testing' )
			);
		}

		if ( ! empty( $parameters['addTestedPostScopeRule'] ) ) {
			$rule = array(
				'id'         => nab_uuid(),
				'attributes' => array(
					'type' => 'tested-post',
				),
			);
			$experiment->set_scope( array( $rule ) );
			$experiment->save();
		}

		return new WP_REST_Response( $this->json( $experiment ), 200 );
	}

	/**
	 * Retrieves an experiment
	 *
	 * @param WP_REST_Request<array<string,mixed>> $request Full data about the request.
	 * @return WP_REST_Response|WP_Error The response
	 */
	public function get_experiment( $request ) {

		$experiment_id = absint( $request['id'] );
		$experiment    = nab_get_experiment( $experiment_id );
		if ( is_wp_error( $experiment ) ) {
			return $experiment;
		}

		return new WP_REST_Response( $this->json( $experiment ), 200 );
	}

	/**
	 * Returns heatmap data.
	 *
	 * @param WP_REST_Request<array<string,mixed>> $request Full data about the request.
	 * @return WP_REST_Response|WP_Error The response
	 */
	public function get_heatmap_data( $request ) {
		$site_id     = nab_get_site_id();
		$experiment  = absint( $request['eid'] );
		$alternative = absint( $request['aidx'] );
		/** @var 'scroll'|'click' */
		$kind = 'scrolls' === $request['kind'] ? 'scroll' : 'click';

		$experiment = nab_get_experiment( $experiment );
		if ( is_wp_error( $experiment ) ) {
			return new WP_REST_Response( false, 200 );
		}

		$url = $this->get_local_heatmap_url( $experiment->ID, $alternative, $kind );
		if ( ! empty( $url ) ) {
			if ( 'finished' !== $experiment->get_status() ) {
				$this->remove_local_heatmap_data( $experiment->ID, $alternative, $kind );
			} else {
				return new WP_REST_Response(
					array(
						'url'  => $url,
						'more' => false,
					),
					200
				);
			}
		}

		$data = array(
			'method'    => 'GET',
			'timeout'   => absint( apply_filters( 'nab_request_timeout', 30 ) ),
			'sslverify' => ! nab_does_api_use_proxy(),
			'headers'   => array(
				'Authorization' => 'Bearer ' . nab_generate_api_auth_token(),
				'accept'        => 'application/json',
				'content-type'  => 'application/json',
			),
		);

		$url = nab_get_api_url( "/site/{$site_id}/experiment/{$experiment->ID}/hm", 'wp' );
		$url = $this->add_dates_in_url( $url, $experiment );
		$url = add_query_arg( 'alternative', $alternative, $url );
		$url = add_query_arg( 'tz', rawurlencode( nab_get_timezone() ), $url );
		$url = add_query_arg( 'kind', $kind, $url );

		$response = wp_remote_request( $url, $data );
		$result   = nab_extract_response_body( $response );
		if ( is_wp_error( $response ) ) {
			return new WP_REST_Response( false, 200 );
		}

		/** @var array<string,mixed> $result */
		if ( ! isset( $result['url'] ) || ! is_string( $result['url'] ) ) {
			return new WP_Error( 'heatmap-data-not-available' );
		}

		if ( 'finished' === $experiment->get_status() && ! $result['more'] ) {
			$this->cache_heatmap_data( $result['url'], $experiment->ID, $alternative, $kind );
		}

		return new WP_REST_Response( $result, 200 );
	}

	/**
	 * Retrieves the results of an experiment
	 *
	 * @param WP_REST_Request<array<string,mixed>> $request Full data about the request.
	 * @return WP_REST_Response|WP_Error The response
	 */
	public function get_experiment_results( $request ) {
		$result = nab_get_experiment_results( absint( $request['id'] ) );
		return is_wp_error( $result ) ? $result : new WP_REST_Response( $result->results, 200 );
	}

	/**
	 * Changes the public result status of the experiment in the database
	 *
	 * @param WP_REST_Request<array<string,mixed>> $request Full data about the request.
	 * @return WP_REST_Response|WP_Error The response
	 */
	public function set_public_result_status( $request ) {
		$experiment_id = absint( $request['id'] );

		$parameters = $request->get_json_params();
		if ( ! isset( $parameters['status'] ) ) {
			return new WP_Error(
				'bad-request',
				_x( 'Public result status is missing.', 'text', 'nelio-ab-testing' )
			);
		}

		$status = ! empty( $parameters['status'] );
		update_post_meta( $experiment_id, '_nab_is_result_public', $status );
		return new WP_REST_Response( $status, 200 );
	}

	/**
	 * Retrieves the collection of running experiments
	 *
	 * @return WP_REST_Response The response
	 */
	public function get_running_experiments() {

		$experiments = nab_get_running_experiments();

		$data = array_map(
			function ( $experiment ) {
				return $this->json( $experiment );
			},
			$experiments
		);

		return new WP_REST_Response( $data, 200 );
	}

	/**
	 * Updates an experiment
	 *
	 * @param WP_REST_Request<array<string,mixed>> $request Full data about the request.
	 * @return WP_REST_Response|WP_Error The response
	 */
	public function update_experiment( $request ) {

		$experiment_id = absint( $request['id'] );
		$parameters    = $request->get_json_params();

		$experiment = nab_get_experiment( $experiment_id );
		if ( is_wp_error( $experiment ) ) {
			return $experiment;
		}

		$can_be_edited = $experiment->can_be_edited();
		if ( is_wp_error( $can_be_edited ) ) {
			return $can_be_edited;
		}

		/** @var string */
		$value = is_string( $parameters['name'] ) ? $parameters['name'] : '';
		$experiment->set_name( $value );

		/** @var TExperiment_AI_Info */
		$value = is_array( $parameters['ai'] ) ? $parameters['ai'] : array();
		$experiment->set_ai_info( $value );

		/** @var string */
		$value = is_string( $parameters['description'] ) ? $parameters['description'] : '';
		$experiment->set_description( $value );

		/** @var string */
		$value = is_string( $parameters['status'] ) ? $parameters['status'] : 'draft';
		$experiment->set_status( $value );

		/** @var string */
		$value = is_string( $parameters['startDate'] ) ? $parameters['startDate'] : '';
		$experiment->set_start_date( $value );

		/** @var string */
		$value = is_string( $parameters['endMode'] ) ? $parameters['endMode'] : 'manual';
		$experiment->set_end_mode_and_value( $value, absint( $parameters['endValue'] ) );

		/** @var bool */
		$value = ! empty( $parameters['autoAlternativeApplication'] );
		$experiment->set_auto_alternative_application( $value );

		if ( 'nab/heatmap' !== $experiment->get_type() ) {
			/** @var list<TAlternative> */
			$value = is_array( $parameters['alternatives'] ) ? $parameters['alternatives'] : array();
			$experiment->set_alternatives( $value );

			/** @var list<TGoal> */
			$value = is_array( $parameters['goals'] ) ? $parameters['goals'] : array();
			$experiment->set_goals( $value );

			/** @var list<TSegment> */
			$value = is_array( $parameters['segments'] ) ? $parameters['segments'] : array();
			$experiment->set_segments( $value );

			/** @var string */
			$value = is_string( $parameters['segmentEvaluation'] ) ? $parameters['segmentEvaluation'] : '';
			$experiment->set_segment_evaluation( $value );

			/** @var list<TScope_Rule> */
			$value = is_array( $parameters['scope'] ) ? $parameters['scope'] : array();
			$experiment->set_scope( $value );
		} else {
			/** @var Nelio_AB_Testing_Heatmap */
			$heatmap = $experiment;

			/** @var 'post'|'url' */
			$value = is_string( $parameters['trackingMode'] ) && 'post' === $parameters['trackingMode'] ? 'post' : 'url';
			$heatmap->set_tracking_mode( $value );

			/** @var int */
			$value = is_numeric( $parameters['trackedPostId'] ) ? absint( $parameters['trackedPostId'] ) : 0;
			$heatmap->set_tracked_post_id( $value );

			/** @var string */
			$value = is_string( $parameters['trackedPostType'] ) ? $parameters['trackedPostType'] : '';
			$heatmap->set_tracked_post_type( $value );

			/** @var string */
			$value = is_string( $parameters['trackedUrl'] ) ? $parameters['trackedUrl'] : '';
			$heatmap->set_tracked_url( $value );

			/** @var list<TSegmentation_Rule> */
			$value = is_array( $parameters['participationConditions'] ) ? $parameters['participationConditions'] : array();
			$heatmap->set_participation_conditions( $value );
		}

		$experiment->save();
		$experiment = nab_get_experiment( $experiment_id );
		if ( is_wp_error( $experiment ) ) {
			return $experiment;
		}

		return new WP_REST_Response( $this->json( $experiment ), 200 );
	}

	/**
	 * Start an experiment
	 *
	 * @param WP_REST_Request<array<string,mixed>> $request Full data about the request.
	 * @return WP_REST_Response|WP_Error The response
	 */
	public function start_experiment( $request ) {

		$experiment_id = absint( $request['id'] );
		$experiment    = nab_get_experiment( $experiment_id );
		if ( is_wp_error( $experiment ) ) {
			return $experiment;
		}

		$ignore_scope = $request['ignoreScopeOverlap'];
		$started      = $experiment->start( $ignore_scope ? 'ignore-scope-overlap' : 'check-scope-overlap' );
		if ( is_wp_error( $started ) ) {
			return $started;
		}

		return new WP_REST_Response( $this->json( $experiment ), 200 );
	}

	/**
	 * Resumes an experiment
	 *
	 * @param WP_REST_Request<array<string,mixed>> $request Full data about the request.
	 * @return WP_REST_Response|WP_Error The response
	 */
	public function resume_experiment( $request ) {

		$experiment_id = absint( $request['id'] );
		$experiment    = nab_get_experiment( $experiment_id );
		if ( is_wp_error( $experiment ) ) {
			return $experiment;
		}

		$ignore_scope = $request['ignoreScopeOverlap'];
		$resumed      = $experiment->resume( $ignore_scope ? 'ignore-scope-overlap' : 'check-scope-overlap' );
		if ( is_wp_error( $resumed ) ) {
			return $resumed;
		}

		return new WP_REST_Response( $this->json( $experiment ), 200 );
	}

	/**
	 * Stop an experiment
	 *
	 * @param WP_REST_Request<array<string,mixed>> $request Full data about the request.
	 * @return WP_REST_Response|WP_Error The response
	 */
	public function stop_experiment( $request ) {

		$experiment_id = absint( $request['id'] );
		$experiment    = nab_get_experiment( $experiment_id );
		if ( is_wp_error( $experiment ) ) {
			return $experiment;
		}

		$stopped = $experiment->stop();
		if ( is_wp_error( $stopped ) ) {
			return $stopped;
		}

		return new WP_REST_Response( $this->json( $experiment ), 200 );
	}

	/**
	 * Pauses an experiment
	 *
	 * @param WP_REST_Request<array<string,mixed>> $request Full data about the request.
	 * @return WP_REST_Response|WP_Error The response
	 */
	public function pause_experiment( $request ) {

		$experiment_id = absint( $request['id'] );
		$experiment    = nab_get_experiment( $experiment_id );
		if ( is_wp_error( $experiment ) ) {
			return $experiment;
		}

		$paused = $experiment->pause();
		if ( is_wp_error( $paused ) ) {
			return $paused;
		}

		return new WP_REST_Response( $this->json( $experiment ), 200 );
	}

	/**
	 * Applies the given alternative.
	 *
	 * @param WP_REST_Request<array<string,mixed>> $request Full data about the request.
	 * @return WP_REST_Response|WP_Error The response
	 */
	public function apply_alternative( $request ) {

		$experiment_id = absint( $request['id'] );
		$experiment    = nab_get_experiment( $experiment_id );
		if ( is_wp_error( $experiment ) ) {
			return $experiment;
		}

		$alternative_id = $request['alternative'];
		if ( ! is_string( $alternative_id ) ) {
			return new WP_Error( 'invalid-alternative-id', _x( 'Invalid alternative ID', 'text', 'nelio-ab-testing' ) );
		}

		$result = $experiment->apply_alternative( $alternative_id );
		if ( is_wp_error( $result ) ) {
			return $result;
		}

		return new WP_REST_Response( $this->json( $experiment ), 200 );
	}

	/**
	 * Adds experiment start/end params to the URL.
	 *
	 * @param string                      $url URL.
	 * @param Nelio_AB_Testing_Experiment $experiment Experiment.
	 *
	 * @return string
	 */
	private function add_dates_in_url( $url, $experiment ) {
		$start = $experiment->get_start_date();
		if ( is_string( $start ) ) {
			$url = add_query_arg( 'start', rawurlencode( $start ), $url );
		}

		$end = $experiment->get_end_date();
		if ( is_string( $end ) && 'finished' === $experiment->get_status() ) {
			$url = add_query_arg( 'end', rawurlencode( $end ), $url );
		}

		return add_query_arg( 'tz', rawurlencode( nab_get_timezone() ), $url );
	}

	/**
	 * Converts the experiment into a JSON-ready array.
	 *
	 * @param Nelio_AB_Testing_Experiment $experiment The experiment.
	 *
	 * @return array<string,mixed> The JSON.
	 */
	public function json( $experiment ) {

		$data = array(
			'id'          => $experiment->get_id(),
			'name'        => $experiment->get_name(),
			'description' => $experiment->get_description(),
			'status'      => $experiment->get_status(),
			'type'        => $experiment->get_type(),
			'startDate'   => $experiment->get_start_date(),
			'endDate'     => $experiment->get_end_date(),
			'endMode'     => $experiment->get_end_mode(),
			'endValue'    => $experiment->get_end_value(),
			'links'       => array(
				'preview' => $experiment->get_preview_url(),
				'edit'    => $experiment->get_url(),
			),
		);

		$ai_info = $experiment->get_ai_info();
		if ( ! empty( $ai_info ) ) {
			$data['ai'] = $ai_info;
		}

		if ( 'nab/heatmap' === $experiment->get_type() ) {
			/** @var Nelio_AB_Testing_Heatmap $experiment */
			$data = array_merge(
				$data,
				array(
					'trackingMode'            => $experiment->get_tracking_mode(),
					'trackedPostId'           => $experiment->get_tracked_post_id(),
					'trackedPostType'         => $experiment->get_tracked_post_type(),
					'trackedUrl'              => $experiment->get_tracked_url(),
					'participationConditions' => $experiment->get_participation_conditions(),
				)
			);

			$data['links']['heatmap'] = $experiment->get_heatmap_url();
			return $data;
		}

		$data['autoAlternativeApplication'] = $experiment->is_auto_alternative_application_enabled();

		$data['alternatives'] = $experiment->get_alternatives();

		$data['goals'] = array_map(
			function ( $goal ) {
				/** @var TGoal $goal */
				$goal['conversionActions'] = array_map(
					array( $this, 'fix_scope_in_conversion_action' ),
					$goal['conversionActions']
				);
				return $goal;
			},
			$experiment->get_goals()
		);

		$segments                  = $experiment->get_segments();
		$segments                  = ! empty( $segments ) ? $segments : array();
		$data['segments']          = $segments;
		$data['segmentEvaluation'] = $experiment->get_segment_evaluation();

		$scope = $experiment->get_scope();
		if ( ! empty( $scope ) ) {
			$data['scope'] = $scope;
		}

		return $data;
	}

	/**
	 * Callback to fix scope in conversion action.
	 *
	 * @param TConversion_Action $action Conversion action.
	 *
	 * @return TConversion_Action
	 */
	public function fix_scope_in_conversion_action( $action ) {
		$action_type = $action['scope']['type'];
		if ( 'php-function' === $action_type ) {
			$action['scope'] = array( 'type' => 'php-function' );
		}

		$settings      = Nelio_AB_Testing_Settings::instance();
		$goal_tracking = $settings->get( 'goal_tracking' );
		if ( 'custom' !== $goal_tracking ) {
			$action['scope'] = array( 'type' => $goal_tracking );
		}

		return $action;
	}

	/**
	 * Gets local heatmap path.
	 *
	 * @param int              $experiment_id     Experiment ID.
	 * @param int              $alternative_index Alternative index.
	 * @param 'scroll'|'click' $kind              Data kind.
	 *
	 * @return string
	 */
	private function get_local_heatmap_path( $experiment_id, $alternative_index, $kind ) {
		return "/nab/heatmaps/{$experiment_id}-{$alternative_index}-{$kind}s.jsonl";
	}

	/**
	 * Gets heatmap data.
	 *
	 * @param int              $experiment_id     Experiment ID.
	 * @param int              $alternative_index Alternative index.
	 * @param 'scroll'|'click' $kind              Data kind.
	 *
	 * @return string|false
	 */
	private function get_local_heatmap_url( $experiment_id, $alternative_index, $kind ) {
		$path = $this->get_local_heatmap_path( $experiment_id, $alternative_index, $kind );
		if ( ! file_exists( WP_CONTENT_DIR . $path ) ) {
			return false;
		}
		return content_url( $path );
	}

	/**
	 * Caches heatmap data.
	 *
	 * @param string           $url               Data URL.
	 * @param int              $experiment_id     Experiment ID.
	 * @param int              $alternative_index Alternative index.
	 * @param 'scroll'|'click' $kind              Data kind.
	 *
	 * @return void
	 */
	private function cache_heatmap_data( $url, $experiment_id, $alternative_index, $kind ) {
		/** @var WP_Filesystem_Base */
		global $wp_filesystem;
		nab_require_wp_file( '/wp-admin/includes/file.php' );
		WP_Filesystem();
		$wp_filesystem->mkdir( WP_CONTENT_DIR . '/nab' );
		$wp_filesystem->mkdir( WP_CONTENT_DIR . '/nab/heatmaps' );
		$tmp = download_url( $url );
		if ( ! is_wp_error( $tmp ) ) {
			$dest = WP_CONTENT_DIR . $this->get_local_heatmap_path( $experiment_id, $alternative_index, $kind );
			$wp_filesystem->move( $tmp, $dest, true );
		}
	}

	/**
	 * Removes local heatmap data.
	 *
	 * @param int              $experiment_id     Experiment ID.
	 * @param int              $alternative_index Alternative index.
	 * @param 'scroll'|'click' $kind              Data kind.
	 *
	 * @return void
	 */
	private function remove_local_heatmap_data( $experiment_id, $alternative_index, $kind ) {
		/** @var WP_Filesystem_Base */
		global $wp_filesystem;
		nab_require_wp_file( '/wp-admin/includes/file.php' );
		WP_Filesystem();
		$file = WP_CONTENT_DIR . $this->get_local_heatmap_path( $experiment_id, $alternative_index, $kind );
		$wp_filesystem->delete( $file );
	}
}
