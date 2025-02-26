<?php

namespace Nelio_AB_Testing\Conversion_Action_Library\Custom_Event;

defined( 'ABSPATH' ) || exit;

use function add_action;
use function add_filter;

add_filter(
	'nab_sanitize_conversion_action_attributes',
	function ( $attributes, $action, $experiment ) {
		if ( 'nab/custom-event' !== $action['type'] ) {
			return $attributes;
		}//end if

		// NOTE. Compatibility with old conversion actions.
		$status = $experiment->get_status();
		if ( 'running' === $status || 'finished' === $status ) {
			return $attributes;
		}//end if

		return array( 'snippet' => trim( $attributes['snippet'] ) );
	},
	10,
	3
);

add_action(
	'nab_duplicate_experiment',
	function ( $experiment ) {
		$draft = false;
		$goals = array_map(
			function ( $goal ) use ( &$draft ) {
				$actions = nab_array_get( $goal, 'conversionActions', array() );
				$actions = is_array( $actions ) ? $actions : array();
				$actions = array_map(
					function ( $action ) use ( &$draft ) {
						if ( 'nab/custom-event' !== $action['type'] ) {
							return $action;
						}//end if
						$attributes = array(
							'snippet' => nab_array_get( $action, 'attributes.snippet', '' ),
						);
						$draft      = $draft || empty( nab_array_get( $attributes, 'snippet', '' ) );
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
		}//end if
	}
);
