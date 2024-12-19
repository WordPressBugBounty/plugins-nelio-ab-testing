<?php

namespace Nelio_AB_Testing\Experiment_Library\Template_Experiment;

defined( 'ABSPATH' ) || exit;

use function add_filter;

function backup_control( $backup, $control ) {

	$backup = array(
		'templateId' => $control['templateId'],
		'name'       => $control['name'],
	);
	return $backup;
}//end backup_control()
add_filter( 'nab_nab/template_backup_control', __NAMESPACE__ . '\backup_control', 10, 2 );
