<?php
/**
 * Ability for retrieving Nelio A/B Testing installation and account status.
 *
 * @package Nelio_AB_Testing
 */

defined( 'ABSPATH' ) || exit;

/** Returns the current Nelio A/B Testing installation and account status. */
class Nelio_AB_Testing_Get_Plugin_Status_Ability extends Nelio_AB_Testing_Ability {

	/**
	 * Registers the ability.
	 *
	 * @return void
	 */
	public function register() {
		wp_register_ability(
			'nelio-ab-testing/get-plugin-status',
			array(
				'label'               => _x( 'Get plugin status', 'text (ability name)', 'nelio-ab-testing' ),
				'description'         => 'Returns the installed Nelio A/B Testing version, public support key, subscription information, and available quota.',
				'category'            => 'nelio-ab-testing',
				'input_schema'        => $this->object_schema( array(), array() ),
				'output_schema'       => $this->object_schema(
					array(
						'pluginVersion'      => array(
							'type'        => 'string',
							'description' => 'Version of Nelio A/B Testing installed on the site.',
						),
						'wordpressVersion'   => array(
							'type'        => 'string',
							'description' => 'Version of WordPress running on the site.',
						),
						'supportKey'         => array(
							'type'        => 'string',
							'description' => 'Public site identifier used by Nelio support.',
						),
						'subscriptionPlan'   => array(
							'type'        => array( 'string', 'null' ),
							'description' => 'Current subscription plan, or null when the site has no subscription.',
						),
						'subscriptionAddons' => array(
							'type'        => 'array',
							'description' => 'Subscription add-ons currently enabled for the site.',
							'items'       => array( 'type' => 'string' ),
						),
						'quota'              => array(
							'type'                 => 'object',
							'description'          => 'Current quota information, when it can be retrieved from the Nelio service.',
							'properties'           => array(
								'available'       => array(
									'type'        => 'integer',
									'description' => 'Number of page views available for tracking.',
								),
								'percentage'      => array(
									'type'        => 'integer',
									'description' => 'Percentage of the current quota that remains available.',
								),
								'boostExpiration' => array(
									'type'        => 'string',
									'description' => 'Expiration date of a temporary quota boost, when one exists.',
								),
							),
							'required'             => array( 'available', 'percentage' ),
							'additionalProperties' => false,
						),
					),
					array( 'pluginVersion', 'wordpressVersion', 'supportKey', 'subscriptionPlan', 'subscriptionAddons' )
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
						'idempotent'  => false,
					),
				),
			)
		);
	}

	/**
	 * Checks whether the current user may inspect plugin status.
	 *
	 * @param array<array-key,mixed> $input Input.
	 *
	 * @return true|WP_Error True when the user may inspect plugin status; otherwise, an error.
	 */
	public function permissions( $input ) {
		if ( ! current_user_can( 'edit_nab_experiments' ) && ! current_user_can( 'read_nab_results' ) ) {
			return new WP_Error( 'missing-capability', 'You cannot inspect Nelio A/B Testing plugin status.', array( 'status' => 403 ) );
		}

			return true;
	}

	/**
	 * Returns plugin, subscription, and quota status.
	 *
	 * @param array<array-key,mixed> $input Input.
	 *
	 * @return array<string,mixed>|WP_Error Plugin status, or an error.
	 */
	public function execute( $input ) {
		$allowed = $this->permissions( $input );
		if ( is_wp_error( $allowed ) ) {
			return $allowed;
		}

		$addons = nab_get_subscription_addons();
		$plan   = nab_get_subscription();
		$plan   = is_string( $plan ) && '' !== $plan ? $plan : null;
		$result = array(
			'pluginVersion'      => nelioab()->plugin_version,
			'wordpressVersion'   => get_bloginfo( 'version' ),
			'supportKey'         => (string) nab_get_site_id(),
			'subscriptionPlan'   => $plan,
			'subscriptionAddons' => $addons,
		);

		$quota = ( new Nelio_AB_Testing_Account_REST_Controller() )->get_site_quota();
		if ( ! is_wp_error( $quota ) ) {
			$result['quota'] = array(
				'available'  => $quota['availableQuota'],
				'percentage' => $quota['percentage'],
			);
			if ( isset( $quota['boostExpiration'] ) ) {
				$result['quota']['boostExpiration'] = $quota['boostExpiration'];
			}
		}

		return $this->bounded_response( $result );
	}
}
