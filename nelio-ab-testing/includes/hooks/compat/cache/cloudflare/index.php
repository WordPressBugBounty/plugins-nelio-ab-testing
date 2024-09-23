<?php
/**
 * This file defines hooks to prevent cloudflare from "optimizing" Nelio’s scripts.
 *
 * @package    Nelio_AB_Testing
 * @subpackage Nelio_AB_Testing/includes/hooks/compat/cache
 * @since      5.0.22
 */

namespace Nelio_AB_Testing\Compat\Cache\Cloudflare;

defined( 'ABSPATH' ) || exit;

function add_data_cfasync_attr() {
	return array( 'data-cfasync' => 'false' );
}//end add_data_cfasync_attr()

add_filter( 'nab_add_extra_script_attributes', __NAMESPACE__ . '\add_data_cfasync_attr' );
