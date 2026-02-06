<?php

namespace Nelio_AB_Testing\Conversion_Action_Library\Page_View;

defined( 'ABSPATH' ) || exit;

use function add_filter;

/**
 * Sanitizes attributes.
 *
 * @param TAttributes        $attributes Attributes.
 * @param TConversion_Action $action     Action.
 *
 * @return TAttributes
 */
function sanitize_conversion_action_attributes( $attributes, $action ) {
	if ( 'nab/page-view' !== $action['type'] ) {
		return $attributes;
	}

	$defaults = array(
		'mode'     => 'id',
		'postId'   => 0,
		'postType' => 'page',
		'url'      => '',
	);

	/** @var TAttributes */
	$attributes = wp_parse_args( $attributes, $defaults );

	if ( 'id' === $attributes['mode'] ) {
		$attributes['url'] = $defaults['url'];
	} elseif ( 'url' === $attributes['mode'] ) {
		$attributes['postType'] = $defaults['postType'];
		$attributes['postId']   = $defaults['postId'];
	}

	return $attributes;
}
add_filter( 'nab_sanitize_conversion_action_attributes', __NAMESPACE__ . '\sanitize_conversion_action_attributes', 10, 2 );
