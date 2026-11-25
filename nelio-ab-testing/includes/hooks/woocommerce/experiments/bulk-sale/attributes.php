<?php

namespace Nelio_AB_Testing\WooCommerce\Experiment_Library\Bulk_Sale_Experiment;

defined( 'ABSPATH' ) || exit;

use Nelio_AB_Testing\Zod\Schema;
use Nelio_AB_Testing\Zod\Zod as Z;

/**
 * Callback to sanitize control attributes.
 *
 * @param TAttributes $control Control attributes.
 *
 * @return TWC_Bulk_Sale_Control_Attributes
 */
function sanitize_control_attributes( $control ) {
	/** @var Schema|null */
	static $schema;
	if ( empty( $schema ) ) {
		$schema = Z::object(
			array(
				'chance'            => Z::number()->optional()->catch( null ),
				'productSelections' => Z::array(
					Z::union(
						array(
							Z::object(
								array(
									'type' => Z::literal( 'all-products' ),
								)
							),
							Z::object(
								array(
									'type'  => Z::literal( 'some-products' ),
									'value' => Z::object(
										array(
											'type'  => Z::literal( 'product-taxonomies' ),
											'value' => Z::array(
												Z::object(
													array(
														'type'     => Z::literal( 'product-terms' ),
														'taxonomy' => Z::enum( array( 'product_cat', 'product_tag' ) ),
														'termIds'  => Z::array(
															Z::number()->transform( fn( $v ) => absint( $v ) )->catch( 0 )
														)->min( 1 )->catch( array() ),
														'mode'     => Z::literal( 'or' )->catch( 'or' ),
													)
												)
											)->catch( array() ),
										)
									)->catch(
										array(
											'type'  => 'product-taxonomies',
											'value' => array(),
										)
									),
								)
							),
						)
					)->catch( false )
				)->transform(
					fn( $v ) => is_array( $v ) ? array_values( array_filter( $v ) ) : array()
				)->catch( array() ),
			)
		)->catch( array( 'productSelections' => array() ) );
	}

	$parsed = $schema->safe_parse( $control );
	assert( $parsed['success'] );
	/** @var TWC_Bulk_Sale_Control_Attributes */
	$control = $parsed['data'];

	if ( count( $control['productSelections'] ) !== 1 ) {
		return array(
			'productSelections' => array( array( 'type' => 'all-products' ) ),
		);
	}

	$selection = $control['productSelections'][0];
	if ( 'some-products' !== $selection['type'] ) {
		return $control;
	}

	$taxonomies = array();
	foreach ( $selection['value']['value'] as $taxonomy ) {
		if ( 0 === count( $taxonomy['termIds'] ) ) {
			continue;
		}
		if ( isset( $taxonomies[ $taxonomy['taxonomy'] ] ) ) {
			continue;
		}
		$taxonomies[ $taxonomy['taxonomy'] ] = $taxonomy;
	}

	if ( empty( $taxonomies ) ) {
		return array(
			'productSelections' => array( array( 'type' => 'all-products' ) ),
		);
	}

	$control['productSelections'] = array(
		array(
			'type'  => 'some-products',
			'value' => array(
				'type'  => 'product-taxonomies',
				'value' => array_values( $taxonomies ),
			),
		),
	);
	return $control;
}
add_filter( 'nab_nab/wc-bulk-sale_sanitize_control_attributes', __NAMESPACE__ . '\sanitize_control_attributes' );

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
		return $goal;
	}

	$alternatives = $experiment->get_alternatives();
	if ( empty( $alternatives ) ) {
		return $goal;
	}

	$attributes = $alternatives[0]['attributes'];
	$selections = is_array( $attributes['productSelections'] ?? null ) ? $attributes['productSelections'] : array();
	$actions    = array();
	foreach ( $selections as $index => $selection ) {
		if ( ! is_array( $selection ) ) {
			continue;
		}
		$actions[] = array(
			'id'         => $goal['conversionActions'][ $index ]['id'] ?? nab_uuid(),
			'type'       => 'nab/wc-order',
			'attributes' => array(
				'type'  => 'product-selection',
				'value' => $selection,
			),
			'scope'      => array( 'type' => 'test-scope' ),
		);
	}

	$goal['conversionActions'] = $actions;
	return $goal;
}
add_filter( 'nab_nab/wc-bulk-sale_sanitize_goal', __NAMESPACE__ . '\maybe_set_automatic_goal_actions', 10, 3 );

/**
 * Callback to sanitize alternative attributes.
 *
 * @param TAttributes $alternative Alternative attributes.
 *
 * @return TWC_Bulk_Sale_Alternative_Attributes
 */
function sanitize_alternative_attributes( $alternative ) {
	/** @var Schema|null */
	static $schema;
	if ( empty( $schema ) ) {
		$schema = Z::object(
			array(
				'name'                        => Z::string()->default( '' )->trim()->catch( '' ),
				'chance'                      => Z::number()->optional()->catch( null ),
				'discount'                    => Z::number()->default( 20 )->catch( 20 ),
				'overwritesExistingSalePrice' => Z::boolean()->default( true )->catch( true ),
			)
		)->catch(
			array(
				'name'                        => '',
				'discount'                    => 20,
				'overwritesExistingSalePrice' => true,
			)
		);
	}

	$parsed = $schema->safe_parse( $alternative );
	assert( $parsed['success'] );
	/** @var TWC_Bulk_Sale_Alternative_Attributes */
	return $parsed['data'];
}
add_filter( 'nab_nab/wc-bulk-sale_sanitize_alternative_attributes', __NAMESPACE__ . '\sanitize_alternative_attributes' );
