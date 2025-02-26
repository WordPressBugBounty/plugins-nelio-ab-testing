<?php

namespace Nelio_AB_Testing\Conversion_Action_Library\Custom_Event;

defined( 'ABSPATH' ) || exit;

use function add_filter;

add_filter(
	'nab_get_nab/custom-event_conversion_action_summary',
	function ( $attributes, $experiment_id, $goal_index ) {
		if ( ! empty( $attributes['snippet'] ) ) {
			$convert_function = sprintf(
				'() => window.nab.convert( %d, %d )',
				$experiment_id,
				$goal_index
			);

			$snippet = sprintf(
				'!! window.nab?.convert && ( ( convert ) => { %1$s } )( %2$s )',
				$attributes['snippet'],
				$convert_function
			);

			return array( 'snippet' => nab_minify_js( $snippet ) );
		}//end if

		if ( isset( $attributes['snippet'] ) ) {
			unset( $attributes['snippet'] );
		}//end if

		return $attributes;
	},
	10,
	3
);
