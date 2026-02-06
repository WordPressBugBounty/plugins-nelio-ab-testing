<?php

namespace Nelio_AB_Testing\Compat\ACF;

defined( 'ABSPATH' ) || exit;

use function add_filter;
use function get_post_meta;

use function nab_get_experiment;

/**
 * Callback to apply a rule to alternative content as well.
 *
 * @param bool                               $is_match Whether it’s a match or not.
 * @param array{value:mixed,operator:string} $rule     Rule.
 * @param array{post_id?:int}                $options  Options.
 *
 * @return bool
 */
function nelio_rule_match_post( $is_match, $rule, $options ) {

	if ( ! isset( $options['post_id'] ) ) {
		return $is_match;
	}

	$post_id       = absint( $options['post_id'] );
	$experiment_id = absint( get_post_meta( $post_id, '_nab_experiment', true ) );
	if ( empty( $experiment_id ) ) {
		return $is_match;
	}

	$experiment = nab_get_experiment( $experiment_id );
	if ( is_wp_error( $experiment ) ) {
		return $is_match;
	}

	$tested_element = $experiment->get_tested_post();
	if ( empty( $tested_element ) ) {
		return $is_match;
	}

	$selected_post = absint( $rule['value'] );
	if ( '==' === $rule['operator'] ) {
		$is_match = ( $tested_element === $selected_post );
	} elseif ( '!=' === $rule['operator'] ) {
		$is_match = ( $tested_element !== $selected_post );
	}

	return $is_match;
}
add_filter( 'acf/location/rule_match/page', __NAMESPACE__ . '\nelio_rule_match_post', 99, 3 );
add_filter( 'acf/location/rule_match/post', __NAMESPACE__ . '\nelio_rule_match_post', 99, 3 );

/**
 * Callback to apply a rule to alternative content as well.
 *
 * @param bool                               $is_match Whether it’s a match or not.
 * @param array{value:mixed,operator:string} $rule     Rule.
 * @param array{post_id?:int}                $options  Options.
 *
 * @return bool
 */
function nelio_is_editing_alternative_front_page( $is_match, $rule, $options ) {
	$rule_type = $rule['value'];
	if ( 'front_page' !== $rule_type ) {
		return $is_match;
	}

	$post_id = absint( $options['post_id'] ?? 0 );
	if ( 'page' !== get_post_type( $post_id ) ) {
		return $is_match;
	}

	$exp_id = absint( get_post_meta( $post_id, '_nab_experiment', true ) );
	$exp    = nab_get_experiment( $exp_id );
	if ( is_wp_error( $exp ) ) {
		return $is_match;
	}

	$control = $exp->get_alternative( 'control' );
	$control = absint( $control['attributes']['postId'] ?? 0 );

	$page_on_front = absint( get_option( 'page_on_front' ) );
	return $page_on_front && $control === $page_on_front;
}
add_filter( 'acf/location/rule_match/page_type', __NAMESPACE__ . '\nelio_is_editing_alternative_front_page', 99, 3 );
