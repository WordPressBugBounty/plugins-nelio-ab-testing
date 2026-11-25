<?php

namespace Nelio_AB_Testing\Conversion_Action_Library\Page_View;

defined( 'ABSPATH' ) || exit;

use function add_action;
use function add_filter;
use function remove_filter;

/**
 * Sanitizes conversion action scope.
 *
 * @param TConversion_Action_Scope $scope  Scope.
 * @param TConversion_Action       $action Action.
 *
 * @return TConversion_Action_Scope
 */
function sanitize_conversion_action_scope( $scope, $action ) {
	if ( 'nab/page-view' !== $action['type'] ) {
		return $scope;
	}

	return array( 'type' => 'php-function' );
}
add_filter( 'nab_sanitize_conversion_action_scope', __NAMESPACE__ . '\sanitize_conversion_action_scope', 10, 2 );

/**
 * Converts a page-view PHP-function scope into the internal scope represented by its attributes.
 *
 * @param TConversion_Action_Scope $scope  Scope.
 * @param TConversion_Action       $action Page-view conversion action.
 *
 * @return TConversion_Action_Scope Converted scope.
 */
function convert_php_function_scope( $scope, $action ) {
	if ( 'nab/page-view' !== $action['type'] ) {
		return $scope; // @codeCoverageIgnore
	}

	return get_page_view_scope( $action );
}

/**
 * Adds the temporary page-view scope conversion filter.
 *
 * @param TConversion_Action $action Conversion action.
 *
 * @return void
 */
function add_scope_conversion_filter( $action ) {
	if ( 'nab/page-view' !== $action['type'] ) {
		return;
	}

	add_filter(
		'nab_sanitize_conversion_action_scope',
		__NAMESPACE__ . '\convert_php_function_scope',
		20,
		2
	);
}
add_action( 'nab_summarize_conversion_action_before', __NAMESPACE__ . '\add_scope_conversion_filter' );

/**
 * Removes the temporary page-view scope conversion filter.
 *
 * @param TConversion_Action $action Conversion action.
 *
 * @return void
 */
function remove_scope_conversion_filter( $action ) {
	if ( 'nab/page-view' !== $action['type'] ) {
		return;
	}

	remove_filter(
		'nab_sanitize_conversion_action_scope',
		__NAMESPACE__ . '\convert_php_function_scope',
		20
	);
}
add_action( 'nab_summarize_conversion_action_after', __NAMESPACE__ . '\remove_scope_conversion_filter' );

/**
 * Returns the scope represented by a page-view action.
 *
 * @param TConversion_Action $action Page-view conversion action.
 *
 * @return array{type: 'urls', regexes: list<string>}|array{type: 'post-ids', ids: list<int>}
 */
function get_page_view_scope( $action ) {
	$mode = $action['attributes']['mode'] ?? 'id';
	if ( 'url' === $mode ) {
		$url = $action['attributes']['url'] ?? '';
		$url = is_string( $url ) ? trim( $url ) : '';
		if ( ! empty( $url ) ) {
			$url_a = untrailingslashit( $url );
			$url_b = trailingslashit( $url );
			return array(
				'type'    => 'urls',
				'regexes' => array( $url_a, $url_b, "{$url_a}?*", "{$url_b}?*" ),
			);
		}
	}

	$post_id = absint( $action['attributes']['postId'] ?? 0 );
	return array(
		'type' => 'post-ids',
		'ids'  => ! empty( $post_id ) ? array( $post_id ) : array(),
	);
}
