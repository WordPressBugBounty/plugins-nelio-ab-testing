<?php

namespace Nelio_AB_Testing\Compat\Elementor\Templates;

defined( 'ABSPATH' ) || exit;

use function add_action;
use function add_filter;
use function remove_action;

add_action(
	'plugins_loaded',
	function () {
		if ( is_admin() ) {
			return;
		}

		if ( ! did_action( 'elementor/loaded' ) ) {
			return;
		}

		// Add a custom hook to compute relevant Elementor template tests.
		// We need this because default’s “mid” priority checks, which should work,
		// simply don’t.
		add_action( 'parse_query', __NAMESPACE__ . '\compute_relevant_elementor_template_experiments', 100 );
		add_filter( 'nab_nab/template_experiment_priority', __NAMESPACE__ . '\fix_elementor_template_experiment_priority', 20, 2 );

		add_filter( 'nab_is_nab/template_php_scope_relevant', __NAMESPACE__ . '\is_elementor_template_experiment_relevant', 10, 3 );
	}
);

/**
 * Callback to tweak experiment priority.
 *
 * @param 'low'|'mid'|'high'|'custom'  $priority Priority.
 * @param TTemplate_Control_Attributes $control  Control.
 *
 * @return 'low'|'mid'|'high'|'custom'
 */
function fix_elementor_template_experiment_priority( $priority, $control ) {
	return is_elementor_template_control( $control ) ? 'custom' : $priority;
}

/**
 * Callback to compute which template experiments are relevant.
 *
 * @return void
 */
function compute_relevant_elementor_template_experiments() {
	/** @var \WP_Query|null $wp_query */
	global $wp_query;
	if ( empty( $wp_query ) || ! $wp_query->is_main_query() ) {
		return;
	}
	remove_action( 'parse_query', __NAMESPACE__ . '\compute_relevant_elementor_template_experiments', 100 );

	// First, get the experiments that are potentially relevant.
	$experiments = get_running_elementor_template_experiments();
	if ( empty( $experiments ) ) {
		return;
	}

	// Second, get the relevant experiments.
	$runtime = \Nelio_AB_Testing_Runtime::instance();
	add_action(
		'wp',
		function () use ( &$runtime, &$experiments ) {
			/** @var list<int> */
			$relevant = wp_list_pluck( $experiments, 'ID' );
			$relevant = array_filter( $relevant, array( $runtime, 'is_custom_priority_experiment_relevant' ) );
			$relevant = array_values( $relevant );
			foreach ( $relevant as $re ) {
				$runtime->add_custom_priority_experiment( $re );
			}
		}
	);

	// Third, get the alternative we’re supposed to see.
	$alt = nab_get_requested_alternative();
	if ( ! is_alternative_content_potentially_required( $experiments, $alt ) ) {
		// If we’re supposed to see control, there’s no need to add any extra hooks.
		return;
	}

	// Fourth, prepare a data structure to know what template
	// replacements should be applied.
	$template_mapping = array_reduce(
		$experiments,
		function ( $result, $e ) use ( $alt ) {
			/** @var array<int,int>               $result */
			/** @var \Nelio_AB_Testing_Experiment $e      */

			$control      = $e->get_alternative( 'control' );
			$control      = absint( $control['attributes']['templateId'] ?? 0 );
			$alternatives = $e->get_alternatives();
			$alternative  = $alternatives[ $alt % count( $alternatives ) ];
			$alternative  = absint( $alternative['attributes']['templateId'] ?? 0 );

			$result[ $control ] = $alternative;
			return $result;
		},
		array()
	);

	// Finally, hook into Elementor to apply template replacements.
	add_filter(
		'elementor/theme/get_location_templates/template_id',
		function ( $template_id ) use ( $template_mapping ) {
			/** @var string $template_id */

			return ! empty( $template_mapping[ $template_id ] ) ? $template_mapping[ $template_id ] : $template_id;
		}
	);
}

// =======
// HELPERS
// =======

/**
 * Returns running elementor template experiments.
 *
 * @return list<\Nelio_AB_Testing_Experiment>
 */
function get_running_elementor_template_experiments() {
	$exps = nab_get_running_experiments();
	$exps = array_filter( $exps, __NAMESPACE__ . '\is_elementor_template_experiment' );
	return array_values( $exps );
}

/**
 * Whether the experiment is an Elementor template experiment.
 *
 * @param \Nelio_AB_Testing_Experiment $experiment Experiment.
 *
 * @return bool
 */
function is_elementor_template_experiment( $experiment ) {
	$control = $experiment->get_alternative( 'control' );
	return (
		'nab/template' === $experiment->get_type() &&
		is_elementor_template_control( $control['attributes'] )
	);
}

/**
 * Whether it’s testing an Elementor template or not.
 *
 * @param TAttributes $control Control.
 *
 * @return bool
 * @phpstan-assert-if-true TTemplate_Page_Builder_Control_Attributes $control
 */
function is_elementor_template_control( $control ) {
	return ! empty( $control['builder'] ) && 'elementor' === $control['builder'];
}

/**
 * Whether the experiment (as defined by its control variant) is relevant or not.
 *
 * @param bool                         $is_relevant Whether it’s relevant.
 * @param TTemplate_Control_Attributes $control     Control.
 * @param int                          $exp_id      Experiment ID.
 *
 * @return bool
 */
function is_elementor_template_experiment_relevant( $is_relevant, $control, $exp_id ) {

	if ( ! is_elementor_template_control( $control ) ) {
		return $is_relevant;
	}

	$context    = $control['context'];
	$experiment = nab_get_experiment( $exp_id );
	if ( is_wp_error( $experiment ) ) {
		return $is_relevant;
	}

	$alternative_template_ids = $experiment->get_alternatives();
	$alternative_template_ids = wp_list_pluck( $alternative_template_ids, 'attributes' );
	$alternative_template_ids = wp_list_pluck( $alternative_template_ids, 'templateId' );
	$alternative_template_ids = array_map( 'absint', $alternative_template_ids );

	if ( 'archive' === $context ) {
		return is_archive();
	}

	if ( 'search-results' === $context ) {
		return is_search();
	}

	if ( 'error-404' === $context ) {
		return is_404();
	}

	if ( 'single-page' === $context && ! is_page() ) {
		return false;
	}

	if ( 'single-post' === $context && ! is_singular() ) {
		return false;
	}

	if ( 'product' === $context && ( ! function_exists( 'is_product' ) || ! is_product() ) ) {
		return false;
	}

	if ( 'footer' === $context || 'header' === $context ) {
		return in_array( get_elementor_template_id_in( $context ), $alternative_template_ids, true );
	}

	return in_array( get_elementor_template_id_in( 'single' ), $alternative_template_ids, true );
}

/**
 * Returns the elementor template ID in the given context.
 *
 * @param string $context Context.
 *
 * @return false|int
 */
function get_elementor_template_id_in( $context ) {
	$manager                = \ElementorPro\Plugin::instance()->modules_manager->get_modules( 'theme-builder' )->get_conditions_manager();
	$templates_by_condition = $manager->get_documents_for_location( $context );
	if ( empty( $templates_by_condition ) ) {
		return false;
	}

	$template    = reset( $templates_by_condition );
	$template_id = $template->get_post()->ID;
	return $template_id;
}

/**
 * Whther alternative content is potentially required or not.
 *
 * @param list<\Nelio_AB_Testing_Experiment> $experiments Experiments.
 * @param int                                $alt         Alternative value.
 *
 * @return bool
 */
function is_alternative_content_potentially_required( $experiments, $alt ) {
	foreach ( $experiments as $exp ) {
		$alt_count = count( $exp->get_alternatives() );
		$variant   = $alt % $alt_count;
		if ( ! empty( $variant ) ) {
			return true;
		}
	}
	return false;
}
