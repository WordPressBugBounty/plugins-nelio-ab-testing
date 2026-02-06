<?php

namespace Nelio_AB_Testing\Conversion_Action_Library\Custom_Event;

defined( 'ABSPATH' ) || exit;

use function add_filter;

/**
 * Summarizes the conversion action.
 *
 * @param array{snippet?:string} $attributes    Attributes.
 * @param int                    $experiment_id Experiment ID.
 * @param int                    $goal_index    Goal index.
 *
 * @return array{snippet?:string}
 */
function summarize_conversion_action( $attributes, $experiment_id, $goal_index ) {
	if ( ! empty( $attributes['snippet'] ) ) {
		$convert_function = sprintf(
			'() => window.nab?.convert?.( %d, %d )',
			$experiment_id,
			$goal_index
		);

		$utils_object = '{
			onVariantReady: ( callback ) => window.nab?.ready?.( callback ),
		}';

		$snippet = sprintf(
			'( ( convert, utils ) => { %1$s } )( %2$s, %3$s )',
			$attributes['snippet'] . "\n",
			$convert_function,
			$utils_object
		);

		return array( 'snippet' => nab_minify_js( $snippet ) );
	}

	if ( isset( $attributes['snippet'] ) ) {
		unset( $attributes['snippet'] );
	}

	return $attributes;
}
add_filter( 'nab_get_nab/custom-event_conversion_action_summary', __NAMESPACE__ . '\summarize_conversion_action', 10, 3 );
