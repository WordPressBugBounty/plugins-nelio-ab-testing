<?php

namespace Nelio_AB_Testing\Compat\Nelio_Popups;

defined( 'ABSPATH' ) || exit;

function is_testing_nelio_popup( $experiment ) {
	$experiment = is_numeric( $experiment ) ? nab_get_experiment( $experiment ) : $experiment;
	if ( is_wp_error( $experiment ) ) {
		return false;
	}//end if

	$control = $experiment->get_alternative( 'control' );
	return is_nelio_popup( $control );
}//end is_testing_nelio_popup()

function is_nelio_popup( $control ) {
	return (
		'nelio_popup' === nab_array_get( $control, 'attributes.postType' ) ||
		'nelio_popup' === nab_array_get( $control, 'postType' )
	);
}//end is_nelio_popup()
