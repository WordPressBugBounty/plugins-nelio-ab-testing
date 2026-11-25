<?php

namespace Nelio_AB_Testing\WooCommerce\Experiment_Library\Product_Experiment;

defined( 'ABSPATH' ) || exit;

use Nelio_AB_Testing\Zod\Schema;
use Nelio_AB_Testing\Zod\Zod as Z;

/**
 * Callback to sanitize control attributes.
 *
 * @param TAttributes                  $control    Control.
 * @param \Nelio_AB_Testing_Experiment $experiment Experiment.
 *
 * @return TWC_Product_Control_Attributes
 */
function sanitize_control_attributes( $control, $experiment ) {
	/** @var Schema|null */
	static $schema;
	if ( empty( $schema ) ) {
		$schema = Z::object(
			array(
				'chance'              => Z::number()->optional()->catch( null ),
				'postId'              => Z::number()->transform( fn( $v ) => absint( $v ) )->catch( 0 ),
				'postType'            => Z::literal( 'product' )->catch( 'product' ),
				'disablePriceTesting' => Z::literal( true )->optional()->catch( null ),
			)
		)->catch(
			array(
				'postId'   => 0,
				'postType' => 'product',
			)
		);
	}

	$parsed = $schema->safe_parse( $control );
	assert( $parsed['success'] );
	/** @var TWC_Product_Control_Attributes */
	$control = $parsed['data'];

	$scope = $experiment->get_scope();
	$scope = $scope[0]['attributes']['type'] ?? '';
	if ( 'tested-post' === $scope ) {
		$control['disablePriceTesting'] = true;
	}

	return $control;
}
add_filter( 'nab_nab/wc-product_sanitize_control_attributes', __NAMESPACE__ . '\sanitize_control_attributes', 10, 2 );

/**
 * Sanitizes the automatically configured first goal.
 *
 * @param TGoal $goal          Goal.
 * @param int   $goal_index    Goal index.
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
		return $goal;
	}

	$alternatives = $experiment->get_alternatives();
	if ( empty( $alternatives ) ) {
		return $goal;
	}

	$attributes                = $alternatives[0]['attributes'];
	$post_id                   = absint( $attributes['postId'] ?? 0 );
	$action_id                 = $goal['conversionActions'][0]['id'] ?? nab_uuid();
	$goal['conversionActions'] = array(
		array(
			'id'         => $action_id,
			'type'       => 'nab/wc-order',
			'attributes' => array(
				'type'  => 'product-selection',
				'value' => array(
					'type'  => 'some-products',
					'value' => array(
						'type'       => 'product-ids',
						'mode'       => 'and',
						'productIds' => $post_id ? array( $post_id ) : array(),
					),
				),
			),
			'scope'      => array( 'type' => 'test-scope' ),
		),
	);

	return $goal;
}
add_filter( 'nab_nab/wc-product_sanitize_goal', __NAMESPACE__ . '\maybe_set_automatic_goal_actions', 10, 3 );

/**
 * Callback to sanitize alternative attributes.
 *
 * @param TAttributes $alternative Alternative.
 *
 * @return TWC_Product_Alternative_Attributes
 */
function sanitize_alternative_attributes( $alternative ) {
	/** @var Schema|null */
	static $schema;
	if ( empty( $schema ) ) {
		$schema = Z::object(
			array(
				'name'   => Z::string()->default( '' )->trim()->catch( '' ),
				'chance' => Z::number()->optional()->catch( null ),
				'postId' => Z::number()->transform( fn( $v ) => absint( $v ) )->default( 0 )->catch( 0 ),
			)
		)->catch(
			array(
				'name'   => '',
				'postId' => 0,
			)
		);
	}

	$parsed = $schema->safe_parse( $alternative );
	assert( $parsed['success'] );
	/** @var TWC_Product_Alternative_Attributes */
	return $parsed['data'];
}
add_filter( 'nab_nab/wc-product_sanitize_alternative_attributes', __NAMESPACE__ . '\sanitize_alternative_attributes' );
