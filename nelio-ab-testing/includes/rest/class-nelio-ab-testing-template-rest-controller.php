<?php
/**
 * This file contains the class that defines REST API endpoints for templates.
 *
 * @package    Nelio_AB_Testing
 * @subpackage Nelio_AB_Testing/includes/rest
 * @since      5.0.0
 */

defined( 'ABSPATH' ) || exit;

class Nelio_AB_Testing_Template_REST_Controller extends WP_REST_Controller {

	/**
	 * The single instance of this class.
	 *
	 * @since  5.0.0
	 * @var    Nelio_AB_Testing_REST_API
	 */
	protected static $instance;

	/**
	 * Returns the single instance of this class.
	 *
	 * @return Nelio_AB_Testing_Template_REST_Controller the single instance of this class.
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

		add_action( 'rest_api_init', array( $this, 'register_routes' ) );
	}//end init()

	/**
	 * Register the routes for the objects of the controller.
	 */
	public function register_routes() {

		register_rest_route(
			nelioab()->rest_namespace,
			'/template-contexts/',
			array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_template_contexts' ),
					'permission_callback' => nab_capability_checker( 'edit_nab_experiments' ),
					'args'                => array(),
				),
			)
		);

		register_rest_route(
			nelioab()->rest_namespace,
			'/templates/',
			array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_templates' ),
					'permission_callback' => nab_capability_checker( 'edit_nab_experiments' ),
					'args'                => $this->get_collection_params(),
				),
			)
		);
	}//end register_routes()

	/**
	 * Returns all templates.
	 *
	 * @param WP_REST_Request $request Full data about the request.
	 *
	 * @return WP_REST_Response The response
	 */
	public function get_template_contexts( $request ) {

		$post_types = get_post_types(
			array(
				'public'   => true,
				'_builtin' => false,
			),
			'objects'
		);

		$page = get_post_type_object( 'page' );
		$post = get_post_type_object( 'post' );
		if ( isset( $page ) ) {
			$post_types['page'] = $page;
		}//end if
		if ( isset( $post ) ) {
			$post_types['post'] = $post;
		}//end if

		$contexts = array_map(
			function ( $post_type ) {
				return array(
					'name'  => $post_type->name,
					'label' => $post_type->label,
				);
			},
			$post_types
		);

		$result = array(
			'wp' => array(
				'label'    => _x( 'WordPress', 'text', 'nelio-ab-testing' ),
				'contexts' => $contexts,
			),
		);

		/**
		 * Filters the array of template contexts available in a Nelio A/B Testing’s REST request.
		 *
		 * @param array $template_contexts The template contexts.
		 *
		 * @since 6.7.0
		 */
		$result = apply_filters( 'nab_template_contexts', $result );

		$data = array(
			'results'    => $result,
			'pagination' => array(
				'more'  => false,
				'pages' => 1,
			),
		);
		return new WP_REST_Response( $data, 200 );
	}//end get_template_contexts()

	/**
	 * Returns all templates.
	 *
	 * @param WP_REST_Request $request Full data about the request.
	 *
	 * @return WP_REST_Response The response
	 */
	public function get_templates( $request ) {

		$args = array(
			'public'   => true,
			'_builtin' => false,
		);

		$post_types = get_post_types( $args, 'names', 'and' );
		$post_types = array_unique( array_merge( $post_types, array( 'page', 'post' ) ) );

		$result = array();
		foreach ( $post_types as $post_type ) {
			$result[ "wp:{$post_type}" ] = $this->get_templates_in_post_type( $post_type );
		}//end foreach

		/**
		 * Filters the array of templates available for each post type in a Nelio A/B Testing’s REST request.
		 *
		 * @param array $templates The templates for each post type.
		 *
		 * @since 6.7.0
		 */
		$result = apply_filters( 'nab_templates', $result );

		$data = array(
			'results'    => $result,
			'pagination' => array(
				'more'  => false,
				'pages' => 1,
			),
		);
		return new WP_REST_Response( $data, 200 );
	}//end get_templates()

	/**
	 * Get the query params for collections
	 *
	 * @return array
	 */
	public function get_collection_params() {
		return array(
			'type' => array(
				'description'       => 'Limit results to those matching a post type.',
				'type'              => 'string',
				'default'           => 'post',
				'sanitize_callback' => 'sanitize_text_field',
			),
		);
	}//end get_collection_params()

	private function get_templates_in_post_type( $post_type ) {

		$templates = wp_get_theme()->get_page_templates( null, $post_type );
		$templates = array_map(
			function ( $id, $name ) {
				return array(
					'id'   => $id,
					'name' => $name,
				);
			},
			array_keys( $templates ),
			array_values( $templates )
		);

		$has_front_page_template = ! empty( locate_template( 'front-page.php' ) );
		if ( $has_front_page_template ) {
			$templates[] = array(
				'id'   => '_nab_front_page_template',
				'name' => sprintf(
					/* translators: template name */
					_x( 'Front Page template (%s)', 'text', 'nelio-ab-testing' ),
					'front-page.php'
				),
			);
		}//end if

		usort(
			$templates,
			function ( $a, $b ) {
				return strcasecmp( $a['name'], $b['name'] );
			}
		);

		if ( count( $templates ) ) {
			$templates = array_merge(
				array(
					array(
						'id'   => '_nab_default_template',
						'name' => _x( 'Default template', 'text', 'nelio-ab-testing' ),
					),
				),
				$templates
			);
		}//end if

		return $templates;
	}//end get_templates_in_post_type()
}//end class
