<?php

namespace Nelio_AB_Testing\Compat\Nelio_Popups;

defined( 'ABSPATH' ) || exit;

function get_preview_link( $link, $alternative, $control ) {
	if ( ! is_nelio_popup( $control ) ) {
		return $link;
	}//end if
	return get_preview_post_link( $alternative['postId'] );
}//end get_preview_link()
add_filter( 'nab_nab/popup_preview_link_alternative', __NAMESPACE__ . '\get_preview_link', 10, 3 );
