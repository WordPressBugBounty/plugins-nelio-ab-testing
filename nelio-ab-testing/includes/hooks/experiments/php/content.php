<?php

namespace Nelio_AB_Testing\Experiment_Library\Php_Experiment;

defined( 'ABSPATH' ) || exit;

/**
 * Callback to backup control.
 *
 * @return TPhp_Alternative_Attributes
 */
function backup_control() {
	return array(
		'name'    => '',
		'snippet' => '',
	);
}
add_filter( 'nab_nab/php_backup_control', __NAMESPACE__ . '\backup_control' );
