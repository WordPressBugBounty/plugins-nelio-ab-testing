<?php

namespace Nelio_AB_Testing\Conversion_Action_Library\Form_Submission;

defined( 'ABSPATH' ) || exit;

add_filter( 'nab_conversion_action_types', __NAMESPACE__ . '\register_type' );

/**
 * Registers the form-submission conversion action type definition.
 *
 * @param array<string,TConversion_Action_Type_Definition> $types Registered definitions.
 *
 * @return array<string,TConversion_Action_Type_Definition>
 */
function register_type( $types ) {
	$controller = new \Nelio_AB_Testing_Post_REST_Controller();
	$post_types = $controller->get_post_types();
	$post_types = wp_list_pluck( $post_types, 'kind' );
	$post_types = array_filter( $post_types, static fn( $v ) => 'form' === $v );
	if ( empty( $post_types ) ) {
		return $types;
	}

	$types['nab/form-submission'] = array(
		'name'                => 'nab/form-submission',
		'category'            => 'interaction',
		'title'               => _x( 'Form submission', 'text', 'nelio-ab-testing' ),
		'description'         => _x( 'Counts submission of a selected form as a conversion.', 'text', 'nelio-ab-testing' ),
		'defaults'            => array(
			'formType' => '',
			'formId'   => 0,
		),
		'supportsCustomScope' => true,
	);
	return $types;
}
