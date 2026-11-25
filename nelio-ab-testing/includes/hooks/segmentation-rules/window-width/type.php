<?php

namespace Nelio_AB_Testing\Segmentation_Rule_Library\Window_Width;

defined( 'ABSPATH' ) || exit;

add_filter( 'nab_segmentation_rule_types', __NAMESPACE__ . '\register_type' );

/**
 * Registers the window width segmentation rule type definition.
 *
 * @param array<string,TSegmentation_Rule_Type_Definition> $types Registered definitions.
 *
 * @return array<string,TSegmentation_Rule_Type_Definition>
 */
function register_type( $types ) {
	$types['nab/window-width'] = array(
		'name'        => 'nab/window-width',
		'title'       => _x( 'Window Width', 'text', 'nelio-ab-testing' ),
		'description' => _x( 'Segments visitors according to their browser window width.', 'text', 'nelio-ab-testing' ),
		'category'    => 'nab/technology',
		'singleton'   => true,
		'defaults'    => array(
			'condition' => 'is-greater-than',
			'value'     => 800,
			'interval'  => array(
				'min' => 800,
				'max' => 1200,
			),
		),
	);
	return $types;
}
