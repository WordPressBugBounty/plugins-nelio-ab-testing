<?php
/**
 * This file contains the class that defines REST API endpoints for posts.
 *
 * @package    Nelio_AB_Testing
 * @subpackage Nelio_AB_Testing/includes/rest
 * @since      5.0.0
 */

defined( 'ABSPATH' ) || exit;

class Nelio_AB_Testing_Post_REST_Controller extends WP_REST_Controller {

	/**
	 * The single instance of this class.
	 *
	 * @since  5.0.0
	 * @var    Nelio_AB_Testing_Post_REST_Controller|null
	 */
	protected static $instance;

	/**
	 * Returns the single instance of this class.
	 *
	 * @return Nelio_AB_Testing_Post_REST_Controller the single instance of this class.
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
			'/post',
			array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_post' ),
					'permission_callback' => nab_capability_checker( 'edit_nab_experiments' ),
					'args'                => $this->get_item_params(),
				),
			)
		);

		register_rest_route(
			nelioab()->rest_namespace,
			'/post/search',
			array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'search_posts' ),
					'permission_callback' => nab_capability_checker( 'edit_nab_experiments' ),
					'args'                => $this->get_collection_params(),
				),
			)
		);

		register_rest_route(
			nelioab()->rest_namespace,
			'/types',
			array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_post_types' ),
					'permission_callback' => nab_capability_checker( 'edit_nab_experiments' ),
					'args'                => array(),
				),
			)
		);

		register_rest_route(
			nelioab()->rest_namespace,
			'/post/(?P<src>[\d]+)/overwrites/(?P<dest>[\d]+)',
			array(
				array(
					'methods'             => WP_REST_Server::EDITABLE,
					'callback'            => array( $this, 'overwrite_post_content' ),
					'permission_callback' => nab_capability_checker( 'edit_nab_experiments' ),
					'args'                => array(),
				),
			)
		);
	}

	/**
	 * Search posts
	 *
	 * @param WP_REST_Request<array<string,mixed>> $request Full data about the request.
	 * @return WP_REST_Response|WP_Error The response
	 */
	public function search_posts( $request ) {

		/** @var string */
		$query = $request['query'];
		/** @var string */
		$post_type = $request['type'];
		/** @var int */
		$per_page = $request['per_page'];
		/** @var int */
		$page = $request['page'];

		if ( 'nab_experiment' === $post_type ) {
			return new WP_Error(
				'not-found',
				_x( 'Tests are not exposed through this endpoint.', 'text', 'nelio-ab-testing' )
			);
		}

		/**
		 * Filters the post before the actual query is run.
		 *
		 * @param null|array{results:list<TPost>, pagination: array{more:bool, pages:int}} $data The result data.
		 * @param string        $post_type The post type.
		 * @param string        $query     The query term.
		 * @param int           $per_page  The number of posts to show per page.
		 * @param int           $page      The number of the current page.
		 *
		 * @since 7.2.0
		 */
		$data = apply_filters( 'nab_pre_get_posts', null, $post_type, $query, $per_page, $page );
		if ( null !== $data ) {
			return new WP_REST_Response( $data, 200 );
		}

		$data = $this->search_wp_posts( $query, $post_type, $per_page, $page );
		return new WP_REST_Response( $data, 200 );
	}

	/**
	 * Get post
	 *
	 * @param WP_REST_Request<array<string,mixed>> $request Full data about the request.
	 * @return WP_REST_Response|WP_Error The response
	 */
	public function get_post( $request ) {

		/** @var int|string */
		$post_id = $request['id'];
		/** @var string */
		$post_type = $request['type'];

		if ( 'nab_experiment' === $post_type ) {
			return new WP_Error(
				'not-found',
				_x( 'Tests are not exposed through this endpoint.', 'text', 'nelio-ab-testing' )
			);
		}

		/**
		 * Filters the post before the actual query is run.
		 *
		 * @param null|TPost|WP_Post|WP_Error $post The post to filter.
		 * @param int|string                  $post_id The id of the post.
		 * @param string                      $post_type The post type.
		 *
		 * @since 7.2.0
		 */
		$post = apply_filters( 'nab_pre_get_post', null, $post_id, $post_type );
		if ( null !== $post ) {
			if ( is_wp_error( $post ) ) {
				return $post;
			}
			if ( $post instanceof WP_Post ) {
				$post = $this->build_post_json( $post );
			}
			return new WP_REST_Response( $post, 200 );
		}

		$post = get_post( absint( $post_id ) );
		if ( ! $post || $post_type !== $post->post_type ) {
			return new WP_Error(
				'not-found',
				sprintf(
					/* translators: %d: Post ID. */
					_x( 'Content with ID “%d” not found.', 'text', 'nelio-ab-testing' ),
					$post_id
				)
			);
		}

		$data = $this->build_post_json( $post );
		return new WP_REST_Response( $data, 200 );
	}

	/**
	 * Returns post types.
	 *
	 * @return WP_REST_Response
	 */
	public function get_post_types() {

		nab_require_wp_file( '/wp-admin/includes/plugin.php' );

		$post_types = get_post_types(
			array(
				'public' => true,
			),
			'objects'
		);

		$data = array_map(
			function ( $post_type ) {
				return array(
					'name'   => $post_type->name,
					'label'  => $post_type->label,
					'labels' => array(
						'singular_name' => is_string( $post_type->labels->singular_name )
							? $post_type->labels->singular_name
							: $post_type->name,
					),
					'kind'   => 'entity',
				);
			},
			$post_types
		);

		if ( isset( $data['product'] ) ) {
			$data['product_variation'] = array(
				'name'   => 'product_variation',
				'label'  => _x( 'Product Variations', 'text', 'nelio-ab-testing' ),
				'labels' => array(
					'singular_name' => _x( 'Product Variation', 'text', 'nelio-ab-testing' ),
				),
				'kind'   => 'entity',
			);
		}

		/**
		 * Filters the list of available post types in A/B tests.
		 *
		 * @param array<string,TPost_Type> $data associative array of post types available including a kind property that can be 'entity' or 'form'.
		 *
		 * @since 7.2.0
		 */
		$data = apply_filters( 'nab_get_post_types', $data );

		return new WP_REST_Response( $data, 200 );
	}

	/**
	 * Overwrites content from a post into another one.
	 *
	 * @param WP_REST_Request<array{src:int,dest:int}> $request Full data about the request.
	 * @return WP_REST_Response The response
	 */
	public function overwrite_post_content( $request ) {

		$src_id  = absint( $request['src'] );
		$dest_id = absint( $request['dest'] );

		$post_helper = Nelio_AB_Testing_Post_Helper::instance();
		$post_helper->overwrite( $dest_id, $src_id );

		return new WP_REST_Response( array(), 200 );
	}

	/**
	 * Get the query params for collections
	 *
	 * @return array<string,mixed>
	 */
	public function get_collection_params() {
		return array(
			'page'     => array(
				'description'       => 'Current page of the collection.',
				'type'              => 'integer',
				'default'           => 1,
				'sanitize_callback' => 'absint',
			),
			'per_page' => array(
				'description'       => 'Maximum number of items to be returned in result set.',
				'type'              => 'integer',
				'default'           => 50,
				'sanitize_callback' => 'absint',
			),
			'type'     => array(
				'description'       => 'Limit results to those matching a post type.',
				'type'              => 'string',
				'default'           => 'post',
				'sanitize_callback' => 'sanitize_text_field',
			),
			'query'    => array(
				'required'          => true,
				'description'       => 'Limit results to those matching a string.',
				'type'              => 'string',
				'sanitize_callback' => 'sanitize_text_field',
			),
		);
	}

	/**
	 * Get the query params for a single item.
	 *
	 * @return array<string,mixed>
	 */
	public function get_item_params() {
		return array(
			'id'   => array(
				'required'    => true,
				'description' => 'Post ID.',
			),
			'type' => array(
				'description'       => 'Limit results to those matching a post type.',
				'type'              => 'string',
				'default'           => 'post',
				'sanitize_callback' => 'sanitize_text_field',
			),
		);
	}

	/**
	 * Search posts by title using the given query.
	 *
	 * @param string $query     Search query.
	 * @param string $post_type Post type.
	 * @param int    $per_page  Number of items per page.
	 * @param int    $page      Page.
	 *
	 * @return array{
	 *   results: list<TPost>,
	 *   pagination: array{more: bool, pages:int}
	 * }
	 */
	private function search_wp_posts( $query, $post_type, $per_page, $page ) {

		$posts = array();
		if ( 1 === $page ) {
			$posts = $this->search_wp_post_by_id_or_url( $query, $post_type );
		}

		$args = array(
			'post_title__like' => $query,
			'post_type'        => $post_type,
			'order'            => 'desc',
			'orderby'          => 'date',
			'posts_per_page'   => $per_page,
			'post_status'      => array( 'publish', 'draft' ),
			'paged'            => $page,
		);

		/**
		 * Filters the arguments used to search for WordPress posts.
		 *
		 * @param array<string,mixed> $args The arguments used in a `WP_Query`.
		 *
		 * @since 7.4.0
		 */
		$args = apply_filters( 'nab_wp_post_search_args', $args );

		add_filter( 'posts_where', array( $this, 'add_title_filter_to_wp_query' ), 10, 2 );
		$wp_query = new WP_Query( $args );
		remove_filter( 'posts_where', array( $this, 'add_title_filter_to_wp_query' ), 10 );

		while ( $wp_query->have_posts() ) {

			$wp_query->the_post();

			// If the query was a number, we catched it when searching by ID or URL.
			if ( get_the_ID() === absint( $query ) ) {
				continue;
			}

			/** @var WP_Post */
			global $post;
			array_push(
				$posts,
				$this->build_post_json( $post )
			);

		}

		wp_reset_postdata();

		$data = array(
			'results'    => $posts,
			'pagination' => array(
				'more'  => $page < $wp_query->max_num_pages,
				'pages' => $wp_query->max_num_pages,
			),
		);

		return $data;
	}

	/**
	 * Searches a post by ID or by URL.
	 *
	 * @param int|string $id_or_url Post ID or Post URL.
	 * @param string     $post_type Post type.
	 *
	 * @return list<TPost>
	 */
	private function search_wp_post_by_id_or_url( $id_or_url, $post_type ) {
		if ( ! absint( $id_or_url ) && ! filter_var( $id_or_url, FILTER_VALIDATE_URL ) ) {
			return array();
		}

		$post_id = $id_or_url;
		if ( is_string( $id_or_url ) ) {
			$post_id = nab_url_to_postid( $id_or_url );
		}

		$post = get_post( absint( $post_id ) );
		if ( ! $post ) {
			return array();
		}

		if ( $post_type !== $post->post_type ) {
			return array();
		}

		if ( ! in_array( $post->post_status, array( 'publish', 'draft' ), true ) ) {
			return array();
		}

		return array( $this->build_post_json( $post ) );
	}

	/**
	 * A filter to search posts based on their title.
	 *
	 * This function modifies the posts query so that we can search posts based
	 * on a term that should appear in their titles.
	 *
	 * @param string   $where    The where clause, as it's originally defined.
	 * @param WP_Query $wp_query The $wp_query object that contains the params
	 *                           used to build the where clause.
	 *
	 * @return string a modified where statement that includes the post_title.
	 *
	 * @since  5.0.0
	 */
	public function add_title_filter_to_wp_query( $where, $wp_query ) {
		$term = $wp_query->get( 'post_title__like' );

		if ( ! empty( $term ) && is_string( $term ) ) {
			/** @var wpdb */
			global $wpdb;
			$term   = esc_sql( $wpdb->esc_like( $term ) );
			$term   = ' \'%' . $term . '%\'';
			$where .= ' AND ' . $wpdb->posts . '.post_title LIKE ' . $term;

		}

		return $where;
	}

	/**
	 * Returns the name of a post’s author.
	 *
	 * @param WP_Post $post Post.
	 *
	 * @return string
	 */
	private function get_the_author( $post ) {
		return get_the_author_meta( 'display_name', absint( $post->post_author ) );
	}

	/**
	 * Returns the name of a post’s type.
	 *
	 * @param WP_Post $post          Post.
	 *
	 * @return string|false
	 */
	private function get_post_time( $post ) {
		$date = ' ' . $post->post_date_gmt;
		$date = strpos( $date, '0000-00-00' ) ? false : get_post_time( 'c', true, $post );
		return is_string( $date ) ? $date : false;
	}

	/**
	 * Returns the name of a post’s type.
	 *
	 * @param WP_Post $post Post.
	 *
	 * @return string
	 */
	private function get_post_type_name( $post ) {

		$post_type_name = _x( 'Post', 'text (default post type name)', 'nelio-ab-testing' );
		$post_type      = get_post_type_object( $post->post_type );
		if ( ! empty( $post_type ) && ! empty( $post_type->labels->singular_name ) && is_string( $post_type->labels->singular_name ) ) {
			$post_type_name = $post_type->labels->singular_name;
		}

		return $post_type_name;
	}

	/**
	 * Summarizes the post.
	 *
	 * @param WP_Post $post Post.
	 *
	 * @return TPost
	 */
	private function build_post_json( $post ) {

		$post_title   = trim( $post->post_title );
		$post_excerpt = trim( $post->post_excerpt );
		$permalink    = get_permalink( $post );
		$type_label   = $this->get_post_type_name( $post );

		$author      = absint( $post->post_author );
		$author_name = $this->get_the_author( $post );

		$date = $this->get_post_time( $post );

		$image_id      = absint( get_post_meta( $post->ID, '_thumbnail_id', true ) );
		$image_src     = '';
		$thumbnail_src = '';
		if ( $image_id ) {
			$image     = wp_get_attachment_image_src( $image_id );
			$thumbnail = wp_get_attachment_image_src( $image_id, 'thumbnail' );
			if ( empty( $image ) ) {
				$image_id = 0;
			} else {
				$image_src = $image[0];
			}
			if ( ! empty( $thumbnail ) ) {
				$thumbnail_src = $thumbnail[0];
			}
		}

		$extra_info = array();
		if ( absint( get_option( 'page_on_front' ) ) === $post->ID ) {
			$extra_info['specialPostType'] = 'page-on-front';
		} elseif ( absint( get_option( 'page_for_posts' ) ) === $post->ID ) {
			$extra_info['specialPostType'] = 'page-for-posts';
		}

		/**
		 * Adds extra data to a post that’s about to be included in a Nelio A/B Testing’s post-related REST request.
		 *
		 * @param array<string,mixed> $options extra options.
		 * @param WP_Post             $post    the post.
		 *
		 * @since 5.0.0
		 */
		$extra_info = apply_filters( 'nab_post_json_extra_data', $extra_info, $post );

		$status_object = get_post_status_object( $post->post_status );
		$status_label  = ! empty( $status_object ) ? $status_object->label : '';
		$status_label  = ! empty( $status_label ) && is_string( $status_label ) ? $status_label : $post->post_status;

		$json = array(
			'author'       => $author,
			'authorName'   => $author_name,
			'date'         => $date,
			'id'           => $post->ID,
			'title'        => $post_title,
			'excerpt'      => $post_excerpt,
			'imageId'      => $image_id,
			'imageSrc'     => $image_src,
			'thumbnailSrc' => $thumbnail_src,
			'type'         => $post->post_type,
			'typeLabel'    => $type_label,
			'status'       => $post->post_status,
			'statusLabel'  => $status_label,
			'link'         => $permalink,
			'extra'        => $extra_info,
		);

		/**
		 * Filters the values in an encoded post, as used in Nelio’s REST API.
		 *
		 * @param TPost   $json encoded post.
		 * @param WP_Post $post the post.
		 *
		 * @since 7.4.0
		 */
		return apply_filters( 'nab_post_json', $json, $post );
	}
}
