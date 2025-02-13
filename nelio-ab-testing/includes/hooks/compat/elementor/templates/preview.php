<?php

namespace Nelio_AB_Testing\Compat\Elementor\Templates;

defined( 'ABSPATH' ) || exit;

use function add_filter;

add_filter(
	'nab_nab/template_preview_link_alternative',
	function ( $preview_link, $alternative, $control ) {
		if ( ! is_elementor_template_control( $control ) ) {
			return $preview_link;
		}//end if

		$template_id = $alternative['templateId'];
		return get_preview_post_link( $template_id );
	},
	10,
	3
);

add_filter(
	'nab_simulate_anonymous_visitor',
	function ( $enabled ) {
		if ( ! nab_is_preview() ) {
			return $enabled;
		}//end if

		$experiment = isset( $_GET['experiment'] ) ? absint( $_GET['experiment'] ) : 0; // phpcs:ignore
		$experiment = nab_get_experiment( $experiment );
		if ( is_wp_error( $experiment ) ) {
			return $enabled;
		}//end if

		if ( ! is_elementor_template_experiment( $experiment ) ) {
			return $enabled;
		}//end if

		return false;
	},
	99
);
