<?php

namespace Nelio_AB_Testing\Compat\Hubspot;

defined( 'ABSPATH' ) || exit;

use function add_filter;
use function add_action;
use function is_plugin_active;

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
}//end add_form_types()

function get_hubspot_form( $post, $post_id, $post_type ) {
	if ( null !== $post ) {
		return $post;
	}//end if

	if ( 'nab_hubspot_form' !== $post_type ) {
		return $post;
	}//end if

	return new \WP_Error(
		'not-found',
		_x( 'HubSpot forms are not exposed through this endpoint.', 'text', 'nelio-ab-testing' )
	);
}//end get_hubspot_form()

function get_hubspot_forms( $result, $post_type ) {
	if ( null !== $result ) {
		return $result;
	}//end if

	if ( 'nab_hubspot_form' !== $post_type ) {
		return $result;
	}//end if

	return array();
}//end get_hubspot_forms()

add_action(
	'plugins_loaded',
	function () {
		if ( ! function_exists( 'is_plugin_active' ) ) {
			include_once ABSPATH . 'wp-admin/includes/plugin.php';
		}//end if

		/**
		 * Whether hubspot forms should be available in conversion actions or not.
		 *
		 * @param boolean $enabled whether hubspot forms should be available in conversion actions or not.
		 *
		 * @since 6.3.0
		 */
		if ( ! apply_filters( 'nab_are_hubspot_forms_enabled', is_plugin_active( 'leadin/leadin.php' ) ) ) {
			return;
		}//end if

		add_filter( 'nab_get_post_types', __NAMESPACE__ . '\add_form_types' );
		add_filter( 'nab_pre_get_post', __NAMESPACE__ . '\get_hubspot_form', 10, 3 );
		add_filter( 'nab_pre_get_posts', __NAMESPACE__ . '\get_hubspot_forms', 10, 2 );
	}
);
