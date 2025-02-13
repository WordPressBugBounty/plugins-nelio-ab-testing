<?php
/**
 * This file contains the class that defines the Alternative CSS Editor Page.
 *
 * @package    Nelio_AB_Testing
 * @subpackage Nelio_AB_Testing/admin/pages
 * @since      5.0.0
 */

namespace Nelio_AB_Testing\Experiment_Library\Css_Experiment;

use function add_action;
use function esc_html_x;
use function nelioab;
use function sanitize_text_field;
use function wp_add_inline_script;
use function wp_die;
use function wp_enqueue_script;
use function wp_enqueue_style;
use function wp_json_encode;
use function wp_unslash;

defined( 'ABSPATH' ) || exit;

/**
 * Class that defines the Alternative CSS Editor Page.
 *
 * @package    Nelio_AB_Testing
 * @subpackage Nelio_AB_Testing/admin/pages
 * @since      5.0.0
 */
class Nelio_AB_Testing_Css_Editor_Page {

	private $experiment_id;
	private $alternative_id;

	public function init() {

		add_action( 'admin_init', array( $this, 'extract_params_from_url_or_die' ) );
		add_action( 'current_screen', array( $this, 'display' ), 99 );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_assets' ) );
		add_submenu_page( '__nelio-ab-testing', '', '', 'edit_nab_experiments', 'nelio-ab-testing-css-editor', '' );
	}//end init()

	public function extract_params_from_url_or_die() {

		if ( ! $this->is_current_screen_this_page() ) {
			return;
		}//end if

		if ( empty( $_GET['experiment'] ) || ! absint( $_GET['experiment'] ) ) { // phpcs:ignore
			wp_die( esc_html_x( 'Missing test ID.', 'text', 'nelio-ab-testing' ) );
		}//end if

		if ( empty( $_GET['alternative'] ) || empty( sanitize_text_field( wp_unslash( $_GET['alternative'] ) ) ) ) { // phpcs:ignore
			wp_die( esc_html_x( 'Missing CSS Variant ID.', 'text', 'nelio-ab-testing' ) );
		}//end if

		$experiment_id = absint( $_GET['experiment'] ); // phpcs:ignore
		$experiment    = nab_get_experiment( $experiment_id );
		if ( empty( $experiment ) || is_wp_error( $experiment ) ) {
			wp_die( esc_html_x( 'You attempted to edit a test that doesn’t exist. Perhaps it was deleted?', 'user', 'nelio-ab-testing' ) );
		}//end if

		$alternative_id = sanitize_text_field( wp_unslash( $_GET['alternative'] ) ); // phpcs:ignore
		if ( 'control' === $alternative_id ) {
			wp_die( esc_html_x( 'Control version can’t be edited.', 'user', 'nelio-ab-testing' ) );
		}//end if

		$alternative = array_values(
			array_filter(
				$experiment->get_alternatives(),
				function ( $alternative ) use ( $alternative_id ) {
					return $alternative['id'] === $alternative_id;
				}
			)
		)[0];

		if ( empty( $alternative ) ) {
			wp_die( esc_html_x( 'You attempted to edit a CSS variant that doesn’t exist. Perhaps it was deleted?', 'user', 'nelio-ab-testing' ) );
		}//end if

		if ( 'nab/css' !== $experiment->get_type() ) {
			wp_die( esc_html_x( 'Test variant is not a CSS variant.', 'user', 'nelio-ab-testing' ) );
		}//end if

		$this->experiment_id  = $experiment_id;
		$this->alternative_id = $alternative_id;
	}//end extract_params_from_url_or_die()

	public function enqueue_assets() {

		if ( ! $this->is_current_screen_this_page() ) {
			return;
		}//end if

		$script = '
		( function() {
			wp.domReady( function() {
				nab.initCssEditorPage( "nab-css-editor", %s );
			} );
		} )();';

		$settings = array(
			'experimentId'  => $this->experiment_id,
			'alternativeId' => $this->alternative_id,
		);

		wp_enqueue_script( 'nab-css-experiment-admin' );
		wp_add_inline_script(
			'nab-css-experiment-admin',
			sprintf(
				$script,
				wp_json_encode( $settings ) // phpcs:ignore
			)
		);

		wp_enqueue_style( 'nab-components' );
		wp_enqueue_style( 'nab-css-experiment-admin' );
	}//end enqueue_assets()

	public function display() {

		if ( ! $this->is_current_screen_this_page() ) {
			return;
		}//end if

		// phpcs:ignore
		include_once nelioab()->plugin_path . '/admin/views/nelio-ab-testing-css-editor-page.php';
		die();
	}//end display()

	private function is_current_screen_this_page() {

		if ( empty( $_GET['page'] ) ) { // phpcs:ignore
			return false;
		}//end if

		return 'nelio-ab-testing-css-editor' === sanitize_text_field( wp_unslash( $_GET['page'] ) ); // phpcs:ignore
	}//end is_current_screen_this_page()
}//end class
