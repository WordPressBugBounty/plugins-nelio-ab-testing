<?php
/**
 * This file contains the class that defines reusable entities REST API endpoints.
 *
 * @package    Nelio_AB_Testing
 * @subpackage Nelio_AB_Testing/includes/rest
 * @since      8.4.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * Defines the REST API endpoints for reusable entities.
 */
class Nelio_AB_Testing_Reusable_Entities_REST_Controller extends WP_REST_Controller {

	/**
	 * Hooks into WordPress.
	 *
	 * @return void
	 */
	public function init() {
		add_action( 'rest_api_init', array( $this, 'register_routes' ) );
	}

	/**
	 * Registers the reusable-entity routes.
	 *
	 * @return void
	 */
	public function register_routes() {
		register_rest_route(
			nelioab()->rest_namespace,
			'/reusable-goals',
			array(
				array(
					'methods'             => WP_REST_Server::EDITABLE,
					'callback'            => array( $this, 'update_reusable_goals' ),
					'permission_callback' => nab_capability_checker( 'edit_nab_experiments' ),
					'args'                => array(
						'goals' => array(
							'required'          => true,
							'type'              => 'list<TReusable_Goal>',
							'sanitize_callback' => array( $this, 'sanitize_reusable_goals' ),
						),
					),
				),
			)
		);

		register_rest_route(
			nelioab()->rest_namespace,
			'/reusable-segments',
			array(
				array(
					'methods'             => WP_REST_Server::EDITABLE,
					'callback'            => array( $this, 'update_reusable_segments' ),
					'permission_callback' => nab_capability_checker( 'edit_nab_experiments' ),
					'args'                => array(
						'segments' => array(
							'required'          => true,
							'type'              => 'list<TReusable_Segment>',
							'sanitize_callback' => array( $this, 'sanitize_reusable_segments' ),
						),
					),
				),
			)
		);
	}

	/**
	 * Sanitizes reusable goals.
	 *
	 * @param mixed $goals List of goals.
	 *
	 * @return list<TReusable_Goal> Sanitized goals.
	 */
	public function sanitize_reusable_goals( $goals ) {
		return Nelio_AB_Testing_Reusable_Entity_Manager::sanitize_reusable_goals( $goals );
	}

	/**
	 * Updates the list of reusable goals.
	 *
	 * @param WP_REST_Request<array{goals:list<TReusable_Goal>}> $request Full request data.
	 *
	 * @return list<TReusable_Goal>|WP_Error Sanitized goals or an error.
	 */
	public function update_reusable_goals( $request ) {
		return Nelio_AB_Testing_Reusable_Entity_Manager::set_reusable_goals( $request['goals'] );
	}

	/**
	 * Sanitizes reusable segments.
	 *
	 * @param mixed $segments List of segments.
	 *
	 * @return list<TReusable_Segment>|WP_Error Sanitized segments or an error.
	 */
	public function sanitize_reusable_segments( $segments ) {
		return Nelio_AB_Testing_Reusable_Entity_Manager::sanitize_reusable_segments( $segments );
	}

	/**
	 * Updates the list of reusable segments.
	 *
	 * @param WP_REST_Request<array{segments:list<TReusable_Segment>}> $request Full request data.
	 *
	 * @return list<TReusable_Segment>|WP_Error Sanitized segments or an error.
	 */
	public function update_reusable_segments( $request ) {
		return Nelio_AB_Testing_Reusable_Entity_Manager::set_reusable_segments( $request['segments'] );
	}
}
