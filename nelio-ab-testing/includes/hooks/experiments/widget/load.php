<?php

namespace Nelio_AB_Testing\Experiment_Library\Widget_Experiment;

defined( 'ABSPATH' ) || exit;

use function add_action;
use function add_filter;
use function array_filter;
use function array_keys;
use function str_replace;
use function strpos;

add_filter( 'nab_nab/widget_experiment_priority', fn() => 'high' );

/**
 * Callback to add required hooks to load alternative content.
 *
 * @param TWidget_Alternative_Attributes|TWidget_Control_Attributes $alternative    Alternative.
 * @param TWidget_Control_Attributes                                $control        Control.
 * @param int                                                       $experiment_id  Experiment ID.
 * @param string                                                    $alternative_id Alternative ID.
 *
 * @return void
 */
function load_alternative( $alternative, $control, $experiment_id, $alternative_id ) {

	if ( 'control' === $alternative_id ) {
		return;
	}

	$prefix = get_sidebar_prefix( $experiment_id, $alternative_id );

	add_filter(
		'sidebars_widgets',
		function ( $sidebars_widgets ) use ( $prefix ) {
			/** @var array<string,list<mixed>> $sidebars_widgets */
			$sidebars_widgets = $sidebars_widgets;

			$sidebars_widgets = array_filter(
				$sidebars_widgets,
				function ( $sidebar ) use ( $prefix ) {
					/** @var string $sidebar */

					return 0 === strpos( $sidebar, $prefix );
				},
				ARRAY_FILTER_USE_KEY
			);

			$keys = array_keys( $sidebars_widgets );
			foreach ( $keys as $key ) {
				$new_key                      = str_replace( $prefix, '', $key );
				$sidebars_widgets[ $new_key ] = $sidebars_widgets[ $key ];
				unset( $sidebars_widgets[ $key ] );
			}

			return $sidebars_widgets;
		}
	);
}
add_action( 'nab_nab/widget_load_alternative', __NAMESPACE__ . '\load_alternative', 10, 4 );
