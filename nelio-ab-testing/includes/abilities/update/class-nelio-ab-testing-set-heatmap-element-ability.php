<?php
/**
 * Ability for configuring the element tracked by a heatmap test.
 *
 * @package Nelio_AB_Testing
 */

defined( 'ABSPATH' ) || exit;

/** Configures the element tracked by an editable heatmap test. */
class Nelio_AB_Testing_Set_Heatmap_Element_Ability extends Nelio_AB_Testing_Ability {

	/**
	 * Registers the ability with WordPress.
	 *
	 * @return void
	 */
	public function register() {
		$post_element = $this->object_schema(
			array(
				'mode'     => array(
					'type'        => 'string',
					'enum'        => array( 'post' ),
					'description' => 'Tracks a WordPress post or other public post type.',
				),
				'postType' => array(
					'type'        => 'string',
					'minLength'   => 1,
					'description' => 'Post type of the tracked WordPress content.',
				),
				'postId'   => array(
					'type'        => 'integer',
					'minimum'     => 1,
					'description' => 'Database ID of the tracked WordPress content.',
				),
			),
			array( 'mode', 'postType', 'postId' )
		);
		$url_element  = $this->object_schema(
			array(
				'mode' => array(
					'type'        => 'string',
					'enum'        => array( 'url' ),
					'description' => 'Tracks a URL.',
				),
				'url'  => array(
					'type'        => 'string',
					'minLength'   => 1,
					'description' => 'URL of the page tracked by the heatmap.',
				),
			),
			array( 'mode', 'url' )
		);

		wp_register_ability(
			'nelio-ab-testing/set-heatmap-element',
			array(
				'label'               => _x( 'Set the heatmap element', 'text (ability name)', 'nelio-ab-testing' ),
				'description'         => 'Sets the element tracked by an editable heatmap test. Choose either WordPress content by post type and ID or a URL. This ability is specific to heatmap tests.',
				'category'            => 'nelio-ab-testing',
				'input_schema'        => $this->object_schema(
					array(
						'testId'  => array(
							'type'        => 'integer',
							'minimum'     => 1,
							'description' => 'Identifier of the editable heatmap test.',
						),
						'element' => array(
							'type'        => 'object',
							'description' => 'Element to track. Provide either a post element or a URL.',
							'oneOf'       => array( $post_element, $url_element ),
						),
					),
					array( 'testId', 'element' )
				),
				'output_schema'       => $this->object_schema(
					array(
						'testId'  => array(
							'type'        => 'integer',
							'description' => 'Identifier of the updated heatmap test.',
						),
						'element' => array(
							'type'        => 'object',
							'description' => 'Element currently tracked by the heatmap.',
							'oneOf'       => array( $post_element, $url_element ),
						),
					),
					array( 'testId', 'element' )
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
	 * Checks whether the current user may configure a heatmap element.
	 *
	 * @param array<array-key,mixed> $input Input containing the test identifier.
	 *
	 * @return true|WP_Error True when the user may configure the element; otherwise, an error.
	 */
	public function permissions( $input ) {
		$experiment = nab_get_experiment( $this->integer_input( $input, 'testId' ) );
		if ( is_wp_error( $experiment ) ) {
			return $experiment;
		}
		if ( ! $experiment instanceof Nelio_AB_Testing_Heatmap ) {
			return new WP_Error( 'invalid-test-type', 'This ability only configures heatmap tests.' );
		}
		if ( ! nelioab()->does_test_type_support_ai( $experiment->get_type() ) ) {
			return new WP_Error( 'unsupported-test-type', 'This heatmap test is not configured for AI abilities.' );
		}
		return $experiment->can_be_edited();
	}

	/**
	 * Sets the element tracked by a heatmap test.
	 *
	 * @param array<array-key,mixed> $input Input containing the test and tracked element.
	 *
	 * @return array<string,mixed>|WP_Error Updated heatmap element or an error.
	 */
	public function execute( $input ) {
		$allowed = $this->permissions( $input );
		if ( is_wp_error( $allowed ) ) {
			return $allowed;
		}

		$experiment = nab_get_experiment( $this->integer_input( $input, 'testId' ) );
		assert( $experiment instanceof Nelio_AB_Testing_Heatmap );
		$element = is_array( $input['element'] ?? null ) ? $input['element'] : array();
		$mode    = is_string( $element['mode'] ?? null ) ? $element['mode'] : '';

		if ( 'post' === $mode ) {
			$post_type = is_string( $element['postType'] ?? null ) ? trim( $element['postType'] ) : '';
			$post_id   = absint( $element['postId'] ?? 0 );
			$post      = get_post( $post_id );
			if ( '' === $post_type || ! $post || $post->post_type !== $post_type ) {
				return new WP_Error( 'invalid-heatmap-element', 'The selected post type and post ID do not identify existing content.' );
			}
			$experiment->set_tracking_mode( 'post' );
			$experiment->set_tracked_post_type( $post_type );
			$experiment->set_tracked_post_id( $post_id );
			$experiment->set_tracked_url( '' );
		} elseif ( 'url' === $mode ) {
			$url      = is_string( $element['url'] ?? null ) ? trim( $element['url'] ) : '';
			$home_url = untrailingslashit( nab_home_url() );
			if ( '' === $url || false === wp_http_validate_url( $url ) ) {
				return new WP_Error( 'invalid-heatmap-element', 'The tracked URL must be a valid URL.' );
			}
			if ( 0 !== strpos( $url, $home_url ) || ( $url !== $home_url && 0 !== strpos( $url, $home_url . '/' ) ) ) {
				return new WP_Error( 'invalid-heatmap-element', 'The tracked URL must belong to this WordPress site.' );
			}
			$experiment->set_tracking_mode( 'url' );
			$experiment->set_tracked_post_type( '' );
			$experiment->set_tracked_post_id( 0 );
			$experiment->set_tracked_url( $url );
		} else {
			return new WP_Error( 'invalid-heatmap-element', 'The tracked element must use either post mode or URL mode.' );
		}

		nab_ai_refresh_heatmap_status( $experiment );
		$experiment->save();
		return $this->format_element( $experiment );
	}

	/**
	 * Formats the current heatmap element.
	 *
	 * @param Nelio_AB_Testing_Heatmap $heatmap Heatmap.
	 *
	 * @return array<string,mixed> Current tracked element.
	 */
	private function format_element( $heatmap ) {
		if ( 'post' === $heatmap->get_tracking_mode() ) {
			return array(
				'testId'  => $heatmap->get_id(),
				'element' => array(
					'mode'     => 'post',
					'postType' => $heatmap->get_tracked_post_type(),
					'postId'   => $heatmap->get_tracked_post_id(),
				),
			);
		}

		return array(
			'testId'  => $heatmap->get_id(),
			'element' => array(
				'mode' => 'url',
				'url'  => $heatmap->get_tracked_url(),
			),
		);
	}
}
