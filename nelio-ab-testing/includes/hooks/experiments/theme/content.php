<?php

namespace Nelio_AB_Testing\Experiment_Library\Theme_Experiment;

defined( 'ABSPATH' ) || exit;

use function add_filter;
use function wp_get_theme;

/**
 * Callback to backup control.
 *
 * @param TAttributes $backup Backup.
 *
 * @return TTheme_Alternative_Attributes
 */
function backup_control( $backup ) {
	$theme  = wp_get_theme();
	$name   = $theme->display( 'name' );
	$backup = array(
		'themeId' => $theme->get_stylesheet(),
		'name'    => is_string( $name ) ? $name : $theme->get_stylesheet(),
	);
	return $backup;
}
add_filter( 'nab_nab/theme_backup_control', __NAMESPACE__ . '\backup_control', 10 );

/**
 * Callback to apply alternative.
 *
 * @param bool                          $applied Applied.
 * @param TTheme_Alternative_Attributes $alternative Alternative.
 *
 * @return bool
 */
function apply_alternative( $applied, $alternative ) {
	$theme = wp_get_theme( $alternative['themeId'] );
	if ( ! $theme->exists() ) {
		return false;
	}

	switch_theme( $alternative['themeId'] );
	return true;
}
add_filter( 'nab_nab/theme_apply_alternative', __NAMESPACE__ . '\apply_alternative', 10, 2 );
