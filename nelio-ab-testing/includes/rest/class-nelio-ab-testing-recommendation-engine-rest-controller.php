<?php

defined( 'ABSPATH' ) || exit;

use Nelio_AB_Testing\Recommendation_Engine\Engine;
use Nelio_AB_Testing\Recommendation_Engine\Experiment_Repository;
use Nelio_AB_Testing\Recommendation_Engine\Opportunity;
use Nelio_AB_Testing\Recommendation_Engine\Optimization_Score_Calculator;
use Nelio_AB_Testing\Recommendation_Engine\Providers\High_Traffic_Provider;
use Nelio_AB_Testing\Recommendation_Engine\Providers\Key_Pages_Provider;
use Nelio_AB_Testing\Recommendation_Engine\Providers\Recently_Finished_Experiment_Provider;
use Nelio_AB_Testing\Recommendation_Engine\Providers\Recently_Published_Content_Provider;
use Nelio_AB_Testing\Recommendation_Engine\Providers\Stale_Experiments_Provider;
use Nelio_AB_Testing\Recommendation_Engine\Providers\Traffic\Traffic_Provider_Factory;

class Nelio_AB_Testing_Recommendation_Engine_REST_Controller extends WP_REST_Controller {

	/**
	 * Hooks into WordPress.
	 *
	 * @return void
	 */
	public function init() {
		add_action( 'rest_api_init', array( $this, 'register_routes' ) );
	}

	/**
	 * Registers routes.
	 *
	 * @return void
	 */
	public function register_routes() {
		register_rest_route(
			nelioab()->rest_namespace,
			'/recommendation-engine/ga4-connect',
			array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'connect_ga4' ),
					'permission_callback' => nab_capability_checker( 'edit_nab_experiments' ),
				),
			)
		);

		register_rest_route(
			nelioab()->rest_namespace,
			'/recommendation-engine/ga4-properties',
			array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_ga4_properties' ),
					'permission_callback' => nab_capability_checker( 'edit_nab_experiments' ),
				),
			)
		);

		register_rest_route(
			nelioab()->rest_namespace,
			'/recommendation-engine',
			array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_data' ),
					'permission_callback' => nab_capability_checker( 'read_nab_results' ),
				),
			)
		);
	}

	/**
	 * Returns optimization score and list of opportunities.
	 *
	 * @return array{
	 *   opportunities: list<Opportunity>,
	 *   score: TOptimization_Score,
	 *   experiments: array{
	 *       recentCount: int,
	 *       recentSummaries: list<TFinished_Experiment_Summary>,
	 *       runningCount: int,
	 *       runningSummaries: list<TRunning_Experiment_Summary>
	 *   }
	 * }
	 */
	public function get_data() {
		$score_calculator = new Optimization_Score_Calculator();
		$experiment_repo  = new Experiment_Repository();
		$engine           = $this->create_recommendation_engine( $experiment_repo );

		$opportunities = $engine->get_opportunities();

		$score = $score_calculator->compute(
			nab_get_running_experiments(),
			$experiment_repo->get_recently_finished_experiments( 'last50' ),
			$opportunities,
			$experiment_repo->get_tested_content_count(),
			$experiment_repo->get_testable_content_count()
		);

		$running_experiments = array_merge(
			nab_get_running_experiments(),
			nab_get_running_heatmaps()
		);
		$running_experiments = $this->sort_by_start_date( $running_experiments );
		$running_experiments = array_slice( $running_experiments, 0, 3 );
		$running_summaries   = array_map( array( $this, 'get_running_summary' ), $running_experiments );

		$recent_experiments = $experiment_repo->get_recently_finished_experiments( 'last5' );
		$recent_summaries   = array_map( array( $this, 'get_finished_summary' ), $recent_experiments );
		$recent_summaries   = array_values( array_filter( $recent_summaries ) );

		$recent_draft_experiments = array_slice( $experiment_repo->get_recent_drafts(), 0, 3 );
		$pending_summaries        = array_map( array( $this, 'get_pending_summary' ), $recent_draft_experiments );
		$pending_summaries        = array_values( array_filter( $pending_summaries ) );

		return array(
			'opportunities' => $opportunities,
			'score'         => $score,
			'experiments'   => array(
				'exist'            => $experiment_repo->has_experiments(),
				'pendingSummaries' => $pending_summaries,
				'recentCount'      => count( $recent_experiments ),
				'recentSummaries'  => $recent_summaries,
				'runningCount'     => count( $running_experiments ),
				'runningSummaries' => $running_summaries,
			),
		);
	}

	/**
	 * Callback to convert an experiment into a finished experiment summary.
	 *
	 * @param Nelio_AB_Testing_Experiment $experiment The experiment.
	 *
	 * @return TFinished_Experiment_Summary|null
	 */
	public function get_finished_summary( $experiment ) {
		$end_date = $experiment->get_end_date();
		assert( is_string( $end_date ) );

		return array(
			'id'        => $experiment->get_id(),
			'name'      => $experiment->get_name(),
			'endDate'   => $end_date,
			'resultUrl' => $experiment->get_url(),
		);
	}

	/**
	 * Callback to convert an experiment into a running experiment summary.
	 *
	 * @param Nelio_AB_Testing_Experiment $experiment The experiment.
	 *
	 * @return TRunning_Experiment_Summary
	 */
	public function get_running_summary( $experiment ) {
		$start_date = $experiment->get_start_date();
		assert( is_string( $start_date ) );
		return array(
			'id'               => $experiment->get_id(),
			'type'             => $experiment->get_type(),
			'name'             => $experiment->get_name(),
			'startedAt'        => $start_date,
			'alternativeCount' => count( $experiment->get_alternatives() ),
			'resultUrl'        => $experiment->get_url(),
		);
	}

	/**
	 * Callback to convert an experiment into a pending summary.
	 *
	 * @param Nelio_AB_Testing_Experiment $experiment The experiment.
	 *
	 * @return TPending_Experiment_Summary|null
	 */
	public function get_pending_summary( $experiment ) {
		$end_date = $experiment->get_end_date();
		assert( is_string( $end_date ) );

		return array(
			'id'      => $experiment->get_id(),
			'type'    => $experiment->get_type(),
			'name'    => $experiment->get_name(),
			'editUrl' => $experiment->get_url(),
		);
	}

	/**
	 * Creates a new instance of the recommendation engine.
	 *
	 * @param Experiment_Repository $experiment_repo Experiment repository.
	 *
	 * @return Engine
	 */
	private function create_recommendation_engine( $experiment_repo ) {
		$post_types = array_merge(
			array( 'page', 'post' ),
			\Nelio_AB_Testing\Experiment_Library\Post_Experiment\get_testable_custom_post_types()
		);
		return new Engine(
			array(
				new High_Traffic_Provider( Traffic_Provider_Factory::make(), $experiment_repo ),
				new Key_Pages_Provider( $experiment_repo ),
				new Recently_Finished_Experiment_Provider( $experiment_repo ),
				new Recently_Published_Content_Provider( $experiment_repo, $post_types ),
				new Stale_Experiments_Provider( $experiment_repo ),
			),
			$experiment_repo
		);
	}

	/**
	 * Redirect page on GA4 connect.
	 *
	 * @return void
	 */
	public function connect_ga4() {
		if ( ! headers_sent() ) {
			header( 'Content-Type: text/html; charset=UTF-8' ); // @codeCoverageIgnore
		}
		echo '<!DOCTYPE html>';
		echo "\n";
		echo '<html><head><script>window.close();</script></head></html>';
		nab_die();
	}

	/**
	 * Gets GA4 properties.
	 *
	 * @return mixed|WP_Error
	 */
	public function get_ga4_properties() {
		$data = array(
			'method'    => 'GET',
			'timeout'   => absint( apply_filters( 'nab_request_timeout', 60 ) ),
			'sslverify' => ! nab_does_api_use_proxy(),
			'headers'   => array(
				'Authorization' => 'Bearer ' . nab_generate_api_auth_token(),
				'accept'        => 'application/json',
				'content-type'  => 'application/json',
			),
		);

		$url      = add_query_arg(
			'siteId',
			nab_get_site_id(),
			nab_get_api_url( '/ga4/properties', 'wp' )
		);
		$response = wp_remote_request( $url, $data );
		$result   = nab_extract_response_body( $response );
		if ( is_wp_error( $result ) ) {
			return new WP_Error( 'nelio-ai-error-911', $result->get_error_code() . ' ' . $result->get_error_message() );
		}

		return $result;
	}

	/**
	 * Sorts experiments by start date.
	 *
	 * @param list<Nelio_AB_Testing_Experiment> $experiments Experiment list.
	 *
	 * @return list<Nelio_AB_Testing_Experiment>
	 */
	private function sort_by_start_date( $experiments ) {
		usort(
			$experiments,
			function ( $e1, $e2 ) {
				$d1 = $e1->get_start_date();
				$d2 = $e2->get_start_date();
				assert( ! empty( $d1 ) );
				assert( ! empty( $d2 ) );
				if ( $d1 === $d2 ) {
					return 0;
				}
				return $d1 > $d2 ? -1 : 1;
			}
		);
		return $experiments;
	}
}
