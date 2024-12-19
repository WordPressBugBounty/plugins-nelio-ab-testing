<?php

namespace Nelio_AB_Testing\Compat\Leadpages;

defined( 'ABSPATH' ) || exit;

use function add_action;
use function add_filter;
use function class_exists;

function fix_leadpages_query_for_alternative( $alternative, $control ) {

	if ( $control['postId'] === $alternative['postId'] ) {
		return;
	}//end if

	if ( 'leadpages_post' !== $control['postType'] ) {
		return;
	}//end if

	$alternative_id = $alternative['postId'];

	add_filter(
		'query',
		function ( $query ) use ( $alternative_id ) {
			if ( 0 >= strpos( $query, 'pm.meta_key = \'leadpages_slug\'' ) ) {
				return $query;
			}//end if
			$alternative_slug = get_post_meta( $alternative_id, 'leadpages_slug', true );
			return preg_replace( '/pm.meta_value = \'[^\']+\'/', "pm.meta_value = '$alternative_slug'", $query );
		}
	);
}//end fix_leadpages_query_for_alternative()

add_action(
	'plugins_loaded',
	function () {
		if ( ! class_exists( 'LeadpagesWP\Admin\CustomPostTypes\LeadpagesPostType' ) ) {
			return;
		}//end if
		add_action( 'nab_nab/custom-post-type_load_alternative', __NAMESPACE__ . '\fix_leadpages_query_for_alternative', 10, 2 );
	}
);
