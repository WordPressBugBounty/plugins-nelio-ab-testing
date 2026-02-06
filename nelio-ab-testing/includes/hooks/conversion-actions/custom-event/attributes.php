<?php

namespace Nelio_AB_Testing\Conversion_Action_Library\Custom_Event;

defined( 'ABSPATH' ) || exit;

use function add_action;
use function add_filter;

/**
 * Sanitizes conversion action attributes.
 *
 * @param TAttributes                  $attributes Attributes.
 * @param TConversion_Action           $action Action.
 * @param \Nelio_AB_Testing_Experiment $experiment Experiment.
 *
 * @return TAttributes
 */
function sanitize_conversion_action_attributes( $attributes, $action, $experiment ) {
	if ( 'nab/custom-event' !== $action['type'] ) {
		return $attributes;
	}

	// NOTE. Compatibility with old conversion actions.
	$status = $experiment->get_status();
	if ( 'running' === $status || 'finished' === $status ) {
		return $attributes;
	}

	$snippet = $attributes['snippet'] ?? '';
	$snippet = is_string( $snippet ) ? $snippet : '';
	return array( 'snippet' => trim( $snippet ) );
}
add_filter( 'nab_sanitize_conversion_action_attributes', __NAMESPACE__ . '\sanitize_conversion_action_attributes', 10, 3 );

/**
 * Updates custom event conversion actions when duplicating tests.
 *
 * @param \Nelio_AB_Testing_Experiment $experiment Experiment.
 *
 * @return void
 */
function duplicate_experiment( $experiment ) {
	$draft = false;
	$goals = array_map(
		function ( $goal ) use ( &$draft ) {
			$actions = $goal['conversionActions'];
			$actions = array_map(
				function ( $action ) use ( &$draft ) {
					if ( 'nab/custom-event' !== $action['type'] ) {
						return $action;
					}
					$attributes = array(
						'snippet' => $action['attributes']['snippet'] ?? '',
					);
					$draft      = $draft || empty( $attributes['snippet'] );
					return array_merge( $action, array( 'attributes' => $attributes ) );
				},
				$actions
			);
			return array_merge( $goal, array( 'conversionActions' => $actions ) );
		},
		$experiment->get_goals()
	);
	$experiment->set_goals( $goals );
	if ( $draft ) {
		$experiment->set_status( 'draft' );
	}
}
add_action( 'nab_duplicate_experiment', __NAMESPACE__ . '\duplicate_experiment' );
