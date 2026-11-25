<?php
/**
 * Ability for finding WordPress navigation menus.
 *
 * @package Nelio_AB_Testing
 */

defined( 'ABSPATH' ) || exit;

/** Searches navigation menus that can be used by menu tests. */
class Nelio_AB_Testing_Find_Menus_Ability extends Nelio_AB_Testing_Ability {

	/**
	 * Registers the ability.
	 *
	 * @return void
	 */
	public function register() {
		wp_register_ability(
			'nelio-ab-testing/find-menus',
			array(
				'label'               => _x( 'Find navigation menus', 'text (ability name)', 'nelio-ab-testing' ),
				'description'         => 'Searches the navigation menus available on this site and returns their identifiers, names, and theme locations.',
				'category'            => 'nelio-ab-testing',
				'input_schema'        => $this->object_schema(
					array(
						'query' => array(
							'type'        => 'string',
							'description' => 'Optional text used to limit results to menus whose names contain it.',
						),
					),
					array()
				),
				'output_schema'       => $this->object_schema(
					array(
						'menus' => array(
							'type'        => 'array',
							'description' => 'Navigation menus matching the search query.',
							'items'       => array(
								'type'                 => 'object',
								'properties'           => array(
									'id'        => array(
										'type'        => 'integer',
										'description' => 'Stable identifier of the navigation menu.',
									),
									'name'      => array(
										'type'        => 'string',
										'description' => 'Name of the navigation menu.',
									),
									'locations' => array(
										'type'        => 'array',
										'description' => 'Theme locations assigned to this menu.',
										'items'       => array(
											'type'        => 'string',
											'description' => 'Machine-readable theme location assigned to the menu.',
										),
									),
								),
								'required'             => array( 'id', 'name', 'locations' ),
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
	 * Checks whether the current user may search navigation menus.
	 *
	 * @param array<array-key,mixed> $input Input.
	 * @return true|WP_Error
	 */
	public function permissions( $input ) {
		if ( ! current_theme_supports( 'menus' ) ) {
			return new WP_Error( 'menus-not-supported', 'The active theme does not support navigation menus.', array( 'status' => 400 ) );
		}

		return current_user_can( 'edit_nab_experiments' )
			? true
			: new WP_Error( 'missing-capability', 'You cannot search navigation menus for Nelio tests.', array( 'status' => 403 ) );
	}

	/**
	 * Searches navigation menus.
	 *
	 * @param array<array-key,mixed> $input Input.
	 * @return array<string,mixed>|WP_Error
	 */
	public function execute( $input ) {
		$allowed = $this->permissions( $input );
		if ( is_wp_error( $allowed ) ) {
			return $allowed;
		}

		$query             = is_string( $input['query'] ?? null ) ? trim( $input['query'] ) : '';
		$locations         = get_nav_menu_locations();
		$locations_by_menu = array();
		foreach ( $locations as $location => $menu_id ) {
			$locations_by_menu[ absint( $menu_id ) ][] = (string) $location;
		}

		$menus = wp_get_nav_menus();
		$menus = array_filter(
			$menus,
			function ( $menu ) use ( $query ) {
				return ! get_term_meta( $menu->term_id, '_nab_experiment', true ) && ( empty( $query ) || false !== mb_stripos( $menu->name, $query ) );
			}
		);

		return array(
			'menus' => array_values(
				array_map(
					function ( $menu ) use ( $locations_by_menu ) {
						return array(
							'id'        => (int) $menu->term_id,
							'name'      => $this->text( $menu->name ),
							'locations' => $locations_by_menu[ absint( $menu->term_id ) ] ?? array(),
						);
					},
					$menus
				)
			),
		);
	}
}
