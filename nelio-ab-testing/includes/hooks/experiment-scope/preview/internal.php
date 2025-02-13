<?php
namespace Nelio_AB_Testing\Hooks\Experiment_Scope\Preview;

defined( 'ABSPATH' ) || exit;

function find_preview_url_in_scope( $scope ) {

	$url = find_exact_local_url_in_scope( $scope );
	if ( $url ) {
		return $url;
	}//end if

	$url = find_local_url_in_scope_from_partial_specification( $scope );
	if ( $url ) {
		return $url;
	}//end if

	$url = nab_home_url();
	foreach ( $scope as $rule ) {
		$rule = nab_array_get( $rule, 'attributes', array() );
		if ( nab_does_rule_apply_to_url( $rule, $url, array() ) ) {
			return true;
		}//end if
	}//end foreach

	return false;
}//end find_preview_url_in_scope()

function find_local_url_in_scope_from_partial_specification( $scope ) {

	static $partial_url_to_preview_url_list = array();

	$scope = array_filter(
		$scope,
		function ( $candidate ) {
			return 'partial' === $candidate['attributes']['type'];
		}
	);

	$partials = array_map(
		function ( $candidate ) {
			return $candidate['attributes']['value'];
		},
		$scope
	);

	foreach ( $partials as $partial ) {

		if ( isset( $partial_url_to_preview_url_list[ $partial ] ) ) {
			$url = $partial_url_to_preview_url_list[ $partial ];
			if ( $url ) {
				return $url;
			} elseif ( false === $url ) {
				continue;
			}//end if
		}//end if

		$url = find_url_from_partial( $partial );
		$partial_url_to_preview_url_list[ $partial ] = $url;
		if ( $url ) {
			return $url;
		}//end if
	}//end foreach

	return false;
}//end find_local_url_in_scope_from_partial_specification()

function find_exact_local_url_in_scope( $scope ) {

	if ( 'php-snippet' === nab_array_get( $scope, '0.attributes.type' ) ) {
		$scope = array(
			array(
				'attributes' => array(
					'type'  => 'exact',
					'value' => nab_array_get( $scope, '0.attributes.value.previewUrl' ),
				),
			),
		);
	}//end if

	static $full_preview_urls = array();

	$scope = array_filter(
		$scope,
		function ( $candidate ) {
			return 'exact' === $candidate['attributes']['type'];
		}
	);

	$urls = array_map(
		function ( $candidate ) {
			return $candidate['attributes']['value'];
		},
		$scope
	);

	foreach ( $urls as $url ) {

		if ( isset( $full_preview_urls[ $url ] ) ) {
			if ( $full_preview_urls[ $url ] ) {
				return $full_preview_urls[ $url ];
			} else {
				continue;
			}//end if
		}//end if

		if ( ! is_local_url( $url ) ) {
			$full_preview_urls[ $url ] = false;
			continue;
		}//end if

		$clean_url = esc_url( $url );
		if ( ! is_url_valid( $clean_url ) ) {
			$full_preview_urls[ $url ] = false;
			continue;
		}//end if

		$full_preview_urls[ $url ] = $clean_url;
		return $clean_url;

	}//end foreach

	return false;
}//end find_exact_local_url_in_scope()

function is_local_url( $url ) {

	$clean_home_url = preg_replace( '/^https?:/', '', nab_home_url() );
	$url            = preg_replace( '/^https?:/', '', $url );
	return 0 === strpos( $url, $clean_home_url );
}//end is_local_url()

function clean_url( $url ) {

	$clean_home_url = preg_replace( '/^https?:/', '', nab_home_url() );
	$url            = preg_replace( '/^https?:/', '', $url );
	return nab_home_url() . str_replace( $clean_home_url, '', $url );
}//end clean_url()

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
	}//end if

	$response = wp_remote_head( $url );
	if ( is_wp_error( $response ) ) {
		return false;
	}//end if

	return in_array( wp_remote_retrieve_response_code( $response ), array( 200, 301, 302 ), true );
}//end is_url_valid()

function find_url_from_partial( $partial ) {

	if ( seems_valid_full_url( $partial ) ) {
		$url = get_full_url_from_partial( $partial );
		if ( ! empty( $url ) ) {
			return $url;
		}//end if
	}//end if

	$post_name = '%' . $partial . '%';
	$post_name = preg_replace( '/^%\//', '', $post_name );
	$post_name = preg_replace( '/\/%$/', '', $post_name );

	if ( 0 <= strpos( $post_name, '/' ) ) {
		$post_name = preg_replace( '/.*\/([^\/]*)/', '$1', $post_name );
	}//end if

	$url = find_url_from_post_name( $post_name );
	if ( -1 === strpos( $url, $partial ) ) {
		return false;
	}//end if

	return $url;
}//end find_url_from_partial()

function find_url_from_post_name( $name ) {

	$key       = "nab_permalink_for_$name";
	$permalink = wp_cache_get( $key );
	if ( $permalink ) {
		return $permalink;
	}//end if

	global $wpdb;
	$result    = $wpdb->get_results( // phpcs:ignore
		$wpdb->prepare(
			"SELECT ID, post_type
			 FROM $wpdb->posts
			 WHERE
				 post_status IN ( 'publish', 'draft' ) AND
				 post_name LIKE %s
			 LIMIT 1",
			esc_like( $name )
		)
	);
	$permalink = false;

	if ( ! empty( $result ) ) {
		$result = $result[0];
		if ( 'page' === $result->post_type ) {
			$permalink = get_page_link( $result->ID );
		} else {
			$permalink = get_permalink( $result->ID );
		}//end if
	}//end if

	wp_cache_set( $key, $permalink );
	return $permalink;
}//end find_url_from_post_name()

function esc_like( $value ) {
	$value = explode( '%', $value );

	global $wpdb;
	$value = array_map(
		function ( $fragment ) use ( $wpdb ) {
			return $wpdb->esc_like( $fragment );
		},
		$value
	);

	$value = implode( '%', $value );
	return $value;
}//end esc_like()

function seems_valid_full_url( $partial ) {

	if ( 0 === strpos( $partial, 'http://' ) ) {
		return true;
	}//end if

	if ( 0 === strpos( $partial, 'https://' ) ) {
		return true;
	}//end if

	return false;
}//end seems_valid_full_url()

function get_full_url_from_partial( $partial ) {

	$post_id = nab_url_to_postid( $partial );
	if ( $post_id ) {
		return get_permalink( $post_id );
	}//end if

	return false;
}//end get_full_url_from_partial()
