<?php
/**
 * This file contains the class that defines reusable entities REST API endpoints.
 *
 * @package    Nelio_AB_Testing
 * @subpackage Nelio_AB_Testing/includes/rest
 * @since      8.4.0
 */

defined( 'ABSPATH' ) || exit;

use Nelio_AB_Testing\Zod\Zod as Z;

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
	 * Register the routes for the objects of the controller.
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
	 * @return list<TReusable_Goal>
	 */
	public function sanitize_reusable_goals( $goals ) {
		$goal_schema = Z::object(
			array(
				'attributes'        => Z::object(
					array(
						'name' => Z::string()->trim()->min( 1 ),
					)
				)->loose(),
				'conversionActions' => Z::array(
					Z::object(
						array(
							'id'         => Z::string(),
							'type'       => Z::string(),
							'attributes' => Z::object( array() )->loose(),
							'scope'      => Z::object(
								array(
									'type' => Z::enum(
										array(
											'all-pages',
											'test-scope',
											'php-function',
											'post-ids',
											'urls',
										)
									),
								)
							)->loose(),
						)
					)
				),
			)
		);

		// Parse goals.
		$goals = is_array( $goals ) ? $goals : array();
		$goals = array_map(
			fn( $goal ) => $goal_schema->safe_parse( $goal ),
			$goals
		);
		$goals = array_map(
			fn( $parsed_goal ) => $parsed_goal['success'] ? $parsed_goal['data'] : null,
			$goals
		);

		/** @var list<TReusable_Goal> $goals */
		$goals = array_values( array_filter( $goals ) );
		$goals = $this->remove_duplicated_names( $goals );
		$goals = $this->sort_by_name( $goals );

		// Sanitize actions.
		$goals = array_map(
			function ( $goal ) {
				$goal['conversionActions'] = array_map(
					function ( $action ) {
						$action['attributes'] = Nelio_AB_Testing\Conversion_Action_Library\Utils\sanitize_conversion_action_attributes( $action['attributes'], $action );
						$action['scope']      = Nelio_AB_Testing\Conversion_Action_Library\Utils\sanitize_conversion_action_scope( $action['scope'], $action );
						if ( 'php-function' === $action['scope']['type'] ) {
							unset( $action['scope']['enabled'] );
						}
						return $action;
					},
					$goal['conversionActions']
				);
				return $goal;
			},
			$goals
		);

		return $goals;
	}

	/**
	 * Callback to update list of reusable goals.
	 *
	 * @param WP_REST_Request<array{goals:list<TReusable_Goal>}> $request Full data about the request.
	 *
	 * @return list<TReusable_Goal>
	 */
	public function update_reusable_goals( $request ) {
		/** @var list<TReusable_Goal> */
		$goals = $request['goals'];

		if ( count( $goals ) ) {
			update_option( 'nab_reusable_goals', $goals );
		} else {
			delete_option( 'nab_reusable_goals' );
		}

		return $goals;
	}

	/**
	 * Sanitizes reusable segments.
	 *
	 * @param mixed $segments List of segments.
	 *
	 * @return list<TReusable_Segment>
	 */
	public function sanitize_reusable_segments( $segments ) {
		$segment_schema = Z::object(
			array(
				'attributes'        => Z::object(
					array(
						'name' => Z::string()->trim()->min( 1 ),
					)
				)->loose(),
				'segmentationRules' => Z::array(
					Z::object(
						array(
							'id'         => Z::string()->trim()->min( 1 ),
							'type'       => Z::string()->trim()->min( 1 ),
							'attributes' => Z::object( array() )->loose(),
						)
					)
				),
			)
		);

		// Parse segments.
		$segments = is_array( $segments ) ? $segments : array();
		$segments = array_map(
			fn( $segment ) => $segment_schema->safe_parse( $segment ),
			$segments
		);
		$segments = array_map(
			fn( $parsed_segment ) => $parsed_segment['success'] ? $parsed_segment['data'] : null,
			$segments
		);

		/** @var list<TReusable_Segment> $segments */
		$segments = array_values( array_filter( $segments ) );
		$segments = $this->remove_duplicated_names( $segments );
		$segments = $this->sort_by_name( $segments );

		return $segments;
	}

	/**
	 * Callback to update list of reusable segments.
	 *
	 * @param WP_REST_Request<array{segments:list<TReusable_Segment>}> $request Full data about the request.
	 *
	 * @return list<TReusable_Segment>
	 */
	public function update_reusable_segments( $request ) {
		/** @var list<TReusable_Segment> */
		$segments = $request['segments'];

		if ( count( $segments ) ) {
			update_option( 'nab_reusable_segments', $segments );
		} else {
			delete_option( 'nab_reusable_segments' );
		}

		return $segments;
	}

	/**
	 * Removes elements whose name is duplicated, keeping the last element with a repeated name.
	 *
	 * @param list<T> $elements Elements.
	 *
	 * @return list<T>
	 *
	 * @template T of array{attributes:array{name:string}}
	 */
	private function remove_duplicated_names( $elements ) {
		$elements = array_combine(
			array_map( fn( $s ) => mb_strtolower( $s['attributes']['name'] ), $elements ),
			$elements
		);
		return array_values( $elements );
	}

	/**
	 * Removes elements whose name is duplicated, keeping the last element with a repeated name.
	 *
	 * @param list<T> $elements Elements.
	 *
	 * @return list<T>
	 *
	 * @template T of array{attributes:array{name:string}}
	 */
	private function sort_by_name( $elements ) {
		$elements = array_combine(
			array_map( fn( $s ) => mb_strtolower( $s['attributes']['name'] ), $elements ),
			$elements
		);
		ksort( $elements );
		return array_values( $elements );
	}
}
