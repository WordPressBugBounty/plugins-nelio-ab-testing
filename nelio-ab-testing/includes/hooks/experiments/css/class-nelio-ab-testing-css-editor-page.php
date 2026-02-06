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

	/**
	 * Experiment ID.
	 *
	 * @var int
	 */
	private $experiment_id;

	/**
	 * Alternative ID.
	 *
	 * @var string
	 */
	private $alternative_id;

	/**
	 * Hooks into WordPress.
	 *
	 * @return void
	 */
	public function init() {
		add_action( 'admin_init', array( $this, 'extract_params_from_url_or_die' ) );
		add_action( 'current_screen', array( $this, 'display' ), 99 );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_assets' ) );
		add_submenu_page( '__nelio-ab-testing', '', '', 'edit_nab_experiments', 'nelio-ab-testing-css-editor', '' );
	}

	/**
	 * Callback to validate that all params are valid and, if they aren’t, die.
	 *
	 * @return void
	 */
	public function extract_params_from_url_or_die() {
		if ( ! $this->is_current_screen_this_page() ) {
			return;
		}

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$experiment_id = absint( $_GET['experiment'] ?? '' );
		if ( empty( $experiment_id ) ) {
			wp_die( esc_html_x( 'Missing test ID.', 'text', 'nelio-ab-testing' ) );
		}

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$alternative_id = sanitize_text_field( wp_unslash( $_GET['alternative'] ?? '' ) );
		if ( empty( $alternative_id ) ) {
			wp_die( esc_html_x( 'Missing CSS Variant ID.', 'text', 'nelio-ab-testing' ) );
		}

		$experiment = nab_get_experiment( $experiment_id );
		if ( is_wp_error( $experiment ) ) {
			wp_die( esc_html_x( 'You attempted to edit a test that doesn’t exist. Perhaps it was deleted?', 'user', 'nelio-ab-testing' ) );
		}

		if ( 'control' === $alternative_id ) {
			wp_die( esc_html_x( 'Control version can’t be edited.', 'user', 'nelio-ab-testing' ) );
		}

		$alternative = array_filter( $experiment->get_alternatives(), fn( $a ) => $a['id'] === $alternative_id );
		if ( empty( $alternative ) ) {
			wp_die( esc_html_x( 'You attempted to edit a CSS variant that doesn’t exist. Perhaps it was deleted?', 'user', 'nelio-ab-testing' ) );
		}

		if ( 'nab/css' !== $experiment->get_type() ) {
			wp_die( esc_html_x( 'Test variant is not a CSS variant.', 'user', 'nelio-ab-testing' ) );
		}

		$this->experiment_id  = $experiment_id;
		$this->alternative_id = $alternative_id;
	}

	/**
	 * Callback to enqueue editor assets.
	 *
	 * @return void
	 */
	public function enqueue_assets() {

		if ( ! $this->is_current_screen_this_page() ) {
			return;
		}

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

		wp_enqueue_media();
		wp_enqueue_script( 'nab-css-experiment-admin' );
		wp_add_inline_script(
			'nab-css-experiment-admin',
			sprintf(
				$script,
				wp_json_encode( $settings )
			)
		);

		wp_print_media_templates();
		wp_enqueue_style( 'nab-components' );
		wp_enqueue_style( 'nab-css-experiment-admin' );
	}

	/**
	 * Callback to render this page if it’s the current page.
	 *
	 * @return void
	 */
	public function display() {
		if ( ! $this->is_current_screen_this_page() ) {
			return;
		}

		include_once nelioab()->plugin_path . '/admin/views/nelio-ab-testing-css-editor-page.php';
		die();
	}

	/**
	 * Whether the current page is this page.
	 *
	 * @return bool
	 */
	private function is_current_screen_this_page() {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		return 'nelio-ab-testing-css-editor' === sanitize_text_field( wp_unslash( $_GET['page'] ?? '' ) );
	}
}
