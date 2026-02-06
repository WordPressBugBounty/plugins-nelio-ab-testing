<?php

namespace Nelio_AB_Testing\Experiment_Library\Url_Experiment;

defined( 'ABSPATH' ) || exit;

/**
 * Callback to sanitize control and alternatibe attributes.
 *
 * @param TUrl_Alternative_Attributes|TUrl_Control_Attributes $attrs Attrs.
 *
 * @return TUrl_Alternative_Attributes|TUrl_Control_Attributes
 */
function sanitize_attributes( $attrs ) {
	/** @var TUrl_Alternative_Attributes|TUrl_Control_Attributes */
	$defaults = array(
		'url'  => '',
		'name' => '',
	);

	/** @var TUrl_Alternative_Attributes|TUrl_Control_Attributes */
	$result = wp_parse_args( $attrs, $defaults );
	if ( empty( $result['useControlUrl'] ) ) {
		unset( $result['useControlUrl'] );
	}

	return $result;
}
add_filter( 'nab_nab/url_sanitize_control_attributes', __NAMESPACE__ . '\sanitize_attributes' );
add_filter( 'nab_nab/url_sanitize_alternative_attributes', __NAMESPACE__ . '\sanitize_attributes' );
