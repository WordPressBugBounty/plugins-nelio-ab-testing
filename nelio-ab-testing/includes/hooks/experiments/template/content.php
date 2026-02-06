<?php

namespace Nelio_AB_Testing\Experiment_Library\Template_Experiment;

defined( 'ABSPATH' ) || exit;

use function add_filter;

/**
 * Callback to backup control.
 *
 * @param TAttributes                  $backup  Backup.
 * @param TTemplate_Control_Attributes $control Control.
 *
 * @return TTemplate_Alternative_Attributes
 */
function backup_control( $backup, $control ) {
	$backup = array(
		'templateId' => $control['templateId'],
		'name'       => $control['name'],
	);
	return $backup;
}
add_filter( 'nab_nab/template_backup_control', __NAMESPACE__ . '\backup_control', 10, 2 );
