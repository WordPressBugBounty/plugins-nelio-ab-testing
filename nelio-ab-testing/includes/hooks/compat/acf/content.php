<?php

namespace Nelio_AB_Testing\Compat\ACF;

defined( 'ABSPATH' ) || exit;

use function add_filter;
use function get_post_meta;

use function nab_get_experiment;

function nelio_rule_match_post( $match, $rule, $options ) {

	if ( ! isset( $options['post_id'] ) ) {
		return $match;
	}//end if

	$post_id       = $options['post_id'];
	$experiment_id = get_post_meta( $post_id, '_nab_experiment', true );
	if ( empty( $experiment_id ) ) {
		return $match;
	}//end if

	$experiment     = nab_get_experiment( $experiment_id );
	$tested_element = $experiment->get_tested_element();
	if ( empty( $tested_element ) ) {
		return $match;
	}//end if

	$selected_post = intval( $rule['value'] );
	if ( '==' === $rule['operator'] ) {
		$match = ( $tested_element === $selected_post );
	} elseif ( '!=' === $rule['operator'] ) {
		$match = ( $tested_element !== $selected_post );
	}//end if

	return $match;

}//end nelio_rule_match_post()
add_filter( 'acf/location/rule_match/page', __NAMESPACE__ . '\nelio_rule_match_post', 99, 3 );
add_filter( 'acf/location/rule_match/post', __NAMESPACE__ . '\nelio_rule_match_post', 99, 3 );
