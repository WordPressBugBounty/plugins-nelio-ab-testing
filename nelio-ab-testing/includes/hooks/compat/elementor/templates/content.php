<?php

namespace Nelio_AB_Testing\Compat\Elementor\Templates;

defined( 'ABSPATH' ) || exit;

use function add_action;
use function add_filter;

/**
 * Extends template contexts to include Elementor’s.
 *
 * @param array<string,TTemplate_Context_Group> $template_contexts Template contexts.
 *
 * @return array<string,TTemplate_Context_Group>
 */
function extend_template_contexts( $template_contexts ) {
	unset( $template_contexts['wp']['contexts']['e-landing-page'] );
	unset( $template_contexts['wp']['contexts']['elementor_library'] );

	$supported_type_labels = array(
		'error-404'      => _x( '404 Error Page', 'text', 'nelio-ab-testing' ),
		'archive'        => _x( 'Archives', 'text', 'nelio-ab-testing' ),
		'header'         => _x( 'Headers', 'text', 'nelio-ab-testing' ),
		'footer'         => _x( 'Footers', 'text', 'nelio-ab-testing' ),
		'single-post'    => _x( 'Single Posts', 'text', 'nelio-ab-testing' ),
		'single-page'    => _x( 'Single Pages', 'text', 'nelio-ab-testing' ),
		'search-results' => _x( 'Search Results', 'text', 'nelio-ab-testing' ),
		'product'        => _x( 'Product', 'text', 'nelio-ab-testing' ),
	);
	$supported_types       = array_keys( $supported_type_labels );

	// Get Elementor global templates.
	$templates = get_elementor_global_templates();
	$templates = array_filter(
		$templates,
		function ( $t ) use ( $supported_types ) {
			return in_array( $t['type'], $supported_types, true );
		}
	);
	$templates = array_values( $templates );

	if ( empty( $templates ) ) {
		return $template_contexts;
	}

	$elementor_contexts = array_map(
		function ( $t ) use ( $supported_type_labels ) {
			return array(
				'label' => $supported_type_labels[ $t['type'] ],
				'name'  => $t['type'],
			);
		},
		$templates
	);
	$elementor_contexts = array_combine(
		array_map( fn( $c ) => $c['name'], $elementor_contexts ),
		$elementor_contexts
	);

	$template_contexts['elementor'] = array(
		'label'    => 'Elementor',
		'contexts' => $elementor_contexts,
	);

	return $template_contexts;
}

/**
 * Extends templates to include Elementor’s.
 *
 * @param array<string,list<TTemplate>> $templates Templates.
 *
 * @return array<string,list<TTemplate>>
 */
function extend_templates( $templates ) {
	unset( $templates['wp:e-landing-page'] );
	unset( $templates['wp:elementor_library'] );

	// Remove Elementor core templates.
	$templates = array_map(
		function ( $items ) {
			return array_values(
				array_filter(
					$items,
					function ( $item ) {
						return ! in_array( $item['id'], array( 'elementor_canvas', 'elementor_header_footer', 'elementor_theme' ) );
					}
				)
			);
		},
		$templates
	);

	// Get Elementor global templates.
	$elementor_templates = get_elementor_global_templates();
	$elementor_templates = array_map(
		function ( $t ) {
			return array(
				'id'   => $t['template_id'],
				'name' => $t['title'],
				'type' => $t['type'],
			);
		},
		$elementor_templates
	);

	foreach ( $elementor_templates as $template ) {
		$type = "elementor:{$template['type']}";

		if ( ! array_key_exists( $type, $templates ) ) {
			$templates[ $type ] = array();
		}

		array_push(
			$templates[ $type ],
			array(
				'id'   => "{$template['id']}",
				'name' => $template['name'],
			)
		);
	}

	return $templates;
}

add_action(
	'plugins_loaded',
	function () {
		if ( ! did_action( 'elementor/loaded' ) ) {
			return;
		}

		add_filter( 'nab_template_contexts', __NAMESPACE__ . '\extend_template_contexts' );
		add_filter( 'nab_templates', __NAMESPACE__ . '\extend_templates' );
	}
);

/**
 * Returns elementor global templates.
 *
 * @return list<TElementor_Template>
 */
function get_elementor_global_templates() {
	return \Elementor\Plugin::instance()->templates_manager->get_templates( array( 'local' ) );
}
