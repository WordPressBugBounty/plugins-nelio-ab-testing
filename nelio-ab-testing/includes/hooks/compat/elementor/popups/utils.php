<?php

namespace Nelio_AB_Testing\Compat\Elementor\Popups;

defined( 'ABSPATH' ) || exit;

/**
 * Whether the experiment is testing an Elementor popup.
 *
 * @param \Nelio_AB_Testing_Experiment|int $experiment Experiment.
 *
 * @return bool
 */
function is_testing_elementor_popup( $experiment ) {
	$experiment = is_numeric( $experiment ) ? nab_get_experiment( $experiment ) : $experiment;
	if ( is_wp_error( $experiment ) ) {
		return false;
	}

	$control = $experiment->get_alternative( 'control' );
	return is_elementor( $control );
}

/**
 * Whether the control variant is testing a Nelio Popup or not.
 *
 * @param TAlternative|TAttributes $control Control attributes.
 *
 * @return bool
 */
function is_elementor( $control ) {
	$type = isset( $control['attributes'] ) && is_array( $control['attributes'] )
		? ( $control['attributes']['postType'] ?? '' )
		: ( $control['postType'] ?? '' );
	return 'nab_elementor_popup' === $type;
}
