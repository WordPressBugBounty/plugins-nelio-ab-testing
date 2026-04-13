<?php

namespace Nelio_AB_Testing\Experiment_Library\Template_Experiment;

defined( 'ABSPATH' ) || exit;

use Nelio_AB_Testing\Zod\Schema;
use Nelio_AB_Testing\Zod\Zod as Z;

/**
 * Sanitizes control attributes.
 *
 * @param TAttributes $control Control.
 *
 * @return TTemplate_Control_Attributes
 */
function sanitize_control_attributes( $control ) {
	/** @var Schema|null */
	static $schema;
	if ( empty( $schema ) ) {
		$schema = Z::union(
			array(
				Z::object(
					array(
						'builder'    => Z::string()->trim(),
						'context'    => Z::string()->trim()->catch( '' ),
						'templateId' => Z::string()->trim()->catch( '' ),
						'name'       => Z::string()->trim()->catch( '' ),
					)
				),
				Z::object(
					array(
						'postType'   => Z::string()->trim()->catch( '' ),
						'templateId' => Z::string()->trim()->catch( '' ),
						'name'       => Z::string()->trim()->catch( '' ),
					)
				),
			)
		)->catch(
			array(
				'postType'   => '',
				'templateId' => '',
				'name'       => '',
			)
		);
	}

	$parsed = $schema->safe_parse( $control );
	assert( $parsed['success'] );
	/** @var TTemplate_Control_Attributes */
	return $parsed['data'];
}
add_filter( 'nab_nab/template_sanitize_control_attributes', __NAMESPACE__ . '\sanitize_control_attributes' );

/**
 * Sanitizes alternative attributes.
 *
 * @param TAttributes $alternative Alternative.
 *
 * @return TTemplate_Alternative_Attributes
 */
function sanitize_alternative_attributes( $alternative ) {
	/** @var Schema|null */
	static $schema;
	if ( empty( $schema ) ) {
		$schema = Z::object(
			array(
				'name'       => Z::string()->default( '' )->trim(),
				'templateId' => Z::string()->default( '' )->trim(),
			)
		);
	}

	$parsed = $schema->safe_parse( $alternative );
	assert( $parsed['success'] );
	/** @var TTemplate_Alternative_Attributes */
	return $parsed['data'];
}
add_filter( 'nab_nab/template_sanitize_alternative_attributes', __NAMESPACE__ . '\sanitize_alternative_attributes' );
