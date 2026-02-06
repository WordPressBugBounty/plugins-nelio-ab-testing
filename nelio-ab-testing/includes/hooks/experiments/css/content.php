<?php

namespace Nelio_AB_Testing\Experiment_Library\Css_Experiment;

defined( 'ABSPATH' ) || exit;

/**
 * Callback to duplicate alternative.
 *
 * @param TCss_Alternative_Attributes $dest   Dest.
 * @param TCss_Alternative_Attributes $source Source.
 *
 * @return TCss_Alternative_Attributes
 */
function duplicate_alternative( $dest, $source ) {
	return $source;
}
add_filter( 'nab_nab/css_duplicate_alternative_content', __NAMESPACE__ . '\duplicate_alternative', 10, 2 );

/**
 * Callback to backup control.
 *
 * @return TCss_Alternative_Attributes
 */
function backup_control() {
	return array(
		'name' => '',
		'css'  => '',
	);
}
add_filter( 'nab_nab/css_backup_control', __NAMESPACE__ . '\backup_control' );
