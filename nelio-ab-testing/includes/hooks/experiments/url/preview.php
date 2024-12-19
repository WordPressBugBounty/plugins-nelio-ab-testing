<?php

namespace Nelio_AB_Testing\Experiment_Library\Url_Experiment;

defined( 'ABSPATH' ) || exit;

use function add_filter;

function get_preview_link( $preview_link, $alternative ) {

	if ( ! empty( $alternative['url'] ) ) {
		return $alternative['url'];
	}//end if

	return $preview_link;
}//end get_preview_link()
add_filter( 'nab_nab/url_preview_link_alternative', __NAMESPACE__ . '\get_preview_link', 10, 2 );
