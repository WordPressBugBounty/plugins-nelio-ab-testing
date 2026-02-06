<?php

namespace Nelio_AB_Testing\Experiment_Library\Url_Experiment;

defined( 'ABSPATH' ) || exit;

use function add_filter;

/**
 * Callback to get preview link.
 *
 * @param string|false                                        $preview_link   Preview link.
 * @param TUrl_Alternative_Attributes|TUrl_Control_Attributes $alternative    Alternative.
 *
 * @return string|false
 */
function get_preview_link( $preview_link, $alternative ) {

	if ( ! empty( $alternative['url'] ) ) {
		return $alternative['url'];
	}

	return $preview_link;
}
add_filter( 'nab_nab/url_preview_link_alternative', __NAMESPACE__ . '\get_preview_link', 10, 2 );
