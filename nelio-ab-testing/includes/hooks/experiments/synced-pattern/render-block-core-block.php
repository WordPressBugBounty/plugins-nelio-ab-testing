<?php
namespace Nelio_AB_Testing\Experiment_Library\Synced_Pattern_Experiment;

defined( 'ABSPATH' ) || exit;

use function add_filter;

function render_block_core_block( $attributes ) {
	$original_render = function_exists( '\render_block_core_block' )
		? \render_block_core_block( $attributes )
		: '';
	return ! empty( $original_render )
		? $original_render
		: alternative_render_block_core_block( $attributes );
}//end render_block_core_block()

function alternative_render_block_core_block( $attributes ) {
	// NOTE. This function is duplicated from wp-includes/blocks/block.php
	// The only thing different is the required post status for rendering:
	// instead of “publish”, it expects “nab_hidden”.

	static $seen_refs = array();

	if ( empty( $attributes['ref'] ) ) {
		return '';
	}//end if

	$reusable_block = get_post( $attributes['ref'] );
	if ( ! $reusable_block || 'wp_block' !== $reusable_block->post_type ) {
		return '';
	}//end if

	if ( isset( $seen_refs[ $attributes['ref'] ] ) ) {
		// WP_DEBUG_DISPLAY must only be honored when WP_DEBUG. This precedent
		// is set in `wp_debug_mode()`.
		$is_debug = WP_DEBUG && WP_DEBUG_DISPLAY;

		return $is_debug ?
			// translators: Visible only in the front end, this warning takes the place of a faulty block.
			__( '[block rendering halted]' ) :
			'';
	}//end if

	if ( 'nab_hidden' !== $reusable_block->post_status || ! empty( $reusable_block->post_password ) ) {
		return '';
	}//end if

	$seen_refs[ $attributes['ref'] ] = true;

	// Handle embeds for reusable blocks.
	global $wp_embed;
	$content = $wp_embed->run_shortcode( $reusable_block->post_content );
	$content = $wp_embed->autoembed( $content );

	// Back compat.
	// For blocks that have not been migrated in the editor, add some back compat
	// so that front-end rendering continues to work.

	// This matches the `v2` deprecation. Removes the inner `values` property
	// from every item.
	if ( isset( $attributes['content'] ) ) {
		foreach ( $attributes['content'] as &$content_data ) {
			if ( isset( $content_data['values'] ) ) {
				$is_assoc_array = is_array( $content_data['values'] ) && ! wp_is_numeric_array( $content_data['values'] );

				if ( $is_assoc_array ) {
					$content_data = $content_data['values'];
				}//end if
			}//end if
		}//end foreach
	}//end if

	// This matches the `v1` deprecation. Rename `overrides` to `content`.
	if ( isset( $attributes['overrides'] ) && ! isset( $attributes['content'] ) ) {
		$attributes['content'] = $attributes['overrides'];
	}//end if

	/**
	 * We set the `pattern/overrides` context through the `render_block_context`
	 * filter so that it is available when a pattern's inner blocks are
	 * rendering via do_blocks given it only receives the inner content.
	 */
	$has_pattern_overrides = isset( $attributes['content'] ) && null !== get_block_bindings_source( 'core/pattern-overrides' );
	if ( $has_pattern_overrides ) {
		$filter_block_context = static function ( $context ) use ( $attributes ) {
			$context['pattern/overrides'] = $attributes['content'];
			return $context;
		};
		add_filter( 'render_block_context', $filter_block_context, 1 );
	}//end if

	$content = do_blocks( $content );
	unset( $seen_refs[ $attributes['ref'] ] );

	if ( $has_pattern_overrides ) {
		remove_filter( 'render_block_context', $filter_block_context, 1 );
	}//end if

	return $content;
}//end alternative_render_block_core_block()
