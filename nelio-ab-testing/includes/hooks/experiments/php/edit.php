<?php

namespace Nelio_AB_Testing\Experiment_Library\Php_Experiment;

defined( 'ABSPATH' ) || exit;

/**
 * Callback to get the edit link.
 *
 * @param string|false                                        $edit_link      Edit link.
 * @param TPhp_Alternative_Attributes|TPhp_Control_Attributes $alternative    Alternative.
 * @param TPhp_Control_Attributes                             $control        Control.
 * @param int                                                 $experiment_id  Experiment ID.
 * @param string                                              $alternative_id Alternative ID.
 *
 * @return string|false
 */
function get_edit_link( $edit_link, $alternative, $control, $experiment_id, $alternative_id ) {

	if ( 'control' === $alternative_id ) {
		return false;
	}

	return add_query_arg(
		array(
			'page'        => 'nelio-ab-testing-php-editor',
			'experiment'  => $experiment_id,
			'alternative' => $alternative_id,
		),
		admin_url( 'admin.php' )
	);
}
add_filter( 'nab_nab/php_edit_link_alternative', __NAMESPACE__ . '\get_edit_link', 10, 5 );

/**
 * Callback to register admin assets.
 *
 * @return void
 */
function register_admin_assets() {

	nab_register_script_with_auto_deps( 'nab-php-experiment-admin', 'php-experiment-admin', true );

	wp_register_style(
		'nab-php-experiment-admin',
		nelioab()->plugin_url . '/assets/dist/css/php-experiment-admin.css',
		array( 'wp-admin', 'wp-components' ),
		nelioab()->plugin_version
	);
}
add_action( 'admin_enqueue_scripts', __NAMESPACE__ . '\register_admin_assets' );

/**
 * Callback to register the PHP Editor page in the Dashboard.
 *
 * @return void
 */
function add_php_editor_page() {
	$page = new Nelio_AB_Testing_Php_Editor_Page();
	$page->init();
}
add_action( 'admin_menu', __NAMESPACE__ . '\add_php_editor_page' );
