<?php

namespace Nelio_AB_Testing\Conversion_Action_Library\Page_View;

defined( 'ABSPATH' ) || exit;

add_filter( 'nab_conversion_action_types', __NAMESPACE__ . '\register_type' );

/**
 * Registers the page-view conversion action type definition.
 *
 * @param array<string,TConversion_Action_Type_Definition> $types Registered definitions.
 *
 * @return array<string,TConversion_Action_Type_Definition>
 */
function register_type( $types ) {
	$types['nab/page-view'] = array(
		'name'        => 'nab/page-view',
		'category'    => 'view',
		'title'       => _x( 'Page view', 'text', 'nelio-ab-testing' ),
		'description' => _x( 'Counts a visit to a selected page or URL as a conversion.', 'text', 'nelio-ab-testing' ),
		'defaults'    => array(
			'mode'     => 'id',
			'postType' => 'page',
			'postId'   => 0,
			'url'      => '',
		),
	);
	return $types;
}
