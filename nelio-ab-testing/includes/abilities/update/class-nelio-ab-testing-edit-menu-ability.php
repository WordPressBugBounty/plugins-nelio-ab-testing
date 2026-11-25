<?php
/**
 * Ability for editing a generated menu-test variant.
 *
 * @package Nelio_AB_Testing
 */

defined( 'ABSPATH' ) || exit;

/** Edits the items of a generated menu-test variant. */
class Nelio_AB_Testing_Edit_Menu_Ability extends Nelio_AB_Testing_Ability {

	/**
	 * Registers the ability.
	 *
	 * @return void
	 */
	public function register() {
		$item_schema = $this->object_schema(
			array(
				'id'          => array(
					'oneOf'       => array(
						array( 'type' => 'integer' ),
						array( 'type' => 'string' ),
					),
					'description' => 'Existing menu-item ID, or a unique client-side identifier for a new item.',
				),
				'parentId'    => array(
					'oneOf'       => array(
						array( 'type' => 'integer' ),
						array( 'type' => 'string' ),
					),
					'description' => 'Identifier of another item in this request to use as the parent. Omit it or use an empty string for a top-level item.',
				),
				'title'       => array(
					'type'        => 'string',
					'description' => 'Visible label of the menu item.',
				),
				'url'         => array(
					'type'        => 'string',
					'description' => 'Destination URL. Use an empty value when the item is represented by a WordPress object.',
				),
				'type'        => array(
					'type'        => 'string',
					'description' => 'WordPress menu-item type, such as `custom`, `post_type`, or `taxonomy`.',
				),
				'object'      => array(
					'type'        => 'string',
					'description' => 'WordPress object type associated with the item, when applicable.',
				),
				'objectId'    => array(
					'type'        => 'integer',
					'description' => 'Identifier of the WordPress object associated with the item, or zero when there is none.',
				),
				'order'       => array(
					'type'        => 'integer',
					'description' => 'Position of the item among the menu items.',
				),
				'description' => array(
					'type'        => 'string',
					'description' => 'Optional description of the menu item.',
				),
				'attrTitle'   => array(
					'type'        => 'string',
					'description' => 'Optional HTML title attribute for the menu item.',
				),
				'target'      => array(
					'type'        => 'string',
					'description' => 'Optional link target for the menu item.',
				),
				'classes'     => array(
					'type'        => 'array',
					'description' => 'Optional CSS classes assigned to the menu item.',
					'items'       => array(
						'type'        => 'string',
						'description' => 'CSS class assigned to the menu item.',
					),
				),
				'xfn'         => array(
					'type'        => 'string',
					'description' => 'Optional XFN relationship value for the menu item.',
				),
				'status'      => array(
					'type'        => 'string',
					'description' => 'Optional WordPress status of the menu item.',
				),
			),
			array( 'id', 'title' )
		);

		wp_register_ability(
			'nelio-ab-testing/edit-menu',
			array(
				'label'               => _x( 'Edit a test menu', 'text (ability name)', 'nelio-ab-testing' ),
				'description'         => 'Replaces the items of a menu created as a non-control variant of a Nelio menu test. It cannot edit control menus, existing menus selected as variants, or unrelated navigation menus.',
				'category'            => 'nelio-ab-testing',
				'input_schema'        => $this->object_schema(
					array(
						'menuId' => array(
							'type'        => 'integer',
							'minimum'     => 1,
							'description' => 'Identifier of the generated menu belonging to a non-control variant of a menu test.',
						),
						'items'  => array(
							'type'        => 'array',
							'description' => 'Complete desired list of menu items. Items omitted from this list are removed.',
							'items'       => $item_schema,
						),
					),
					array( 'menuId', 'items' )
				),
				'output_schema'       => $this->object_schema(
					array(
						'menuId' => array(
							'type'        => 'integer',
							'description' => 'Identifier of the updated menu.',
						),
						'items'  => array(
							'type'        => 'array',
							'description' => 'Final menu items after the update.',
							'items'       => $item_schema,
						),
					),
					array( 'menuId', 'items' )
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
						'idempotent'  => false,
					),
				),
			)
		);
	}

	/**
	 * Checks whether the current user may edit the menu.
	 *
	 * @param array<array-key,mixed> $input Input containing the menu identifier.
	 *
	 * @return true|WP_Error True when the menu may be edited; otherwise, an error.
	 */
	public function permissions( $input ) {
		if ( ! current_theme_supports( 'menus' ) ) {
			return new WP_Error( 'menus-not-supported', 'The active theme does not support navigation menus.', array( 'status' => 400 ) );
		}

		if ( ! current_user_can( 'edit_nab_experiments' ) ) {
			return new WP_Error( 'missing-capability', 'You cannot edit navigation menus for Nelio tests.', array( 'status' => 403 ) );
		}

		$menu_id = absint( $input['menuId'] ?? 0 );
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

		$experiment_id = absint( get_term_meta( $menu_id, '_nab_experiment', true ) );
		if ( ! $experiment_id ) {
			return new WP_Error( 'invalid-menu', 'The selected menu was not created as a variant of a Nelio menu test.' );
		}

		$experiment = nab_get_experiment( $experiment_id );
		if ( is_wp_error( $experiment ) ) {
			return $experiment;
		}

		$can_be_edited = $experiment->can_be_edited();
		if ( is_wp_error( $can_be_edited ) ) {
			return $can_be_edited;
		}

		if ( 'nab/menu' !== $experiment->get_type() ) {
			return new WP_Error( 'invalid-menu', 'The selected menu does not belong to a menu test.' );
		}

		if ( ! nelioab()->does_test_type_support_ai( $experiment->get_type() ) ) {
			return new WP_Error( 'unsupported-test-type', 'This test type is registered but cannot be configured through AI abilities.' );
		}

		foreach ( $experiment->get_alternatives() as $index => $alternative ) {
			if ( 0 === $index || absint( $alternative['attributes']['menuId'] ?? 0 ) !== $menu_id ) {
				continue;
			}
			if ( ! empty( $alternative['attributes']['isExistingMenu'] ) ) {
				return new WP_Error( 'invalid-menu', 'The selected menu is an existing menu used as a variant and cannot be edited through this ability.' );
			}
			return true;
		}

		return new WP_Error( 'invalid-menu', 'The selected menu is not a generated variant of the test.' );
	}

	/**
	 * Replaces the complete item list of a generated menu-test variant.
	 *
	 * Existing numeric menu-item IDs are preserved when possible. New items may
	 * use any unique string identifier, which can also be referenced by parentId.
	 *
	 * @param array<array-key,mixed> $input Input containing the menu identifier and complete item list.
	 *
	 * @return array<string,mixed>|WP_Error Updated menu data or an error.
	 */
	public function execute( $input ) {
		$allowed = $this->permissions( $input );
		if ( is_wp_error( $allowed ) ) {
			return $allowed;
		}

		$menu_id = absint( $input['menuId'] ?? 0 );
		$items   = isset( $input['items'] ) && is_array( $input['items'] ) ? $input['items'] : array();
		/** @var array<string,array{input:array<array-key,mixed>,parentId:string}> $records */
		$records = array();
		foreach ( $items as $item ) {
			if (
				! is_array( $item ) ||
				! array_key_exists( 'id', $item ) ||
				( ! is_string( $item['id'] ) && ! is_int( $item['id'] ) ) ||
				'' === (string) $item['id'] ||
				array_key_exists( (string) $item['id'], $records )
			) {
				return new WP_Error( 'invalid-menu-items', 'Every menu item must have a unique identifier.' );
			}
			$record_id             = (string) $item['id'];
			$parent_id             = $item['parentId'] ?? '';
			$records[ $record_id ] = array(
				'input'    => $item,
				'parentId' => is_string( $parent_id ) || is_int( $parent_id ) ? (string) $parent_id : '',
			);
		}

		foreach ( $records as $record_id => $record ) {
			$parent_id = $record['parentId'];
			$seen      = array( $record_id => true );
			while ( '' !== $parent_id ) {
				if ( ! array_key_exists( $parent_id, $records ) || isset( $seen[ $parent_id ] ) ) {
					return new WP_Error( 'invalid-menu-items', 'Every menu item parent must reference another item without creating a hierarchy loop.' );
				}
				$seen[ $parent_id ] = true;
				$parent_id          = $records[ $parent_id ]['parentId'];
			}
		}

		/** @var list<WP_Post>|false $current_items */
		$current_items = wp_get_nav_menu_items( $menu_id );
		if ( false === $current_items ) {
			return new WP_Error( 'invalid-menu', 'The selected menu items could not be read.' );
		}

		/** @var array<string,WP_Post> $current_by_id */
		$current_by_id = array();
		foreach ( $current_items as $current_item ) {
			$current_by_id[ (string) $current_item->ID ] = $current_item;
		}

		/** @var array<string,int> $actual_ids */
		$actual_ids = array();
		foreach ( $records as $record_id => $record ) {
			$item       = $record['input'];
			$existing   = $current_by_id[ $record_id ] ?? null;
			$item_id    = null !== $existing ? absint( $existing->ID ) : 0;
			$saved_item = $this->save_item( $menu_id, $item_id, $item, 0 );
			if ( is_wp_error( $saved_item ) ) {
				return $saved_item;
			}
			$actual_ids[ $record_id ] = $saved_item;
		}

		foreach ( $records as $record_id => $record ) {
			$parent_id = $record['parentId'];
			$parent    = '' === $parent_id ? 0 : $actual_ids[ $parent_id ];
			$result    = $this->save_item( $menu_id, $actual_ids[ $record_id ], $record['input'], $parent );
			if ( is_wp_error( $result ) ) {
				return $result;
			}
		}

		$requested_existing_ids = array_intersect( array_keys( $current_by_id ), array_keys( $actual_ids ) );
		foreach ( array_diff( array_keys( $current_by_id ), $requested_existing_ids ) as $removed_id ) {
			wp_delete_post( absint( $removed_id ), true );
		}

		$menu_ability = new Nelio_AB_Testing_Get_Menu_Ability();
		$menu         = $menu_ability->execute( array( 'id' => $menu_id ) );
		if ( is_wp_error( $menu ) ) {
			return $menu;
		}

		return array(
			'menuId' => $menu_id,
			'items'  => $menu['items'],
		);
	}

	/**
	 * Saves one menu item.
	 *
	 * @param int                    $menu_id Menu identifier.
	 * @param int                    $item_id Menu-item identifier, or zero for a new item.
	 * @param array<array-key,mixed> $item Item data.
	 * @param int                    $parent_id Actual parent item identifier.
	 *
	 * @return int|WP_Error Saved item identifier or an error.
	 */
	private function save_item( $menu_id, $item_id, $item, $parent_id ) {
		$classes = isset( $item['classes'] ) && is_array( $item['classes'] ) ? array_filter( $item['classes'], 'is_string' ) : array();
		$args    = array(
			'menu-item-title'       => is_string( $item['title'] ?? null ) ? $item['title'] : '',
			'menu-item-url'         => is_string( $item['url'] ?? null ) ? $item['url'] : '',
			'menu-item-type'        => is_string( $item['type'] ?? null ) ? $item['type'] : 'custom',
			'menu-item-object'      => is_string( $item['object'] ?? null ) ? $item['object'] : '',
			'menu-item-object-id'   => absint( $item['objectId'] ?? 0 ),
			'menu-item-parent-id'   => $parent_id,
			'menu-item-position'    => absint( $item['order'] ?? 0 ),
			'menu-item-description' => is_string( $item['description'] ?? null ) ? $item['description'] : '',
			'menu-item-attr-title'  => is_string( $item['attrTitle'] ?? null ) ? $item['attrTitle'] : '',
			'menu-item-target'      => is_string( $item['target'] ?? null ) ? $item['target'] : '',
			'menu-item-classes'     => implode( ' ', $classes ),
			'menu-item-xfn'         => is_string( $item['xfn'] ?? null ) ? $item['xfn'] : '',
			'menu-item-status'      => is_string( $item['status'] ?? null ) ? $item['status'] : 'publish',
		);
		return wp_update_nav_menu_item( $menu_id, $item_id, $args );
	}
}
