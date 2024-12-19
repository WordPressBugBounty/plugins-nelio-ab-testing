<?php

namespace Nelio_AB_Testing\Experiment_Library\Template_Experiment;

defined( 'ABSPATH' ) || exit;

use function add_action;
use function add_filter;

function is_relevant( $_, $control ) {

	if ( '_nab_front_page_template' === $control['templateId'] ) {
		return is_front_page();
	}//end if

	if ( ! is_singular() ) {
		return false;
	}//end if

	if ( empty( $control['postType'] ) ) {
		return false;
	}//end if

	if ( get_post_type() !== $control['postType'] ) {
		return false;
	}//end if

	$current_template = get_actual_template( get_the_ID() );
	if ( empty( $current_template ) || 'default' === $current_template ) {
		$current_template = '_nab_default_template';
	}//end if

	if ( $control['templateId'] !== $current_template ) {
		return false;
	}//end if

	return true;
}//end is_relevant()
add_filter( 'nab_is_nab/template_php_scope_relevant', __NAMESPACE__ . '\is_relevant', 10, 2 );


function load_alternative( $alternative, $control ) {

	if ( ! empty( $control['builder'] ) ) {
		return;
	}//end if

	if ( $alternative['templateId'] === $control['templateId'] ) {
		return;
	}//end if

	if ( '_nab_front_page_template' === $control['templateId'] ) {
		add_filter(
			'template_include',
			fn( $t ) => is_front_page() && strpos( $t, '/front-page.php' )
				? locate_template( $alternative['templateId'] )
				: $t
		);
		return;
	}//end if

	add_filter(
		'get_post_metadata',
		function ( $value, $object_id, $meta_key ) use ( $alternative, $control ) {

			if ( '_wp_page_template' !== $meta_key ) {
				return $value;
			}//end if
			if ( get_post_type( $object_id ) !== $control['postType'] ) {
				return $value;
			}//end if

			$value = get_actual_template( $object_id );
			if ( '_nab_default_template' === $control['templateId'] ) {
				if ( empty( $value ) || 'default' === $value ) {
					return $alternative['templateId'];
				}//end if
				return $value;
			}//end if

			if ( $value !== $control['templateId'] ) {
				return $value;
			}//end if

			if ( '_nab_default_template' === $alternative['templateId'] ) {
				return null;
			}//end if

			return $alternative['templateId'];
		},
		10,
		3
	);
}//end load_alternative()
add_action( 'nab_nab/template_load_alternative', __NAMESPACE__ . '\load_alternative', 10, 2 );
