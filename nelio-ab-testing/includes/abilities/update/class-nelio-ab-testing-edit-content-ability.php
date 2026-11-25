<?php
/**
 * Ability for editing generated test content.
 *
 * @package Nelio_AB_Testing
 */

defined( 'ABSPATH' ) || exit;

/** Edits the title and post content of generated test content. */
class Nelio_AB_Testing_Edit_Content_Ability extends Nelio_AB_Testing_Ability {

	/**
	 * Registers the ability with WordPress.
	 *
	 * @return void
	 */
	public function register() {
		wp_register_ability(
			'nelio-ab-testing/edit-content',
			array(
				'label'               => _x( 'Edit content', 'text (ability name)', 'nelio-ab-testing' ),
				'description'         => 'Replaces the title and raw post content of generated WordPress content belonging to a test variant. Use it only with content created by a non-control variant when its page builder does not expose a dedicated editing ability. It cannot edit control content or existing content that the test is comparing, and it does not update builder-specific content or metadata.',
				'category'            => 'nelio-ab-testing',
				'input_schema'        => $this->object_schema(
					array(
						'postId'      => array(
							'type'        => 'integer',
							'minimum'     => 1,
							'description' => 'Identifier of the generated post content to edit.',
						),
						'title'       => array(
							'type'        => 'string',
							'minLength'   => 1,
							'description' => 'Complete new title for the content.',
						),
						'postContent' => array(
							'type'        => 'string',
							'minLength'   => 1,
							'description' => 'Complete new raw post content. The value replaces the existing content; it is not merged.',
						),
					),
					array( 'postId', 'title', 'postContent' )
				),
				'output_schema'       => $this->object_schema(
					array(
						'postId'      => array(
							'type'        => 'integer',
							'description' => 'Identifier of the edited content.',
						),
						'title'       => array(
							'type'        => 'string',
							'description' => 'Final title stored for the content.',
						),
						'postContent' => array(
							'type'        => 'string',
							'description' => 'Final raw post content stored for the content.',
						),
					),
					array( 'postId', 'title', 'postContent' )
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
						'readonly'    => false,
						'destructive' => false,
						'idempotent'  => true,
					),
				),
			)
		);
	}

	/**
	 * Checks whether the current user may edit generated test content.
	 *
	 * @param array<array-key,mixed> $input Input containing the post identifier.
	 *
	 * @return true|WP_Error True when the user may edit the content; otherwise, an error.
	 */
	public function permissions( $input ) {
		$post_id = absint( $input['postId'] ?? 0 );
		$test_id = absint( get_post_meta( $post_id, '_nab_experiment', true ) );
		if ( ! $test_id ) {
			return new WP_Error( 'invalid-content', 'The selected content does not belong to a generated test variant.' );
		}

		$experiment = nab_get_experiment( $test_id );
		if ( is_wp_error( $experiment ) ) {
			return $experiment;
		}

		$can_be_edited = $experiment->can_be_edited();
		if ( is_wp_error( $can_be_edited ) ) {
			return $can_be_edited;
		}

		$post = get_post( $post_id );
		if ( ! $post ) {
			return new WP_Error( 'content-not-found', 'The selected generated variant content could not be found.' );
		}

		$alternatives    = $experiment->get_alternatives();
		$alternative_ids = array_map( fn( $a ) => absint( $a['attributes']['postId'] ?? 0 ), $alternatives );
		unset( $alternative_ids[0] );

		$is_generated_alternative = (
			empty( $alternatives[0]['attributes']['testAgainstExistingContent'] ) &&
			in_array( $post_id, $alternative_ids, true )
		);
		if ( ! $is_generated_alternative ) {
			return new WP_Error( 'invalid-content', 'The selected content is not an editable alternative created by this test.' );
		}

		if ( ! nelioab()->does_test_type_support_ai( $experiment->get_type() ) ) {
			return new WP_Error( 'unsupported-test-type', 'This test type is registered but cannot be configured through AI abilities.' );
		}

		return true;
	}

	/**
	 * Replaces the title and post content of generated test content.
	 *
	 * @param array<array-key,mixed> $input Input containing the post identifier, title, and post content.
	 *
	 * @return array<string,mixed>|WP_Error Updated variant content or an error.
	 */
	public function execute( $input ) {
		$allowed = $this->permissions( $input );
		if ( is_wp_error( $allowed ) ) {
			return $allowed;
		}

		$post_id = absint( $input['postId'] ?? 0 );
		$post    = get_post( $post_id );
		assert( $post instanceof WP_Post );

		$title = is_string( $input['title'] ?? null ) ? $input['title'] : '';
		if ( '' === $title ) {
			return new WP_Error( 'invalid-title', 'The replacement title cannot be empty.' );
		}

		$post_content = is_string( $input['postContent'] ?? null ) ? $input['postContent'] : '';
		if ( '' === $post_content ) {
			return new WP_Error( 'invalid-content', 'The replacement post content cannot be empty.' );
		}

		$updated_id = wp_update_post(
			wp_slash(
				array(
					'ID'           => $post_id,
					'post_title'   => $title,
					'post_content' => $post_content,
				)
			),
			true
		);
		if ( is_wp_error( $updated_id ) ) {
			return $updated_id; // @codeCoverageIgnore
		}

		return array(
			'postId'      => $post_id,
			'title'       => get_the_title( $post_id ),
			'postContent' => get_post_field( 'post_content', $post_id ),
		);
	}
}
