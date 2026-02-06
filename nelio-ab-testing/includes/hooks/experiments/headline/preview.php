<?php

namespace Nelio_AB_Testing\Experiment_Library\Headline_Experiment;

defined( 'ABSPATH' ) || exit;

use function add_action;
use function add_filter;
use function get_permalink;

/**
 * Callback to get preview link.
 *
 * @param string|false                                                  $preview_link Preview link.
 * @param THeadline_Alternative_Attributes|THeadline_Control_Attributes $alternative  Alternative.
 * @param THeadline_Control_Attributes                                  $control      Control.
 *
 * @return string|false
 */
function get_preview_link( $preview_link, $alternative, $control ) {
	$link = get_permalink( $control['postId'] );
	if ( ! $link ) {
		return false;
	}

	return $link;
}
add_filter( 'nab_nab/headline_preview_link_alternative', __NAMESPACE__ . '\get_preview_link', 10, 3 );

add_action( 'nab_nab/headline_preview_alternative', __NAMESPACE__ . '\load_alternative', 10, 4 );
