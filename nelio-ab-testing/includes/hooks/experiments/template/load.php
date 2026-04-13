<?php

namespace Nelio_AB_Testing\Experiment_Library\Template_Experiment;

defined( 'ABSPATH' ) || exit;

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
 * Callback to get alternative loaders.
 *
 * @param list<\Nelio_AB_Testing_Alternative_Loader<TTemplate_Control_Attributes,TTemplate_Alternative_Attributes>> $loaders        Loaders.
 * @param TTemplate_Alternative_Attributes|TTemplate_Control_Attributes                                             $alternative    Alternative.
 * @param TTemplate_Control_Attributes                                                                              $control        Alternative.
 * @param int                                                                                                       $experiment_id  Experiment ID.
 * @param string                                                                                                    $alternative_id Alternative ID.
 *
 * @return list<\Nelio_AB_Testing_Alternative_Loader<TTemplate_Control_Attributes,TTemplate_Alternative_Attributes>>
 */
function get_alternative_loaders( $loaders, $alternative, $control, $experiment_id, $alternative_id ) {
	if ( is_page_builder_control( $control ) ) {
		return $loaders;
	}

	if ( $alternative['templateId'] === $control['templateId'] ) {
		return $loaders;
	}

	if ( '_nab_front_page_template' === $control['templateId'] ) {
		$loaders[] = new Alternative_Front_Page_Template_Loader( $alternative, $control, $experiment_id, $alternative_id );
		return $loaders;
	}

	$loaders[] = new Alternative_WordPress_Template_Loader( $alternative, $control, $experiment_id, $alternative_id );
	return $loaders;
}
add_filter( 'nab_get_nab/template_alternative_loaders', __NAMESPACE__ . '\get_alternative_loaders', 10, 5 );

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
