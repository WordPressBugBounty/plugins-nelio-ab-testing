<?php
/**
 * List of settings.
 *
 * @package    Nelio A/B Testing
 * @subpackage Nelio A/B Testing/includes/data
 * @since      5.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}//end if

// Fix GDPR cookie setting.
add_filter(
	'option_nelio-ab-testing_settings',
	function ( $settings ) {
		$old_value = nab_array_get( $settings, 'gdpr_cookie_name', '' );
		$new_value = nab_array_get(
			$settings,
			'gdpr_cookie_setting',
			array(
				'name'  => '',
				'value' => '',
			)
		);

		$new_value['name'] = empty( $new_value['name'] ) ? $old_value : $new_value['name'];

		unset( $settings['gdpr_cookie_name'] );
		$settings['gdpr_cookie_setting'] = $new_value;

		return $settings;
	}
);

return array(

	array(
		'type' => 'section',
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
		'ui'      => fn() => array(
			'label' => _x( 'Tested Visitors', 'text', 'nelio-ab-testing' ) .
				( nab_is_subscribed_to( 'basic' ) ? '' : '<span style="font-weight:normal;font-size:12px;margin-left:1em;border:1px solid currentColor;border-radius:2px;padding:2px 5px;">Premium</span>' ),
			'desc'  => _x( 'When a person accesses your website she may participate in your running tests. This setting defines how likely it is for a visitor to be part of your tests.', 'user', 'nelio-ab-testing' ),
			'args'  => array(
				/* translators: percentage of visitors */
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
		'config'  => array(
			'required-plan' => 'basic',
		),
		'ui'      => fn() => array(
			'label'   => _x( 'Segmentation', 'text', 'nelio-ab-testing' ) .
				( nab_is_subscribed_to( 'basic' ) ? '' : '<span style="font-weight:normal;font-size:12px;margin-left:1em;border:1px solid currentColor;border-radius:2px;padding:2px 5px;">Premium</span>' ),
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
		'config'  => array(
			'required-plan' => 'basic',
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
		'ui'      => fn() => array(
			'label' => '',
			'desc'  => _x( 'Exclude bots from participating in split tests', 'command', 'nelio-ab-testing' ),
		),
	),

	array(
		'type'    => 'custom',
		'name'    => '_excluded_ips',
		'default' => array(
			'name'  => '',
			'value' => '',
		),
		'ui'      => fn() => array(
			'desc'     => true,
			'label'    => _x( 'Excluded IPs', 'text', 'nelio-ab-testing' ),
			'instance' => new Nelio_AB_Testing_Excluded_IPs_Setting(),
		),
	),

	array(
		'type'    => 'custom',
		'name'    => 'gdpr_cookie',
		'default' => array(
			'name'  => '',
			'value' => '',
		),
		'ui'      => fn() => array(
			'desc'     => true,
			'label'    => _x( 'GDPR Cookie', 'text', 'nelio-ab-testing' ),
			'instance' => new Nelio_AB_Testing_GDPR_Cookie_Setting(),
		),
	),

	array(
		'type' => 'section',
		'name' => 'plugin-behavior',
		'ui'   => fn() => array(
			'label' => _x( 'Plugin Behavior', 'text', 'nelio-ab-testing' ),
		),
	),

	array(
		'type'    => 'checkbox',
		'name'    => 'use_control_id_in_alternative',
		'default' => true,
		'ui'      => fn() => array(
			'label' => _x( 'Variants', 'text', 'nelio-ab-testing' ),
			'desc'  => _x( 'Use control ID in test variants', 'command', 'nelio-ab-testing' ),
			'more'  => _x( 'https://neliosoftware.com/testing/help/is-nelio-ab-testing-compatible-with-page-builders/', 'text', 'nelio-ab-testing' ),
		),
	),

	array(
		'type'    => 'checkbox',
		'name'    => 'hide_query_args',
		'default' => false,
		'ui'      => fn() => array(
			'label' => '',
			'desc'  => _x( 'Hide testing query arg <code>nab</code> from URL after alternative content has been properly loaded', 'command', 'nelio-ab-testing' ),
		),
	),

	array(
		'type'    => 'checkbox',
		'name'    => 'preload_query_args',
		'default' => true,
		'ui'      => fn() => array(
			'label' => '',
			'desc'  => _x( 'Add testing query arg <code>nab</code> to all URLs to speed up page loading times when browsing your site', 'command', 'nelio-ab-testing' ),
		),
	),

	array(
		'type'    => 'checkbox',
		'name'    => 'inline_tracking_script',
		'default' => false,
		'ui'      => fn() => array(
			'label' => _x( 'Tracking Script', 'text', 'nelio-ab-testing' ),
			'desc'  => _x( 'Insert tracking script as an inline script', 'command', 'nelio-ab-testing' ),
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
		'ui'      => fn() => array(
			'desc'     => true,
			'label'    => _x( 'Cloud Proxy', 'text', 'nelio-ab-testing' ),
			'instance' => new Nelio_AB_Testing_Cloud_Proxy_Setting(),
		),
	),

	array(
		'type'    => 'custom',
		'name'    => 'alternative_loading',
		'default' => array(
			'mode' => 'redirection',
		),
		'ui'      => fn() => array(
			'desc'     => false,
			'label'    => _x( 'Variant Loading', 'text', 'nelio-ab-testing' ),
			'instance' => new Nelio_AB_Testing_Alternative_Loading_Setting(),
		),
	),

	array(
		'type' => 'section',
		'name' => 'user-interface',
		'ui'   => fn() => array(
			'label' => _x( 'User Interface', 'text', 'nelio-ab-testing' ),
		),
	),

	array(
		'type'    => 'range',
		'name'    => 'min_sample_size',
		'default' => 100,
		'ui'      => fn() => array(
			'label' => _x( 'Required Sample Size', 'text', 'nelio-ab-testing' ),
			'desc'  => _x( 'The sample size is the number of observations taken from a population through which statistical inferences for the whole population are made. The larger the sample size, the more accurate your results will be. This setting defines the minimum number of page views required by a test in order to determine whether one of its variants is better than the rest. Recommended value: 500.', 'user', 'nelio-ab-testing' ),
			'args'  => array(
				/* translators: page views */
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
		'default' => 85,
		'ui'      => fn() => array(
			'label' => _x( 'Required Confidence', 'text', 'nelio-ab-testing' ),
			'desc'  => _x( 'The confidence level is the percentage of time that a statistical result would be correct if you took numerous random samples. In other words, it’s a measure of “assuredness.” When Nelio A/B Testing finds a winner in a test, there’s an associated confidence value that tells you how likely it is that the winner is really better than the other variants. Changing the required confidence value will change some visual clues in the user interface that will help you identify when you can call a winner. Recommended value: 95% or above.', 'user', 'nelio-ab-testing' ),
			'args'  => array(
				/* translators: confidence value */
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
		'ui'      => fn() => array(
			'label' => _x( 'Miscellaneous', 'text', 'nelio-ab-testing' ),
			'desc'  => _x( 'Show plugin tutorials automatically to introduce new users to Nelio A/B Testing’s features', 'command', 'nelio-ab-testing' ),
		),
	),

	array(
		'type'   => 'section',
		'name'   => 'notifications-setup',
		'config' => array(
			'required-plan' => 'professional',
		),
		'ui'     => fn() => array(
			'label' => _x( 'Notifications', 'text', 'nelio-ab-testing' ) .
				( nab_is_subscribed_to( 'professional' ) ? '' : '<span style="font-weight:normal;font-size:12px;margin-left:1em;border:1px solid currentColor;border-radius:2px;padding:2px 5px;">Professional</span>' ),
		),
	),

	array(
		'type'    => 'textarea',
		'name'    => 'notification_emails',
		'default' => '',
		'config'  => array(
			'required-plan' => 'professional',
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
		'ui'      => fn() => array(
			'label' => '',
			'desc'  => _x( 'Send email notification when there is less than 20%% of quota remaining', 'command', 'nelio-ab-testing' ),
		),
	),

);
