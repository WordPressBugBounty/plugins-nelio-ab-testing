<?php

namespace Nelio_AB_Testing\Compat\Elementor\Templates;

defined( 'ABSPATH' ) || exit;

use function add_filter;

/**
 * Callback to get the preview link for the alternative template.
 *
 * @param string|false                                                  $preview_link Preview link.
 * @param TTemplate_Control_Attributes|TTemplate_Alternative_Attributes $alternative  Alternative.
 * @param TTemplate_Control_Attributes                                  $control      Alternative.
 *
 * @return string|false
 */
function get_preview_link_for_alternative( $preview_link, $alternative, $control ) {
	if ( ! is_elementor_template_control( $control ) ) {
		return $preview_link;
	}

	$template_id  = absint( $alternative['templateId'] );
	$preview_link = get_preview_post_link( $template_id );
	$preview_link = is_string( $preview_link ) ? $preview_link : false;
	return $preview_link;
}
add_filter( 'nab_nab/template_preview_link_alternative', __NAMESPACE__ . '\get_preview_link_for_alternative', 10, 3 );

add_filter(
	'nab_simulate_anonymous_visitor',
	function ( $enabled ) {
		if ( ! nab_is_preview() ) {
			return $enabled;
		}

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$experiment = isset( $_GET['experiment'] ) ? absint( $_GET['experiment'] ) : 0;
		$experiment = nab_get_experiment( $experiment );
		if ( is_wp_error( $experiment ) ) {
			return $enabled;
		}

		if ( ! is_elementor_template_experiment( $experiment ) ) {
			return $enabled;
		}

		return false;
	},
	99
);
