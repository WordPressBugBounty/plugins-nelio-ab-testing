<?php

namespace Nelio_AB_Testing\Experiment_Library\Heatmap_Experiment;

defined( 'ABSPATH' ) || exit;

/**
 * Registers the AI definition for the nab/heatmap experiment type.
 *
 * @return TExperiment_Type_AI
 */
function register_ai_definition() {
	return array(
		'selection' => array(
			'strategy'                => 'explicit_only',
			'requiresExplicitRequest' => true,
			'useWhen'                 => 'Use it only when the user explicitly asks to gather evidence about visitor attention or interaction patterns before deciding which page changes to A/B test; it does not compare alternative versions.',
		),
		'schema'    => array(
			'original'    => array(
				'type'                 => 'object',
				'properties'           => array(),
				'additionalProperties' => false,
			),
			'alternative' => array(
				'type'                 => 'object',
				'properties'           => array(),
				'additionalProperties' => false,
			),
		),
	);
}
add_filter( 'nab_nab/heatmap_ai_definition', __NAMESPACE__ . '\register_ai_definition' );
