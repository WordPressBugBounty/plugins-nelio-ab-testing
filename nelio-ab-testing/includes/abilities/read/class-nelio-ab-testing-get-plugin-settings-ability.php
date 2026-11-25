<?php
/**
 * Ability for reading AI-compatible Nelio A/B Testing settings.
 *
 * @package Nelio_AB_Testing
 */

defined( 'ABSPATH' ) || exit;

/** Reads the site's AI-compatible Nelio A/B Testing settings. */
class Nelio_AB_Testing_Get_Plugin_Settings_Ability extends Nelio_AB_Testing_Ability {

	/**
	 * Registers the ability with WordPress.
	 *
	 * @return void
	 */
	public function register() {
		$setting = $this->object_schema(
			array(
				'name'        => array(
					'type'        => 'string',
					'description' => 'Internal name of the setting.',
				),
				'label'       => array(
					'type'        => 'string',
					'description' => 'Human-readable label of the setting.',
				),
				'description' => array(
					'type'        => 'string',
					'description' => 'Description of what the setting controls and when it is useful.',
				),
				'access'      => array(
					'type'        => 'string',
					'enum'        => array( 'full', 'readonly', 'protected', 'disabled' ),
					'description' => 'Current access available to the agent: full means readable and writable, readonly means readable but not writable, protected means the setting is known but its value is not exposed, and disabled means the setting is currently unavailable to both the agent and the site user.',
				),
				'schema'      => array(
					'type'                 => 'object',
					'description'          => 'Schema describing the setting value, when the setting exposes one.',
					'additionalProperties' => true,
				),
				'value'       => array(
					'type'        => array( 'string', 'number', 'integer', 'boolean', 'array', 'object', 'null' ),
					'description' => 'Current setting value. This property is omitted when the setting is protected or disabled.',
				),
			),
			array( 'name', 'label', 'description', 'access' )
		);

		wp_register_ability(
			'nelio-ab-testing/get-plugin-settings',
			array(
				'label'               => _x( 'Get plugin settings', 'text (ability name)', 'nelio-ab-testing' ),
				'description'         => 'Reads the settings that are exposed to AI agents, including their descriptions, schemas, current access state, and values when the agent is allowed to read them.',
				'category'            => 'nelio-ab-testing',
				'input_schema'        => $this->object_schema( array() ),
				'output_schema'       => $this->object_schema(
					array(
						'settings' => array(
							'type'        => 'array',
							'description' => 'AI-compatible plugin settings and their current values when readable.',
							'items'       => $setting,
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
	 * Checks whether the current user may read plugin settings.
	 *
	 * @param array<array-key,mixed> $input Input values.
	 *
	 * @return true|WP_Error True when the user may read plugin settings; otherwise, an error.
	 */
	public function permissions( $input ) {
		unset( $input );
		return current_user_can( 'manage_nab_options' ) ? true : new WP_Error( 'missing-capability', 'You cannot read Nelio A/B Testing settings.' );
	}

	/**
	 * Reads AI-compatible plugin settings.
	 *
	 * @param array<array-key,mixed> $input Input values.
	 *
	 * @return array<string,mixed>|WP_Error Settings or an error.
	 */
	public function execute( $input ) {
		$allowed = $this->permissions( $input );
		if ( is_wp_error( $allowed ) ) {
			return $allowed;
		}

		return array( 'settings' => Nelio_AB_Testing_Settings::instance()->get_ai_settings() );
	}
}
