<?php

namespace Nelio_AB_Testing\Experiment_Library\Post_Experiment;

defined( 'ABSPATH' ) || exit;

use Nelio_AB_Testing_Settings;

/**
 * Whether we should use the control ID in alternative content or not.
 *
 * The value comes from a setting, but it’s also filtered.
 *
 * @return bool
 */
function use_control_id_in_alternative() {
	$settings       = Nelio_AB_Testing_Settings::instance();
	$use_control_id = ! empty( $settings->get( 'use_control_id_in_alternative' ) );

	/**
	 * Whether we should use the original post ID when loading an alternative post or not.
	 *
	 * @param bool $use_control_id whether we should use the original post ID or not.
	 *
	 * @since 5.0.4
	 */
	return apply_filters( 'nab_use_control_id_in_alternative', $use_control_id );
}
