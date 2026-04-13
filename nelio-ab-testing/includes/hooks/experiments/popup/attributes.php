<?php

namespace Nelio_AB_Testing\Experiment_Library\Popup_Experiment;

defined( 'ABSPATH' ) || exit;

use Nelio_AB_Testing\Zod\Schema;
use Nelio_AB_Testing\Zod\Zod as Z;

/**
 * Sanitizes control attributes.
 *
 * @param TAttributes $control Control.
 *
 * @return TPopup_Control_Attributes
 */
function sanitize_control_attributes( $control ) {
	/** @var Schema|null */
	static $schema;
	if ( empty( $schema ) ) {
		$schema = Z::object(
			array(
				'postId'   => Z::number()->catch( 0 ),
				'postType' => Z::string()->catch( '' ),
			)
		)->catch(
			array(
				'postId'   => 0,
				'postType' => '',
			)
		);
	}

	$parsed = $schema->safe_parse( $control );
	assert( $parsed['success'] );
	/** @var TPopup_Control_Attributes */
	return $parsed['data'];
}
add_filter( 'nab_nab/popup_sanitize_control_attributes', __NAMESPACE__ . '\sanitize_control_attributes' );

/**
 * Sanitizes alternative attributes.
 *
 * @param TAttributes $alternative Alternative.
 *
 * @return TPopup_Alternative_Attributes
 */
function sanitize_alternative_attributes( $alternative ) {
	/** @var Schema|null */
	static $schema;
	if ( empty( $schema ) ) {
		$schema = Z::object(
			array(
				'name'   => Z::string()->default( '' )->trim(),
				'postId' => Z::number()->default( 0 ),
			)
		);
	}

	$parsed = $schema->safe_parse( $alternative );
	assert( $parsed['success'] );
	/** @var TPopup_Alternative_Attributes */
	return $parsed['data'];
}
add_filter( 'nab_nab/popup_sanitize_alternative_attributes', __NAMESPACE__ . '\sanitize_alternative_attributes' );
