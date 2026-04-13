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
					'args'                => array(
						'type'                   => array(
							'description'       => 'Experiment type',
							'required'          => true,
							'sanitize_callback' => fn( $v ) => trim( sanitize_text_field( $v ) ),
							'validate_callback' => fn( $v ) => ! empty( $v ),
						),
						'addTestedPostScopeRule' => array(
							'description'       => 'Whether to include a tested-post scope rule in the new test or not.',
							'sanitize_callback' => fn( $v ) => ! empty( $v ),
						),
					),
				),
			)
		);

		register_rest_route(
			nelioab()->rest_namespace,
			'/experiment/(?P<eid>[\d]+)',
			array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_experiment' ),
					'permission_callback' => array( $this, 'can_view_experiment' ),
				),
				array(
					'methods'             => WP_REST_Server::EDITABLE,
					'callback'            => array( $this, 'update_experiment' ),
					'permission_callback' => array( $this, 'can_edit_experiment' ),
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
					'permission_callback' => array( $this, 'can_view_results' ),
				),
			)
		);

		register_rest_route(
			nelioab()->rest_namespace,
			'/experiment/(?P<eid>[\d]+)/start',
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
			'/experiment/(?P<eid>[\d]+)/resume',
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
			'/experiment/(?P<eid>[\d]+)/stop',
			array(
				array(
					'methods'             => WP_REST_Server::EDITABLE,
					'callback'            => array( $this, 'stop_experiment' ),
					'permission_callback' => nab_capability_checker( 'stop_nab_experiments' ),
				),
			)
		);

		register_rest_route(
			nelioab()->rest_namespace,
			'/experiment/(?P<eid>[\d]+)/pause',
			array(
				array(
					'methods'             => WP_REST_Server::EDITABLE,
					'callback'            => array( $this, 'pause_experiment' ),
					'permission_callback' => nab_capability_checker( 'pause_nab_experiments' ),
				),
			)
		);

		register_rest_route(
			nelioab()->rest_namespace,
			'/experiment/(?P<eid>[\d]+)/apply/(?P<alternative>[A-Za-z0-9-]+)',
			array(
				array(
					'methods'             => WP_REST_Server::EDITABLE,
					'callback'            => array( $this, 'apply_alternative' ),
					'permission_callback' => nab_capability_checker( 'edit_nab_experiments' ),
				),
			)
		);

		register_rest_route(
			nelioab()->rest_namespace,
			'/experiment/(?P<eid>[\d]+)/result',
			array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_experiment_results' ),
					'permission_callback' => array( $this, 'can_view_results' ),
				),
			)
		);

		register_rest_route(
			nelioab()->rest_namespace,
			'/experiment/(?P<eid>[\d]+)/public-result-status',
			array(
				array(
					'methods'             => WP_REST_Server::EDITABLE,
					'callback'            => array( $this, 'set_public_result_status' ),
					'permission_callback' => nab_capability_checker( 'edit_nab_experiments' ),
					'args'                => array(
						'status' => array(
							'type'              => 'boolean',
							'sanitize_callback' => fn( $v ) => ! empty( $v ),
						),
					),
				),
			)
		);
	}

	/**
	 * Create a new experiment
	 *
	 * @param WP_REST_Request<array<string,mixed>> $request Full data about the request.
	 *
	 * @return array<string,mixed>|WP_Error
	 */
	public function create_experiment( $request ) {

		/** @var string */
		$type = $request['type'];

		if ( 'nab/php' === $type && ! current_user_can( 'edit_nab_php_experiments' ) ) {
			return new WP_Error(
				'missing-capability',
				_x( 'Sorry, you are not allowed to create a PHP test.', 'text', 'nelio-ab-testing' )
			);
		}

		$experiment = nab_create_experiment( $type );
		if ( is_wp_error( $experiment ) ) {
			return $experiment; // @codeCoverageIgnore
		}

		if ( ! empty( $request['addTestedPostScopeRule'] ) ) {
			$rule = array(
				'id'         => nab_uuid(),
				'attributes' => array(
					'type' => 'tested-post',
				),
			);
			$experiment->set_scope( array( $rule ) );
			$experiment->save();
		}

		return $experiment->json();
	}

	/**
	 * Permission callback to determine if user can view experiment.
	 *
	 * @param WP_REST_Request<array<string,mixed>> $request Full data about the request.
	 *
	 * @return bool|WP_Error
	 */
	public function can_view_experiment( $request ) {
		$experiment_id = absint( $request['eid'] );

		$experiment = nab_get_experiment( $experiment_id );
		if ( is_wp_error( $experiment ) ) {
			return $experiment; // @codeCoverageIgnore
		}

		$status = $experiment->get_status();
		if ( ! in_array( $status, array( 'running', 'finished' ), true ) ) {
			return nab_capability_checker( 'edit_nab_experiments' )();
		}

		return (
			nab_capability_checker( 'read_nab_results' )() ||
			$experiment->has_public_results()
		);
	}

	/**
	 * Retrieves an experiment
	 *
	 * @param WP_REST_Request<array{eid:string}> $request Full data about the request.
	 *
	 * @return array<string,mixed>
	 */
	public function get_experiment( $request ) {
		$experiment_id = absint( $request['eid'] );
		$experiment    = nab_get_experiment( $experiment_id );
		assert( ! is_wp_error( $experiment ) );
		return $experiment->json();
	}

	/**
	 * Permission callback to determine if user can view experiment results.
	 *
	 * @param WP_REST_Request<array<string,mixed>> $request Full data about the request.
	 *
	 * @return bool|WP_Error
	 */
	public function can_view_results( $request ) {
		$experiment_id = absint( $request['eid'] );

		$experiment = nab_get_experiment( $experiment_id );
		if ( is_wp_error( $experiment ) ) {
			return $experiment; // @codeCoverageIgnore
		}

		$status = $experiment->get_status();
		if ( ! in_array( $status, array( 'running', 'finished' ), true ) ) {
			return false;
		}

		return (
			nab_capability_checker( 'read_nab_results' )() ||
			$experiment->has_public_results()
		);
	}

	/**
	 * Returns heatmap data.
	 *
	 * @param WP_REST_Request<array<string,mixed>> $request Full data about the request.
	 *
	 * @return array{url:string,more:bool}|false|WP_Error
	 */
	public function get_heatmap_data( $request ) {
		$site_id     = nab_get_site_id();
		$experiment  = absint( $request['eid'] );
		$alternative = absint( $request['aidx'] );
		/** @var 'scroll'|'click' */
		$kind = 'scrolls' === $request['kind'] ? 'scroll' : 'click';

		$experiment = nab_get_experiment( $experiment );
		assert( ! is_wp_error( $experiment ) );

		$url = $this->get_local_heatmap_url( $experiment->ID, $alternative, $kind );
		if ( ! empty( $url ) ) {
			if ( 'finished' !== $experiment->get_status() ) {
				$this->remove_local_heatmap_data( $experiment->ID, $alternative, $kind );
			} else {
				return array(
					'url'  => $url,
					'more' => false,
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
		if ( is_wp_error( $result ) ) {
			return false;
		}

		/** @var array<string,mixed> $result */
		if ( ! isset( $result['url'] ) || ! is_string( $result['url'] ) ) {
			return new WP_Error( 'heatmap-data-not-available' );
		}

		if ( 'finished' === $experiment->get_status() && ! $result['more'] ) {
			$this->cache_heatmap_data( $result['url'], $experiment->ID, $alternative, $kind );
		}

		$result['more'] = ! empty( $result['more'] );
		return $result;
	}

	/**
	 * Retrieves the results of an experiment
	 *
	 * @param WP_REST_Request<array<string,mixed>> $request Full data about the request.
	 *
	 * @return TAWS_Experiment_Results|null|WP_Error
	 */
	public function get_experiment_results( $request ) {
		// @codeCoverageIgnoreStart
		$result = nab_get_experiment_results( absint( $request['eid'] ) );
		return is_wp_error( $result ) ? $result : $result->results;
		// @codeCoverageIgnoreEnd
	}

	/**
	 * Changes the public result status of the experiment in the database
	 *
	 * @param WP_REST_Request<array<string,mixed>> $request Full data about the request.
	 *
	 * @return bool|WP_Error
	 */
	public function set_public_result_status( $request ) {
		$experiment_id = absint( $request['eid'] );
		$status        = ! empty( $request['status'] );

		$experiment = nab_get_experiment( $experiment_id );
		if ( is_wp_error( $experiment ) ) {
			return $experiment; // @codeCoverageIgnore
		}

		$experiment->mark_results_as_public( $status );
		$experiment->save();
		return $status;
	}

	/**
	 * Whether the experiment can be edited or not.
	 *
	 * @param WP_REST_Request<array<string,mixed>> $request Full data about the request.
	 *
	 * @return true|WP_Error
	 */
	public function can_edit_experiment( $request ) {
		$experiment_id = absint( $request['eid'] );

		$experiment = nab_get_experiment( $experiment_id );
		if ( is_wp_error( $experiment ) ) {
			return $experiment; // @codeCoverageIgnore
		}

		$can_be_edited = $experiment->can_be_edited();
		if ( is_wp_error( $can_be_edited ) ) {
			return $can_be_edited;
		}

		return true;
	}

	/**
	 * Updates an experiment
	 *
	 * @param WP_REST_Request<array<string,mixed>> $request Full data about the request.
	 *
	 * @return array<string,mixed>|WP_Error
	 */
	public function update_experiment( $request ) {

		$experiment_id = absint( $request['eid'] );
		$parameters    = $request->get_json_params();

		$experiment = nab_get_experiment( $experiment_id );
		assert( ! is_wp_error( $experiment ) );

		/** @var string */
		$value = is_string( $parameters['name'] ?? null ) ? $parameters['name'] : '';
		$experiment->set_name( $value );

		/** @var TExperiment_AI_Info */
		$value = is_array( $parameters['ai'] ?? null ) ? $parameters['ai'] : array();
		$experiment->set_ai_info( $value );

		/** @var string */
		$value = is_string( $parameters['description'] ?? null ) ? $parameters['description'] : '';
		$experiment->set_description( $value );

		/** @var string */
		$value = is_string( $parameters['status'] ?? null ) ? $parameters['status'] : 'draft';
		$experiment->set_status( $value );

		/** @var string */
		$value = is_string( $parameters['startDate'] ?? null ) ? $parameters['startDate'] : '';
		$experiment->set_start_date( $value );

		/** @var string */
		$value = is_string( $parameters['endMode'] ?? null ) ? $parameters['endMode'] : 'manual';
		$value = in_array( $value, array( 'confidence', 'duration', 'manual', 'page-views' ), true ) ? $value : 'manual';
		$experiment->set_end_mode_and_value( $value, absint( $parameters['endValue'] ?? null ) );

		/** @var bool */
		$value = ! empty( $parameters['autoAlternativeApplication'] );
		$experiment->set_auto_alternative_application( $value );

		if ( 'nab/heatmap' !== $experiment->get_type() ) {
			/** @var list<TAlternative> */
			$value = is_array( $parameters['alternatives'] ?? null ) ? $parameters['alternatives'] : array();
			$experiment->set_alternatives( $value );

			/** @var list<TGoal> */
			$value = is_array( $parameters['goals'] ?? null ) ? $parameters['goals'] : array();
			$experiment->set_goals( $value );

			/** @var list<TSegment> */
			$value = is_array( $parameters['segments'] ?? null ) ? $parameters['segments'] : array();
			$experiment->set_segments( $value );

			/** @var string */
			$value = is_string( $parameters['segmentEvaluation'] ?? null ) ? $parameters['segmentEvaluation'] : '';
			$value = in_array( $value, array( 'site', 'tested-page' ), true ) ? $value : 'site';
			$experiment->set_segment_evaluation( $value );

			/** @var list<TScope_Rule> */
			$value = is_array( $parameters['scope'] ?? null ) ? $parameters['scope'] : array();
			$experiment->set_scope( $value );
		} else {
			/** @var Nelio_AB_Testing_Heatmap */
			$heatmap = $experiment;

			/** @var 'post'|'url' */
			$value = is_string( $parameters['trackingMode'] ?? null ) && 'post' === $parameters['trackingMode'] ? 'post' : 'url';
			$heatmap->set_tracking_mode( $value );

			/** @var int */
			$value = is_numeric( $parameters['trackedPostId'] ?? null ) ? absint( $parameters['trackedPostId'] ) : 0;
			$heatmap->set_tracked_post_id( $value );

			/** @var string */
			$value = is_string( $parameters['trackedPostType'] ?? null ) ? $parameters['trackedPostType'] : '';
			$heatmap->set_tracked_post_type( $value );

			/** @var string */
			$value = is_string( $parameters['trackedUrl'] ?? null ) ? $parameters['trackedUrl'] : '';
			$heatmap->set_tracked_url( $value );

			/** @var list<TSegmentation_Rule> */
			$value = is_array( $parameters['participationConditions'] ?? null ) ? $parameters['participationConditions'] : array();
			$heatmap->set_participation_conditions( $value );
		}

		$experiment->save();
		$experiment = nab_get_experiment( $experiment_id );
		assert( ! is_wp_error( $experiment ) );

		return $experiment->json();
	}

	/**
	 * Start an experiment
	 *
	 * @param WP_REST_Request<array<string,mixed>> $request Full data about the request.
	 *
	 * @return array<string,mixed>|WP_Error
	 */
	public function start_experiment( $request ) {

		$experiment_id = absint( $request['eid'] );
		$experiment    = nab_get_experiment( $experiment_id );
		if ( is_wp_error( $experiment ) ) {
			return $experiment; // @codeCoverageIgnore
		}

		$ignore_scope = $request['ignoreScopeOverlap'];
		$started      = $experiment->start( $ignore_scope ? 'ignore-scope-overlap' : 'check-scope-overlap' );
		if ( is_wp_error( $started ) ) {
			return $started;
		}

		return $experiment->json();
	}

	/**
	 * Resumes an experiment
	 *
	 * @param WP_REST_Request<array<string,mixed>> $request Full data about the request.
	 *
	 * @return array<string,mixed>|WP_Error
	 */
	public function resume_experiment( $request ) {

		$experiment_id = absint( $request['eid'] );
		$experiment    = nab_get_experiment( $experiment_id );
		if ( is_wp_error( $experiment ) ) {
			return $experiment; // @codeCoverageIgnore
		}

		$ignore_scope = $request['ignoreScopeOverlap'];
		$resumed      = $experiment->resume( $ignore_scope ? 'ignore-scope-overlap' : 'check-scope-overlap' );
		if ( is_wp_error( $resumed ) ) {
			return $resumed;
		}

		return $experiment->json();
	}

	/**
	 * Stop an experiment
	 *
	 * @param WP_REST_Request<array<string,mixed>> $request Full data about the request.
	 *
	 * @return array<string,mixed>|WP_Error
	 */
	public function stop_experiment( $request ) {

		$experiment_id = absint( $request['eid'] );
		$experiment    = nab_get_experiment( $experiment_id );
		if ( is_wp_error( $experiment ) ) {
			return $experiment; // @codeCoverageIgnore
		}

		$stopped = $experiment->stop();
		if ( is_wp_error( $stopped ) ) {
			return $stopped;
		}

		return $experiment->json();
	}

	/**
	 * Pauses an experiment
	 *
	 * @param WP_REST_Request<array<string,mixed>> $request Full data about the request.
	 *
	 * @return array<string,mixed>|WP_Error
	 */
	public function pause_experiment( $request ) {

		$experiment_id = absint( $request['eid'] );
		$experiment    = nab_get_experiment( $experiment_id );
		if ( is_wp_error( $experiment ) ) {
			return $experiment; // @codeCoverageIgnore
		}

		$paused = $experiment->pause();
		if ( is_wp_error( $paused ) ) {
			return $paused;
		}

		return $experiment->json();
	}

	/**
	 * Applies the given alternative.
	 *
	 * @param WP_REST_Request<array<string,mixed>> $request Full data about the request.
	 *
	 * @return array<string,mixed>|WP_Error
	 */
	public function apply_alternative( $request ) {

		$experiment_id = absint( $request['eid'] );
		$experiment    = nab_get_experiment( $experiment_id );
		if ( is_wp_error( $experiment ) ) {
			return $experiment; // @codeCoverageIgnore
		}

		$alternative_id = $request['alternative'];
		assert( is_string( $alternative_id ) );

		$result = $experiment->apply_alternative( $alternative_id );
		if ( is_wp_error( $result ) ) {
			return $result; // @codeCoverageIgnore
		}

		return $experiment->json();
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
