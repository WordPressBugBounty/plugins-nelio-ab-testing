<?php

namespace Nelio_AB_Testing\Conversion_Action_Library\Page_View;

defined( 'ABSPATH' ) || exit;

use function add_filter;

add_filter(
	'nab_sanitize_conversion_action_attributes',
	function ( $attributes, $action ) {
		if ( 'nab/page-view' !== $action['type'] ) {
			return $attributes;
		}//end if

		$defaults = array(
			'mode'     => 'id',
			'postId'   => 0,
			'postType' => 'page',
			'url'      => '',
		);

		$attributes = wp_parse_args( $attributes, $defaults );

		if ( 'id' === $attributes['mode'] ) {
			$attributes['url'] = $defaults['url'];
		} elseif ( 'url' === $attributes['mode'] ) {
			$attributes['postType'] = $defaults['postType'];
			$attributes['postId']   = $defaults['postId'];
		}//end if

		return $attributes;
	},
	10,
	2
);
