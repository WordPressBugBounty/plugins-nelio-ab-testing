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

	protected static $instance;

	public static function instance() {

		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
		}//end if

		return self::$instance;
	}//end instance()

	public function init() {
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_script' ), 1 );
		add_filter( 'script_loader_tag', array( $this, 'add_extra_script_attributes' ), 10, 2 );
		add_filter( 'wp_inline_script_attributes', array( $this, 'add_extra_inline_script_attributes' ), 10, 2 );
	}//end init()

	public function add_extra_script_attributes( $tag, $handle ) {
		if ( 'nelio-ab-testing-main' !== $handle ) {
			return $tag;
		}//end if
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
		$tag   = empty( $attrs ) ? $tag : str_replace( '></script>', " {$attrs}></script>", $tag ); // phpcs:ignore
		return $tag;
	}//end add_extra_script_attributes()

	public function add_extra_inline_script_attributes( $attrs ) {
		if ( ! isset( $attrs['id'] ) || 'nelio-ab-testing-main-js-before' !== $attrs['id'] ) {
			return $attrs;
		}//end if
		$attrs = array_merge( $attrs, nab_get_extra_script_attributes() );
		return $attrs;
	}//end add_extra_inline_script_attributes()

	public function enqueue_script() {
		if ( nab_is_split_testing_disabled() ) {
			return;
		}//end if

		$experiments = $this->get_running_experiment_summaries();
		$heatmaps    = $this->get_relevant_heatmap_summaries();
		if ( $this->can_skip_script_enqueueing( $experiments, $heatmaps ) ) {
			return;
		}//end if

		$alt_loader      = Nelio_AB_Testing_Alternative_Loader::instance();
		$plugin_settings = Nelio_AB_Testing_Settings::instance();

		$runtime  = Nelio_AB_Testing_Runtime::instance();
		$settings = array(
			'alternativeUrls'     => $this->get_alternative_urls(),
			'api'                 => $this->get_api_settings(),
			// phpcs:ignore
			'cookieTesting'       => 'cookie' === nab_get_variant_loading_strategy() ? nab_array_get( $_COOKIE, 'nabAlternative', false ) : false,
			'excludeBots'         => $plugin_settings->get( 'exclude_bots' ),
			'experiments'         => $experiments,
			'gdprCookie'          => $this->get_gdpr_cookie(),
			'heatmaps'            => $heatmaps,
			'hideQueryArgs'       => $plugin_settings->get( 'hide_query_args' ),
			'ignoreTrailingSlash' => nab_ignore_trailing_slash_in_alternative_loading(),
			'isStagingSite'       => ! empty( nab_is_staging() ),
			'isTestedPostRequest' => $runtime->is_tested_post_request(),
			'maxCombinations'     => nab_max_combinations(),
			'numOfAlternatives'   => $alt_loader->get_number_of_alternatives(),
			'optimizeXPath'       => $this->should_track_clicks_with_optimized_xpath(),
			'participationChance' => $plugin_settings->get( 'percentage_of_tested_visitors' ),
			'postId'              => is_singular() ? get_the_ID() : false,
			'preloadQueryArgUrls' => nab_get_preload_query_arg_urls(),
			'referrerParam'       => $this->get_referrer_param(),
			'segmentMatching'     => $plugin_settings->get( 'match_all_segments' ) ? 'all' : 'some',
			'site'                => nab_get_site_id(),
			'throttle'            => $this->get_throttle_settings(),
			'timezone'            => nab_get_timezone(),
			'useSendBeacon'       => $this->use_send_beacon(),
			'version'             => nelioab()->plugin_version,
		);

		/**
		 * Filters main public script settings.
		 *
		 * @param object $settings public script settings.
		 *
		 * @since 6.0.0
		 */
		$settings = apply_filters( 'nab_main_script_settings', $settings );

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
			require_once ABSPATH . 'wp-admin/includes/class-wp-filesystem-base.php';
			require_once ABSPATH . 'wp-admin/includes/class-wp-filesystem-direct.php';
			$filesystem = new \WP_Filesystem_Direct( true );

			wp_register_script( 'nelio-ab-testing-main', '' ); // phpcs:ignore
			wp_enqueue_script( 'nelio-ab-testing-main' );
			$script = nelioab()->plugin_path . '/assets/dist/js/public.js';
			$script = file_exists( $script ) ? $filesystem->get_contents( $script ) : '';
			wp_add_inline_script( 'nelio-ab-testing-main', $script );
		}//end if

		wp_add_inline_script(
			'nelio-ab-testing-main',
			sprintf( 'window.nabSettings=%s;', wp_json_encode( $settings ) ),
			'before'
		);
	}//end enqueue_script()

	private function can_skip_script_enqueueing( $all_exp_summaries, $relevant_heats ) {
		$theres_nothing_under_test = empty( $all_exp_summaries ) && empty( $relevant_heats );
		if ( $theres_nothing_under_test ) {
			return true;
		}//end if

		$should_track_heatmaps = ! empty( $relevant_heats );
		if ( $should_track_heatmaps ) {
			return false;
		}//end if

		$settings = Nelio_AB_Testing_Settings::instance();
		if ( $settings->get( 'preload_query_args' ) ) {
			return false;
		}//end if

		$theres_something_to_track = nab_some(
			function ( $exp ) {
				if ( $exp['active'] ) {
					return true;
				}//end if

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
	}//end can_skip_script_enqueueing()

	private function get_api_settings() {
		$settings      = Nelio_AB_Testing_Settings::instance();
		$setting       = $settings->get( 'cloud_proxy_setting' );
		$mode          = $setting['mode'];
		$value         = $setting['value'];
		$domain        = $setting['domain'];
		$domain_status = $setting['domainStatus'];

		if ( 'domain-forwarding' === $mode && 'success' === $domain_status ) {
			return array(
				'mode' => 'domain-forwarding',
				'url'  => str_replace( 'api.nelioabtesting.com', $domain, nab_get_api_url( '', 'browser' ) ),
			);
		}//end if

		if ( 'rest' === $mode && preg_match( '/^\/[a-z0-9-]+\/[a-z0-9-]+$/', $value ) ) {
			return array(
				'mode' => 'rest',
				'url'  => get_rest_url( null, $value ),
			);
		}//end if

		return array(
			'mode' => 'native',
			'url'  => nab_get_api_url( '', 'browser' ),
		);
	}//end get_api_settings()

	private function get_gdpr_cookie() {

		$settings = Nelio_AB_Testing_Settings::instance();
		$cookie   = $settings->get( 'gdpr_cookie_setting' );
		$cookie   = is_array( $cookie ) ? $cookie : array(
			'name'  => '',
			'value' => '',
		);
		if ( ! empty( $cookie['name'] ) ) {
			return $cookie;
		}//end if

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
		 * @param boolean|string $gdpr_cookie the name of the cookie that should exist if GDPR has
		 *                                    been accepted and, therefore, tracking is allowed.
		 *                                    Default: `false`.
		 *
		 * @since 5.0.0
		 */
		$name           = apply_filters( 'nab_gdpr_cookie', false );
		$name           = empty( $name ) ? '' : trim( $name );
		$cookie['name'] = $name;
		return $cookie;
	}//end get_gdpr_cookie()

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
	}//end get_relevant_heatmap_summaries()

	private function should_track_clicks_with_optimized_xpath() {
		/**
		 * Whether the plugin should track click events with an optimized xpath structured.
		 *
		 * If set to `true`, the tracked xpath element IDs and, therefore, it’s smaller
		 * and a little bit faster to process.
		 *
		 * If your theme (or one of your plugins) generates random IDs for the HTML
		 * elements included in your pages, disable this feature. Otherwise, heatmaps
		 * may not work properly.
		 *
		 * @param boolean $optimized_xpath Default: `true`.
		 *
		 * @since 5.0.0
		 */
		return true === apply_filters( 'nab_should_track_clicks_with_optimized_xpath', true );
	}//end should_track_clicks_with_optimized_xpath()

	private function get_throttle_settings() {

		/**
		 * Filters the throttle interval to trigger page view events on global tests.
		 *
		 * Global tests include headline, template, theme, widget, menu, and CSS tests.
		 *
		 * @param number $wait Minutes to wait between consecutive page view events. Value must be between 0 and 10. Default: 0.
		 *
		 * @since 5.4.4
		 */
		$global = apply_filters( 'nab_global_page_view_throttle', 0 );

		/**
		 * Filters the throttle interval to trigger page view events on WooCommerce tests.
		 *
		 * WooCommerce tests include product and bulk sale tests.
		 *
		 * @param number $wait Minutes to wait between consecutive page view events. Value must be between 0 and 10. Default: 5.
		 *
		 * @since 5.4.4
		 */
		$woocommerce = apply_filters( 'nab_woocommerce_page_view_throttle', 5 );

		return array(
			'global'      => $global,
			'woocommerce' => $woocommerce,
		);
	}//end get_throttle_settings()

	private function use_send_beacon() {
		/**
		 * Filters whether the plugin should track JS events with `navigator.sendBeacon` or not.
		 *
		 * In general, `navigator.sendBeacon` is faster and more reliable, and
		 * therefore it's the preferrer option for tracking JS events. However,
		 * some browsers and/or ad and track blockers may block them.
		 *
		 * @param boolean $enabled whether to use `navigator.sendBeacon` or not. Default: `true`.
		 *
		 * @since 5.2.2
		 */
		return apply_filters( 'nab_use_send_beacon_tracking', true );
	}//end use_send_beacon()

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
	}//end get_running_experiment_summaries()

	private function get_referrer_param() {
		/**
		 * Filters the query arg that retains the original referrer after performing a JavaScript redirection.
		 *
		 * Nelio A/B Testing loads the appropriate variant a visitor is supposed to see by
		 * performing a JavaScript redirection. As a result of this redirection, the original
		 * referrer is lost and the website owner can’t no longer know where the traffic is
		 * coming from.
		 *
		 * Use this filter to specify the query arg the plugin should use to retain this
		 * information. By default, it uses Google Analytics default arg: `utm_referrer`.
		 *
		 * If you don’t want to keep that information at all, set the value to `false`.
		 *
		 * @param boolean|string $param_name the name of the query arg that should keep track of the referrer or false otherwise.
		 *
		 * @since 5.0.0
		 */
		return apply_filters( 'nab_referrer_param', 'utm_referrer' );
	}//end get_referrer_param()

	private function get_alternative_urls() {
		$urls = is_singular() ? array( get_permalink() ) : array();
		/**
		 * Filters the list of alternative URLs in the current request.
		 *
		 * @param array $urls List of alternative Urls. Default: if `is_singular` then `[ get_permalink() ]` else `[]`.
		 *
		 * @since 7.1.0
		 */
		return apply_filters( 'nab_alternative_urls', $urls );
	}//end get_alternative_urls()
}//end class
