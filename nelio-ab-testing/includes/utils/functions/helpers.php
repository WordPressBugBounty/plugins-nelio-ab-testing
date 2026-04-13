<?php
/**
 * Nelio A/B Testing helper functions to ease development.
 *
 * @package    Nelio_AB_Testing
 * @subpackage Nelio_AB_Testing/includes/utils/helpers
 * @since      5.0.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * Dies using `wp_die()` with no default screen.
 *
 * @return void
 *
 * @since 8.3.0
 */
function nab_die() {
	/**
	 * Runs before dying.
	 *
	 * @since 8.3.0
	 */
	do_action( 'nab_before_nab_die' );
	die(); // @codeCoverageIgnore
}

/**
 * Returns the experiment whose ID is the given ID.
 *
 * @param Nelio_AB_Testing_Experiment|WP_Post|int $experiment The experiment or its ID.
 *
 * @return Nelio_AB_Testing_Experiment|WP_Error The experiment with the given
 *               ID or a WP_Error.
 *
 * @since 5.0.0
 */
function nab_get_experiment( $experiment ) {
	return nelioab()->manager()->get_experiment( $experiment );
}

/**
 * Returns the experiment results for the experiment whose ID is the given ID.
 *
 * @param Nelio_AB_Testing_Experiment|WP_Post|int $experiment The experiment or its ID.
 *
 * @return Nelio_AB_Testing_Experiment_Results|WP_Error The results for the experiment or WP_Error.
 *
 * @since 5.0.0
 */
function nab_get_experiment_results( $experiment ) {
	/** @var array<int, WP_Error|Nelio_AB_Testing_Experiment_Results> */
	static $cache  = array();
	$eid           = is_numeric( $experiment ) ? absint( $experiment ) : absint( $experiment->ID );
	$result        = isset( $cache[ $eid ] ) ? $cache[ $eid ] : Nelio_AB_Testing_Experiment_Results::get_experiment_results( $eid );
	$cache[ $eid ] = $result;
	return $result;
}

/**
 * Creates a new experiment with the given type.
 *
 * @param string $experiment_type The type of the experiment.
 *
 * @return Nelio_AB_Testing_Experiment|WP_Error The experiment with the given
 *               type or a WP_Error.
 *
 * @since 5.0.0
 */
function nab_create_experiment( $experiment_type ) {
	return nelioab()->manager()->create_experiment( $experiment_type );
}

/**
 * Returns a list of IDs with the corresponding running split testing experiments.
 *
 * @return list<Nelio_AB_Testing_Experiment> a list of IDs with the corresponding running split testing experiments.
 *
 * @since 5.0.0
 */
function nab_get_running_experiments() {
	$helper = nelioab()->manager();
	return $helper->get_running_experiments();
}

/**
 * Returns the list of running nab/heatmap experiments.
 *
 * @return list<Nelio_AB_Testing_Heatmap>
 *
 * @since 5.0.0
 */
function nab_get_running_heatmaps() {
	$helper = nelioab()->manager();
	return $helper->get_running_heatmaps();
}

/**
 * Returns whether there are running experiments (split tests and heatmaps).
 *
 * @return boolean true if there are running experiments, false otherwise.
 *
 * @since 5.0.0
 */
function nab_are_there_experiments_running() {
	/** @var wpdb */
	global $wpdb;
	// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
	$running_exps = $wpdb->get_var(
		$wpdb->prepare(
			'SELECT COUNT(*) FROM %i WHERE post_type = %s AND post_status = %s',
			$wpdb->posts,
			'nab_experiment',
			'nab_running'
		)
	);
	return $running_exps > 0;
}

/**
 * Returns whether the current request should be split tested or not.
 *
 * If it’s split tested, hooks for loading alternative content and tracking events will be set. Otherwise, the public facet of Nelio A/B Testing will be disabled.
 *
 * @return boolean whether the current request should be split tested or not.
 *
 * @since 5.0.0
 */
function nab_is_split_testing_disabled() {

	if ( ! is_ssl() ) {
		return true;
	}

	if ( isset( $_COOKIE['nabAlternative'] ) && 'none' === sanitize_text_field( wp_unslash( $_COOKIE['nabAlternative'] ) ) ) {
		return true;
	}

	/**
	 * Whether the current request should be excluded from split testing or not.
	 *
	 * If it’s split tested, hooks for loading alternative content and tracking events will be set.
	 * Otherwise, the public facet of Nelio A/B Testing will be disabled.
	 *
	 * **Notice.** Our plugin uses JavaScript to load alternative content. Be careful when limiting tests
	 * in PHP, as it’s possible that your cache or CDN ends up caching these limitations and, as a result,
	 * none of your visitors are tested.
	 *
	 * @param boolean $disabled whether the current request should be excluded from split testing or not. Default: `false`.
	 *
	 * @since 5.0.0
	 */
	return apply_filters( 'nab_disable_split_testing', false );
}

/**
 * Returns whether this site is a staging site (based on its URL) or not.
 *
 * If it is, it’ll either return `environment-type` or `url` depending on the reason why it's considered a staging site.
 *
 * @return 'environment-type'|'url'|false Whether this site is a staging site or not.
 *
 * @since 5.0.0
 */
function nab_is_staging() {
	/**
	 * Filters whether WP environment’s type should be ignored to determine if we’re on a staging site.
	 *
	 * If not ignored and WP’s environment type is anything other than `production`, Nelio
	 * will consider the site as a staging site.
	 *
	 * @param bool $ignored Is WP’s environment type ignore. Default: `false`.
	 *
	 * @since 8.1.5
	 */
	if ( ! apply_filters( 'nab_staging_ignore_wp_environment_type', false ) ) {
		// @codeCoverageIgnoreStart
		if ( 'production' !== wp_get_environment_type() ) {
			return 'environment-type';
		}
		// @codeCoverageIgnoreEnd
	}

	/**
	 * List of URLs (or keywords) used to identify a staging site.
	 *
	 * If `nab_home_url` matches one of the given values, the current site will
	 * be considered as a staging site.
	 *
	 * @param list<string> $urls list of staging URLs (or fragments). Default: `[ 'staging' ]`.
	 *
	 * @since 5.0.0
	 */
	$staging_urls = apply_filters( 'nab_staging_urls', array( 'staging' ) );
	foreach ( $staging_urls as $staging_url ) {
		if ( strpos( nab_home_url(), $staging_url ) !== false ) {
			return 'url';
		}
	}

	return false;
}

/**
 * Returns whether the subscription controls are disabled or not.
 *
 * @return boolean Whether he subscription controls are disabled or not.
 *
 * @since 6.3.0
 */
function nab_are_subscription_controls_disabled() {

	/**
	 * Filters whether the subscription controls are disabled or not.
	 *
	 * If subscription controls are disabled, the UI does not show the
	 * subscription-related actions in the site
	 *
	 * @param boolean $value Whether the subscription controls are disabled or not.
	 *
	 * @since 6.3.0
	 */
	return apply_filters( 'nab_are_subscription_controls_disabled', false );
}

/**
 * This function returns the timezone/UTC offset used in WordPress.
 *
 * @return string the meta ID, false otherwise.
 *
 * @since 5.0.0
 */
function nab_get_timezone() {

	/** @var string */
	$timezone_string = get_option( 'timezone_string', '' );
	if ( ! empty( $timezone_string ) ) {
		return 'UTC' === $timezone_string ? '+00:00' : $timezone_string;
	}

	$utc_offset        = get_option( 'gmt_offset', 0 );
	$utc_offset        = is_numeric( $utc_offset ) ? $utc_offset : 0;
	$utc_offset        = '' . $utc_offset;
	$utc_offset_no_dec = '' . intval( $utc_offset );

	if ( $utc_offset < 0 ) {
		$result = sprintf( '-%02d', absint( $utc_offset ) );
	} else {
		$result = sprintf( '+%02d', absint( $utc_offset ) );
	}

	if ( $utc_offset === $utc_offset_no_dec ) {
		$result .= ':00';
	} else {
		$result .= ':30';
	}

	return $result;
}

/**
 * Returns the script version if available. If it isn't, it defaults to the plugin's version.
 *
 * @param string $file_name the JS name of a script in $plugin_path/assets/dist/js/. Don't include the extension or the path.
 *
 * @return string the version of the given script or the plugin's version if the former wasn't be found.
 *
 * @since 6.1.0
 */
function nab_get_script_version( $file_name ) {
	if ( ! file_exists( nelioab()->plugin_path . "/assets/dist/js/$file_name.asset.php" ) ) {
		return nelioab()->plugin_version;
	}
	$asset = include nelioab()->plugin_path . "/assets/dist/js/$file_name.asset.php";
	$asset = is_array( $asset ) && isset( $asset['version'] ) ? $asset['version'] : '';
	return ! empty( $asset ) && is_string( $asset ) ? $asset : nelioab()->plugin_version;
}

/**
 * Registers a script loading the dependencies automatically.
 *
 * @param string                                          $handle    the script handle name.
 * @param string                                          $file_name the JS name of a script in $plugin_path/assets/dist/js/. Don't include the extension or the path.
 * @param array{strategy?: string, in_footer?: bool}|bool $args      (optional) An array of additional script loading strategies.
 *                                                        Otherwise, it may be a boolean in which case it determines whether the script is printed in the footer. Default: `false`.
 *
 * @return void
 *
 * @since 5.0.0
 */
function nab_register_script_with_auto_deps( $handle, $file_name, $args = false ) {

	$path = nelioab()->plugin_path . "/assets/dist/js/$file_name.asset.php";
	if ( file_exists( $path ) ) {
		$asset = include $path;
	}

	$asset = ! empty( $asset ) && is_array( $asset ) ? $asset : array();
	/** @var array{dependencies:list<string>, version:string} */
	$asset = wp_parse_args(
		$asset,
		array(
			'dependencies' => array(),
			'version'      => nelioab()->plugin_version,
		)
	);

	// HACK. Add regenerator-runtime to our components package to make sure AsyncPaginate works.
	if ( 'nab-components' === $handle ) {
		$asset['dependencies'] = array_merge( $asset['dependencies'], array( 'regenerator-runtime' ) );
	}

	wp_register_script(
		$handle,
		nelioab()->plugin_url . "/assets/dist/js/$file_name.js",
		$asset['dependencies'],
		$asset['version'],
		$args
	);

	if ( in_array( 'wp-i18n', $asset['dependencies'], true ) ) {
		wp_set_script_translations( $handle, 'nelio-ab-testing' );
	}
}

/**
 * Enqueues a script loading the dependencies automatically.
 *
 * @param string                                          $handle    the script handle name.
 * @param string                                          $file_name the JS name of a script in $plugin_path/assets/dist/js/. Don't include the extension or the path.
 * @param array{strategy?: string, in_footer?: bool}|bool $args      (optional) An array of additional script loading strategies.
 *                                                                   Otherwise, it may be a boolean in which case it determines whether the script is printed in the footer. Default: `false`.
 *
 * @return void
 *
 * @since 5.0.0
 */
function nab_enqueue_script_with_auto_deps( $handle, $file_name, $args = false ) {

	nab_register_script_with_auto_deps( $handle, $file_name, $args );
	wp_enqueue_script( $handle );
}

/**
 * This function returns the two-letter locale used in WordPress.
 *
 * @return string the two-letter locale used in WordPress.
 *
 * @since 5.0.0
 */
function nab_get_language() {

	// Language of the blog.
	$lang = get_option( 'WPLANG' );
	$lang = ! empty( $lang ) && is_string( $lang ) ? $lang : 'en_US';

	// Convert into a two-char string.
	if ( strpos( $lang, '_' ) > 0 ) {
		$lang = substr( $lang, 0, strpos( $lang, '_' ) );
	}

	return $lang;
}

/**
 * Returns the home URL.
 *
 * @param string $path Optional. Path relative to the home URL.
 *
 * @return string Returns the home URL.
 *
 * @since 5.0.16
 */
function nab_home_url( $path = '' ) {

	$path = preg_replace( '/^\/*/', '', $path );
	$path = ! empty( $path ) ? $path : '';
	if ( ! empty( $path ) ) {
		$path = "/{$path}";
	}

	/**
	 * Filters the home URL.
	 *
	 * @param string $url  Home URL using the given path.
	 * @param string $path Path relative to the home URL.
	 *
	 * @since 5.0.16
	 */
	return apply_filters( 'nab_home_url', home_url( $path ), $path );
}

/**
 * Gets script extra attributes.
 *
 * @return array<string,string> List of attribute pairs (key,value) to insert in a script tag.
 *
 * @since 5.5.5
 */
function nab_get_extra_script_attributes() {
	/**
	 * Filters the attributes that should be added to a <script> tag.
	 *
	 * @param array<string,string> $attributes an array where keys and values are the attribute names and values.
	 *
	 * @since 5.0.22
	 */
	return apply_filters( 'nab_add_extra_script_attributes', array() );
}

/**
 * Generates a unique ID.
 *
 * @return string unique ID.
 *
 * @since 5.0.0
 */
function nab_uuid() {

	$data    = random_bytes( 16 );
	$data[6] = chr( ord( $data[6] ) & 0x0f | 0x40 );
	$data[8] = chr( ord( $data[8] ) & 0x3f | 0x80 );

	return vsprintf( '%s%s-%s-%s-%s-%s%s%s', str_split( bin2hex( $data ), 4 ) );
}

/**
 * Returns the post ID of a given URL.
 *
 * @param string $url a URL.
 *
 * @return int post ID or 0 on failure
 *
 * @since 5.2.6
 */
function nab_url_to_postid( $url ) {
	if ( function_exists( 'wpcom_vip_url_to_postid' ) ) {
		/** @disregard P1010 — Function exists */
		return absint( wpcom_vip_url_to_postid( $url ) ); // @codeCoverageIgnore
	}

	// phpcs:ignore WordPressVIPMinimum.Functions.RestrictedFunctions.url_to_postid_url_to_postid
	return url_to_postid( $url );
}

/**
 * Logs something on the screen if request contains “nablog”.
 *
 * @param mixed   $log what to log.
 * @param boolean $pre whether to wrap log in `<pre>` or not (i.e. HTML comment). Default: `false`.
 *
 * @return void
 *
 * @since 5.3.4
 */
function nab_log( $log, $pre = false ) {
	// phpcs:ignore WordPress.Security.NonceVerification.Recommended
	if ( ! isset( $_GET['nablog'] ) ) {
		return;
	}
	echo $pre ? '<pre>' : "\n<!-- [NABLOG]\n";
	// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_print_r
	print_r( $log );
	echo $pre ? '</pre>' : "\n-->\n";
}

/**
 * Returns the queried object ID.
 *
 * @return int queried object ID.
 *
 * @since 5.2.9
 */
function nab_get_queried_object_id() {
	global $wp_query;
	if ( empty( $wp_query ) ) {
		return 0;
	}

	$run = function () {
		$id = get_queried_object_id();
		if ( $id ) {
			return $id;
		}

		$id = absint( get_query_var( 'page_id' ) );
		if ( $id ) {
			return $id;
		}

		$id = absint( get_query_var( 'p' ) );
		if ( $id ) {
			return $id;
		}

		$name = get_query_var( 'name' );
		$name = is_array( $name ) ? array_values( $name )[0] : $name;
		$name = is_string( $name ) ? $name : '';
		$type = get_query_var( 'post_type' );
		$type = is_array( $type ) ? array_values( $type )[0] : $type;
		$type = is_string( $type ) ? $type : '';
		if ( empty( $type ) ) {
			/** @var WP_Query */
			global $wp_query;
			if ( $wp_query->is_attachment ) {
				$type = 'attachment'; // @codeCoverageIgnore
			} elseif ( $wp_query->is_page ) {
				$type = 'page';       // @codeCoverageIgnore
			} else {
				$type = 'post';
			}
		}

		if ( ! empty( $name ) ) {
			if ( function_exists( 'wpcom_vip_get_page_by_path' ) ) {
				/** @disregard P1010 — Function exists */
				/** @var WP_Post|null */
				$post = wpcom_vip_get_page_by_path( $name, OBJECT, $type ); // @codeCoverageIgnore
			} else {
				$post = get_page_by_path( $name, OBJECT, $type );
			}
			if ( ! empty( $post ) ) {
				return $post->ID;
			}
		}

		/** @var wpdb */
		global $wpdb;
		if ( ! empty( $name ) ) {
			$key = "nab/{$type}/$name";
			$id  = absint( wp_cache_get( $key ) );
			if ( $id ) {
				return $id; // @codeCoverageIgnore
			}

			$id = absint(
				// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
				$wpdb->get_var(
					$wpdb->prepare(
						'SELECT ID FROM %i p WHERE p.post_type = %s AND p.post_name = %s',
						$wpdb->posts,
						$type,
						$name
					)
				)
			);
			wp_cache_set( $key, $id );

			if ( $id ) {
				return $id;
			}
		}

		if ( ! empty( $name ) ) {
			$key = "nab/unknown-type/$name";
			$id  = absint( wp_cache_get( $key ) );
			if ( $id ) {
				return $id; // @codeCoverageIgnore
			}

			$id = absint(
				// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
				$wpdb->get_var(
					$wpdb->prepare(
						'SELECT ID FROM %i p WHERE p.post_name = %s',
						$wpdb->posts,
						$name
					)
				)
			);
			wp_cache_set( $key, $id );

			if ( $id ) {
				return $id;
			}
		}

		return 0;
	};

	/**
	 * Filters the queried object ID.
	 *
	 * @param int $object_id ID of the queried object.
	 *
	 * @since 6.0.0
	 */
	return apply_filters( 'nab_get_queried_object_id', $run() );
}

/**
 * Prints loading overlay style tag.
 *
 * @return void
 *
 * @since 6.0.0
 */
function nab_print_loading_overlay() {
	/**
	 * Filters the maximum time the alternative loading overlay will be visible.
	 *
	 * @param number $time maximum time in ms the alternative loading overlay will be visible. Default: 3000.
	 *
	 * @since 6.0.0
	 */
	$time = apply_filters( 'nab_alternative_loading_overlay_timeout', 3000 );

	/**
	 * Filters the overlay color.
	 *
	 * @param string $color       Overlay color. Default: `#fff`.
	 *
	 * @since 8.1.0
	 */
	$color = apply_filters( 'nab_alternative_loading_overlay_color', '#fff' );

	if ( empty( $time ) ) {
		return;
	}

	$css = "
	@keyframes nelio-ab-testing-overlay {
		to { width: 0; height: 0; }
	}
	body:not(.nab-done)::before,
	body:not(.nab-done)::after {
		animation: 1ms {$time}ms linear nelio-ab-testing-overlay forwards !important;
		background: {$color} !important;
		display: block !important;
		content: \"\" !important;
		position: fixed !important;
		top: 0 !important;
		left: 0 !important;
		width: 100vw;
		height: 120vh;
		pointer-events: none !important;
		z-index: 999999998 !important;
	}
	html.nab-redirecting body::before,
	html.nab-redirecting body::after {
		animation: none !important;
	}";

	nab_print_html(
		sprintf(
			'<style id="nelio-ab-testing-overlay" type="text/css">%s</style>',
			nab_minify_css( $css )
		)
	);
}

/**
 * Creates a permission callback function that check if the current user has the provided capability.
 *
 * @param string $capability expected capability.
 *
 * @return callable():bool permission callback function to use in REST API.
 *
 * @since 6.0.1
 */
function nab_capability_checker( $capability ) {
	/** @var array<string,callable():bool>|null */
	static $functions;
	if ( empty( $functions[ $capability ] ) ) {
		$functions                = $functions ?? array();
		$functions[ $capability ] = function () use ( $capability ) {
			return current_user_can( $capability );
		};
	}
	return $functions[ $capability ];
}

/**
 * Prints raw HTML without escaping.
 *
 * @param string $html HTML string.
 *
 * @return void
 *
 * @since 6.1.0
 */
function nab_print_html( $html ) {
	$use_raw_html = function ( $safe, $raw ) {
		return $raw;
	};
	add_filter( 'esc_html', $use_raw_html, 10, 2 );
	echo esc_html( $html );
	remove_filter( 'esc_html', $use_raw_html, 10 );
}

/**
 * Determines whether a plugin is installed.
 *
 * @param string $plugin Path to the plugin file relative to the plugins directory.
 *
 * @return boolean whether the plugin is installed.
 *
 * @since 6.4.0
 */
function nab_is_plugin_installed( $plugin ) {
	$plugins = get_plugins();
	return ! empty( $plugins[ $plugin ] );
}

/**
 * Helper function to wrap regular WordPress filters into our own.
 *
 * @param string   $hook_name Name of the hook.
 * @param callable $callback  Callback to execute.
 * @param number   $priority  Priority to enqueue the callback.
 * @param number   $args      Number of arguments accepted by the callback.
 *
 * @return void
 *
 * @since 6.5.0
 */
function nab_add_filter( $hook_name, $callback, $priority = 10, $args = 1 ) {
	/**
	 * Wraps regular WordPress filters into our own.
	 *
	 * @since 6.5.0
	 */
	do_action( "nab_add_filter_for_{$hook_name}", $callback, $priority, $args );
}

/**
 * Minifies the given script.
 *
 * @param string $code the code to minify.
 *
 * @return string minified code.
 *
 * @since 6.5.0
 */
function nab_minify_js( $code ) {
	/**
	 * Filters whether JavaScript code inserted by our plugin should be minified or not.
	 *
	 * @param boolean $minify Whether JS code should be minified. Default: `true`.
	 *
	 * @since 6.5.0
	 */
	if ( ! apply_filters( 'nab_minify_js', true ) ) {
		return $code;
	}
	$minifier = new \MatthiasMullie\Minify\JS();
	$minifier->add( $code );
	return trim( $minifier->minify() );
}

/**
 * Minifies the given style.
 *
 * @param string $code the code to minify.
 *
 * @return string minified code.
 *
 * @since 6.5.0
 */
function nab_minify_css( $code ) {
	/**
	 * Filters whether JavaScript code inserted by our plugin should be minified or not.
	 *
	 * @param boolean $minify Whether CSS code should be minified. Default: `true`.
	 *
	 * @since 6.5.0
	 */
	if ( ! apply_filters( 'nab_minify_css', true ) ) {
		return $code;
	}
	$minifier = new \MatthiasMullie\Minify\CSS();
	$minifier->add( $code );
	return trim( $minifier->minify() );
}

/**
 * Returns whether alternative content loading should ignore the trailing slash in a URL when comparing the current URL and the URL of the alternative the visitor is supposed to see.
 *
 * If it’s set to ignore, `https://example.com/some-page` and `https://example.com/some-page/` will be considered the same page. Otherwise, they’ll be different.
 *
 * @return boolean whether to ignore the trailing slash or not.
 *
 * @since 7.3.1
 */
function nab_ignore_trailing_slash_in_alternative_loading() {
	/**
	 * Filters whether alternative content loading should ignore the trailing slash in a URL when comparing the current URL and the URL of the alternative the visitor is supposed to see.
	 *
	 * If it’s set to ignore, `https://example.com/some-page` and `https://example.com/some-page/` will be considered the same page. Otherwise, they’ll be different.
	 *
	 * @param boolean $ignore_trailing_slash whether to ignore the trailing slash or not.
	 *
	 * @since 5.0.8
	 */
	return apply_filters( 'nab_ignore_trailing_slash_in_alternative_loading', true );
}

/**
 * Returns true if the request is a non-legacy REST API request.
 *
 * Legacy REST requests should still run some extra code for backwards compatibility.
 *
 * @return boolean true if the request is a non-legacy REST API request.
 *
 * @since 7.5.1
 */
function nab_is_rest_api_request() {
	$request_uri = sanitize_url( is_string( $_SERVER['REQUEST_URI'] ?? '' ) ? sanitize_url( wp_unslash( $_SERVER['REQUEST_URI'] ?? '' ) ) : '' );
	$rest_prefix = trailingslashit( rest_get_url_prefix() );

	$is_rest_api_request = false !== strpos( $request_uri, $rest_prefix );

	/**
	 * Whether the request is a non-legacy REST API request.
	 *
	 * @param boolean $is_rest_api_request whether the request is a non-legacy REST API request.
	 *
	 * @since 7.5.1
	 */
	return apply_filters( 'nab_is_rest_api_request', $is_rest_api_request );
}

/**
 * Requires the file from WordPress once.
 *
 * @param string $path Filename relative from ABSPATH.
 *
 * @return void
 *
 * @since 8.1.0
 */
function nab_require_wp_file( $path ) {
	if ( 0 !== strpos( $path, '/' ) ) {
		$path = "/{$path}"; // @codeCoverageIgnore
	}
	require_once untrailingslashit( ABSPATH ) . $path;
}

/**
 * Sets the cookie.
 *
 * Wrapper to enable unit tests.
 *
 * @param string $name       Cookie name.
 * @param string $value      Cookie value.
 * @param int    $expiration Expiration date.
 * @param string $path       Cookie path.
 *
 * @return void
 *
 * @since 8.3.0
 */
function nab_setcookie( $name, $value, $expiration, $path ) {
	/**
	 * Filters whether a cookie should be set or not.
	 *
	 * This filter was added for unit testing our code.
	 *
	 * @param bool   $skip       Whether to skip setting the cookie or not.
	 * @param string $name       Cookie name.
	 * @param string $value      Cookie value.
	 * @param int    $expiration Expiration date.
	 * @param string $path       Cookie path.
	 *
	 * @since 8.3.0
	 */
	$skip = apply_filters( 'nab_skip_setcookie', false, $name, $value, $expiration, $path );
	if ( $skip ) {
		return;
	}

	// phpcs:ignore WordPressVIPMinimum.Functions.RestrictedFunctions.cookies_setcookie
	setcookie( $name, $value, $expiration, $path ); // @codeCoverageIgnore
}

/**
 * Returns `high`.
 *
 * @return 'high'
 *
 * @since 8.3.0
 */
function nab_return_high_priority() {
	return 'high';
}

/**
 * Returns `mid`.
 *
 * @return 'mid'
 *
 * @since 8.3.0
 */
function nab_return_mid_priority() {
	return 'mid';
}

/**
 * Returns `low`.
 *
 * @return 'low'
 *
 * @since 8.3.0
 */
function nab_return_low_priority() {
	return 'low';
}

/**
 * Returns `custom`.
 *
 * @return 'custom'
 *
 * @since 8.3.0
 */
function nab_return_custom_priority() {
	return 'custom';
}

/**
 * Returns `footer`.
 *
 * @return 'footer'
 *
 * @since 8.3.0
 */
function nab_return_footer() {
	return 'footer';
}

/**
 * Returns an inline settings object to load tests as a script in the header.
 *
 * @return array{load:'header',mode:'script'}
 *
 * @since 8.3.0
 */
function nab_return_header_script() {
	return array(
		'load' => 'header',
		'mode' => 'script',
	);
}

/**
 * Converts an empty value to null.
 *
 * @template T
 *
 * @param T $arg Argument.
 *
 * @return T|null
 *
 * @since 8.3.0
 */
function nab_nullify( $arg ) {
	return ! empty( $arg ) ? $arg : null;
}
