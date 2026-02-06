<?php

namespace Nelio_AB_Testing\Compat\Leadpages;

defined( 'ABSPATH' ) || exit;

use Nelio_AB_Testing_Main_Script;
use Nelio_AB_Testing_Tracking;
use Nelio_AB_Testing_Heatmap_Renderer;
use Nelio_AB_Testing_Css_Selector_Finder;

use function add_action;
use function add_filter;
use function class_exists;

add_action(
	'plugins_loaded',
	function () {
		if ( ! class_exists( 'LeadpagesWP\Admin\CustomPostTypes\LeadpagesPostType' ) ) {
			return;
		}
		add_filter( 'leadpages_html', __NAMESPACE__ . '\maybe_add_public_scripts' );
		add_filter( 'leadpages_html', __NAMESPACE__ . '\maybe_add_heatmap_scripts' );
		add_filter( 'leadpages_html', __NAMESPACE__ . '\maybe_add_css_selector_scripts' );
	}
);

/**
 * Adds public scripts to the HTML.
 *
 * @param string $html HTML.
 *
 * @return string
 */
function maybe_add_public_scripts( $html ) {

	if ( nab_is_split_testing_disabled() ) {
		return $html;
	}

	$main = Nelio_AB_Testing_Main_Script::instance();
	enqueue_head_and_footer_scripts(
		array(
			array( $main, 'enqueue_script' ),
			array( $main, 'enqueue_visitor_type_script' ),
		),
		array(
			array( Nelio_AB_Testing_Tracking::instance(), 'maybe_print_inline_script_to_track_footer_views' ),
		)
	);

	$head_scripts   = get_head_scripts_as_html();
	$footer_scripts = get_footer_scripts_as_html();

	$html = str_replace( '<head>', "<head>\n{$head_scripts}", $html );
	$html = str_replace( '</body>', "{$footer_scripts}\n</body>", $html );
	return $html;
}

/**
 * Adds heatmap scripts to HTML if needed.
 *
 * @param string $html HTML.
 *
 * @return string
 */
function maybe_add_heatmap_scripts( $html ) {

	if ( ! nab_is_heatmap() ) {
		return $html;
	}

	enqueue_head_and_footer_scripts(
		array(
			array( Nelio_AB_Testing_Heatmap_Renderer::instance(), 'enqueue_assets' ),
		),
		array()
	);

	$head_scripts   = get_head_scripts_as_html();
	$footer_scripts = get_footer_scripts_as_html();

	$html = str_replace( '<head>', "<head>\n{$head_scripts}", $html );
	$html = str_replace( '</body>', "{$footer_scripts}\n</body>", $html );
	return $html;
}

/**
 * Adds CSS selector scripts to HTML if needed.
 *
 * @param string $html HTML.
 *
 * @return string
 */
function maybe_add_css_selector_scripts( $html ) {

	$aux = Nelio_AB_Testing_Css_Selector_Finder::instance();
	if ( ! $aux->should_css_selector_finder_be_loaded() ) {
		return $html;
	}

	enqueue_head_and_footer_scripts(
		array(
			array( Nelio_AB_Testing_Css_Selector_Finder::instance(), 'enqueue_assets' ),
		),
		array()
	);

	$head_scripts   = get_head_scripts_as_html();
	$footer_scripts = get_footer_scripts_as_html();

	$html = str_replace( '<head>', "<head>\n{$head_scripts}", $html );
	$html = str_replace( '</body>', "{$footer_scripts}\n</body>", $html );
	return $html;
}

/**
 * Enqueues head and footer scripts.
 *
 * @param list<Callable():void> $head_scripts   Head scripts.
 * @param list<Callable():void> $footer_scripts Footer scripts.
 *
 * @return void
 */
function enqueue_head_and_footer_scripts( $head_scripts, $footer_scripts ) {

	remove_all_filters( 'wp_head' );
	remove_all_filters( 'wp_footer' );

	foreach ( $head_scripts as $script ) {
		add_action( 'wp_head', $script );
	}
	// @phpstan-ignore-next-line
	add_action( 'wp_head', 'wp_print_head_scripts' );

	foreach ( $footer_scripts as $script ) {
		add_action( 'wp_footer', $script );
	}
	add_action( 'wp_footer', 'wp_print_footer_scripts' );
}

/**
 * Returns head scripts.
 *
 * @return string
 */
function get_head_scripts_as_html() {
	ob_start();
	wp_head();
	$result = ob_get_clean();
	return is_string( $result ) ? $result : '';
}

/**
 * Returns footer scripts.
 *
 * @return string
 */
function get_footer_scripts_as_html() {
	ob_start();
	wp_footer();
	$result = ob_get_clean();
	return is_string( $result ) ? $result : '';
}
