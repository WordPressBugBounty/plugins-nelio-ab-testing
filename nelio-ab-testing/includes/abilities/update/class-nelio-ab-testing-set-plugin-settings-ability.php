<?php
/**
 * Ability for updating AI-compatible Nelio A/B Testing settings.
 *
 * @package Nelio_AB_Testing
 */

defined( 'ABSPATH' ) || exit;

/** Updates the site's AI-compatible Nelio A/B Testing settings. */
class Nelio_AB_Testing_Set_Plugin_Settings_Ability extends Nelio_AB_Testing_Ability {

	/**
	 * Registers the ability with WordPress.
	 *
	 * @return void
	 */
	public function register() {
		wp_register_ability(
			'nelio-ab-testing/set-plugin-settings',
			array(
				'label'               => _x( 'Set plugin settings', 'text (ability name)', 'nelio-ab-testing' ),
				'description'         => 'Updates a partial set of AI-compatible Nelio A/B Testing settings. Only the supplied settings are changed. Protected and readonly settings cannot be written by the agent, while disabled settings are currently unavailable to both the agent and the site user.',
				'category'            => 'nelio-ab-testing',
				'input_schema'        => $this->object_schema(
					array(
						'settings' => array(
							'type'                 => 'object',
							'description'          => 'Setting names and their new values. Provide at least one setting and omit settings that should remain unchanged. Use the schemas returned by the plugin-settings read ability to construct the values. A value is accepted only when the setting sanitizer leaves it unchanged; otherwise the update is rejected.',
							'additionalProperties' => true,
						),
					)
				),
				'output_schema'       => $this->object_schema(
					array(
						'settings' => array(
							'type'                 => 'object',
							'description'          => 'Complete set of currently readable plugin settings after the update. Protected and disabled settings are omitted.',
							'additionalProperties' => true,
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
						'readonly'    => false,
						'destructive' => false,
						'idempotent'  => true,
					),
				),
			)
		);
	}

	/**
	 * Checks whether the current user may update plugin settings.
	 *
	 * @param array<array-key,mixed> $input Input values.
	 *
	 * @return true|WP_Error True when the user may update plugin settings; otherwise, an error.
	 */
	public function permissions( $input ) {
		unset( $input );
		return current_user_can( 'manage_nab_options' ) ? true : new WP_Error( 'missing-capability', 'You cannot update Nelio A/B Testing settings.' );
	}

	/**
	 * Updates a partial set of AI-compatible plugin settings.
	 *
	 * @param array<array-key,mixed> $input Input containing setting values.
	 *
	 * @return array<string,mixed>|WP_Error Updated settings or an error.
	 */
	public function execute( $input ) {
		$allowed = $this->permissions( $input );
		if ( is_wp_error( $allowed ) ) {
			return $allowed;
		}

		$settings       = array();
		$input_settings = $input['settings'] ?? array();
		$input_settings = is_array( $input_settings ) ? $input_settings : array();
		if ( empty( $input_settings ) ) {
			return new WP_Error( 'invalid-settings', 'At least one setting must be provided.' );
		}

		foreach ( $input_settings as $name => $value ) {
			if ( ! is_string( $name ) ) {
				return new WP_Error( 'invalid-settings', 'Setting names must be strings.' );
			}
			$settings[ $name ] = $value;
		}

		$result = Nelio_AB_Testing_Settings::instance()->update_ai_settings( $settings );
		return is_wp_error( $result ) ? $result : array( 'settings' => $result );
	}
}
