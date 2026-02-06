<?php

namespace Nelio_AB_Testing\Experiment_Library\Theme_Experiment;

defined( 'ABSPATH' ) || exit;

use function add_action;
use function add_filter;
use function wp_get_theme;

add_filter( 'nab_nab/theme_experiment_priority', fn() => 'high' );

/**
 * Callback to add hooks to load alternative.
 *
 * @param TTheme_Alternative_Attributes|TTheme_Control_Attributes $alternative Alternative.
 *
 * @return void
 */
function load_alternative( $alternative ) {

	$theme_id = '';
	if ( isset( $alternative['themeId'] ) ) {
		$theme_id = $alternative['themeId'];
	}

	$theme = wp_get_theme( $theme_id );
	if ( ! $theme->exists() ) {
		return;
	}

	add_filter(
		'option_stylesheet',
		function () use ( $theme ) {
			return $theme['Stylesheet'];
		}
	);

	add_filter(
		'option_template',
		function () use ( $theme ) {
			return $theme['Template'];
		}
	);
}
add_action( 'nab_nab/theme_load_alternative', __NAMESPACE__ . '\load_alternative' );
