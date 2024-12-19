<?php

namespace Nelio_AB_Testing\Compat\Optimize_Press;

defined( 'ABSPATH' ) || exit;

use OPBuilder\Support\Tools;

use function add_action;
use function add_filter;
use function class_exists;

function prevent_cache_in_wrong_meta( $alternative, $control ) {

	if ( ! Tools::isOPPage( $control['postId'] ) ) {
		return;
	}//end if

	if ( ! empty( $control['testAgainstExistingContent'] ) ) {
		return;
	}//end if

	$control_id     = $control['postId'];
	$alternative_id = $alternative['postId'];

	add_filter(
		'update_post_metadata',
		function ( $its_ok, $object_id, $meta_key, $meta_value ) use ( $control_id, $alternative_id ) {

			if ( ! in_array( $meta_key, array( '_op3_cache', '_op3_cache_timestamp' ), true ) ) {
				return $its_ok;
			}//end if

			if ( $object_id !== $control_id ) {
				return $its_ok;
			}//end if

			if ( $control_id === $alternative_id ) {
				return $its_ok;
			}//end if

			update_post_meta( $alternative_id, $meta_key, $meta_value );
			return false;
		},
		10,
		4
	);
}//end prevent_cache_in_wrong_meta()

add_action(
	'plugins_loaded',
	function () {
		if ( ! class_exists( 'OPBuilder\Support\Tools' ) ) {
			return;
		}//end if
		add_action( 'nab_nab/page_load_alternative', __NAMESPACE__ . '\prevent_cache_in_wrong_meta', 99, 2 );
		add_action( 'nab_nab/post_load_alternative', __NAMESPACE__ . '\prevent_cache_in_wrong_meta', 99, 2 );
		add_action( 'nab_nab/custom-post-type_load_alternative', __NAMESPACE__ . '\prevent_cache_in_wrong_meta', 99, 2 );
	}
);
