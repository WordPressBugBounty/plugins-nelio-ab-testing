<?php

namespace Nelio_AB_Testing\Conversion_Action_Library\Custom_Event;

defined( 'ABSPATH' ) || exit;

use function add_action;
use function add_filter;

use Nelio_AB_Testing\Zod\Schema;
use Nelio_AB_Testing\Zod\Zod as Z;

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

	/** @var Schema|null */
	static $schema;
	if ( empty( $schema ) ) {
		$schema = Z::object(
			array(
				'snippet' => Z::string()->trim()->catch( '' ),
			)
		)->catch(
			array(
				'snippet' => '',
			)
		);
	}

	// NOTE. Compatibility with old conversion actions.
	$status = $experiment->get_status();
	if ( 'running' === $status || 'finished' === $status ) {
		return $attributes;
	}

	$parsed = $schema->safe_parse( $attributes );
	assert( $parsed['success'] );
	/** @var TAttributes */
	return $parsed['data'];
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
		function ( $goal ) use ( &$experiment, &$draft ) {
			$goal['conversionActions'] = array_map(
				function ( $action ) use ( &$experiment, &$draft ) {
					if ( 'nab/custom-event' !== $action['type'] ) {
						return $action;
					}
					$attributes = sanitize_conversion_action_attributes( $action['attributes'], $action, $experiment );
					$draft      = $draft || empty( $attributes['snippet'] );
					return array_merge( $action, array( 'attributes' => $attributes ) );
				},
				$goal['conversionActions']
			);
			return $goal;
		},
		$experiment->get_goals()
	);
	$experiment->set_goals( $goals );
	if ( $draft ) {
		$experiment->set_status( 'draft' );
	}
}
add_action( 'nab_duplicate_experiment', __NAMESPACE__ . '\duplicate_experiment' );
