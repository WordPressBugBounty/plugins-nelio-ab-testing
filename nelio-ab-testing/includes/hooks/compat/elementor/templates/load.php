<?php

namespace Nelio_AB_Testing\Compat\Elementor\Templates;

defined( 'ABSPATH' ) || exit;

use function add_action;
use function add_filter;
use function remove_action;

add_action(
	'plugins_loaded',
	function () {
		if ( ! did_action( 'elementor/loaded' ) ) {
			return;
		}//end if

		// Add a custom hook to compute relevant Elementor template tests.
		// We need this because default’s “mid” priority checks, which should work,
		// simply don’t.
		add_action( 'parse_query', __NAMESPACE__ . '\compute_relevant_elementor_template_experiments', 100 );
		add_filter( 'nab_nab/template_experiment_priority', __NAMESPACE__ . '\fix_elementor_template_experiment_priority', 20, 2 );

		add_filter( 'nab_is_nab/template_php_scope_relevant', __NAMESPACE__ . '\is_elementor_template_experiment_relevant', 10, 3 );
	}
);

function fix_elementor_template_experiment_priority( $priority, $control ) {
	return is_elementor_template_control( $control ) ? 'custom' : $priority;
}//end fix_elementor_template_experiment_priority()

function compute_relevant_elementor_template_experiments() {
	global $wp_query;
	if ( empty( $wp_query ) || ! $wp_query->is_main_query() ) {
		return;
	}//end if
	remove_action( 'parse_query', __NAMESPACE__ . '\compute_relevant_elementor_template_experiments', 100 );

	// First, get the experiments that are potentially relevant.
	$experiments = get_running_elementor_template_experiments();

	// Second, prepare a data structure to know what template
	// replacements should be applied.
	$runtime = \Nelio_AB_Testing_Runtime::instance();
	$alt     = $runtime->get_alternative_from_request();

	$template_mapping = array_reduce(
		$experiments,
		function ( $result, $e ) use ( $alt ) {
			$control      = $e->get_alternative( 'control' );
			$control      = nab_array_get( $control, 'attributes.templateId', 0 );
			$alternatives = $e->get_alternatives();
			$alternative  = $alternatives[ $alt % count( $alternatives ) ];
			$alternative  = nab_array_get( $alternative, 'attributes.templateId', 0 );

			$result[ absint( $control ) ] = absint( $alternative );
			return $result;
		},
		array()
	);

	// Third, hook into Elementor to apply template replacements.
	add_filter(
		'elementor/theme/get_location_templates/template_id',
		function ( $template_id ) use ( $template_mapping ) {
			return ! empty( $template_mapping[ $template_id ] ) ? $template_mapping[ $template_id ] : $template_id;
		}
	);

	// Finally, get the relevant experiments.
	add_action(
		'wp',
		function () use ( &$runtime, &$experiments ) {
			$relevant = wp_list_pluck( $experiments, 'ID' );
			$relevant = array_filter( $relevant, array( $runtime, 'is_custom_priority_experiment_relevant' ) );
			$relevant = array_values( $relevant );
			foreach ( $relevant as $re ) {
				$runtime->add_custom_priority_experiment( $re );
			}//end foreach
		}
	);
}//end compute_relevant_elementor_template_experiments()

// =======
// HELPERS
// =======

function get_running_elementor_template_experiments() {
	$exps = nab_get_running_experiments();
	$exps = array_filter( $exps, __NAMESPACE__ . '\is_elementor_template_experiment' );
	return array_values( $exps );
}//end get_running_elementor_template_experiments()

function is_elementor_template_experiment( $experiment ) {
	$control = $experiment->get_alternative( 'control' );
	return (
		'nab/template' === $experiment->get_type() &&
		is_elementor_template_control( $control['attributes'] )
	);
}//end is_elementor_template_experiment()

function is_elementor_template_control( $control ) {
	return ! empty( $control['builder'] ) && 'elementor' === $control['builder'];
}//end is_elementor_template_control()

function is_elementor_template_experiment_relevant( $is_relevant, $control, $exp_id ) {

	if ( ! is_elementor_template_control( $control ) ) {
		return $is_relevant;
	}//end if

	$context    = $control['context'];
	$experiment = nab_get_experiment( $exp_id );

	$alternative_template_ids = $experiment->get_alternatives();
	$alternative_template_ids = wp_list_pluck( $alternative_template_ids, 'attributes' );
	$alternative_template_ids = wp_list_pluck( $alternative_template_ids, 'templateId' );
	$alternative_template_ids = array_map( 'absint', $alternative_template_ids );

	if ( 'archive' === $context ) {
		return is_archive();
	}//end if

	if ( 'search-results' === $context ) {
		return is_search();
	}//end if

	if ( 'error-404' === $context ) {
		return is_404();
	}//end if

	if ( 'single-page' === $context && ! is_page() ) {
		return false;
	}//end if

	if ( 'single-post' === $context && ! is_singular() ) {
		return false;
	}//end if

	if ( 'product' === $context && ( ! function_exists( 'is_product' ) || ! is_product() ) ) {
		return false;
	}//end if

	if ( 'footer' === $context || 'header' === $context ) {
		return in_array( get_elementor_template_id_in( $context ), $alternative_template_ids, true );
	}//end if

	return in_array( get_elementor_template_id_in( 'single' ), $alternative_template_ids, true );
}//end is_elementor_template_experiment_relevant()

function get_elementor_template_id_in( $context ) {
	$manager                = \ElementorPro\Plugin::instance()->modules_manager->get_modules( 'theme-builder' )->get_conditions_manager();
	$templates_by_condition = $manager->get_documents_for_location( $context );
	if ( empty( $templates_by_condition ) ) {
		return false;
	}//end if

	$template    = reset( $templates_by_condition );
	$template_id = $template->get_post()->ID;
	return $template_id;
}//end get_elementor_template_id_in()
