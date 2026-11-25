<?php
/**
 * Ability for discovering WooCommerce product taxonomy terms.
 *
 * @package Nelio_AB_Testing
 */

defined( 'ABSPATH' ) || exit;

/** Searches product categories and tags that can be used in product selections. */
class Nelio_AB_Testing_Find_Product_Terms_Ability extends Nelio_AB_Testing_Ability {

	/** Register the ability. */
	public function register() {
		wp_register_ability(
			'nelio-ab-testing/find-product-terms',
			array(
				'label'               => _x( 'Find WooCommerce product terms', 'text (ability name)', 'nelio-ab-testing' ),
				'description'         => 'Searches WooCommerce product categories and tags and returns their identifiers and labels for use in product selections.',
				'category'            => 'nelio-ab-testing',
				'input_schema'        => $this->object_schema(
					array(
						'taxonomy' => array(
							'type'        => 'string',
							'enum'        => array( 'product_cat', 'product_tag' ),
							'description' => 'Product taxonomy to search.',
						),
						'query'    => array(
							'type'        => 'string',
							'description' => 'Optional text used to search term names and slugs.',
						),
						'page'     => array(
							'type'        => 'integer',
							'minimum'     => 1,
							'default'     => 1,
							'description' => 'Page of results to return.',
						),
						'perPage'  => array(
							'type'        => 'integer',
							'minimum'     => 1,
							'maximum'     => 100,
							'default'     => 100,
							'description' => 'Maximum number of terms to return per page.',
						),
					),
					array( 'taxonomy' )
				),
				'output_schema'       => $this->object_schema(
					array(
						'terms'      => array(
							'type'        => 'array',
							'description' => 'Product taxonomy terms matching the requested search.',
							'items'       => array(
								'type'                 => 'object',
								'properties'           => array(
									'id'       => array(
										'type'        => 'integer',
										'description' => 'Identifier of the term.',
									),
									'taxonomy' => array(
										'type'        => 'string',
										'description' => 'Taxonomy containing the term.',
									),
									'name'     => array(
										'type'        => 'string',
										'description' => 'Human-readable name of the term.',
									),
									'slug'     => array(
										'type'        => 'string',
										'description' => 'URL-friendly slug of the term.',
									),
								),
								'required'             => array( 'id', 'taxonomy', 'name', 'slug' ),
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
									'description' => 'Number of terms returned per page.',
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
	 * Checks whether the current user may search product terms.
	 *
	 * @param array<array-key,mixed> $input Input.
	 *
	 * @return true|WP_Error True when the user may search terms; otherwise, an error.
	 */
	public function permissions( $input ) {
		if ( ! current_user_can( 'edit_nab_experiments' ) ) {
			return new WP_Error( 'missing-capability', 'You cannot search WooCommerce product terms for Nelio tests.', array( 'status' => 403 ) );
		}

		$taxonomy = is_string( $input['taxonomy'] ?? null ) ? $input['taxonomy'] : '';
		if ( ! taxonomy_exists( $taxonomy ) || ! in_array( $taxonomy, array( 'product_cat', 'product_tag' ), true ) ) {
			return new WP_Error( 'unsupported-taxonomy', 'The requested product taxonomy is not available on this site.', array( 'status' => 400 ) );
		}

		return true;
	}

	/**
	 * Searches product taxonomy terms.
	 *
	 * @param array<array-key,mixed> $input Input.
	 *
	 * @return array<string,mixed>|WP_Error Search results or an error.
	 */
	public function execute( $input ) {
		$allowed = $this->permissions( $input );
		if ( is_wp_error( $allowed ) ) {
			return $allowed;
		}

		$taxonomy = is_string( $input['taxonomy'] ?? null ) ? $input['taxonomy'] : '';
		$query    = sanitize_text_field( $input['query'] ?? '' );
		$page     = max( 1, absint( $input['page'] ?? 1 ) );
		$per_page = min( 100, max( 1, absint( $input['perPage'] ?? 100 ) ) );
		$terms    = get_terms(
			array(
				'taxonomy'   => $taxonomy,
				'search'     => $query,
				'hide_empty' => false,
				'number'     => $per_page,
				'offset'     => ( $page - 1 ) * $per_page,
			)
		);

		if ( is_wp_error( $terms ) ) {
			return $terms; // @codeCoverageIgnore
		}

		$total = wp_count_terms(
			array(
				'taxonomy'   => $taxonomy,
				'search'     => $query,
				'hide_empty' => false,
			)
		);
		$total = is_wp_error( $total ) ? 0 : absint( $total );
		return array(
			'terms'      => array_map(
				fn( $term ) => array(
					'id'       => absint( $term->term_id ),
					'taxonomy' => $taxonomy,
					'name'     => html_entity_decode( $term->name ),
					'slug'     => $term->slug,
				),
				$terms
			),
			'pagination' => array(
				'page'    => $page,
				'perPage' => $per_page,
				'pages'   => $total ? (int) ceil( $total / $per_page ) : 0,
				'more'    => $page * $per_page < $total,
			),
		);
	}
}
