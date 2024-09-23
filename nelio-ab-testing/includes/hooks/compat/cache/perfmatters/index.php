<?php
/**
 * This file defines hooks to filters and actions to make the plugin compatible with Perfmatters.
 *
 * @package    Nelio_AB_Testing
 * @subpackage Nelio_AB_Testing/includes/hooks/compat/cache
 * @since      6.4.0
 */

namespace Nelio_AB_Testing\Compat\Cache\Perfmatters;

defined( 'ABSPATH' ) || exit;

function override_js_exclude( $exclusions ) {
	$exclusions[] = 'nelio-ab-testing';
	$exclusions[] = 'nabSettings';
	return $exclusions;
}//end override_js_exclude()
add_filter( 'perfmatters_delay_js_exclusions', __NAMESPACE__ . '\override_js_exclude' );
