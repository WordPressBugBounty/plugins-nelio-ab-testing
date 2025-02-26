<?php
/**
 * The admin-specific functionality of the plugin.
 *
 * @package    Nelio_AB_Testing
 * @subpackage Nelio_AB_Testing/admin
 * @since      5.0.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * The admin-specific functionality of the plugin.
 */
class Nelio_AB_Testing_Admin {

	protected static $instance;

	public static function instance() {

		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
		}//end if

		return self::$instance;
	}//end instance()

	public function init() {

		add_action( 'admin_menu', array( $this, 'create_menu_pages' ) );
		add_action( 'admin_menu', array( $this, 'remove_main_page' ), 999 );
		add_action( 'admin_enqueue_scripts', array( $this, 'register_assets' ), 5 );
		add_filter( 'option_page_capability_nelio-ab-testing_group', array( $this, 'get_settings_capability' ) );
	}//end init()

	public function create_menu_pages() {

		add_menu_page(
			'Nelio A/B Testing',
			'Nelio A/B Testing',
			nelioab()->is_ready() ? 'edit_nab_experiments' : 'manage_nab_account',
			'nelio-ab-testing',
			null,
			$this->get_plugin_icon(),
			25
		);

		if ( ! nelioab()->is_ready() ) {
			$page = new Nelio_AB_Testing_Welcome_Page();
			$page->init();
			return;
		}//end if

		$page = new Nelio_AB_Testing_Overview_Page();
		$page->init();

		$page = new Nelio_AB_Testing_Experiment_List_Page();
		$page->init();

		$page = new Nelio_AB_Testing_Experiment_Page();
		$page->init();

		$page = new Nelio_AB_Testing_Results_Page();
		$page->init();

		if (
			/**
			 * Whether the recordings page should be visible.
			 *
			 * Used to decide if the recordings page should be visible.
			 *
			 * @param boolean $is_visible
			 *
			 * @since 6.4.0
			 */
			apply_filters( 'nab_show_session_recordings_page', ! nab_are_subscription_controls_disabled() )
		) {
			$page = new Nelio_AB_Testing_Recordings_Page();
			$page->init();
		}//end if

		if ( ! nab_are_subscription_controls_disabled() ) {
			$page = new Nelio_AB_Testing_Account_Page();
			$page->init();
		}//end if

		$page = new Nelio_AB_Testing_Settings_Page();
		$page->init();

		$page = new Nelio_AB_Testing_Roadmap_Page();
		$page->init();

		$page = new Nelio_AB_Testing_Help_Page();
		$page->init();

		$page = new Nelio_AB_Testing_Plugin_List_Page();
		$page->init();
	}//end create_menu_pages()

	public function remove_main_page() {
		if ( ! nelioab()->is_ready() ) {
			return;
		}//end if

		global $submenu;
		if ( nab_array_get( $submenu, array( 'nelio-ab-testing', 0, 2 ), false ) === 'nelio-ab-testing' ) {
			unset( $submenu['nelio-ab-testing'][0] );
			$submenu['nelio-ab-testing'] = array_values( $submenu['nelio-ab-testing'] ); // phpcs:ignore
		}//end if
	}//end remove_main_page()

	public function register_assets() {

		$url     = nelioab()->plugin_url;
		$version = nelioab()->plugin_version;

		$scripts = array(
			'nab-components',
			'nab-conversion-action-library',
			'nab-conversion-actions',
			'nab-data',
			'nab-date',
			'nab-editor',
			'nab-experiment-library',
			'nab-experiments',
			'nab-heatmap-editor',
			'nab-i18n',
			'nab-segmentation-rule-library',
			'nab-segmentation-rules',
			'nab-utils',
		);

		foreach ( $scripts as $script ) {
			$file_without_ext = preg_replace( '/^nab-/', '', $script );
			nab_register_script_with_auto_deps( $script, $file_without_ext, true );
		}//end foreach

		wp_add_inline_script(
			'nab-data',
			sprintf(
				'wp.data.dispatch( "nab/data" ).receivePluginSettings( %s );' .
				'wp.data.dispatch( "nab/data" ).receiveECommerceSettings( "woocommerce", %s );' .
				'wp.data.dispatch( "nab/data" ).receiveECommerceSettings( "edd", %s );',
				wp_json_encode( $this->get_plugin_settings() ),
				wp_json_encode( $this->get_woocommerce_settings() ),
				wp_json_encode( $this->get_edd_settings() )
			)
		);

		/**
		 * Filters global variables and functions in the JavaScript editor to prevent it from showing linter warnings when using one of those.
		 *
		 * @param array $globals List of global variables and functions. Default: empty array.
		 *
		 * @since 7.4.0
		 */
		$javascript_globals = apply_filters( 'nab_javascript_editor_globals', array() );
		if ( ! empty( $javascript_globals ) ) {
			wp_add_inline_script(
				'nab-components',
				sprintf(
					'wp.data.dispatch( "nab/data" ).setPageAttribute( "components/javascriptGlobals", %s );',
					wp_json_encode( $javascript_globals )
				)
			);
		}//end if

		wp_localize_script(
			'nab-i18n',
			'nabI18n',
			array(
				'locale' => str_replace( '_', '-', get_locale() ),
			)
		);

		wp_register_style(
			'nab-components',
			$url . '/assets/dist/css/components.css',
			array( 'wp-admin', 'wp-components' ),
			$version
		);

		wp_register_style(
			'nab-conversion-action-library',
			$url . '/assets/dist/css/conversion-action-library.css',
			array(),
			$version
		);

		wp_register_style(
			'nab-segmentation-rule-library',
			$url . '/assets/dist/css/segmentation-rule-library.css',
			array(),
			$version
		);

		wp_register_style(
			'nab-editor',
			$url . '/assets/dist/css/editor.css',
			array( 'nab-components', 'nab-experiment-library', 'nab-conversion-action-library', 'nab-segmentation-rule-library', 'wp-edit-post' ),
			$version
		);

		wp_register_style(
			'nab-experiment-library',
			$url . '/assets/dist/css/experiment-library.css',
			array( 'nab-components' ),
			$version
		);

		wp_register_style(
			'nab-heatmap-editor',
			$url . '/assets/dist/css/heatmap-editor.css',
			array( 'nab-editor' ),
			$version
		);
	}//end register_assets()

	public function get_settings_capability() {
		return 'manage_nab_options';
	}//end get_settings_capability()

	private function get_plugin_icon() {

		$svg_icon_file = nelioab()->plugin_path . '/assets/dist/images/logo.svg';
		if ( ! file_exists( $svg_icon_file ) ) {
			return false;
		}//end if

		return 'data:image/svg+xml;base64,' . base64_encode( file_get_contents( $svg_icon_file ) ); // phpcs:ignore
	}//end get_plugin_icon()

	private function get_plugin_settings() {
		$settings = Nelio_AB_Testing_Settings::instance();
		return array(
			'adminUrl'                        => admin_url(),
			'apiUrl'                          => nab_get_api_url( '', 'browser' ),
			'areAutoTutorialsEnabled'         => $settings->get( 'are_auto_tutorials_enabled' ),
			'areSubscriptionControlsDisabled' => nab_are_subscription_controls_disabled(),
			'capabilities'                    => $this->get_nab_capabilities(),
			'goalTracking'                    => $settings->get( 'goal_tracking' ),
			'homeUrl'                         => nab_home_url(),
			'isCookieTestingEnabled'          => 'redirection' !== nab_get_variant_loading_strategy(),
			'maxCombinations'                 => nab_max_combinations(),
			'minConfidence'                   => $settings->get( 'min_confidence' ),
			'minSampleSize'                   => $settings->get( 'min_sample_size' ),
			'segmentEvaluation'               => $settings->get( 'segment_evaluation' ),
			'siteId'                          => nab_get_site_id(),
			'subscription'                    => nab_get_subscription(),
			'themeSupport'                    => array(
				'menus'   => current_theme_supports( 'menus' ),
				'widgets' => current_theme_supports( 'widgets' ),
			),
		);
	}//end get_plugin_settings()

	private function get_nab_capabilities() {
		$aux  = Nelio_AB_Testing_Capability_Manager::instance();
		$caps = $aux->get_all_capabilities();
		$caps = array_filter(
			$caps,
			function ( $cap ) {
				return current_user_can( $cap );
			}
		);
		return array_values( $caps );
	}//end get_nab_capabilities()

	private function get_woocommerce_settings() {
		$statuses = function_exists( 'wc_get_order_statuses' ) ? wc_get_order_statuses() : array();
		$statuses = array_map(
			function ( $key, $value ) {
				return array(
					'value' => $key,
					'label' => $value,
				);
			},
			array_keys( $statuses ),
			array_values( $statuses )
		);
		return array(
			'currency'           => function_exists( 'get_woocommerce_currency' ) ? html_entity_decode( get_woocommerce_currency(), ENT_COMPAT ) : 'USD',
			'currencyPosition'   => strpos( get_option( 'woocommerce_currency_pos', 'left' ), 'right' ) !== false ? 'after' : 'before',
			'currencySymbol'     => function_exists( 'get_woocommerce_currency_symbol' ) ? html_entity_decode( get_woocommerce_currency_symbol(), ENT_COMPAT ) : '$',
			'decimalSeparator'   => get_option( 'woocommerce_price_decimal_sep', '.' ) ? get_option( 'woocommerce_price_decimal_sep', '.' ) : '.',
			'numberOfDecimals'   => absint( get_option( 'woocommerce_price_num_decimals', true ) ),
			'orderStatuses'      => $statuses,
			'thousandsSeparator' => get_option( 'woocommerce_price_thousand_sep', ',' ) ? get_option( 'woocommerce_price_thousand_sep', ',' ) : ',',
		);
	}//end get_woocommerce_settings()

	private function get_edd_settings() {
		$statuses = function_exists( 'edd_get_payment_statuses' ) ? edd_get_payment_statuses() : array();
		$statuses = array_map(
			function ( $key, $value ) {
				return array(
					'value' => $key,
					'label' => $value,
				);
			},
			array_keys( $statuses ),
			array_values( $statuses )
		);
		return array(
			'currency'           => function_exists( 'edd_get_currency_name' ) ? html_entity_decode( edd_get_currency_name(), ENT_COMPAT ) : 'USD',
			'currencyPosition'   => function_exists( 'edd_get_option' ) ? edd_get_option( 'currency_position', 'before' ) : 'before',
			'currencySymbol'     => function_exists( 'edd_currency_symbol' ) ? html_entity_decode( edd_currency_symbol(), ENT_COMPAT ) : '$',
			'decimalSeparator'   => function_exists( 'edd_get_option' ) ? edd_get_option( 'decimal_separator', '.' ) : '.',
			'numberOfDecimals'   => absint( get_option( 'woocommerce_price_num_decimals', true ) ),
			'orderStatuses'      => $statuses,
			'thousandsSeparator' => function_exists( 'edd_get_option' ) ? edd_get_option( 'thousands_separator', ',' ) : ',',
		);
	}//end get_edd_settings()
}//end class
