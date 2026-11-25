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
				'chance'                     => Z::number()->optional()->catch( null ),
				'postId'                     => Z::number()->transform( fn( $v ) => absint( $v ) )->catch( 0 ),
				'postType'                   => Z::string()->catch( '' ),
				'useControlUrl'              => Z::boolean()->optional()->transform( 'nab_nullify' )->catch( null ),
				'testAgainstExistingContent' => Z::boolean()->optional()->transform( 'nab_nullify' )->catch( null ),
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
 * Callback to make sure a page test uses the page post type.
 *
 * @param TPost_Control_Attributes $control Control attributes.
 *
 * @return TPost_Control_Attributes
 */
function sanitize_post_type_for_page_tests( $control ) {
	$control['postType'] = 'page';
	return $control;
}
add_filter( 'nab_nab/page_sanitize_control_attributes', __NAMESPACE__ . '\sanitize_post_type_for_page_tests' );

/**
 * Callback to make sure a post test uses the post post type.
 *
 * @param TPost_Control_Attributes $control Control attributes.
 *
 * @return TPost_Control_Attributes
 */
function sanitize_post_type_for_post_tests( $control ) {
	$control['postType'] = 'post';
	return $control;
}
add_filter( 'nab_nab/post_sanitize_control_attributes', __NAMESPACE__ . '\sanitize_post_type_for_post_tests' );

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
				'name'              => Z::string()->default( '' )->trim()->catch( '' ),
				'chance'            => Z::number()->optional()->catch( null ),
				'postId'            => Z::number()->transform( fn( $v ) => absint( $v ) )->default( 0 )->catch( 0 ),
				'isExistingContent' => Z::boolean()->optional()->transform( 'nab_nullify' )->catch( null ),
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
	/** @var TPost_Alternative_Attributes */
	return $parsed['data'];
}
add_filter( 'nab_nab/page_sanitize_alternative_attributes', __NAMESPACE__ . '\sanitize_alternative_attributes' );
add_filter( 'nab_nab/post_sanitize_alternative_attributes', __NAMESPACE__ . '\sanitize_alternative_attributes' );
add_filter( 'nab_nab/custom-post-type_sanitize_alternative_attributes', __NAMESPACE__ . '\sanitize_alternative_attributes' );
