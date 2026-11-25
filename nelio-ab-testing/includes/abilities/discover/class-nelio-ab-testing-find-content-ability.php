<?php
/**
 * Ability for finding WordPress content that can be tested.
 *
 * @package Nelio_AB_Testing
 */

defined( 'ABSPATH' ) || exit;

/** Searches public and draft WordPress content that can be used in a test. */
class Nelio_AB_Testing_Find_Content_Ability extends Nelio_AB_Testing_Ability {

	/** Register the ability. */
	public function register() {
		wp_register_ability(
			'nelio-ab-testing/find-content',
			array(
				'label'               => _x( 'Find content for a Nelio test', 'text (ability name)', 'nelio-ab-testing' ),
				'description'         => 'Searches public or draft WordPress content by title, excerpt, or content and returns enough metadata to identify an item before configuring a test.',
				'category'            => 'nelio-ab-testing',
				'input_schema'        => $this->object_schema(
					array(
						'query'    => array(
							'type'        => 'string',
							'minLength'   => 1,
							'maxLength'   => 200,
							'description' => 'Text used to search content titles, excerpts, and content.',
						),
						'postType' => array(
							'type'        => 'string',
							'default'     => 'page',
							'maxLength'   => 50,
							'description' => 'Machine-readable logical content type identifier for the content or form to find. Use a type returned by the content-type discovery information.',
						),
						'page'     => array(
							'type'        => 'integer',
							'minimum'     => 1,
							'default'     => 1,
							'description' => 'Page of search results to return.',
						),
						'perPage'  => array(
							'type'        => 'integer',
							'minimum'     => 1,
							'maximum'     => 50,
							'default'     => 20,
							'description' => 'Maximum number of content items to return per page.',
						),
					),
					array( 'query' )
				),
				'output_schema'       => $this->object_schema(
					array(
						'results'    => array(
							'type'        => 'array',
							'description' => 'Content items that may be selected for the test.',
							'items'       => array(
								'type'                 => 'object',
								'properties'           => array(
									'id'         => array(
										'oneOf'       => array(
											array(
												'type'    => 'integer',
												'minimum' => 1,
											),
											array(
												'type' => 'string',
												'minLength' => 1,
											),
										),
										'description' => 'Stable identifier of the content item. The identifier can be numeric or a string, depending on the integration that provides the entity.',
									),
									'type'       => array(
										'type'        => 'string',
										'description' => 'Logical post type identifier of the content item.',
									),
									'status'     => array(
										'type'        => 'string',
										'description' => 'WordPress status of the content item.',
									),
									'title'      => array(
										'type'        => 'string',
										'description' => 'Title of the content item.',
									),
									'excerpt'    => array(
										'type'        => 'string',
										'description' => 'Excerpt or short text extracted from the content item.',
									),
									'url'        => array(
										'type'        => 'string',
										'description' => 'Permalink of the content item, when available.',
									),
									'previewUrl' => array(
										'type'        => 'string',
										'description' => 'Preview URL of the content item, when available.',
									),
									'editUrl'    => array(
										'type'        => 'string',
										'description' => 'Editing URL of the content item, when available.',
									),
									'authorId'   => array(
										'type'        => 'integer',
										'description' => 'Identifier of the content item author.',
									),
									'updatedAt'  => array(
										'type'        => 'string',
										'description' => 'Last modification time of the content item.',
									),
								),
								'required'             => array( 'id', 'type', 'status', 'title', 'excerpt', 'url', 'previewUrl', 'editUrl', 'authorId', 'updatedAt' ),
								'additionalProperties' => false,
							),
						),
						'pagination' => array(
							'type'                 => 'object',
							'description'          => 'Pagination information for the content search.',
							'properties'           => array(
								'page'    => array(
									'type'        => 'integer',
									'description' => 'Current result page.',
								),
								'perPage' => array(
									'type'        => 'integer',
									'description' => 'Number of results returned per page.',
								),
								'pages'   => array(
									'type'        => 'integer',
									'description' => 'Total number of result pages.',
								),
								'more'    => array(
									'type'        => 'boolean',
									'description' => 'Whether another result page is available.',
								),
							),
							'required'             => array( 'page', 'perPage', 'pages', 'more' ),
							'additionalProperties' => false,
						),
					)
				),
				'permission_callback' => array( $this, 'permissions' ),
				'execute_callback'    => array( $this, 'execute' ),
				'meta'                => array(
					'show_in_rest' => false,
					'mcp'          => array(
						'public' => true,
						'type'   => 'tool',
						'server' => 'nelio-ab-testing',
					),
					'annotations'  => array(
						'readonly'    => true,
						'destructive' => false,
						'idempotent'  => true,
					),
				),
			)
		);
	}

	/**
	 * Check whether the current user may search content.
	 *
	 * @param array<array-key,mixed> $input Input.
	 * @return true|WP_Error
	 */
	public function permissions( $input ) {
		return current_user_can( 'edit_nab_experiments' )
			? true
			: new WP_Error( 'missing-capability', 'You cannot search content for Nelio tests.', array( 'status' => 403 ) );
	}

	/**
	 * Search content.
	 *
	 * @param array<array-key,mixed> $input Input.
	 * @return array<string,mixed>|WP_Error
	 */
	public function execute( $input ) {
		$allowed = $this->permissions( $input );
		if ( is_wp_error( $allowed ) ) {
			return $allowed;
		}
		$query     = sanitize_text_field( $input['query'] ?? '' );
		$post_type = sanitize_key( is_string( $input['postType'] ?? null ) ? $input['postType'] : 'page' );
		$page      = max( 1, absint( $input['page'] ?? 1 ) );
		$per_page  = min( 50, max( 1, absint( $input['perPage'] ?? 20 ) ) );
		if ( ! in_array( $post_type, $this->get_discoverable_content_types(), true ) ) {
			return new WP_Error(
				'unsupported-content-type',
				sprintf(
					'The post type `%s` is not supported for test-content discovery.',
					$post_type
				)
			);
		}
		$request = new WP_REST_Request( 'GET' );
		$request->set_param( 'query', $query );
		$request->set_param( 'type', $post_type );
		$request->set_param( 'page', $page );
		$request->set_param( 'per_page', $per_page );
		$data = ( new Nelio_AB_Testing_Post_REST_Controller() )->search_posts( $request );
		if ( is_wp_error( $data ) ) {
			return $data; // @codeCoverageIgnore
		}
		$results = array_map( array( $this, 'summarize_post' ), $data['results'] );
		return array(
			'results'    => $results,
			'pagination' => array(
				'page'    => $page,
				'perPage' => $per_page,
				'pages'   => absint( $data['pagination']['pages'] ),
				'more'    => ! empty( $data['pagination']['more'] ),
			),
		);
	}

	/**
	 * Returns content and form types that can be discovered by the AI workflow.
	 *
	 * @return list<string>
	 */
	private function get_discoverable_content_types() {
		$types      = array();
		$registered = ( new Nelio_AB_Testing_Post_REST_Controller() )->get_post_types();
		foreach ( $registered as $type => $definition ) {
			if ( 'nab_experiment' !== $type ) {
				$types[] = $type;
			}
		}

		return $types;
	}

	/**
	 * Build a safe content summary.
	 *
	 * @param array<string,mixed> $post REST post response.
	 * @return array<string,mixed>
	 */
	private function summarize_post( $post ) {
		$post_id     = $post['id'] ?? '';
		$post_id     = is_int( $post_id ) || is_string( $post_id ) ? $post_id : '';
		$source      = is_numeric( $post_id ) ? get_post( absint( $post_id ) ) : null;
		$preview_url = $source ? get_preview_post_link( $source ) : false;
		$edit_url    = $source ? get_edit_post_link( absint( $post_id ), 'raw' ) : false;
		return array(
			'id'         => $post_id,
			'type'       => is_string( $post['type'] ?? null ) ? $post['type'] : '',
			'status'     => is_string( $post['status'] ?? null ) ? $post['status'] : '',
			'title'      => $this->text( is_string( $post['title'] ?? null ) ? $post['title'] : '' ),
			'excerpt'    => $this->text( is_string( $post['excerpt'] ?? null ) ? $post['excerpt'] : '' ),
			'url'        => is_string( $post['link'] ?? null ) ? $post['link'] : '',
			'previewUrl' => is_string( $preview_url ) ? $preview_url : '',
			'editUrl'    => is_string( $edit_url ) ? $edit_url : '',
			'authorId'   => absint( $post['author'] ?? 0 ),
			'updatedAt'  => $source ? get_post_modified_time( 'c', true, $source ) : '',
		);
	}
}
