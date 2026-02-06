<?php

namespace Nelio_AB_Testing\Conversion_Action_Library\Page_View;

defined( 'ABSPATH' ) || exit;

use function add_filter;

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

	$mode = $action['attributes']['mode'] ?? 'id';
	if ( 'id' === $mode ) {
		$post_id = absint( $action['attributes']['postId'] ?? 0 );
		if ( ! empty( $post_id ) ) {
			return array(
				'type' => 'post-ids',
				'ids'  => array( $post_id ),
			);
		}
	}

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

	return array(
		'type' => 'post-ids',
		'ids'  => array(),
	);
}
add_filter( 'nab_sanitize_conversion_action_scope', __NAMESPACE__ . '\sanitize_conversion_action_scope', 10, 2 );
