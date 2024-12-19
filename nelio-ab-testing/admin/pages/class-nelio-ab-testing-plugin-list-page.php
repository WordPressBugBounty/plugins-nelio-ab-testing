<?php
/**
 * This file customizes the plugin list page added by WordPress.
 *
 * @package    Nelio_AB_Testing
 * @subpackage Nelio_AB_Testing/admin/pages
 * @since      5.0.6
 */

defined( 'ABSPATH' ) || exit;

/**
 * This class contains several methods to customize the plugin list page added
 * by WordPress and, in particular, the actions associated with Nelio A/B Testing.
 *
 * @package    Nelio_AB_Testing
 * @subpackage Nelio_AB_Testing/admin/pages
 * @since      5.0.6
 */
class Nelio_AB_Testing_Plugin_List_Page {

	public function init() {

		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_assets' ) );
		add_action( 'plugin_action_links_' . nelioab()->plugin_file, array( $this, 'customize_plugin_actions' ) );
	}//end init()

	public function customize_plugin_actions( $actions ) {

		if ( ! nab_get_site_id() ) {
			return $actions;
		}//end if

		if ( ! nab_is_subscribed() ) {

			$subscribe_url = add_query_arg(
				array(
					'utm_source'   => 'nelio-ab-testing',
					'utm_medium'   => 'plugin',
					'utm_campaign' => 'free',
					'utm_content'  => 'subscribe-in-plugin-list',
				),
				_x( 'https://neliosoftware.com/testing/pricing/', 'text', 'nelio-ab-testing' )
			);

			$actions['subscribe'] = sprintf(
				'<a href="%s" target="_blank">%s</a>',
				esc_url( $subscribe_url ),
				esc_html_x( 'Subscribe', 'command', 'nelio-ab-testing' )
			);

		}//end if

		if ( isset( $actions['deactivate'] ) && current_user_can( 'deactivate_plugin', nelioab()->plugin_file ) ) {
			$actions['deactivate'] = sprintf(
				'<span class="nelio-ab-testing-deactivate-link">%1$s</span>',
				$actions['deactivate']
			);
		}//end if

		return $actions;
	}//end customize_plugin_actions()

	public function enqueue_assets() {

		$screen = get_current_screen();
		if ( empty( $screen ) || 'plugins' !== $screen->id ) {
			return;
		}//end if

		$settings = array(
			'isSubscribed'    => nab_is_subscribed(),
			'cleanNonce'      => wp_create_nonce( 'nab_clean_plugin_data_' . get_current_user_id() ),
			'deactivationUrl' => $this->get_deactivation_url(),
		);

		$script = '
		( function() {
			wp.domReady( function() {
				nab.initPage( %s );
			} );
		} )();';

		wp_enqueue_style(
			'nab-plugin-list-page',
			nelioab()->plugin_url . '/assets/dist/css/plugin-list-page.css',
			array( 'nab-components' ),
			nelioab()->plugin_version
		);
		nab_enqueue_script_with_auto_deps( 'nab-plugin-list-page', 'plugin-list-page', true );

		wp_add_inline_script(
			'nab-plugin-list-page',
			sprintf(
				$script,
				wp_json_encode( $settings ) // phpcs:ignore
			)
		);
	}//end enqueue_assets()

	private function get_deactivation_url() {

		global $status, $page, $s;
		return add_query_arg(
			array(
				'action'        => 'deactivate',
				'plugin'        => rawurlencode( nelioab()->plugin_file ),
				'plugin_status' => $status,
				'paged'         => $page,
				's'             => $s,
				'_wpnonce'      => wp_create_nonce( 'deactivate-plugin_' . nelioab()->plugin_file ),
			),
			admin_url( 'plugins.php' )
		);
	}//end get_deactivation_url()
}//end class
