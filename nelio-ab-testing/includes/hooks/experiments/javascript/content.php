<?php

namespace Nelio_AB_Testing\Experiment_Library\JavaScript_Experiment;

defined( 'ABSPATH' ) || exit;

/**
 * Callback to duplicate alternative.
 *
 * @param TJavaScript_Alternative_Attributes $dest   Dest.
 * @param TJavaScript_Alternative_Attributes $source Source.
 *
 * @return TJavaScript_Alternative_Attributes
 */
function duplicate_alternative( $dest, $source ) {
	return $source;
}
add_filter( 'nab_nab/javascript_duplicate_alternative_content', __NAMESPACE__ . '\duplicate_alternative', 10, 2 );

/**
 * Callback to backup control.
 *
 * @return TJavaScript_Alternative_Attributes
 */
function backup_control() {
	return array(
		'name' => '',
		'code' => '',
	);
}
add_filter( 'nab_nab/javascript_backup_control', __NAMESPACE__ . '\backup_control' );
