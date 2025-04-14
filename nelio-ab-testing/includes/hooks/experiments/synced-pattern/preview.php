<?php

namespace Nelio_AB_Testing\Experiment_Library\Synced_Pattern_Experiment;

use function add_filter;

defined( 'ABSPATH' ) || exit;

function get_preview_link( $preview_link, $alternative ) {
	$pattern_id = nab_array_get( $alternative, 'patternId', 0 );
	$pattern    = get_post( $pattern_id );
	return $pattern
		? add_query_arg( 'nab-synced-pattern-preview-mode', 'true', nab_home_url() )
		: $preview_link;
}//end get_preview_link()
add_filter( 'nab_nab/synced-pattern_preview_link_alternative', __NAMESPACE__ . '\get_preview_link', 10, 2 );

function preview_alternative( $alternative, $control, $experiment_id ) {
	if ( ! isset( $_GET['nab-synced-pattern-preview-mode'] ) ) { //phpcs:ignore
		load_alternative( $alternative, $control, $experiment_id );
		return;
	}//end if

	add_filter(
		'template_include',
		function () {
			return nelioab()->plugin_path . '/includes/hooks/experiments/synced-pattern/preview-template.php';
		}
	);

	add_action(
		'nab_preview_synced_pattern',
		function () use ( &$alternative ) {
			$pattern_id = nab_array_get( $alternative, 'patternId', 0 );
			echo render_block_core_block( array( 'ref' => $pattern_id ) ); // phpcs:ignore
		}
	);
}//end preview_alternative()
add_action( 'nab_nab/synced-pattern_preview_alternative', __NAMESPACE__ . '\preview_alternative', 10, 3 );
