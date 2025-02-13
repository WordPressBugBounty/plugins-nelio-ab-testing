<?php

namespace Nelio_AB_Testing\Compat\Elementor\Popups;

defined( 'ABSPATH' ) || exit;

function is_testing_elementor_popup( $experiment ) {
	$experiment = is_numeric( $experiment ) ? nab_get_experiment( $experiment ) : $experiment;
	if ( is_wp_error( $experiment ) ) {
		return false;
	}//end if

	$control = $experiment->get_alternative( 'control' );
	return is_elementor( $control );
}//end is_testing_elementor_popup()

function is_elementor( $control ) {
	return (
		'nab_elementor_popup' === nab_array_get( $control, 'attributes.postType' ) ||
		'nab_elementor_popup' === nab_array_get( $control, 'postType' )
	);
}//end is_elementor()
