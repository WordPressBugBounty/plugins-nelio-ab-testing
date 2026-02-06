<?php

namespace Nelio_AB_Testing\Experiment_Library\Synced_Pattern_Experiment;

use function add_filter;

defined( 'ABSPATH' ) || exit;

/**
 * Callback to get preview link.
 *
 * @param string|false                                                              $preview_link   Preview link.
 * @param TSynced_Pattern_Alternative_Attributes|TSynced_Pattern_Control_Attributes $alternative    Alternative.
 *
 * @return string|false
 */
function get_preview_link( $preview_link, $alternative ) {
	$pattern_id = $alternative['patternId'];
	$pattern    = get_post( $pattern_id );
	return $pattern
		? add_query_arg( 'nab-synced-pattern-preview-mode', 'true', nab_home_url() )
		: $preview_link;
}
add_filter( 'nab_nab/synced-pattern_preview_link_alternative', __NAMESPACE__ . '\get_preview_link', 10, 2 );

/**
 * Callback to add hooks to preview alternative.
 *
 * @param TSynced_Pattern_Alternative_Attributes|TSynced_Pattern_Control_Attributes $alternative   Alternative.
 * @param TSynced_Pattern_Control_Attributes                                        $control       Control.
 * @param int                                                                       $experiment_id Experiment ID.
 *
 * @return void
 */
function preview_alternative( $alternative, $control, $experiment_id ) {
	// phpcs:ignore WordPress.Security.NonceVerification.Recommended
	if ( ! isset( $_GET['nab-synced-pattern-preview-mode'] ) ) {
		load_alternative( $alternative, $control, $experiment_id );
		return;
	}

	add_filter(
		'template_include',
		function () {
			return nelioab()->plugin_path . '/includes/hooks/experiments/synced-pattern/preview-template.php';
		}
	);

	add_action(
		'nab_preview_synced_pattern',
		function () use ( &$alternative ) {
			$post = get_post( $alternative['patternId'] );
			if ( is_null( $post ) ) {
				return;
			}
			// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			echo do_blocks( $post->post_content );
		}
	);
}
add_action( 'nab_nab/synced-pattern_preview_alternative', __NAMESPACE__ . '\preview_alternative', 10, 3 );
