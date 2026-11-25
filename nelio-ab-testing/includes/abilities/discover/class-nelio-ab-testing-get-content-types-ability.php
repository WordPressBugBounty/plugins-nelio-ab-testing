<?php
/**
 * Ability for retrieving content types exposed by Nelio A/B Testing.
 *
 * @package Nelio_AB_Testing
 */

defined( 'ABSPATH' ) || exit;

/** Returns the logical content and form types available through the REST registry. */
class Nelio_AB_Testing_Get_Content_Types_Ability extends Nelio_AB_Testing_Ability {

	/**
	 * Registers the ability.
	 *
	 * @return void
	 */
	public function register() {
		wp_register_ability(
			'nelio-ab-testing/get-content-types',
			array(
				'label'               => _x( 'Get content types', 'text (ability name)', 'nelio-ab-testing' ),
				'description'         => 'Returns the logical content and form types available through Nelio A/B Testing for discovery and configuration.',
				'category'            => 'nelio-ab-testing',
				'input_schema'        => $this->object_schema( array(), array() ),
				'output_schema'       => $this->object_schema(
					array(
						'contentTypes' => array(
							'type'        => 'array',
							'description' => 'Logical content and form types available for content discovery.',
							'items'       => array(
								'type'                 => 'object',
								'properties'           => array(
									'name'          => array(
										'type'        => 'string',
										'description' => 'Machine-readable logical content type identifier.',
									),
									'pluralLabel'   => array(
										'type'        => 'string',
										'description' => 'Localized plural label of the content type.',
									),
									'singularLabel' => array(
										'type'        => 'string',
										'description' => 'Localized singular label of the content type.',
									),
									'kind'          => array(
										'type'        => 'string',
										'enum'        => array( 'entity', 'form' ),
										'description' => 'Whether the type represents a content entity or a form.',
									),
								),
								'required'             => array( 'name', 'pluralLabel', 'singularLabel', 'kind' ),
								'additionalProperties' => false,
							),
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
	 * Checks whether the current user may inspect content types.
	 *
	 * @param array<array-key,mixed> $input Input.
	 * @return true|WP_Error
	 */
	public function permissions( $input ) {
		return current_user_can( 'edit_nab_experiments' )
			? true
			: new WP_Error( 'missing-capability', 'You cannot inspect content types for Nelio tests.', array( 'status' => 403 ) );
	}

	/**
	 * Returns content and form types from the REST registry.
	 *
	 * @param array<array-key,mixed> $input Input.
	 * @return array<string,mixed>|WP_Error
	 */
	public function execute( $input ) {
		$allowed = $this->permissions( $input );
		if ( is_wp_error( $allowed ) ) {
			return $allowed;
		}

		$registered = ( new Nelio_AB_Testing_Post_REST_Controller() )->get_post_types();
		$result     = array();
		foreach ( $registered as $name => $type ) {
			if ( 'nab_experiment' === $name ) {
				continue;
			}
			$result[] = array(
				'name'          => $name,
				'pluralLabel'   => $this->text( $type['label'] ),
				'singularLabel' => $this->text( $type['labels']['singular_name'] ),
				'kind'          => $type['kind'],
			);
		}

		return array( 'contentTypes' => $result );
	}
}
