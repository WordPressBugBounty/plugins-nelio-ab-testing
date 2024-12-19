<?php

namespace Nelio_AB_Testing\Compat\Leadpages;

defined( 'ABSPATH' ) || exit;

use Nelio_AB_Testing_Public;
use Nelio_AB_Testing_Alternative_Loader;
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
		}//end if
		add_filter( 'leadpages_html', __NAMESPACE__ . '\maybe_add_public_scripts' );
		add_filter( 'leadpages_html', __NAMESPACE__ . '\maybe_add_heatmap_scripts' );
		add_filter( 'leadpages_html', __NAMESPACE__ . '\maybe_add_css_selector_scripts' );
	}
);

function maybe_add_public_scripts( $html ) {

	if ( nab_is_split_testing_disabled() ) {
		return $html;
	}//end if

	enqueue_head_and_footer_scripts(
		array(
			array( Nelio_AB_Testing_Public::instance(), 'add_kickoff_script' ),
			array( Nelio_AB_Testing_Alternative_Loader::instance(), 'add_alternative_loader_script' ),
			array( Nelio_AB_Testing_Tracking::instance(), 'enqueue_tracking_script' ),
		),
		array(
			array( Nelio_AB_Testing_Tracking::instance(), 'add_script_for_tracking_later_page_views' ),
		)
	);

	$head_scripts   = get_head_scripts_as_html();
	$footer_scripts = get_footer_scripts_as_html();

	$html = str_replace( '<head>', "<head>\n{$head_scripts}", $html );
	$html = str_replace( '</body>', "{$footer_scripts}\n</body>", $html );
	return $html;
}//end maybe_add_public_scripts()

function maybe_add_heatmap_scripts( $html ) {

	if ( ! nab_is_heatmap() ) {
		return $html;
	}//end if

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
}//end maybe_add_heatmap_scripts()

function maybe_add_css_selector_scripts( $html ) {

	$aux = Nelio_AB_Testing_Css_Selector_Finder::instance();
	if ( ! $aux->should_css_selector_finder_be_loaded() ) {
		return $html;
	}//end if

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
}//end maybe_add_css_selector_scripts()

function enqueue_head_and_footer_scripts( $head_scripts, $footer_scripts ) {

	remove_all_filters( 'wp_head' );
	remove_all_filters( 'wp_footer' );

	foreach ( $head_scripts as $script ) {
		add_action( 'wp_head', $script );
	}//end foreach
	add_action( 'wp_head', 'wp_print_head_scripts' );

	foreach ( $footer_scripts as $script ) {
		add_action( 'wp_footer', $script );
	}//end foreach
	add_action( 'wp_footer', 'wp_print_footer_scripts' );
}//end enqueue_head_and_footer_scripts()

function get_head_scripts_as_html() {
	ob_start();
	wp_head();
	return ob_get_clean();
}//end get_head_scripts_as_html()

function get_footer_scripts_as_html() {
	ob_start();
	wp_footer();
	return ob_get_clean();
}//end get_footer_scripts_as_html()
