<?php

namespace Nelio_AB_Testing\Experiment_Library\Url_Experiment;

defined( 'ABSPATH' ) || exit;

use function add_filter;

/**
 * Callback to backup control.
 *
 * @param TAttributes             $backup  Backup.
 * @param TUrl_Control_Attributes $control Control.
 *
 * @return TUrl_Alternative_Attributes
 */
function backup_control( $backup, $control ) {
	return array(
		'name' => '',
		'url'  => $control['url'],
	);
}
add_filter( 'nab_nab/url_backup_control', __NAMESPACE__ . '\backup_control', 10, 2 );

// Heatmap link is essentially the preview link which will need some extra params to load the heatmap renderer on top of it.
add_filter( 'nab_nab/url_heatmap_link_alternative', __NAMESPACE__ . '\get_preview_link', 10, 2 );
