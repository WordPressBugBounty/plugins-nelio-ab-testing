<?php

namespace Nelio_AB_Testing\Experiment_Library\Post_Experiment;

defined( 'ABSPATH' ) || exit;

use Nelio_AB_Testing\Zod\Schema;
use Nelio_AB_Testing\Zod\Zod as Z;

/**
 * Callback to sanitize control attributes.
 *
 * @param TAttributes $control Control attributes.
 *
 * @return TPost_Control_Attributes
 */
function sanitize_control_attributes( $control ) {
	/** @var Schema|null */
	static $schema;
	if ( empty( $schema ) ) {
		$schema = Z::object(
			array(
				'postId'                     => Z::number()->catch( 0 ),
				'postType'                   => Z::string()->catch( '' ),
				'useControlUrl'              => Z::boolean()->optional()->transform( 'nab_nullify' ),
				'testAgainstExistingContent' => Z::boolean()->optional()->transform( 'nab_nullify' ),
			)
		)->transform(
			function ( $v ) {
				/** @var TPost_Control_Attributes $v */
				if ( empty( $v['testAgainstExistingContent'] ) ) {
					unset( $v['useControlUrl'] );
				}
				return $v;
			}
		)->catch(
			array(
				'postId'   => 0,
				'postType' => '',
			)
		);
	}

	$parsed = $schema->safe_parse( $control );
	assert( $parsed['success'] );
	/** @var TPost_Control_Attributes */
	return $parsed['data'];
}
add_filter( 'nab_nab/page_sanitize_control_attributes', __NAMESPACE__ . '\sanitize_control_attributes' );
add_filter( 'nab_nab/post_sanitize_control_attributes', __NAMESPACE__ . '\sanitize_control_attributes' );
add_filter( 'nab_nab/custom-post-type_sanitize_control_attributes', __NAMESPACE__ . '\sanitize_control_attributes' );

/**
 * Callback to sanitize alternative attributes.
 *
 * @param TAttributes $alternative Alternative attributes.
 *
 * @return TPost_Alternative_Attributes
 */
function sanitize_alternative_attributes( $alternative ) {
	/** @var Schema|null */
	static $schema;
	if ( empty( $schema ) ) {
		$schema = Z::object(
			array(
				'name'              => Z::string()->default( '' )->trim(),
				'postId'            => Z::number()->default( 0 ),
				'isExistingContent' => Z::boolean()->optional()->transform( 'nab_nullify' ),
			)
		);
	}

	$parsed = $schema->safe_parse( $alternative );
	assert( $parsed['success'] );
	/** @var TPost_Alternative_Attributes */
	return $parsed['data'];
}
add_filter( 'nab_nab/page_sanitize_alternative_attributes', __NAMESPACE__ . '\sanitize_alternative_attributes' );
add_filter( 'nab_nab/post_sanitize_alternative_attributes', __NAMESPACE__ . '\sanitize_alternative_attributes' );
add_filter( 'nab_nab/custom-post-type_sanitize_alternative_attributes', __NAMESPACE__ . '\sanitize_alternative_attributes' );
