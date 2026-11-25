<?php

namespace Nelio_AB_Testing\EasyDigitalDownloads\Conversion_Action_Library\Order_Completed;

defined( 'ABSPATH' ) || exit;

use Nelio_AB_Testing\Zod\Schema;
use Nelio_AB_Testing\Zod\Zod as Z;

/**
 * Sanitizes Easy Digital Downloads order conversion action attributes.
 *
 * @param TAttributes $attributes Attributes.
 *
 * @return TAttributes
 */
function sanitize( $attributes ) {
	/** @var Schema|null */
	static $schema;
	if ( empty( $schema ) ) {
		$download_ids        = Z::object(
			array(
				'type'        => Z::literal( 'download-ids' ),
				'downloadIds' => Z::array( Z::number()->transform( fn( $v ) => absint( $v ) )->catch( 0 ) )->catch( array() ),
				'mode'        => Z::union( array( Z::literal( 'or' ), Z::literal( 'and' ) ) )->default( 'or' )->catch( 'or' ),
				'excluded'    => Z::boolean()->optional()->catch( null ),
			)
		)->catch(
			array(
				'type'        => 'download-ids',
				'downloadIds' => array(),
				'mode'        => 'or',
			)
		);
		$download_terms      = Z::object(
			array(
				'type'     => Z::literal( 'download-terms' ),
				'taxonomy' => Z::string()->trim()->catch( '' ),
				'termIds'  => Z::array( Z::number()->transform( fn( $v ) => absint( $v ) )->catch( 0 ) )->catch( array() ),
				'mode'     => Z::union( array( Z::literal( 'or' ), Z::literal( 'and' ) ) )->default( 'or' )->catch( 'or' ),
				'excluded' => Z::boolean()->optional()->catch( null ),
			)
		)->catch(
			array(
				'type'     => 'download-terms',
				'taxonomy' => '',
				'termIds'  => array(),
				'mode'     => 'or',
			)
		);
		$download_taxonomies = Z::object(
			array(
				'type'  => Z::literal( 'download-taxonomies' ),
				'value' => Z::array( $download_terms )->catch( array() ),
			)
		)->catch(
			array(
				'type'  => 'download-taxonomies',
				'value' => array(),
			)
		);
		$schema              = Z::object(
			array(
				'type'  => Z::literal( 'download-selection' )->default( 'download-selection' )->catch( 'download-selection' ),
				'value' => Z::union(
					array(
						Z::object( array( 'type' => Z::literal( 'all-downloads' ) ) ),
						Z::object(
							array(
								'type'  => Z::literal( 'some-downloads' ),
								'value' => Z::union( array( $download_ids, $download_taxonomies ) ),
							)
						),
					)
				)->default( array( 'type' => 'all-downloads' ) )->catch( array( 'type' => 'all-downloads' ) ),
			)
		)->catch(
			array(
				'type'  => 'download-selection',
				'value' => array( 'type' => 'all-downloads' ),
			)
		);
	}

	$parsed = $schema->safe_parse( $attributes );
	assert( $parsed['success'] );
	/** @var TAttributes */
	return $parsed['data'];
}
add_filter( 'nab_nab/edd-order_sanitize_conversion_action_attributes', __NAMESPACE__ . '\sanitize' );

/**
 * Sanitizes goal attributes that configure Easy Digital Downloads purchase statuses.
 *
 * @param TAttributes $attributes Goal attributes.
 * @param TGoal       $goal       Goal.
 *
 * @return TAttributes Sanitized goal attributes.
 */
function sanitize_goal_attributes( $attributes, $goal ) {
	$action_types = array_map( fn( $action ) => $action['type'], $goal['conversionActions'] );
	if ( ! in_array( 'nab/edd-order', $action_types, true ) ) {
		return $attributes;
	}

	if ( ! array_key_exists( 'orderStatusForConversion', $attributes ) ) {
		return $attributes;
	}

	$statuses = function_exists( 'edd_get_payment_statuses' ) ? edd_get_payment_statuses() : array();
	$status   = $attributes['orderStatusForConversion'];
	if ( ! is_string( $status ) || ! array_key_exists( $status, $statuses ) ) {
		if ( array_key_exists( 'complete', $statuses ) ) {
			$attributes['orderStatusForConversion'] = 'complete';
		} elseif ( ! empty( $statuses ) ) {
			$attributes['orderStatusForConversion'] = (string) array_key_first( $statuses );
		} else {
			unset( $attributes['orderStatusForConversion'] );
		}
	}

	return $attributes;
}
add_filter( 'nab_sanitize_goal_attributes', __NAMESPACE__ . '\sanitize_goal_attributes', 10, 2 );
