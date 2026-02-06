<?php

namespace Nelio_AB_Testing\Compat\Forms\Hubspot;

defined( 'ABSPATH' ) || exit;

use function add_filter;
use function add_action;
use function is_plugin_active;

/**
 * Adds a new form type.
 *
 * @param array<string,TPost_Type> $data Post types.
 *
 * @return array<string,TPost_Type>
 */
function add_form_types( $data ) {
	$data['nab_hubspot_form'] = array(
		'name'   => 'nab_hubspot_form',
		'label'  => _x( 'HubSpot Form', 'text', 'nelio-ab-testing' ),
		'labels' => array(
			'singular_name' => _x( 'HubSpot Form', 'text', 'nelio-ab-testing' ),
		),
		'kind'   => 'form',
	);
	return $data;
}

/**
 * Callback to return the appropriate form.
 *
 * @param null|TPost|\WP_Post|\WP_Error $post      The post to filter.
 * @param int|string                    $post_id   The id of the post.
 * @param string                        $post_type The post type.
 *
 * @return null|TPost|\WP_Post|\WP_Error
 */
function get_hubspot_form( $post, $post_id, $post_type ) {
	if ( null !== $post ) {
		return $post;
	}

	if ( 'nab_hubspot_form' !== $post_type ) {
		return $post;
	}

	return new \WP_Error(
		'not-found',
		_x( 'HubSpot forms are not exposed through this endpoint.', 'text', 'nelio-ab-testing' )
	);
}

/**
 * Returns the list of forms matching the search query.
 *
 * @param null|array{results:list<TPost>, pagination: array{more:bool, pages:int}} $result    The result data.
 * @param string                                                                   $post_type The post type.
 *
 * @return null|array{results:list<TPost>, pagination: array{more:bool, pages:int}}
 */
function get_hubspot_forms( $result, $post_type ) {
	if ( null !== $result ) {
		return $result;
	}

	if ( 'nab_hubspot_form' !== $post_type ) {
		return $result;
	}

	return array(
		'results'    => array(),
		'pagination' => array(
			'more'  => false,
			'pages' => 0,
		),
	);
}

add_action(
	'plugins_loaded',
	function () {
		if ( ! function_exists( 'is_plugin_active' ) ) {
			nab_require_wp_file( '/wp-admin/includes/plugin.php' );
		}

		/**
		 * Whether hubspot forms should be available in conversion actions or not.
		 *
		 * @param boolean $enabled whether hubspot forms should be available in conversion actions or not.
		 *
		 * @since 6.3.0
		 */
		if ( ! apply_filters( 'nab_are_hubspot_forms_enabled', is_plugin_active( 'leadin/leadin.php' ) ) ) {
			return;
		}

		add_filter( 'nab_get_post_types', __NAMESPACE__ . '\add_form_types' );
		add_filter( 'nab_pre_get_post', __NAMESPACE__ . '\get_hubspot_form', 10, 3 );
		add_filter( 'nab_pre_get_posts', __NAMESPACE__ . '\get_hubspot_forms', 10, 2 );
	}
);
