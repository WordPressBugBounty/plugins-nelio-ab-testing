<?php
/**
 * List of settings.
 *
 * @package    Nelio A/B Testing
 * @subpackage Nelio A/B Testing/includes/data
 * @since      5.0.0
 */

defined( 'ABSPATH' ) || exit;

// Fix GDPR cookie setting.
add_filter(
	'option_nelio-ab-testing_settings',
	function ( array $settings ) {
		// @codeCoverageIgnoreStart
		$old_value = $settings['gdpr_cookie_name'] ?? '';
		$new_value = $settings['gdpr_cookie_setting'] ?? '';
		$new_value = is_array( $new_value ) ? $new_value : array(
			'name'  => '',
			'value' => '',
		);

		$new_value['name'] = empty( $new_value['name'] ) ? $old_value : $new_value['name'];

		unset( $settings['gdpr_cookie_name'] );
		$settings['gdpr_cookie_setting'] = $new_value;

		return $settings;
		// @codeCoverageIgnoreEnd
	}
);

return array(

	array(
		'type' => 'section',
		'icon' => 'dashicons-admin-settings',
		'name' => 'tracking-settings',
		'ui'   => fn() => array(
			'label' => _x( 'Tracking Settings', 'text', 'nelio-ab-testing' ),
		),
	),

	array(
		'type'    => 'range',
		'name'    => 'percentage_of_tested_visitors',
		'default' => 100,
		'config'  => array(
			'required-plan' => 'basic',
		),
		'ai'      => array(
			'access'      => 'full',
			'description' => 'Defines the percentage of visitors who may participate in running tests.',
			'schema'      => array(
				'type'       => 'integer',
				'minimum'    => 5,
				'maximum'    => 100,
				'multipleOf' => 5,
			),
		),
		'ui'      => fn() => array(
			'label' => _x( 'Tested Visitors', 'text', 'nelio-ab-testing' ),
			'desc'  => _x( 'When a person accesses your website she may participate in your running tests. This setting defines how likely it is for a visitor to be part of your tests.', 'user', 'nelio-ab-testing' ),
			'args'  => array(
				/* translators: %s: Percentage of visitors. */
				'label' => sprintf( _x( '<strong>%s%%</strong> of the visitors that access your site will participate in the running tests.', 'text', 'nelio-ab-testing' ), '{value}' ),
				'min'   => 5,
				'max'   => 100,
				'step'  => 5,
			),
		),
	),

	array(
		'type'    => 'select',
		'name'    => 'goal_tracking',
		'default' => 'all-pages',
		'ai'      => array(
			'access'      => 'full',
			'description' => 'Defines where conversion actions are tracked by default. `all-pages` tracks conversion actions on every page, `test-scope` tracks them only within each test scope, and `custom` lets each action define its own scope.',
			'schema'      => array(
				'type' => 'string',
				'enum' => array( 'all-pages', 'test-scope', 'custom' ),
			),
		),
		'ui'      => fn() => array(
			'label'   => _x( 'Goal Tracking', 'text', 'nelio-ab-testing' ),
			'desc'    => esc_html_x( 'Defines the pages in which goals can be tracked:', 'text', 'nelio-ab-testing' ),
			'options' => array(
				array(
					'value' => 'all-pages',
					'label' => esc_html_x( 'All Pages', 'text', 'nelio-ab-testing' ),
					'desc'  => esc_html_x( 'Nelio A/B Testing’s will track conversion actions on all pages.', 'text', 'nelio-ab-testing' ),
				),
				array(
					'value' => 'test-scope',
					'label' => esc_html_x( 'Tested Pages', 'text', 'nelio-ab-testing' ),
					'desc'  => esc_html_x( 'Nelio A/B Testing’s will track conversion actions on tested pages only.', 'text', 'nelio-ab-testing' ),
				),
				array(
					'value' => 'custom',
					'label' => esc_html_x( 'Custom', 'text', 'nelio-ab-testing' ),
					'desc'  => esc_html_x( 'When defining a test, you’ll be able to define the pages in which each conversion action might occur.', 'user', 'nelio-ab-testing' ),
				),
			),
		),
	),

	array(
		'type'    => 'select',
		'name'    => 'segment_evaluation',
		'default' => 'tested-page',
		'ai'      => array(
			'access'      => 'full',
			'description' => 'Defines where segmentation rules are evaluated to determine visitor participation in segments. `site` evaluates segments when visitors land on the site, `tested-page` evaluates them when visitors land on a page affected by the test, and `custom` lets each test define its evaluation strategy.',
			'schema'      => array(
				'type' => 'string',
				'enum' => array( 'site', 'tested-page', 'custom' ),
			),
		),
		'ui'      => fn() => array(
			'label'   => _x( 'Segmentation', 'text', 'nelio-ab-testing' ),
			'desc'    => esc_html_x( 'Customizes where segmentation rules are evaluated to determine if a visitor is part of a segment or not.', 'text', 'nelio-ab-testing' ),
			'options' => array(
				array(
					'value' => 'site',
					'label' => esc_html_x( 'Evaluate on Site Landing', 'command', 'nelio-ab-testing' ),
					'desc'  => esc_html_x( 'Segments of all active tests are evaluated when the visitor lands on the site.', 'text', 'nelio-ab-testing' ),
				),
				array(
					'value' => 'tested-page',
					'label' => esc_html_x( 'Evaluate on Tested Pages', 'command', 'nelio-ab-testing' ),
					'desc'  => esc_html_x( 'Segments of tests affecting a certain page are evaluated when the visitor lands on said page.', 'text', 'nelio-ab-testing' ),
				),
				array(
					'value' => 'custom',
					'label' => esc_html_x( 'Custom Evaluation', 'text', 'nelio-ab-testing' ),
					'desc'  => esc_html_x( 'Concrete segment evaluation strategies are defined on a per-test basis.', 'text', 'nelio-ab-testing' ),
				),
			),
		),
	),

	array(
		'type'    => 'checkbox',
		'name'    => 'match_all_segments',
		'default' => true,
		'ai'      => array(
			'access'      => 'full',
			'description' => 'Determines whether visitors must participate in every test affecting the current page.',
			'schema'      => array( 'type' => 'boolean' ),
		),
		'ui'      => fn() => array(
			'label' => '',
			'desc'  => _x( 'Require visitor’s participation in all tests affecting current page', 'command', 'nelio-ab-testing' ),
		),
	),

	array(
		'type'    => 'checkbox',
		'name'    => 'exclude_bots',
		'default' => true,
		'ai'      => array(
			'access'      => 'full',
			'description' => 'Determines whether known bots are excluded from participating in tests.',
			'schema'      => array( 'type' => 'boolean' ),
		),
		'ui'      => fn() => array(
			'label' => '',
			'desc'  => _x( 'Exclude bots from participating in split tests', 'command', 'nelio-ab-testing' ),
		),
	),

	array(
		'type'    => 'custom',
		'name'    => 'excluded_ips',
		'default' => array(
			'ips' => array(),
		),
		'ai'      => array(
			'access'         => 'full',
			'description'    => 'Defines IP addresses that should be excluded from participating in tests.',
			'is_write_valid' => function ( $provided, $sanitized ) {
				if ( ! is_array( $provided ) || ( ! is_string( $sanitized ) && ! is_null( $sanitized ) ) ) {
					return false;
				}

				if ( ! array_key_exists( 'ips', $provided ) || ! is_array( $provided['ips'] ) ) {
					return false;
				}

				$ips = $provided['ips'];
				foreach ( $ips as $ip ) {
					if ( ! is_string( $ip ) ) {
						return false;
					}
				}
				$expected = empty( $ips ) ? null : join( "\n", $ips );
				return $expected === $sanitized;
			},
			'schema'         => array(
				'type'                 => 'object',
				'properties'           => array(
					'ips' => array(
						'type'        => 'array',
						'description' => 'IPv4 or IPv6 addresses excluded from test participation. The plugin also accepts wildcard patterns for matching IP subnets.',
						'items'       => array( 'type' => 'string' ),
					),
				),
				'required'             => array( 'ips' ),
				'additionalProperties' => false,
			),
		),
		'ui'      => fn() => array(
			'desc'  => true,
			'label' => _x( 'Excluded IPs', 'text', 'nelio-ab-testing' ),
			'class' => Nelio_AB_Testing_Excluded_IPs_Setting::class,
		),
	),

	array(
		'type'    => 'custom',
		'name'    => 'gdpr_cookie_setting',
		'default' => array(
			'name'  => '',
			'value' => '',
		),
		'ai'      => array(
			'access'      => 'full',
			'description' => 'Defines the cookie used to comply with the site’s GDPR tracking requirements.',
			'schema'      => array(
				'type'                 => 'object',
				'properties'           => array(
					'name'  => array(
						'type'        => 'string',
						'description' => 'Name of the cookie used to store consent information.',
					),
					'value' => array(
						'type'        => 'string',
						'description' => 'Expected value of the consent cookie. Leave empty to require only that the cookie exists, regardless of its value.',
					),
				),
				'required'             => array( 'name', 'value' ),
				'additionalProperties' => false,
			),
		),
		'ui'      => fn() => array(
			'desc'  => true,
			'label' => _x( 'GDPR Cookie', 'text', 'nelio-ab-testing' ),
			'class' => Nelio_AB_Testing_GDPR_Cookie_Setting::class,
		),
	),

	array(
		'type'    => 'custom',
		'name'    => 'google_analytics_tracking',
		'config'  => array(
			'required-plan' => 'basic',
		),
		'default' => array(
			// @phpstan-ignore-next-line argument.type
			'enabled'       => ! empty( get_option( 'nelio-ab-testing_settings' )['integrate_ga4'] ),
			// @phpstan-ignore-next-line argument.type
			'measurementId' => get_option( 'nelio-ab-testing_settings' )['ga4_measurement_id'] ?? '',
			// @phpstan-ignore-next-line argument.type
			'apiSecret'     => get_option( 'nelio-ab-testing_settings' )['ga4_api_secret'] ?? '',
		),
		'ai'      => array(
			'access'      => 'protected',
			'description' => 'Stores the Google Analytics 4 tracking configuration used to send Nelio A/B Testing events. You cannot read or modify these credentials.',
		),
		'ui'      => fn() => array(
			'desc'  => true,
			'label' => _x( 'Google Analytics', 'text', 'nelio-ab-testing' ),
			'class' => Nelio_AB_Testing_Google_Analytics_Tracking_Setting::class,
		),
	),

	array(
		'type' => 'section',
		'icon' => 'dashicons-admin-tools',
		'name' => 'plugin-behavior',
		'ui'   => fn() => array(
			'label' => _x( 'Plugin Behavior', 'text', 'nelio-ab-testing' ),
		),
	),

	array(
		'type'    => 'checkbox',
		'name'    => 'is_alternative_distribution_allowed',
		'default' => false,
		'ai'      => array(
			'access'           => 'full',
			'description'      => 'Determines whether tests may use custom percentages for distributing visitors among variants.',
			'schema'           => array( 'type' => 'boolean' ),
			'is_write_allowed' => fn() => count( nab_get_running_experiments() )
				? new WP_Error(
					'setting-not-editable',
					'The supplied setting `is_alternative_distribution_allowed` cannot be changed while one or more tests are running.'
				)
				: true,
		),
		'ui'      => fn() => array(
			'label' => _x( 'Variants', 'text', 'nelio-ab-testing' ),
			'desc'  => _x( 'Allow custom variant distribution in tests', 'command', 'nelio-ab-testing' ) . (
				count( nab_get_running_experiments() )
					? '<br /><em>' . _x( '<strong>Warning!</strong> Avoid changing this setting while tests are running, as it may affect which variants visitors see.', 'user', 'nelio-ab-testing' ) . '</em>' // @codeCoverageIgnore
					: ''
			),
		),
	),

	array(
		'type'    => 'checkbox',
		'name'    => 'use_control_id_in_alternative',
		'default' => true,
		'ai'      => array(
			'access'      => 'full',
			'description' => 'Determines whether the control content identifier is reused when loading test variants.',
			'schema'      => array( 'type' => 'boolean' ),
		),
		'ui'      => fn() => array(
			'label' => '',
			'desc'  => _x( 'Use control ID in test variants', 'command', 'nelio-ab-testing' ),
			'more'  => 'https://neliosoftware.com/testing/help/is-nelio-ab-testing-compatible-with-page-builders/',
		),
	),

	array(
		'type'    => 'checkbox',
		'name'    => 'is_nab_first_arg',
		'default' => true,
		'ai'      => array(
			'access'      => 'full',
			'description' => 'Determines whether the Nelio testing query argument is placed first in URLs.',
			'schema'      => array( 'type' => 'boolean' ),
		),
		'ui'      => fn() => array(
			'label' => '',
			'desc'  => _x( 'Make testing query arg <code>nab</code> the first query arg in URLs instead of appending it', 'command', 'nelio-ab-testing' ),
		),
	),

	array(
		'type'    => 'checkbox',
		'name'    => 'hide_query_args',
		'default' => false,
		'ai'      => array(
			'access'      => 'full',
			'description' => 'Determines whether the Nelio testing query argument is removed from the URL after variant content loads.',
			'schema'      => array( 'type' => 'boolean' ),
		),
		'ui'      => fn() => array(
			'label' => '',
			'desc'  => _x( 'Hide testing query arg <code>nab</code> from URL after alternative content has been properly loaded', 'command', 'nelio-ab-testing' ),
		),
	),

	array(
		'type'    => 'checkbox',
		'name'    => 'preload_query_args',
		'default' => true,
		'ai'      => array(
			'access'      => 'full',
			'description' => 'Determines whether the Nelio testing query argument is added to links to speed up variant loading.',
			'schema'      => array( 'type' => 'boolean' ),
		),
		'ui'      => fn() => array(
			'label' => '',
			'desc'  => _x( 'Add testing query arg <code>nab</code> to all URLs to speed up page loading times when browsing your site', 'command', 'nelio-ab-testing' ),
		),
	),

	array(
		'type'    => 'checkbox',
		'name'    => 'inline_tracking_script',
		'default' => false,
		'ai'      => array(
			'access'      => 'full',
			'description' => 'Determines whether the tracking script is inserted inline instead of loaded as a separate script.',
			'schema'      => array( 'type' => 'boolean' ),
		),
		'ui'      => fn() => array(
			'label' => _x( 'Tracking Script', 'text', 'nelio-ab-testing' ),
			'desc'  => _x( 'Insert tracking script as an inline script', 'command', 'nelio-ab-testing' ),
		),
	),

	array(
		'type'    => 'custom',
		'name'    => 'public_checker',
		'default' => array(
			'enabled'      => true,
			'includeNames' => false,
		),
		'ai'      => array(
			'access'      => 'full',
			'description' => 'Configures the public debugging information shown by Nelio A/B Testing.',
			'schema'      => array(
				'type'                 => 'object',
				'properties'           => array(
					'enabled'      => array(
						'type'        => 'boolean',
						'description' => 'Whether the public debugging tool is enabled.',
					),
					'includeNames' => array(
						'type'        => 'boolean',
						'description' => 'Whether test names are included in the public debugging information.',
					),
				),
				'required'             => array( 'enabled', 'includeNames' ),
				'additionalProperties' => false,
			),
		),
		'ui'      => fn() => array(
			'label' => _x( 'Debug Tools', 'text', 'nelio-ab-testing' ),
			'class' => Nelio_AB_Testing_Public_Checker_Setting::class,
		),
	),

	array(
		'type'    => 'custom',
		'name'    => 'cloud_proxy_setting',
		'default' => array(
			'mode'             => 'disabled',
			'isCheckingStatus' => false,
			'value'            => '',
			'domain'           => '',
			'domainStatus'     => 'disabled',
		),
		'ai'      => array(
			'access'      => 'readonly',
			'description' => 'Reports the cloud proxy configuration used to forward site traffic through Nelio services. You can read this setting, but you cannot modify it.',
			'schema'      => array(
				'type'                 => 'object',
				'properties'           => array(
					'mode'             => array(
						'type'        => 'string',
						'description' => 'Cloud proxy operating mode.',
					),
					'isCheckingStatus' => array(
						'type'        => 'boolean',
						'description' => 'Whether the cloud proxy status is currently being checked.',
					),
					'value'            => array(
						'type'        => 'string',
						'description' => 'Cloud proxy configuration value.',
					),
					'domain'           => array(
						'type'        => 'string',
						'description' => 'Domain configured for the cloud proxy.',
					),
					'domainStatus'     => array(
						'type'        => 'string',
						'description' => 'Current status of the configured cloud proxy domain.',
					),
				),
				'additionalProperties' => false,
			),
		),
		'ui'      => fn() => array(
			'desc'  => true,
			'label' => _x( 'Cloud Proxy', 'text', 'nelio-ab-testing' ),
			'class' => Nelio_AB_Testing_Cloud_Proxy_Setting::class,
		),
	),

	array(
		'type'    => 'custom',
		'name'    => 'alternative_loading',
		'default' => array(
			'mode' => 'redirection',
		),
		'ai'      => array(
			'access'      => 'full',
			'description' => 'Defines how test variants are loaded for visitors.',
			'schema'      => array(
				'type'                 => 'object',
				'properties'           => array(
					'mode'                      => array(
						'type'        => 'string',
						'description' => 'Variant loading mode. `redirection` loads variants by redirecting the visitor, and `cookie` uses a cookie to select the variant. The cookie-with-redirection fallback is a derived runtime behavior, not a stored setting value.',
						'enum'        => array( 'redirection', 'cookie' ),
					),
					'lockParticipationSettings' => array(
						'type'        => 'boolean',
						'description' => 'Whether participation settings are locked so other code cannot change them.',
					),
					'redirectIfCookieIsMissing' => array(
						'type'        => 'boolean',
						'description' => 'Whether visitors are redirected when the variant-selection cookie is missing.',
					),
				),
				'required'             => array( 'mode' ),
				'additionalProperties' => false,
			),
		),
		'ui'      => fn() => array(
			'desc'  => false,
			'label' => _x( 'Variant Loading', 'text', 'nelio-ab-testing' ),
			'class' => Nelio_AB_Testing_Alternative_Loading_Setting::class,
		),
	),

	array(
		'type' => 'section',
		'icon' => '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" style="transform: translateY(1px); width: 1em; height: 1em; margin-right: 6px;"><path style="fill: currentcolor;" d="M19,1L17.74,3.75L15,5L17.74,6.26L19,9L20.25,6.26L23,5L20.25,3.75M9,4L6.5,9.5L1,12L6.5,14.5L9,20L11.5,14.5L17,12L11.5,9.5M19,15L17.74,17.74L15,19L17.74,20.25L19,23L20.25,20.25L23,19L20.25,17.74"></path></svg>',
		'name' => 'nelio-ai',
		'ui'   => fn() => array(
			'label' => _x( 'Recommendation Engine', 'text', 'nelio-ab-testing' ),
		),
	),

	array(
		'type'    => 'checkbox',
		'name'    => 'is_nelio_ai_enabled',
		'default' => true,
		'ai'      => array(
			'access'      => 'full',
			'description' => 'Determines whether Nelio AI may generate test suggestions in the recommendation engine.',
			'schema'      => array( 'type' => 'boolean' ),
		),
		'ui'      => fn() => array(
			'label' => 'Nelio AI',
			'desc'  => _x( 'Use AI to generate test suggestions', 'command', 'nelio-ab-testing' ),
		),
	),

	array(
		'type'    => 'custom',
		'name'    => 'google_analytics_data',
		'config'  => array(
			'visibility-toggle' => 'is_nelio_ai_enabled',
		),
		'default' => array(
			'enabled'      => false,
			// @phpstan-ignore-next-line argument.type
			'propertyId'   => get_option( 'nelio-ab-testing_settings' )['ga4_property_id'] ?? '',
			// @phpstan-ignore-next-line argument.type
			'propertyName' => get_option( 'nelio-ab-testing_settings' )['ga4_property_name'] ?? '',
		),
		'ai'      => array(
			'access'      => 'protected',
			'description' => 'Stores the Google Analytics 4 property information used by the recommendation engine to generate test suggestions. You cannot read or modify this setting directly.',
		),
		'ui'      => fn() => array(
			'label' => _x( 'Traffic Analysis', 'text', 'nelio-ab-testing' ),
			'class' => Nelio_AB_Testing_Google_Analytics_Data_Setting::class,
		),
	),

	array(
		'type' => 'section',
		'icon' => 'dashicons-admin-appearance',
		'name' => 'user-interface',
		'ui'   => fn() => array(
			'label' => _x( 'User Interface', 'text', 'nelio-ab-testing' ),
		),
	),

	array(
		'type'    => 'range',
		'name'    => 'min_sample_size',
		'default' => 100,
		'ai'      => array(
			'access'      => 'full',
			'description' => 'Defines the minimum number of page views required before statistical significance is computed.',
			'schema'      => array(
				'type'       => 'integer',
				'minimum'    => 100,
				'maximum'    => 1500,
				'multipleOf' => 100,
			),
		),
		'ui'      => fn() => array(
			'label' => _x( 'Required Sample Size', 'text', 'nelio-ab-testing' ),
			'desc'  => _x( 'The sample size is the number of observations taken from a population through which statistical inferences for the whole population are made. The larger the sample size, the more accurate your results will be. This setting defines the minimum number of page views required by a test in order to determine whether one of its variants is better than the rest. Recommended value: 500.', 'user', 'nelio-ab-testing' ),
			'args'  => array(
				/* translators: %s: Page views. */
				'label' => sprintf( _x( 'Nelio A/B Testing will compute statistical significance if the test has at least <strong>%s</strong> page views.', 'text', 'nelio-ab-testing' ), '{value}' ),
				'min'   => 100,
				'max'   => 1500,
				'step'  => 100,
			),
		),
	),

	array(
		'type'    => 'range',
		'name'    => 'min_confidence',
		'default' => 90,
		'ai'      => array(
			'access'      => 'full',
			'description' => 'Defines the minimum confidence percentage required before a test is reported as having a winner.',
			'schema'      => array(
				'type'    => 'integer',
				'minimum' => 80,
				'maximum' => 99,
			),
		),
		'ui'      => fn() => array(
			'label' => _x( 'Required Confidence', 'text', 'nelio-ab-testing' ),
			'desc'  => _x( 'The confidence level is the percentage of time that a statistical result would be correct if you took numerous random samples. In other words, it’s a measure of “assuredness.” When Nelio A/B Testing finds a winner in a test, there’s an associated confidence value that tells you how likely it is that the winner is really better than the other variants. Changing the required confidence value will change some visual clues in the user interface that will help you identify when you can call a winner. Recommended value: 95% or above.', 'user', 'nelio-ab-testing' ),
			'args'  => array(
				/* translators: %s: Confidence value. */
				'label' => sprintf( _x( 'Nelio A/B Testing will only report a test has an actual winner if its confidence is at least <strong>%s%%</strong>.', 'text', 'nelio-ab-testing' ), '{value}' ),
				'min'   => 80,
				'max'   => 99,
				'step'  => 1,
			),
		),
	),

	array(
		'type'    => 'checkbox',
		'name'    => 'are_auto_tutorials_enabled',
		'default' => true,
		'ai'      => array(
			'access'      => 'full',
			'description' => 'Determines whether plugin tutorials are shown automatically to new users.',
			'schema'      => array( 'type' => 'boolean' ),
		),
		'ui'      => fn() => array(
			'label' => _x( 'Miscellaneous', 'text', 'nelio-ab-testing' ),
			'desc'  => _x( 'Show plugin tutorials automatically to introduce new users to Nelio A/B Testing’s features', 'command', 'nelio-ab-testing' ),
		),
	),

	array(
		'type'   => 'section',
		'icon'   => 'dashicons-bell',
		'name'   => 'notifications-setup',
		'config' => array(
			'required-plan' => 'professional',
		),
		'ui'     => fn() => array(
			'label' => _x( 'Notifications', 'text', 'nelio-ab-testing' ),
		),
	),

	array(
		'type'    => 'textarea',
		'name'    => 'notification_emails',
		'default' => '',
		'config'  => array(
			'required-plan' => 'professional',
		),
		'ai'      => array(
			'access'      => 'full',
			'description' => 'Defines the email addresses that receive plugin notifications, with one address per line.',
			'schema'      => array( 'type' => 'string' ),
		),
		'ui'      => fn() => array(
			'label'       => _x( 'Email(s)', 'text', 'nelio-ab-testing' ),
			'desc'        => _x( 'Nelio A/B Testing might send some email notifications when certain events occur. Use this field to specify the email(s) that should receive these notification emails. Please write one email per line.', 'user', 'nelio-ab-testing' ),
			'placeholder' => get_option( 'admin_email', '' ),
		),
	),

	array(
		'type'    => 'checkbox',
		'name'    => 'notify_experiment_start',
		'default' => true,
		'config'  => array(
			'required-plan' => 'professional',
		),
		'ai'      => array(
			'access'      => 'full',
			'description' => 'Determines whether an email notification is sent when a test starts.',
			'schema'      => array( 'type' => 'boolean' ),
		),
		'ui'      => fn() => array(
			'label' => _x( 'Tests', 'text', 'nelio-ab-testing' ),
			'desc'  => _x( 'Send email notification when a test starts', 'command', 'nelio-ab-testing' ),
		),
	),

	array(
		'type'    => 'checkbox',
		'name'    => 'notify_experiment_stop',
		'default' => true,
		'config'  => array(
			'required-plan' => 'professional',
		),
		'ai'      => array(
			'access'      => 'full',
			'description' => 'Determines whether an email notification is sent when a test stops.',
			'schema'      => array( 'type' => 'boolean' ),
		),
		'ui'      => fn() => array(
			'label' => '',
			'desc'  => _x( 'Send email notification when a test stops', 'command', 'nelio-ab-testing' ),
		),
	),

	array(
		'type'    => 'checkbox',
		'name'    => 'notify_no_more_quota',
		'default' => true,
		'config'  => array(
			'required-plan' => 'professional',
		),
		'ai'      => array(
			'access'      => 'full',
			'description' => 'Determines whether an email notification is sent when no tracking quota remains.',
			'schema'      => array( 'type' => 'boolean' ),
		),
		'ui'      => fn() => array(
			'label' => _x( 'Account', 'text', 'nelio-ab-testing' ),
			'desc'  => _x( 'Send email notification when there is no more quota', 'command', 'nelio-ab-testing' ),
		),
	),

	array(
		'type'    => 'checkbox',
		'name'    => 'notify_almost_no_more_quota',
		'default' => true,
		'config'  => array(
			'required-plan' => 'professional',
		),
		'ai'      => array(
			'access'      => 'full',
			'description' => 'Determines whether an email notification is sent when less than 20 percent of tracking quota remains.',
			'schema'      => array( 'type' => 'boolean' ),
		),
		'ui'      => fn() => array(
			'label' => '',
			'desc'  => _x( 'Send email notification when there is less than 20% of quota remaining', 'command', 'nelio-ab-testing' ),
		),
	),

	array(
		'type' => 'section',
		'icon' => 'dashicons-admin-generic',
		'name' => 'misc',
		'ui'   => fn() => array(
			'label' => _x( 'Misc', 'text', 'nelio-ab-testing' ),
		),
	),

	array(
		'type'    => 'custom',
		'name'    => 'has_user_objected_to_product_offers',
		'default' => false,
		'ai'      => array(
			'access'      => 'full',
			'description' => 'Records whether the site user has objected to receiving product offers.',
			'schema'      => array( 'type' => 'boolean' ),
		),
		'ui'      => fn() => array(
			'desc'  => true,
			'label' => _x( 'Product Offers', 'text', 'nelio-ab-testing' ),
			'class' => Nelio_AB_Testing_Product_Offers_Setting::class,
		),
	),

	array(
		'type'    => 'custom',
		'name'    => '_external_page_script',
		'default' => '',
		'ui'      => fn() => array(
			'class' => Nelio_AB_Testing_External_Page_Script::class,
			'label' => _x( 'External Page Script', 'text', 'nelio-ab-testing' ),
			'desc'  => true,
		),
	),

);
