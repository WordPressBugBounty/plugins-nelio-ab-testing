<?php

namespace Nelio_AB_Testing\Experiment_Library\Template_Experiment;

defined( 'ABSPATH' ) || exit;

use function add_action;
use function add_filter;

/**
 * Whether the experiment is relevant or not.
 *
 * @param bool                         $is_relevant Relevant.
 * @param TTemplate_Control_Attributes $control Control.
 *
 * @return bool
 */
function is_relevant( $is_relevant, $control ) {

	if ( '_nab_front_page_template' === $control['templateId'] ) {
		return is_front_page();
	}

	if ( ! is_singular() ) {
		return false;
	}

	if ( empty( $control['postType'] ) ) {
		return false;
	}

	if ( get_post_type() !== $control['postType'] ) {
		return false;
	}

	$current_template = get_actual_template( absint( get_the_ID() ) );
	if ( empty( $current_template ) || 'default' === $current_template ) {
		$current_template = '_nab_default_template';
	}

	if ( $control['templateId'] !== $current_template ) {
		return false;
	}

	return true;
}
add_filter( 'nab_is_nab/template_php_scope_relevant', __NAMESPACE__ . '\is_relevant', 10, 2 );

/**
 * Callback to add required hooks to load alternative content.
 *
 * @param TTemplate_Alternative_Attributes|TTemplate_Control_Attributes $alternative Alternative.
 * @param TTemplate_Control_Attributes                                  $control     Control.
 *
 * @return void
 */
function load_alternative( $alternative, $control ) {

	if ( is_page_builder_control( $control ) ) {
		return;
	}

	if ( $alternative['templateId'] === $control['templateId'] ) {
		return;
	}

	if ( '_nab_front_page_template' === $control['templateId'] ) {
		add_filter(
			'template_include',
			function ( $t ) use ( $alternative ) {
				/** @var string $t */
				return is_front_page() && strpos( $t, '/front-page.php' )
					? locate_template( $alternative['templateId'] )
					: $t;
			}
		);
		return;
	}

	add_filter(
		'get_post_metadata',
		function ( $value, $object_id, $meta_key ) use ( $alternative, $control ) {
			/** @var mixed  $value     */
			/** @var int    $object_id */
			/** @var string $meta_key  */

			if ( '_wp_page_template' !== $meta_key ) {
				return $value;
			}
			if ( get_post_type( $object_id ) !== $control['postType'] ) {
				return $value;
			}

			$value = get_actual_template( $object_id );
			if ( '_nab_default_template' === $control['templateId'] ) {
				if ( empty( $value ) || 'default' === $value ) {
					return $alternative['templateId'];
				}
				return $value;
			}

			if ( $value !== $control['templateId'] ) {
				return $value;
			}

			if ( '_nab_default_template' === $alternative['templateId'] ) {
				return null;
			}

			return $alternative['templateId'];
		},
		10,
		3
	);
}
add_action( 'nab_nab/template_load_alternative', __NAMESPACE__ . '\load_alternative', 10, 2 );

/**
 * Whether the control is testing a page builder template or not.
 *
 * @param TTemplate_Control_Attributes $control Control.
 *
 * @return bool
 *
 * @phpstan-assert-if-true TTemplate_Page_Builder_Control_Attributes $control
 */
function is_page_builder_control( $control ) {
	return ! empty( $control['builder'] );
}
