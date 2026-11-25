<?php
/**
 * Ability for inspecting a WordPress navigation menu.
 *
 * @package Nelio_AB_Testing
 */

defined( 'ABSPATH' ) || exit;

/** Returns the hierarchy and relevant details of a navigation menu. */
class Nelio_AB_Testing_Get_Menu_Ability extends Nelio_AB_Testing_Ability {

	/**
	 * Registers the ability.
	 *
	 * @return void
	 */
	public function register() {
		wp_register_ability(
			'nelio-ab-testing/get-menu',
			array(
				'label'               => _x( 'Get a navigation menu', 'text (ability name)', 'nelio-ab-testing' ),
				'description'         => 'Returns the structure and items of a specific navigation menu, including parent-child relationships.',
				'category'            => 'nelio-ab-testing',
				'input_schema'        => $this->object_schema(
					array(
						'id' => array(
							'type'        => 'integer',
							'minimum'     => 1,
							'description' => 'Stable identifier of the navigation menu to inspect.',
						),
					),
					array( 'id' )
				),
				'output_schema'       => $this->object_schema(
					array(
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
						'items'     => array(
							'type'        => 'array',
							'description' => 'Items in menu order, with parent identifiers that describe the menu hierarchy.',
							'items'       => array(
								'type'                 => 'object',
								'properties'           => array(
									'id'       => array(
										'type'        => 'integer',
										'description' => 'Stable identifier of the menu item.',
									),
									'title'    => array(
										'type'        => 'string',
										'description' => 'Visible label of the menu item.',
									),
									'parentId' => array(
										'type'        => 'integer',
										'description' => 'Identifier of the parent menu item, or 0 when the item is at the top level.',
									),
									'order'    => array(
										'type'        => 'integer',
										'description' => 'Position of the item among the menu items.',
									),
									'url'      => array(
										'type'        => 'string',
										'description' => 'Destination URL of the menu item.',
									),
									'type'     => array(
										'type'        => 'string',
										'description' => 'WordPress menu-item type.',
									),
									'object'   => array(
										'type'        => 'string',
										'description' => 'WordPress object type associated with the item, when applicable.',
									),
									'objectId' => array(
										'type'        => 'integer',
										'description' => 'Identifier of the WordPress object associated with the item, or 0 when there is none.',
									),
								),
								'required'             => array( 'id', 'title', 'parentId', 'order', 'url', 'type', 'object', 'objectId' ),
								'additionalProperties' => false,
							),
						),
					),
					array( 'id', 'name', 'locations', 'items' )
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
	 * Checks whether the current user may inspect navigation menus.
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
			: new WP_Error( 'missing-capability', 'You cannot inspect navigation menus for Nelio tests.', array( 'status' => 403 ) );
	}

	/**
	 * Returns one navigation menu and its hierarchy.
	 *
	 * @param array<array-key,mixed> $input Input containing the menu identifier.
	 * @return array<string,mixed>|WP_Error
	 */
	public function execute( $input ) {
		$allowed = $this->permissions( $input );
		if ( is_wp_error( $allowed ) ) {
			return $allowed;
		}

		$menu_id = absint( $input['id'] ?? 0 );
		$menu    = get_term( $menu_id, 'nav_menu' );
		if ( ! $menu || is_wp_error( $menu ) ) {
			return new WP_Error(
				'menu-not-found',
				sprintf(
					'The navigation menu with ID `%s` was not found.',
					$menu_id
				)
			);
		}

		$locations = array();
		foreach ( get_nav_menu_locations() as $location => $assigned_menu_id ) {
			if ( absint( $assigned_menu_id ) === $menu_id ) {
				$locations[] = (string) $location;
			}
		}

		$items = wp_get_nav_menu_items( $menu_id );
		if ( ! is_array( $items ) ) {
			$items = array();
		}

		return array(
			'id'        => $menu_id,
			'name'      => $this->text( $menu->name ),
			'locations' => $locations,
			'items'     => array_values( array_map( array( $this, 'summarize_item' ), $items ) ),
		);
	}

	/**
	 * Summarizes a navigation menu item.
	 *
	 * @param WP_Post $item Navigation menu item.
	 * @return array<string,mixed>
	 */
	private function summarize_item( $item ) {
		$item_data = get_object_vars( $item );
		return array(
			'id'       => absint( $item_data['ID'] ?? 0 ),
			'title'    => $this->text( is_string( $item_data['title'] ?? null ) ? $item_data['title'] : '' ),
			'parentId' => absint( $item_data['menu_item_parent'] ?? 0 ),
			'order'    => absint( $item_data['menu_order'] ?? 0 ),
			'url'      => is_string( $item_data['url'] ?? null ) ? $item_data['url'] : '',
			'type'     => is_string( $item_data['type'] ?? null ) ? $item_data['type'] : '',
			'object'   => is_string( $item_data['object'] ?? null ) ? $item_data['object'] : '',
			'objectId' => absint( $item_data['object_id'] ?? 0 ),
		);
	}
}
