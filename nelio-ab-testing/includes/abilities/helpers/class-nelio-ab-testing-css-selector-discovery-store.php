<?php
/**
 * Stores temporary CSS selector-discovery requests.
 *
 * @package Nelio_AB_Testing
 */

defined( 'ABSPATH' ) || exit;

/** Stores and retrieves short-lived CSS selector-discovery data. */
class Nelio_AB_Testing_Css_Selector_Discovery_Store {

	/**
	 * Lifetime of a selector-discovery request.
	 *
	 * @var int
	 */
	private const LIFETIME = 30 * MINUTE_IN_SECONDS;

	/**
	 * Creates or retrieves a selector-discovery request for a URL.
	 *
	 * @param string $url URL to inspect.
	 *
	 * @return TCss_Selector_Discovery_Result|WP_Error Discovery request or an error.
	 */
	public static function create_or_get( $url ) {
		$url = self::normalize_url( $url );
		if ( is_wp_error( $url ) ) {
			return $url;
		}

		$existing = self::get( $url );
		if ( is_array( $existing ) ) {
			return $existing;
		}

		$record = array(
			'token'       => wp_generate_uuid4(),
			'siteId'      => get_current_blog_id(),
			'url'         => $url,
			'status'      => 'pending',
			'selectors'   => array(),
			'createdAt'   => time(),
			'completedAt' => 0,
		);
		self::save( $record );

		return $record;
	}

	/**
	 * Retrieves a selector-discovery request.
	 *
	 * @param string $url URL associated with the request.
	 *
	 * @return TCss_Selector_Discovery_Result|false Discovery request, or false if it does not exist.
	 */
	public static function get( $url ) {
		$url = self::normalize_url( $url );
		if ( is_wp_error( $url ) ) {
			return false;
		}
		$record = get_transient( self::record_key( $url ) );
		if ( ! is_array( $record ) ) {
			return false;
		}

		$record_token = $record['token'] ?? null;
		$site_id      = $record['siteId'] ?? null;
		$url          = $record['url'] ?? null;
		$status       = $record['status'] ?? null;
		$selectors    = $record['selectors'] ?? null;
		$created_at   = $record['createdAt'] ?? null;
		$completed_at = $record['completedAt'] ?? null;
		if (
			! is_string( $record_token ) ||
			! is_int( $site_id ) ||
			! is_string( $url ) ||
			! is_string( $status ) ||
			! in_array( $status, array( 'pending', 'ready' ), true ) ||
			! is_array( $selectors ) ||
			! is_int( $created_at ) ||
			! is_int( $completed_at )
		) {
			return false;
		}

		$clean_selectors = array();
		foreach ( $selectors as $selector => $details ) {
			if (
				! is_string( $selector ) ||
				! is_array( $details ) ||
				! is_string( $details['outerHTML'] ?? null ) ||
				! is_array( $details['allowedTags'] ?? null )
			) {
				return false;
			}
			$allowed_tags = array();
			foreach ( $details['allowedTags'] as $tag ) {
				if ( ! is_string( $tag ) ) {
					return false; // @codeCoverageIgnore
				}
				$allowed_tags[] = $tag;
			}
			$clean_selectors[ $selector ] = array(
				'outerHTML'   => $details['outerHTML'],
				'allowedTags' => $allowed_tags,
			);
		}

		return array(
			'token'       => $record_token,
			'siteId'      => $site_id,
			'url'         => $url,
			'status'      => $status,
			'selectors'   => $clean_selectors,
			'createdAt'   => $created_at,
			'completedAt' => $completed_at,
		);
	}

	/**
	 * Marks a request as ready and stores the selectors generated in the browser.
	 *
	 * @param string                                                         $token Request token.
	 * @param string                                                         $url Inspected URL.
	 * @param array<string,array{outerHTML:string,allowedTags:list<string>}> $selectors Selector metadata map.
	 *
	 * @return TCss_Selector_Discovery_Result|WP_Error Updated request or an error.
	 */
	public static function complete( $token, $url, $selectors ) {
		$normalized_url = self::normalize_url( $url );
		if ( is_wp_error( $normalized_url ) ) {
			return $normalized_url;
		}
		$record = self::get( $normalized_url );
		if ( ! is_array( $record ) || $token !== $record['token'] ) {
			return new WP_Error( 'invalid-selector-discovery', 'This selector-discovery request does not exist or has expired.', array( 'status' => 404 ) );
		}

		if ( $normalized_url !== $record['url'] ) {
			return new WP_Error( 'invalid-selector-discovery', 'The selector-discovery response does not belong to the requested URL.', array( 'status' => 400 ) );
		}

		$clean_selectors = array();
		foreach ( $selectors as $selector => $details ) {
			if ( '' === trim( $selector ) ) {
				return new WP_Error( 'invalid-selector-discovery', 'The selector-discovery response contains an invalid selector.', array( 'status' => 400 ) );
			}
			$clean_selectors[ $selector ] = $details;
		}

		$record['status']      = 'ready';
		$record['selectors']   = $clean_selectors;
		$record['completedAt'] = time();
		self::save( $record );
		return $record;
	}

	/**
	 * Saves a discovery record.
	 *
	 * @param TCss_Selector_Discovery_Result $record Discovery record.
	 *
	 * @return void
	 */
	private static function save( $record ) {
		set_transient( self::record_key( $record['url'] ), $record, self::LIFETIME );
	}

	/**
	 * Normalizes and validates a URL on this site.
	 *
	 * @param string $url URL to validate.
	 *
	 * @return string|WP_Error Normalized URL or an error.
	 */
	private static function normalize_url( $url ) {
		if ( '' === $url ) {
			return new WP_Error( 'invalid-selector-discovery-url', 'Selector discovery requires a URL.' );
		}
		$url = esc_url_raw( $url );
		if ( '' === $url || '' === wp_validate_redirect( $url, '' ) ) {
			return new WP_Error( 'invalid-selector-discovery-url', 'Selector discovery only supports URLs from this site.' );
		}
		return remove_query_arg( 'nab-css-selector-discovery', $url );
	}

	/**
	 * Returns the transient key for a URL.
	 *
	 * @param string $url URL.
	 *
	 * @return string Transient key.
	 */
	private static function record_key( $url ) {
		return 'nab_css_selector_request_' . md5( get_current_blog_id() . ':' . $url );
	}
}
