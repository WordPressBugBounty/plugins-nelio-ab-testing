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
 * Returns the experiment whose ID is the given ID.
 *
 * @param Nelio_AB_Testing_Experiment_Results|WP_Post|number $experiment The experiment or its ID.
 *
 * @return Nelio_AB_Testing_Experiment|WP_Error The experiment with the given
 *               ID or a WP_Error.
 *
 * @since 5.0.0
 */
function nab_get_experiment( $experiment ) {
	static $cache = array();

	$experiment_id = is_numeric( $experiment ) ? $experiment : $experiment->ID;
	if ( ! isset( $cache[ $experiment_id ] ) ) {
		$cache[ $experiment_id ] = Nelio_AB_Testing_Experiment::get_experiment( $experiment_id );
	}//end if
	return $cache[ $experiment_id ];
}//end nab_get_experiment()

/**
 * Returns the experiment results for the experiment whose ID is the given ID.
 *
 * @param Nelio_AB_Testing_Experiment_Results|WP_Post|number $experiment The experiment or its ID.
 *
 * @return Nelio_AB_Testing_Experiment_Results|WP_Error The results for the experiment or WP_Error.
 *
 * @since 5.0.0
 */
function nab_get_experiment_results( $experiment ) {
	static $cache = array();

	$experiment_id = is_numeric( $experiment ) ? $experiment : $experiment->ID;
	if ( ! isset( $cache[ $experiment_id ] ) ) {
		$cache[ $experiment_id ] = Nelio_AB_Testing_Experiment_Results::get_experiment_results( $experiment );
	}//end if

	return $cache[ $experiment_id ];
}//end nab_get_experiment_results()

/**
 * Returns whether the experiment whose ID is the given ID has public results enabled.
 *
 * @param integer $experiment_id The ID of the experiment.
 *
 * @return boolean|WP_Error whether the experiment whose ID is the given ID has
 *                          public results enabled or a WP_Error.
 *
 * @since 7.1.1
 */
function nab_is_experiment_result_public( $experiment_id ) {
	$exp = nab_get_experiment( $experiment_id );
	return ! is_wp_error( $exp ) && ! empty( get_post_meta( $experiment_id, '_nab_is_result_public', true ) );
}//end nab_is_experiment_result_public()

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
	return Nelio_AB_Testing_Experiment::create_experiment( $experiment_type );
}//end nab_create_experiment()

/**
 * Returns the list of ids of running split testing experiments.
 *
 * @return array the list of ids of running split testing experiments.
 *
 * @since 5.0.0
 */
function nab_get_all_experiment_ids() {

	global $wpdb;
	return array_map(
		'abs',
		$wpdb->get_col( // phpcs:ignore
			$wpdb->prepare(
				"SELECT ID FROM $wpdb->posts p
					WHERE p.post_type = %s",
				'nab_experiment'
			)
		)
	);
}//end nab_get_all_experiment_ids()

/**
 * Returns a list of IDs with the corresponding running split testing experiments.
 *
 * @return array a list of IDs with the corresponding running split testing experiments.
 *
 * @since 5.0.0
 */
function nab_get_running_experiments() {

	$helper = Nelio_AB_Testing_Experiment_Helper::instance();
	return $helper->get_running_experiments();
}//end nab_get_running_experiments()

/**
 * Returns the list of ids of running split testing experiments.
 *
 * @return array the list of ids of running split testing experiments.
 *
 * @since 5.0.0
 */
function nab_get_running_experiment_ids() {

	global $wpdb;
	return array_map(
		'abs',
		$wpdb->get_col( // phpcs:ignore
			$wpdb->prepare(
				"SELECT ID FROM $wpdb->posts p, $wpdb->postmeta m
					WHERE
						p.post_type = %s AND p.post_status = %s AND
						p.ID = m.post_id AND
						m.meta_key = %s AND m.meta_value != %s",
				'nab_experiment',
				'nab_running',
				'_nab_experiment_type',
				'nab/heatmap'
			)
		)
	);
}//end nab_get_running_experiment_ids()

/**
 * Returns the list of running nab/heatmap experiments.
 *
 * @return array the list of running nab/heatmap experiments.
 *
 * @since 5.0.0
 */
function nab_get_running_heatmaps() {

	$helper = Nelio_AB_Testing_Experiment_Helper::instance();
	return $helper->get_running_heatmaps();
}//end nab_get_running_heatmaps()

/**
 * Returns a list of IDs corresponding to running heatmaps.
 *
 * @return array a list of IDs corresponding to running heatmaps.
 *
 * @since 5.0.0
 */
function nab_get_running_heatmap_ids() {

	global $wpdb;
	return array_map(
		'abs',
		$wpdb->get_col( // phpcs:ignore
			$wpdb->prepare(
				"SELECT ID FROM $wpdb->posts p, $wpdb->postmeta m
					WHERE
						p.post_type = %s AND p.post_status = %s AND
						p.ID = m.post_id AND
						m.meta_key = %s AND m.meta_value = %s",
				'nab_experiment',
				'nab_running',
				'_nab_experiment_type',
				'nab/heatmap'
			)
		)
	);
}//end nab_get_running_heatmap_ids()

/**
 * Returns whether there are running experiments (split tests and heatmaps).
 *
 * @return boolean true if there are running experiments, false otherwise.
 *
 * @since 5.0.0
 */
function nab_are_there_experiments_running() {

	global $wpdb;

	$running_exps = $wpdb->get_var( // phpcs:ignore
		$wpdb->prepare(
			"SELECT COUNT(*) FROM $wpdb->posts WHERE post_type = %s AND post_status = %s",
			'nab_experiment',
			'nab_running'
		)
	);

	return $running_exps > 0;
}//end nab_are_there_experiments_running()

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
	}//end if

	// phpcs:ignore
	if ( isset( $_COOKIE['nabAlternative'] ) && 'none' === $_COOKIE['nabAlternative'] ) {
		return true;
	}//end if

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
}//end nab_is_split_testing_disabled()

/**
 * Returns whether this site is a staging site (based on its URL) or not.
 *
 * If it is, it’ll either return `environment-type` or `url` depending on the reason why it's considered a staging site.
 *
 * @return string|false Whether this site is a staging site or not.
 *
 * @since 5.0.0
 */
function nab_is_staging() {
	if ( 'staging' === wp_get_environment_type() ) {
		return 'environment-type';
	}//end if

	/**
	 * List of URLs (or keywords) used to identify a staging site.
	 *
	 * If `nab_home_url` matches one of the given values, the current site will
	 * be considered as a staging site.
	 *
	 * @param array $urls list of staging URLs (or fragments). Default: `[ 'staging' ]`.
	 *
	 * @since 5.0.0
	 */
	$staging_urls = apply_filters( 'nab_staging_urls', array( 'staging' ) );
	foreach ( $staging_urls as $staging_url ) {
		if ( strpos( nab_home_url(), $staging_url ) !== false ) {
			return 'url';
		}//end if
	}//end foreach

	return false;
}//end nab_is_staging()

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
}//end nab_are_subscription_controls_disabled()

/**
 * This function returns the timezone/UTC offset used in WordPress.
 *
 * @return string the meta ID, false otherwise.
 *
 * @since 5.0.0
 */
function nab_get_timezone() {

	$timezone_string = get_option( 'timezone_string', '' );
	if ( ! empty( $timezone_string ) ) {

		if ( 'UTC' === $timezone_string ) {
			return '+00:00';
		} else {
			return $timezone_string;
		}//end if
	}//end if

	$utc_offset = get_option( 'gmt_offset', 0 );

	if ( $utc_offset < 0 ) {
		$utc_offset_no_dec = '' . absint( $utc_offset );
		$result            = sprintf( '-%02d', absint( $utc_offset_no_dec ) );
	} else {
		$utc_offset_no_dec = '' . absint( $utc_offset );
		$result            = sprintf( '+%02d', absint( $utc_offset_no_dec ) );
	}//end if

	if ( $utc_offset === $utc_offset_no_dec ) {
		$result .= ':00';
	} else {
		$result .= ':30';
	}//end if

	return $result;
}//end nab_get_timezone()

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
	}//end if
	$asset = include nelioab()->plugin_path . "/assets/dist/js/$file_name.asset.php";
	return $asset['version'];
}//end nab_get_script_version()

/**
 * Registers a script loading the dependencies automatically.
 *
 * @param string     $handle    the script handle name.
 * @param string     $file_name the JS name of a script in $plugin_path/assets/dist/js/. Don't include the extension or the path.
 * @param array|bool $args      (optional) An array of additional script loading strategies.
 *                              Otherwise, it may be a boolean in which case it determines whether the script is printed in the footer. Default: `false`.
 *
 * @since 5.0.0
 */
function nab_register_script_with_auto_deps( $handle, $file_name, $args = false ) {

	$asset = array(
		'dependencies' => array(),
		'version'      => nelioab()->plugin_version,
	);

	$path = nelioab()->plugin_path . "/assets/dist/js/$file_name.asset.php";
	if ( file_exists( $path ) ) {
		// phpcs:ignore
		$asset = include $path;
	}//end if

	// NOTE. Add regenerator-runtime to our components package to make sure AsyncPaginate works.
	if ( is_wp_version_compatible( '5.8' ) && 'nab-components' === $handle ) {
		$asset['dependencies'] = array_merge( $asset['dependencies'], array( 'regenerator-runtime' ) );
	}//end if

	if ( is_wp_version_compatible( '6.3' ) ) {
		wp_register_script(
			$handle,
			nelioab()->plugin_url . "/assets/dist/js/$file_name.js",
			$asset['dependencies'],
			$asset['version'],
			$args
		);
	} else {
		wp_register_script(
			$handle,
			nelioab()->plugin_url . "/assets/dist/js/$file_name.js",
			$asset['dependencies'],
			$asset['version'],
			is_array( $args ) ? nab_array_get( $args, 'in_footer', false ) : $args
		);
	}//end if

	if ( in_array( 'wp-i18n', $asset['dependencies'], true ) ) {
		wp_set_script_translations( $handle, 'nelio-ab-testing' );
	}//end if
}//end nab_register_script_with_auto_deps()

/**
 * Enqueues a script loading the dependencies automatically.
 *
 * @param string     $handle    the script handle name.
 * @param string     $file_name the JS name of a script in $plugin_path/assets/dist/js/. Don't include the extension or the path.
 * @param array|bool $args      (optional) An array of additional script loading strategies.
 *                              Otherwise, it may be a boolean in which case it determines whether the script is printed in the footer. Default: `false`.
 *
 * @since 5.0.0
 */
function nab_enqueue_script_with_auto_deps( $handle, $file_name, $args = false ) {

	nab_register_script_with_auto_deps( $handle, $file_name, $args );
	wp_enqueue_script( $handle );
}//end nab_enqueue_script_with_auto_deps()

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
	$lang = ! empty( $lang ) ? $lang : 'en_US';

	// Convert into a two-char string.
	if ( strpos( $lang, '_' ) > 0 ) {
		$lang = substr( $lang, 0, strpos( $lang, '_' ) );
	}//end if

	return $lang;
}//end nab_get_language()

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
	if ( ! empty( $path ) ) {
		$path = '/' . $path;
	}//end if

	/**
	 * Filters the home URL.
	 *
	 * @param string $url  Home URL using the given path.
	 * @param string $path Path relative to the home URL.
	 *
	 * @since 5.0.16
	 */
	return apply_filters( 'nab_home_url', home_url( $path ), $path );
}//end nab_home_url()

/**
 * Gets script extra attributes.
 *
 * @return array List of attribute pairs (key,value) to insert in a script tag.
 *
 * @since 5.5.5
 */
function nab_get_extra_script_attributes() {
	/**
	 * Filters the attributes that should be added to a <script> tag.
	 *
	 * @param array $attributes an array where keys and values are the attribute names and values.
	 *
	 * @since 5.0.22
	 */
	return apply_filters( 'nab_add_extra_script_attributes', array() );
}//end nab_get_extra_script_attributes()

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
}//end nab_uuid()

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
		return wpcom_vip_url_to_postid( $url );
	}//end if

	// phpcs:ignore
	return url_to_postid( $url );
}//end nab_url_to_postid()

/**
 * Logs something on the screen if request contains “nablog”.
 *
 * @param any     $log what to log.
 * @param boolean $pre whether to wrap log in `<pre>` or not (i.e. HTML comment). Default: `false`.
 *
 * @since 5.3.4
 */
function nablog( $log, $pre = false ) {
	// phpcs:disable
	if ( ! isset( $_GET['nablog'] ) ) {
		return;
	}//end if
	echo $pre ? '<pre>' : "\n<!-- [NABLOG]\n";
	print_r( $log );
	echo $pre ? '</pre>' : "\n-->\n";
	// phpcs:enable
}//end nablog()

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
	}//end if

	$run = function () {
		$id = get_queried_object_id();
		if ( $id ) {
			return $id;
		}//end if

		$id = absint( get_query_var( 'page_id' ) );
		if ( $id ) {
			return $id;
		}//end if

		$id = absint( get_query_var( 'p' ) );
		if ( $id ) {
			return $id;
		}//end if

		$name = get_query_var( 'name' );
		$type = get_query_var( 'post_type' );
		if ( empty( $type ) ) {
			global $wp_query;
			if ( $wp_query->is_attachment ) {
				$type = 'attachment';
			} elseif ( $wp_query->is_page ) {
				$type = 'page';
			} else {
				$type = 'post';
			}//end if
		}//end if

		if ( ! empty( $type ) && ! empty( $name ) ) {
			if ( function_exists( 'wpcom_vip_get_page_by_path' ) ) {
				$post = wpcom_vip_get_page_by_path( $name, OBJECT, $type );
			} else {
				// phpcs:ignore
				$post = get_page_by_path( $name, OBJECT, $type );
			}//end if
			if ( ! empty( $post ) ) {
				return $post->ID;
			}//end if
		}//end if

		global $wpdb;
		if ( ! empty( $type ) && ! empty( $name ) ) {
			$key = "nab/{$type}/$name";
			$id  = wp_cache_get( $key );
			if ( $id ) {
				return $id;
			}//end if

			$id = absint(
				// phpcs:ignore
				$wpdb->get_var(
					$wpdb->prepare(
						"SELECT ID FROM $wpdb->posts p WHERE p.post_type = %s AND p.post_name = %s",
						$type,
						$name
					)
				)
			);
			wp_cache_set( $key, $id );

			if ( $id ) {
				return $id;
			}//end if
		}//end if

		return 0;
	};

	/**
	 * Filters the queried object ID.
	 *
	 * @param number $object_id ID of the queried object.
	 *
	 * @since 6.0.0
	 */
	return apply_filters( 'nab_get_queried_object_id', $run() );
}//end nab_get_queried_object_id()

/**
 * Returns a function whose return value is the given constant.
 *
 * @param any $value the constant the generated function will return.
 *
 * @return function a function whose return value is the given constant.
 *
 * @since 6.0.0
 */
function nab_return_constant( $value ) {
	return function () use ( &$value ) {
		return $value;
	};
}//end nab_return_constant()

/**
 * Prints loading overlay style tag.
 *
 * @since 6.0.0
 */
function nab_print_loading_overlay() {
	if ( 'cookie' === nab_get_variant_loading_strategy() ) {
		$runtime     = Nelio_AB_Testing_Runtime::instance();
		$experiments = $runtime->get_relevant_running_experiments();

		$has_inline_experiments = array_reduce(
			$experiments,
			fn( $r, $e ) => $r || false !== $e->get_inline_settings()
		);

		$might_require_redirection = array_reduce(
			$experiments,
			fn( $r, $e ) => $r || false !== $e->has_multi_url_alternative()
		);

		$is_overlay_needed = $has_inline_experiments || $might_require_redirection;
		if ( ! $is_overlay_needed ) {
			return;
		}//end if
	}//end if

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
	 * @since 7.0.0
	 */
	$color = apply_filters( 'nab_alternative_loading_overlay_timeout', '#fff' );

	if ( empty( $time ) ) {
		return;
	}//end if

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
		z-index: 9999999999 !important;
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
}//end nab_print_loading_overlay()

/**
 * Creates a permission callback function that check if the current user has the provided capability.
 *
 * @param string $capability expected capability.
 *
 * @return function permission callback function to use in REST API.
 *
 * @since 6.0.1
 */
function nab_capability_checker( $capability ) {
	return function () use ( $capability ) {
		return current_user_can( $capability );
	};
}//end nab_capability_checker()

/**
 * Creates a predicate function that returns the opposite of the given predicate.
 *
 * @param callable $predicate a boolean function that takes a single argument.
 *
 * @return callable a boolean function that returns the opposite of the given predicate.
 *
 * @since 6.0.4
 */
function nab_not( $predicate ) {
	return function ( $item ) use ( &$predicate ) {
		return ! call_user_func( $predicate, $item );
	};
}//end nab_not()

/**
 * Returns a dictionary of “experiment ID” ⇒ “variant index saw by the visitor.”
 *
 * This value is either extracted from a field named “nab_experiments_with_page_view” in the request
 * (which has been probably added to a form by our public.js script) or, if that’s not set, it will
 * try to recreate its value from the available cookies.
 *
 * @param WP_REST_Request $request Optional request object.
 *
 * @return array a dictionary of experiment ID and variant index saw by the visitor.
 *
 * @since 6.0.4
 */
function nab_get_experiments_with_page_view_from_request( $request = null ) {
	/**
	 * Short-circuits get experiments with page view from request.
	 *
	 * @param null|array A dictionary of experiment IDs and variant seen. Default: `null`.
	 *
	 * @since 7.3.0
	 */
	$result = apply_filters( 'nab_pre_get_experiments_with_page_view_from_request', null );
	if ( null !== $result ) {
		return $result;
	}//end if

	if ( isset( $_REQUEST['nab_experiments_with_page_view'] ) ) { // phpcs:ignore
		$input = sanitize_text_field( wp_unslash( $_REQUEST['nab_experiments_with_page_view'] ) ); // phpcs:ignore
		$sep   = strpos( $input, ';' ) ? ';' : ',';
		return array_reduce(
			explode( $sep, $input ),
			function ( $result, $item ) {
				$item = explode( ':', $item );
				if ( 2 === count( $item ) && absint( $item[0] ) ) {
					$result[ absint( $item[0] ) ] = absint( $item[1] );
				}//end if
				return $result;
			},
			array()
		);
	}//end if

	if ( isset( $_COOKIE['nabAlternative'] ) && isset( $_COOKIE['nabExperimentsWithPageViews'] ) ) { // phpcs:ignore
		$alt  = sanitize_text_field( wp_unslash( $_COOKIE['nabAlternative'] ) ); // phpcs:ignore
		$alt  = preg_match( '/^[0-9][0-9]$/', $alt ) ? absint( $alt ) : -1;
		$eids = sanitize_text_field( wp_unslash( $_COOKIE['nabExperimentsWithPageViews'] ) ); // phpcs:ignore
		$eids = json_decode( $eids, ARRAY_A );
		$eids = empty( $eids ) ? array() : $eids;
		$eids = array_keys( $eids );
		$exps = array_map( 'nab_get_experiment', $eids );
		$exps = array_filter( $exps, nab_not( 'is_wp_error' ) );
		$exps = array_filter( $exps, fn( $e ) => 'nab/heatmap' !== $e->get_type() );
		if ( $alt >= 0 && ! empty( $exps ) ) {
			$eids = wp_list_pluck( $exps, 'ID' );
			$alts = array_map(
				function ( $exp ) use ( $alt ) {
					return $alt % count( $exp->get_alternatives() );
				},
				$exps
			);
			return array_combine( $eids, $alts );
		}//end if
	}//end if

	if ( isset( $request ) && ! empty( $request->get_header( 'cookie' ) ) && false !== strpos( $request->get_header( 'cookie' ), 'nabAlternative' ) && false !== strpos( $request->get_header( 'cookie' ), 'nabExperimentsWithPageViews' ) ) { // phpcs:ignore
		$cookie_values = $request->get_header( 'cookie' );

		// Extract 'nabAlternative'.
		preg_match( '/nabAlternative=([^;]*)/', $cookie_values, $match );
		$alt_value = $match[1];

		// Extract 'nabExperimentsWithPageViews'.
		preg_match( '/nabExperimentsWithPageViews=([^;]*)/', $cookie_values, $match );
		$experiments = $match[1];

		$alt  = sanitize_text_field( wp_unslash( $alt_value ) ); // phpcs:ignore
		$alt  = preg_match( '/^[0-9][0-9]*$/', $alt ) ? absint( $alt ) : -1;
		$eids = sanitize_text_field( urldecode( $experiments ) ); // phpcs:ignore
		$eids = json_decode( $eids, ARRAY_A );
		$eids = empty( $eids ) ? array() : $eids;
		$eids = array_keys( $eids );
		$eids = array_merge( $eids, array( 999999 ) );
		$exps = array_map( 'nab_get_experiment', $eids );
		$exps = array_filter( $exps, nab_not( 'is_wp_error' ) );
		$exps = array_filter( $exps, fn( $e ) => 'nab/heatmap' !== $e->get_type() );
		if ( $alt >= 0 && ! empty( $exps ) ) {
			$eids = wp_list_pluck( $exps, 'ID' );
			$alts = array_map(
				function ( $exp ) use ( $alt ) {
					return $alt % count( $exp->get_alternatives() );
				},
				$exps
			);
			return array_combine( $eids, $alts );
		}//end if
	}//end if

	return array();
}//end nab_get_experiments_with_page_view_from_request()

/**
 * Returns a dictionary of “experiment ID” ⇒ “array of segments.”
 *
 * This value is either extracted from a field named “nab_segments” in the request
 * (which has been probably added to a form by our public.js script) or, if that’s not set, it will
 * try to recreate its value from the available cookies.
 *
 * @param WP_REST_Request $request Optional request object.
 *
 * @return array a dictionary of experiment IDs to array of segments.
 *
 * @since 6.4.1
 */
function nab_get_segments_from_request( $request = null ) {
	/**
	 * Short-circuits get segments from request.
	 *
	 * @param null|array A dictionary of experiment IDs and a list of segment. Default: `null`.
	 *
	 * @since 7.3.0
	 */
	$result = apply_filters( 'nab_pre_get_segments_from_request', null );
	if ( null !== $result ) {
		return $result;
	}//end if

	if ( isset( $_REQUEST['nab_segments'] ) ) { // phpcs:ignore
		return array_reduce(
			explode( ';', sanitize_text_field( wp_unslash( $_REQUEST['nab_segments'] ) ) ), // phpcs:ignore
			function ( $result, $item ) {
				$item = explode( ':', $item );
				if ( 2 !== count( $item ) || ! absint( $item[0] ) ) {
					return $result;
				}//end if

				$exp_id   = absint( $item[0] );
				$segments = explode( ',', $item[1] );
				$segments = array_map( 'absint', $segments );

				$result[ $exp_id ] = $segments;
				return $result;
			},
			array()
		);
	}//end if

	if ( isset( $_COOKIE['nabSegmentation'] ) ) { // phpcs:ignore
		$segmentation = sanitize_text_field( wp_unslash( $_COOKIE['nabSegmentation'] ) ); // phpcs:ignore
		$segmentation = json_decode( $segmentation, ARRAY_A );
		$segmentation = empty( $segmentation ) ? array() : $segmentation;
		$segments     = empty( $segmentation['activeSegments'] ) ? array() : $segmentation['activeSegments'];
		if ( ! empty( $segments ) ) {
			return $segments;
		}//end if
	}//end if

	if ( isset( $request ) && ! empty( $request->get_header( 'cookie' ) ) && false !== strpos( $request->get_header( 'cookie' ), 'nabSegmentation' ) ) { // phpcs:ignore
		$cookie_values = $request->get_header( 'cookie' );

		// Extract 'nabSegmentation'.
		preg_match( '/nabSegmentation=([^;]*)/', $cookie_values, $match );
		$segmentation = $match[1];
		$segmentation = sanitize_text_field( urldecode( $segmentation ) );
		$segmentation = json_decode( $segmentation, ARRAY_A );
		$segmentation = empty( $segmentation ) ? array() : $segmentation;
		$segments     = empty( $segmentation['activeSegments'] ) ? array() : $segmentation['activeSegments'];
		if ( ! empty( $segments ) ) {
			return $segments;
		}//end if
	}//end if

	return array();
}//end nab_get_segments_from_request()

/**
 * Returns a dictionary of “experiment ID” ⇒ “UUID use to track a unique view.”
 *
 * This value is either extracted from a field named “nab_unique_views” in the request
 * (which has been probably added to a form by our public.js script) or, if that’s not set, it will
 * try to recreate its value from the available cookies.
 *
 * @param WP_REST_Request $request Optional request object.
 *
 * @return array a dictionary of experiment IDs to UUIDs.
 *
 * @since 6.0.4
 */
function nab_get_unique_views_from_request( $request = null ) {
	/**
	 * Short-circuits get unique views from request.
	 *
	 * @param null|array A dictionary of experiment IDs and a unique identifier. Default: `null`.
	 *
	 * @since 7.3.0
	 */
	$result = apply_filters( 'nab_pre_get_unique_views_from_request', null );
	if ( null !== $result ) {
		return $result;
	}//end if

	if ( isset( $_REQUEST['nab_unique_views'] ) ) { // phpcs:ignore
		$input = sanitize_text_field( wp_unslash( $_REQUEST['nab_unique_views'] ) ); // phpcs:ignore
		$sep   = strpos( $input, ';' ) ? ';' : ',';
		return array_reduce(
			explode( $sep, $input ),
			function ( $result, $item ) {
				$item = explode( ':', $item );
				if ( 2 === count( $item ) && absint( $item[0] ) && wp_is_uuid( $item[1] ) ) {
					$result[ absint( $item[0] ) ] = $item[1];
				}//end if
				return $result;
			},
			array()
		);
	}//end if

	if ( isset( $_COOKIE['nabUniqueViews'] ) ) { // phpcs:ignore
		$uids = sanitize_text_field( wp_unslash( $_COOKIE['nabUniqueViews'] ) ); // phpcs:ignore
		$uids = json_decode( $uids, ARRAY_A );
		$uids = empty( $uids ) ? array() : $uids;
		$uids = array_filter( $uids, 'wp_is_uuid' );
		if ( ! empty( $uids ) ) {
			return $uids;
		}//end if
	}//end if

	if ( isset( $request ) && ! empty( $request->get_header( 'cookie' ) ) && false !== strpos( $request->get_header( 'cookie' ), 'nabUniqueViews' ) ) { // phpcs:ignore
		$cookie_values = $request->get_header( 'cookie' );

		// Extract 'nabUniqueViews'.
		preg_match( '/nabUniqueViews=([^;]*)/', $cookie_values, $match );
		$uids = $match[1];
		$uids = sanitize_text_field( urldecode( $uids ) );
		$uids = json_decode( $uids, ARRAY_A );
		$uids = empty( $uids ) ? array() : $uids;
		$uids = array_filter( $uids, 'wp_is_uuid' );
		if ( ! empty( $uids ) ) {
			return $uids;
		}//end if
	}//end if

	return array();
}//end nab_get_unique_views_from_request()

/**
 * Prints raw HTML without escaping.
 *
 * @param string $html HTML string.
 *
 * @since 6.1.0
 */
function nab_print_html( $html ) {
	$use_raw_html = function ( $safe, $raw ) {
		return $raw;
	};
	add_filter( 'esc_html', $use_raw_html, 10, 2 );
	echo esc_html( $html );
	remove_filter( 'esc_html', $use_raw_html, 10, 2 );
}//end nab_print_html()

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
}//end nab_is_plugin_installed()

/**
 * Helper function to wrap regular WordPress filters into our own.
 *
 * @param string   $hook_name Name of the hook.
 * @param callable $callback  Callback to execute.
 * @param number   $priority  Priority to enqueue the callback.
 * @param number   $args      Number of arguments accepted by the callback.
 *
 * @since 6.5.0
 */
function add_nab_filter( $hook_name, $callback, $priority = 10, $args = 1 ) {
	/**
	 * Wraps regular WordPress filters into our own.
	 *
	 * @since 6.5.0
	 */
	do_action( "add_nab_filter_for_{$hook_name}", $callback, $priority, $args );
}//end add_nab_filter()

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
	}//end if
	$minifier = new \MatthiasMullie\Minify\JS();
	$minifier->add( $code );
	return trim( $minifier->minify() );
}//end nab_minify_js()

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
	}//end if
	$minifier = new \MatthiasMullie\Minify\CSS();
	$minifier->add( $code );
	return trim( $minifier->minify() );
}//end nab_minify_css()

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
}//end nab_ignore_trailing_slash_in_alternative_loading()

function nab_array_merge( array $a, array $b ): array {
	$a = array_combine( array_map( fn( $k ) => " $k ", array_keys( $a ) ), $a );
	$b = array_combine( array_map( fn( $k ) => " $k ", array_keys( $b ) ), $b );
	$c = array_merge( $a, $b );
	return array_combine( array_map( fn( $k ) => absint( trim( $k ) ), array_keys( $c ) ), $c );
}//end nab_array_merge()
