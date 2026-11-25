<?php

namespace Nelio_AB_Testing\Segmentation_Rule_Library\User_Login;

defined( 'ABSPATH' ) || exit;

add_filter( 'nab_segmentation_rule_types', __NAMESPACE__ . '\register_type' );

/**
 * Registers the user login segmentation rule type definition.
 *
 * @param array<string,TSegmentation_Rule_Type_Definition> $types Registered definitions.
 *
 * @return array<string,TSegmentation_Rule_Type_Definition>
 */
function register_type( $types ) {
	$types['nab/user-login'] = array(
		'name'        => 'nab/user-login',
		'title'       => _x( 'User Login', 'text', 'nelio-ab-testing' ),
		'description' => _x( 'Segments visitors according to whether they are logged in.', 'text', 'nelio-ab-testing' ),
		'category'    => 'nab/visitor',
		'singleton'   => true,
		'defaults'    => array( 'condition' => 'is-logged-in' ),
	);
	return $types;
}
