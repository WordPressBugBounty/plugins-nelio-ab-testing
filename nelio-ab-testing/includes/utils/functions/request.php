<?php
/**
 * Nelio A/B Testing request functions.
 *
 * @package    Nelio_AB_Testing
 * @subpackage Nelio_AB_Testing/includes/utils/functions
 * @since      8.3.0
 */

defined( 'ABSPATH' ) || exit;

/**
 * Returns the active alternative for the given experiment.
 *
 * @param int $experiment_id The ID of the experiment.
 *
 * @return int
 *
 * @since 8.3.0
 */
function nab_get_alternative_from_request( $experiment_id = 0 ) {
	if ( nab_is_preview() ) {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$eid = absint( $_GET['experiment'] ?? 0 );
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$aid = absint( $_GET['alternative'] ?? 0 );
		return empty( $experiment_id ) || $experiment_id === $eid ? $aid : 0;
	}

	$manager = nelioab()->manager();
	if ( ! $manager->has_running_experiments() ) {
		return 0;
	}

	$runtime     = nelioab()->runtime();
	$alternative = $runtime->get_alternative_from_request();
	if ( empty( $experiment_id ) ) {
		return $alternative;
	}

	$experiment = nab_get_experiment( $experiment_id );
	if ( is_wp_error( $experiment ) ) {
		return $alternative;
	}

	return $alternative % count( $experiment->get_alternatives() );
}

/**
 * Returns a dictionary of “experiment ID” ⇒ “variant index saw by the visitor.”
 *
 * This value is either extracted from a field named “nab_experiments_with_page_view” in the request
 * (which has been probably added to a form by our public.js script) or, if that’s not set, it will
 * try to recreate its value from the available cookies.
 *
 * @param WP_REST_Request<array<string,mixed>> $request Optional request object.
 *
 * @return array<int,int> a dictionary of experiment ID and variant index saw by the visitor.
 *
 * @since 6.0.4
 */
function nab_get_experiments_with_page_view_from_request( $request = null ) {
	/**
	 * Short-circuits get experiments with page view from request.
	 *
	 * @param null|array<int,int> $value A dictionary of experiment IDs and variant seen. Default: `null`.
	 *
	 * @since 7.3.0
	 */
	$result = apply_filters( 'nab_pre_get_experiments_with_page_view_from_request', null );
	if ( null !== $result ) {
		return $result;
	}

	// phpcs:ignore WordPress.Security.NonceVerification.Recommended
	if ( isset( $_REQUEST['nab_experiments_with_page_view'] ) ) {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$input = sanitize_text_field( wp_unslash( $_REQUEST['nab_experiments_with_page_view'] ) );
		$sep   = strpos( $input, ';' ) ? ';' : ',';
		/** @var array<int,int> */
		return array_reduce(
			explode( $sep, $input ),
			function ( $result, $item ) {
				$item = explode( ':', $item );
				if ( 2 === count( $item ) && absint( $item[0] ) ) {
					/** @var array<int,int> $result */
					$result[ absint( $item[0] ) ] = absint( $item[1] );
				}
				return $result;
			},
			array()
		);
	}

	if ( isset( $_COOKIE['nabAlternative'] ) && isset( $_COOKIE['nabExperimentsWithPageViews'] ) ) {
		$alt = sanitize_text_field( wp_unslash( $_COOKIE['nabAlternative'] ) );
		$alt = preg_match( '/^[0-9][0-9]$/', $alt ) ? absint( $alt ) : -1;

		$eids = sanitize_text_field( wp_unslash( $_COOKIE['nabExperimentsWithPageViews'] ) );
		$eids = json_decode( $eids, true );
		$eids = is_array( $eids ) ? $eids : array();
		$eids = array_keys( $eids );
		/** @var list<int> */
		$eids = array_map( 'absint', $eids );

		$exps = array_map( 'nab_get_experiment', $eids );
		/** @var list<Nelio_AB_Testing_Experiment> */
		$exps = array_values( array_filter( $exps, fn( $e ) => ! is_wp_error( $e ) ) );
		$exps = array_values( array_filter( $exps, fn( $e ) => 'nab/heatmap' !== $e->get_type() ) );
		if ( $alt >= 0 && ! empty( $exps ) ) {
			/** @var list<int> */
			$eids = wp_list_pluck( $exps, 'ID' );
			$alts = array_map(
				function ( $exp ) use ( $alt ) {
					return $alt % count( $exp->get_alternatives() );
				},
				$exps
			);
			return array_combine( $eids, $alts );
		}
	}

	if ( isset( $request ) && ! empty( $request->get_header( 'cookie' ) ) && false !== strpos( $request->get_header( 'cookie' ), 'nabAlternative' ) && false !== strpos( $request->get_header( 'cookie' ), 'nabExperimentsWithPageViews' ) ) {
		$cookie_values = $request->get_header( 'cookie' );

		// Extract 'nabAlternative'.
		preg_match( '/nabAlternative=([^;]*)/', $cookie_values, $match );
		$alt_value = $match[1];

		// Extract 'nabExperimentsWithPageViews'.
		preg_match( '/nabExperimentsWithPageViews=([^;]*)/', $cookie_values, $match );
		$experiments = $match[1];

		$alt = sanitize_text_field( wp_unslash( $alt_value ) );
		$alt = preg_match( '/^[0-9][0-9]*$/', $alt ) ? absint( $alt ) : -1;

		$eids = sanitize_text_field( urldecode( $experiments ) );
		$eids = json_decode( $eids, true );
		$eids = is_array( $eids ) ? $eids : array();
		/** @var list<int> */
		$eids = array_keys( $eids );
		$eids = array_merge( $eids, array( 999999 ) );

		$exps = array_map( 'nab_get_experiment', $eids );
		/** @var list<Nelio_AB_Testing_Experiment> */
		$exps = array_values( array_filter( $exps, fn( $e ) => ! is_wp_error( $e ) ) );
		$exps = array_values( array_filter( $exps, fn( $e ) => 'nab/heatmap' !== $e->get_type() ) );
		if ( $alt >= 0 && ! empty( $exps ) ) {
			/** @var list<int> */
			$eids = wp_list_pluck( $exps, 'ID' );
			$alts = array_map(
				function ( $exp ) use ( $alt ) {
					return $alt % count( $exp->get_alternatives() );
				},
				$exps
			);
			return array_combine( $eids, $alts );
		}
	}

	return array();
}

/**
 * Returns a dictionary of “experiment ID” ⇒ “array of segments.”
 *
 * This value is either extracted from a field named “nab_segments” in the request
 * (which has been probably added to a form by our public.js script) or, if that’s not set, it will
 * try to recreate its value from the available cookies.
 *
 * @param WP_REST_Request<array<string,mixed>> $request Optional request object.
 *
 * @return array<int,list<int>> a dictionary of experiment IDs to array of segments.
 *
 * @since 6.4.1
 */
function nab_get_segments_from_request( $request = null ) {
	/**
	 * Short-circuits get segments from request.
	 *
	 * @param null|array<int,list<int>> $value A dictionary of experiment IDs and a list of segment. Default: `null`.
	 *
	 * @since 7.3.0
	 */
	$result = apply_filters( 'nab_pre_get_segments_from_request', null );
	if ( null !== $result ) {
		return $result;
	}

	// phpcs:ignore WordPress.Security.NonceVerification.Recommended
	if ( isset( $_REQUEST['nab_segments'] ) ) {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$input = sanitize_text_field( wp_unslash( $_REQUEST['nab_segments'] ) );
		/** @var array<int,list<int>> */
		return array_reduce(
			explode( ';', $input ),
			/** @var callable( array<int,list<int>>, string ): array<int,string> */
			function ( $result, $item ) {
				$item = explode( ':', $item );
				if ( 2 !== count( $item ) || ! absint( $item[0] ) ) {
					return $result;
				}

				$exp_id   = absint( $item[0] );
				$segments = explode( ',', $item[1] );
				$segments = array_map( 'absint', $segments );

				/** @var array<int,list<int>> $result */
				$result[ $exp_id ] = $segments;
				return $result;
			},
			array()
		);
	}

	if ( isset( $_COOKIE['nabSegmentation'] ) ) {
		$segmentation = sanitize_text_field( wp_unslash( $_COOKIE['nabSegmentation'] ) );
		$segmentation = json_decode( $segmentation, true );
		$segmentation = ! empty( $segmentation ) && is_array( $segmentation ) ? $segmentation : array();
		$segments     = ! empty( $segmentation['activeSegments'] ) && is_array( $segmentation['activeSegments'] ) ? $segmentation['activeSegments'] : array();
		if ( ! empty( $segments ) ) {
			/** @var array<int,list<int>> */
			return $segments;
		}
	}

	if ( isset( $request ) && ! empty( $request->get_header( 'cookie' ) ) && false !== strpos( $request->get_header( 'cookie' ), 'nabSegmentation' ) ) {
		$cookie_values = $request->get_header( 'cookie' );

		// Extract 'nabSegmentation'.
		preg_match( '/nabSegmentation=([^;]*)/', $cookie_values, $match );
		$segmentation = $match[1];
		$segmentation = sanitize_text_field( urldecode( $segmentation ) );
		$segmentation = json_decode( $segmentation, true );
		$segmentation = ! empty( $segmentation ) && is_array( $segmentation ) ? $segmentation : array();
		$segments     = ! empty( $segmentation['activeSegments'] ) && is_array( $segmentation['activeSegments'] ) ? $segmentation['activeSegments'] : array();
		if ( ! empty( $segments ) ) {
			/** @var array<int,list<int>> */
			return $segments;
		}
	}

	return array();
}

/**
 * Returns a dictionary of “experiment ID” ⇒ “UUID use to track a unique view.”
 *
 * This value is either extracted from a field named “nab_unique_views” in the request
 * (which has been probably added to a form by our public.js script) or, if that’s not set, it will
 * try to recreate its value from the available cookies.
 *
 * @param WP_REST_Request<array<string,mixed>> $request Optional request object.
 *
 * @return array<int,string> a dictionary of experiment IDs to UUIDs.
 *
 * @since 6.0.4
 */
function nab_get_unique_views_from_request( $request = null ) {
	/**
	 * Short-circuits get unique views from request.
	 *
	 * @param null|array<int,string> $value A dictionary of experiment IDs and a unique identifier. Default: `null`.
	 *
	 * @since 7.3.0
	 */
	$result = apply_filters( 'nab_pre_get_unique_views_from_request', null );
	if ( null !== $result ) {
		return $result;
	}

	// phpcs:ignore WordPress.Security.NonceVerification.Recommended
	if ( isset( $_REQUEST['nab_unique_views'] ) ) {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$input = sanitize_text_field( wp_unslash( $_REQUEST['nab_unique_views'] ) );
		$sep   = strpos( $input, ';' ) ? ';' : ',';
		/** @var array<int,string> */
		return array_reduce(
			explode( $sep, $input ),
			/** @var callable( array<int,string>, string ): array<int,string> */
			function ( $result, $item ) {
				$item = explode( ':', $item );
				if ( 2 === count( $item ) && absint( $item[0] ) && wp_is_uuid( $item[1] ) ) {
					/** @var array<int,string> $result */
					$result[ absint( $item[0] ) ] = $item[1];
				}
				return $result;
			},
			array()
		);
	}

	// phpcs:ignore WordPress.Security.NonceVerification.Recommended
	if ( isset( $_COOKIE['nabUniqueViews'] ) ) {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$uids = sanitize_text_field( wp_unslash( $_COOKIE['nabUniqueViews'] ) );
		$uids = json_decode( $uids, true );
		$uids = is_array( $uids ) && ! empty( $uids ) ? $uids : array();
		$uids = array_filter( $uids, fn( $id ) => is_string( $id ) && wp_is_uuid( $id ) );
		if ( ! empty( $uids ) ) {
			/** @var array<int,string> */
			return $uids;
		}
	}

	if ( isset( $request ) && ! empty( $request->get_header( 'cookie' ) ) && false !== strpos( $request->get_header( 'cookie' ), 'nabUniqueViews' ) ) {
		$cookie_values = $request->get_header( 'cookie' );

		// Extract 'nabUniqueViews'.
		preg_match( '/nabUniqueViews=([^;]*)/', $cookie_values, $match );
		$uids = $match[1];
		$uids = sanitize_text_field( urldecode( $uids ) );
		$uids = json_decode( $uids, true );
		$uids = is_array( $uids ) && ! empty( $uids ) ? $uids : array();
		$uids = array_filter( $uids, fn( $id ) => is_string( $id ) && wp_is_uuid( $id ) );
		if ( ! empty( $uids ) ) {
			/** @var array<int,string> */
			return $uids;
		}
	}

	return array();
}

/**
 * Returns a client ID of Google Analytics 4.
 *
 * This value is either extracted from a field named “nab_ga4_client_id” in the request
 * (which has been probably added to a form by our public.js script) or, if that’s not set, it will
 * try to recreate its value from the available cookies.
 *
 * @return null|string a client ID of Google Analytics 4.
 *
 * @since 7.5.0
 */
function nab_get_ga4_client_id_from_request() {
	$plugin_settings = \Nelio_AB_Testing_Settings::instance();
	if ( empty( $plugin_settings->get( 'google_analytics_tracking' )['enabled'] ) ) {
		return null;
	}

	/**
	 * Short-circuits get GA4 client ID from request.
	 *
	 * @param null|string $client_id A client ID. Default: `null`.
	 *
	 * @since 7.5.0
	 */
	$result = apply_filters( 'nab_pre_get_ga4_client_id_from_request', null );
	if ( null !== $result ) {
		return $result;
	}

	// phpcs:ignore WordPress.Security.NonceVerification.Recommended
	$ga4_client_id = sanitize_text_field( wp_unslash( $_REQUEST['nab_ga4_client_id'] ?? '' ) );
	if ( ! empty( $ga4_client_id ) ) {
		return $ga4_client_id;
	}

	$ga_cookie = sanitize_text_field( wp_unslash( $_COOKIE['_ga'] ?? '' ) );
	// Match the pattern: GA1.1.1234567890.1700000000.
	if ( preg_match( '/^GA\d\.\d\.(\d+\.\d+)$/', $ga_cookie, $matches ) ) {
		return $matches[1];
	}

	return null;
}

/**
 * Returns the active alternative for the given experiment.
 *
 * @param int $experiment_id The ID of the experiment.
 *
 * @return int
 *
 * @since 7.4.0
 *
 * @deprecated Use `nab_get_alternative_from_request` instead.
 */
// @codeCoverageIgnoreStart
function nab_get_requested_alternative( $experiment_id = 0 ) {
	_deprecated_function( __FUNCTION__, '8.3.0', 'nab_get_alternative_from_request()' );
	return nab_get_alternative_from_request( $experiment_id );
}
// @codeCoverageIgnoreEnd
