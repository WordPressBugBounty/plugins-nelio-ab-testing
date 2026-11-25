<?php

namespace Nelio_AB_Testing\Segmentation_Rule_Library\Referring_Url;

defined( 'ABSPATH' ) || exit;

add_filter( 'nab_segmentation_rule_types', __NAMESPACE__ . '\register_type' );

/**
 * Registers the referring url segmentation rule type definition.
 *
 * @param array<string,TSegmentation_Rule_Type_Definition> $types Registered definitions.
 *
 * @return array<string,TSegmentation_Rule_Type_Definition>
 */
function register_type( $types ) {
	$types['nab/referring-url'] = array(
		'name'        => 'nab/referring-url',
		'title'       => _x( 'Referring URL', 'text', 'nelio-ab-testing' ),
		'description' => _x( 'Segments visitors according to document.referrer, the URL that referred them to the site.', 'text', 'nelio-ab-testing' ),
		'category'    => 'nab/source',
		'singleton'   => true,
		'defaults'    => array(
			'condition' => 'is-equal-to',
			'value'     => '',
		),
	);
	return $types;
}
