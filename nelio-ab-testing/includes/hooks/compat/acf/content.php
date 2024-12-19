<?php

namespace Nelio_AB_Testing\Compat\ACF;

defined( 'ABSPATH' ) || exit;

use function add_filter;
use function get_post_meta;

use function nab_get_experiment;

function nelio_rule_match_post( $is_match, $rule, $options ) {

	if ( ! isset( $options['post_id'] ) ) {
		return $is_match;
	}//end if

	$post_id       = $options['post_id'];
	$experiment_id = get_post_meta( $post_id, '_nab_experiment', true );
	if ( empty( $experiment_id ) ) {
		return $is_match;
	}//end if

	$experiment     = nab_get_experiment( $experiment_id );
	$tested_element = $experiment->get_tested_post();
	if ( empty( $tested_element ) ) {
		return $is_match;
	}//end if

	$selected_post = intval( $rule['value'] );
	if ( '==' === $rule['operator'] ) {
		$is_match = ( $tested_element === $selected_post );
	} elseif ( '!=' === $rule['operator'] ) {
		$is_match = ( $tested_element !== $selected_post );
	}//end if

	return $is_match;
}//end nelio_rule_match_post()
add_filter( 'acf/location/rule_match/page', __NAMESPACE__ . '\nelio_rule_match_post', 99, 3 );
add_filter( 'acf/location/rule_match/post', __NAMESPACE__ . '\nelio_rule_match_post', 99, 3 );

function nelio_is_editing_alternative_front_page( $is_match, $rule, $options ) {
	$rule_type = nab_array_get( $rule, 'value', false );
	if ( 'front_page' !== $rule_type ) {
		return $is_match;
	}//end if

	$post_id = nab_array_get( $options, 'post_id', 0 );
	$post_id = absint( $post_id );
	if ( 'page' !== get_post_type( $post_id ) ) {
		return $is_match;
	}//end if

	$exp_id = absint( get_post_meta( $post_id, '_nab_experiment', true ) );
	$exp    = nab_get_experiment( $exp_id );
	if ( is_wp_error( $exp ) ) {
		return $is_match;
	}//end if

	$control = $exp->get_alternative( 'control' );
	$control = nab_array_get( $control, 'attributes.postId', 0 );

	$page_on_front = absint( get_option( 'page_on_front' ) );
	return $page_on_front && $control === $page_on_front;
}//end nelio_is_editing_alternative_front_page()
add_filter( 'acf/location/rule_match/page_type', __NAMESPACE__ . '\nelio_is_editing_alternative_front_page', 99, 3 );
