<?php
namespace Nelio_AB_Testing\Hooks\Experiment_Scope\Preview;

defined( 'ABSPATH' ) || exit;

/**
 * Tries to retrieve the preview URL from the a given scope.
 *
 * @param list<TScope_Rule> $scope Test scope.
 *
 * @return string|false
 */
function find_preview_url_in_scope( $scope ) {

	if ( ! empty( $scope ) ) {
		$url = find_exact_local_url_in_scope( $scope );
		if ( $url ) {
			return $url;
		}

		$url = find_local_url_in_scope_from_partial_specification( $scope );
		if ( $url ) {
			return $url;
		}
	}

	$url = nab_home_url();
	foreach ( $scope as $rule ) {
		$rule = $rule['attributes'];
		if ( nab_does_rule_apply_to_url( $rule, $url, array() ) ) {
			return $url;
		}
	}

	return false;
}

/**
 * Finds an exact local URL in test scope.
 *
 * @param non-empty-list<TScope_Rule> $scope Test scope.
 *
 * @return string|false
 */
function find_exact_local_url_in_scope( $scope ) {

	if ( 'php-snippet' === $scope['0']['attributes']['type'] ) {
		/** @var non-empty-list<TScope_Rule> */
		$scope = array(
			array(
				'id'         => $scope['0']['id'],
				'attributes' => array(
					'type'  => 'exact',
					'value' => $scope['0']['attributes']['value']['previewUrl'],
				),
			),
		);
	}

	/** @var array<string,string|false> */
	static $full_preview_urls = array();

	$urls = array_map(
		fn ( $candidate ): string => 'exact' === $candidate['attributes']['type'] ? $candidate['attributes']['value'] : '',
		$scope
	);
	$urls = array_values( array_filter( $urls ) );

	foreach ( $urls as $url ) {

		if ( isset( $full_preview_urls[ $url ] ) ) {
			if ( $full_preview_urls[ $url ] ) {
				return $full_preview_urls[ $url ];
			} else {
				continue;
			}
		}

		if ( ! is_local_url( $url ) ) {
			$full_preview_urls[ $url ] = false;
			continue;
		}

		$clean_url = esc_url( $url );
		if ( ! is_url_valid( $clean_url ) ) {
			$full_preview_urls[ $url ] = false;
			continue;
		}

		$full_preview_urls[ $url ] = $clean_url;
		return $clean_url;

	}

	return false;
}

/**
 * Using a URL partial specification, it fins a local URL.
 *
 * @param non-empty-list<TScope_Rule> $scope Test scope.
 *
 * @return string|false
 */
function find_local_url_in_scope_from_partial_specification( $scope ) {

	/** @var array<string,string|false> */
	static $partial_url_to_preview_url_list = array();

	$partials = array_map(
		fn ( $candidate ): string =>'partial' === $candidate['attributes']['type'] ? $candidate['attributes']['value'] : '',
		$scope
	);
	$partials = array_values( array_filter( $partials ) );

	foreach ( $partials as $partial ) {

		if ( isset( $partial_url_to_preview_url_list[ $partial ] ) ) {
			$url = $partial_url_to_preview_url_list[ $partial ];
			if ( $url ) {
				return $url;
			} elseif ( false === $url ) {
				continue;
			}
		}

		$url = find_url_from_partial( $partial );
		$partial_url_to_preview_url_list[ $partial ] = $url;
		if ( $url ) {
			return $url;
		}
	}

	return false;
}

/**
 * Whether the URL is local or not.
 *
 * @param string $url URL.
 *
 * @return bool
 */
function is_local_url( $url ) {

	$clean_home_url = preg_replace( '/^https?:/', '', nab_home_url() );
	$clean_home_url = is_string( $clean_home_url ) ? $clean_home_url : '';
	$url            = preg_replace( '/^https?:/', '', $url );
	$url            = is_string( $url ) ? $url : '';
	return 0 === strpos( $url, $clean_home_url );
}

/**
 * Cleans the given URL.
 *
 * @param string $url URL.
 *
 * @return string
 */
function clean_url( $url ) {

	$clean_home_url = preg_replace( '/^https?:/', '', nab_home_url() );
	$clean_home_url = is_string( $clean_home_url ) ? $clean_home_url : '';
	$url            = preg_replace( '/^https?:/', '', $url );
	$url            = is_string( $url ) ? $url : '';
	return nab_home_url() . str_replace( $clean_home_url, '', $url );
}

/**
 * Whether the URL is valid.
 *
 * @param string $url URL.
 *
 * @return bool
 */
function is_url_valid( $url ) {

	/**
	 * Filters whether the plugin should check if the given URL exists or not.
	 *
	 * @param boolean $check if the check should run or not. Default: `false`.
	 * @param string  $url   the URL on which the check should run.
	 *
	 * @since 5.0.0
	 */
	if ( ! apply_filters( 'nab_check_validity_of_preview_url', false, $url ) ) {
		return true;
	}

	$response = wp_remote_head( $url );
	if ( is_wp_error( $response ) ) {
		return false;
	}

	return in_array( wp_remote_retrieve_response_code( $response ), array( 200, 301, 302 ), true );
}

/**
 * Finds a URL from a partial URL.
 *
 * @param string $partial Parital URL.
 *
 * @return string|false
 */
function find_url_from_partial( $partial ) {

	if ( seems_valid_full_url( $partial ) ) {
		$url = get_full_url_from_partial( $partial );
		if ( ! empty( $url ) ) {
			return $url;
		}
	}

	$post_name = '%' . $partial . '%';
	$post_name = preg_replace( '/^%\//', '', $post_name );
	$post_name = is_string( $post_name ) ? $post_name : '';
	$post_name = preg_replace( '/\/%$/', '', $post_name );
	$post_name = is_string( $post_name ) ? $post_name : '';

	if ( false !== strpos( $post_name, '/' ) ) {
		$post_name = preg_replace( '/.*\/([^\/]*)/', '$1', $post_name );
		$post_name = is_string( $post_name ) ? $post_name : '';
	}

	$url = find_url_from_post_name( $post_name );
	if ( ! $url || false === strpos( $url, $partial ) ) {
		return false;
	}

	return $url;
}

/**
 * Finds a URL from a post name.
 *
 * @param string $name Post name.
 *
 * @return string|false
 */
function find_url_from_post_name( $name ) {

	$key = "nab_permalink_for_$name";

	/** @var string|false */
	$permalink = wp_cache_get( $key );
	if ( $permalink ) {
		return $permalink;
	}

	/** @var \wpdb $wpdb */
	global $wpdb;
	// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
	$result = $wpdb->get_results(
		$wpdb->prepare(
			"SELECT ID, post_type
			 FROM %i
			 WHERE
				 post_status IN ( 'publish', 'draft' ) AND
				 post_name LIKE %s
			 LIMIT 1",
			$wpdb->posts,
			esc_like( $name )
		)
	);

	$permalink = false;
	if ( ! empty( $result ) ) {
		$result = $result[0];
		if ( 'page' === $result->post_type ) {
			$permalink = get_page_link( absint( $result->ID ) );
		} else {
			$permalink = get_permalink( absint( $result->ID ) );
		}
	}

	wp_cache_set( $key, $permalink );
	return $permalink;
}

/**
 * Escapes value to be used in SQL LIKE statement.
 *
 * @param string $value Value.
 *
 * @return string
 */
function esc_like( $value ) {
	/** @var \wpdb $wpdb */
	global $wpdb;
	$value = explode( '%', $value );
	$value = array_map( fn( $fragment ) => $wpdb->esc_like( $fragment ), $value );
	$value = implode( '%', $value );
	return $value;
}

/**
 * Whether the partial URL seems a valid full URL.
 *
 * @param string $partial Partial URL.
 *
 * @return bool
 */
function seems_valid_full_url( $partial ) {

	if ( 0 === strpos( $partial, 'http://' ) ) {
		return true;
	}

	if ( 0 === strpos( $partial, 'https://' ) ) {
		return true;
	}

	return false;
}

/**
 * Returns a full URL from a partial URL.
 *
 * @param string $partial Parital URL.
 *
 * @return string|false
 */
function get_full_url_from_partial( $partial ) {

	$post_id = nab_url_to_postid( $partial );
	if ( $post_id ) {
		return get_permalink( $post_id );
	}

	return false;
}
