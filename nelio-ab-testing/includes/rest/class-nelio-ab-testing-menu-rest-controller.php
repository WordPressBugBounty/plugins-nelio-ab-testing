<?php
/**
 * This file contains the class that defines REST API endpoints for menus.
 *
 * @package    Nelio_AB_Testing
 * @subpackage Nelio_AB_Testing/includes/rest
 * @since      5.0.0
 */

defined( 'ABSPATH' ) || exit;

class Nelio_AB_Testing_Menu_REST_Controller extends WP_REST_Controller {

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
			'/menu/search',
			array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'search_menus' ),
					'permission_callback' => nab_capability_checker( 'edit_nab_experiments' ),
					'args'                => array(
						'query' => array(
							'required'          => true,
							'description'       => 'Limit results to those matching a string.',
							'type'              => 'string',
							'sanitize_callback' => 'sanitize_text_field',
						),
					),
				),
			)
		);

		register_rest_route(
			nelioab()->rest_namespace,
			'/menu/(?P<id>[\d]+)',
			array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_menu' ),
					'permission_callback' => nab_capability_checker( 'edit_nab_experiments' ),
					'args'                => array(
						'id' => array(
							'required'          => true,
							'description'       => 'Menu ID.',
							'type'              => 'number',
							'sanitize_callback' => 'absint',
						),
					),
				),
			)
		);
	}

	/**
	 * Search menus
	 *
	 * @param WP_REST_Request<array<string,mixed>> $request Full data about the request.
	 *
	 * @return array{results:list<array{id:number, name:string}>,pagination:array{more:false,pages:1}}
	 */
	public function search_menus( $request ) {

		$query = is_string( $request['query'] ) ? $request['query'] : '';
		$query = trim( $query );
		$menus = wp_get_nav_menus();

		if ( empty( $query ) ) {
			$result = $menus;
		} else {
			$result = array_filter(
				$menus,
				function ( $menu ) use ( $query ) {
					return false !== mb_stripos( $menu->name, $query );
				}
			);
		}

		return array(
			'results'    => array_values( array_map( array( $this, 'build_menu_json' ), $result ) ),
			'pagination' => array(
				'more'  => false,
				'pages' => 1,
			),
		);
	}

	/**
	 * Get menu.
	 *
	 * @param WP_REST_Request<array<string,mixed>> $request Full data about the request.
	 *
	 * @return array{id:number, name:string}|WP_Error
	 */
	public function get_menu( $request ) {

		$menu_id = absint( $request['id'] );
		$menus   = wp_get_nav_menus();

		foreach ( $menus as $menu ) {
			if ( $menu->term_id === $menu_id ) {
				return $this->build_menu_json( $menu );
			}
		}

		return new WP_Error(
			'not-found',
			sprintf(
				/* translators: %d: Menu ID. */
				_x( 'Menu with ID “%d” not found.', 'text', 'nelio-ab-testing' ),
				$menu_id
			)
		);
	}

	/**
	 * Summarizes the menu.
	 *
	 * @param WP_Term $menu Menu.
	 *
	 * @return array{id:number, name:string}
	 */
	private function build_menu_json( $menu ) {
		return array(
			'id'   => $menu->term_id,
			'name' => $menu->name,
		);
	}
}
