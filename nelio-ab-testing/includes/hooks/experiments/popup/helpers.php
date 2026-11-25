<?php

namespace Nelio_AB_Testing\Experiment_Library\Popup_Experiment;

defined( 'ABSPATH' ) || exit;

/**
 * Gets the popup post types that can be tested.
 *
 * @return list<string> Popup post types.
 */
function get_testable_popup_types() {
	/**
	 * Filters the popup post types that can be tested.
	 *
	 * @param list<string> $post_types Popup post types.
	 *
	 * @since 9.0.0
	 */
	return apply_filters( 'nab_get_testable_popup_types', array() );
}
