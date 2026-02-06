<?php
/**
 * The public-facing functionality of the plugin.
 *
 * @package    Nelio_AB_Testing
 * @subpackage Nelio_AB_Testing/public
 * @since      5.0.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * The public-facing functionality of the plugin.
 */
class Nelio_AB_Testing_Main_Script {

	/**
	 * This instance.
	 *
	 * @var Nelio_AB_Testing_Main_Script|null
	 */
	protected static $instance;

	/**
	 * Returns the single instance of this class.
	 *
	 * @return Nelio_AB_Testing_Main_Script
	 */
	public static function instance() {

		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Hooks into WordPress.
	 *
	 * @return void
	 */
	public function init() {
		add_action( 'wp_head', array( $this, 'maybe_add_overlay' ), 1 );
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_script' ), 1 );
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_visitor_type_script' ), 1 );
		add_filter( 'script_loader_tag', array( $this, 'add_extra_script_attributes' ), 10, 2 );
		add_filter( 'wp_inline_script_attributes', array( $this, 'add_extra_inline_script_attributes' ) );
	}

	/**
	 * Callback to add content overlay if needed.
	 *
	 * @return void
	 */
	public function maybe_add_overlay() {
		if ( nab_is_split_testing_disabled() ) {
			return;
		}

		$experiments = $this->get_running_experiment_summaries();
		$heatmaps    = $this->get_relevant_heatmap_summaries();
		if ( $this->can_skip_script_enqueueing( $experiments, $heatmaps ) ) {
			return;
		}

		nab_print_loading_overlay();
	}

	/**
	 * Callback to enqueue Nelio’s main script if needed.
	 *
	 * @return void
	 */
	public function enqueue_script() {
		$site_id = nab_get_site_id();
		if ( empty( $site_id ) ) {
			return;
		}

		if ( nab_is_split_testing_disabled() ) {
			return;
		}

		$settings = $this->get_script_settings();
		if ( $this->can_skip_script_enqueueing( $settings['experiments'], $settings['heatmaps'] ) ) {
			return;
		}

		$plugin_settings = Nelio_AB_Testing_Settings::instance();
		if ( empty( $plugin_settings->get( 'inline_tracking_script' ) ) ) {
			$can_be_async = (
				count( $settings['alternativeUrls'] ) < 2 &&
				false !== $settings['cookieTesting']
			);
			nab_enqueue_script_with_auto_deps(
				'nelio-ab-testing-main',
				'public',
				$can_be_async ? array( 'strategy' => 'async' ) : array()
			);
		} else {
			nab_require_wp_file( '/wp-admin/includes/class-wp-filesystem-base.php' );
			nab_require_wp_file( '/wp-admin/includes/class-wp-filesystem-direct.php' );
			$filesystem = new \WP_Filesystem_Direct( true );

			// phpcs:ignore WordPress.WP.EnqueuedResourceParameters.MissingVersion, WordPress.WP.EnqueuedResourceParameters.NotInFooter
			wp_register_script( 'nelio-ab-testing-main', '' );
			wp_enqueue_script( 'nelio-ab-testing-main' );
			$script = nelioab()->plugin_path . '/assets/dist/js/public.js';
			$script = file_exists( $script ) ? $filesystem->get_contents( $script ) : '';
			$script = is_string( $script ) ? $script : '';
			wp_add_inline_script( 'nelio-ab-testing-main', $script );
		}

		wp_add_inline_script(
			'nelio-ab-testing-main',
			sprintf( 'window.nabIsLoading=true;window.nabSettings=%s;', wp_json_encode( $settings ) ),
			'before'
		);
	}

	/**
	 * Callback to enqueue visitor type script if needed.
	 *
	 * This script is used to determine if a visitor is a new visitor or a returning visitor. It’s something we need to keep track independently from our split tests.
	 *
	 * @return void
	 */
	public function enqueue_visitor_type_script() {
		/**
		 * Short-circuits the enqueue of the visitor type script.
		 *
		 * @param bool $short-circuit Whether to skip the addition of the visitor type script. Default: `false`.
		 *
		 * @since 8.1.0
		 */
		$skip = apply_filters( 'nab_disable_visitor_type_script', false );
		if ( $skip ) {
			return;
		}

		nab_enqueue_script_with_auto_deps(
			'nelio-ab-testing-visitor-type',
			'visitor-type'
		);
	}

	/**
	 * Callback to add extra attributes to Nelio’s main script.
	 *
	 * @param string $tag    Script tag.
	 * @param string $handle Script handle.
	 *
	 * @return string
	 */
	public function add_extra_script_attributes( $tag, $handle ) {
		if ( 'nelio-ab-testing-main' !== $handle ) {
			return $tag;
		}
		$attrs = nab_get_extra_script_attributes();
		$attrs = implode(
			' ',
			array_map(
				function ( $key, $value ) {
					return sprintf( '%s="%s"', $key, esc_attr( $value ) );
				},
				array_keys( $attrs ),
				array_values( $attrs )
			)
		);
		return empty( $attrs ) ? $tag : str_replace( '></script>', " {$attrs}></script>", $tag );
	}

	/**
	 * Callback to add extra attributes to Nelio’s main inline script.
	 *
	 * @param array<string,string> $attrs Current attributes.
	 *
	 * @return array<string,string>
	 */
	public function add_extra_inline_script_attributes( $attrs ) {
		if ( ! isset( $attrs['id'] ) || 'nelio-ab-testing-main-js-before' !== $attrs['id'] ) {
			return $attrs;
		}
		$attrs = array_merge( $attrs, nab_get_extra_script_attributes() );
		return $attrs;
	}

	/**
	 * Gets public script settings.
	 *
	 * @return TPublic_Settings
	 */
	private function get_script_settings() {
		$runtime         = Nelio_AB_Testing_Runtime::instance();
		$plugin_settings = Nelio_AB_Testing_Settings::instance();

		$settings = array(
			'alternativeUrls'     => $this->get_alternative_urls(),
			'api'                 => $this->get_api_settings(),
			'cookieTesting'       => $this->get_cookie_testing(),
			'excludeBots'         => ! empty( $plugin_settings->get( 'exclude_bots' ) ),
			'experiments'         => $this->get_running_experiment_summaries(),
			'gdprCookie'          => $this->get_gdpr_cookie(),
			'heatmaps'            => $this->get_relevant_heatmap_summaries(),
			'hideQueryArgs'       => ! empty( $plugin_settings->get( 'hide_query_args' ) ),
			'ignoreTrailingSlash' => nab_ignore_trailing_slash_in_alternative_loading(),
			'isGA4Integrated'     => ! empty( $plugin_settings->get( 'google_analytics_tracking' )['enabled'] ),
			'isStagingSite'       => ! empty( nab_is_staging() ),
			'isTestedPostRequest' => $runtime->is_tested_post_request(),
			'maxCombinations'     => nab_max_combinations(),
			'nabPosition'         => $plugin_settings->get( 'is_nab_first_arg' ) ? 'first' : 'last',
			'numOfAlternatives'   => $runtime->get_number_of_alternatives(),
			'optimizeXPath'       => $this->should_track_clicks_with_optimized_xpath(),
			'participationChance' => absint( $plugin_settings->get( 'percentage_of_tested_visitors' ) ),
			'postId'              => is_singular() ? get_the_ID() : false,
			'preloadQueryArgUrls' => nab_get_preload_query_arg_urls(),
			'segmentMatching'     => ! empty( $plugin_settings->get( 'match_all_segments' ) ) ? 'all' : 'some',
			'singleConvPerView'   => $this->get_single_conversion_per_view(),
			'site'                => nab_get_site_id(),
			'throttle'            => $this->get_throttle_settings(),
			'timezone'            => nab_get_timezone(),
			'useControlUrl'       => $this->use_control_url(),
			'useSendBeacon'       => $this->use_send_beacon(),
			'version'             => nelioab()->plugin_version,
		);

		if ( ! empty( $settings['isGA4Integrated'] ) ) {
			$ga4_setting = $plugin_settings->get( 'google_analytics_tracking' );
			if ( ! empty( $ga4_setting['trackingMethod'] ) ) {
				$settings['ga4TrackingMethod'] = $ga4_setting['trackingMethod'];
			}
		}

		/**
		 * Filters main public script settings.
		 *
		 * @param TPublic_Settings $settings public script settings.
		 *
		 * @since 6.0.0
		 */
		return apply_filters( 'nab_main_script_settings', $settings );
	}

	/**
	 * Returns whether Nelio’s public script can be skipped during enqueueing.
	 *
	 * @param list<TExperiment_Summary> $all_exp_summaries Experiment summaries.
	 * @param list<THeatmap_Summary>    $relevant_heats    Heatmap summaries.
	 *
	 * @return bool
	 */
	private function can_skip_script_enqueueing( $all_exp_summaries, $relevant_heats ) {
		$theres_nothing_under_test = empty( $all_exp_summaries ) && empty( $relevant_heats );
		if ( $theres_nothing_under_test ) {
			return true;
		}

		$should_track_heatmaps = ! empty( $relevant_heats );
		if ( $should_track_heatmaps ) {
			return false;
		}

		$settings = Nelio_AB_Testing_Settings::instance();
		if ( $settings->get( 'preload_query_args' ) ) {
			return false;
		}

		$theres_something_to_track = nab_some(
			function ( $exp ) {
				if ( $exp['active'] ) {
					return true;
				}

				return nab_some(
					function ( $goal ) {
						return nab_some(
							function ( $ca ) {
								return ! empty( $ca['active'] );
							},
							$goal['conversionActions']
						);
					},
					$exp['goals']
				);
			},
			$all_exp_summaries
		);
		return ! $theres_something_to_track;
	}

	/**
	 * Returns the API settings to sync events with Nelio’s cloud.
	 *
	 * @return array{mode: 'domain-forwarding'|'rest'|'native', url: string}
	 */
	private function get_api_settings() {
		$settings = Nelio_AB_Testing_Settings::instance();
		$setting  = $settings->get( 'cloud_proxy_setting' );

		$mode          = $setting['mode'];
		$value         = $setting['value'];
		$domain        = $setting['domain'];
		$domain_status = $setting['domainStatus'];

		if ( 'domain-forwarding' === $mode && 'success' === $domain_status ) {
			return array(
				'mode' => 'domain-forwarding',
				'url'  => str_replace( 'api.nelioabtesting.com', $domain, nab_get_api_url( '', 'browser' ) ),
			);
		}

		if ( 'rest' === $mode && preg_match( '/^\/[a-z0-9-]+\/[a-z0-9-]+$/', $value ) ) {
			return array(
				'mode' => 'rest',
				'url'  => get_rest_url( null, $value ),
			);
		}

		return array(
			'mode' => 'native',
			'url'  => nab_get_api_url( '', 'browser' ),
		);
	}

	/**
	 * Returns the name of the cookie that monitors GDPR acceptance (if any).
	 *
	 * This value is either retrieved from the settings or, if it’s not set, it uses the legacy `nab_gdpr_cookie` filter.
	 *
	 * @return array{name: string, value: string}
	 */
	private function get_gdpr_cookie() {

		$settings = Nelio_AB_Testing_Settings::instance();
		$cookie   = $settings->get( 'gdpr_cookie_setting' );
		if ( ! empty( $cookie['name'] ) ) {
			return $cookie;
		}

		/**
		 * Filters the name of the cookie that monitors GDPR acceptance.
		 *
		 * Note: the value of this filter will be overwritten, when set, by the plugin setting
		 * “GDPR Cookie Name.”
		 *
		 * According to EU regulations and, in particular, the GDPR, visitors should be able to
		 * decide whether they want to be tracked by your website or not. If you need to comply
		 * to the GDPR, you can use this setting to specify the name of the cookie that must
		 * exist for tracking that visitor.
		 *
		 * By default, this setting is set to `false`, which means that all users will be tracked
		 * regardless of any other cookies.
		 *
		 * @param string|false $gdpr_cookie the name of the cookie that should exist if GDPR has
		 *                                    been accepted and, therefore, tracking is allowed.
		 *                                    Default: `false`.
		 *
		 * @since 5.0.0
		 *
		 * @deprecated Deprecated since January 2026.
		 */
		$name           = apply_filters( 'nab_gdpr_cookie', false );
		$name           = empty( $name ) ? '' : trim( $name );
		$cookie['name'] = $name;
		return $cookie;
	}

	/**
	 * Returns a list of summarized running heatmaps.
	 *
	 * @return list<THeatmap_Summary>
	 */
	private function get_relevant_heatmap_summaries() {
		$runtime  = Nelio_AB_Testing_Runtime::instance();
		$heatmaps = $runtime->get_relevant_running_heatmaps();
		return array_map(
			function ( $heatmap ) {
				return array(
					'id'            => $heatmap->get_id(),
					'participation' => $heatmap->get_participation_conditions(),
				);
			},
			$heatmaps
		);
	}

	/**
	 * Whether the plugin should track click events with an optimized xpath structured.
	 *
	 * @return bool
	 */
	private function should_track_clicks_with_optimized_xpath() {
		/**
		 * Filters whether the plugin should track click events with an optimized xpath structured.
		 *
		 * If set to `true`, the tracked xpath element IDs and, therefore, it’s smaller
		 * and a little bit faster to process.
		 *
		 * If your theme (or one of your plugins) generates random IDs for the HTML
		 * elements included in your pages, disable this feature. Otherwise, heatmaps
		 * may not work properly.
		 *
		 * @param bool $optimized_xpath Default: `true`.
		 *
		 * @since 5.0.0
		 */
		return true === apply_filters( 'nab_should_track_clicks_with_optimized_xpath', true );
	}

	/**
	 * Returns throttle intervals for woocommerce and other global tests.
	 *
	 * @return array{global: int, woocommerce: int}
	 */
	private function get_throttle_settings() {

		/**
		 * Filters the throttle interval to trigger page view events on global tests.
		 *
		 * Global tests include headline, template, theme, widget, menu, and CSS tests.
		 *
		 * @param int $wait Minutes to wait between consecutive page view events. Value must be between 0 and 10. Default: 0.
		 *
		 * @since 5.4.4
		 */
		$global = apply_filters( 'nab_global_page_view_throttle', 0 );

		/**
		 * Filters the throttle interval to trigger page view events on WooCommerce tests.
		 *
		 * WooCommerce tests include product and bulk sale tests.
		 *
		 * @param int $wait Minutes to wait between consecutive page view events. Value must be between 0 and 10. Default: 5.
		 *
		 * @since 5.4.4
		 */
		$woocommerce = apply_filters( 'nab_woocommerce_page_view_throttle', 5 );

		return array(
			'global'      => absint( $global ),
			'woocommerce' => absint( $woocommerce ),
		);
	}

	/**
	 * Whether the plugin should track JS events with `navigator.sendBeacon` or not.
	 *
	 * @return bool
	 */
	private function use_control_url() {
		/**
		 * Filters whether the plugin should rewrite the alternative URL after its content has been loaded and replace it with the control URL.
		 *
		 * @param bool $enabled whether to rewrite the alternative URL with the control’s. Default: `false`.
		 *
		 * @since 8.1.2
		 */
		return apply_filters( 'nab_use_control_url_in_multi_url_alternative', false );
	}

	/**
	 * Whether the plugin should track JS events with `navigator.sendBeacon` or not.
	 *
	 * @return bool
	 */
	private function use_send_beacon() {
		/**
		 * Filters whether the plugin should track JS events with `navigator.sendBeacon` or not.
		 *
		 * In general, `navigator.sendBeacon` is faster and more reliable, and
		 * therefore it's the preferrer option for tracking JS events. However,
		 * some browsers and/or ad and track blockers may block them.
		 *
		 * @param bool $enabled whether to use `navigator.sendBeacon` or not. Default: `true`.
		 *
		 * @since 5.2.2
		 */
		return apply_filters( 'nab_use_send_beacon_tracking', true );
	}

	/**
	 * Returns a list of summarized running experiments.
	 *
	 * @return list<TExperiment_Summary>
	 */
	private function get_running_experiment_summaries() {

		$runtime     = Nelio_AB_Testing_Runtime::instance();
		$active_exps = $runtime->get_relevant_running_experiments();
		$active_exps = wp_list_pluck( $active_exps, 'ID' );

		$experiments = array_map(
			function ( $exp ) use ( &$active_exps ) {
				$active = in_array( $exp->get_id(), $active_exps, true );
				return $exp->summarize( $active );
			},
			nab_get_running_experiments()
		);

		return $experiments;
	}

	/**
	 * Returns the list of alternative URLs in the current request.
	 *
	 * @return list<string>
	 */
	private function get_alternative_urls() {
		$permalink = get_permalink();
		$urls      = is_string( $permalink ) && is_singular() ? array( $permalink ) : array();

		/**
		 * Filters the list of alternative URLs in the current request.
		 *
		 * @param list<string> $urls List of alternative Urls. Default: if `is_singular` then `[ get_permalink() ]` else `[]`.
		 *
		 * @since 7.1.0
		 */
		return apply_filters( 'nab_alternative_urls', $urls );
	}

	/**
	 * Whether the plugin should only trigger one conversion per goal per regular page view or not.
	 *
	 * @return bool
	 */
	private function get_single_conversion_per_view() {
		/**
		 * Filters whether the plugin should only trigger one conversion per goal per regular page view or not.
		 *
		 * @param bool $single whether the plugin should only trigger one conversion per goal per regular page view or not. Default: `true`.
		 *
		 * @since 8.1.0
		 */
		return apply_filters( 'nab_trigger_single_conversion_per_page_view', true );
	}

	/**
	 * Returns cookie testing setting.
	 *
	 * @return int|false
	 */
	private function get_cookie_testing() {
		$nab = sanitize_text_field( wp_unslash( $_COOKIE['nabAlternative'] ?? '' ) );
		$nab = ! empty( $nab ) ? $nab : false;
		$nab = false === $nab ? false : absint( $nab );
		return 'cookie' === nab_get_variant_loading_strategy() ? $nab : false;
	}
}
