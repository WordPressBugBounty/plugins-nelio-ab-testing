<?php
/**
 * This file defines hooks to filters and actions to make the plugin compatible with Yoast.
 *
 * @package    Nelio_AB_Testing
 * @subpackage Nelio_AB_Testing/includes/experiments/library
 * @since      7.0.0
 */

namespace Nelio_AB_Testing\Compat\Yoast;

defined( 'ABSPATH' ) || exit;

/**
 * Adds NAB query arguments to Yoast’s allowlist.
 *
 * @param list<string> $vars Allowlist.
 *
 * @return list<string>
 */
function add_nab_args_to_allowlist_permalink_vars( $vars ) {
	$vars[] = 'nab';
	$vars[] = 'nabforce';
	$vars[] = 'nabstaging';

	if ( nab_is_preview() ) {
		$vars[] = 'nab-preview';
		$vars[] = 'experiment';
		$vars[] = 'alternative';
		$vars[] = 'timestamp';
		$vars[] = 'nabnonce';
	}

	if ( nab_is_heatmap() ) {
		$vars[] = 'nab-heatmap-renderer';
	}

	$vars = array_values( array_unique( $vars ) );
	return $vars;
}
add_filter( 'Yoast\WP\SEO\allowlist_permalink_vars', __NAMESPACE__ . '\add_nab_args_to_allowlist_permalink_vars' );
