<?php
/**
 * Optional Abilities API and MCP integration.
 *
 * @package Nelio_AB_Testing
 */

defined( 'ABSPATH' ) || exit;

/** Coordinates the category, abilities and MCP server registration. */
class Nelio_AB_Testing_Abilities {

	/**
	 * Initialize hooks.
	 *
	 * @return void
	 */
	public function init() {
		add_action( 'wp_abilities_api_categories_init', array( $this, 'register_category' ) );
		add_action( 'wp_abilities_api_init', array( $this, 'register_abilities' ) );
		add_action( 'mcp_adapter_init', array( $this, 'register_server' ) );
	}

	/**
	 * Register the ability category.
	 *
	 * @return void
	 */
	public function register_category() {
		if ( ! function_exists( 'wp_register_ability_category' ) ) {
			return; // @codeCoverageIgnore
		}
		wp_register_ability_category(
			'nelio-ab-testing',
			array(
				'label'       => 'Nelio A/B Testing',
				'description' => 'Reads, creates and configures tests on the connected WordPress site.',
			)
		);
	}

	/**
	 * Register each concrete ability.
	 *
	 * @return void
	 */
	public function register_abilities() {
		if ( ! function_exists( 'wp_register_ability' ) ) {
			return; // @codeCoverageIgnore
		}

		// Discover abilities.
		( new Nelio_AB_Testing_Get_Test_Capabilities_Ability() )->register();
		( new Nelio_AB_Testing_Get_Content_Types_Ability() )->register();
		( new Nelio_AB_Testing_Find_Content_Ability() )->register();
		( new Nelio_AB_Testing_Find_Product_Terms_Ability() )->register();
		( new Nelio_AB_Testing_Find_Menus_Ability() )->register();
		( new Nelio_AB_Testing_Inspect_Content_Ability() )->register();
		( new Nelio_AB_Testing_Get_Testing_Opportunities_Ability() )->register();
		( new Nelio_AB_Testing_Find_Templates_Ability() )->register();
		( new Nelio_AB_Testing_Get_Css_Selectors_Ability() )->register();
		( new Nelio_AB_Testing_Get_Ecommerce_Order_Statuses_Ability() )->register();
		( new Nelio_AB_Testing_Get_Plugin_Status_Ability() )->register();
		( new Nelio_AB_Testing_Get_Plugin_Settings_Ability() )->register();

		// Create abilities.
		( new Nelio_AB_Testing_Create_Draft_Test_Ability() )->register();
		( new Nelio_AB_Testing_Add_Test_Variant_Ability() )->register();
		( new Nelio_AB_Testing_Duplicate_Test_Ability() )->register();

		// Update abilities.
		( new Nelio_AB_Testing_Set_Test_Settings_Ability() )->register();
		( new Nelio_AB_Testing_Set_Heatmap_Element_Ability() )->register();
		( new Nelio_AB_Testing_Set_Heatmap_Participation_Ability() )->register();
		( new Nelio_AB_Testing_Set_Test_Scope_Ability() )->register();
		( new Nelio_AB_Testing_Set_Test_Control_Ability() )->register();
		( new Nelio_AB_Testing_Update_Test_Variant_Ability() )->register();
		( new Nelio_AB_Testing_Set_Test_Variant_Distribution_Ability() )->register();
		( new Nelio_AB_Testing_Edit_Menu_Ability() )->register();
		( new Nelio_AB_Testing_Set_Test_Goals_Ability() )->register();
		( new Nelio_AB_Testing_Set_Test_Goal_Attributes_Ability() )->register();
		( new Nelio_AB_Testing_Set_Test_Goal_Actions_Ability() )->register();
		( new Nelio_AB_Testing_Set_Test_Segments_Ability() )->register();
		( new Nelio_AB_Testing_Set_Test_Segment_Attributes_Ability() )->register();
		( new Nelio_AB_Testing_Set_Test_Segment_Rules_Ability() )->register();
		( new Nelio_AB_Testing_Set_Test_Page_View_Trigger_Ability() )->register();
		( new Nelio_AB_Testing_Edit_Content_Ability() )->register();
		( new Nelio_AB_Testing_Set_Reusable_Goals_Ability() )->register();
		( new Nelio_AB_Testing_Set_Reusable_Segments_Ability() )->register();
		( new Nelio_AB_Testing_Set_Plugin_Settings_Ability() )->register();

		// Delete abilities.
		( new Nelio_AB_Testing_Remove_Test_Variant_Ability() )->register();
		( new Nelio_AB_Testing_Trash_Test_Ability() )->register();
		( new Nelio_AB_Testing_Delete_Test_Ability() )->register();

		// Manage abilities.
		( new Nelio_AB_Testing_Manage_Test_Ability() )->register();
		( new Nelio_AB_Testing_Apply_Test_Variant_Ability() )->register();
		( new Nelio_AB_Testing_Restore_Test_Ability() )->register();

		// Read abilities.
		( new Nelio_AB_Testing_List_Tests_Ability() )->register();
		( new Nelio_AB_Testing_Get_Test_Ability() )->register();
		( new Nelio_AB_Testing_Get_Test_Goals_Ability() )->register();
		( new Nelio_AB_Testing_Get_Test_Segments_Ability() )->register();
		( new Nelio_AB_Testing_Get_Menu_Ability() )->register();
		( new Nelio_AB_Testing_Get_Test_Variant_Ability() )->register();
		( new Nelio_AB_Testing_Get_Test_Goal_Ability() )->register();
		( new Nelio_AB_Testing_Get_Test_Segment_Ability() )->register();
		( new Nelio_AB_Testing_Get_Test_Scope_Ability() )->register();
		( new Nelio_AB_Testing_Get_Experiment_Results_Ability() )->register();
		( new Nelio_AB_Testing_Get_Experiment_Timeline_Ability() )->register();
		( new Nelio_AB_Testing_Get_Heatmap_Results_Ability() )->register();
		( new Nelio_AB_Testing_Get_Reusable_Goals_Ability() )->register();
		( new Nelio_AB_Testing_Get_Reusable_Segments_Ability() )->register();
	}

	/**
	 * Register a dedicated server for Nelio abilities.
	 *
	 * @param \WP\MCP\Core\McpAdapter $adapter Adapter.
	 *
	 * @return void
	 */
	public function register_server( $adapter ) {
		if ( ! function_exists( 'wp_get_ability' ) || ! function_exists( 'wp_get_abilities' ) || ! defined( 'WP_MCP_VERSION' ) || ! is_string( WP_MCP_VERSION ) || version_compare( WP_MCP_VERSION, '0.7.1', '<' ) ) {
			return; // @codeCoverageIgnore
		}
		$tools = array_keys(
			wp_get_abilities(
				array(
					'namespace' => 'nelio-ab-testing',
					'meta'      => array(
						'mcp' => array(
							'server' => 'nelio-ab-testing',
						),
					),
				)
			)
		);
		$adapter->create_server(
			'nelio-ab-testing',
			nelioab()->rest_namespace,
			'mcp',
			'Nelio A/B Testing',
			'Access to read, create and configure Nelio tests on this site.',
			nelioab()->plugin_version,
			array( \WP\MCP\Transport\HttpTransport::class ),
			null,
			null,
			$tools,
			array(),
			array(),
			fn() => is_user_logged_in() && nelioab()->is_ready() && ( current_user_can( 'read_nab_results' ) || current_user_can( 'edit_nab_experiments' ) )
		);
	}
}
