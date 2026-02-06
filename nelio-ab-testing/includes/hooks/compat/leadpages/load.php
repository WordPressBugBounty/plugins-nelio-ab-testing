<?php

namespace Nelio_AB_Testing\Compat\Leadpages;

defined( 'ABSPATH' ) || exit;

use function add_action;
use function add_filter;
use function class_exists;

/**
 * Fixes leadpages query for alternative content.
 *
 * @param TPost_Control_Attributes|TPost_Alternative_Attributes $alternative Alternative.
 * @param TPost_Control_Attributes                              $control     Alternative.
 *
 * @return void
 */
function fix_leadpages_query_for_alternative( $alternative, $control ) {

	if ( $control['postId'] === $alternative['postId'] ) {
		return;
	}

	if ( 'leadpages_post' !== $control['postType'] ) {
		return;
	}

	$alternative_id = $alternative['postId'];

	add_filter(
		'query',
		function ( $query ) use ( $alternative_id ) {
			/** @var string $query */

			if ( 0 >= strpos( $query, 'pm.meta_key = \'leadpages_slug\'' ) ) {
				return $query;
			}
			$alternative_slug = get_post_meta( $alternative_id, 'leadpages_slug', true );
			if ( ! is_string( $alternative_slug ) ) {
				return $query;
			}

			return preg_replace( '/pm.meta_value = \'[^\']+\'/', "pm.meta_value = '$alternative_slug'", $query );
		}
	);
}

add_action(
	'plugins_loaded',
	function () {
		if ( ! class_exists( 'LeadpagesWP\Admin\CustomPostTypes\LeadpagesPostType' ) ) {
			return;
		}
		add_action( 'nab_nab/custom-post-type_load_alternative', __NAMESPACE__ . '\fix_leadpages_query_for_alternative', 10, 2 );
	}
);
