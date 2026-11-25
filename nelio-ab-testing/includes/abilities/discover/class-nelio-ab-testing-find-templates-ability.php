<?php
/**
 * Ability for finding templates available for template tests.
 *
 * @package Nelio_AB_Testing
 */

defined( 'ABSPATH' ) || exit;

/** Searches templates registered by WordPress and supported page builders. */
class Nelio_AB_Testing_Find_Templates_Ability extends Nelio_AB_Testing_Ability {

	/**
	 * Registers the ability.
	 *
	 * @return void
	 */
	public function register() {
		wp_register_ability(
			'nelio-ab-testing/find-templates',
			array(
				'label'               => _x( 'Find templates for a Nelio test', 'text (ability name)', 'nelio-ab-testing' ),
				'description'         => 'Searches the templates available on this site and returns the identifiers and context information needed to configure a template test.',
				'category'            => 'nelio-ab-testing',
				'input_schema'        => $this->object_schema(
					array(
						'query' => array(
							'type'        => 'string',
							'minLength'   => 1,
							'maxLength'   => 200,
							'description' => 'Optional text used to limit results by template name, builder, context, post type, or template collection.',
						),
					),
					array()
				),
				'output_schema'       => $this->object_schema(
					array(
						'templates'  => array(
							'type'        => 'array',
							'description' => 'Templates available for use as controls or existing variants in template tests.',
							'items'       => array(
								'type'                 => 'object',
								'properties'           => array(
									'id'           => array(
										'type'        => 'string',
										'description' => 'Stable identifier of the template. Use this value as the `templateId` attribute when configuring the test.',
									),
									'name'         => array(
										'type'        => 'string',
										'description' => 'Human-readable name of the template.',
									),
									'builder'      => array(
										'type'        => 'string',
										'description' => 'Identifier of the system that provides the template, such as `wordpress` or `elementor`.',
									),
									'context'      => array(
										'type'        => 'string',
										'description' => 'Template context identifier, such as a post type for a WordPress template or a builder-specific context.',
									),
									'contextLabel' => array(
										'type'        => 'string',
										'description' => 'Localized label for the template context, when available.',
									),
									'postType'     => array(
										'type'        => 'string',
										'description' => 'WordPress post type associated with the template, or an empty value for page-builder templates.',
									),
									'collection'   => array(
										'type'        => 'string',
										'description' => 'Original template collection identifier returned by the template registry.',
									),
								),
								'required'             => array( 'id', 'name', 'builder', 'context', 'contextLabel', 'postType', 'collection' ),
								'additionalProperties' => false,
							),
						),
						'pagination' => array(
							'type'                 => 'object',
							'properties'           => array(
								'page'    => array(
									'type'        => 'integer',
									'description' => 'Current result page.',
								),
								'perPage' => array(
									'type'        => 'integer',
									'description' => 'Number of templates returned per page.',
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
					),
					array( 'templates', 'pagination' )
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
	 * Checks whether the current user may search templates.
	 *
	 * @param array<array-key,mixed> $input Input.
	 *
	 * @return true|WP_Error True when the user may search templates; otherwise, an error.
	 */
	public function permissions( $input ) {
		unset( $input );
		return current_user_can( 'edit_nab_experiments' )
			? true
			: new WP_Error( 'missing-capability', 'You cannot search templates for Nelio tests.', array( 'status' => 403 ) );
	}

	/**
	 * Searches the registered templates.
	 *
	 * @param array<array-key,mixed> $input Input containing an optional search query.
	 *
	 * @return array<string,mixed>|WP_Error Matching templates or an error.
	 */
	public function execute( $input ) {
		$allowed = $this->permissions( $input );
		if ( is_wp_error( $allowed ) ) {
			return $allowed;
		}

		$query      = is_string( $input['query'] ?? null ) ? trim( $input['query'] ) : '';
		$controller = new Nelio_AB_Testing_Template_REST_Controller();
		$templates  = $controller->get_templates();
		$contexts   = $controller->get_template_contexts();
		$labels     = $this->get_context_labels( $contexts['results'] );
		$results    = array();

		foreach ( $templates['results'] as $collection => $items ) {
			$parts       = explode( ':', $collection, 2 );
			$builder_key = $parts[0];
			$context     = $parts[1] ?? '';
			$builder     = 'wp' === $builder_key ? 'WordPress' : $builder_key;
			$post_type   = 'wp' === $builder_key ? $context : '';
			$label       = $labels[ $collection ] ?? '';

			foreach ( $items as $template ) {
				$result = array(
					'id'           => (string) $template['id'],
					'name'         => $this->text( $template['name'] ),
					'builder'      => $builder,
					'context'      => $context,
					'contextLabel' => $this->text( $label ),
					'postType'     => $post_type,
					'collection'   => $collection,
				);

				if ( ! $this->matches_query( $result, $query ) ) {
					continue;
				}
				$results[] = $result;
			}
		}

		return array(
			'templates'  => $results,
			'pagination' => array(
				'page'    => 1,
				'perPage' => count( $results ),
				'pages'   => 1,
				'more'    => false,
			),
		);
	}

	/**
	 * Gets labels indexed by template collection.
	 *
	 * @param array<string,array{label:string,contexts:array<string,array{name:string,label:string}>}> $groups Template context groups.
	 *
	 * @return array<string,string> Context labels indexed by collection.
	 */
	private function get_context_labels( $groups ) {
		$labels = array();
		foreach ( $groups as $builder => $group ) {
			foreach ( $group['contexts'] as $context => $definition ) {
				$labels[ "{$builder}:{$context}" ] = $definition['label'];
			}
		}
		return $labels;
	}

	/**
	 * Checks whether a template matches a search query.
	 *
	 * @param array<string,string> $template Template summary.
	 * @param string               $query    Search query.
	 *
	 * @return bool Whether the template matches.
	 */
	private function matches_query( $template, $query ) {
		if ( '' === $query ) {
			return true;
		}
		return false !== mb_stripos( implode( ' ', $template ), $query );
	}
}
