<?php

namespace Nelio_AB_Testing\Conversion_Action_Library\Page_View;

defined( 'ABSPATH' ) || exit;

use function add_filter;

add_filter(
	'nab_sanitize_conversion_action_scope',
	function ( $scope, $action ) {
		if ( 'nab/page-view' !== $action['type'] ) {
			return $scope;
		}//end if

		$mode = nab_array_get( $action, array( 'attributes', 'mode' ), 'id' );
		if ( 'id' === $mode ) {
			$post_id = absint( nab_array_get( $action, array( 'attributes', 'postId' ), 0 ) );
			if ( ! empty( $post_id ) ) {
				return array(
					'type' => 'post-ids',
					'ids'  => array( $post_id ),
				);
			}//end if
		}//end if

		if ( 'url' === $mode ) {
			$url = nab_array_get( $action, array( 'attributes', 'url' ), '' );
			$url = is_string( $url ) ? trim( $url ) : '';
			if ( ! empty( $url ) ) {
				$url_a = untrailingslashit( $url );
				$url_b = trailingslashit( $url );
				return array(
					'type'    => 'urls',
					'regexes' => array( $url_a, $url_b, "{$url_a}?*", "{$url_b}?*" ),
				);
			}//end if
		}//end if

		return array(
			'type' => 'post-ids',
			'ids'  => array(),
		);
	},
	10,
	2
);
