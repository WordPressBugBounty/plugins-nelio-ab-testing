<?php

namespace Nelio_AB_Testing\Compat\Polylang;

defined( 'ABSPATH' ) || exit;

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
}//end exclude_polylang_taxonomies_from_overwriting()

function localize_home_url( $url, $path ) {
	return untrailingslashit( pll_home_url() ) . $path;
}//end localize_home_url()

add_action(
	'plugins_loaded',
	function () {
		if ( ! defined( 'POLYLANG' ) && ! defined( 'POLYLANG_PRO' ) ) {
			return;
		}//end if
		add_action( 'nab_get_taxonomies_to_overwrite', __NAMESPACE__ . '\exclude_polylang_taxonomies_from_overwriting' );
		add_action( 'nab_get_testable_taxonomies', __NAMESPACE__ . '\exclude_polylang_taxonomies_from_overwriting' );
		add_action( 'nab_home_url', __NAMESPACE__ . '\localize_home_url', 10, 2 );
	}
);
