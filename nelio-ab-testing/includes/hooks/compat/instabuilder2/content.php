<?php

namespace Nelio_AB_Testing\Compat\Instabuilder2;

defined( 'ABSPATH' ) || exit;

use function add_action;
use function add_filter;

use function ib2_clone_page;
use function ib2_page_exists;

/**
 * Callback to properly duplicate instabuilder post.
 *
 * @param int $result New post.
 * @param int $src_id Source post.
 *
 * @return int
 */
function duplicate_post( $result, $src_id ) {

	if ( ! ib2_page_exists( $src_id ) ) {
		return $result;
	}

	$post_id = ib2_clone_page( $src_id );
	if ( is_wp_error( $post_id ) ) {
		return $result;
	}

	return $post_id;
}

add_action(
	'plugins_loaded',
	function () {
		if ( ! defined( 'IB2_VERSION' ) ) {
			return;
		}
		add_filter( 'nab_duplicate_post_pre', __NAMESPACE__ . '\duplicate_post', 10, 2 );
	}
);
