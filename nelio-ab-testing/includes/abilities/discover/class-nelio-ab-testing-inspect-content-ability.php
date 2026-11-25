<?php
/**
 * Ability for inspecting editable WordPress content.
 *
 * @package Nelio_AB_Testing
 */

defined( 'ABSPATH' ) || exit;

use function Nelio_AB_Testing\Experiment_Library\Popup_Experiment\get_testable_popup_types;

/** Inspects stored content and its page-builder information. */
class Nelio_AB_Testing_Inspect_Content_Ability extends Nelio_AB_Testing_Ability {

	/**
	 * Registers the ability with WordPress.
	 *
	 * @return void
	 */
	public function register() {
		$content_request = $this->object_schema(
			array(
				'id'   => array(
					'oneOf'       => array(
						array(
							'type'    => 'integer',
							'minimum' => 1,
						),
						array(
							'type'      => 'string',
							'minLength' => 1,
						),
					),
					'description' => 'Identifier of the content to inspect. The identifier can be numeric or a string, depending on the integration that provides the entity.',
				),
				'type' => array(
					'type'        => 'string',
					'minLength'   => 1,
					'description' => 'Logical post type identifier of the content to inspect, as returned by content discovery.',
				),
			),
			array( 'id', 'type' )
		);
		wp_register_ability(
			'nelio-ab-testing/inspect-content',
			array(
				'label'               => _x( 'Inspect content for a Nelio test', 'text (ability name)', 'nelio-ab-testing' ),
				'description'         => 'Inspects stored WordPress content and returns basic metadata, its raw post content, and the page builder detected for it. Some popup integrations do not expose meaningful generic post content or builder information, so those fields can be empty. The response does not render the page and does not describe the final browser DOM. Use the returned permalink when rendered HTML is required. To render a test variant, retrieve its temporary preview URL through the test-reading abilities; preview URLs expire, so retrieve them again when a fresh URL is needed.',
				'category'            => 'nelio-ab-testing',
				'input_schema'        => $content_request,
				'output_schema'       => $this->object_schema(
					array(
						'content' => array(
							'type'                 => 'object',
							'properties'           => array(
								'id'          => array(
									'oneOf'       => array(
										array(
											'type'    => 'integer',
											'minimum' => 1,
										),
										array(
											'type'      => 'string',
											'minLength' => 1,
										),
									),
									'description' => 'Stable identifier of the content. The identifier can be numeric or a string, depending on the integration that provides the entity.',
								),
								'type'        => array(
									'type'        => 'string',
									'description' => 'Logical post type identifier of the content.',
								),
								'status'      => array(
									'type'        => 'string',
									'description' => 'WordPress status of the content.',
								),
								'title'       => array(
									'type'        => 'string',
									'description' => 'Title of the content.',
								),
								'url'         => array(
									'type'        => 'string',
									'description' => 'Permalink of the content, when available.',
								),
								'editUrl'     => array(
									'type'        => 'string',
									'description' => 'Editing URL of the content, when available.',
								),
								'builder'     => array(
									'type'        => 'string',
									'description' => 'Detected page-builder identifier, or an empty value when no generic builder information is available.',
								),
								'postContent' => array(
									'type'        => 'string',
									'description' => 'Raw post content, or an empty value when the content integration does not expose meaningful generic post content.',
								),
							),
							'required'             => array( 'id', 'type', 'status', 'title', 'url', 'editUrl', 'builder', 'postContent' ),
							'additionalProperties' => false,
						),
					),
					array( 'content' )
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
	 * Checks whether the current user may inspect content.
	 *
	 * @param array<array-key,mixed> $input Input.
	 *
	 * @return true|WP_Error True when the user may inspect content; otherwise, an error.
	 */
	public function permissions( $input ) {
		unset( $input );
		return current_user_can( 'edit_nab_experiments' )
			? true
			: new WP_Error( 'missing-capability', 'You cannot inspect content for Nelio tests.', array( 'status' => 403 ) );
	}

	/**
	 * Inspects one content item.
	 *
	 * @param array<array-key,mixed> $input Input containing content identifiers.
	 *
	 * @return array<string,mixed>|WP_Error Content information or an error.
	 */
	public function execute( $input ) {
		$allowed = $this->permissions( $input );
		if ( is_wp_error( $allowed ) ) {
			return $allowed;
		}

		$input_id = $input['id'] ?? '';
		$post_id  = is_int( $input_id ) || is_string( $input_id ) ? $input_id : '';
		$post_id  = is_string( $post_id ) ? sanitize_text_field( $post_id ) : absint( $post_id );
		$type     = sanitize_key( is_string( $input['type'] ?? null ) ? $input['type'] : '' );
		$post     = is_numeric( $post_id ) ? get_post( absint( $post_id ) ) : null;
		if ( $post && 'nab_experiment' === $post->post_type ) {
			return new WP_Error( 'content-not-found', 'The requested content was not found.' );
		}

		$request = new WP_REST_Request( 'GET' );
		$request->set_param( 'id', $post_id );
		$request->set_param( 'type', $type );
		$content = ( new Nelio_AB_Testing_Post_REST_Controller() )->get_post( $request );
		if ( is_wp_error( $content ) ) {
			return $content;
		}

		$is_popup = in_array( $content['type'], get_testable_popup_types(), true );
		$edit_url = $post ? get_edit_post_link( absint( $post_id ), 'raw' ) : false;
		return array(
			'content' => array(
				'id'          => $content['id'],
				'type'        => $content['type'],
				'status'      => $content['status'],
				'title'       => $this->text( $content['title'] ),
				'url'         => $content['link'],
				'editUrl'     => is_string( $edit_url ) ? $edit_url : '',
				'builder'     => $is_popup || ! $post ? '' : $this->get_builder( $post ),
				'postContent' => $is_popup || ! $post ? '' : $post->post_content,
			),
		);
	}

	/**
	 * Infers the page builder used by a post.
	 *
	 * @param WP_Post $post WordPress post.
	 *
	 * @return string Builder identifier.
	 */
	private function get_builder( $post ) {
		$builder = 'unknown';
		if ( function_exists( 'has_blocks' ) && has_blocks( $post->post_content ) ) {
			$builder = 'gutenberg';
		}

		/**
		 * Filters the page-builder identifier inferred for content.
		 *
		 * @param string  $builder Inferred builder identifier.
		 * @param WP_Post $post    WordPress post.
		 *
		 * @since 9.0.0
		 */
		return apply_filters( 'nab_content_builder', $builder, $post );
	}
}
