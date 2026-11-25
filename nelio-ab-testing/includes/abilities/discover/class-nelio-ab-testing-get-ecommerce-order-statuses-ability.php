<?php
/**
 * Ability for discovering e-commerce order statuses.
 *
 * @package Nelio_AB_Testing
 */

defined( 'ABSPATH' ) || exit;

/** Returns the order statuses exposed by a supported e-commerce integration. */
class Nelio_AB_Testing_Get_Ecommerce_Order_Statuses_Ability extends Nelio_AB_Testing_Ability {

	/**
	 * Registers the ability.
	 *
	 * @return void
	 */
	public function register() {
		wp_register_ability(
			'nelio-ab-testing/get-ecommerce-order-statuses',
			array(
				'label'               => _x( 'Get e-commerce order statuses', 'text (ability name)', 'nelio-ab-testing' ),
				'description'         => 'Returns the current order statuses exposed by the available e-commerce integrations. Omit `plugin` to inspect every available integration, or provide the integration associated with the purchase conversion action. Use the returned status `value` when configuring a goal.',
				'category'            => 'nelio-ab-testing',
				'input_schema'        => $this->object_schema(
					array(
						'plugin' => array(
							'type'        => 'string',
							'enum'        => array( 'woocommerce', 'edd' ),
							'description' => 'Optional e-commerce integration whose order statuses should be returned. Omit it to return statuses from every available integration.',
						),
					),
					array()
				),
				'output_schema'       => $this->object_schema(
					array(
						'statuses' => array(
							'type'        => 'array',
							'description' => 'Order statuses currently available in the selected integration or integrations.',
							'items'       => array(
								'type'                 => 'object',
								'properties'           => array(
									'plugin' => array(
										'type'        => 'string',
										'enum'        => array( 'woocommerce', 'edd' ),
										'description' => 'E-commerce integration that provides this status.',
									),
									'value'  => array(
										'type'        => 'string',
										'description' => 'Machine-readable status value to use as orderStatusForConversion.',
									),
									'label'  => array(
										'type'        => 'string',
										'description' => 'Human-readable status label.',
									),
								),
								'required'             => array( 'plugin', 'value', 'label' ),
								'additionalProperties' => false,
							),
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
	 * Checks whether the current user may inspect order statuses.
	 *
	 * @param array<array-key,mixed> $input Input containing the e-commerce integration.
	 *
	 * @return true|WP_Error True when the user may inspect statuses; otherwise, an error.
	 */
	public function permissions( $input ) {
		if ( ! current_user_can( 'edit_nab_experiments' ) ) {
			return new WP_Error( 'missing-capability', 'You cannot inspect e-commerce order statuses for Nelio tests.', array( 'status' => 403 ) );
		}

		$plugin = is_string( $input['plugin'] ?? null ) ? $input['plugin'] : null;
		if ( null !== $plugin && ! in_array( $plugin, array( 'woocommerce', 'edd' ), true ) ) {
			return new WP_Error( 'invalid-ecommerce-plugin', 'The requested e-commerce integration is not supported.', array( 'status' => 400 ) );
		}

		$available_plugins = $this->get_available_plugins();
		if ( null === $plugin && empty( $available_plugins ) ) {
			return new WP_Error( 'unavailable-ecommerce-plugin', 'No supported e-commerce integration is available on this site.', array( 'status' => 400 ) );
		}

		if ( 'woocommerce' === $plugin && ! function_exists( 'wc_get_order_statuses' ) ) {
			return new WP_Error( 'unavailable-ecommerce-plugin', 'WooCommerce is not available on this site.', array( 'status' => 400 ) );
		}

		if ( 'edd' === $plugin && ! function_exists( 'edd_get_payment_statuses' ) ) {
			return new WP_Error( 'unavailable-ecommerce-plugin', 'Easy Digital Downloads is not available on this site.', array( 'status' => 400 ) );
		}

		return true;
	}

	/**
	 * Returns order statuses from the selected e-commerce integration.
	 *
	 * @param array<array-key,mixed> $input Input containing the e-commerce integration.
	 *
	 * @return array<string,mixed>|WP_Error Order statuses or an error.
	 */
	public function execute( $input ) {
		$allowed = $this->permissions( $input );
		if ( is_wp_error( $allowed ) ) {
			return $allowed;
		}

		$plugin            = is_string( $input['plugin'] ?? null ) ? $input['plugin'] : null;
		$available_plugins = null === $plugin ? $this->get_available_plugins() : array( $plugin );
		$statuses          = array();
		foreach ( $available_plugins as $available_plugin ) {
			foreach ( $this->get_statuses( $available_plugin ) as $value => $label ) {
				$statuses[] = array(
					'plugin' => $available_plugin,
					'value'  => (string) $value,
					'label'  => $this->text( $label ),
				);
			}
		}

		return $this->bounded_response(
			array(
				'statuses' => $statuses,
			)
		);
	}

	/**
	 * Returns the supported integrations available on this site.
	 *
	 * @return list<string> Available integration names.
	 */
	private function get_available_plugins() {
		$plugins = array();
		if ( function_exists( 'wc_get_order_statuses' ) ) {
			$plugins[] = 'woocommerce';
		}
		if ( function_exists( 'edd_get_payment_statuses' ) ) {
			$plugins[] = 'edd';
		}
		return $plugins;
	}

	/**
	 * Returns statuses for an integration.
	 *
	 * @param string $plugin Integration name.
	 *
	 * @return array<array-key,mixed> Integration statuses.
	 */
	private function get_statuses( $plugin ) {
		$statuses = 'woocommerce' === $plugin ? wc_get_order_statuses() : edd_get_payment_statuses();
		return $statuses;
	}
}
