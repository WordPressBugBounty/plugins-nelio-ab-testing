<?php

namespace Nelio_AB_Testing\Experiment_Library\Headline_Experiment;

defined( 'ABSPATH' ) || exit;

use Nelio_AB_Testing\Zod\Schema;
use Nelio_AB_Testing\Zod\Zod as Z;

/**
 * Sanitizes control attributes.
 *
 * @param TAttributes $control Control.
 *
 * @return THeadline_Control_Attributes
 */
function sanitize_control_attributes( $control ) {
	/** @var Schema|null */
	static $schema;
	if ( empty( $schema ) ) {
		$schema = Z::object(
			array(
				'chance'   => Z::number()->optional()->catch( null ),
				'postId'   => Z::number()->transform( fn( $v ) => absint( $v ) )->catch( 0 ),
				'postType' => Z::literal( 'post' )->catch( 'post' ),
			)
		)->catch(
			array(
				'postId'   => 0,
				'postType' => 'post',
			)
		);
	}

	$parsed = $schema->safe_parse( $control );
	assert( $parsed['success'] );
	/** @var THeadline_Control_Attributes */
	return $parsed['data'];
}
add_filter( 'nab_nab/headline_sanitize_control_attributes', __NAMESPACE__ . '\sanitize_control_attributes' );

/**
 * Sanitizes the automatically configured first goal.
 *
 * @param TGoal $goal           Goal.
 * @param int   $goal_index     Goal index.
 * @param int   $experiment_id Experiment identifier.
 *
 * @return TGoal Sanitized goal.
 */
function maybe_set_automatic_goal_actions( $goal, $goal_index, $experiment_id ) {
	if ( 0 !== $goal_index ) {
		return $goal;
	}

	$experiment = nab_get_experiment( $experiment_id );
	if ( is_wp_error( $experiment ) ) {
		return $goal; // @codeCoverageIgnore
	}

	$alternatives = $experiment->get_alternatives();
	if ( empty( $alternatives ) ) {
		return $goal; // @codeCoverageIgnore
	}

	$attributes                = $alternatives[0]['attributes'];
	$post_id                   = absint( $attributes['postId'] ?? 0 );
	$action_id                 = $goal['conversionActions'][0]['id'] ?? nab_uuid();
	$goal['conversionActions'] = array(
		array(
			'id'         => $action_id,
			'type'       => 'nab/page-view',
			'attributes' => array(
				'mode'     => 'id',
				'postType' => 'post',
				'postId'   => $post_id,
				'url'      => '',
			),
			'scope'      => array( 'type' => 'test-scope' ),
		),
	);

	return $goal;
}
add_filter( 'nab_nab/headline_sanitize_goal', __NAMESPACE__ . '\maybe_set_automatic_goal_actions', 10, 3 );

/**
 * Sanitizes alternative attributes.
 *
 * @param TAttributes $alternative Alternative.
 *
 * @return THeadline_Alternative_Attributes
 */
function sanitize_alternative_attributes( $alternative ) {
	/** @var Schema|null */
	static $schema;
	if ( empty( $schema ) ) {
		$schema = Z::object(
			array(
				'name'     => Z::string()->default( '' )->trim()->catch( '' ),
				'chance'   => Z::number()->optional()->catch( null ),
				'excerpt'  => Z::string()->default( '' )->trim()->catch( '' ),
				'imageId'  => Z::number()->transform( fn( $v ) => absint( $v ) )->default( 0 )->catch( 0 ),
				'imageUrl' => Z::string()->default( '' )->trim()->catch( '' ),
			)
		)->catch(
			array(
				'name'     => '',
				'excerpt'  => '',
				'imageId'  => 0,
				'imageUrl' => '',
			)
		);
	}

	$parsed = $schema->safe_parse( $alternative );
	assert( $parsed['success'] );
	/** @var THeadline_Alternative_Attributes */
	return $parsed['data'];
}
add_filter( 'nab_nab/headline_sanitize_alternative_attributes', __NAMESPACE__ . '\sanitize_alternative_attributes' );
