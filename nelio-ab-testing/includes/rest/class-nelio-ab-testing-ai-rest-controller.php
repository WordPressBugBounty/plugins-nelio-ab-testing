<?php

defined( 'ABSPATH' ) || exit;

class Nelio_AB_Testing_AI_REST_Controller extends WP_REST_Controller {

	/**
	 * Hooks into WordPress.
	 *
	 * @return void
	 */
	public function init() {
		add_action( 'rest_api_init', array( $this, 'maybe_register_ai_routes' ) );
	}

	/**
	 * Registers REST routes.
	 *
	 * @return void
	 */
	public function maybe_register_ai_routes() {
		if ( ! nab_is_ai_active() ) {
			return; // @codeCoverageIgnore
		}

		register_rest_route(
			nelioab()->rest_namespace,
			'/ai/experiment-suggestions',
			array(
				array(
					'methods'             => WP_REST_Server::EDITABLE,
					'callback'            => array( $this, 'get_experiment_suggestions' ),
					'permission_callback' => nab_capability_checker( 'edit_nab_experiments' ),
					'args'                => array(
						'summary'     => array(
							'required'          => true,
							'description'       => 'Summary of a page.',
							'type'              => 'PageSummary',
							'validate_callback' => fn( $v ) => is_array( $v ) && ! empty( $v['url'] ) && is_string( $v['url'] ),
						),
						'opportunity' => array(
							'required'    => false,
							'description' => 'Opportunity that triggered this recommendation.',
							'type'        => 'Nelio_AB_Testing\Recommendation_Engine\Opportunity',
						),
					),
				),
			)
		);
	}

	/**
	 * Returns test suggestions.
	 *
	 * @param WP_REST_Request<array<string,mixed>> $request Request.
	 *
	 * @return mixed|WP_Error
	 */
	public function get_experiment_suggestions( $request ) {
		$site_id = nab_get_site_id();
		/** @var array{url:string} $summary */
		$summary = $request['summary'];

		$url            = $summary['url'];
		$version        = nelioab()->plugin_version;
		$transient_name = sanitize_title( "nab_{$version}_page_tests_{$url}" );
		$result         = get_transient( $transient_name );
		if ( ! empty( $result ) ) {
			return $result;
		}

		$body = wp_json_encode(
			array_filter(
				array(
					'summary'     => $summary,
					'opportunity' => $request['opportunity'] ?? null,
				),
				fn( $v ) => null !== $v
			)
		);
		assert( ! empty( $body ) );

		$data = array(
			'method'    => 'POST',
			'timeout'   => absint( apply_filters( 'nab_ai_request_timeout', 60 ) ),
			'sslverify' => ! nab_does_api_use_proxy(),
			'headers'   => array(
				'Authorization' => 'Bearer ' . nab_generate_api_auth_token(),
				'accept'        => 'application/json',
				'content-type'  => 'application/json',
			),
			'body'      => $body,
		);

		$url = nab_get_api_url( "/ai/{$site_id}/page-tests", 'wp' );

		$response = wp_remote_request( $url, $data );
		$result   = nab_extract_response_body( $response );
		if ( is_wp_error( $result ) ) {
			return new WP_Error( 'nelio-ai-error', $result->get_error_code() . ' ' . $result->get_error_message() );
		}

		set_transient( $transient_name, $result, WEEK_IN_SECONDS );
		return $result;
	}
}
