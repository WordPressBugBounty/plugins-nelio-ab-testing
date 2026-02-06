<?php

namespace Nelio_AB_Testing\Experiment_Library\Popup_Experiment;

defined( 'ABSPATH' ) || exit;

/**
 * Callback to backup control.
 *
 * @param TAttributes               $backup  Backup.
 * @param TPopup_Control_Attributes $control Control.
 *
 * @return TPopup_Alternative_Attributes
 */
function backup_control( $backup, $control ) {
	return array(
		'name'   => '',
		'postId' => $control['postId'],
	);
}
add_filter( 'nab_nab/popup_backup_control', __NAMESPACE__ . '\backup_control', 10, 2 );
