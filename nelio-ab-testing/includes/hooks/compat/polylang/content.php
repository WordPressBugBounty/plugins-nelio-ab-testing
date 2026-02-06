<?php

namespace Nelio_AB_Testing\Compat\Polylang;

defined( 'ABSPATH' ) || exit;

/**
 * Excludes polylang taxonomies from being overwritten.
 *
 * @param list<string> $taxonomies Taxonomies.
 *
 * @return list<string>
 */
function exclude_polylang_taxonomies_from_overwriting( $taxonomies ) {
	$polylang_taxonomies = array( 'language', 'term_language', 'post_translations', 'term_translations' );
	return array_values(
		array_filter(
			$taxonomies,
			function ( $taxonomy ) use ( &$polylang_taxonomies ) {
				return ! in_array( $taxonomy, $polylang_taxonomies, true );
			}
		)
	);
}

/**
 * Translates home URL.
 *
 * @param string $url  Home URL using the given path.
 * @param string $path Path relative to the home URL.
 * @return string
 */
function localize_home_url( $url, $path ) {
	return untrailingslashit( pll_home_url() ) . $path;
}

add_action(
	'plugins_loaded',
	function () {
		if ( ! defined( 'POLYLANG' ) && ! defined( 'POLYLANG_PRO' ) ) {
			return;
		}
		add_filter( 'nab_get_taxonomies_to_overwrite', __NAMESPACE__ . '\exclude_polylang_taxonomies_from_overwriting' );
		add_filter( 'nab_get_testable_taxonomies', __NAMESPACE__ . '\exclude_polylang_taxonomies_from_overwriting' );
		add_filter( 'nab_home_url', __NAMESPACE__ . '\localize_home_url', 10, 2 );
	}
);
