<?php

namespace Nelio_AB_Testing\Compat\Elementor;

defined( 'ABSPATH' ) || exit;

use function add_filter;
use function add_action;
use function did_action;

add_action(
	'plugins_loaded',
	function () {
		if ( ! did_action( 'elementor/loaded' ) ) {
			return;
		}

		add_filter( 'nab_alternative_urls', __NAMESPACE__ . '\maybe_fix_pagination', 99 );
	}
);

/**
 * Callback to fix pagination URLs on Elementor pages with pagination.
 *
 * @param list<string> $urls URLs.
 *
 * @return list<string>
 */
function maybe_fix_pagination( $urls ) {
	if ( count( $urls ) !== 1 ) {
		return $urls;
	}

	if ( ! is_singular() ) {
		return $urls;
	}

	if ( ! get_post_meta( absint( get_the_ID() ), '_elementor_edit_mode', true ) ) {
		return $urls;
	}

	/** @var int|null $page */
	global $page;
	if ( 1 >= absint( $page ) ) {
		return $urls;
	}

	/** @var \WP $wp */
	global $wp;
	return array( trailingslashit( home_url( $wp->request ) ) );
}
