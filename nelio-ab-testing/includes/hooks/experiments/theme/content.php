<?php

namespace Nelio_AB_Testing\Experiment_Library\Theme_Experiment;

defined( 'ABSPATH' ) || exit;

use function add_filter;
use function wp_get_theme;

function backup_control( $backup ) {

	$theme  = wp_get_theme();
	$backup = array(
		'themeId' => $theme['Stylesheet'],
		'name'    => $theme['Name'],
	);
	return $backup;
}//end backup_control()
add_filter( 'nab_nab/theme_backup_control', __NAMESPACE__ . '\backup_control', 10 );

function apply_alternative( $applied, $alternative ) {

	$theme = wp_get_theme( $alternative['themeId'] );
	if ( empty( $theme ) || is_wp_error( $theme ) ) {
		return false;
	}//end if

	switch_theme( $alternative['themeId'] );
	return true;
}//end apply_alternative()
add_filter( 'nab_nab/theme_apply_alternative', __NAMESPACE__ . '\apply_alternative', 10, 2 );
